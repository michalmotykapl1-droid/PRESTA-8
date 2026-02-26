<?php

namespace AllegroPro\Service;

/**
 * Stores enrichment failures and provides throttling (skip_until) so we don't hammer Allegro API.
 *
 * Table: {prefix}allegropro_order_enrich_skip
 */
class OrderEnrichSkipService
{
    public function ensureSchema(): void
    {
        try {
            $p = _DB_PREFIX_;
            $engine = _MYSQL_ENGINE_;
            $sql = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_order_enrich_skip` (
                `id_allegropro_order_enrich_skip` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_allegropro_account` INT UNSIGNED NOT NULL,
                `order_id` VARCHAR(64) NOT NULL,
                `last_code` INT NULL,
                `last_error` VARCHAR(255) NULL,
                `last_attempt_at` DATETIME NOT NULL,
                `attempts` INT UNSIGNED NOT NULL DEFAULT 1,
                `skip_until` DATETIME NULL,
                PRIMARY KEY (`id_allegropro_order_enrich_skip`),
                UNIQUE KEY `uniq_acc_order` (`id_allegropro_account`,`order_id`),
                KEY `idx_last_attempt` (`last_attempt_at`),
                KEY `idx_skip_until` (`skip_until`),
                KEY `idx_acc_skip_until` (`id_allegropro_account`,`skip_until`)
            ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4;";
            \Db::getInstance()->execute($sql);

            // schema evolution (older installs)
            $cols = \Db::getInstance()->executeS('SHOW COLUMNS FROM `' . bqSQL($p . 'allegropro_order_enrich_skip') . '`');
            $have = [];
            if (is_array($cols)) {
                foreach ($cols as $c) {
                    if (!empty($c['Field'])) {
                        $have[$c['Field']] = true;
                    }
                }
            }
            if (empty($have['skip_until'])) {
                \Db::getInstance()->execute('ALTER TABLE `' . bqSQL($p . 'allegropro_order_enrich_skip') . '` ADD COLUMN `skip_until` DATETIME NULL');
            }

            // indexes (ignore errors if exist)
            $idx = \Db::getInstance()->executeS('SHOW INDEX FROM `' . bqSQL($p . 'allegropro_order_enrich_skip') . '`');
            $haveIdx = [];
            if (is_array($idx)) {
                foreach ($idx as $i) {
                    if (!empty($i['Key_name'])) {
                        $haveIdx[$i['Key_name']] = true;
                    }
                }
            }
            if (empty($haveIdx['idx_skip_until'])) {
                \Db::getInstance()->execute('ALTER TABLE `' . bqSQL($p . 'allegropro_order_enrich_skip') . '` ADD KEY `idx_skip_until` (`skip_until`)');
            }
            if (empty($haveIdx['idx_acc_skip_until'])) {
                \Db::getInstance()->execute('ALTER TABLE `' . bqSQL($p . 'allegropro_order_enrich_skip') . '` ADD KEY `idx_acc_skip_until` (`id_allegropro_account`,`skip_until`)');
            }
        } catch (\Throwable $e) {
            // never hard-fail
        }
    }

    public function normalize(string $id): string
    {
        $id = trim($id);
        if ($id === '') {
            return '';
        }
        return strtolower(str_replace(['-', '_'], '', $id));
    }

    /**
     * Returns skip_until timestamp for a given error code and attempts count.
     */
    public function computeDelaySeconds(int $code, int $attempts): int
    {
        $attempts = max(1, $attempts);

        // base delays + caps
        $base = 1800; // 30 min
        $cap = 4 * 3600; // 4h

        if ($code === 404) {
            $base = 30 * 86400; // 30d
            $cap = 120 * 86400; // 120d
        } elseif ($code === 403) {
            $base = 7 * 86400; // 7d
            $cap = 30 * 86400; // 30d
        } elseif (in_array($code, [500, 502, 503], true)) {
            $base = 6 * 3600; // 6h
            $cap = 24 * 3600; // 24h
        } elseif ($code === 429) {
            $base = 2 * 3600; // 2h
            $cap = 24 * 3600; // 24h
        } elseif ($code === 0) {
            $base = 30 * 60; // 30 min
            $cap = 4 * 3600; // 4h
        }

        $factor = 1;
        if ($attempts > 1) {
            $factor = min(16, 2 ** ($attempts - 1));
        }
        $delay = (int)min($cap, $base * $factor);
        return max(60, $delay); // at least 60s
    }

    /**
     * Upsert skip record. Returns ['attempts'=>int,'skip_until'=>string]
     */
    public function mark(int $accountId, string $orderId, int $code, string $error = ''): array
    {
        $orderId = trim($orderId);
        if ($accountId <= 0 || $orderId === '') {
            return ['attempts' => 0, 'skip_until' => null];
        }

        $this->ensureSchema();

        $p = _DB_PREFIX_;
        $now = date('Y-m-d H:i:s');
        $orderEsc = pSQL($orderId);

        $err = trim((string)$error);
        if ($err !== '' && \Tools::strlen($err) > 255) {
            $err = \Tools::substr($err, 0, 255);
        }
        $errEsc = $err === '' ? 'NULL' : "'" . pSQL($err) . "'";

        // fetch current attempts to compute backoff
        $row = \Db::getInstance()->getRow(
            "SELECT attempts, skip_until, last_attempt_at FROM `{$p}allegropro_order_enrich_skip`
             WHERE id_allegropro_account=" . (int)$accountId . " AND order_id='{$orderEsc}'"
        );
        $attempts = (int)($row['attempts'] ?? 0);
        $attempts = max(0, $attempts) + 1;

        $delay = $this->computeDelaySeconds($code, $attempts);
        $skipUntil = date('Y-m-d H:i:s', time() + $delay);
        $skipEsc = pSQL($skipUntil);

        $sql = "INSERT INTO `{$p}allegropro_order_enrich_skip`
                    (id_allegropro_account, order_id, last_code, last_error, last_attempt_at, attempts, skip_until)
                VALUES
                    (" . (int)$accountId . ", '{$orderEsc}', " . (int)$code . ", {$errEsc}, '" . pSQL($now) . "', 1, '{$skipEsc}')
                ON DUPLICATE KEY UPDATE
                    last_code=VALUES(last_code),
                    last_error=VALUES(last_error),
                    last_attempt_at=VALUES(last_attempt_at),
                    attempts=attempts+1,
                    skip_until=VALUES(skip_until)";
        try {
            \Db::getInstance()->execute($sql);
        } catch (\Throwable $e) {
            // ignore
        }

        return ['attempts' => $attempts, 'skip_until' => $skipUntil];
    }

    public function clear(int $accountId, string $orderId): void
    {
        $orderId = trim($orderId);
        if ($accountId <= 0 || $orderId === '') {
            return;
        }
        $this->ensureSchema();
        $p = _DB_PREFIX_;
        $orderEsc = pSQL($orderId);
        try {
            \Db::getInstance()->execute(
                "DELETE FROM `{$p}allegropro_order_enrich_skip` WHERE id_allegropro_account=" . (int)$accountId . " AND order_id='{$orderEsc}'"
            );
        } catch (\Throwable $e) {
            // ignore
        }
    }
}
