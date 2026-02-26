<?php

class AzadaDbRepository
{
    /**
     * KANONICZNY KATALOG FAKTUR:
     * ZAWSZE trzymamy faktury w downloads/FV (duże litery).
     * Dla starszych instalacji modułu, które mogły zapisywać do downloads/fv,
     * automatycznie migrujemy pliki do FV (move/copy/link).
     */
    const INVOICE_DIR_CANON = 'FV';
    const INVOICE_DIR_LEGACY = 'fv';

    public static function ensureInvoiceTables()
    {
        $db = Db::getInstance();
        $sql1 = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "azada_wholesaler_pro_invoice_files` ( `id_invoice` int(11) NOT NULL AUTO_INCREMENT, `id_wholesaler` int(11) NOT NULL, `doc_number` varchar(100) DEFAULT NULL, `doc_date` varchar(50) DEFAULT NULL, `amount_netto` varchar(50) DEFAULT NULL, `payment_deadline` varchar(50) DEFAULT NULL, `is_paid` tinyint(1) DEFAULT 0, `file_name` varchar(255) DEFAULT NULL, `is_downloaded` tinyint(1) DEFAULT 0, `date_add` datetime NOT NULL, `date_upd` datetime NOT NULL, PRIMARY KEY (`id_invoice`), KEY `doc_number` (`doc_number`) ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
        $sql2 = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "azada_wholesaler_pro_invoice_details` ( `id_detail` int(11) NOT NULL AUTO_INCREMENT, `id_invoice` int(11) NOT NULL, `doc_number` varchar(100) NOT NULL, `product_id` varchar(64) DEFAULT NULL, `ean` varchar(50) DEFAULT NULL, `name` varchar(255) DEFAULT NULL, `quantity` int(11) DEFAULT 0, `price_net` decimal(20,2) DEFAULT 0.00, `vat_rate` int(11) DEFAULT 0, PRIMARY KEY (`id_detail`), KEY `id_invoice` (`id_invoice`) ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
        $db->execute($sql1);
        $db->execute($sql2);

        // Migracje bezpieczeństwa dla starszych instalacji
        try {
            $idx = $db->executeS("SHOW INDEX FROM `" . _DB_PREFIX_ . "azada_wholesaler_pro_invoice_files` WHERE Key_name = 'uniq_wholesaler_doc'");
            if (empty($idx)) {
                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . "azada_wholesaler_pro_invoice_files` ADD UNIQUE KEY `uniq_wholesaler_doc` (`id_wholesaler`, `doc_number`)");
            }
        } catch (Exception $e) {}

