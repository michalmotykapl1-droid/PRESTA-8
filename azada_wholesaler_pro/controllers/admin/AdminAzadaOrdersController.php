<?php

if (!defined('_PS_MODULE_DIR_')) define('_PS_MODULE_DIR_', _PS_ROOT_DIR_ . '/modules/');

require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/AzadaWholesaler.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaDbRepository.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaB2BComparator.php');

if (file_exists(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaOrderSanitizer.php')) {
    require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaOrderSanitizer.php');
}

foreach (glob(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/integrations/*.php') as $filename) {
    require_once($filename);
}

class AdminAzadaOrdersController extends ModuleAdminController
{
    private function normalizeAmount($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '-';
        }

        return preg_replace('/\s+/', ' ', $value);
    }

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'azada_wholesaler_pro_order_files'; 
        $this->className = 'AzadaWholesaler'; 
        $this->lang = false;
        
        AzadaWholesaler::ensureDatabaseStructure();

        parent::__construct();
    }

    public function initContent()
    {
        if (class_exists('AzadaOrderSanitizer')) {
            AzadaOrderSanitizer::ensureTableStructure();
        }

        if (Tools::getValue('action') == 'streamFile') {
            $this->processStreamFile();
            exit;
        }

        parent::initContent();
        
        $this->context->smarty->assign([
            'auto_download_active' => (int)Configuration::get('AZADA_B2B_AUTO_DOWNLOAD'),
            'controller_url' => $this->context->link->getAdminLink('AdminAzadaOrders')
        ]);

        $tplPath = dirname(__FILE__) . '/../../views/templates/admin/b2b_orders.tpl';
        if (file_exists($tplPath)) {
            $this->context->smarty->assign('content', $this->context->smarty->fetch($tplPath));
        } else {
            $this->context->smarty->assign('content', '<div class="alert alert-danger">Brak pliku b2b_orders.tpl</div>');
        }
    }

    private function getWorkerClass($wholesalerName)
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', ucwords($wholesalerName));
        $className = 'Azada' . $cleanName . 'B2B';
        return class_exists($className) ? new $className() : null;
    }


    private function normalizeStatusToBioPlanet($status)
    {
        $raw = trim((string)$status);
        if ($raw === '') {
            return '';
        }

        $statusLower = mb_strtolower($raw, 'UTF-8');

        if (strpos($statusLower, 'różnic') !== false || strpos($statusLower, 'roznic') !== false) {
            return 'ZAMÓWIENIE RÓŻNICOWE';
        }

        if (strpos($statusLower, 'nowe') !== false) {
            return 'NOWE ZAMÓWIENIE';
        }

        if (strpos($statusLower, 'anul') !== false || strpos($statusLower, 'brak towar') !== false) {
            return 'ANULOWANE - BRAK TOWARU';
        }

        if (strpos($statusLower, 'zrealiz') !== false) {
            return 'ZREALIZOWANE';
        }

        if (
            strpos($statusLower, 'przekazan') !== false ||
            strpos($statusLower, 'niezrealiz') !== false ||
            strpos($statusLower, 'w realiz') !== false ||
            strpos($statusLower, 'w trakcie') !== false ||
            strpos($statusLower, 'realizacji') !== false ||
            strpos($statusLower, 'oczek') !== false
        ) {
            return 'PRZEKAZANE DO MAGAZYNU';
        }

        return mb_strtoupper($raw, 'UTF-8');
    }

    public function ajaxProcessFetchList()
    {
        AzadaWholesaler::performMaintenance();

        $integrations = Db::getInstance()->executeS(
            "SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_integration
            WHERE active = 1
            AND b2b_login IS NOT NULL AND b2b_login != ''
            AND b2b_password IS NOT NULL AND b2b_password != ''"
        );

        if (!$integrations) die(json_encode(['status' => 'error', 'msg' => 'Brak aktywnych hurtowni B2B.']));

        $groupedData = [];
        $errors = [];

        foreach ($integrations as $wholesaler) {
            $worker = $this->getWorkerClass($wholesaler['name']);
            
            if ($worker && method_exists($worker, 'scrapeOrders')) {
                $pass = base64_decode($wholesaler['b2b_password']);
                $result = $worker->scrapeOrders($wholesaler['b2b_login'], $pass);

                if (isset($result['status']) && $result['status'] == 'success') {
                    usort($result['data'], function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });

                    foreach ($result['data'] as &$row) {
                        $row['date'] = isset($row['date']) ? trim((string)$row['date']) : '';
                        $row['number'] = isset($row['number']) ? trim((string)$row['number']) : '';
                        $row['netto'] = $this->normalizeAmount(isset($row['netto']) ? $row['netto'] : '');
                        $row['brutto'] = $this->normalizeAmount(isset($row['brutto']) ? $row['brutto'] : '');
                        $row['options'] = (isset($row['options']) && is_array($row['options'])) ? $row['options'] : [];

                        if ($row['number'] === '') {
                            continue;
                        }

                        $row['id_wholesaler'] = $wholesaler['id_wholesaler'];
                        $row['wholesaler_name'] = $wholesaler['name'];

                        $row['status'] = $this->normalizeStatusToBioPlanet(isset($row['status']) ? $row['status'] : '');
                        $statusLower = mb_strtolower($row['status'], 'UTF-8');
                        $row['pill_class'] = 'pill-default';

                        if (stripos($statusLower, 'zrealizowane') !== false) {
                            $row['pill_class'] = 'pill-success';
                        } elseif (stripos($statusLower, 'anulowane') !== false || stripos($statusLower, 'brak towaru') !== false) {
                            $row['pill_class'] = 'pill-danger';
                        } elseif (stripos($statusLower, 'przekazane do magazynu') !== false || stripos($statusLower, 'zamówienie różnicowe') !== false || stripos($statusLower, 'nowe zamówienie') !== false) {
                            $row['pill_class'] = 'pill-warning';
                        }

                        $dbInfo = AzadaDbRepository::getFileByDocNumber($row['number'], (int)$wholesaler['id_wholesaler']);

                        // Status "pobrano" opieramy o faktyczny stan pliku na dysku + wpis w bazie,
                        // a nie o porównanie statusu/kwoty (to powodowało ponowne kolejki po odświeżeniu).
                        $row['is_downloaded'] = false;
                        if (!empty($dbInfo) && (int)$dbInfo['is_downloaded'] === 1 && !empty($dbInfo['file_name'])) {
                            $storedPath = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/' . $dbInfo['file_name'];
                            $row['is_downloaded'] = file_exists($storedPath);
                        }

                        $row['is_verified'] = false;
                        if ($row['is_downloaded'] && isset($dbInfo['is_verified_with_invoice']) && $dbInfo['is_verified_with_invoice'] == 1) {
                            $row['is_verified'] = true;
                        }

                        $row['clean_number'] = preg_replace('/[^a-zA-Z0-9]/', '', $row['number']);

                        $systemCsvUrl = '';
                        if (!empty($row['options'])) {
                            foreach ($row['options'] as $opt) {
                                $nameLower = mb_strtolower($opt['name'], 'UTF-8');
                                if (strpos($nameLower, 'utf8') !== false || strpos($nameLower, 'csv') !== false) {
                                    $systemCsvUrl = $opt['url'];
                                    break;
                                }
                            }
                            if (empty($systemCsvUrl) && isset($row['options'][0])) $systemCsvUrl = $row['options'][0]['url'];
                        }
                        $row['system_csv_url'] = $systemCsvUrl;
                    }
                    $groupedData[$wholesaler['name']] = $result['data'];
                } else {
                    $errors[] = $wholesaler['name'] . ": Błąd połączenia";
                }
            }
        }

        if (empty($groupedData) && !empty($errors)) die(json_encode(['status' => 'error', 'msg' => implode(', ', $errors)]));

        $this->context->smarty->assign([
            'grouped_data' => $groupedData,
            'auto_download_active' => (int)Configuration::get('AZADA_B2B_AUTO_DOWNLOAD'),
            'controller_url' => $this->context->link->getAdminLink('AdminAzadaOrders')
        ]);

        $tplPath = dirname(__FILE__) . '/../../views/templates/admin/b2b_orders_list.tpl';
        if (file_exists($tplPath)) {
            $html = $this->context->smarty->fetch($tplPath);
            die(json_encode(['status' => 'success', 'html' => $html]));
        } else {
            die(json_encode(['status' => 'error', 'msg' => 'Błąd: Brak pliku b2b_orders_list.tpl']));
        }
    }

    public function ajaxProcessDownloadSystemCsv() {
        $idWholesaler = (int)Tools::getValue('id_wholesaler');
        $url = Tools::getValue('url');
        $docNumber = Tools::getValue('doc_number');
        $docDate = Tools::getValue('doc_date');
        $docNetto = Tools::getValue('doc_netto');
        $docStatus = Tools::getValue('doc_status');
        if (!$idWholesaler) die(json_encode(['status' => 'error', 'msg' => 'Brak ID']));
        $wholesalerData = Db::getInstance()->getRow(
            "SELECT name, b2b_login, b2b_password FROM "._DB_PREFIX_."azada_wholesaler_pro_integration WHERE id_wholesaler = ".(int)$idWholesaler
        );
        if (!$wholesalerData) die(json_encode(['status' => 'error', 'msg' => 'Brak konfiguracji.']));
        if (empty($wholesalerData['b2b_login']) || empty($wholesalerData['b2b_password'])) {
            die(json_encode(['status' => 'error', 'msg' => 'Brak danych logowania B2B.']));
        }
        $wholesalerName = $wholesalerData['name'];
        $cookieSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $wholesalerName));
        $cookieFile = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/cookies_' . $cookieSlug . '.txt';
        $res = AzadaWholesaler::processDownload($idWholesaler, $docNumber, $docDate, $docNetto, $docStatus, $url, $cookieFile);
        if ($res['status'] == 'success') AzadaLogger::addLog('PANEL ADMINA', "Pobrano CSV: $docNumber", "Status: $docStatus", AzadaLogger::SEVERITY_SUCCESS);
        else AzadaLogger::addLog('PANEL ADMINA', "Błąd: $docNumber", $res['msg'], AzadaLogger::SEVERITY_ERROR);
        die(json_encode($res));
    }

    public function processStreamFile() {
        $url = Tools::getValue('url');
        $docNumber = Tools::getValue('doc_number');
        $idWholesaler = (int)Tools::getValue('id_wholesaler');
        $formatName = Tools::getValue('format_name');
        if (!$url || !$idWholesaler) die('Błąd: Brak danych.');
        $wholesalerData = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_integration WHERE id_wholesaler = ".(int)$idWholesaler);
        if (!$wholesalerData) die('Brak konfiguracji.');
        if (empty($wholesalerData['b2b_login']) || empty($wholesalerData['b2b_password'])) {
            die('Brak danych logowania B2B.');
        }
        $cookieSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $wholesalerData['name']));
        $cookieFile = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/cookies_' . $cookieSlug . '.txt';
        if (!file_exists($cookieFile) && stripos($wholesalerData['name'], 'Bio Planet') !== false) {
            $pass = base64_decode($wholesalerData['b2b_password']);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://bioplanet.pl/logowanie/m?ReturnURL=/zamowienia');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['Uzytkownik' => $wholesalerData['b2b_login'], 'Haslo' => $pass, 'logowanie' => 'ZALOGUJ']));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            curl_close($ch);
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
        $fileContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode == 200 && !empty($fileContent)) {
            $ext = 'csv';
            if (stripos($formatName, 'pdf') !== false) $ext = 'pdf';
            elseif (stripos($formatName, 'xml') !== false) $ext = 'xml';
            elseif (stripos($formatName, 'epp') !== false) $ext = 'epp';
            $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $docNumber) . '.' . $ext;
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($fileContent));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            echo $fileContent;
            exit;
        } else {
            die('Błąd pobierania pliku (HTTP: ' . $httpCode . ')');
        }
    }

    public function ajaxProcessGetOrderDetails() {
        $docNumber = Tools::getValue('doc_number');
        $rows = AzadaDbRepository::getDetailsByDocNumber($docNumber);
        if (!$rows) die('<div class="alert alert-warning" style="margin:20px;">Brak szczegółów w bazie.</div>');
        $fileInfo = AzadaDbRepository::getFileByDocNumber($docNumber, null);
        $wholesalerId = isset($fileInfo['id_wholesaler']) ? (int)$fileInfo['id_wholesaler'] : 0;
        $rawTableName = ''; $wholesalerName = '';
        if ($wholesalerId) {
            $wData = Db::getInstance()->getRow("SELECT name, raw_table_name FROM "._DB_PREFIX_."azada_wholesaler_pro_integration WHERE id_wholesaler = " . (int)$wholesalerId);
            if ($wData) { $rawTableName = $wData['raw_table_name']; $wholesalerName = $wData['name']; }
        }
        $skuPrefix = AzadaWholesaler::getSkuPrefixByWholesaler($wholesalerName, $rawTableName);
        $html = '<div style="padding:15px; background-color:#fff;">';
        $html .= '<h5 style="margin-top:0; margin-bottom:10px; color:#555; font-size:12px; font-weight:bold; border-bottom:1px solid #eee; padding-bottom:5px;">Szczegóły Zamówienia: '.$docNumber.'</h5>';
        $html .= '<table class="table table-hover" style="font-size:11px; margin:0;"><thead><tr style="background:#f9f9f9;"><th>SKU Hurtowni</th><th>EAN</th><th>Produkt</th><th class="text-center">Ilość</th><th class="text-right">Netto</th><th class="text-right">Wartość</th></tr></thead><tbody>';
        $total = 0;
        foreach ($rows as $row) {
            $valNet = (float)$row['fv_value_net'];
            if ($valNet <= 0.001) $valNet = (float)$row['value_net'];
            
            $total += $valNet;
            
            $skuDisplay = !empty($row['sku_wholesaler']) ? $row['sku_wholesaler'] : $row['product_id'];
            $ean = trim($row['ean']);
            $foundInRaw = false;
            if (!empty($rawTableName)) {
                $tableNameFull = _DB_PREFIX_ . pSQL($rawTableName);
                try {
                    $foundSku = '';
                    if (!empty($ean)) {
                        $foundSku = Db::getInstance()->getValue("SELECT produkt_id FROM `$tableNameFull` WHERE kod_kreskowy = '" . pSQL($ean) . "'");
                    }
                    if (empty($foundSku) && !empty($row['product_id'])) {
                        $foundSku = Db::getInstance()->getValue("SELECT produkt_id FROM `$tableNameFull` WHERE kod = '" . pSQL($row['product_id']) . "'");
                    }
                    if ($foundSku) { $skuDisplay = $foundSku; $foundInRaw = true; }

                    // EAN uzupełniamy po SKU hurtowni z tabeli produktów (ważne dla NaturaMed).
                    if (empty($ean)) {
                        $eanFromRaw = Db::getInstance()->getValue("SELECT kod_kreskowy FROM `$tableNameFull` WHERE produkt_id = '" . pSQL($skuDisplay) . "'");
                        if ($eanFromRaw) {
                            $ean = $eanFromRaw;
                        }
                    }
                } catch (Exception $e) {}
            }
            if (!$foundInRaw) {
                $skuDisplay = AzadaWholesaler::applySkuPrefix($skuDisplay, $skuPrefix);

                if (!empty($rawTableName) && empty($ean)) {
                    $tableNameFull = _DB_PREFIX_ . pSQL($rawTableName);
                    try {
                        $eanFromRaw = Db::getInstance()->getValue("SELECT kod_kreskowy FROM `$tableNameFull` WHERE produkt_id = '" . pSQL($skuDisplay) . "'");
                        if ($eanFromRaw) {
                            $ean = $eanFromRaw;
                        }
                    } catch (Exception $e) {}
                }
            }
            $skuHtml = '<span>'.$skuDisplay.'</span>';
            $quantityHtml = (int)$row['quantity'];
            $correctionNote = '';
            $rowStyle = '';
            if (!empty($row['correction_info'])) {
                $isConfirmed = (stripos($row['correction_info'], 'Potwierdzone') !== false);
                $isInfo = (stripos($row['correction_info'], 'Info:') !== false);
                if ($isConfirmed) {
                    $quantityHtml = '<span style="color:#2ecc71; font-weight:bold;">'.(int)$row['quantity'].'</span>';
                    $correctionNote = '<div style="color:#27ae60; font-size:10px; margin-top:2px; font-weight:bold;"><i class="icon-check"></i> '.$row['correction_info'].'</div>';
                    $rowStyle = 'background-color:#f0fff4;'; 
                } elseif ($isInfo) {
                    $quantityHtml = '<span style="color:#3498db; font-weight:bold;">'.(int)$row['quantity'].'</span>';
                    $correctionNote = '<div style="color:#2980b9; font-size:9px; margin-top:2px; font-style:italic;"><i class="icon-info-sign"></i> '.$row['correction_info'].'</div>';
                    $rowStyle = 'background-color:#f0faff;';
                } else {
                    $orig = isset($row['original_csv_qty']) ? $row['original_csv_qty'] : '?';
                    $quantityHtml = '<span style="color:#2ecc71; font-weight:bold;">'.(int)$row['quantity'].'</span>';
                    $quantityHtml .= '<div style="text-decoration:line-through; color:#e74c3c; font-size:9px;">CSV: '.$orig.'</div>';
                    $correctionNote = '<div style="color:#d35400; font-size:9px; margin-top:2px; font-style:italic;"><i class="icon-warning"></i> '.$row['correction_info'].'</div>';
                    $rowStyle = 'background-color:#fffdf5;';
                }
            }
            $html .= '<tr style="'.$rowStyle.'"><td>'.$skuHtml.'</td><td class="text-muted">'.$ean.'</td><td><strong>'.$row['name'].'</strong>'.$correctionNote.'</td><td class="text-center">'.$quantityHtml.'</td><td class="text-right">'.number_format($row['price_net'], 2, ',', ' ').'</td><td class="text-right">'.number_format($valNet, 2, ',', ' ').'</td></tr>';
        }
        $html .= '<tr style="background:#eafcff; font-weight:bold; border-top:2px solid #ddd;"><td colspan="5" class="text-right">SUMA ZAMÓWIENIA (Skorygowana):</td><td class="text-right" style="color:#25b9d7;">'.number_format($total, 2, ',', ' ').' PLN</td></tr></tbody></table>';
        $html .= '</div>';
        die($html);
    }
}
