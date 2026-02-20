<?php
namespace AllegroPro\Repository;

use Db;
use Configuration;

class BillingEntryRepository
{
    public function ensureSchema(): void
    {
        $p = _DB_PREFIX_;
        $engine = _MYSQL_ENGINE_;

        // create table if missing
        $sql = "CREATE TABLE IF NOT EXISTS `{$p}allegropro_billing_entry` (
            `id_allegropro_billing_entry` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_allegropro_account` INT UNSIGNED NOT NULL,
            `billing_entry_id` VARCHAR(64) NOT NULL,
            `occurred_at` DATETIME NOT NULL,
            `type_id` VARCHAR(16) NULL,
            `type_name` VARCHAR(255) NULL,
            `offer_id` VARCHAR(64) NULL,
            `offer_name` VARCHAR(512) NULL,
            `order_id` VARCHAR(64) NULL,
            `value_amount` DECIMAL(20,2) NOT NULL DEFAULT 0.00,
            `value_currency` VARCHAR(3) NOT NULL DEFAULT 'PLN',
            `balance_amount` DECIMAL(20,2) NULL,
            `balance_currency` VARCHAR(3) NULL,
            `tax_percentage` DECIMAL(10,2) NULL,
            `tax_annotation` VARCHAR(32) NULL,
            `raw_json` LONGTEXT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id_allegropro_billing_entry`),
            UNIQUE KEY `uniq_billing_entry` (`billing_entry_id`),
            KEY `idx_acc_date` (`id_allegropro_account`,`occurred_at`),
            KEY `idx_order_id` (`order_id`),
            KEY `idx_offer_id` (`offer_id`)
        ) ENGINE={$engine} DEFAULT CHARSET=utf8mb4;";

        Db::getInstance()->execute($sql);

	    // For older installations: add missing columns & indexes safely.
        try {
            $cols = Db::getInstance()->executeS('SHOW COLUMNS FROM `' . bqSQL($p . 'allegropro_billing_entry') . '`');
            $have = [];
            if (is_array($cols)) {
                foreach ($cols as $c) {
                    if (!empty($c['Field'])) {
                        $have[$c['Field']] = true;
                    }
                }
            }

            $alter = [];
            if (empty($have['order_id'])) {
                $alter[] = 'ADD COLUMN `order_id` VARCHAR(64) NULL';
            }
	        // Used by settlements Step 2 to mark if order details are already complete.
	        if (empty($have['order_filled'])) {
	            $alter[] = 'ADD COLUMN `order_filled` TINYINT(1) NOT NULL DEFAULT 0';
	        }
            if (empty($have['raw_json'])) {
                $alter[] = 'ADD COLUMN `raw_json` LONGTEXT NULL';
            }
            if (empty($have['created_at'])) {
                $alter[] = 'ADD COLUMN `created_at` DATETIME NOT NULL';
            }
            if (empty($have['updated_at'])) {
                $alter[] = 'ADD COLUMN `updated_at` DATETIME NOT NULL';
            }
            if (!empty($alter)) {
                Db::getInstance()->execute('ALTER TABLE `' . bqSQL($p . 'allegropro_billing_entry') . '` ' . implode(', ', $alter));
            }

	        // Add indexes used by fast missing-order scan (ignore if already exist)
	        $idx = Db::getInstance()->executeS('SHOW INDEX FROM `' . bqSQL($p . 'allegropro_billing_entry') . '`');
	        $haveIdx = [];
	        if (is_array($idx)) {
	            foreach ($idx as $i) {
	                if (!empty($i['Key_name'])) {
	                    $haveIdx[$i['Key_name']] = true;
	                }
	            }
	        }
	        if (empty($haveIdx['idx_acc_filled_date'])) {
	            Db::getInstance()->execute('ALTER TABLE `' . bqSQL($p . 'allegropro_billing_entry') . '` ADD KEY `idx_acc_filled_date` (`id_allegropro_account`,`order_filled`,`occurred_at`)');
	        }
	        if (empty($haveIdx['idx_acc_filled_order'])) {
	            Db::getInstance()->execute('ALTER TABLE `' . bqSQL($p . 'allegropro_billing_entry') . '` ADD KEY `idx_acc_filled_order` (`id_allegropro_account`,`order_filled`,`order_id`)');
	        }

	        // One-time cleanup of legacy placeholders that should not be persisted.
	        $cleanupFlag = 'ALPRO_BILLING_PLACEHOLDER_CLEANUP_20260220';
	        if ((string)Configuration::get($cleanupFlag) !== '1') {
	            Db::getInstance()->execute(
	                'UPDATE `' . bqSQL($p . 'allegropro_billing_entry') . '` '
	                . "SET offer_id=NULL, offer_name=NULL "
	                . "WHERE offer_id='__ORDER__' OR offer_name='[opłata zamówienia]'"
	            );
	            Db::getInstance()->execute(
	                'UPDATE `' . bqSQL($p . 'allegropro_billing_entry') . '` '
	                . "SET tax_annotation=NULL "
	                . "WHERE tax_annotation='__NA__'"
	            );
	            Configuration::updateValue($cleanupFlag, '1');
	        }
        } catch (\Exception $e) {
            // do not break page load
        }
    }

    /**
     * Upsert billing entries.
     * @return array{inserted:int,updated:int}
     */
    public function upsertEntries(int $accountId, array $entries, bool $forceUpdateAll = false): array
    {
        $inserted = 0;
        $updated = 0;

        // 1) Zbierz billing_entry_id z aktualnej paczki i pobierz istniejące rekordy jednym SELECT-em.
        $idsSql = [];
        foreach ($entries as $e) {
            if (!is_array($e) || empty($e['id'])) {
                continue;
            }
            $idsSql[] = "'" . pSQL((string)$e['id']) . "'";
            if (count($idsSql) >= 220) {
                // limit bezpieczeństwa — API zwraca zwykle 100/200
                break;
            }
        }

        $existingMap = [];
        if (!empty($idsSql)) {
            $sql = 'SELECT id_allegropro_billing_entry, billing_entry_id, id_allegropro_account, occurred_at, '
                . 'type_id, type_name, offer_id, offer_name, order_id, '
                . 'value_amount, value_currency, balance_amount, balance_currency, tax_percentage, tax_annotation, raw_json '
                . 'FROM `' . _DB_PREFIX_ . 'allegropro_billing_entry` '
                . 'WHERE billing_entry_id IN (' . implode(',', $idsSql) . ')';
            $rows = Db::getInstance()->executeS($sql) ?: [];
            foreach ($rows as $r) {
                if (!empty($r['billing_entry_id'])) {
                    $existingMap[(string)$r['billing_entry_id']] = $r;
                }
            }
        }

        foreach ($entries as $e) {
            if (!is_array($e) || empty($e['id'])) {
                continue;
            }

            $billingId = (string)$e['id'];
            $billingIdEsc = pSQL($billingId);

            $occurredAt = $this->toMysqlDatetime((string)($e['occurredAt'] ?? ''));
            if ($occurredAt === null) {
                continue;
            }

	    $typeId = pSQL((string)($e['type']['id'] ?? ''));
	    $typeNameRaw = trim((string)($e['type']['name'] ?? ''));
	    $typeName = pSQL($typeNameRaw);

	    // Offer fields are not guaranteed in all billing-entry types.
	    // Extract more robustly (offer, offerId, lineItem.offer, etc.).
	    [$offerIdRaw, $offerNameRaw] = $this->extractOfferIdName($e);
	    $offerId = pSQL($offerIdRaw);
	    $offerName = pSQL($offerNameRaw);

	    $orderIdRaw = $this->extractOrderId($e);
	    $orderId = pSQL($orderIdRaw);

            $valAmount = (float)($e['value']['amount'] ?? 0);
            $valCurrency = pSQL((string)($e['value']['currency'] ?? 'PLN'));
            $balAmount = isset($e['balance']['amount']) ? (float)$e['balance']['amount'] : null;
            $balCurrency = pSQL((string)($e['balance']['currency'] ?? ''));
            $taxPerc = isset($e['tax']['percentage']) ? (float)$e['tax']['percentage'] : null;
	    $taxAnnRaw = trim((string)($e['tax']['annotation'] ?? ''));
	    // tax.annotation is optional and often absent; do NOT treat it as required.
	    $taxAnn = pSQL($taxAnnRaw);
            $raw = pSQL(json_encode($e, JSON_UNESCAPED_UNICODE));
            $now = date('Y-m-d H:i:s');

            $existsRow = $existingMap[$billingId] ?? null;

            if (!$existsRow) {
                // INSERT
                $row = [
                    'id_allegropro_account' => (int)$accountId,
                    'billing_entry_id' => $billingIdEsc,
                    'occurred_at' => $occurredAt,
                    'type_id' => $typeId ?: null,
                    'type_name' => $typeName ?: null,
	                'offer_id' => $offerIdRaw !== '' ? $offerId : null,
	                'offer_name' => $offerNameRaw !== '' ? $offerName : null,
	                'order_id' => $orderIdRaw !== '' ? $orderId : null,
                    'value_amount' => (float)$valAmount,
                    'value_currency' => $valCurrency ?: 'PLN',
                    'balance_amount' => $balAmount,
                    'balance_currency' => $balCurrency ?: null,
                    'tax_percentage' => $taxPerc,
	                'tax_annotation' => $taxAnnRaw !== '' ? $taxAnn : null,
                    'raw_json' => $raw ?: null,
                    'created_at' => pSQL($now),
                    'updated_at' => pSQL($now),
                ];
                Db::getInstance()->insert('allegropro_billing_entry', $row);
                $inserted++;
                continue;
            }

            // UPDATE (tylko jeśli:
            // - forceUpdateAll = TRUE
            // - LUB w DB brakuje części danych, które już mamy z API)
            $idPk = (int)($existsRow['id_allegropro_billing_entry'] ?? 0);
            if (!$idPk) {
                continue;
            }

            $upd = [];

            if ($forceUpdateAll) {
                $upd = [
                    'id_allegropro_account' => (int)$accountId,
                    'billing_entry_id' => $billingIdEsc,
                    'occurred_at' => $occurredAt,
                    'type_id' => $typeId ?: null,
                    'type_name' => $typeName ?: null,
	                    // If offer is not present for given entry, keep NULL (do not write placeholders like __ORDER__).
	                    'offer_id' => $offerIdRaw !== '' ? $offerId : null,
	                    'offer_name' => $offerNameRaw !== '' ? $offerName : null,
	                    'order_id' => $orderIdRaw !== '' ? $orderId : null,
                    'value_amount' => (float)$valAmount,
                    'value_currency' => $valCurrency ?: 'PLN',
                    'balance_amount' => $balAmount,
                    'balance_currency' => $balCurrency ?: null,
                    'tax_percentage' => $taxPerc,
	                    // tax.annotation is optional
	                    'tax_annotation' => $taxAnnRaw !== '' ? $taxAnn : null,
                    'raw_json' => $raw ?: null,
                ];
            } else {
                // dogrywaj tylko braki (oszczędza czas + nie generuje "aktualizacji" bez sensu)
                $isEmpty = function ($v): bool {
                    return $v === null || $v === '';
                };

	                // Clean up legacy placeholders written by older patches (one-time).
	                // We want offer fields empty/NULL, not values like '__ORDER__' / '[opłata zamówienia]'.
	                $dbOfferId = (string)($existsRow['offer_id'] ?? '');
	                $dbOfferName = (string)($existsRow['offer_name'] ?? '');
	                if ($dbOfferId === '__ORDER__' || $dbOfferName === '[opłata zamówienia]') {
	                    if ($offerIdRaw !== '' || $offerNameRaw !== '') {
	                        $upd['offer_id'] = $offerIdRaw !== '' ? $offerId : null;
	                        $upd['offer_name'] = $offerNameRaw !== '' ? $offerName : null;
	                    } else {
	                        $upd['offer_id'] = null;
	                        $upd['offer_name'] = null;
	                    }
	                }

                if ($isEmpty($existsRow['occurred_at'] ?? null) || (string)($existsRow['occurred_at'] ?? '') === '0000-00-00 00:00:00') {
                    $upd['occurred_at'] = $occurredAt;
                }

                if ($isEmpty($existsRow['type_id'] ?? null) && $typeId !== '') {
                    $upd['type_id'] = $typeId;
                }
                if ($isEmpty($existsRow['type_name'] ?? null) && $typeName !== '') {
                    $upd['type_name'] = $typeName;
                }

	                if ($isEmpty($existsRow['offer_id'] ?? null) && $offerIdRaw !== '') {
	                    $upd['offer_id'] = $offerId;
	                }
	                if ($isEmpty($existsRow['offer_name'] ?? null) && $offerNameRaw !== '') {
	                    $upd['offer_name'] = $offerName;
	                }

	                if ($isEmpty($existsRow['order_id'] ?? null) && $orderIdRaw !== '') {
	                    $upd['order_id'] = $orderId;
                }

                // wartości liczbowe: popraw tylko gdy ewidentnie puste/zerowe (po starym błędzie)
                $dbVal = isset($existsRow['value_amount']) ? (float)$existsRow['value_amount'] : 0.0;
                if (abs($dbVal) < 0.00001 && abs((float)$valAmount) > 0.00001) {
                    $upd['value_amount'] = (float)$valAmount;
                }
                if ($isEmpty($existsRow['value_currency'] ?? null) && $valCurrency !== '') {
                    $upd['value_currency'] = $valCurrency;
                }

                if ($existsRow['balance_amount'] === null && $balAmount !== null) {
                    $upd['balance_amount'] = $balAmount;
                }
                if ($isEmpty($existsRow['balance_currency'] ?? null) && $balCurrency !== '') {
                    $upd['balance_currency'] = $balCurrency;
                }
                if ($existsRow['tax_percentage'] === null && $taxPerc !== null) {
                    $upd['tax_percentage'] = $taxPerc;
                }
	                // tax.annotation is optional; never update just because it's empty.
	                // Cleanup legacy placeholder '__NA__' if present.
	                $dbTax = (string)($existsRow['tax_annotation'] ?? '');
	                if ($dbTax === '__NA__' && $taxAnnRaw === '') {
	                    $upd['tax_annotation'] = null;
	                } elseif (($dbTax === '' || $dbTax === '__NA__') && $taxAnnRaw !== '') {
	                    $upd['tax_annotation'] = $taxAnn;
	                }

	                if ($isEmpty($existsRow['raw_json'] ?? null) && $raw !== '') {
                    $upd['raw_json'] = $raw;
                }
            }

            if (!empty($upd)) {
                $upd['updated_at'] = pSQL($now);
                Db::getInstance()->update('allegropro_billing_entry', $upd, 'id_allegropro_billing_entry = ' . $idPk);
                $updated++;
            }
        }

        return ['inserted' => $inserted, 'updated' => $updated];
    }

    /**
     * Max occurred_at w zakresie (dla szybkiej synchronizacji "tylko nowe").
     */
    public function getMaxOccurredAtInRange(int $accountId, string $dateFrom, string $dateTo): ?string
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');
        $sql = "SELECT MAX(occurred_at) FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'";
        $v = Db::getInstance()->getValue($sql);
        if (!$v || !is_string($v)) {
            return null;
        }
        $v = trim($v);
        return $v !== '' ? $v : null;
    }

    /**
     * Try to extract Allegro order id / checkout form id from multiple possible shapes.
     */
    private function extractOrderId(array $e): string
    {
        // Najczęstsze przypadki (różne warianty struktury API).
        // 1) order: { id: "..." }
        if (!empty($e['order']) && is_array($e['order'])) {
            $orderId = (string)($e['order']['id'] ?? '');
            if ($orderId !== '') {
                return $orderId;
            }
            // czasem: order: { checkoutFormId: "..." }
            $orderId = (string)($e['order']['checkoutFormId'] ?? $e['order']['checkout_form_id'] ?? '');
            if ($orderId !== '') {
                return $orderId;
            }
        }

        // 2) order: "..." (string)
        if (!empty($e['order']) && is_string($e['order'])) {
            $orderId = trim((string)$e['order']);
            if ($orderId !== '') {
                return $orderId;
            }
        }

        // 3) top-level
        $orderId = (string)($e['orderId'] ?? $e['order_id'] ?? '');
        if ($orderId !== '') {
            return $orderId;
        }
        $orderId = (string)($e['checkoutFormId'] ?? $e['checkout_form_id'] ?? '');
        if ($orderId !== '') {
            return $orderId;
        }

        // 4) checkoutForm: { id: "..." }
        if (!empty($e['checkoutForm']) && is_array($e['checkoutForm'])) {
            $orderId = (string)($e['checkoutForm']['id'] ?? $e['checkoutForm']['checkoutFormId'] ?? $e['checkoutForm']['checkout_form_id'] ?? '');
            if ($orderId !== '') {
                return $orderId;
            }
        }

        // Sometimes in additionalInfo.
        if (!empty($e['additionalInfo']) && is_array($e['additionalInfo'])) {
            foreach ($e['additionalInfo'] as $ai) {
                if (!is_array($ai)) {
                    continue;
                }
                $type = strtolower((string)($ai['type'] ?? ''));
                $val = (string)($ai['value'] ?? '');
                if ($val === '') {
                    continue;
                }

                // Known variants in the wild.
                if (in_array($type, ['orderid', 'order_id', 'checkoutformid', 'checkout_form_id', 'checkoutform.id', 'checkoutform', 'checkout-form-id'], true)) {
                    return $val;
                }
            }
        }

        return '';
    }

	/**
	 * Extract offer id/name from various known billing-entry shapes.
	 * Some entry types (delivery/smart/fees) may not have an offer at all.
	 * @return array{0:string,1:string}
	 */
	private function extractOfferIdName(array $e): array
	{
	    $id = '';
	    $name = '';

	    // Most common: offer: { id, name }
	    if (isset($e['offer']) && is_array($e['offer'])) {
	        $id = (string)($e['offer']['id'] ?? $id);
	        $name = (string)($e['offer']['name'] ?? $name);
	    }

	    // Alternate keys
	    if ($id === '' && isset($e['offerId'])) {
	        $id = (string)$e['offerId'];
	    }
	    if ($name === '' && isset($e['offerName'])) {
	        $name = (string)$e['offerName'];
	    }

	    // Sometimes nested under lineItem
	    if (isset($e['lineItem']) && is_array($e['lineItem'])) {
	        $li = $e['lineItem'];
	        if ($id === '' && isset($li['offerId'])) {
	            $id = (string)$li['offerId'];
	        }
	        if ($name === '' && isset($li['offerName'])) {
	            $name = (string)$li['offerName'];
	        }
	        if (isset($li['offer']) && is_array($li['offer'])) {
	            if ($id === '' && isset($li['offer']['id'])) {
	                $id = (string)$li['offer']['id'];
	            }
	            if ($name === '' && isset($li['offer']['name'])) {
	                $name = (string)$li['offer']['name'];
	            }
	        }
	    }

	    $id = trim((string)$id);
	    $name = trim((string)$name);

	    // Treat legacy placeholders as empty.
	    if ($id === '__ORDER__' || $name === '[opłata zamówienia]') {
	        $id = '';
	        $name = '';
	    }

	    return [$id, $name];
	}

    /**
     * SQL: filtr "opłat".
     *
     * Allegro Billing Entries potrafią zawierać też operacje "środków" (wpłaty/wypłaty),
     * które rozwalają sumy opłat (bo są dodatnie).
     *
     * Zostawiamy:
     * - wszystkie ujemne wpisy (opłaty)
     * - dodatnie tylko jeśli są ewidentną korektą/rabatem/zwrotem
     */
    private function buildFeeWhereSql(string $typeNameExpr): string
    {
        // Zasada:
        // - bierzemy wszystkie ujemne operacje (opłaty)
        // - oraz dodatnie tylko wtedy, gdy są korektą/rabatem/zwrotem
        // - wykluczamy przepływy środków (wpłaty/wypłaty/przelewy/"środki"), bo one
        //   potrafią „zerować” sumy opłat i nie odpowiadają widokowi w Sales Center.

        $include = "(value_amount < 0 OR {$typeNameExpr} LIKE '%zwrot%' OR {$typeNameExpr} LIKE '%rabat%' OR {$typeNameExpr} LIKE '%korekt%' OR {$typeNameExpr} LIKE '%rekompens%')";
        $exclude = "({$typeNameExpr} LIKE '%wypł%' OR {$typeNameExpr} LIKE '%wypl%' OR {$typeNameExpr} LIKE '%wpł%' OR {$typeNameExpr} LIKE '%wpl%' OR {$typeNameExpr} LIKE '%przelew%' OR {$typeNameExpr} LIKE '%środk%' OR {$typeNameExpr} LIKE '%srodk%')";
        return "({$include} AND NOT {$exclude})";
    }

    /**
     * Pobiera wpisy billingowe dla zamówienia, ale akceptuje wiele kandydatów order_id.
     * Używane, gdy Allegro zwraca order_id w innym formacie (np. bez myślników).
     */
    public function listForOrderCandidates(int $accountId, array $orderIds, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $vals = [];
        foreach ($orderIds as $id) {
            $id = trim((string)$id);
            if ($id === '') {
                continue;
            }
            $vals[] = "'" . pSQL($id) . "'";
        }
        if (empty($vals)) {
            return [];
        }

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);

        $sql = "SELECT *\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`\n"
            . "WHERE id_allegropro_account=" . (int)$accountId . "\n"
            . "  AND order_id IN (" . implode(',', $vals) . ")\n"
            . "  AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'\n"
            . "  AND {$feeWhere}\n"
            . "ORDER BY occurred_at DESC";

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function getCategorySums(int $accountId, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);

        // UWAGA: total i kategorie liczymy TYLKO po opłatach (patrz $feeWhere)
        // Wcześniej feeWhere było w CASE, co w praktyce (przy pewnych danych) potrafiło
        // dawać niespójne sumy. Tu filtrujemy w WHERE i sumujemy prosto.
        $sql = "SELECT
            SUM(value_amount) AS total,
            SUM(CASE WHEN (type_id='SUC' OR {$tn} LIKE '%prowiz%') THEN value_amount ELSE 0 END) AS commission,
            SUM(CASE WHEN ({$tn} LIKE '%smart%') THEN value_amount ELSE 0 END) AS smart,
            SUM(CASE WHEN ({$tn} LIKE '%dostaw%' OR {$tn} LIKE '%przesy%') THEN value_amount ELSE 0 END) AS delivery,
            SUM(CASE WHEN ({$tn} LIKE '%promow%' OR {$tn} LIKE '%reklam%') THEN value_amount ELSE 0 END) AS promotion,
            SUM(CASE WHEN ({$tn} LIKE '%zwrot%' OR {$tn} LIKE '%rabat%' OR {$tn} LIKE '%korekt%' OR {$tn} LIKE '%rekompens%') THEN value_amount ELSE 0 END) AS refunds
        FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
        WHERE id_allegropro_account=" . (int)$accountId . "
          AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
          AND {$feeWhere}";

        $row = Db::getInstance()->getRow($sql) ?: [];
        return [
            'total' => (float)($row['total'] ?? 0),
            'commission' => (float)($row['commission'] ?? 0),
            'smart' => (float)($row['smart'] ?? 0),
            'delivery' => (float)($row['delivery'] ?? 0),
            'promotion' => (float)($row['promotion'] ?? 0),
            'refunds' => (float)($row['refunds'] ?? 0),
        ];
    }

    public function sumByOrder(int $accountId, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);

        $sql = "SELECT order_id, SUM(value_amount) AS sum_amount
                FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND order_id IS NOT NULL AND order_id <> ''
                  AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
                  AND {$feeWhere}
                GROUP BY order_id";

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $map[(string)$r['order_id']] = (float)$r['sum_amount'];
        }
        return $map;
    }

    public function listForOrder(int $accountId, string $orderId, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');
        $orderId = pSQL($orderId);

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);

        $sql = "SELECT *
                FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND order_id='" . $orderId . "'
                  AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
                  AND {$feeWhere}
                ORDER BY occurred_at DESC";

        return Db::getInstance()->executeS($sql) ?: [];
    }

    public function countUnassigned(int $accountId, string $dateFrom, string $dateTo): int
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);

        $sql = "SELECT COUNT(*)
                FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND (order_id IS NULL OR order_id='')
                  AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'
                  AND {$feeWhere}";

        return (int)Db::getInstance()->getValue($sql);
    }



    /**
     * Buduje bezpieczny warunek IN() dla wielu kont.
     * @param int[] $accountIds
     */
    private function buildAccountInWhere(array $accountIds): string
    {
        $ids = [];
        foreach ($accountIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if (empty($ids)) {
            return 'id_allegropro_account=0';
        }
        return 'id_allegropro_account IN (' . implode(',', $ids) . ')';
    }

    /**
     * Multi-account wariant getCategorySums().
     * @param int[] $accountIds
     */
    public function getCategorySumsMulti(array $accountIds, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);
        $accWhere = $this->buildAccountInWhere($accountIds);

        $sql = "SELECT
"
            . "    SUM(value_amount) AS total,
"
            . "    SUM(CASE WHEN (type_id='SUC' OR {$tn} LIKE '%prowiz%') THEN value_amount ELSE 0 END) AS commission,
"
            . "    SUM(CASE WHEN ({$tn} LIKE '%smart%') THEN value_amount ELSE 0 END) AS smart,
"
            . "    SUM(CASE WHEN ({$tn} LIKE '%dostaw%' OR {$tn} LIKE '%przesy%') THEN value_amount ELSE 0 END) AS delivery,
"
            . "    SUM(CASE WHEN ({$tn} LIKE '%promow%' OR {$tn} LIKE '%reklam%') THEN value_amount ELSE 0 END) AS promotion,
"
            . "    SUM(CASE WHEN ({$tn} LIKE '%zwrot%' OR {$tn} LIKE '%rabat%' OR {$tn} LIKE '%korekt%' OR {$tn} LIKE '%rekompens%') THEN value_amount ELSE 0 END) AS refunds
"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
"
            . "WHERE {$accWhere}
"
            . "  AND occurred_at BETWEEN '{$from}' AND '{$to}'
"
            . "  AND {$feeWhere}";

        $row = Db::getInstance()->getRow($sql) ?: [];
        return [
            'total' => (float)($row['total'] ?? 0),
            'commission' => (float)($row['commission'] ?? 0),
            'smart' => (float)($row['smart'] ?? 0),
            'delivery' => (float)($row['delivery'] ?? 0),
            'promotion' => (float)($row['promotion'] ?? 0),
            'refunds' => (float)($row['refunds'] ?? 0),
        ];
    }

    /**
     * Multi-account wariant sumByOrder() - zwraca mapę: [accountId][orderId] = suma.
     * @param int[] $accountIds
     * @return array<int, array<string, float>>
     */
    public function sumByOrderMulti(array $accountIds, string $dateFrom, string $dateTo): array
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);
        $accWhere = $this->buildAccountInWhere($accountIds);

        $sql = "SELECT id_allegropro_account, order_id, SUM(value_amount) AS sum_amount
