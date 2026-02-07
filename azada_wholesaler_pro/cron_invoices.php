<?php
/**
 * CRON FAKTURY (FV) - TYLKO SYSTEMOWY CSV
 */

ob_start();
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');

$token = Tools::getValue('token');
if (!$token || $token !== Configuration::get('AZADA_CRON_KEY')) die('Error: Invalid Token');

require_once(dirname(__FILE__) . '/classes/AzadaWholesaler.php');
require_once(dirname(__FILE__) . '/classes/services/AzadaLogger.php');
require_once(dirname(__FILE__) . '/classes/services/AzadaDbRepository.php');
require_once(dirname(__FILE__) . '/classes/services/AzadaInvoiceComparator.php');
require_once(dirname(__FILE__) . '/classes/services/AzadaFileHandler.php');
foreach (glob(dirname(__FILE__) . '/classes/integrations/*.php') as $f) require_once($f);

echo "<pre>";
echo "==========================================================\n";
echo "   CRON FAKTURY (SYSTEMOWY) - START: " . date('Y-m-d H:i:s') . "\n";
echo "==========================================================\n";

$hasErrors = false;
$filesDownloaded = 0;

echo "[INFO] Czyszczenie starych plików... ";
AzadaWholesaler::performMaintenance();
echo "OK.\n\n";

$integrations = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_integration WHERE active = 1 AND b2b_login IS NOT NULL");

if ($integrations) {
    foreach ($integrations as $wholesaler) {
        echo "----------------------------------------------------------\n";
        echo "HURTOWNIA: [" . $wholesaler['name'] . "]\n";

        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', ucwords($wholesaler['name']));
        $className = 'Azada' . $cleanName . 'B2B';
        $worker = null;
        if (class_exists($className)) $worker = new $className();

        if (!$worker) { echo "[SKIP] Brak klasy integracji.\n"; continue; }

        echo "[LOGOWANIE] Pobieranie listy... ";
        $pass = base64_decode($wholesaler['b2b_password']);
        $list = $worker->scrapeInvoices($wholesaler['b2b_login'], $pass);
        
        if (!isset($list['status']) || $list['status'] == 'error') {
            echo "BŁĄD POŁĄCZENIA.\n"; $hasErrors = true; continue;
        }
        
        $count = count($list['data']);
        echo "OK (Znaleziono: $count)\n";

        foreach ($list['data'] as $row) {
            // SZUKAMY CSV UTF-8 DLA BAZY
            $url = '';
            foreach ($row['options'] as $opt) { 
                $nameLower = mb_strtolower($opt['name'], 'UTF-8');
                if (strpos($nameLower, 'utf8') !== false || strpos($nameLower, 'csv') !== false) { 
                    $url = $opt['url']; break; 
                }
            }
            if (!$url && !empty($row['options'])) $url = $row['options'][0]['url']; // Fallback

            if (!$url) { echo " > " . $row['number'] . " [SKIP - BRAK PLIKU]\n"; continue; }

            $dbInfo = AzadaDbRepository::getInvoiceByNumber($row['number']);
            $action = AzadaInvoiceComparator::compare($row['number'], $row['netto'], $row['is_paid'], $dbInfo);

            if ($action != AzadaInvoiceComparator::ACTION_NONE) {
                echo " > " . $row['number'] . " [POBIERANIE DO BAZY]... ";
                
                // Używamy funkcji processInvoiceDownload, która obsługuje tylko CSV->Baza
                $cookieSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $wholesaler['name']));
                $cookieFile = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/cookies_' . $cookieSlug . '.txt';
                
                $res = AzadaWholesaler::processInvoiceDownload(
                    $wholesaler['id_wholesaler'], $row['number'], $row['date'], $row['netto'], $row['deadline'], $row['is_paid'], $url, $cookieFile
                );
                
                if ($res['status'] == 'success') { echo "OK.\n"; $filesDownloaded++; } 
                else { echo "BŁĄD: " . $res['msg'] . "\n"; $hasErrors = true; }
            } else {
                echo " > " . $row['number'] . " [OK]\n";
            }
        }
    }
} else { echo "Brak aktywnych hurtowni B2B.\n"; }

echo "\n==========================================================\n";
echo "   KONIEC PRACY CRON FV\n";
echo "==========================================================\n";
echo "</pre>";

$output = ob_get_contents();
ob_end_flush();

$title = "CRON FV";
if ($filesDownloaded > 0) $title .= " (Pobrano: $filesDownloaded)";
$severity = ($hasErrors) ? AzadaLogger::SEVERITY_ERROR : (($filesDownloaded > 0) ? AzadaLogger::SEVERITY_SUCCESS : AzadaLogger::SEVERITY_INFO);
AzadaLogger::addLog('CRON_FV', $title, $output, $severity);