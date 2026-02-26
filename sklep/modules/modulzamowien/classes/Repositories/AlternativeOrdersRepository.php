<?php
/**
 * Klasa AlternativeOrdersRepository
 * Wersja: 5.2 (Employee scoped sessions + DB Structure Force Fix)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AlternativeOrdersRepository
{
    private $table = 'modulzamowien_orders_session_alt';

    public function __construct()
    {
        $this->checkAndFixTable();
    }

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

    private function checkAndFixTable()
    {
        $db = Db::getInstance();

        // 1. Stwórz tabelę jeśli brak (NOWE INSTALACJE)
        $db->execute("CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . $this->table . "` (
            `id_order_item` int(11) NOT NULL AUTO_INCREMENT,
            `id_employee` INT(11) NOT NULL DEFAULT 0,
            `ean` varchar(32) DEFAULT NULL,
            `sku` varchar(64) DEFAULT NULL,
            `name` varchar(255) DEFAULT NULL,
            `qty_buy` int(11) DEFAULT '0',
            `qty_total` int(11) DEFAULT '0',
            `supplier_name` varchar(128) DEFAULT NULL,
            `price_net` decimal(20,2) DEFAULT '0.00',
            `savings` decimal(20,2) DEFAULT '0.00',
            `tax_rate` decimal(10,2) DEFAULT '0.00',
            `status` varchar(32) DEFAULT 'OK',
            `was_switched` tinyint(1) DEFAULT '0',
            `missing_qty` int(11) DEFAULT '0',
            `is_logistic_switch` tinyint(1) NOT NULL DEFAULT '0',
            `date_add` datetime DEFAULT NULL,
            PRIMARY KEY (`id_order_item`),
            KEY `id_employee_idx` (`id_employee`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;");

        // 2. Upewnij się, że kolumny istnieją (ALTER TABLE)
        $this->addColumnIfNotExists('id_employee', "INT(11) NOT NULL DEFAULT 0");
        $this->addColumnIfNotExists('savings', "DECIMAL(20,2) NOT NULL DEFAULT '0.00'");
        $this->addColumnIfNotExists('is_logistic_switch', "TINYINT(1) NOT NULL DEFAULT '0'");

        // Index
        $idxEmp = $db->executeS("SHOW INDEX FROM `" . _DB_PREFIX_ . $this->table . "` WHERE Key_name = 'id_employee_idx'");
        if (empty($idxEmp)) {
            $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` ADD KEY `id_employee_idx` (`id_employee`)");
        }

        // Legacy -> przypisz do aktualnego pracownika
        $idEmployee = (int)$this->getEmployeeId();
        if ($idEmployee > 0) {
            $db->execute("UPDATE `" . _DB_PREFIX_ . $this->table . "` SET id_employee = " . (int)$idEmployee . " WHERE id_employee = 0");
        }
    }

    private function addColumnIfNotExists($columnName, $definition)
    {
        $db = Db::getInstance();
        $check = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . $this->table . "` LIKE '" . pSQL($columnName) . "'");
        if (empty($check)) {
            $db->execute("ALTER TABLE `" . _DB_PREFIX_ . $this->table . "` ADD `" . pSQL($columnName) . "` $definition");
        }
    }

    public function clearSession()
    {
        $idEmployee = (int)$this->getEmployeeId();
        return Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . $this->table . "` WHERE id_employee = " . (int)$idEmployee);
    }

    public function addItemsToSession(array $items)
    {
        if (empty($items)) return true;

        $db = Db::getInstance();
        $date = date('Y-m-d H:i:s');
        $idEmployee = (int)$this->getEmployeeId();

        foreach ($items as $item) {
            $ean = isset($item['ean']) ? pSQL($item['ean']) : '';
            $name = isset($item['name']) ? pSQL($item['name']) : 'Produkt';
            $sku = isset($item['sku']) ? pSQL($item['sku']) : '';

            $supplierName = isset($item['supplier']) ? pSQL($item['supplier']) : '';
            $price = isset($item['price']) ? (float)$item['price'] : 0.0;

            if (!empty($ean)) {
                $keyWhere = "id_employee = " . (int)$idEmployee . " AND ean = '$ean' AND supplier_name = '$supplierName' AND price_net = $price";
            } else {
                $keyWhere = "id_employee = " . (int)$idEmployee . " AND name = '$name' AND supplier_name = '$supplierName' AND price_net = $price";
            }

            $qtyBuy = isset($item['qty_buy']) ? (int)$item['qty_buy'] : 0;
            $qtyTotal = isset($item['qty_total']) ? (int)$item['qty_total'] : 0;
            $savings = isset($item['savings']) ? (float)$item['savings'] : 0;

            $taxRate = isset($item['tax_rate']) ? (float)$item['tax_rate'] : 0;
            $status = isset($item['status']) ? pSQL($item['status']) : 'OK';
            $wasSwitched = (isset($item['was_switched']) && $item['was_switched']) ? 1 : 0;
            $missingQty = isset($item['missing_qty']) ? (int)$item['missing_qty'] : 0;

            $isLogistic = (isset($item['is_logistic_switch']) && $item['is_logistic_switch']) ? 1 : 0;

            $existsId = $db->getValue("SELECT id_order_item FROM `" . _DB_PREFIX_ . $this->table . "` WHERE $keyWhere");

            if ($existsId) {
                $db->execute("UPDATE `" . _DB_PREFIX_ . $this->table . "`
                    SET qty_buy = qty_buy + $qtyBuy,
                        qty_total = qty_total + $qtyTotal,
                        missing_qty = missing_qty + $missingQty,
                        savings = savings + $savings,
                        status = '$status',
                        was_switched = $wasSwitched,
                        is_logistic_switch = $isLogistic
                    WHERE id_order_item = " . (int)$existsId . " AND id_employee = " . (int)$idEmployee);
            } else {
                $db->execute("INSERT INTO `" . _DB_PREFIX_ . $this->table . "`
                    (id_employee, ean, sku, name, qty_buy, qty_total, supplier_name, price_net, savings, tax_rate, status, was_switched, missing_qty, is_logistic_switch, date_add)
                    VALUES
                    (" . (int)$idEmployee . ", '$ean', '$sku', '$name', $qtyBuy, $qtyTotal, '$supplierName', $price, $savings, $taxRate, '$status', $wasSwitched, $missingQty, $isLogistic, '$date')");
            }
        }
        return true;
    }

    public function updateQtyBuy($identifier, $delta)
    {
        if ((int)$delta == 0) return true;

        $idEmployee = (int)$this->getEmployeeId();
        $identSafe = pSQL($identifier);
        $sqlWhere = "(sku = '$identSafe' OR ean = '$identSafe')";

        $sql = "UPDATE `" . _DB_PREFIX_ . $this->table . "`
                SET qty_buy = GREATEST(0, qty_buy + ($delta))
                WHERE id_employee = " . (int)$idEmployee . " AND $sqlWhere";

        return Db::getInstance()->execute($sql);
    }

    public function getAllItems()
    {
        $idEmployee = (int)$this->getEmployeeId();
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . $this->table . "` WHERE id_employee = " . (int)$idEmployee . " ORDER BY name ASC";
        $rows = Db::getInstance()->executeS($sql);

        if (!$rows) return [];

        $mapped = [];
        foreach ($rows as $r) {
            $mapped[] = [
                'ean' => $r['ean'],
                'sku' => $r['sku'],
                'name' => $r['name'],
                'qty_buy' => (int)$r['qty_buy'],
                'qty_total' => (int)$r['qty_total'],
                'supplier' => $r['supplier_name'],
                'price' => (float)$r['price_net'],
                'savings' => isset($r['savings']) ? (float)$r['savings'] : 0,
                'tax_rate' => (float)$r['tax_rate'],
                'status' => $r['status'],
                'was_switched' => (bool)$r['was_switched'],
                'missing_qty' => (int)$r['missing_qty'],
                'is_logistic_switch' => isset($r['is_logistic_switch']) ? (bool)$r['is_logistic_switch'] : false
            ];
        }
        return $mapped;
    }
}