"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
"
            . "WHERE {$accWhere}
"
            . "  AND order_id IS NOT NULL AND order_id <> ''
"
            . "  AND occurred_at BETWEEN '{$from}' AND '{$to}'
"
            . "  AND {$feeWhere}
"
            . "GROUP BY id_allegropro_account, order_id";

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $aid = (int)($r['id_allegropro_account'] ?? 0);
            if ($aid <= 0) {
                continue;
            }
            if (!isset($map[$aid])) {
                $map[$aid] = [];
            }
            $map[$aid][(string)$r['order_id']] = (float)$r['sum_amount'];
        }
        return $map;
    }

    /**
     * Multi-account wariant countUnassigned().
     * @param int[] $accountIds
     */
    public function countUnassignedMulti(array $accountIds, string $dateFrom, string $dateTo): int
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);
        $accWhere = $this->buildAccountInWhere($accountIds);

        $sql = "SELECT COUNT(*)
"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
"
            . "WHERE {$accWhere}
"
            . "  AND (order_id IS NULL OR order_id='')
"
            . "  AND occurred_at BETWEEN '{$from}' AND '{$to}'
"
            . "  AND {$feeWhere}";

        return (int)Db::getInstance()->getValue($sql);
    }

    /**
     * Multi-account wariant countInRange().
     * @param int[] $accountIds
     */
    public function countInRangeMulti(array $accountIds, string $dateFrom, string $dateTo): int
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $accWhere = $this->buildAccountInWhere($accountIds);
        $sql = "SELECT COUNT(*)
