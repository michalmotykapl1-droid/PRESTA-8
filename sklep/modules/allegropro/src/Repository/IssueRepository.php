<?php
namespace AllegroPro\Repository;

use Db;

class IssueRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_issue';
    }


    /**
     * Zwraca MAX aktywności (UNIX ts) dla issue w DB:
     * COALESCE(last_message_at, updated_at_allegro, created_at_allegro)
     * Używane do szybkiej synchronizacji przyrostowej (delta) przy wejściu w Korespondencję.
     */
    public function getAccountMaxActivityTs(int $accountId, ?string $cutoffMysql = null): int
    {
        $where = 'id_allegropro_account=' . (int)$accountId;
        if ($cutoffMysql) {
            $where .= " AND COALESCE(last_message_at, updated_at_allegro, created_at_allegro) >= '" . pSQL($cutoffMysql) . "'";
        }

        $val = Db::getInstance()->getValue(
            'SELECT UNIX_TIMESTAMP(MAX(COALESCE(last_message_at, updated_at_allegro, created_at_allegro))) FROM `' . pSQL($this->table) . '` WHERE ' . $where
        );

        return (int)($val ?: 0);
    }

    public function upsertFromApi(int $accountId, array $issue): bool
    {
        $issueId = (string)($issue['id'] ?? '');
        if ($issueId === '') {
            return false;
        }

        $type = strtoupper(trim($this->toScalarString($issue['type'] ?? '')));

        // Status w /sale/issues bywa zwracany jako string lub obiekt (np. {code:"DISPUTE_ONGOING"}).
        // Dlatego wyciągamy wartość w sposób „odporny” i normalizujemy.
        $statusRaw = '';
        if (isset($issue['currentState']) && is_array($issue['currentState']) && isset($issue['currentState']['status'])) {
            $statusRaw = $this->toScalarString($issue['currentState']['status']);
        }
        if ($statusRaw === '' && isset($issue['status'])) {
            $statusRaw = $this->toScalarString($issue['status']);
        }
        $status = $this->normalizeStatus($type, $statusRaw);

        $checkoutFormId = null;
        if (isset($issue['checkoutForm']) && is_array($issue['checkoutForm']) && !empty($issue['checkoutForm']['id'])) {
            $checkoutFormId = (string)$issue['checkoutForm']['id'];
        }

        $buyerLogin = null;
        if (isset($issue['buyer']) && is_array($issue['buyer']) && !empty($issue['buyer']['login'])) {
            $buyerLogin = (string)$issue['buyer']['login'];
        }
        if (!$buyerLogin && isset($issue['customer']) && is_array($issue['customer']) && !empty($issue['customer']['login'])) {
            $buyerLogin = (string)$issue['customer']['login'];
        }

        // Allegro w tutorialu zwraca m.in. openedDate; trzymamy to jako created_at_allegro.
        $createdAt = $this->isoToMysql($issue['createdAt'] ?? null);
        if ($createdAt === null) {
            $createdAt = $this->isoToMysql($issue['openedDate'] ?? null);
        }

        $updatedAt = $this->isoToMysql($issue['updatedAt'] ?? null);
        if ($updatedAt === null) {
            // fallback: często brak updatedAt w odpowiedzi listy
            $updatedAt = $createdAt;
        }

        $lastStatus = null;
        $lastMsgAt = null;
        if (isset($issue['chat']) && is_array($issue['chat'])) {
            if (isset($issue['chat']['lastMessage']) && is_array($issue['chat']['lastMessage'])) {
                $lastStatus = isset($issue['chat']['lastMessage']['status']) ? $this->toScalarString($issue['chat']['lastMessage']['status']) : null;
                $lastMsgAt = $this->isoToMysql($issue['chat']['lastMessage']['createdAt'] ?? null);
            }
        }

        $decisionDue = $this->isoToMysql($issue['decisionDueDate'] ?? null);
        $statusDue = null;
        $returnRequired = 0;
        if (isset($issue['currentState']) && is_array($issue['currentState'])) {
            $statusDue = $this->isoToMysql($issue['currentState']['statusDueDate'] ?? null);
            $returnRequired = !empty($issue['currentState']['returnRequired']) ? 1 : 0;
        }

        $right = null;
        if (!empty($issue['right'])) {
            $right = (string)$issue['right'];
        }

        $expRefund = 0;
        $expPartialRefund = 0;
        $expExchange = 0;
        $expRepair = 0;
        if (isset($issue['expectations']) && is_array($issue['expectations'])) {
            foreach ($issue['expectations'] as $exp) {
                if (!is_array($exp)) {
                    continue;
                }
                $name = (string)($exp['name'] ?? '');
                if ($name === 'REFUND') $expRefund = 1;
                if ($name === 'PARTIAL_REFUND') $expPartialRefund = 1;
                if ($name === 'EXCHANGE') $expExchange = 1;
                if ($name === 'REPAIR') $expRepair = 1;
            }
        }

        $payload = json_encode($issue, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now = date('Y-m-d H:i:s');

        // Warunek minimalizacji update'u (delta): aktualizuj tylko gdy dane się zmieniły / nowsze
        $condChanged = "((VALUES(last_message_at) IS NOT NULL AND (last_message_at IS NULL OR VALUES(last_message_at) > last_message_at))
            OR (VALUES(updated_at_allegro) IS NOT NULL AND (updated_at_allegro IS NULL OR VALUES(updated_at_allegro) > updated_at_allegro))
            OR NOT (VALUES(status) <=> status)
            OR NOT (VALUES(last_message_status) <=> last_message_status)
            OR NOT (VALUES(status_due_date) <=> status_due_date)
            OR NOT (VALUES(decision_due_date) <=> decision_due_date))";

        $sql = 'INSERT INTO `' . pSQL($this->table) . '` (
                    `id_allegropro_account`, `issue_id`, `type`, `status`, `checkout_form_id`, `buyer_login`,
                    `created_at_allegro`, `updated_at_allegro`,
                    `last_message_status`, `last_message_at`,
                    `decision_due_date`, `status_due_date`,
                    `return_required`, `right_type`,
                    `exp_refund`, `exp_partial_refund`, `exp_exchange`, `exp_repair`,
                    `payload_json`, `is_synced`, `synced_at`, `created_at`, `updated_at`
                ) VALUES (
                    ' . (int)$accountId . ',
                    \'' . pSQL($issueId) . '\',
                    \'' . pSQL($type) . '\',
                    \'' . pSQL($status) . '\',
                    ' . ($checkoutFormId === null ? 'NULL' : ('\'' . pSQL($checkoutFormId) . '\'')) . ',
                    ' . ($buyerLogin === null ? 'NULL' : ('\'' . pSQL($buyerLogin) . '\'')) . ',
                    ' . ($createdAt === null ? 'NULL' : ('\'' . pSQL($createdAt) . '\'')) . ',
                    ' . ($updatedAt === null ? 'NULL' : ('\'' . pSQL($updatedAt) . '\'')) . ',
                    ' . ($lastStatus === null ? 'NULL' : ('\'' . pSQL((string)$lastStatus) . '\'')) . ',
                    ' . ($lastMsgAt === null ? 'NULL' : ('\'' . pSQL($lastMsgAt) . '\'')) . ',
                    ' . ($decisionDue === null ? 'NULL' : ('\'' . pSQL($decisionDue) . '\'')) . ',
                    ' . ($statusDue === null ? 'NULL' : ('\'' . pSQL($statusDue) . '\'')) . ',
                    ' . (int)$returnRequired . ',
                    ' . ($right === null ? 'NULL' : ('\'' . pSQL($right) . '\'')) . ',
                    ' . (int)$expRefund . ',
                    ' . (int)$expPartialRefund . ',
                    ' . (int)$expExchange . ',
                    ' . (int)$expRepair . ',
                    ' . ($payload ? ('\'' . pSQL($payload, true) . '\'') : 'NULL') . ',
                    1,
                    \'' . pSQL($now) . '\',
                    \'' . pSQL($now) . '\',
                    \'' . pSQL($now) . '\'
                )
                ON DUPLICATE KEY UPDATE
                    `type` = IF(' . $condChanged . ', VALUES(`type`), `type`),
                    `status` = IF(' . $condChanged . ', VALUES(`status`), `status`),
                    checkout_form_id = IF(' . $condChanged . ', VALUES(checkout_form_id), checkout_form_id),
                    buyer_login = IF(' . $condChanged . ', VALUES(buyer_login), buyer_login),
                    created_at_allegro = IF(' . $condChanged . ', VALUES(created_at_allegro), created_at_allegro),
                    updated_at_allegro = IF(' . $condChanged . ', VALUES(updated_at_allegro), updated_at_allegro),
                    last_message_status = IF(' . $condChanged . ', VALUES(last_message_status), last_message_status),
                    last_message_at = IF(' . $condChanged . ', VALUES(last_message_at), last_message_at),
                    decision_due_date = IF(' . $condChanged . ', VALUES(decision_due_date), decision_due_date),
                    status_due_date = IF(' . $condChanged . ', VALUES(status_due_date), status_due_date),
                    return_required = IF(' . $condChanged . ', VALUES(return_required), return_required),
                    right_type = IF(' . $condChanged . ', VALUES(right_type), right_type),
                    exp_refund = IF(' . $condChanged . ', VALUES(exp_refund), exp_refund),
                    exp_partial_refund = IF(' . $condChanged . ', VALUES(exp_partial_refund), exp_partial_refund),
                    exp_exchange = IF(' . $condChanged . ', VALUES(exp_exchange), exp_exchange),
                    exp_repair = IF(' . $condChanged . ', VALUES(exp_repair), exp_repair),
                    payload_json = IF(' . $condChanged . ', VALUES(payload_json), payload_json),
                    updated_at = IF(' . $condChanged . ', VALUES(updated_at), updated_at),
                    is_synced = 1,
                    synced_at = IF(synced_at IS NULL, VALUES(synced_at), synced_at)';

        return (bool)Db::getInstance()->execute($sql);
    }

    public function counts(): array
    {
        $cutoff = $this->getCutoffMysql();
        $whereSql = $cutoff ? (" WHERE COALESCE(last_message_at, updated_at_allegro, created_at_allegro) >= '" . pSQL($cutoff) . "'") : '';

        // Db::getRow() w PrestaShop może dopiąć własne LIMIT 1, więc NIE dodajemy LIMIT ręcznie.
        $row = Db::getInstance()->getRow('SELECT
                COUNT(*) AS iss_all,
                SUM(CASE WHEN type = \'DISPUTE\' THEN 1 ELSE 0 END) AS iss_dispute_all,
                SUM(CASE WHEN type = \'CLAIM\' THEN 1 ELSE 0 END) AS iss_claim_all,

                SUM(CASE WHEN last_message_status = \'NEW\' THEN 1 ELSE 0 END) AS iss_new,
                SUM(CASE WHEN type = \'DISPUTE\' AND last_message_status = \'NEW\' THEN 1 ELSE 0 END) AS iss_dispute_new,
                SUM(CASE WHEN type = \'CLAIM\' AND last_message_status = \'NEW\' THEN 1 ELSE 0 END) AS iss_claim_new,

                SUM(CASE WHEN (last_message_status IN (\'NEW\',\'BUYER_REPLIED\',\'ALLEGRO_ADVISOR_REPLIED\')
                          AND NOT (
                            (type=\'DISPUTE\' AND status IN (\'DISPUTE_CLOSED\',\'CLOSED\'))
                            OR (type=\'CLAIM\' AND status IN (\'CLAIM_ACCEPTED\',\'ACCEPTED\',\'CLAIM_REJECTED\',\'REJECTED\'))
                          ))
                    THEN 1 ELSE 0 END) AS iss_waiting_me,

                SUM(CASE WHEN (type=\'DISPUTE\' AND last_message_status IN (\'NEW\',\'BUYER_REPLIED\',\'ALLEGRO_ADVISOR_REPLIED\')
                          AND status NOT IN (\'DISPUTE_CLOSED\',\'CLOSED\'))
                    THEN 1 ELSE 0 END) AS iss_dispute_waiting,

                SUM(CASE WHEN (type=\'CLAIM\' AND last_message_status IN (\'NEW\',\'BUYER_REPLIED\',\'ALLEGRO_ADVISOR_REPLIED\')
                          AND status NOT IN (\'CLAIM_ACCEPTED\',\'ACCEPTED\',\'CLAIM_REJECTED\',\'REJECTED\'))
                    THEN 1 ELSE 0 END) AS iss_claim_waiting,

                SUM(CASE WHEN ((status_due_date IS NOT NULL AND status_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)) OR (decision_due_date IS NOT NULL AND decision_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR))) THEN 1 ELSE 0 END) AS iss_due_soon,
                SUM(CASE WHEN (type=\'DISPUTE\' AND ((status_due_date IS NOT NULL AND status_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)) OR (decision_due_date IS NOT NULL AND decision_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)))) THEN 1 ELSE 0 END) AS iss_dispute_due_soon,
                SUM(CASE WHEN (type=\'CLAIM\' AND ((status_due_date IS NOT NULL AND status_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)) OR (decision_due_date IS NOT NULL AND decision_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)))) THEN 1 ELSE 0 END) AS iss_claim_due_soon,

                SUM(CASE WHEN type = \'DISPUTE\' AND status IN (\'DISPUTE_ONGOING\',\'ONGOING\') THEN 1 ELSE 0 END) AS iss_dispute_ongoing,
                SUM(CASE WHEN type = \'DISPUTE\' AND status IN (\'DISPUTE_UNRESOLVED\',\'UNRESOLVED\') THEN 1 ELSE 0 END) AS iss_dispute_unresolved,
                SUM(CASE WHEN type = \'DISPUTE\' AND status IN (\'DISPUTE_CLOSED\',\'CLOSED\') THEN 1 ELSE 0 END) AS iss_dispute_closed,

                SUM(CASE WHEN type = \'CLAIM\' AND status IN (\'CLAIM_SUBMITTED\',\'SUBMITTED\') THEN 1 ELSE 0 END) AS iss_claim_submitted,
                SUM(CASE WHEN type = \'CLAIM\' AND status IN (\'CLAIM_ACCEPTED\',\'ACCEPTED\') THEN 1 ELSE 0 END) AS iss_claim_accepted,
                SUM(CASE WHEN type = \'CLAIM\' AND status IN (\'CLAIM_REJECTED\',\'REJECTED\') THEN 1 ELSE 0 END) AS iss_claim_rejected,

                SUM(CASE WHEN exp_refund = 1 THEN 1 ELSE 0 END) AS iss_expect_refund,
                SUM(CASE WHEN exp_partial_refund = 1 THEN 1 ELSE 0 END) AS iss_expect_partial_refund,
                SUM(CASE WHEN exp_exchange = 1 THEN 1 ELSE 0 END) AS iss_expect_exchange,
                SUM(CASE WHEN exp_repair = 1 THEN 1 ELSE 0 END) AS iss_expect_repair,

                SUM(CASE WHEN return_required = 1 THEN 1 ELSE 0 END) AS iss_return_required,
                SUM(CASE WHEN right_type = \'WARRANTY\' THEN 1 ELSE 0 END) AS iss_right_warranty,
                SUM(CASE WHEN right_type = \'COMPLAINT\' THEN 1 ELSE 0 END) AS iss_right_complaint

            FROM `' . pSQL($this->table) . '`' . $whereSql);

        $issAll = (int)($row['iss_all'] ?? 0);

        return [
            'issues_all' => $issAll,
            'iss_all' => $issAll,
            'iss_dispute_all' => (int)($row['iss_dispute_all'] ?? 0),
            'iss_claim_all' => (int)($row['iss_claim_all'] ?? 0),
            'iss_dispute_new' => (int)($row['iss_dispute_new'] ?? 0),
            'iss_claim_new' => (int)($row['iss_claim_new'] ?? 0),
            'iss_dispute_waiting' => (int)($row['iss_dispute_waiting'] ?? 0),
            'iss_claim_waiting' => (int)($row['iss_claim_waiting'] ?? 0),
            'iss_dispute_due_soon' => (int)($row['iss_dispute_due_soon'] ?? 0),
            'iss_claim_due_soon' => (int)($row['iss_claim_due_soon'] ?? 0),
            'iss_new' => (int)($row['iss_new'] ?? 0),
            'iss_waiting_me' => (int)($row['iss_waiting_me'] ?? 0),
            'iss_due_soon' => (int)($row['iss_due_soon'] ?? 0),
            'iss_dispute_ongoing' => (int)($row['iss_dispute_ongoing'] ?? 0),
            'iss_dispute_unresolved' => (int)($row['iss_dispute_unresolved'] ?? 0),
            'iss_dispute_closed' => (int)($row['iss_dispute_closed'] ?? 0),
            'iss_claim_submitted' => (int)($row['iss_claim_submitted'] ?? 0),
            'iss_claim_accepted' => (int)($row['iss_claim_accepted'] ?? 0),
            'iss_claim_rejected' => (int)($row['iss_claim_rejected'] ?? 0),
            'iss_expect_refund' => (int)($row['iss_expect_refund'] ?? 0),
            'iss_expect_partial_refund' => (int)($row['iss_expect_partial_refund'] ?? 0),
            'iss_expect_exchange' => (int)($row['iss_expect_exchange'] ?? 0),
            'iss_expect_repair' => (int)($row['iss_expect_repair'] ?? 0),
            'iss_return_required' => (int)($row['iss_return_required'] ?? 0),
            'iss_right_warranty' => (int)($row['iss_right_warranty'] ?? 0),
            'iss_right_complaint' => (int)($row['iss_right_complaint'] ?? 0),
        ];
    }


    /**
     * Pobiera pojedyncze zgłoszenie z bazy (konto + issue_id).
     * Przydaje się do podglądu metadanych po kliknięciu na listę.
     */
    public function getOne(int $accountId, string $issueId): ?array
    {
        $issueId = trim($issueId);
        if ($accountId <= 0 || $issueId === '') {
            return null;
        }

        // Db::getRow() w PrestaShop może dopiąć własne LIMIT 1, więc NIE dodajemy LIMIT ręcznie.
        $row = Db::getInstance()->getRow('SELECT
                id_allegropro_account,
                issue_id,
                type,
                status,
                checkout_form_id,
                buyer_login,
                created_at_allegro,
                updated_at_allegro,
                last_message_status,
                last_message_at,
                decision_due_date,
                status_due_date,
                return_required,
                right_type,
                exp_refund,
                exp_partial_refund,
                exp_exchange,
                exp_repair
            FROM `' . pSQL($this->table) . '`
            WHERE id_allegropro_account=' . (int)$accountId . " AND issue_id='" . pSQL($issueId) . "'"
            );

        return $row ?: null;
    }

    /**
     * Naprawcze uzupełnienie pól pochodnych (np. status) z payload_json dla istniejących rekordów.
     *
     * Po wcześniejszych etapach rozwoju mogły powstać rekordy bez currentState.status (bo API nie ma pola issue.status).
     * Ta metoda pozwala szybko uzupełnić dane BEZ dodatkowych requestów do Allegro.
     */
    public function enrichMissingFromPayload(?string $cutoffMysql = null, int $limit = 2000): array
    {
        $limit = max(1, min(10000, (int)$limit));

        $where = [
            "(status IS NULL OR status='' OR status='Array' OR status='ARRAY')",
            "payload_json IS NOT NULL",
            "payload_json <> ''",
        ];
        if ($cutoffMysql) {
            $where[] = "COALESCE(last_message_at, updated_at_allegro, created_at_allegro) >= '" . pSQL($cutoffMysql) . "'";
        }

        $whereSql = implode(' AND ', $where);

        $rows = Db::getInstance()->executeS('SELECT id_allegropro_account, payload_json FROM `' . pSQL($this->table) . '` WHERE ' . $whereSql . ' LIMIT ' . (int)$limit) ?: [];

        $processed = 0;
        $upserted = 0;
        $decodeErrors = 0;

        foreach ($rows as $r) {
            $accId = (int)($r['id_allegropro_account'] ?? 0);
            $json = (string)($r['payload_json'] ?? '');
            if ($accId <= 0 || $json === '') {
                continue;
            }

            $issue = json_decode($json, true);
            if (!is_array($issue)) {
                $decodeErrors++;
                continue;
            }

            $processed++;
            if ($this->upsertFromApi($accId, $issue)) {
                $upserted++;
            }
        }

        $pending = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . pSQL($this->table) . '` WHERE ' . $whereSql);

        return [
            'processed' => $processed,
            'upserted' => $upserted,
            'decode_errors' => $decodeErrors,
            'pending' => $pending,
        ];
    }



    /**
     * Liczba zgłoszeń w DB (opcjonalnie ograniczona do okresu cutoff).
     * Przydatne do narzędzi developerskich (np. pełna re-segregacja już pobranych rekordów).
     */
    public function countInRange(?string $cutoffMysql = null): int
    {
        $where = '1=1';
        if ($cutoffMysql) {
            $where .= " AND COALESCE(last_message_at, updated_at_allegro, created_at_allegro) >= '" . pSQL($cutoffMysql) . "'";
        }
        return (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . pSQL($this->table) . '` WHERE ' . $where);
    }

    /**
     * Pobiera paczkę zgłoszeń do przeliczenia pól pochodnych (segregacja) na podstawie payload_json.
     */
    public function fetchBatchForReseg(int $limit = 200, int $offset = 0, ?string $cutoffMysql = null): array
    {
        $limit = max(1, min(500, (int)$limit));
        $offset = max(0, (int)$offset);

        $where = '1=1';
        if ($cutoffMysql) {
            $where .= " AND COALESCE(last_message_at, updated_at_allegro, created_at_allegro) >= '" . pSQL($cutoffMysql) . "'";
        }

        return Db::getInstance()->executeS('SELECT
                id_allegropro_issue,
                id_allegropro_account,
                issue_id,
                payload_json
            FROM `' . pSQL($this->table) . '`
            WHERE ' . $where . '
            ORDER BY id_allegropro_issue ASC
            LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset) ?: [];
    }
    public function list(string $filter, string $q = '', int $limit = 50, int $offset = 0): array
    {
        $filter = $filter !== '' ? $filter : 'iss_all';
        $limit = max(1, min(200, (int)$limit));
        $offset = max(0, (int)$offset);

        $meta = [
            'filter' => $filter,
            'q' => $q,
            'filterReady' => true,
            'note' => '',
        ];

        if (!$this->isFilterReady($filter)) {
            $meta['filterReady'] = false;
            $meta['note'] = 'Ten filtr będzie w pełni aktywny po dopięciu dodatkowych danych z issue.currentState / permissions (kolejny etap).';
            return [
                'data' => ['items' => [], 'total' => 0, 'limit' => $limit, 'offset' => $offset],
                'meta' => $meta,
            ];
        }

        $where = ['1=1'];

        // Globalny cutoff z ustawień (ostatnie X miesięcy)
        $cutoff = $this->getCutoffMysql();
        if ($cutoff) {
            $where[] = "COALESCE(last_message_at, updated_at_allegro, created_at_allegro) >= '" . pSQL($cutoff) . "'";
        }

        switch ($filter) {
            // globalne filtry (dla wszystkich typów)
            case 'iss_new':
                $where[] = "last_message_status = 'NEW'";
                break;
            case 'iss_waiting_me':
                $where[] = "(last_message_status IN ('NEW','BUYER_REPLIED','ALLEGRO_ADVISOR_REPLIED')
                           AND NOT (
                             (type='DISPUTE' AND status IN ('DISPUTE_CLOSED','CLOSED'))
                             OR (type='CLAIM' AND status IN ('CLAIM_ACCEPTED','ACCEPTED','CLAIM_REJECTED','REJECTED'))
                           ))";
                break;
            case 'iss_due_soon':
                $where[] = "((status_due_date IS NOT NULL AND status_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)) OR (decision_due_date IS NOT NULL AND decision_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)))";
                break;

            // (NOWE) – dyskusje (DISPUTE)
            case 'iss_dispute_all':
                $where[] = "type='DISPUTE'";
                break;
            case 'iss_dispute_new':
                $where[] = "type='DISPUTE' AND last_message_status='NEW'";
                break;
            case 'iss_dispute_waiting':
                $where[] = "(type='DISPUTE' AND last_message_status IN ('NEW','BUYER_REPLIED','ALLEGRO_ADVISOR_REPLIED')
                           AND status NOT IN ('DISPUTE_CLOSED','CLOSED'))";
                break;
            case 'iss_dispute_due_soon':
                $where[] = "(type='DISPUTE' AND ((status_due_date IS NOT NULL AND status_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)) OR (decision_due_date IS NOT NULL AND decision_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR))))";
                break;
            case 'iss_dispute_ongoing':
                $where[] = "type='DISPUTE' AND status IN ('DISPUTE_ONGOING','ONGOING')";
                break;
            case 'iss_dispute_unresolved':
                $where[] = "type='DISPUTE' AND status IN ('DISPUTE_UNRESOLVED','UNRESOLVED')";
                break;
            case 'iss_dispute_closed':
                $where[] = "type='DISPUTE' AND status IN ('DISPUTE_CLOSED','CLOSED')";
                break;

            // (NOWE) – reklamacje (CLAIM)
            case 'iss_claim_all':
                $where[] = "type='CLAIM'";
                break;
            case 'iss_claim_new':
                $where[] = "type='CLAIM' AND last_message_status='NEW'";
                break;
            case 'iss_claim_waiting':
                $where[] = "(type='CLAIM' AND last_message_status IN ('NEW','BUYER_REPLIED','ALLEGRO_ADVISOR_REPLIED')
                           AND status NOT IN ('CLAIM_ACCEPTED','ACCEPTED','CLAIM_REJECTED','REJECTED'))";
                break;
            case 'iss_claim_due_soon':
                $where[] = "(type='CLAIM' AND ((status_due_date IS NOT NULL AND status_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR)) OR (decision_due_date IS NOT NULL AND decision_due_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 48 HOUR))))";
                break;
            case 'iss_claim_submitted':
                $where[] = "type='CLAIM' AND status IN ('CLAIM_SUBMITTED','SUBMITTED')";
                break;
            case 'iss_claim_accepted':
                $where[] = "type='CLAIM' AND status IN ('CLAIM_ACCEPTED','ACCEPTED')";
                break;
            case 'iss_claim_rejected':
                $where[] = "type='CLAIM' AND status IN ('CLAIM_REJECTED','REJECTED')";
                break;

            // dodatkowe pola / oczekiwania
            case 'iss_expect_refund':
                $where[] = "exp_refund=1";
                break;
            case 'iss_expect_partial_refund':
                $where[] = "exp_partial_refund=1";
                break;
            case 'iss_expect_exchange':
                $where[] = "exp_exchange=1";
                break;
            case 'iss_expect_repair':
                $where[] = "exp_repair=1";
                break;
            case 'iss_return_required':
                $where[] = "return_required=1";
                break;
            case 'iss_right_warranty':
                $where[] = "right_type='WARRANTY'";
                break;
            case 'iss_right_complaint':
                $where[] = "right_type='COMPLAINT'";
                break;

            case 'iss_all':
            default:
                break;
        }

        $q = trim($q);
        if ($q !== '') {
            $like = '%' . pSQL($q) . '%';
            $where[] = "(issue_id LIKE '" . $like . "' OR buyer_login LIKE '" . $like . "' OR checkout_form_id LIKE '" . $like . "')";
        }

        $whereSql = implode(' AND ', $where);

        $total = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . pSQL($this->table) . '` WHERE ' . $whereSql);

        $accTable = _DB_PREFIX_ . 'allegropro_account';

        $items = Db::getInstance()->executeS('SELECT
                i.id_allegropro_account,
                a.label AS account_label,
                i.issue_id,
                i.type,
                i.status,
                i.checkout_form_id,
                i.buyer_login,
                i.last_message_status,
                i.last_message_at,
                COALESCE(i.last_message_at, i.updated_at_allegro, i.created_at_allegro) AS activity_at,
                i.decision_due_date,
                i.status_due_date,
                i.return_required,
                i.right_type,
                i.exp_refund,
                i.exp_partial_refund,
                i.exp_exchange,
                i.exp_repair
            FROM `' . pSQL($this->table) . '` i
            LEFT JOIN `' . pSQL($accTable) . '` a ON a.id_allegropro_account = i.id_allegropro_account
            WHERE ' . $whereSql . '
            ORDER BY COALESCE(i.last_message_at, i.updated_at_allegro, i.created_at_allegro) DESC, i.issue_id DESC
            LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset) ?: [];

        return [
            'data' => [
                'items' => $items,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ],
            'meta' => $meta,
        ];
    }

    private function isFilterReady(string $filter): bool
    {
        // Wszystkie filtry dla zgłoszeń działają na polach zapisanych w DB.
        // (np. status, last_message_status, terminy).
        return true;
    }



    /**
     * Zamienia wartość z payloadu (string/number/array/object) na możliwie sensowny string.
     * Allegro API czasami zwraca pola jako obiekty (np. status {code: ...}).
     */
    private function toScalarString($val): string
    {
        if ($val === null) {
            return '';
        }
        if (is_string($val) || is_numeric($val)) {
            return (string)$val;
        }
        if (is_bool($val)) {
            return $val ? '1' : '0';
        }
        if (is_array($val)) {
            // Najczęstsze klucze w odpowiedziach API
            foreach (['code', 'value', 'name', 'status', 'id', 'key'] as $k) {
                if (isset($val[$k]) && (is_string($val[$k]) || is_numeric($val[$k]))) {
                    return (string)$val[$k];
                }
            }
            // Jeśli tablica ma 1 element skalarny
            foreach ($val as $v) {
                if (is_string($v) || is_numeric($v)) {
                    return (string)$v;
                }
            }
            return '';
        }
        if (is_object($val)) {
            // Na wypadek gdyby payload nie był z json_decode(true)
            return $this->toScalarString((array)$val);
        }
        return '';
    }

    /**
     * Normalizuje status tak, aby filtry/county działały stabilnie.
     * Czasami API zwraca skrócone statusy (np. ONGOING zamiast DISPUTE_ONGOING).
     */
    private function normalizeStatus(string $type, $statusRaw): string
    {
        $type = strtoupper(trim($type));
        $status = strtoupper(trim($this->toScalarString($statusRaw)));

        if ($status === '') {
            return '';
        }

        if ($type === 'DISPUTE') {
            if (in_array($status, ['ONGOING', 'UNRESOLVED', 'CLOSED'], true)) {
                return 'DISPUTE_' . $status;
            }
        }

        if ($type === 'CLAIM') {
            if (in_array($status, ['SUBMITTED', 'ACCEPTED', 'REJECTED'], true)) {
                return 'CLAIM_' . $status;
            }
        }

        return $status;
    }
    private function isoToMysql($iso): ?string
    {
        $iso = $iso !== null ? trim((string)$iso) : '';
        if ($iso === '') {
            return null;
        }
        $ts = strtotime($iso);
        if (!$ts) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }

    private function getCutoffMysql(): ?string
    {
        $months = (int)\Configuration::get('ALLEGROPRO_CORR_ISSUE_MONTHS');
        if ($months < 1) {
            $months = 12;
        }
        if ($months > 60) {
            $months = 60;
        }
        $ts = strtotime('-' . $months . ' months');
        if (!$ts) {
            return null;
        }
        return date('Y-m-d H:i:s', $ts);
    }
}
