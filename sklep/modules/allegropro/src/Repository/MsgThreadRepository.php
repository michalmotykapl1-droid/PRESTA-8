<?php
namespace AllegroPro\Repository;

use Db;

class MsgThreadRepository
{
    private string $table;

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_msg_thread';
    }

    /**
     * Zwraca MAX(last_message_at) jako timestamp (UNIX) dla danego konta.
     * Używane do szybkiej, przyrostowej synchronizacji (delta) – nie ma sensu
     * pobierać i przepisywać całego okresu przy każdym wejściu w Korespondencję.
     */
    public function getAccountMaxLastMessageTs(int $accountId, ?string $cutoffMysql = null): int
    {
        $where = 'id_allegropro_account=' . (int)$accountId;
        if ($cutoffMysql) {
            $where .= " AND last_message_at IS NOT NULL AND last_message_at >= '" . pSQL($cutoffMysql) . "'";
        }
        $val = Db::getInstance()->getValue(
            'SELECT UNIX_TIMESTAMP(MAX(last_message_at)) FROM `' . pSQL($this->table) . '` WHERE ' . $where
        );

        return (int)($val ?: 0);
    }

    /**
     * Upsert wątku z /messaging/threads.
     * - Na INSERT ustawiamy flagę is_synced=1
     * - Na UPDATE minimalizujemy przepisywanie: aktualizujemy tylko gdy last_message_at jest nowsze
     */
    public function upsertFromApi(int $accountId, array $thread): bool
    {
        $threadId = (string)($thread['id'] ?? '');
        if ($threadId === '') {
            return false;
        }

        $read = isset($thread['read']) ? (int)((bool)$thread['read']) : 1;
        $lastAtIso = (string)($thread['lastMessageDateTime'] ?? '');
        $lastAt = $this->isoToMysql($lastAtIso);

        $login = null;
        if (isset($thread['interlocutor']) && is_array($thread['interlocutor'])) {
            $login = $thread['interlocutor']['login'] ?? null;
        }
        if (!$login && isset($thread['interlocutorLogin'])) {
            $login = $thread['interlocutorLogin'];
        }
        $login = $login ? (string)$login : null;

        $payload = json_encode($thread, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now = date('Y-m-d H:i:s');

        // Warunek: aktualizuj rekord tylko jeśli mamy nowszy last_message_at
        $condNewer = "(VALUES(last_message_at) IS NOT NULL AND (last_message_at IS NULL OR VALUES(last_message_at) > last_message_at))";

        $sql = 'INSERT INTO `' . pSQL($this->table) . '` (
                    `id_allegropro_account`, `thread_id`, `interlocutor_login`, `read`, `last_message_at`,
                    `checkout_form_id`, `offer_id`, `payload_json`, `is_synced`, `synced_at`, `created_at`, `updated_at`
                ) VALUES (
                    ' . (int)$accountId . ',
                    \'' . pSQL($threadId) . '\',
                    ' . ($login === null ? 'NULL' : ('\'' . pSQL($login) . '\'')) . ',
                    ' . (int)$read . ',
                    ' . ($lastAt === null ? 'NULL' : ('\'' . pSQL($lastAt) . '\'')) . ',
                    NULL,
                    NULL,
                    ' . ($payload ? ('\'' . pSQL($payload, true) . '\'') : 'NULL') . ',
                    1,
                    \'' . pSQL($now) . '\',
                    \'' . pSQL($now) . '\',
                    \'' . pSQL($now) . '\'
                )
                ON DUPLICATE KEY UPDATE
                    interlocutor_login = VALUES(interlocutor_login),
                    `read` = IF(' . $condNewer . ', VALUES(`read`), `read`),
                    last_message_at = IF(' . $condNewer . ', VALUES(last_message_at), last_message_at),
                    payload_json = IF(' . $condNewer . ', VALUES(payload_json), payload_json),
                    updated_at = IF(' . $condNewer . ', VALUES(updated_at), updated_at),
                    is_synced = 1,
                    synced_at = IF(synced_at IS NULL, VALUES(synced_at), synced_at)';

        return (bool)Db::getInstance()->execute($sql);
    }

    /**
     * Pobiera pojedynczy wątek (konto + thread_id).
     */
    public function getOne(int $accountId, string $threadId): ?array
    {
        $row = Db::getInstance()->getRow('SELECT
                id_allegropro_account,
                thread_id,
                interlocutor_login,
                `read`,
                last_message_at,
                checkout_form_id,
                offer_id,
                is_synced,
                synced_at,
                messages_sync_complete,
                messages_sync_months
            FROM `' . pSQL($this->table) . '`
            WHERE id_allegropro_account=' . (int)$accountId . " AND thread_id='" . pSQL($threadId) . "'");

        return $row ?: null;
    }

    /**
     * Oznacza, że dla danego wątku mamy kompletną historię wiadomości
     * w ramach bieżącego zakresu (np. "ostatnie X miesięcy").
     *
     * Dzięki temu przy kolejnych wejściach możemy robić delta sync bez ryzyka,
     * że utnie starsze wypowiedzi w obrębie tego zakresu.
     */
    public function setMessagesSyncComplete(int $accountId, string $threadId, bool $complete, ?int $months = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE `' . pSQL($this->table) . '`
                SET messages_sync_complete=' . (int)($complete ? 1 : 0) . ',
                    messages_sync_months=' . ($months === null ? 'messages_sync_months' : (int)$months) . ',
                    updated_at=\'' . pSQL($now) . '\'
                WHERE id_allegropro_account=' . (int)$accountId . " AND thread_id='" . pSQL($threadId) . "'";
        return (bool)Db::getInstance()->execute($sql);
    }

    /**
     * Ustawia status przeczytania wątku w DB.
     */
    public function setRead(int $accountId, string $threadId, bool $read): bool
    {
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE `' . pSQL($this->table) . '`
                SET `read`=' . (int)$read . ', updated_at=\'' . pSQL($now) . '\'
                WHERE id_allegropro_account=' . (int)$accountId . " AND thread_id='" . pSQL($threadId) . "'";
        return (bool)Db::getInstance()->execute($sql);
    }

    /**
     * Uzupełnia relacje wątku do zamówienia/oferty na podstawie treści messages.
     * Ustawia tylko jeśli pola są puste (nie nadpisuje istniejących wartości).
     */
    public function setRelationsIfEmpty(int $accountId, string $threadId, ?string $checkoutFormId, ?string $offerId): bool
    {
        $set = [];
        if ($checkoutFormId) {
            $set[] = "checkout_form_id = IF(checkout_form_id IS NULL OR checkout_form_id='', '" . pSQL($checkoutFormId) . "', checkout_form_id)";
        }
        if ($offerId) {
            $set[] = "offer_id = IF(offer_id IS NULL OR offer_id='', '" . pSQL($offerId) . "', offer_id)";
        }
        if (empty($set)) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE `' . pSQL($this->table) . '`
                SET ' . implode(', ', $set) . ", updated_at='" . pSQL($now) . "'"
                . ' WHERE id_allegropro_account=' . (int)$accountId . " AND thread_id='" . pSQL($threadId) . "'";

        return (bool)Db::getInstance()->execute($sql);
    }

    public function counts(): array
    {
        $cutoff = $this->getCutoffMysql();
        $whereSql = $cutoff ? (" WHERE last_message_at IS NOT NULL AND last_message_at >= '" . pSQL($cutoff) . "'") : '';

        $row = Db::getInstance()->getRow('SELECT
                COUNT(*) AS msg_all,
                SUM(CASE WHEN `read` = 0 THEN 1 ELSE 0 END) AS msg_unread,
                SUM(CASE WHEN need_reply = 1 THEN 1 ELSE 0 END) AS msg_need_reply,
                SUM(CASE WHEN checkout_form_id IS NOT NULL AND checkout_form_id <> "" THEN 1 ELSE 0 END) AS msg_order,
                SUM(CASE WHEN offer_id IS NOT NULL AND offer_id <> "" THEN 1 ELSE 0 END) AS msg_offer,
                SUM(CASE WHEN (checkout_form_id IS NULL OR checkout_form_id = "") AND (offer_id IS NULL OR offer_id = "") THEN 1 ELSE 0 END) AS msg_general,
                SUM(CASE WHEN has_attachments = 1 THEN 1 ELSE 0 END) AS msg_attachments,
                SUM(CASE WHEN last_message_at IS NOT NULL AND last_message_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS msg_last24h,
                SUM(CASE WHEN last_message_at IS NOT NULL AND last_message_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS msg_last7d
            FROM `' . pSQL($this->table) . '`' . $whereSql);

        $msgAll = (int)($row['msg_all'] ?? 0);

        return [
            'messages_all' => $msgAll,
            'msg_all' => $msgAll,
            'msg_unread' => (int)($row['msg_unread'] ?? 0),
            'msg_need_reply' => (int)($row['msg_need_reply'] ?? 0),
            'msg_order' => (int)($row['msg_order'] ?? 0),
            'msg_offer' => (int)($row['msg_offer'] ?? 0),
            'msg_general' => (int)($row['msg_general'] ?? 0),
            'msg_attachments' => (int)($row['msg_attachments'] ?? 0),
            'msg_last24h' => (int)($row['msg_last24h'] ?? 0),
            'msg_last7d' => (int)($row['msg_last7d'] ?? 0),
        ];
    }

    public function list(string $filter, string $q = '', int $limit = 50, int $offset = 0): array
    {
        $filter = $filter !== '' ? $filter : 'msg_all';
        $limit = max(1, min(200, (int)$limit));
        $offset = max(0, (int)$offset);

        $meta = [
            'filter' => $filter,
            'q' => $q,
            'filterReady' => true,
            'note' => '',
        ];

        // Globalny cutoff z ustawień (ostatnie X miesięcy)
        $cutoff = $this->getCutoffMysql();

        // Filtry oparte o "treść" (need_reply / załączniki / powiązania) zależą od tego,
        // czy dany wątek miał już pobraną przynajmniej część wiadomości.
        $contentFilters = ['msg_need_reply', 'msg_order', 'msg_offer', 'msg_general', 'msg_attachments'];
        if (in_array($filter, $contentFilters, true)) {
            $missing = $this->countDerivedMissing($cutoff);
            if ($missing > 0) {
                $meta['note'] = 'Uwaga: część filtrów (np. „Wymaga odpowiedzi”, „Dot. zamówienia”) wymaga pobrania wiadomości w wątku. Moduł uzupełnia te dane automatycznie w tle (prefetch) i podczas otwierania wątku.';
            }
        }

        $where = ['1=1', 'is_synced=1'];

        if ($cutoff) {
            $where[] = "last_message_at IS NOT NULL AND last_message_at >= '" . pSQL($cutoff) . "'";
        }

        switch ($filter) {
            case 'msg_unread':
                $where[] = '`read` = 0';
                break;
            case 'msg_need_reply':
                $where[] = 'need_reply = 1';
                break;
            case 'msg_order':
                $where[] = 'checkout_form_id IS NOT NULL AND checkout_form_id <> ""';
                break;
            case 'msg_offer':
                $where[] = 'offer_id IS NOT NULL AND offer_id <> ""';
                break;
            case 'msg_general':
                $where[] = '(checkout_form_id IS NULL OR checkout_form_id = "") AND (offer_id IS NULL OR offer_id = "")';
                break;
            case 'msg_attachments':
                $where[] = 'has_attachments = 1';
                break;
            case 'msg_last24h':
                $where[] = 'last_message_at IS NOT NULL AND last_message_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
                break;
            case 'msg_last7d':
                $where[] = 'last_message_at IS NOT NULL AND last_message_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
                break;
            case 'msg_all':
            default:
                // no extra
                break;
        }

        $q = trim($q);
        if ($q !== '') {
            $like = '%' . pSQL($q) . '%';
            $where[] = "(thread_id LIKE '" . $like . "' OR interlocutor_login LIKE '" . $like . "' OR checkout_form_id LIKE '" . $like . "' OR offer_id LIKE '" . $like . "')";
        }

        $whereSql = implode(' AND ', $where);

        $total = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . pSQL($this->table) . '` WHERE ' . $whereSql);

        $acctTable = _DB_PREFIX_ . 'allegropro_account';

        $msgTable = _DB_PREFIX_ . 'allegropro_msg_message';

        $items = Db::getInstance()->executeS('SELECT
            t.id_allegropro_account,
            t.thread_id,
            t.interlocutor_login,
            t.read,
            t.last_message_at,
            t.checkout_form_id,
            t.offer_id,
            t.is_synced,
            t.messages_sync_complete,
            t.need_reply,
            t.has_attachments,
            a.label AS account_label,
            (SELECT m.text
             FROM `' . pSQL($msgTable) . '` m
             WHERE m.id_allegropro_account = t.id_allegropro_account
               AND m.thread_id = t.thread_id
             ORDER BY m.created_at_allegro DESC, m.id_allegropro_msg_message DESC
             LIMIT 1) AS last_message_text,
            (SELECT m.text
             FROM `' . pSQL($msgTable) . '` m
             WHERE m.id_allegropro_account = t.id_allegropro_account
               AND m.thread_id = t.thread_id
               AND m.author_is_interlocutor = 1
             ORDER BY m.created_at_allegro DESC, m.id_allegropro_msg_message DESC
             LIMIT 1) AS last_interlocutor_text
        FROM `' . pSQL($this->table) . '` t
        LEFT JOIN `' . pSQL($acctTable) . '` a ON a.id_allegropro_account = t.id_allegropro_account
        WHERE ' . $whereSql . '
        ORDER BY t.last_message_at DESC, t.need_reply DESC, t.`read` ASC, t.thread_id DESC
        LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset
        ) ?: [];

        foreach ($items as &$it) {
            $last = isset($it['last_message_text']) ? (string)$it['last_message_text'] : '';
            $buyer = isset($it['last_interlocutor_text']) ? (string)$it['last_interlocutor_text'] : '';
            $it['last_message_snippet'] = $this->makeSnippet($last, 180);
            $base = $buyer !== '' ? $buyer : $last;
            $it['sentiment'] = $this->computeSentiment($base);
            unset($it['last_message_text'], $it['last_interlocutor_text']);
        }
        unset($it);

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

    

    private function makeSnippet(string $text, int $maxLen = 160): string
    {
        if ($text === '') {
            return '';
        }
        // Decode HTML entities and strip tags (messages may contain HTML).
        $t = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = strip_tags($t);
        $t = preg_replace('/\s+/u', ' ', $t);
        $t = trim($t);

        if ($t === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($t, 'UTF-8') > $maxLen) {
                $t = mb_substr($t, 0, $maxLen, 'UTF-8') . '...';
            }
        } else {
            if (strlen($t) > $maxLen) {
                $t = substr($t, 0, $maxLen) . '...';
            }
        }

        return $t;
    }

    private function computeSentiment(string $text): string
    {
        $base = $this->makeSnippet($text, 500);
        if ($base === '') {
            return 'neu';
        }

        $t = function_exists('mb_strtolower') ? mb_strtolower($base, 'UTF-8') : strtolower($base);

        // Very lightweight heuristic sentiment (PL + common EN).
        $neg = [
            'reklamac', 'zwrot', 'uszkod', 'zepsut', 'zniszcz', 'brak', 'nie dotar', 'nie dosz', 'nie otrzym',
            'opoz', 'opóź', 'spoz', 'spóź', 'problem', 'skarga', 'oszust', 'nie polecam', 'trag', 'fatal', 'zly', 'zły',
            'wsciek', 'wściek', 'rozbit', 'pek', 'pęk', 'dziuraw', 'nie działa', 'nie dziala', 'refund', 'chargeback',
        ];
        $pos = [
            'dziekuj', 'dziękuj', 'super', 'swiet', 'świet', 'polecam', 'zadowol', 'ok', 'w porzadku', 'w porządku',
            'dobrze', 'dobra obs', 'szybko', 'perfekt', 'great', 'thanks', 'thank you',
        ];

        $score = 0;
        foreach ($neg as $w) {
            if ($w !== '' && strpos($t, $w) !== false) {
                $score -= 1;
            }
        }
        foreach ($pos as $w) {
            if ($w !== '' && strpos($t, $w) !== false) {
                $score += 1;
            }
        }

        if ($score >= 2) {
            return 'pos';
        }
        if ($score <= -2) {
            return 'neg';
        }
        return 'neu';
    }

private function isFilterReady(string $filter): bool
    {
        // Wszystkie filtry są dostępne, ale część wymaga pobranych wiadomości w wątku
        // (wtedy wartości need_reply / has_attachments / checkout_form_id mogą być jeszcze puste).
        $ready = [
            'msg_all',
            'msg_unread',
            'msg_need_reply',
            'msg_order',
            'msg_offer',
            'msg_general',
            'msg_attachments',
            'msg_last24h',
            'msg_last7d',
        ];
        return in_array($filter, $ready, true);
    }

    /**
     * Rekalkulacja pól pochodnych na wątku (need_reply / has_attachments / last_*).
     *
     * @return array{need_reply:int,has_attachments:int,last_interlocutor_at:?string,last_seller_at:?string}
     */
    public function recomputeDerivedStats(int $accountId, string $threadId, ?string $cutoffMysql = null): array
    {
        $p = _DB_PREFIX_;
        $mm = $p . 'allegropro_msg_message';

        $where = 'm.id_allegropro_account=' . (int)$accountId . " AND m.thread_id='" . pSQL($threadId) . "'";
        if ($cutoffMysql) {
            $where .= " AND (m.created_at_allegro IS NULL OR m.created_at_allegro >= '" . pSQL($cutoffMysql) . "')";
        }

        $row = Db::getInstance()->getRow('SELECT
                MAX(CASE WHEN m.author_is_interlocutor = 1 THEN m.created_at_allegro ELSE NULL END) AS last_interlocutor_at,
                MAX(CASE WHEN m.author_is_interlocutor = 0 THEN m.created_at_allegro ELSE NULL END) AS last_seller_at,
                MAX(m.has_attachments) AS has_attachments
            FROM `' . pSQL($mm) . '` m
            WHERE ' . $where);

        $lastInter = $row && !empty($row['last_interlocutor_at']) ? (string)$row['last_interlocutor_at'] : null;
        $lastSell = $row && !empty($row['last_seller_at']) ? (string)$row['last_seller_at'] : null;
        $hasAtt = (int)($row['has_attachments'] ?? 0);

        $needReply = 0;
        if ($lastInter !== null) {
            if ($lastSell === null) {
                $needReply = 1;
            } else {
                $needReply = (strtotime($lastInter) > strtotime($lastSell)) ? 1 : 0;
            }
        }

        $now = date('Y-m-d H:i:s');

        Db::getInstance()->execute('UPDATE `' . pSQL($this->table) . '`
            SET
                need_reply=' . (int)$needReply . ',
                has_attachments=' . (int)$hasAtt . ',
                last_interlocutor_at=' . ($lastInter ? "'" . pSQL($lastInter) . "'" : 'NULL') . ',
                last_seller_at=' . ($lastSell ? "'" . pSQL($lastSell) . "'" : 'NULL') . ',
                derived_updated_at=' . "'" . pSQL($now) . "'" . '
            WHERE id_allegropro_account=' . (int)$accountId . " AND thread_id='" . pSQL($threadId) . "'");

        return [
            'need_reply' => $needReply,
            'has_attachments' => $hasAtt,
            'last_interlocutor_at' => $lastInter,
            'last_seller_at' => $lastSell,
        ];
    }

    /**
     * Kandydaci do prefetchu (uzupełnienie pól pochodnych bez pełnego otwierania wątku).
     *
     * @return array<int, array{thread_id:string}>
     */
    public function listPrefetchCandidates(int $accountId, ?string $cutoffMysql, int $limit = 10): array
    {
        // To jest "kolejka" do segregacji: wątki bez zaktualizowanych pól pochodnych.
        // Pozwalamy na większy limit (w module może być nawet kilka tys. wątków w zakresie miesięcy).
        $limit = max(0, min(5000, (int)$limit));
        if ($limit <= 0) {
            return [];
        }

        $where = ['id_allegropro_account=' . (int)$accountId, 'is_synced=1'];
        if ($cutoffMysql) {
            $where[] = "last_message_at IS NOT NULL AND last_message_at >= '" . pSQL($cutoffMysql) . "'";
        }
        // Brak danych pochodnych albo są starsze niż ostatnia wiadomość w wątku.
        $where[] = '(derived_updated_at IS NULL OR (last_message_at IS NOT NULL AND derived_updated_at < last_message_at))';

        $rows = Db::getInstance()->executeS('SELECT thread_id
            FROM `' . pSQL($this->table) . '`
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY last_message_at DESC, thread_id DESC
            LIMIT ' . (int)$limit);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Ile wątków wymaga jeszcze uzupełnienia pól pochodnych (need_reply / załączniki / relacje).
     * To jest "pending" do segregacji – używamy w auto-sync, żeby wiedzieć czy dopinać kolejne batch'e.
     */
    
    /**
     * Kandydaci do re-segregacji (tryb serwisowy / ręczne "Segreguj").
     * W odróżnieniu od listPrefetchCandidates() uwzględnia także wątki,
     * które mogły zostać wcześniej przetworzone uproszczonym algorytmem,
     * ale nadal nie mają relacji (checkout_form_id/offer_id).
     *
     * @return array<int, array{thread_id:string}>
     */
    public function listPrefetchCandidatesForce(int $accountId, ?string $cutoffMysql, int $limit = 10): array
    {
        $limit = max(0, min(5000, (int)$limit));
        if ($limit <= 0) {
            return [];
        }

        $where = ['id_allegropro_account=' . (int)$accountId, 'is_synced=1'];
        if ($cutoffMysql) {
            $where[] = "last_message_at IS NOT NULL AND last_message_at >= '" . pSQL($cutoffMysql) . "'";
        }

        // Re-segregujemy:
        // - wątki bez danych pochodnych (jak w standardowym trybie),
        // - oraz te, które nadal wyglądają na "ogólne" (brak relacji order/offer),
        //   bo wcześniej mogły zostać przetworzone tylko na podstawie 1 wiadomości.
        $where[] = '((derived_updated_at IS NULL OR (last_message_at IS NOT NULL AND derived_updated_at < last_message_at))'
            . ' OR ((checkout_form_id IS NULL OR checkout_form_id = \'\') AND (offer_id IS NULL OR offer_id = \'\')))';

        $rows = Db::getInstance()->executeS('SELECT thread_id
            FROM `' . pSQL($this->table) . '`
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY last_message_at DESC, thread_id DESC
            LIMIT ' . (int)$limit);

        return is_array($rows) ? $rows : [];
    }

public function countPrefetchPending(int $accountId, ?string $cutoffMysql = null): int
    {
        $where = ['id_allegropro_account=' . (int)$accountId, 'is_synced=1'];
        if ($cutoffMysql) {
            $where[] = "last_message_at IS NOT NULL AND last_message_at >= '" . pSQL($cutoffMysql) . "'";
        }
        $where[] = '(derived_updated_at IS NULL OR (last_message_at IS NOT NULL AND derived_updated_at < last_message_at))';

        return (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . pSQL($this->table) . '` WHERE ' . implode(' AND ', $where)
        );
    }

    
    /**
     * Ile wątków kwalifikuje się do re-segregacji (tryb serwisowy).
     */
    public function countPrefetchPendingForce(int $accountId, ?string $cutoffMysql): int
    {
        $where = ['id_allegropro_account=' . (int)$accountId, 'is_synced=1'];
        if ($cutoffMysql) {
            $where[] = "last_message_at IS NOT NULL AND last_message_at >= '" . pSQL($cutoffMysql) . "'";
        }

        $where[] = '((derived_updated_at IS NULL OR (last_message_at IS NOT NULL AND derived_updated_at < last_message_at))'
            . ' OR ((checkout_form_id IS NULL OR checkout_form_id = \'\') AND (offer_id IS NULL OR offer_id = \'\')))';

        $val = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . pSQL($this->table) . '` WHERE ' . implode(' AND ', $where)
        );

        return (int)($val ?: 0);
    }


    /**
     * Liczba wątków już pobranych (is_synced=1) w ramach cutoff (ostatnie X miesięcy).
     * Używane do pełnej re-segregacji (progress bar).
     */
    public function countSyncedInRange(int $accountId, ?string $cutoffMysql = null): int
    {
        $where = ['id_allegropro_account=' . (int)$accountId, 'is_synced=1'];
        if ($cutoffMysql) {
            $where[] = "last_message_at IS NOT NULL AND last_message_at >= '" . pSQL($cutoffMysql) . "'";
        }

        return (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . pSQL($this->table) . '` WHERE ' . implode(' AND ', $where)
        );
    }

    /**
     * Resetuje pola pochodne na wątkach, aby wymusić pełne przeliczenie segregacji.
     * Nie rusza checkout_form_id / offer_id (żeby nie zgubić już wykrytych relacji).
     *
     * @return int liczba rekordów objętych resetem (przybliżenie)
     */
    public function resetDerivedForAccount(int $accountId, ?string $cutoffMysql = null): int
    {
        $where = ['id_allegropro_account=' . (int)$accountId, 'is_synced=1'];
        if ($cutoffMysql) {
            $where[] = "last_message_at IS NOT NULL AND last_message_at >= '" . pSQL($cutoffMysql) . "'";
        }

        $sql = 'UPDATE `' . pSQL($this->table) . '`
                SET need_reply=0,
                    has_attachments=0,
                    last_interlocutor_at=NULL,
                    last_seller_at=NULL,
                    derived_updated_at=NULL
                WHERE ' . implode(' AND ', $where);

        Db::getInstance()->execute($sql);

        // PrestaShop Db ma różne implementacje; jeśli nie ma Affected_Rows, zwracamy count().
        try {
            if (method_exists(Db::getInstance(), 'Affected_Rows')) {
                return (int)Db::getInstance()->Affected_Rows();
            }
        } catch (\Throwable $e) {}

        return $this->countSyncedInRange($accountId, $cutoffMysql);
    }

private function countDerivedMissing(?string $cutoffMysql): int
    {
        $where = ['1=1', 'is_synced=1'];
        if ($cutoffMysql) {
            $where[] = "last_message_at IS NOT NULL AND last_message_at >= '" . pSQL($cutoffMysql) . "'";
        }
        $where[] = '(derived_updated_at IS NULL OR (last_message_at IS NOT NULL AND derived_updated_at < last_message_at))';
        return (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . pSQL($this->table) . '` WHERE ' . implode(' AND ', $where));
    }

    private function isoToMysql(?string $iso): ?string
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
        $months = (int)\Configuration::get('ALLEGROPRO_CORR_MSG_MONTHS');
        if ($months < 1) {
            $months = 6;
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
