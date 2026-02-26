<?php
/**
 * Klasa HistoryManager
 * Wersja: 2.0 (Obsługa SKU w historii)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class HistoryManager
{
    const TABLE_MAIN = 'modulzamowien_history';
    const TABLE_ITEMS = 'modulzamowien_history_items';

    public function __construct()
    {
        $this->checkAndCreateTable();
    }

    public function saveHistory($id_employee, $employee_name, $supplier_name, $total_cost, $items_json)
    {
        $db = Db::getInstance();
        $items = json_decode($items_json, true);
        
        if (!is_array($items) || empty($items)) {
            return false;
        }

        // 1. Zapisz Nagłówek
        $date = date('Y-m-d H:i:s');
        $res = $db->insert(self::TABLE_MAIN, [
            'id_employee'   => (int)$id_employee,
            'employee_name' => pSQL($employee_name),
            'supplier_name' => pSQL($supplier_name),
            'total_cost'    => (float)$total_cost,
            'items_count'   => count($items),
            'order_data'    => pSQL($items_json), 
            'date_add'      => $date
        ]);

        if (!$res) return false;

        $id_history = $db->Insert_ID();

        // 2. Zapisz Szczegóły (z SKU)
        foreach ($items as $item) {
            $nameRaw = isset($item['name']) ? $item['name'] : 'Produkt';
            
            $isExtra = 0;
            if (strpos($nameRaw, '[EXTRA]') !== false) {
                $isExtra = 1;
            }

            $ean = isset($item['ean']) ? pSQL($item['ean']) : '';
            $sku = isset($item['sku']) ? pSQL($item['sku']) : ''; // ODBIERAMY SKU
            $qty = isset($item['qty']) ? (int)$item['qty'] : 0;
            $price = isset($item['price']) ? (float)$item['price'] : 0.00;
            
            $db->insert(self::TABLE_ITEMS, [
                'id_history' => (int)$id_history,
                'ean'        => $ean,
                'sku'        => $sku, // ZAPISUJEMY SKU
                'name'       => pSQL($nameRaw),
                'qty'        => $qty,
                'price'      => $price,
                'is_extra'   => $isExtra
            ]);
        }

        return true;
    }

    public function deleteHistory($id_history)
    {
        $id = (int)$id_history;
        if (!$id) return false;

        $db = Db::getInstance();
        $db->delete(self::TABLE_ITEMS, 'id_history = ' . $id);
        return $db->delete(self::TABLE_MAIN, 'id_history = ' . $id);
    }

    private function checkAndCreateTable()
    {
        $db = Db::getInstance();
        
        // Sprawdź czy tabela istnieje
        $sqlCheck = "SHOW TABLES LIKE '" . _DB_PREFIX_ . self::TABLE_ITEMS . "'";
        $exists = $db->executeS($sqlCheck);

        if (empty($exists)) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE_ITEMS . '` (
                `id_history_item` INT(11) NOT NULL AUTO_INCREMENT,
                `id_history` INT(11) NOT NULL,
                `ean` VARCHAR(32) DEFAULT NULL,
                `sku` VARCHAR(64) DEFAULT NULL,
                `name` VARCHAR(255) NOT NULL,
                `qty` INT(11) NOT NULL DEFAULT 0,
                `price` DECIMAL(20,2) NOT NULL DEFAULT "0.00",
                `is_extra` TINYINT(1) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id_history_item`),
                KEY `idx_history` (`id_history`),
                KEY `idx_ean` (`ean`),
                KEY `idx_sku` (`sku`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';
            $db->execute($sql);
        } else {
            // Auto-Fix: Jeśli tabela istnieje, ale nie ma kolumny SKU
            $cols = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . self::TABLE_ITEMS . "` LIKE 'sku'");
            if (empty($cols)) {
                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . self::TABLE_ITEMS . "` ADD `sku` VARCHAR(64) DEFAULT NULL AFTER `ean`");
                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . self::TABLE_ITEMS . "` ADD INDEX `idx_sku` (`sku`)");
            }
        }
    }
}
?>