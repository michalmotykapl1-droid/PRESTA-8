<?php
/**
 * Klasa SurplusManager - Zarządzanie Wirtualnym Magazynem (Pick Stół)
 * POPRAWKA: Naprawiono nazwę kolumny w addToPickingQueue ('qty' -> 'qty_to_pick')
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class SurplusManager
{
    const TABLE_SURPLUS = 'modulzamowien_surplus_v2';
    const TABLE_QUEUE   = 'modulzamowien_picking_queue';
    const TABLE_LOG     = 'modulzamowien_upload_log_v2';

    public function __construct()
    {
        Db::getInstance()->execute("CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . self::TABLE_SURPLUS . "` (
            `id_surplus` INT(11) NOT NULL AUTO_INCREMENT,
            `ean` VARCHAR(32) NOT NULL,
            `name` VARCHAR(128) NOT NULL,
            `qty` INT(11) NOT NULL DEFAULT '0',
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_surplus`),
            UNIQUE KEY `ean_idx` (`ean`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;");

        Db::getInstance()->execute("CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . self::TABLE_QUEUE . "` (
            `id_task` INT(11) NOT NULL AUTO_INCREMENT,
            `ean` VARCHAR(32) NOT NULL,
            `name` VARCHAR(128) NOT NULL,
            `qty_to_pick` INT(11) NOT NULL DEFAULT '0',
            `qty_picked` INT(11) NOT NULL DEFAULT '0',
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_task`),
            UNIQUE KEY `ean_idx` (`ean`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;");

        Db::getInstance()->execute("CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . self::TABLE_LOG . "` (
            `id_log` INT(11) NOT NULL AUTO_INCREMENT,
            `file_hash` VARCHAR(32) NOT NULL,
            `file_name` VARCHAR(128) NOT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_log`),
            UNIQUE KEY `hash_idx` (`file_hash`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;");
    }

    public function addSurplus($ean, $name, $qtyToAdd)
    {
        if ($qtyToAdd <= 0) return;
        $existing = Db::getInstance()->getValue("SELECT qty FROM `" . _DB_PREFIX_ . self::TABLE_SURPLUS . "` WHERE `ean` = '" . pSQL($ean) . "'");
        if ($existing !== false) {
            Db::getInstance()->update(self::TABLE_SURPLUS, ['qty' => $existing + $qtyToAdd, 'name' => pSQL($name), 'date_upd' => date('Y-m-d H:i:s')], "ean = '" . pSQL($ean) . "'");
        } else {
            Db::getInstance()->insert(self::TABLE_SURPLUS, ['ean' => pSQL($ean), 'name' => pSQL($name), 'qty' => (int)$qtyToAdd, 'date_upd' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * ZMIANA: Dodano parametr $addToQueue = true.
     * Jeśli ustawisz na false, produkt zniknie ze stołu, ale NIE trafi do Zakładki 4.
     */
    public function consumeSurplus($ean, $qtyNeeded, $productName = '', $addToQueue = true)
    {
        $sql = "SELECT qty FROM `" . _DB_PREFIX_ . self::TABLE_SURPLUS . "` WHERE `ean` = '" . pSQL($ean) . "'";
        $available = (int)Db::getInstance()->getValue($sql);

        if ($available <= 0) return 0;

        $taken = min($available, $qtyNeeded);
        $left = $available - $taken;

        if ($left > 0) {
            Db::getInstance()->update(self::TABLE_SURPLUS, ['qty' => $left], "ean = '" . pSQL($ean) . "'");
        } else {
            Db::getInstance()->delete(self::TABLE_SURPLUS, "ean = '" . pSQL($ean) . "'");
        }

        // Dodajemy do kolejki TYLKO jeśli parametr $addToQueue jest true
        if ($taken > 0 && $addToQueue) {
            $this->addToPickingQueue($ean, $productName, $taken);
        }

        return $taken;
    }
    
    public function removeItem($ean)
    {
        if (empty($ean)) return false;
        return Db::getInstance()->delete(self::TABLE_SURPLUS, "ean = '" . pSQL($ean) . "'");
    }
    
    public function getProductByEan($ean)
    {
        return Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . self::TABLE_SURPLUS . "` WHERE `ean` = '" . pSQL($ean) . "'");
    }

    public function checkSurplus($ean)
    {
        return (int)Db::getInstance()->getValue("SELECT qty FROM `" . _DB_PREFIX_ . self::TABLE_SURPLUS . "` WHERE `ean` = '" . pSQL($ean) . "'");
    }

    public function getSurplusList()
    {
        return Db::getInstance()->executeS("
            SELECT * FROM `" . _DB_PREFIX_ . self::TABLE_SURPLUS . "` 
            WHERE qty > 0 
            ORDER BY DATE(date_upd) ASC, name ASC
        ");
    }

    public function moveToWarehouseAndClear()
    {
        Db::getInstance()->execute("TRUNCATE TABLE `" . _DB_PREFIX_ . self::TABLE_SURPLUS . "`");
        return true;
    }

    public function transferToWmsStaging()
    {
        // 1. Pobierz wszystko ze stołu
        $surplusItems = $this->getSurplusList();
        if (empty($surplusItems)) return 0;

        $count = 0;
        $session_id = 'SCANNER_WAITING';
        $receipt_date = date('Y-m-d');
        
        // Domyślna data ważności (rok do przodu), bo ze stołu nie mamy daty
        $expiry_date = date('Y-m-d', strtotime('+1 year')); 

        foreach ($surplusItems as $item) {
            $ean = $item['ean'];
            // Pomijamy produkty bez EAN (nie można ich wgrać do WMS)
            if (empty($ean) || strlen($ean) < 3) continue;

            $qty = (int)$item['qty'];
            
            // Wstawiamy do tabeli WMS
            $res = Db::getInstance()->insert('wyprzedazpro_csv_staging', [
                'session_id' => pSQL($session_id),
                'ean'        => pSQL($ean),
                'quantity'   => $qty,
                'receipt_date' => pSQL($receipt_date),
                'expiry_date'  => pSQL($expiry_date),
                'regal'      => 'MAG', // Domyślna lokalizacja dla masowego przenoszenia
                'polka'      => 'AUTO'
            ]);

            if ($res) $count++;
        }

        return $count;
    }

    private function addToPickingQueue($ean, $name, $qty)
    {
        $existing = Db::getInstance()->getRow("SELECT id_task, qty_to_pick FROM `" . _DB_PREFIX_ . self::TABLE_QUEUE . "` WHERE `ean` = '" . pSQL($ean) . "'");
        
        if ($existing) {
            $newQty = (int)$existing['qty_to_pick'] + $qty;
            Db::getInstance()->update(self::TABLE_QUEUE, ['qty_to_pick' => $newQty, 'name' => pSQL($name)], "id_task = " . (int)$existing['id_task']);
        } else {
            // POPRAWKA: Zmiana klucza 'qty' na 'qty_to_pick'
            Db::getInstance()->insert(self::TABLE_QUEUE, [
                'ean' => pSQL($ean), 
                'name' => pSQL($name), 
                'qty_to_pick' => (int)$qty, 
                'qty_picked' => 0, 
                'date_add' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public function getQueueList()
    {
        return Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . self::TABLE_QUEUE . "` ORDER BY date_add DESC, name ASC");
    }

    public function updateQueueProgress($ean, $qtyPicked)
    {
        Db::getInstance()->update(self::TABLE_QUEUE, ['qty_picked' => (int)$qtyPicked], "ean = '" . pSQL($ean) . "'");
    }
    
    public function clearQueue()
    {
        Db::getInstance()->execute("DELETE FROM `" . _DB_PREFIX_ . self::TABLE_QUEUE . "` WHERE DATE(date_add) != CURDATE()");
    }

    public function isFileProcessed($fileHash) {
        return (bool)Db::getInstance()->getValue("SELECT id_log FROM `" . _DB_PREFIX_ . self::TABLE_LOG . "` WHERE `file_hash` = '" . pSQL($fileHash) . "'");
    }
    public function logProcessedFile($fileHash, $fileName) {
        Db::getInstance()->insert(self::TABLE_LOG, ['file_hash' => pSQL($fileHash), 'file_name' => pSQL($fileName), 'date_add' => date('Y-m-d H:i:s')]);
    }
}