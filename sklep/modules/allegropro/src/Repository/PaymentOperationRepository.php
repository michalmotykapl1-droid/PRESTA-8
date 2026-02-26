<?php
namespace AllegroPro\Repository;

use Db;
use DbQuery;

/**
 * Cache dziennika Allegro Finanse (GET /payments/payment-operations).
 *
 * UWAGA: kolumny nazywamy op_group/op_type, bo GROUP to słowo kluczowe w SQL.
 */
class PaymentOperationRepository
{
    private string $table;
    private string $tableRaw;

    public function __construct()
    {
        $this->tableRaw = 'allegropro_payment_operation';
        $this->table = _DB_PREFIX_ . $this->tableRaw;
    }

    /**
     * W panelu PrestaShop użytkownik operuje na "dacie lokalnej" (PS_TIMEZONE),
     * natomiast payment-operations (i nasz cache) trzymamy w UTC.
     *
     * Żeby zakresy w module zgadzały się 1:1 z Allegro (PL), musimy konwertować
     * granice dni z PS_TIMEZONE → UTC.
     */
    private function getShopTimezone(): \DateTimeZone
    {
        $tzId = 'UTC';
        try {
            if (class_exists('\\Configuration')) {
                $conf = (string)\Configuration::get('PS_TIMEZONE');
                if ($conf !== '') {
                    $tzId = $conf;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        try {
            return new \DateTimeZone($tzId);
        } catch (\Throwable $e) {
            try {
                return new \DateTimeZone('Europe/Warsaw');
            } catch (\Throwable $e2) {
                return new \DateTimeZone('UTC');
            }
        }
    }

    private function ymdToUtcDbStart(string $ymd): string
    {
        return $this->localDayToUtcDb($ymd, 0, 0, 0);
    }

    private function ymdToUtcDbEnd(string $ymd): string
    {
        return $this->localDayToUtcDb($ymd, 23, 59, 59);
    }

    private function ymdToUtcDbNextDayStart(string $ymd): string
    {
        $tz = $this->getShopTimezone();
        try {
            $dtLocal = new \DateTimeImmutable($ymd . ' 00:00:00', $tz);
            $dtLocal = $dtLocal->modify('+1 day');
            return $dtLocal->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            // fallback
            return date('Y-m-d H:i:s', strtotime($ymd . ' +1 day'));
        }
    }

    private function utcDbToLocal(string $utcDb): string
    {
        $utcDb = trim((string)$utcDb);
        if ($utcDb === '') {
            return '';
        }

        try {
            $dtUtc = new \DateTimeImmutable($utcDb, new \DateTimeZone('UTC'));
            return $dtUtc->setTimezone($this->getShopTimezone())->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return $utcDb;
        }
    }

    private function localDayToUtcDb(string $ymd, int $h, int $m, int $s): string
    {
        $tz = $this->getShopTimezone();
        try {
            $dtLocal = (new \DateTimeImmutable($ymd, $tz))->setTime($h, $m, $s);
            return $dtLocal->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return $ymd . sprintf(' %02d:%02d:%02d', $h, $m, $s);
        }
    }

    public function ensureSchema(): bool
    {
        $engine = _MYSQL_ENGINE_;
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->table}` (
            `id_allegropro_payment_operation` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_allegropro_account` INT UNSIGNED NOT NULL,
            `operation_id` VARCHAR(96) NOT NULL,
            `occurred_at` DATETIME NOT NULL,
            `occurred_at_iso` VARCHAR(40) NOT NULL,
            `op_group` VARCHAR(32) NOT NULL,
            `op_type` VARCHAR(64) NOT NULL,
            `wallet_type` VARCHAR(32) NULL,
            `wallet_payment_operator` VARCHAR(32) NULL,
            `amount` DECIMAL(20,2) NOT NULL,
            `currency` CHAR(3) NOT NULL DEFAULT 'PLN',
            `payment_id` VARCHAR(128) NULL,
            `participant_login` VARCHAR(128) NULL,
            `raw_json` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_allegropro_payment_operation`),
            UNIQUE KEY `uniq_account_opid` (`id_allegropro_account`,`operation_id`),
            KEY `idx_account_date` (`id_allegropro_account`,`occurred_at`),
            KEY `idx_payment_id` (`payment_id`),
            KEY `idx_group_type` (`op_group`,`op_type`)
        ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4;";

        try {
            return (bool)Db::getInstance()->execute($sql);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param int $accountId
     * @param array<int,array<string,mixed>> $ops normalized ops
     */
    public function upsertMany(int $accountId, array $ops, int $chunkSize = 200): int
    {
        if ($accountId <= 0 || empty($ops)) {
            return 0;
        }

        $now = date('Y-m-d H:i:s');
        $total = 0;
        $chunkSize = max(1, $chunkSize);

        foreach (array_chunk($ops, $chunkSize) as $chunk) {
            $values = [];
            foreach ($chunk as $op) {
                $amount = (string)($op['amount'] ?? '0.00');
                // amount jako DECIMAL (bezpiecznie jako liczba / string)
                if (!preg_match('/^-?\d+(?:\.\d+)?$/', $amount)) {
                    $amount = '0.00';
                }
                $values[] = "(" . (int)$accountId
                    . ",'" . pSQL((string)($op['operation_id'] ?? '')) . "'"
                    . ",'" . pSQL((string)($op['occurred_at'] ?? $now)) . "'"
                    . ",'" . pSQL((string)($op['occurred_at_iso'] ?? '')) . "'"
                    . ",'" . pSQL((string)($op['op_group'] ?? '')) . "'"
                    . ",'" . pSQL((string)($op['op_type'] ?? '')) . "'"
                    . ",'" . pSQL((string)($op['wallet_type'] ?? '')) . "'"
                    . ",'" . pSQL((string)($op['wallet_payment_operator'] ?? '')) . "'"
                    . ",'" . pSQL($amount) . "'"
                    . ",'" . pSQL((string)($op['currency'] ?? 'PLN')) . "'"
                    . ",'" . pSQL((string)($op['payment_id'] ?? '')) . "'"
                    . ",'" . pSQL((string)($op['participant_login'] ?? '')) . "'"
                    . ",'" . pSQL((string)($op['raw_json'] ?? ''), true) . "'"
                    . ",'" . pSQL($now) . "'"
                    . ",'" . pSQL($now) . "')";
            }

            if (empty($values)) {
                continue;
            }

            $sql = "INSERT INTO `{$this->table}`
                (`id_allegropro_account`,`operation_id`,`occurred_at`,`occurred_at_iso`,`op_group`,`op_type`,`wallet_type`,`wallet_payment_operator`,`amount`,`currency`,`payment_id`,`participant_login`,`raw_json`,`created_at`,`updated_at`)
                VALUES " . implode(',', $values) . "
                ON DUPLICATE KEY UPDATE
                    `occurred_at`=VALUES(`occurred_at`),
                    `occurred_at_iso`=VALUES(`occurred_at_iso`),
                    `op_group`=VALUES(`op_group`),
                    `op_type`=VALUES(`op_type`),
                    `wallet_type`=VALUES(`wallet_type`),
                    `wallet_payment_operator`=VALUES(`wallet_payment_operator`),
                    `amount`=VALUES(`amount`),
                    `currency`=VALUES(`currency`),
                    `payment_id`=VALUES(`payment_id`),
                    `participant_login`=VALUES(`participant_login`),
                    `raw_json`=VALUES(`raw_json`),
                    `updated_at`=VALUES(`updated_at`);";

            Db::getInstance()->execute($sql);
            $total += count($chunk);
        }

        return $total;
    }

    public function getMaxOccurredAt(int $accountId): ?string
    {
        if ($accountId <= 0) {
            return null;
        }
        $sql = 'SELECT MAX(occurred_at) AS m FROM `' . $this->table . '` WHERE id_allegropro_account=' . (int)$accountId;
        $row = Db::getInstance()->getRow($sql);
        $m = $row['m'] ?? null;
        return $m ? (string)$m : null;
    }

    public function countForDay(int $accountId, string $ymd): int
    {
        // Lokalny dzień (PS_TIMEZONE) → UTC granice w DB.
        $start = pSQL($this->ymdToUtcDbStart($ymd));
        $end = pSQL($this->ymdToUtcDbNextDayStart($ymd));
        $sql = 'SELECT COUNT(*) AS c FROM `' . $this->table . '` WHERE id_allegropro_account=' . (int)$accountId
            . " AND occurred_at >= '{$start}' AND occurred_at < '{$end}'";
        $row = Db::getInstance()->getRow($sql);
        return (int)($row['c'] ?? 0);
    }

    public function findPage(int $accountId, string $dateFrom, string $dateTo, array $filters, int $limit, int $offset): array
    {
        $q = $this->baseQuery($accountId, $dateFrom, $dateTo, $filters);
        $q->select('occurred_at_iso AS occurredAt, op_group AS `group`, op_type AS `type`, wallet_type, wallet_payment_operator AS wallet_operator, participant_login, amount, currency, payment_id');
        $q->orderBy('occurred_at DESC');
        $q->limit((int)$limit, (int)$offset);
        return Db::getInstance()->executeS($q) ?: [];
    }

    public function countTotal(int $accountId, string $dateFrom, string $dateTo, array $filters): int
    {
        $q = $this->baseQuery($accountId, $dateFrom, $dateTo, $filters);
        $q->select('COUNT(*) AS c');
        $row = Db::getInstance()->getRow($q);
        return (int)($row['c'] ?? 0);
    }

    public function kpiTotal(int $accountId, string $dateFrom, string $dateTo, array $filters): array
    {
        $q = $this->baseQuery($accountId, $dateFrom, $dateTo, $filters);
        $q->select('COUNT(*) AS cnt, SUM(amount) AS total, SUM(CASE WHEN amount >= 0 THEN amount ELSE 0 END) AS pos, SUM(CASE WHEN amount < 0 THEN amount ELSE 0 END) AS neg');
        $row = Db::getInstance()->getRow($q);
        return [
            'count' => (int)($row['cnt'] ?? 0),
            'total' => (float)($row['total'] ?? 0),
            'pos' => (float)($row['pos'] ?? 0),
            'neg' => (float)($row['neg'] ?? 0),
        ];
    }

    /**
     * Iteracja do CSV (chunkami) dla aktualnych filtrów.
     */
    public function iterateForCsv(int $accountId, string $dateFrom, string $dateTo, array $filters, int $chunk = 2000): \Generator
    {
        $offset = 0;
        while (true) {
            $rows = $this->findPage($accountId, $dateFrom, $dateTo, $filters, $chunk, $offset);
            if (empty($rows)) {
                break;
            }
            foreach ($rows as $r) {
                yield $r;
            }
            $offset += count($rows);
            if (count($rows) < $chunk) {
                break;
            }
        }
    }

    /**
     * Podsumowanie dzienne (dla widoku PAYOUT itp.).
     * Zwraca: [{day, count, sum}]
     */
    public function dailySummary(int $accountId, string $dateFrom, string $dateTo, array $filters): array
    {
        $q = $this->baseQuery($accountId, $dateFrom, $dateTo, $filters);
        $q->select('DATE(po.occurred_at) AS day, COUNT(*) AS cnt, SUM(po.amount) AS total');
        $q->groupBy('day');
        $q->orderBy('day DESC');
        $rows = Db::getInstance()->executeS($q) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $sum = (float)($r['total'] ?? 0);
            $out[] = [
                'day' => (string)($r['day'] ?? ''),
                'count' => (int)($r['cnt'] ?? 0),
                'sum' => $sum,
                'sum_abs' => abs($sum),
            ];
        }
        return $out;
    }

/**
 * Wypłaty na konto bankowe (operacje OUTCOME/PAYOUT) oraz anulowania wypłat (OUTCOME/PAYOUT_CANCEL).
 * Uwaga: to są operacje Allegro → Twoje konto, a nie zwroty dla klienta.
 */
public function findPayouts(int $accountId, string $dateFrom, string $dateTo, int $limit = 50, int $offset = 0): array
{
    $q = new DbQuery();
    $q->from($this->tableRaw, 'po');
    $q->where('po.id_allegropro_account=' . (int)$accountId);

    // Zakres dat w UI jest lokalny (PS_TIMEZONE) → w DB trzymamy UTC.
    $start = pSQL($this->ymdToUtcDbStart($dateFrom));
    $end = pSQL($this->ymdToUtcDbEnd($dateTo));
    $q->where("po.occurred_at >= '{$start}' AND po.occurred_at <= '{$end}'");

    $q->where("po.op_group='OUTCOME'");
    $q->where("po.op_type IN ('PAYOUT','PAYOUT_CANCEL')");

    $q->select("po.occurred_at, po.op_type AS `type`, po.wallet_type, po.wallet_payment_operator AS wallet_operator, po.amount, po.currency, po.raw_json");
    $q->orderBy('po.occurred_at DESC');
    $q->limit((int)$limit, (int)$offset);

    return Db::getInstance()->executeS($q) ?: [];
}

public function countPayoutsTotal(int $accountId, string $dateFrom, string $dateTo): int
{
    $q = new DbQuery();
    $q->from($this->tableRaw, 'po');
    $q->select('COUNT(*) AS cnt');
    $q->where('po.id_allegropro_account=' . (int)$accountId);

    $start = pSQL($this->ymdToUtcDbStart($dateFrom));
    $end = pSQL($this->ymdToUtcDbEnd($dateTo));
    $q->where("po.occurred_at >= '{$start}' AND po.occurred_at <= '{$end}'");

    $q->where("po.op_group='OUTCOME'");
    $q->where("po.op_type IN ('PAYOUT','PAYOUT_CANCEL')");

    $row = Db::getInstance()->getRow($q);
    return (int)($row['cnt'] ?? 0);
}



    /**
     * Zwraca datę ostatniej wypłaty (PAYOUT) sprzed podanego zakresu.
     * Przydaje się do kontroli "między wypłatami".
     */
    public function getLastPayoutBefore(int $accountId, string $dateFrom): ?string
    {
        if ($accountId <= 0) {
            return null;
        }

        $start = pSQL($this->ymdToUtcDbStart($dateFrom));
        $sql = "SELECT MAX(occurred_at) AS dt
                FROM `{$this->table}`
                WHERE id_allegropro_account=" . (int) $accountId . "
                  AND op_group='OUTCOME'
                  AND op_type='PAYOUT'
                  AND occurred_at < '{$start}'";

        $row = Db::getInstance()->getRow($sql);
        $dt = $row['dt'] ?? null;
        return $dt ? (string) $dt : null;
    }

    /**
     * Zwraca pełny rekord wypłaty (PAYOUT) sprzed zakresu – m.in. do odczytu wallet.balance.amount.
     *
     * @return array{occurred_at:string, raw_json:string}|null
     */
    public function findPrevPayoutBefore(int $accountId, string $dateFrom): ?array
    {
        $this->ensureSchema();

        if ($accountId <= 0) {
            return null;
        }

        // DbQuery (zamiast surowego SQL) – w praktyce unika problemów składniowych
        // zależnych od konfiguracji serwera/trybu SQL.
        $cutoff = pSQL($this->ymdToUtcDbStart($dateFrom));

        $q = new DbQuery();
        $q->select('po.occurred_at, po.raw_json');
        // from() przyjmuje nazwę BEZ prefiksu – PrestaShop dokleja _DB_PREFIX_.
        $q->from($this->tableRaw, 'po');
        $q->where('po.id_allegropro_account=' . (int)$accountId);
        $q->where("po.op_group='OUTCOME'");
        $q->where("po.op_type='PAYOUT'");
        $q->where("po.occurred_at < '{$cutoff}'");
        $q->orderBy('po.occurred_at DESC');
        $q->limit(1);

        try {
            $row = Db::getInstance()->getRow($q);
        } catch (\Throwable $e) {
            return null;
        }

        return $row ?: null;
    }

    /**
     * Lista wypłat (PAYOUT) w zakresie – rosnąco po dacie.
     * (Bez anulowań – te pokazujemy osobno w tabeli wypłat).
     *
     * @return array<int,array<string,mixed>>
     */
    public function findPayoutsAsc(int $accountId, string $dateFrom, string $dateTo, int $limit = 200): array
    {
        if ($accountId <= 0) {
            return [];
        }
        $start = pSQL($this->ymdToUtcDbStart($dateFrom));
        $end = pSQL($this->ymdToUtcDbEnd($dateTo));

        $q = new DbQuery();
        $q->from($this->tableRaw, 'po');
        $q->where('po.id_allegropro_account=' . (int)$accountId);
        $q->where("po.occurred_at >= '{$start}' AND po.occurred_at <= '{$end}'");

        $q->where("po.op_group='OUTCOME'");
        $q->where("po.op_type='PAYOUT'");

        $q->select("po.occurred_at, po.amount, po.currency, po.raw_json");
        $q->orderBy('po.occurred_at ASC');
        $q->limit((int)$limit);

        return Db::getInstance()->executeS($q) ?: [];
    }



    /**
     * Podsumowanie ruchów na portfelu AVAILABLE w zadanym oknie czasu,
     * z wyłączeniem samych wypłat (OUTCOME/PAYOUT).
     *
     * UWAGA: anulowanie wypłaty (OUTCOME/PAYOUT_CANCEL) wpływa na saldo AVAILABLE,
     * więc NIE wykluczamy go z bilansu okresu.
     *
     * @return array{inflow:float,deduction:float,net:float}
     */
    public function availableSummaryBetween(int $accountId, string $fromInclusive, string $toExclusive): array
    {
        if ($accountId <= 0) {
            return ['inflow' => 0.0, 'deduction' => 0.0, 'net' => 0.0];
        }

        $from = pSQL($fromInclusive);
        $to = pSQL($toExclusive);

        $sql = "SELECT
                  COALESCE(SUM(CASE WHEN wallet_type='AVAILABLE' AND amount > 0 THEN amount ELSE 0 END),0) AS inflow,
                  COALESCE(SUM(CASE WHEN wallet_type='AVAILABLE' AND amount < 0 THEN ABS(amount) ELSE 0 END),0) AS deduction,
                  COALESCE(SUM(CASE WHEN wallet_type='AVAILABLE' THEN amount ELSE 0 END),0) AS net
                FROM `{$this->table}`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND occurred_at >= '{$from}'
                  AND occurred_at < '{$to}'
                  AND NOT (op_group='OUTCOME' AND op_type='PAYOUT')";
        $row = Db::getInstance()->getRow($sql) ?: [];

        return [
            'inflow' => (float)($row['inflow'] ?? 0),
            'deduction' => (float)($row['deduction'] ?? 0),
            'net' => (float)($row['net'] ?? 0),
        ];
    }

    /**
     * Bardziej szczegółowe podsumowanie do kontroli wypłat.
     *
     * Liczymy w oknie [fromInclusive, toExclusive) oraz wykluczamy samą wypłatę (OUTCOME/PAYOUT),
     * bo ona jest porównywana osobno.
     *
     * - payments: wpłaty z zamówień, które trafiły do portfela AVAILABLE (INCOME/CONTRIBUTION)
     * - fee_deduction / fee_refund: opłaty Allegro (typy kończące się na "CHARGE") i ich zwroty
     * - other_net: bilans pozostałych operacji w AVAILABLE (np. zwroty dla kupujących, korekty, anulowania wypłat)
     *
     * @return array{payments:float,fee_deduction:float,fee_refund:float,inflow_all:float,deduction_all:float,net_all:float,expected_orders:float,other_net:float}
     */
    public function availableBreakdownBetween(int $accountId, string $fromInclusive, string $toExclusive): array
    {
        if ($accountId <= 0) {
            return [
                'payments' => 0.0,
                'fee_deduction' => 0.0,
                'fee_refund' => 0.0,
                'inflow_all' => 0.0,
                'deduction_all' => 0.0,
                'net_all' => 0.0,
                'expected_orders' => 0.0,
                'other_net' => 0.0,
            ];
        }

        $from = pSQL($fromInclusive);
        $to = pSQL($toExclusive);

        // Uwaga dot. typów operacji:
        // - DEDUCTION_CHARGE        → potrącenie opłat Allegro (OUTCOME)
        // - DEDUCTION_INCREASE      → zwrot/korekta potrąconych opłat (INCOME)
        // - REFUND_CHARGE           → zwrot środków dla kupującego (REFUND) – to NIE jest „zwrot opłaty Allegro”.
        //
        // Poprzednia implementacja używała sufiksu "*CHARGE" jako heurystyki dla opłat.
        // To powodowało błędne klasyfikowanie REFUND_CHARGE (zwroty dla kupujących) jako „zwroty opłat”.
        // Tu opieramy się na typach DEDUCTION_* zgodnych z API Allegro.

        $sql = "SELECT
                  COALESCE(SUM(CASE WHEN wallet_type='AVAILABLE' AND op_group='INCOME' AND op_type='CONTRIBUTION' THEN amount ELSE 0 END),0) AS payments,
                  COALESCE(SUM(CASE WHEN wallet_type='AVAILABLE' AND op_type='DEDUCTION_CHARGE' THEN ABS(amount) ELSE 0 END),0) AS fee_deduction,
                  COALESCE(SUM(CASE WHEN wallet_type='AVAILABLE' AND op_type='DEDUCTION_INCREASE' THEN ABS(amount) ELSE 0 END),0) AS fee_refund,
                  COALESCE(SUM(CASE WHEN wallet_type='AVAILABLE' AND amount > 0 THEN amount ELSE 0 END),0) AS inflow_all,
                  COALESCE(SUM(CASE WHEN wallet_type='AVAILABLE' AND amount < 0 THEN ABS(amount) ELSE 0 END),0) AS deduction_all,
                  COALESCE(SUM(CASE WHEN wallet_type='AVAILABLE' THEN amount ELSE 0 END),0) AS net_all
                FROM `{$this->table}`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND occurred_at >= '{$from}'
                  AND occurred_at < '{$to}'
                  AND NOT (op_group='OUTCOME' AND op_type='PAYOUT')";

        $row = Db::getInstance()->getRow($sql) ?: [];

        $payments = (float)($row['payments'] ?? 0);
        $feeDed = (float)($row['fee_deduction'] ?? 0);
        $feeRef = (float)($row['fee_refund'] ?? 0);
        $netAll = (float)($row['net_all'] ?? 0);

        $expectedOrders = $payments - $feeDed + $feeRef;
        $otherNet = $netAll - $expectedOrders;

        return [
            'payments' => $payments,
            'fee_deduction' => $feeDed,
            'fee_refund' => $feeRef,
            'inflow_all' => (float)($row['inflow_all'] ?? 0),
            'deduction_all' => (float)($row['deduction_all'] ?? 0),
            'net_all' => $netAll,
            'expected_orders' => $expectedOrders,
            'other_net' => $otherNet,
        ];
    }



    /**
     * Tłumaczenie kodów group/type z Allegro (payment-operations) na czytelne etykiety po polsku.
     * Przykład: OUTCOME/DEDUCTION_CHARGE → "Pobranie opłat ze środków (opłaty Allegro)".
     *
     * Zwracamy krótki opis (bez kodu). Kod zawsze jest dostępny osobno jako group/type.
     */
    private function opTypeLabel(string $group, string $type): string
    {
        $g = strtoupper(trim((string)$group));
        $t = strtoupper(trim((string)$type));
        $key = ($g !== '' ? $g . '/' : '') . $t;

        $map = [
            'INCOME/CONTRIBUTION' => 'Wpłata od kupującego',
            'OUTCOME/DEDUCTION_CHARGE' => 'Pobranie opłat ze środków (opłaty Allegro)',
            'INCOME/DEDUCTION_INCREASE' => 'Zwrot/korekta opłat Allegro',
            'REFUND/REFUND_CHARGE' => 'Zwrot dla kupującego',
            'OUTCOME/PAYOUT' => 'Wypłata środków na konto',
            'OUTCOME/PAYOUT_CANCEL' => 'Anulowanie wypłaty',
            'TRANSFER/BALANCE_INCREASE' => 'Przeniesienie środków na saldo',
            'TRANSFER/BALANCE_DECREASE' => 'Przeniesienie środków z salda',
        ];
        if (isset($map[$key])) {
            return $map[$key];
        }

        // Gdy group bywa różne, ale typ jest ten sam – mapowanie po samym typie.
        $typeMap = [
            'CONTRIBUTION' => 'Wpłata od kupującego',
            'DEDUCTION_CHARGE' => 'Pobranie opłat ze środków (opłaty Allegro)',
            'DEDUCTION_INCREASE' => 'Zwrot/korekta opłat Allegro',
            'REFUND_CHARGE' => 'Zwrot dla kupującego',
            'PAYOUT' => 'Wypłata środków na konto',
            'PAYOUT_CANCEL' => 'Anulowanie wypłaty',
            'BALANCE_INCREASE' => 'Przeniesienie środków na saldo',
            'BALANCE_DECREASE' => 'Przeniesienie środków z salda',
            'WALLET_TRANSFER' => 'Przeniesienie środków (między portfelami)',
        ];
        if (isset($typeMap[$t])) {
            return $typeMap[$t];
        }

        // Fallback: spróbuj zrobić czytelny opis z kodu (zamiana _ na spacje + proste tłumaczenia tokenów).
        $pretty = strtolower(str_replace('_', ' ', $t));
        $pretty = str_replace(
            ['payout', 'cancel', 'refund', 'charge', 'deduction', 'increase', 'decrease', 'contribution', 'transfer', 'balance', 'waiting', 'available'],
            ['wypłata', 'anulowanie', 'zwrot', 'opłata', 'potrącenie', 'zwiększenie', 'zmniejszenie', 'wpłata', 'przeniesienie', 'saldo', 'oczekujące', 'dostępne'],
            $pretty
        );
        $pretty = trim($pretty);

        // ucfirst z uwzględnieniem UTF-8 (jeśli mbstring jest dostępny).
        if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
            $pretty = mb_strtoupper(mb_substr($pretty, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($pretty, 1, null, 'UTF-8');
        } else {
            $pretty = ucfirst($pretty);
        }

        $groupMap = [
            'INCOME' => 'Wpływ',
            'OUTCOME' => 'Wypływ',
            'REFUND' => 'Zwrot',
            'TRANSFER' => 'Przeniesienie',
        ];
        $gLabel = $groupMap[$g] ?? $g;

        if ($gLabel !== '' && $pretty !== '') {
            return $gLabel . ': ' . $pretty;
        }

        return $pretty !== '' ? $pretty : ($key !== '' ? $key : (string)$type);
    }

    /**
     * Czytelna nazwa operatora płatności (wallet.paymentOperator).
     */
    private function walletOperatorLabel(string $operator): string
    {
        $op = strtoupper(trim((string)$operator));
        if ($op === '') {
            return '';
        }

        $map = [
            'AF' => 'Allegro Finance',
            'PAYU' => 'PayU',
            'P' => 'PayU',
        ];

        return $map[$op] ?? (string)$operator;
    }


    /**
     * Szczegóły okna (między wypłatami) – do weryfikacji w UI.
     *
     * Zwraca listy operacji, które wchodzą do:
     * - Opłaty Allegro (DEDUCTION_CHARGE)
     * - Zwroty opłat (DEDUCTION_INCREASE)
     * - Inne operacje (wszystko poza wpłatami i opłatami; np. zwroty dla kupujących, transfery, anulowania wypłat)
     *
     * @return array{
     *   fee:array{count:int,total:float,by_type:array<int,array{key:string,count:int,sum:float}>,rows:array<int,array<string,mixed>>},
     *   fee_refund:array{count:int,total:float,by_type:array<int,array{key:string,count:int,sum:float}>,rows:array<int,array<string,mixed>>},
     *   other:array{count:int,total:float,by_type:array<int,array{key:string,count:int,sum:float}>,rows:array<int,array<string,mixed>>}
     * }
     */
    private function availableWindowDetails(int $accountId, string $fromInclusive, string $toExclusive, int $limit = 2000): array
    {
        if ($accountId <= 0) {
            return [
                'fee' => ['count' => 0, 'total' => 0.0, 'by_type' => [], 'rows' => []],
                'fee_refund' => ['count' => 0, 'total' => 0.0, 'by_type' => [], 'rows' => []],
                'other' => ['count' => 0, 'total' => 0.0, 'by_type' => [], 'rows' => []],
            ];
        }

        $from = pSQL($fromInclusive);
        $to = pSQL($toExclusive);
        $limit = max(100, (int)$limit);

        // 1) Opłaty i zwroty opłat
        // Dodajemy operation_id oraz wallet_operator aby łatwo porównać z eksportem CSV/PDF z Allegro.
        // Uwaga: w tabeli mamy kolumnę wallet_payment_operator.
        // W UI chcemy pole wallet_operator, więc robimy alias w SELECT.
        $sqlFees = "SELECT operation_id, occurred_at, op_group, op_type, amount, currency, payment_id, participant_login, wallet_payment_operator AS wallet_operator
                    FROM `{$this->table}`
                    WHERE id_allegropro_account=" . (int)$accountId . "
                      AND wallet_type='AVAILABLE'
                      AND occurred_at >= '{$from}' AND occurred_at < '{$to}'
                      AND op_type IN ('DEDUCTION_CHARGE','DEDUCTION_INCREASE')
                      AND NOT (op_group='OUTCOME' AND op_type='PAYOUT')
                    ORDER BY occurred_at ASC
                    LIMIT {$limit}";
        $feeRowsAll = Db::getInstance()->executeS($sqlFees) ?: [];

        $fee = ['count' => 0, 'total' => 0.0, 'by_type' => [], 'rows' => []];
        $feeRefund = ['count' => 0, 'total' => 0.0, 'by_type' => [], 'rows' => []];
        $feeAgg = [];
        $feeRefAgg = [];

        foreach ($feeRowsAll as $r) {
            $type = (string)($r['op_type'] ?? '');
            $group = (string)($r['op_group'] ?? '');
            $key = $group . '/' . $type;
            $amount = (float)($r['amount'] ?? 0);
            $sumAbs = abs($amount);

            $rowOut = [
                'operation_id' => (string)($r['operation_id'] ?? ''),
                'occurred_at' => (string)($r['occurred_at'] ?? ''),
                'occurred_at_local' => $this->utcDbToLocal((string)($r['occurred_at'] ?? '')),
                'group' => $group,
                'type' => $type,
                'type_label' => $this->opTypeLabel($group, $type),
                'amount' => $amount,
                'amount_abs' => $sumAbs,
                'currency' => (string)($r['currency'] ?? 'PLN'),
                'payment_id' => (string)($r['payment_id'] ?? ''),
                'participant_login' => (string)($r['participant_login'] ?? ''),
                'wallet_operator' => (string)($r['wallet_operator'] ?? ''),
                'wallet_operator_label' => $this->walletOperatorLabel((string)($r['wallet_operator'] ?? '')),
                // order mapping (uzupełniane niżej, jeżeli mamy payment_id)
                'checkout_form_id' => '',
                'id_order_prestashop' => 0,
                'order_items_preview' => '',
            ];

            if ($type === 'DEDUCTION_CHARGE') {
                $fee['count']++;
                $fee['total'] += $sumAbs;
                $fee['rows'][] = $rowOut;
                if (!isset($feeAgg[$key])) {
                    $feeAgg[$key] = ['key' => $key, 'label' => $this->opTypeLabel($group, $type), 'count' => 0, 'sum' => 0.0];
                }
                $feeAgg[$key]['count']++;
                $feeAgg[$key]['sum'] += $sumAbs;
            } else {
                $feeRefund['count']++;
                $feeRefund['total'] += $sumAbs;
                $feeRefund['rows'][] = $rowOut;
                if (!isset($feeRefAgg[$key])) {
                    $feeRefAgg[$key] = ['key' => $key, 'label' => $this->opTypeLabel($group, $type), 'count' => 0, 'sum' => 0.0];
                }
                $feeRefAgg[$key]['count']++;
                $feeRefAgg[$key]['sum'] += $sumAbs;
            }
        }
        $fee['by_type'] = array_values($feeAgg);
        $feeRefund['by_type'] = array_values($feeRefAgg);

        // 2) Inne operacje (poza wpłatami z zamówień i opłatami Allegro)
        $sqlOther = "SELECT operation_id, occurred_at, op_group, op_type, amount, currency, payment_id, participant_login, wallet_payment_operator AS wallet_operator
                     FROM `{$this->table}`
                     WHERE id_allegropro_account=" . (int)$accountId . "
                       AND wallet_type='AVAILABLE'
                       AND occurred_at >= '{$from}' AND occurred_at < '{$to}'
                       AND NOT (op_group='OUTCOME' AND op_type='PAYOUT')
                       AND NOT (op_group='INCOME' AND op_type='CONTRIBUTION')
                       AND op_type NOT IN ('DEDUCTION_CHARGE','DEDUCTION_INCREASE')
                     ORDER BY occurred_at ASC
                     LIMIT {$limit}";
        $otherRowsAll = Db::getInstance()->executeS($sqlOther) ?: [];

        $other = ['count' => 0, 'total' => 0.0, 'by_type' => [], 'rows' => []];
        $otherAgg = [];
        foreach ($otherRowsAll as $r) {
            $group = (string)($r['op_group'] ?? '');
            $type = (string)($r['op_type'] ?? '');
            $key = $group . '/' . $type;
            $amount = (float)($r['amount'] ?? 0);
            $other['count']++;
            $other['total'] += $amount;
            $other['rows'][] = [
                'operation_id' => (string)($r['operation_id'] ?? ''),
                'occurred_at' => (string)($r['occurred_at'] ?? ''),
                'occurred_at_local' => $this->utcDbToLocal((string)($r['occurred_at'] ?? '')),
                'group' => $group,
                'type' => $type,
                'type_label' => $this->opTypeLabel($group, $type),
                'amount' => $amount,
                'currency' => (string)($r['currency'] ?? 'PLN'),
                'payment_id' => (string)($r['payment_id'] ?? ''),
                'participant_login' => (string)($r['participant_login'] ?? ''),
                'wallet_operator' => (string)($r['wallet_operator'] ?? ''),
                'wallet_operator_label' => $this->walletOperatorLabel((string)($r['wallet_operator'] ?? '')),
                // order mapping (uzupełniane niżej, jeżeli mamy payment_id)
                'checkout_form_id' => '',
                'id_order_prestashop' => 0,
                'order_items_preview' => '',
            ];

            if (!isset($otherAgg[$key])) {
                $otherAgg[$key] = ['key' => $key, 'label' => $this->opTypeLabel($group, $type), 'count' => 0, 'sum' => 0.0];
            }
            $otherAgg[$key]['count']++;
            $otherAgg[$key]['sum'] += $amount;
        }
        $other['by_type'] = array_values($otherAgg);

        // 3) Jeżeli mamy payment_id – spróbuj zmapować operacje na checkoutFormId / zamówienie Presta
        // To pomaga odpowiedzieć na pytanie: "czy to zwrot dla klienta? do jakiego zamówienia?".
        $this->enrichOpsRowsWithOrderContext($accountId, $fee['rows'], $feeRefund['rows'], $other['rows']);

        return [
            'fee' => $fee,
            'fee_refund' => $feeRefund,
            'other' => $other,
        ];
    }

    /**
     * Dla list operacji (fee / fee_refund / other) uzupełnia kontekst zamówienia na podstawie payment_id:
     * - checkout_form_id (Allegro)
     * - id_order_prestashop (jeżeli zamówienie zostało sparowane)
     * - krótki podgląd pozycji (2 pierwsze produkty) – jeżeli mamy cache order_item
     *
     * Uwaga: nie każda operacja posiada payment_id (np. część opłat Allegro jest globalna).
     *
     * @param array<int,array<string,mixed>> $rowsFee
     * @param array<int,array<string,mixed>> $rowsFeeRefund
     * @param array<int,array<string,mixed>> $rowsOther
     */
    private function enrichOpsRowsWithOrderContext(int $accountId, array &$rowsFee, array &$rowsFeeRefund, array &$rowsOther): void
    {
        $accountId = (int)$accountId;
        if ($accountId <= 0) {
            return;
        }

        // Zbierz payment_id
        $pids = [];
        foreach ([$rowsFee, $rowsFeeRefund, $rowsOther] as $rows) {
            foreach ($rows as $r) {
                $pid = trim((string)($r['payment_id'] ?? ''));
                if ($pid !== '') {
                    $pids[$pid] = true;
                }
            }
        }
        $paymentIds = array_keys($pids);
        if (empty($paymentIds)) {
            return;
        }

        $tPay = _DB_PREFIX_ . 'allegropro_order_payment';
        $tOrd = _DB_PREFIX_ . 'allegropro_order';
        $tItem = _DB_PREFIX_ . 'allegropro_order_item';

        // Sprawdź, czy mamy wymagane tabele (dla zgodności ze starszymi instalacjami).
        if (!$this->tableExists($tPay)) {
            return;
        }

        // payment_id -> checkoutFormId (+ Presta)
        $in = [];
        foreach ($paymentIds as $pid) {
            $in[] = "'" . pSQL($pid) . "'";
        }
        $inSql = implode(',', $in);

        $map = [];
        try {
            $sql = "SELECT op.payment_id, op.checkout_form_id, o.id_order_prestashop
                    FROM `{$tPay}` op
                    LEFT JOIN `{$tOrd}` o ON o.checkout_form_id = op.checkout_form_id
                    WHERE op.payment_id IN ({$inSql})";
            $rows = Db::getInstance()->executeS($sql) ?: [];
            foreach ($rows as $r) {
                $pid = (string)($r['payment_id'] ?? '');
                if ($pid === '') {
                    continue;
                }
                $map[$pid] = [
                    'checkout_form_id' => (string)($r['checkout_form_id'] ?? ''),
                    'id_order_prestashop' => (int)($r['id_order_prestashop'] ?? 0),
                ];
            }
        } catch (\Throwable $e) {
            // Jeżeli join się nie uda (np. brak tabeli allegropro_order), po prostu pomijamy.
            $map = [];
        }

        if (empty($map)) {
            return;
        }

        // checkoutFormId -> items preview
        $itemsPreviewByCf = [];
        if ($this->tableExists($tItem)) {
            $cfs = [];
            foreach ($map as $m) {
                $cf = trim((string)($m['checkout_form_id'] ?? ''));
                if ($cf !== '') {
                    $cfs[$cf] = true;
                }
            }
            $cfIds = array_keys($cfs);
            if (!empty($cfIds)) {
                $inCf = [];
                foreach ($cfIds as $cf) {
                    $inCf[] = "'" . pSQL($cf) . "'";
                }
                $inCfSql = implode(',', $inCf);
                try {
                    $sqlIt = "SELECT checkout_form_id, name, quantity
                              FROM `{$tItem}`
                              WHERE checkout_form_id IN ({$inCfSql})
                              ORDER BY checkout_form_id ASC, id_allegropro_order_item ASC";
                    $rowsIt = Db::getInstance()->executeS($sqlIt) ?: [];
                    $tmp = [];
                    foreach ($rowsIt as $it) {
                        $cf = (string)($it['checkout_form_id'] ?? '');
                        if ($cf === '') {
                            continue;
                        }
                        if (!isset($tmp[$cf])) {
                            $tmp[$cf] = [];
                        }
                        $tmp[$cf][] = [
                            'name' => (string)($it['name'] ?? ''),
                            'qty' => (int)($it['quantity'] ?? 1),
                        ];
                    }
                    foreach ($tmp as $cf => $items) {
                        $itemsPreviewByCf[$cf] = $this->buildItemsPreview($items, 2);
                    }
                } catch (\Throwable $e) {
                    $itemsPreviewByCf = [];
                }
            }
        }

        // Wstrzyknij dane wierszy
        $apply = function (array &$rows) use ($map, $itemsPreviewByCf): void {
            foreach ($rows as &$r) {
                $pid = trim((string)($r['payment_id'] ?? ''));
                if ($pid === '' || !isset($map[$pid])) {
                    continue;
                }
                $cf = (string)($map[$pid]['checkout_form_id'] ?? '');
                $r['checkout_form_id'] = $cf;
                $r['id_order_prestashop'] = (int)($map[$pid]['id_order_prestashop'] ?? 0);
                $r['order_items_preview'] = ($cf !== '' && isset($itemsPreviewByCf[$cf])) ? (string)$itemsPreviewByCf[$cf] : '';
            }
            unset($r);
        };

        $apply($rowsFee);
        $apply($rowsFeeRefund);
        $apply($rowsOther);
    }

    /**
     * Sprawdza czy tabela istnieje (podaj pełną nazwę z prefixem).
     */
    private function tableExists(string $fullTableName): bool
    {
        $t = trim($fullTableName);
        if ($t === '') {
            return false;
        }
        try {
            $rows = Db::getInstance()->executeS("SHOW TABLES LIKE '" . pSQL($t) . "'") ?: [];
            return !empty($rows);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Buduje krótki podgląd pozycji zamówienia (np. "Produkt A ×1; Produkt B ×2 +3").
     *
     * @param array<int,array{name:string,qty:int}> $items
     */
    private function buildItemsPreview(array $items, int $max = 2): string
    {
        $max = max(1, (int)$max);
        $parts = [];
        $total = 0;
        foreach ($items as $it) {
            $name = trim((string)($it['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $qty = (int)($it['qty'] ?? 1);
            if ($qty <= 0) {
                $qty = 1;
            }
            $total++;
            if (count($parts) < $max) {
                $parts[] = $name . ' ×' . $qty;
            }
        }
        if ($total > $max) {
            $parts[] = '+' . ($total - $max);
        }
        return implode('; ', $parts);
    }

    /**
     * Kontrola wypłat: dla każdej wypłaty (PAYOUT) liczy bilans operacji na portfelu AVAILABLE
     * pomiędzy poprzednią wypłatą a tą wypłatą.
     *
     * Zwracamy 2 poziomy kontroli:
     * - expected_orders: wpłaty klientów (AVAILABLE) − opłaty + zwroty opłat
     * - expected_total (bilans okresu): pełny bilans AVAILABLE w tym oknie (uwzględnia też inne operacje)
     *
     * Wypłata powinna być zbliżona do bilansu okresu, jeżeli Allegro wypłaca "do zera".
     *
     * @return array<int,array<string,mixed>>
     */


    /**
     * Kontrola wypłat: dla każdej wypłaty (PAYOUT) liczy bilans operacji na portfelu AVAILABLE
     * pomiędzy poprzednią wypłatą a tą wypłatą.
     *
     * Zwracamy 2 poziomy kontroli:
     * - expected_orders: wpłaty klientów (AVAILABLE) − opłaty + zwroty opłat
     * - expected_total (bilans okresu): pełny bilans AVAILABLE w tym oknie (uwzględnia też inne operacje)
     *
     * Wypłata powinna być zbliżona do bilansu okresu, jeżeli Allegro wypłaca "do zera".
     *
     * @return array<int,array<string,mixed>>
     */
    public function payoutWindowsCheck(int $accountId, string $dateFrom, string $dateTo, int $limit = 200): array
    {
        $this->ensureSchema();

        $accountId = (int) $accountId;
        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 200;
        }

        // Uwaga: $dateFrom/$dateTo to daty z UI (lokalne, bez godziny) – nie escapujemy ich tutaj,
        // bo potrzebujemy ich do konwersji strefy czasowej.
        $dateFrom = trim($dateFrom);
        $dateTo = trim($dateTo);

        // Wypłaty w zakresie (OUTCOME/PAYOUT) – kolejność rosnąca.
        $payouts = $this->findPayoutsAsc($accountId, $dateFrom, $dateTo, $limit);
        if (empty($payouts)) {
            return [];
        }

        // Jeżeli mamy wypłatę sprzed zakresu (w bazie), startujemy od jej znacznika czasu.
        // W przeciwnym razie od początku dnia dateFrom.
        $prevPayoutRow = $this->findPrevPayoutBefore($accountId, $dateFrom);
        $hasPrevBefore = !empty($prevPayoutRow);

        $prev = $hasPrevBefore ? (string) ($prevPayoutRow['occurred_at'] ?? '') : '';
        if ($prev === '') {
            $prev = $this->ymdToUtcDbStart($dateFrom);
            $hasPrevBefore = false;
        }

        // Saldo portfela AVAILABLE po poprzedniej wypłacie (jeżeli Allegro zwróciło wallet.balance.amount).
        $walletStart = null;
        if (!empty($prevPayoutRow['raw_json'])) {
            $rawPrev = json_decode($prevPayoutRow['raw_json'], true);
            if (is_array($rawPrev) && isset($rawPrev['wallet']['balance']['amount'])) {
                $walletStart = (float) $rawPrev['wallet']['balance']['amount'];
            }
        }

        $walletStartKnown = ($walletStart !== null);
        $walletStartVal = $walletStartKnown ? (float) $walletStart : 0.0;

        $checks = [];
        $tol = 0.01;

        foreach ($payouts as $idx => $p) {
            $payoutAt = (string) ($p['occurred_at'] ?? '');
            if ($payoutAt === '') {
                continue;
            }

            // Okno między wypłatami liczymy jako [prev, payoutAt) wg occurredAt (z dokładną godziną wypłaty).
            // Dzięki temu operacje z tą samą sekundą co wypłata trafią do kolejnego okna.
            $b = $this->availableBreakdownBetween($accountId, $prev, $payoutAt);

            $payments = (float) ($b['payments'] ?? 0);
            $feeDeduction = (float) ($b['fee_deduction'] ?? 0);
            $feeRefund = (float) ($b['fee_refund'] ?? 0);
            $otherNet = (float) ($b['other_net'] ?? 0);

            $expectedTotal = $payments - $feeDeduction + $feeRefund + $otherNet;
            $payout = abs((float) ($p['amount'] ?? 0));
            $balanceChange = $payout - $expectedTotal;

            // Składowa bilansu bez „innych operacji” (pokazywana jako: wpłaty−opłaty+zwroty).
            $expectedOrders = $payments - $feeDeduction + $feeRefund;

            // Wallet balance po wypłacie (jeśli Allegro zwróciło).
            $walletEnd = null;
            $raw = [];
            if (!empty($p['raw_json'])) {
                $raw = json_decode($p['raw_json'], true);
                if (!is_array($raw)) {
                    $raw = [];
                }
            }
            if (isset($raw['wallet']['balance']['amount'])) {
                $walletEnd = (float) $raw['wallet']['balance']['amount'];
            }

            $walletChange = null;
            $gap = null;
            if ($walletStartKnown && $walletEnd !== null) {
                // Zmiana salda portfela (po poprzedniej wypłacie -> po tej wypłacie).
                $walletChange = $walletStartVal - $walletEnd;
                $gap = $balanceChange - $walletChange;
            }

            // Domyślny status.
            $status = 'OK';
            $statusKind = 'ok';
            $statusLabel = 'OK';
            $noteLines = [];

            // Notatka diagnostyczna (tooltip) – krótko, ale konkretnie.
            $noteLines[] = 'Okno (lokalnie): [' . $this->utcDbToLocal($prev) . ' → ' . $this->utcDbToLocal($payoutAt) . ')';
            $noteLines[] = 'Okno (UTC): [' . $prev . ' → ' . $payoutAt . ') (wg occurredAt)';
            $noteLines[] = sprintf(
                'Bilans AVAILABLE: wpłaty %.2f, opłaty %.2f, zwroty opłat %.2f, inne %.2f → razem %.2f',
                $payments,
                $feeDeduction,
                $feeRefund,
                $otherNet,
                $expectedTotal
            );
            $noteLines[] = sprintf('Wypłata: %.2f → różnica (wypłata - bilans): %.2f', $payout, $balanceChange);

            if (!$hasPrevBefore && $idx === 0) {
                // Bez wypłaty sprzed zakresu nie wiemy, jakie było saldo na starcie.
                $status = 'WARN_NO_HISTORY';
                $statusKind = 'warn';
                $statusLabel = 'Brak historii sprzed zakresu';
                $noteLines[] = 'Brak wypłaty sprzed dateFrom w bazie – saldo start mogło pochodzić sprzed zakresu.';
            } elseif ($walletChange !== null && $gap !== null && abs($gap) > $tol) {
                // Mamy dane salda, ale różnica nie zgadza się ze zmianą salda → coś się nie skleiło (czas/okno/braki).
                $status = 'DIFF';
                $statusKind = 'diff';
                $statusLabel = 'Różnica (sprawdź operacje)';
                $noteLines[] = sprintf(
                    'Saldo AVAILABLE: start %.2f, koniec %.2f → zmiana salda %.2f',
                    $walletStartVal,
                    (float) $walletEnd,
                    (float) $walletChange
                );
                $noteLines[] = sprintf('Uwaga: różnica nie zgadza się ze zmianą salda (gap: %.2f).', (float) $gap);
                $noteLines[] = 'Najczęstsza przyczyna: operacje z tą samą sekundą co wypłata lub braki w synchronizacji.';
            } elseif (abs($balanceChange) < $tol) {
                $status = 'OK';
                $statusKind = 'ok';
                $statusLabel = 'OK';
            } elseif ($balanceChange > 0) {
                $status = 'SALDO_Z_POPRZEDNICH';
                $statusKind = 'carry';
                $statusLabel = 'Wypłata obejmuje saldo z poprzednich okresów';
            } else {
                $status = 'SALDO_POZOSTAJE';
                $statusKind = 'left';
                $statusLabel = 'Część środków została w portfelu (na koniec okna)';
            }

            // Dodatkowa uwaga, jeżeli faktycznie w oknie nie było operacji AVAILABLE.
            $hasAnyOps = (abs((float) ($b['inflow_all'] ?? 0)) > $tol) || (abs((float) ($b['deduction_all'] ?? 0)) > $tol);
            if (!$hasAnyOps && $payout > $tol) {
                $noteLines[] = 'W oknie nie ma żadnych operacji AVAILABLE – wypłata dotyczyła istniejącego salda portfela.';
                if ($walletStartKnown) {
                    $noteLines[] = sprintf('Saldo na starcie okna (%s): %.2f', $prev, $walletStartVal);
                }
            }

            // Jeśli mamy saldo – dopisz to do notatki (ułatwia ręczną weryfikację).
            if ($walletStartKnown && $walletEnd !== null) {
                $noteLines[] = sprintf(
                    'Saldo AVAILABLE: start %.2f, koniec %.2f (gap: %s)',
                    $walletStartVal,
                    (float) $walletEnd,
                    ($gap === null ? '-' : sprintf('%.2f', (float) $gap))
                );
            }

            // Rozkład operacji (opłaty/zwroty opłat/inne) – do podglądu w UI per okno.
            $details = $this->availableWindowDetails($accountId, $prev, $payoutAt);

            $checks[] = [
                'from' => $prev,
                'to' => $payoutAt,
                'payout_at' => $payoutAt,
                'from_local' => $this->utcDbToLocal($prev),
                'to_local' => $this->utcDbToLocal($payoutAt),
                'payout_at_local' => $this->utcDbToLocal($payoutAt),
                'payments_available' => $payments,
                'fee_deduction' => $feeDeduction,
                'fee_refund' => $feeRefund,
                'other_net' => $otherNet,
                'expected_total' => $expectedTotal,
                'expected_orders' => $expectedOrders,
                'payout' => $payout,
                'balance_change' => $balanceChange,
                'status' => $status,
                'status_kind' => $statusKind,
                'status_label' => $statusLabel,
                'note' => implode("\n", $noteLines),
                'currency' => $p['currency'] ?? 'PLN',
                'details' => $details,
            ];

            // Następne okno zaczyna się w chwili tej wypłaty (inclusive).
            $prev = $payoutAt;
            if ($walletEnd !== null) {
                $walletStartKnown = true;
                $walletStartVal = (float) $walletEnd;
            }
        }

        return $checks;
    }

        // Dla pierwszego okna próbujemy znaleźć ostatnią wypłatę sprzed zakresu,
        // żeby różnice wynikały z realnego salda AVAILABLE, a nie z braku historii.

    private function baseQuery(int $accountId, string $dateFrom, string $dateTo, array $filters): DbQuery
    {
        $q = new DbQuery();
        $q->from($this->tableRaw, 'po');

        $start = pSQL($this->ymdToUtcDbStart($dateFrom));
        $end = pSQL($this->ymdToUtcDbEnd($dateTo));

        $q->where('po.id_allegropro_account=' . (int)$accountId);
        $q->where("po.occurred_at >= '{$start}'");
        $q->where("po.occurred_at <= '{$end}'");

        $walletType = (string)($filters['wallet_type'] ?? '');
        if ($walletType !== '') {
            $q->where("po.wallet_type='" . pSQL($walletType) . "'");
        }
        $walletOp = (string)($filters['wallet_payment_operator'] ?? '');
        if ($walletOp !== '') {
            $q->where("po.wallet_payment_operator='" . pSQL($walletOp) . "'");
        }
        $group = (string)($filters['group'] ?? '');
        if ($group !== '') {
            $q->where("po.op_group='" . pSQL($group) . "'");
        }

        $type = (string)($filters['type'] ?? '');
        if ($type !== '') {
            $q->where("po.op_type='" . pSQL($type) . "'");
        }
        $paymentId = (string)($filters['payment_id'] ?? '');
        if ($paymentId !== '') {
            $q->where("po.payment_id='" . pSQL($paymentId) . "'");
        }
        $participant = (string)($filters['participant_login'] ?? '');
        if ($participant !== '') {
            $q->where("po.participant_login LIKE '%" . pSQL($participant) . "%' ");
        }
        return $q;
    }
}
