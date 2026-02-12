<?php

/**
 * CRON: Pobiera nowe / zmienione pliki CSV zamówień B2B do bazy.
 * Uruchamiany zewnętrznie po URL-u.
 */

include_once(dirname(__FILE__) . '/../../config/config.inc.php');
include_once(dirname(__FILE__) . '/../../init.php');

if (!defined('_PS_MODULE_DIR_')) define('_PS_MODULE_DIR_', _PS_ROOT_DIR_ . '/modules/');

require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/AzadaWholesaler.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaDbRepository.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaB2BComparator.php');

foreach (glob(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/integrations/*.php') as $filename) {
    require_once($filename);
}

function getWorkerClassCron($wholesalerName)
{
    $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', ucwords($wholesalerName));
    $className = 'Azada' . $cleanName . 'B2B';
    return class_exists($className) ? new $className() : null;
}

echo "<pre>";
echo "==========================================================\n";
echo "   START CRON ZAMÓWIENIA B2B - " . date('Y-m-d H:i:s') . "\n";
echo "==========================================================\n\n";

AzadaWholesaler::performMaintenance();

$integrations = Db::getInstance()->executeS(
    "SELECT * FROM "._DB_PREFIX_."azada_wholesaler_pro_integration
    WHERE active = 1
    AND b2b_login IS NOT NULL AND b2b_login != ''
    AND b2b_password IS NOT NULL AND b2b_password != ''"
);

$filesDownloaded = 0;
$hasErrors = false;

if ($integrations) {
    foreach ($integrations as $wholesaler) {
        echo ">> HURTOWNIA: " . $wholesaler['name'] . "\n";

        $worker = getWorkerClassCron($wholesaler['name']);
        if (!$worker || !method_exists($worker, 'scrapeOrders')) {
            echo "BŁĄD: Brak klasy integracji lub metody scrapeOrders.\n\n";
            $hasErrors = true;
            continue;
        }

        echo "[LOGOWANIE] Pobieranie listy... ";
        $pass = base64_decode($wholesaler['b2b_password']);
        $list = $worker->scrapeOrders($wholesaler['b2b_login'], $pass);
        
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

            $dbInfo = AzadaDbRepository::getFileByDocNumber($row['number'], (int)$wholesaler['id_wholesaler']);
            $action = AzadaB2BComparator::compare($row['number'], $row['netto'], $row['status'], $dbInfo);

            if ($action != AzadaB2BComparator::ACTION_NONE) {
                echo " > " . $row['number'] . " [POBIERANIE DO BAZY]... ";
                
                $cookieSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $wholesaler['name']));
                $cookieFile = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/cookies_' . $cookieSlug . '.txt';
                
                $res = AzadaWholesaler::processDownload(
                    $wholesaler['id_wholesaler'], $row['number'], $row['date'], $row['netto'], $row['status'], $url, $cookieFile
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
echo "   KONIEC PRACY CRON ZAMÓWIENIA\n";
echo "==========================================================\n";
echo "</pre>";
