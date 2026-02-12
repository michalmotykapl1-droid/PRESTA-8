<?php

class AzadaDbRepository
{
    public static function ensureInvoiceTables()
    {
        $db = Db::getInstance();
        $sql1 = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "azada_wholesaler_pro_invoice_files` ( `id_invoice` int(11) NOT NULL AUTO_INCREMENT, `id_wholesaler` int(11) NOT NULL, `doc_number` varchar(100) DEFAULT NULL, `doc_date` varchar(50) DEFAULT NULL, `amount_netto` varchar(50) DEFAULT NULL, `payment_deadline` varchar(50) DEFAULT NULL, `is_paid` tinyint(1) DEFAULT 0, `file_name` varchar(255) DEFAULT NULL, `is_downloaded` tinyint(1) DEFAULT 0, `date_add` datetime NOT NULL, `date_upd` datetime NOT NULL, PRIMARY KEY (`id_invoice`), KEY `doc_number` (`doc_number`) ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
        $sql2 = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "azada_wholesaler_pro_invoice_details` ( `id_detail` int(11) NOT NULL AUTO_INCREMENT, `id_invoice` int(11) NOT NULL, `doc_number` varchar(100) NOT NULL, `ean` varchar(50) DEFAULT NULL, `name` varchar(255) DEFAULT NULL, `quantity` int(11) DEFAULT 0, `price_net` decimal(20,2) DEFAULT 0.00, `vat_rate` int(11) DEFAULT 0, PRIMARY KEY (`id_detail`), KEY `id_invoice` (`id_invoice`) ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";
        $db->execute($sql1);
        $db->execute($sql2);
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

    public static function getInvoiceByNumber($docNumber)
    {
        $invoice = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_invoice_files WHERE doc_number = '".pSQL($docNumber)."'");
        
        // AUTO-CZYSZCZENIE "W LOCIE": Weryfikacja istnienia fizycznego pliku na serwerze
        if ($invoice && !empty($invoice['file_name']) && (int)$invoice['is_downloaded'] == 1) {
            $filePath = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/fv/' . $invoice['file_name'];
            
            // Jeśli usunąłeś plik z folderu downloads/fv, skrypt wyczyści wpisy w obu tabelach bazy danych
            if (!file_exists($filePath)) {
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
        $existing = self::getInvoiceByNumber($docNumber);
        
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
                $filePath = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/fv/' . $inv['file_name'];
                
                // Zabezpieczenie także tutaj przy pobieraniu list
                if (!empty($inv['file_name']) && !file_exists($filePath)) {
                    self::deleteInvoiceById($inv['id_invoice']);
                } else {
                    $validInvoices[] = $inv;
                }
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
