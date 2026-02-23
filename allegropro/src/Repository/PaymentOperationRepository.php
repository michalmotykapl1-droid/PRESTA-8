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

    public function __construct()
    {
        $this->table = _DB_PREFIX_ . 'allegropro_payment_operation';
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
        $start = pSQL($ymd . ' 00:00:00');
        $end = pSQL(date('Y-m-d', strtotime($ymd . ' +1 day')) . ' 00:00:00');
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

    private function baseQuery(int $accountId, string $dateFrom, string $dateTo, array $filters): DbQuery
    {
        $q = new DbQuery();
        $q->from('allegropro_payment_operation', 'po');

        $start = pSQL($dateFrom . ' 00:00:00');
        $end = pSQL($dateTo . ' 23:59:59');

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
