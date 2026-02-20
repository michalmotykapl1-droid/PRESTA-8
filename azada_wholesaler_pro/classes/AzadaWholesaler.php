<?php

require_once(dirname(__FILE__) . '/services/AzadaDbRepository.php');
require_once(dirname(__FILE__) . '/services/AzadaFileHandler.php');
require_once(dirname(__FILE__) . '/services/AzadaCsvParser.php');
require_once(dirname(__FILE__) . '/services/AzadaB2BComparator.php');
require_once(dirname(__FILE__) . '/services/AzadaLogger.php');

if (file_exists(dirname(__FILE__) . '/services/AzadaOrderSanitizer.php')) {
    require_once(dirname(__FILE__) . '/services/AzadaOrderSanitizer.php');
}

class AzadaWholesaler extends ObjectModel
{
    public $id_wholesaler; 
    public $name; 
    public $active; 
    public $raw_table_name;
    public $file_url; 
    public $file_format; 
    public $delimiter; 
    public $encoding; 
    public $skip_header; 
    public $api_key; 
    public $b2b_login; 
    public $b2b_password; 
    public $connection_status; 
    public $diagnostic_result; 
    public $last_import; 
    public $date_add; 
    public $date_upd;

    public static $definition = [
        'table' => 'azada_wholesaler_pro_integration',
        'primary' => 'id_wholesaler',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 255],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'raw_table_name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 64],
            'file_url' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'required' => true],
            'file_format' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 10],
            'delimiter' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 5],
            'encoding' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 20],
            'skip_header' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'api_key' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'b2b_login' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'b2b_password' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 255],
            'connection_status' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'diagnostic_result' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'allow_null' => true],
            'last_import' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'allow_null' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        self::ensureDatabaseStructure();
        self::ensureTabs();
        parent::__construct($id, $id_lang, $id_shop);
    }

    public static function performMaintenance()
    {
        self::ensureDatabaseStructure();
        $daysToDelete = (int)Configuration::get('AZADA_B2B_DELETE_DAYS', 7);
        if ($daysToDelete > 0) {
            $cutoffDate = date('Y-m-d', strtotime("-$daysToDelete days"));
            $oldFiles = AzadaDbRepository::deleteOldRecords($cutoffDate);
            if ($oldFiles) { foreach ($oldFiles as $file) { AzadaFileHandler::deleteFile(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/' . $file['file_name']); } }
        }
        $allOrders = AzadaDbRepository::getAllDownloadedFiles();
        if ($allOrders) { foreach ($allOrders as $file) { if (!file_exists(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/' . $file['file_name'])) { AzadaDbRepository::deleteRecordById($file['id_file']); } } }
        $allInvoices = AzadaDbRepository::getAllDownloadedInvoices();
        if ($allInvoices) { foreach ($allInvoices as $inv) { if (!file_exists(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/FV/' . $inv['file_name'])) { AzadaDbRepository::deleteInvoiceById($inv['id_invoice']); } } }
        AzadaLogger::cleanOldLogs();
    }

    private static function detectExtensionFromUrl($url)
    {
        $lowerUrl = strtolower($url);
        if (strpos($lowerUrl, 'pdf') !== false) return 'pdf';
        if (strpos($lowerUrl, 'xml') !== false) return 'xml';
        if (strpos($lowerUrl, 'epp') !== false) return 'epp';
        return 'csv'; 
    }


    public static function getSkuPrefixByWholesaler($wholesalerName = '', $rawTableName = '')
    {
        $name = (string)$wholesalerName;
        $raw = (string)$rawTableName;

        if (stripos($raw, 'azada_raw_bioplanet') !== false || stripos($name, 'Bio Planet') !== false) {
            return 'BP_';
        }
        if (stripos($raw, 'azada_raw_ekowital') !== false || stripos($name, 'EkoWital') !== false || stripos($name, 'Eko Wital') !== false) {
            return 'EKOWIT_';
        }
        if (stripos($raw, 'azada_raw_naturamed') !== false || stripos($name, 'NaturaMed') !== false || stripos($name, 'Natura Med') !== false) {
            return 'NAT_';
        }

        return '';
    }

    public static function applySkuPrefix($sku, $prefix)
    {
        $sku = trim((string)$sku);
        $prefix = (string)$prefix;

        if ($sku === '' || $prefix === '') {
            return $sku;
        }

        if (stripos($sku, $prefix) === 0) {
            return $sku;
        }

        return $prefix . $sku;
    }

    // --- ZAMÓWIENIA (Orders) ---
    public static function processDownload($wholesalerId, $docNumber, $docDate, $docNetto, $docStatus, $downloadUrl, $cookieFile = null)
    {
        self::ensureDatabaseStructure();

        $dateSql = AzadaCsvParser::sanitizeDate($docDate);
        $nettoSql = AzadaCsvParser::sanitizePrice($docNetto);
        $ext = self::detectExtensionFromUrl($downloadUrl);
        $safeName = AzadaFileHandler::getSafeFileName($docNumber, $ext);
        $localPath = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/' . $safeName;

        if (!is_dir(dirname($localPath))) {
            mkdir(dirname($localPath), 0777, true);
        }

        $downloadRes = AzadaFileHandler::downloadFile($downloadUrl, $localPath, $cookieFile);

        if ($downloadRes['status'] == 'success') {
            if ($ext === 'csv') {
                $wholesalerNameForEncoding = (string)Db::getInstance()->getValue(
                    'SELECT name FROM ' . _DB_PREFIX_ . 'azada_wholesaler_pro_integration WHERE id_wholesaler = ' . (int)$wholesalerId
                );
                if (stripos($wholesalerNameForEncoding, 'NaturaMed') !== false || stripos($wholesalerNameForEncoding, 'Natura Med') !== false) {
                    AzadaFileHandler::normalizeCsvFileToUtf8($localPath);
                }
            }

            $idFile = AzadaDbRepository::saveFileHeader($wholesalerId, $docNumber, $dateSql, $nettoSql, $docStatus, $safeName);

            if ($idFile && $ext === 'csv') {
                $rows = AzadaCsvParser::parseCsv($localPath);
                
                if (!empty($rows)) {
                    $wMeta = Db::getInstance()->getRow("SELECT name, raw_table_name FROM "._DB_PREFIX_."azada_wholesaler_pro_integration WHERE id_wholesaler = ".(int)$wholesalerId);
                    $rawTableName = isset($wMeta['raw_table_name']) ? $wMeta['raw_table_name'] : '';
                    $wholesalerName = isset($wMeta['name']) ? $wMeta['name'] : '';
                    $skuPrefix = self::getSkuPrefixByWholesaler($wholesalerName, $rawTableName);

                    $skuMapByEan = [];
                    $skuMapByCode = [];
                    $eanMapBySku = [];
                    if ($rawTableName) {
                        $fullRawTable = _DB_PREFIX_ . pSQL($rawTableName);
                        try {
                            $rawList = Db::getInstance()->executeS("SELECT kod_kreskowy, kod, produkt_id FROM `$fullRawTable`");
                            if ($rawList) {
                                foreach ($rawList as $rItem) {
                                    $eanKey = trim((string)$rItem['kod_kreskowy']);
                                    $codeKey = trim((string)$rItem['kod']);
                                    $mappedSku = trim((string)$rItem['produkt_id']);

                                    if ($eanKey !== '' && $mappedSku !== '') {
                                        $skuMapByEan[$eanKey] = $mappedSku;
                                        $eanMapBySku[$mappedSku] = $eanKey;
                                    }
                                    if ($codeKey !== '' && $mappedSku !== '') {
                                        $skuMapByCode[$codeKey] = $mappedSku;
                                    }
                                }
                            }
                        } catch (Exception $e) {}
                    }

                    $invoiceVerifiedCount = 0; 

                    foreach ($rows as &$row) {
                        $ean = trim((string)$row['ean']);
                        $productId = trim((string)$row['product_id']);
                        $foundSku = '';

                        if ($ean !== '' && isset($skuMapByEan[$ean])) {
                            $foundSku = $skuMapByEan[$ean];
                        } elseif ($productId !== '' && isset($skuMapByCode[$productId])) {
                            $foundSku = $skuMapByCode[$productId];
                        } elseif ($productId !== '') {
                            // W CSV NaturaMed "Kod" to SKU hurtowni bez prefiksu
                            $foundSku = self::applySkuPrefix($productId, $skuPrefix);
                        }

                        $row['sku_wholesaler'] = $foundSku;

                        // Jeśli EAN nie jest dostępny w CSV zamówienia, dociągamy go z tabeli produktów hurtowni
                        // po SKU hurtowni (np. NAT_XXXX dla NaturaMed).
                        if ($ean === '' && $foundSku !== '' && isset($eanMapBySku[$foundSku])) {
                            $row['ean'] = $eanMapBySku[$foundSku];
                            $ean = $row['ean'];
                        }

                        // SANITYZACJA ILOŚCI
                        if (class_exists('AzadaOrderSanitizer')) {
                            $sanitized = AzadaOrderSanitizer::sanitizeRow($ean, $row['quantity'], $wholesalerId, $dateSql);
                            
                            $row['quantity'] = $sanitized['final_qty'];
                            
                            if ($sanitized['is_corrected']) {
                                $row['original_csv_qty'] = $sanitized['original_qty'];
                                $row['correction_info'] = $sanitized['correction_info'];
                                $row['invoice_qty'] = isset($sanitized['invoice_qty']) ? $sanitized['invoice_qty'] : 0;
                                
                                if ($row['invoice_qty'] > 0 || stripos($row['correction_info'], 'Fakturą') !== false) {
                                    $invoiceVerifiedCount++;
                                }
                            }
                        }

                        // === NOWOŚĆ: PRZELICZANIE WARTOŚCI FV (DLA TABELI) ===
                        // Wyliczamy nowe wartości na podstawie ZMIENIONEJ ilości
                        $finalQty = (float)$row['quantity'];
                        $unitNet = (float)$row['price_net'];
                        $unitGross = (float)$row['price_gross'];

                        $row['fv_price_net'] = $unitNet;
                        $row['fv_value_net'] = $finalQty * $unitNet;
                        $row['fv_price_gross'] = $unitGross;
                        $row['fv_value_gross'] = $finalQty * $unitGross;
                        // ======================================================
                    }
                    
                    AzadaDbRepository::saveFileDetails($idFile, $docNumber, $rows);
                    
                    $isVerified = ($invoiceVerifiedCount > 0) ? 1 : 0;
                    AzadaDbRepository::updateFileVerificationStatus($idFile, $isVerified);
                }
            }
            return ['status' => 'success', 'msg' => 'OK (Pobrano i Zaktualizowano)'];
        }
        return $downloadRes;
    }

    public static function processInvoiceDownload($wholesalerId, $docNumber, $docDate, $docNetto, $docDeadline, $isPaid, $downloadUrl, $cookieFile = null)
{
    self::ensureDatabaseStructure();

    $dateSql = AzadaCsvParser::sanitizeDate($docDate);
    $nettoSql = AzadaCsvParser::sanitizePrice($docNetto);
    $ext = self::detectExtensionFromUrl($downloadUrl);
    $safeName = AzadaFileHandler::getSafeFileName($docNumber, $ext);
    $localPath = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/FV/' . $safeName;

    if (!is_dir(dirname($localPath))) {
        mkdir(dirname($localPath), 0777, true);
    }

    $res = AzadaFileHandler::downloadFile($downloadUrl, $localPath, $cookieFile);

    if ($res['status'] == 'success') {
        if ($ext === 'csv') {
            $wholesalerName = (string)Db::getInstance()->getValue(
                'SELECT name FROM ' . _DB_PREFIX_ . 'azada_wholesaler_pro_integration WHERE id_wholesaler = ' . (int)$wholesalerId
            );
            if (stripos($wholesalerName, 'NaturaMed') !== false || stripos($wholesalerName, 'Natura Med') !== false) {
                AzadaFileHandler::normalizeCsvFileToUtf8($localPath);
            }
        }

        $idInvoice = AzadaDbRepository::saveInvoiceHeader($wholesalerId, $docNumber, $dateSql, $nettoSql, $docDeadline, $isPaid, $safeName);
        
        if ($idInvoice && $ext === 'csv') {
            $rows = AzadaCsvParser::parseCsv($localPath);
            if (!empty($rows)) {
                AzadaDbRepository::saveInvoiceDetails($idInvoice, $docNumber, $rows);
                AzadaLogger::addLog('FV IMPORT', "Zapisano FV: $docNumber", "Pozycji: " . count($rows), 1);
            } else {
                AzadaLogger::addLog('FV IMPORT', "Pusty plik FV: $docNumber", "", 2);
            }
        }
        return ['status' => 'success', 'msg' => 'OK (Format: ' . strtoupper($ext) . ')'];
    } else {
        AzadaLogger::addLog('FV ERROR', "Błąd pobierania FV: $docNumber", $res['msg'], 3);
    }
    return $res;
}


    public static function ensureDatabaseStructure()
    {
        $db = Db::getInstance();
        $tableInt = _DB_PREFIX_ . 'azada_wholesaler_pro_integration';
        $tableDetails = _DB_PREFIX_ . 'azada_wholesaler_pro_order_details';
        $tableFiles = _DB_PREFIX_ . 'azada_wholesaler_pro_order_files';
        $tableHubSettings = _DB_PREFIX_ . 'azada_wholesaler_pro_hub_settings';
        
        // Struktura tabeli integration
        $existsInt = $db->executeS("SHOW TABLES LIKE 'azada_wholesaler_pro_integration'"); 
        if (!empty($existsInt) || true) { 
             $colRaw = $db->executeS("SHOW COLUMNS FROM `$tableInt` LIKE 'raw_table_name'");
             if (empty($colRaw)) {
                 $db->execute("ALTER TABLE `$tableInt` ADD COLUMN `raw_table_name` VARCHAR(64) DEFAULT NULL AFTER `active`");
                 $db->execute("UPDATE `$tableInt` SET `raw_table_name` = 'azada_raw_bioplanet' WHERE `name` LIKE '%Bio Planet%'");
                 $db->execute("UPDATE `$tableInt` SET `raw_table_name` = 'azada_raw_ekowital' WHERE `name` LIKE '%EkoWital%' OR `name` LIKE '%Eko Wital%'");
             }
             $db->execute("UPDATE `$tableInt` SET `raw_table_name` = 'azada_raw_naturamed' WHERE (`name` LIKE '%NaturaMed%' OR `name` LIKE '%Natura Med%') AND (`raw_table_name` IS NULL OR `raw_table_name` = '')");
             $colLogin = $db->executeS("SHOW COLUMNS FROM `$tableInt` LIKE 'b2b_login'");
             if (empty($colLogin)) $db->execute("ALTER TABLE `$tableInt` ADD COLUMN `b2b_login` VARCHAR(255) DEFAULT NULL AFTER `api_key`");
             $colPass = $db->executeS("SHOW COLUMNS FROM `$tableInt` LIKE 'b2b_password'");
             if (empty($colPass)) $db->execute("ALTER TABLE `$tableInt` ADD COLUMN `b2b_password` VARCHAR(255) DEFAULT NULL AFTER `b2b_login`");
        }

        // Tabela Order Details - DODAJEMY NOWE KOLUMNY FV_
        $db->execute("CREATE TABLE IF NOT EXISTS `$tableDetails` ( `id_detail` int(11) NOT NULL AUTO_INCREMENT, `id_file` int(11) NOT NULL, `doc_number` varchar(100) NOT NULL, `product_id` varchar(50) DEFAULT NULL, `ean` varchar(50) DEFAULT NULL, `name` varchar(255) DEFAULT NULL, `quantity` int(11) DEFAULT 0, `unit` varchar(20) DEFAULT NULL, `price_net` decimal(20,2) DEFAULT 0.00, `value_net` decimal(20,2) DEFAULT 0.00, `vat_rate` int(11) DEFAULT 0, `price_gross` decimal(20,2) DEFAULT 0.00, `value_gross` decimal(20,2) DEFAULT 0.00, `date_add` datetime NOT NULL, PRIMARY KEY (`id_detail`), KEY `id_file` (`id_file`) ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;");
        
        $colSku = $db->executeS("SHOW COLUMNS FROM `$tableDetails` LIKE 'sku_wholesaler'");
        if (empty($colSku)) $db->execute("ALTER TABLE `$tableDetails` ADD COLUMN `sku_wholesaler` VARCHAR(64) DEFAULT NULL AFTER `doc_number`");
        
        $colOrig = $db->executeS("SHOW COLUMNS FROM `$tableDetails` LIKE 'original_csv_qty'");
        if (empty($colOrig)) $db->execute("ALTER TABLE `$tableDetails` ADD COLUMN `original_csv_qty` INT(11) DEFAULT NULL AFTER `quantity`");
        
        $colInfo = $db->executeS("SHOW COLUMNS FROM `$tableDetails` LIKE 'correction_info'");
        if (empty($colInfo)) $db->execute("ALTER TABLE `$tableDetails` ADD COLUMN `correction_info` VARCHAR(255) DEFAULT NULL AFTER `original_csv_qty`");
        
        $colInv = $db->executeS("SHOW COLUMNS FROM `$tableDetails` LIKE 'invoice_qty'");
        if (empty($colInv)) $db->execute("ALTER TABLE `$tableDetails` ADD COLUMN `invoice_qty` INT(11) DEFAULT 0 AFTER `correction_info`");

        // === NOWE KOLUMNY DLA WARTOŚCI FV ===
        $colFvPn = $db->executeS("SHOW COLUMNS FROM `$tableDetails` LIKE 'fv_price_net'");
        if (empty($colFvPn)) $db->execute("ALTER TABLE `$tableDetails` ADD COLUMN `fv_price_net` DECIMAL(20,2) DEFAULT 0.00 AFTER `value_gross`");

        $colFvVn = $db->executeS("SHOW COLUMNS FROM `$tableDetails` LIKE 'fv_value_net'");
        if (empty($colFvVn)) $db->execute("ALTER TABLE `$tableDetails` ADD COLUMN `fv_value_net` DECIMAL(20,2) DEFAULT 0.00 AFTER `fv_price_net`");

        $colFvPg = $db->executeS("SHOW COLUMNS FROM `$tableDetails` LIKE 'fv_price_gross'");
        if (empty($colFvPg)) $db->execute("ALTER TABLE `$tableDetails` ADD COLUMN `fv_price_gross` DECIMAL(20,2) DEFAULT 0.00 AFTER `fv_value_net`");

        $colFvVg = $db->executeS("SHOW COLUMNS FROM `$tableDetails` LIKE 'fv_value_gross'");
        if (empty($colFvVg)) $db->execute("ALTER TABLE `$tableDetails` ADD COLUMN `fv_value_gross` DECIMAL(20,2) DEFAULT 0.00 AFTER `fv_price_gross`");
        // =====================================

        $db->execute("CREATE TABLE IF NOT EXISTS `$tableFiles` ( `id_file` int(11) NOT NULL AUTO_INCREMENT, `id_wholesaler` int(11) NOT NULL, `external_doc_number` varchar(100) DEFAULT NULL, `doc_date` varchar(50) DEFAULT NULL, `amount_netto` varchar(50) DEFAULT NULL, `file_name` varchar(255) DEFAULT NULL, `download_hash` varchar(255) DEFAULT NULL, `status` varchar(50) DEFAULT NULL, `is_downloaded` tinyint(1) DEFAULT 0, `date_add` datetime NOT NULL, `date_upd` datetime NOT NULL, PRIMARY KEY (`id_file`), KEY `id_wholesaler` (`id_wholesaler`) ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;");
        
        $colVer = $db->executeS("SHOW COLUMNS FROM `$tableFiles` LIKE 'is_verified_with_invoice'");
        if (empty($colVer)) {
            $db->execute("ALTER TABLE `$tableFiles` ADD COLUMN `is_verified_with_invoice` TINYINT(1) DEFAULT 0 AFTER `is_downloaded`");
        }

        $db->execute("CREATE TABLE IF NOT EXISTS `$tableHubSettings` (
            `id_setting` INT(11) NOT NULL AUTO_INCREMENT,
            `id_wholesaler` INT(11) NOT NULL,
            `hub_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `sync_mode` VARCHAR(32) NOT NULL DEFAULT 'api',
            `price_field` VARCHAR(64) NOT NULL DEFAULT 'CenaPoRabacieNetto',
            `notes` VARCHAR(255) DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_setting`),
            UNIQUE KEY `uniq_wholesaler` (`id_wholesaler`)
        ) ENGINE="._MYSQL_ENGINE_." DEFAULT CHARSET=utf8;");

        $allWholesalers = $db->executeS('SELECT id_wholesaler FROM `'.bqSQL($tableInt).'`');
        if (is_array($allWholesalers)) {
            foreach ($allWholesalers as $wholesalerRow) {
                $idWholesaler = (int)$wholesalerRow['id_wholesaler'];
                if ($idWholesaler <= 0) {
                    continue;
                }

                $existsHubRow = (int)$db->getValue('SELECT COUNT(*) FROM `'.bqSQL($tableHubSettings).'` WHERE id_wholesaler='.(int)$idWholesaler);
                if ($existsHubRow > 0) {
                    continue;
                }

                $db->insert('azada_wholesaler_pro_hub_settings', [
                    'id_wholesaler' => $idWholesaler,
                    'hub_enabled' => 1,
                    'sync_mode' => 'api',
                    'price_field' => 'CenaPoRabacieNetto',
                    'notes' => null,
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        AzadaLogger::ensureTable();
        AzadaDbRepository::ensureInvoiceTables();
    }

    public static function ensureTabs()
    {
        $parentClassName = 'AdminAzadaParent';
        $parentId = (int)Tab::getIdFromClassName($parentClassName);
        if (!$parentId) return;

        if (!Tab::getIdFromClassName('AdminAzadaOrders')) { $tab = new Tab(); $tab->active = 1; $tab->class_name = 'AdminAzadaOrders'; $tab->name = array(); foreach (Language::getLanguages(true) as $lang) { $tab->name[$lang['id_lang']] = 'Dokumenty CSV (B2B)'; } $tab->id_parent = $parentId; $tab->module = 'azada_wholesaler_pro'; $tab->add(); }
        if (!Tab::getIdFromClassName('AdminAzadaInvoices')) { $tab = new Tab(); $tab->active = 1; $tab->class_name = 'AdminAzadaInvoices'; $tab->name = array(); foreach (Language::getLanguages(true) as $lang) { $tab->name[$lang['id_lang']] = 'Faktury Zakupu'; } $tab->id_parent = $parentId; $tab->module = 'azada_wholesaler_pro'; $tab->add(); }
        if (!Tab::getIdFromClassName('AdminAzadaLogs')) { $tab = new Tab(); $tab->active = 1; $tab->class_name = 'AdminAzadaLogs'; $tab->name = array(); foreach (Language::getLanguages(true) as $lang) { $tab->name[$lang['id_lang']] = 'Logi Systemowe'; } $tab->id_parent = $parentId; $tab->module = 'azada_wholesaler_pro'; $tab->add(); }
    }
}