        try {
            $col = $db->executeS("SHOW COLUMNS FROM `" . _DB_PREFIX_ . "azada_wholesaler_pro_invoice_details` LIKE 'product_id'");
            if (empty($col)) {
                $db->execute("ALTER TABLE `" . _DB_PREFIX_ . "azada_wholesaler_pro_invoice_details` ADD `product_id` VARCHAR(64) DEFAULT NULL AFTER `doc_number`");
            }
        } catch (Exception $e) {}
    }

    /**
     * Zwraca ścieżkę do katalogu downloads/<dir>/ (z końcowym /).
     */
    private static function getDownloadsDir($dir)
    {
        return _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/' . trim($dir, '/\\') . '/';
    }

    /**
     * Zapewnia istnienie katalogu kanonicznego downloads/FV.
     */
    private static function ensureCanonInvoiceDirExists()
    {
        $canonDir = self::getDownloadsDir(self::INVOICE_DIR_CANON);
        if (!is_dir($canonDir)) {
            @mkdir($canonDir, 0777, true);
        }
        return $canonDir;
    }

    /**
     * Sprząta legacy katalog downloads/fv jeśli jest pusty.
     */
    private static function cleanupLegacyInvoiceDirIfEmpty()
    {
        $legacyDir = self::getDownloadsDir(self::INVOICE_DIR_LEGACY);
        if (!is_dir($legacyDir)) {
            return;
        }

        $items = @scandir($legacyDir);
        if (!is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            // Nie jest pusty
            return;
        }

        @rmdir($legacyDir);
    }

    /**
     * Zapewnia, że plik faktury znajduje się w downloads/FV.
     * - jeśli istnieje w FV -> OK
     * - jeśli istnieje w fv -> przenosi/kopiuje/linkuje do FV
     * Zwraca true jeśli po operacji plik istnieje w FV.
     */
    public static function ensureInvoiceFileInFV($fileName)
    {
        $fileName = (string)$fileName;
        if ($fileName === '') {
            return false;
        }

        $canonDir = self::ensureCanonInvoiceDirExists();
        $canonPath = $canonDir . $fileName;
        if (file_exists($canonPath)) {
            return true;
        }

        $legacyPath = self::getDownloadsDir(self::INVOICE_DIR_LEGACY) . $fileName;
        if (!file_exists($legacyPath)) {
            return false;
        }

        // 1) Najpierw próbujemy atomowego rename (najlepszy scenariusz)
        if (@rename($legacyPath, $canonPath)) {
            self::cleanupLegacyInvoiceDirIfEmpty();
            return file_exists($canonPath);
        }

        // 2) Fallback: copy + unlink
        if (@copy($legacyPath, $canonPath)) {
            @unlink($legacyPath);
            self::cleanupLegacyInvoiceDirIfEmpty();
            return file_exists($canonPath);
        }

        // 3) Fallback: symlink (jeśli polityka serwera pozwala)
        if (function_exists('symlink')) {
            @symlink($legacyPath, $canonPath);
            if (file_exists($canonPath)) {
                // Legacy może zostać (symlink), ale jeśli jest pusty poza plikiem, nie usuwamy,
                // bo plik nadal tam fizycznie istnieje jako target.
                return true;
            }
        }

        // 4) Fallback: hardlink
        if (function_exists('link')) {
            @link($legacyPath, $canonPath);
            if (file_exists($canonPath)) {
                return true;
            }
        }

        // Nie udało się przenieść/udostępnić w FV.
        return false;
    }

    /**
     * Sprawdza czy plik faktury istnieje w którymkolwiek wariancie katalogu (FV lub fv).
     * Używane jako zabezpieczenie przed kasowaniem rekordów, gdy migracja do FV
     * nie jest możliwa z powodów uprawnień.
     */
    public static function invoiceFileExistsAnyCase($fileName)
    {
        $fileName = (string)$fileName;
        if ($fileName === '') {
            return false;
        }

        $canonPath = self::getDownloadsDir(self::INVOICE_DIR_CANON) . $fileName;
        if (file_exists($canonPath)) {
            return true;
        }

        $legacyPath = self::getDownloadsDir(self::INVOICE_DIR_LEGACY) . $fileName;
        return file_exists($legacyPath);
    }

    public static function getFileByDocNumber($docNumber, $idWholesaler = null)
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('azada_wholesaler_pro_order_files');
        $sql->where("external_doc_number = '" . pSQL($docNumber) . "'");

        if ($idWholesaler !== null) {
            $sql->where('id_wholesaler = ' . (int)$idWholesaler);
        }

        $sql->orderBy('id_file DESC');
        return Db::getInstance()->getRow($sql);
    }

    public static function getAllDownloadedFiles()
    {
        return Db::getInstance()->executeS("SELECT id_file, file_name FROM "._DB_PREFIX_."azada_wholesaler_pro_order_files WHERE is_downloaded = 1");
    }

    public static function deleteRecordById($idFile)
    {
        $db = Db::getInstance();
        $db->delete('azada_wholesaler_pro_order_files', 'id_file = ' . (int)$idFile);
        $db->delete('azada_wholesaler_pro_order_details', 'id_file = ' . (int)$idFile);
    }

    public static function saveFileHeader($idWholesaler, $docNumber, $dateSql, $nettoSql, $status, $fileName)
    {
        $db = Db::getInstance();
        $table = 'azada_wholesaler_pro_order_files';
        $existing = self::getFileByDocNumber($docNumber, $idWholesaler);
        $data = [
            'external_doc_number' => pSQL($docNumber),
            'doc_date' => pSQL($dateSql),
            'amount_netto' => pSQL($nettoSql),
            'file_name' => pSQL($fileName),
            'status' => pSQL($status),
            'is_downloaded' => 1,
            'date_upd' => date('Y-m-d H:i:s')
        ];
        if ($existing) {
            $db->update($table, $data, "id_file = " . (int)$existing['id_file']);
            return (int)$existing['id_file'];
        } else {
            $data['id_wholesaler'] = (int)$idWholesaler;
            $data['date_add'] = date('Y-m-d H:i:s');
            $data['download_hash'] = '';
            $db->insert($table, $data);
            return (int)$db->Insert_ID();
        }
    }

    public static function updateFileVerificationStatus($idFile, $status)
    {
        Db::getInstance()->update(
            'azada_wholesaler_pro_order_files',
            ['is_verified_with_invoice' => (int)$status],
            'id_file = ' . (int)$idFile
        );
    }

    public static function saveFileDetails($idFile, $docNumber, $rows)
    {
        $db = Db::getInstance();
        $table = 'azada_wholesaler_pro_order_details';
        $db->delete($table, 'id_file = ' . (int)$idFile);

        foreach ($rows as $row) {
            $skuWholesaler = isset($row['sku_wholesaler']) ? $row['sku_wholesaler'] : null;
            $originalQty = isset($row['original_csv_qty']) ? (int)$row['original_csv_qty'] : null;
            $correctionInfo = isset($row['correction_info']) ? $row['correction_info'] : null;
            $invoiceQty = isset($row['invoice_qty']) ? (int)$row['invoice_qty'] : 0;

            // Pobieramy przeliczone wartości FV (jeśli nie ma w tablicy, fallback na 0)
            $fvPn = isset($row['fv_price_net']) ? (float)$row['fv_price_net'] : 0.00;
            $fvVn = isset($row['fv_value_net']) ? (float)$row['fv_value_net'] : 0.00;
            $fvPg = isset($row['fv_price_gross']) ? (float)$row['fv_price_gross'] : 0.00;
            $fvVg = isset($row['fv_value_gross']) ? (float)$row['fv_value_gross'] : 0.00;

            $db->insert($table, [
                'id_file' => (int)$idFile,
                'doc_number' => pSQL($docNumber),
                'sku_wholesaler' => pSQL($skuWholesaler),
                'product_id' => pSQL($row['product_id']),
                'ean' => pSQL($row['ean']),
                'name' => pSQL($row['name']),
                'quantity' => (int)$row['quantity'],
                'original_csv_qty' => $originalQty,
                'correction_info' => pSQL($correctionInfo),
                'invoice_qty' => $invoiceQty,
                'unit' => pSQL($row['unit']),
                
                // Stare kolumny CSV (pozostawiamy dla historii)
                'price_net' => (float)$row['price_net'],
                'value_net' => (float)$row['value_net'],
                'vat_rate' => (int)$row['vat_rate'],
                'price_gross' => (float)$row['price_gross'],
                'value_gross' => (float)$row['value_gross'],
                
                // Nowe kolumny FV (przeliczone)
                'fv_price_net' => $fvPn,
                'fv_value_net' => $fvVn,
                'fv_price_gross' => $fvPg,
                'fv_value_gross' => $fvVg,

                'date_add' => date('Y-m-d H:i:s')
            ]);
        }
    }

    public static function deleteOldRecords($cutoffDate)
    {
        $db = Db::getInstance();
        $files = $db->executeS("SELECT id_file, file_name FROM "._DB_PREFIX_."azada_wholesaler_pro_order_files WHERE doc_date < '".pSQL($cutoffDate)."' AND is_downloaded = 1");
        if ($files) {
            foreach ($files as $f) {
                self::deleteRecordById($f['id_file']);
            }
        }
        return $files;
    }

    public static function getDetailsByDocNumber($docNumber)
    {
        return Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_order_details WHERE doc_number = '".pSQL($docNumber)."' ORDER BY name ASC");
    }

    // --- FAKTURY (Invoices) ---

    public static function getInvoiceByNumber($docNumber, $idWholesaler = null)
    {
        $sql = "SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_invoice_files WHERE doc_number = '".pSQL($docNumber)."'";
        if ($idWholesaler !== null) {
            $sql .= " AND id_wholesaler = " . (int)$idWholesaler;
        }
        $sql .= " ORDER BY id_invoice DESC";
        $invoice = Db::getInstance()->getRow($sql);
        
        // AUTO-CZYSZCZENIE "W LOCIE": Weryfikacja istnienia fizycznego pliku na serwerze
        if ($invoice && !empty($invoice['file_name']) && (int)$invoice['is_downloaded'] == 1) {
            // KANON: zawsze FV (duże litery). Jeśli plik jest w legacy downloads/fv -> migrujemy.
            $existsInFV = self::ensureInvoiceFileInFV($invoice['file_name']);
            
            // Jeśli nie ma ani w FV (po migracji), traktujemy jako brak pliku i czyścimy rekord.
            if (!$existsInFV && !self::invoiceFileExistsAnyCase($invoice['file_name'])) {
                self::deleteInvoiceById($invoice['id_invoice']);
                return false; // Skasowane, więc zwracamy false - panel od razu zobaczy "NIE POBRANO"
            }
        }
        
        return $invoice;
    }

    public static function saveInvoiceHeader($idWholesaler, $docNumber, $date, $netto, $deadline, $isPaid, $fileName)
    {
        $db = Db::getInstance();
        $table = 'azada_wholesaler_pro_invoice_files';
        $existing = self::getInvoiceByNumber($docNumber, (int)$idWholesaler);
        
        $data = [
            'doc_number' => pSQL($docNumber),
            'doc_date' => pSQL($date),
            'amount_netto' => pSQL($netto),
            'payment_deadline' => pSQL($deadline),
            'is_paid' => (int)$isPaid,
            'file_name' => pSQL($fileName),
            'is_downloaded' => 1,
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        if ($existing) {
            $db->update($table, $data, "id_invoice = " . (int)$existing['id_invoice']);
            return (int)$existing['id_invoice'];
        } else {
            $data['id_wholesaler'] = (int)$idWholesaler;
            $data['date_add'] = date('Y-m-d H:i:s');
            $db->insert($table, $data);
            return (int)$db->Insert_ID();
        }
    }

    public static function saveInvoiceDetails($idInvoice, $docNumber, $rows)
    {
        $db = Db::getInstance();
        $table = 'azada_wholesaler_pro_invoice_details';
        
        // Zawsze czyścimy starą zawartość faktury przed dodaniem nowych pozycji
        $db->delete($table, 'id_invoice = ' . (int)$idInvoice);
        
        foreach ($rows as $row) {
            $db->insert($table, [
                'id_invoice' => (int)$idInvoice,
                'doc_number' => pSQL($docNumber),
                'product_id' => isset($row['product_id']) ? pSQL($row['product_id']) : null,
                'ean' => pSQL($row['ean']),
                'name' => pSQL($row['name']),
                'quantity' => (int)$row['quantity'],
                'price_net' => (float)$row['price_net'],
                'vat_rate' => (int)$row['vat_rate']
            ]);
        }
    }

    public static function getAllDownloadedInvoices()
    {
        $invoices = Db::getInstance()->executeS("SELECT id_invoice, file_name FROM "._DB_PREFIX_."azada_wholesaler_pro_invoice_files WHERE is_downloaded = 1");
        $validInvoices = [];
        
        if ($invoices) {
            foreach ($invoices as $inv) {
                // Zabezpieczenie także tutaj przy pobieraniu list:
                // zawsze FV (duże litery) + automatyczna migracja z legacy downloads/fv.
                if (!empty($inv['file_name'])) {
                    $existsInFV = self::ensureInvoiceFileInFV($inv['file_name']);
                    if (!$existsInFV && !self::invoiceFileExistsAnyCase($inv['file_name'])) {
                        self::deleteInvoiceById($inv['id_invoice']);
                        continue;
                    }
                }

                $validInvoices[] = $inv;
            }
        }
        
        return $validInvoices;
    }

    public static function deleteInvoiceById($idInvoice)
    {
        $db = Db::getInstance();
        // Kasujemy z tabeli nagłówkowej oraz (najważniejsze) z tabeli ze szczegółami faktury
        $db->delete('azada_wholesaler_pro_invoice_files', 'id_invoice = ' . (int)$idInvoice);
        $db->delete('azada_wholesaler_pro_invoice_details', 'id_invoice = ' . (int)$idInvoice);
    }
}