"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
"
            . "WHERE {$accWhere}
"
            . "  AND occurred_at BETWEEN '{$from}' AND '{$to}'";

        return (int)Db::getInstance()->getValue($sql);
    }
    private function toMysqlDatetime(string $iso): ?string
    {
        if ($iso === '') {
            return null;
        }

        // Use DateTime parser to support: Z, milliseconds, and timezone offsets (+01:00).
        try {
            $dt = new \DateTimeImmutable($iso);
        } catch (\Exception $e) {
            return null;
        }

        $tzId = (string)(Configuration::get('PS_TIMEZONE') ?: 'UTC');
        try {
            $tz = new \DateTimeZone($tzId);
        } catch (\Exception $e) {
            $tz = new \DateTimeZone('UTC');
        }

        return $dt->setTimezone($tz)->format('Y-m-d H:i:s');
    }

    public function countInRange(int $accountId, string $dateFrom, string $dateTo): int
    {
        $from = pSQL($dateFrom . ' 00:00:00');
        $to = pSQL($dateTo . ' 23:59:59');

        $sql = "SELECT COUNT(*)
                FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`
                WHERE id_allegropro_account=" . (int)$accountId . "
                  AND occurred_at BETWEEN '" . $from . "' AND '" . $to . "'";

        return (int)Db::getInstance()->getValue($sql);
    }


    /**
     * Pobiera wpisy billingowe dla zamówienia, ale akceptuje wiele kandydatów order_id.
     * BEZ filtra daty (wszystkie operacje dla zamówienia).
     */
    public function listForOrderCandidatesNoDate(int $accountId, array $orderIds): array
    {
        $vals = [];
        foreach ($orderIds as $id) {
            $id = trim((string)$id);
            if ($id === '') {
                continue;
            }
            $vals[] = "'" . pSQL($id) . "'";
            if (count($vals) >= 2000) {
                break;
            }
        }
        if (empty($vals)) {
            return [];
        }

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);

        $sql = "SELECT *\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`\n"
            . "WHERE id_allegropro_account=" . (int)$accountId . "\n"
            . "  AND order_id IN (" . implode(',', $vals) . ")\n"
            . "  AND {$feeWhere}\n"
            . "ORDER BY occurred_at DESC";

        return Db::getInstance()->executeS($sql) ?: [];
    }

    /**
     * Multi-account: suma opłat (feeWhere) dla podanych kandydatów order_id, BEZ filtra daty.
     * @param int[] $accountIds
     * @param string[] $orderIds
     * @return array<int, array<string, float>>
     */
    public function sumByOrderIdsMultiNoDate(array $accountIds, array $orderIds): array
    {
        $accWhere = $this->buildAccountInWhere($accountIds);

        $vals = [];
        foreach ($orderIds as $id) {
            $id = trim((string)$id);
            if ($id === '') {
                continue;
            }
            $vals[] = "'" . pSQL($id) . "'";
            if (count($vals) >= 4000) {
                break;
            }
        }
        if (empty($vals)) {
            return [];
        }

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);

        $sql = "SELECT id_allegropro_account, order_id, SUM(value_amount) AS sum_amount\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`\n"
            . "WHERE {$accWhere}\n"
            . "  AND order_id IN (" . implode(',', $vals) . ")\n"
            . "  AND {$feeWhere}\n"
            . "GROUP BY id_allegropro_account, order_id";

        $rows = Db::getInstance()->executeS($sql) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $aid = (int)($r['id_allegropro_account'] ?? 0);
            if ($aid <= 0) {
                continue;
            }
            if (!isset($map[$aid])) {
                $map[$aid] = [];
            }
            $map[$aid][(string)$r['order_id']] = (float)($r['sum_amount'] ?? 0);
        }
        return $map;
    }

    /**
     * Multi-account: kategorie opłat (feeWhere) dla podanych kandydatów order_id, BEZ filtra daty.
     * @param int[] $accountIds
     * @param string[] $orderIds
     */
    public function getCategorySumsForOrderIdsMultiNoDate(array $accountIds, array $orderIds): array
    {
        $accWhere = $this->buildAccountInWhere($accountIds);

        $vals = [];
        foreach ($orderIds as $id) {
            $id = trim((string)$id);
            if ($id === '') {
                continue;
            }
            $vals[] = "'" . pSQL($id) . "'";
            if (count($vals) >= 4000) {
                break;
            }
        }
        if (empty($vals)) {
            return [
                'total' => 0.0,
                'commission' => 0.0,
                'smart' => 0.0,
                'delivery' => 0.0,
                'promotion' => 0.0,
                'refunds' => 0.0,
            ];
        }

        $tn = "LOWER(IFNULL(type_name,''))";
        $feeWhere = $this->buildFeeWhereSql($tn);

        $sql = "SELECT\n"
            . "    SUM(value_amount) AS total,\n"
            . "    SUM(CASE WHEN (type_id='SUC' OR {$tn} LIKE '%prowiz%') THEN value_amount ELSE 0 END) AS commission,\n"
            . "    SUM(CASE WHEN ({$tn} LIKE '%smart%') THEN value_amount ELSE 0 END) AS smart,\n"
            . "    SUM(CASE WHEN ({$tn} LIKE '%dostaw%' OR {$tn} LIKE '%przesy%') THEN value_amount ELSE 0 END) AS delivery,\n"
            . "    SUM(CASE WHEN ({$tn} LIKE '%promow%' OR {$tn} LIKE '%reklam%') THEN value_amount ELSE 0 END) AS promotion,\n"
            . "    SUM(CASE WHEN ({$tn} LIKE '%zwrot%' OR {$tn} LIKE '%rabat%' OR {$tn} LIKE '%korekt%' OR {$tn} LIKE '%rekompens%') THEN value_amount ELSE 0 END) AS refunds\n"
            . "FROM `" . _DB_PREFIX_ . "allegropro_billing_entry`\n"
            . "WHERE {$accWhere}\n"
            . "  AND order_id IN (" . implode(',', $vals) . ")\n"
            . "  AND {$feeWhere}";

        $row = Db::getInstance()->getRow($sql) ?: [];
        return [
            'total' => (float)($row['total'] ?? 0),
            'commission' => (float)($row['commission'] ?? 0),
            'smart' => (float)($row['smart'] ?? 0),
            'delivery' => (float)($row['delivery'] ?? 0),
            'promotion' => (float)($row['promotion'] ?? 0),
            'refunds' => (float)($row['refunds'] ?? 0),
        ];
    }

}