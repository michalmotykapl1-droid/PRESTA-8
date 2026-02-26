<?php
/**
 * Klasa PickingSessionRepository
 * Wersja: 6.0 (Employee scoped sessions + Smart Swap columns)
 *
 * WAŻNE:
 * - Nie wymaga reinstalacji modułu.
 * - Dodaje kolumnę `id_employee` do tabel sesyjnych (jeśli brak) i filtruje dane per pracownik.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class PickingSessionRepository
{
    private $table = 'modulzamowien_picking_session';
    private $table_files = 'modulzamowien_picking_files';

    public function __construct()
    {
        $this->ensureEmployeeScope();
        $this->fixTableStructure();
        $this->checkAndAddAltColumns();
    }

    /**
     * Pobiera ID aktualnie zalogowanego pracownika.
     * Fallback: cookie psAdmin (mobile.php).
     */
    private function getEmployeeId()
    {
        try {
            $ctx = Context::getContext();
            if ($ctx && isset($ctx->employee) && (int)$ctx->employee->id > 0) {
                return (int)$ctx->employee->id;
            }
        } catch (Exception $e) {
            // ignore
        }

        try {
            $cookie = new Cookie('psAdmin');
            if (!empty($cookie->id_employee)) {
                return (int)$cookie->id_employee;
            }
        } catch (Exception $e) {
            // ignore
        }

        return 0;
    }

    /**
     * Dodaje kolumny/idxy wymagane do separacji sesji per pracownik.
     * Nie usuwa danych – ewentualnie "przypisuje" legacy dane (id_employee=0) do aktualnego pracownika,
     * żeby po wdrożeniu patcha nie wyglądało jakby sesja zniknęła.
     */
    private function ensureEmployeeScope()
    {
        $db = Db::getInstance();

        // 1) Kolumna id_employee w picking_session
        $check = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . $this->table . "` LIKE 'id_employee'");
        if (empty($check)) {
            $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` ADD `id_employee` INT(11) NOT NULL DEFAULT 0 AFTER `id_item`");
        }

        // 2) Kolumna id_employee w picking_files
        $checkFiles = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . $this->table_files . "` LIKE 'id_employee'");
        if (empty($checkFiles)) {
            $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table_files . "` ADD `id_employee` INT(11) NOT NULL DEFAULT 0 AFTER `id_file`");
        }

        // 3) Legacy -> przypisz do aktualnego pracownika (żeby nie zniknęły dane po aktualizacji)
        $idEmployee = (int)$this->getEmployeeId();
        if ($idEmployee > 0) {
            $db->execute("UPDATE `" . _DB_PREFIX_ . $this->table . "` SET id_employee = " . (int)$idEmployee . " WHERE id_employee = 0");
            $db->execute("UPDATE `" . _DB_PREFIX_ . $this->table_files . "` SET id_employee = " . (int)$idEmployee . " WHERE id_employee = 0");
        }

        // 4) Index na id_employee (wydajność)
        $idx = $db->executeS("SHOW INDEX FROM `" . _DB_PREFIX_ . $this->table . "` WHERE Key_name = 'id_employee_idx'");
        if (empty($idx)) {
            $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` ADD KEY `id_employee_idx` (`id_employee`)");
        }
        $idx2 = $db->executeS("SHOW INDEX FROM `" . _DB_PREFIX_ . $this->table_files . "` WHERE Key_name = 'id_employee_idx'");
        if (empty($idx2)) {
            $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table_files . "` ADD KEY `id_employee_idx` (`id_employee`)");
        }

        // 5) Unique na picking_files: (id_employee, file_hash)
        $uniqFiles = $db->executeS("SHOW INDEX FROM `" . _DB_PREFIX_ . $this->table_files . "` WHERE Key_name = 'emp_hash_idx'");
        if (empty($uniqFiles)) {
            // Drop starego unikalnego indexu (file_hash)
            $old = $db->executeS("SHOW INDEX FROM `" . _DB_PREFIX_ . $this->table_files . "` WHERE Key_name = 'hash_idx'");
            if (!empty($old)) {
                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table_files . "` DROP INDEX `hash_idx`");
            }

            $ok = $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table_files . "` ADD UNIQUE KEY `emp_hash_idx` (`id_employee`,`file_hash`)");
            if (!$ok) {
                // Jeżeli są duplikaty - usuń powtórki (zostaw najstarszy wpis)
                $db->execute("DELETE f1 FROM `" . _DB_PREFIX_ . $this->table_files . "` f1
                              INNER JOIN `" . _DB_PREFIX_ . $this->table_files . "` f2
                              ON f1.id_employee = f2.id_employee AND f1.file_hash = f2.file_hash AND f1.id_file > f2.id_file");

                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table_files . "` ADD UNIQUE KEY `emp_hash_idx` (`id_employee`,`file_hash`)");
            }
        }
    }

    /**
     * Zapewnia unikalność rekordów kompletacji per pracownik: (id_employee, sku).
     */
    private function fixTableStructure()
    {
        $db = Db::getInstance();

        $exists = $db->executeS("SHOW INDEX FROM `" . _DB_PREFIX_ . $this->table . "` WHERE Key_name = 'emp_sku_unique'");
        if (empty($exists)) {
            // Usuń stare UNIQUE po sku (globalne)
            $old = $db->executeS("SHOW INDEX FROM `" . _DB_PREFIX_ . $this->table . "` WHERE Key_name = 'sku_unique'");
            if (!empty($old)) {
                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` DROP INDEX `sku_unique`");
            }

            $ok = $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` ADD UNIQUE KEY `emp_sku_unique` (`id_employee`,`sku`)");

            if (!$ok) {
                // Bezpieczne łączenie duplikatów (jeśli instalacja miała wcześniej brak unikalności)
                $db->execute("UPDATE `" . _DB_PREFIX_ . $this->table . "` t
                              JOIN (
                                  SELECT MIN(id_item) AS keep_id, id_employee, sku,
                                         SUM(qty_to_pick) AS qty_to_pick_sum,
                                         SUM(qty_picked) AS qty_picked_sum,
                                         SUM(qty_original) AS qty_original_sum
                                  FROM `" . _DB_PREFIX_ . $this->table . "`
                                  GROUP BY id_employee, sku
                                  HAVING COUNT(*) > 1
                              ) dup ON t.id_item = dup.keep_id
                              SET t.qty_to_pick = dup.qty_to_pick_sum,
                                  t.qty_picked = dup.qty_picked_sum,
                                  t.qty_original = dup.qty_original_sum");

                $db->execute("DELETE t FROM `" . _DB_PREFIX_ . $this->table . "` t
                              JOIN (
                                  SELECT MIN(id_item) AS keep_id, id_employee, sku
                                  FROM `" . _DB_PREFIX_ . $this->table . "`
                                  GROUP BY id_employee, sku
                                  HAVING COUNT(*) > 1
                              ) d ON t.id_employee = d.id_employee AND t.sku = d.sku AND t.id_item <> d.keep_id");

                // Spróbuj ponownie
                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` ADD UNIQUE KEY `emp_sku_unique` (`id_employee`,`sku`)");
            }
        }
    }

    private function checkAndAddAltColumns()
    {
        $db = Db::getInstance();
        $checkJson = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . $this->table . "` LIKE 'alternatives_json'");
        if (empty($checkJson)) {
            $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` ADD `alternatives_json` TEXT DEFAULT NULL AFTER `location`");
        }
        $checkSku = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . $this->table . "` LIKE 'alt_sku'");
        if (empty($checkSku)) {
            $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` ADD `alt_sku` VARCHAR(64) DEFAULT NULL AFTER `sku`");
        }
        $checkRegal = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . $this->table . "` LIKE 'alt_regal'");
        if (empty($checkRegal)) {
            $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` ADD `alt_regal` VARCHAR(16) DEFAULT NULL AFTER `regal`");
        }
        $checkPolka = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . $this->table . "` LIKE 'alt_polka'");
        if (empty($checkPolka)) {
            $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` ADD `alt_polka` VARCHAR(16) DEFAULT NULL AFTER `polka`");
        }
    }

    public function clearSession()
    {
        $idEmployee = (int)$this->getEmployeeId();
        Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . $this->table . "` WHERE id_employee = " . (int)$idEmployee);
        Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . $this->table_files . "` WHERE id_employee = " . (int)$idEmployee);
        return true;
    }

    public function deleteCollectedItems()
    {
        $idEmployee = (int)$this->getEmployeeId();
        $sql = "DELETE FROM `" . _DB_PREFIX_ . $this->table . "` 
                WHERE id_employee = " . (int)$idEmployee . "
                AND (is_collected = 1 OR qty_picked >= qty_to_pick)";
        return Db::getInstance()->execute($sql);
    }

    public function deleteItem($sku)
    {
        $idEmployee = (int)$this->getEmployeeId();
        $skuSafe = pSQL($sku);
        return Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . $this->table . "` WHERE id_employee = " . (int)$idEmployee . " AND sku = '$skuSafe'");
    }

    public function setAlternativeLocation($originalSku, $newRegal, $newPolka, $newRealSku)
    {
        $idEmployee = (int)$this->getEmployeeId();
        $originalSkuSafe = pSQL($originalSku);
        $newSkuSafe = pSQL($newRealSku);
        $newRegalSafe = pSQL($newRegal);
        $newPolkaSafe = pSQL($newPolka);

        $sql = "UPDATE `" . _DB_PREFIX_ . $this->table . "` 
                SET alt_sku = '$newSkuSafe',
                    alt_regal = '$newRegalSafe',
                    alt_polka = '$newPolkaSafe',
                    date_upd = NOW()
                WHERE id_employee = " . (int)$idEmployee . " AND sku = '$originalSkuSafe'";

        return Db::getInstance()->execute($sql);
    }

    // --- NOWA METODA: ZMNIEJSZENIE ILOŚCI (Dla Smart Correction) ---
    public function decreaseQtyToPick($sku, $qtyToSubtract)
    {
        $idEmployee = (int)$this->getEmployeeId();
        $skuSafe = pSQL($sku);
        $qty = (int)$qtyToSubtract;

        $sql = "UPDATE `" . _DB_PREFIX_ . $this->table . "` 
                SET qty_to_pick = qty_to_pick - $qty,
                    qty_original = qty_original - $qty
                WHERE id_employee = " . (int)$idEmployee . " AND sku = '$skuSafe'";

        return Db::getInstance()->execute($sql);
    }

    public function resetAlternative($originalSku)
    {
        $idEmployee = (int)$this->getEmployeeId();
        $originalSkuSafe = pSQL($originalSku);
        $sql = "UPDATE `" . _DB_PREFIX_ . $this->table . "` 
                SET alt_sku = NULL, alt_regal = NULL, alt_polka = NULL
                WHERE id_employee = " . (int)$idEmployee . " AND sku = '$originalSkuSafe'";
        return Db::getInstance()->execute($sql);
    }

    public function isFileProcessed($hash)
    {
        if (empty($hash)) return false;

        $idEmployee = (int)$this->getEmployeeId();
        $hashSafe = pSQL($hash);

        // DUPLIKAT tylko "dzisiaj" (żeby po dniu można było ponownie przetworzyć ten sam plik bez resetu).
        $sql = "SELECT id_file FROM `" . _DB_PREFIX_ . $this->table_files . "` 
                WHERE id_employee = " . (int)$idEmployee . "
                  AND file_hash = '" . $hashSafe . "'
                  AND date_add >= CURDATE()";
        return (bool)Db::getInstance()->getValue($sql);
    }


    public function logProcessedFile($hash, $name)
    {
        if (empty($hash)) return false;

        $idEmployee = (int)$this->getEmployeeId();
        $hashSafe = pSQL($hash);
        $nameSafe = pSQL($name);

        // Uwaga: mamy UNIQUE (id_employee, file_hash). Jeśli ten sam plik był kiedyś wgrywany (np. wczoraj),
        // aktualizujemy date_add na NOW() – dzięki temu duplikat działa per dzień.
        $sql = "INSERT INTO `" . _DB_PREFIX_ . $this->table_files . "` (`id_employee`, `file_hash`, `file_name`, `date_add`)
                VALUES (" . (int)$idEmployee . ", '" . $hashSafe . "', '" . $nameSafe . "', NOW())
                ON DUPLICATE KEY UPDATE `file_name` = VALUES(`file_name`), `date_add` = NOW()";

        return (bool)Db::getInstance()->execute($sql);
    }


    public function addItemsToSession(array $items)
    {
        if (empty($items)) return true;

        $db = Db::getInstance();
        $date = date('Y-m-d H:i:s');
        $idEmployee = (int)$this->getEmployeeId();

        foreach ($items as $item) {
            $ean = isset($item['ean']) ? pSQL($item['ean']) : '';
            $sku = isset($item['sku']) ? pSQL($item['sku']) : '';

            if (empty($sku) && !empty($ean)) $sku = $ean;
            if (empty($sku)) continue;

            $name = isset($item['name']) ? pSQL($item['name']) : 'Produkt';
            // UWAGA: historycznie tutaj używaliśmy qty_stock jako "ilość do zebrania".
            $qtyNew = isset($item['qty_stock']) ? (int)$item['qty_stock'] : 0;
            if ($qtyNew <= 0) continue;

            $regal = isset($item['regal']) ? pSQL($item['regal']) : '';
            $polka = isset($item['polka']) ? pSQL($item['polka']) : '';
            $location = isset($item['location']) ? pSQL($item['location']) : '';
            $imageId = isset($item['image_id']) ? pSQL($item['image_id']) : '';
            $linkRewrite = isset($item['link_rewrite']) ? pSQL($item['link_rewrite']) : '';
            $idProduct = isset($item['id_product']) ? (int)$item['id_product'] : 0;

            $altJson = isset($item['alternatives_json']) ? pSQL($item['alternatives_json']) : '';

            $sql = "INSERT INTO `" . _DB_PREFIX_ . $this->table . "`
                    (id_employee, ean, sku, name, location, regal, polka, qty_to_pick, qty_picked, qty_original, image_id, link_rewrite, id_product, is_collected, alternatives_json, date_add, date_upd)
                    VALUES
                    (" . (int)$idEmployee . ", '$ean', '$sku', '$name', '$location', '$regal', '$polka', $qtyNew, 0, $qtyNew, '$imageId', '$linkRewrite', $idProduct, 0, '$altJson', '$date', '$date')
                    ON DUPLICATE KEY UPDATE
                    qty_to_pick = qty_to_pick + VALUES(qty_to_pick),
                    qty_original = qty_original + VALUES(qty_original),
                    alternatives_json = VALUES(alternatives_json),
                    is_collected = 0,
                    date_upd = VALUES(date_upd)";

            $db->execute($sql);
        }

        return true;
    }

    public function getAllItems()
    {
        $idEmployee = (int)$this->getEmployeeId();
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . $this->table . "`
                WHERE id_employee = " . (int)$idEmployee . "
                ORDER BY is_collected ASC, regal ASC, polka ASC, name ASC";

        $rows = Db::getInstance()->executeS($sql);

        if (!$rows) return [];

        $mapped = [];
        foreach ($rows as $r) {
            $mapped[] = [
                'ean' => $r['ean'],
                'sku' => $r['sku'],
                'name' => $r['name'],
                'location' => $r['location'],
                'regal' => $r['regal'],
                'polka' => $r['polka'],
                'qty_stock' => (int)$r['qty_to_pick'],
                'qty_stock_original' => (int)$r['qty_original'],
                'user_picked_qty' => (int)$r['qty_picked'],
                'image_id' => $r['image_id'],
                'link_rewrite' => $r['link_rewrite'],
                'is_collected' => (bool)$r['is_collected'],
                'alternatives_json' => isset($r['alternatives_json']) ? $r['alternatives_json'] : null,
                'alt_sku' => isset($r['alt_sku']) ? $r['alt_sku'] : null,
                'alt_regal' => isset($r['alt_regal']) ? $r['alt_regal'] : null,
                'alt_polka' => isset($r['alt_polka']) ? $r['alt_polka'] : null,
                'date_add' => $r['date_add']
            ];
        }
        return $mapped;
    }

    public function updatePickedQty($sku, $qty, $isCollected)
    {
        $idEmployee = (int)$this->getEmployeeId();
        $skuSafe = pSQL($sku);
        $qty = (int)$qty;
        $isCollectedVal = $isCollected ? 1 : 0;
        $date = date('Y-m-d H:i:s');

        $sql = "UPDATE `" . _DB_PREFIX_ . $this->table . "`
                SET qty_picked = $qty, is_collected = $isCollectedVal, date_upd = '$date'
                WHERE id_employee = " . (int)$idEmployee . " AND sku = '$skuSafe'";

        return Db::getInstance()->execute($sql);
    }

    public function getPickedQty($sku)
    {
        $idEmployee = (int)$this->getEmployeeId();
        $skuSafe = pSQL($sku);
        $row = Db::getInstance()->getRow("SELECT qty_picked FROM `" . _DB_PREFIX_ . $this->table . "` WHERE id_employee = " . (int)$idEmployee . " AND sku = '$skuSafe'");
        if ($row) return (int)$row['qty_picked'];
        return 0;
    }

    public function getAlternativeSku($originalSku)
    {
        $idEmployee = (int)$this->getEmployeeId();
        $originalSkuSafe = pSQL($originalSku);
        return Db::getInstance()->getValue("SELECT alt_sku FROM `" . _DB_PREFIX_ . $this->table . "` WHERE id_employee = " . (int)$idEmployee . " AND sku = '$originalSkuSafe'");
    }
}
