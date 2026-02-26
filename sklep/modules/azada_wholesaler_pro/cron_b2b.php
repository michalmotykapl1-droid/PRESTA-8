<?php

/**
 * CRON: Pobiera nowe / zmienione pliki CSV zamówień B2B do bazy.
 *
 * Endpoint uruchamiany zewnętrznie (URL z tokenem).
 *
 * Logowanie:
 * - zapisuje pełny output do tabeli azada_wholesaler_pro_logs (source=CRON_B2B)
 * - severity zależne od błędów / ilości pobranych plików
 */

require_once(dirname(__FILE__) . '/cron_init.php');

require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaDbRepository.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaB2BComparator.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaLogger.php');

// Integracje
foreach (glob(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/integrations/*.php') as $filename) {
    require_once($filename);
}

function azadaGetB2BWorker($wholesalerName)
{
    $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', ucwords($wholesalerName));
    $className = 'Azada' . $cleanName . 'B2B';
    return class_exists($className) ? new $className() : null;
}

// Token + lock + środowisko
AzadaCronRunner::init('b2b_orders');

$ok = 0;
$err = 0;
$skip = 0;
$filesDownloaded = 0;
$hasErrors = false;

ob_start();

echo "==========================================================\n";
echo "   CRON B2B CSV (ZAMÓWIENIA) - START: " . date('Y-m-d H:i:s') . "\n";
echo "==========================================================\n\n";

echo "[INFO] Maintenance... ";
AzadaWholesaler::performMaintenance();
echo "OK.\n\n";

$integrations = Db::getInstance()->executeS(
    "SELECT * FROM " . _DB_PREFIX_ . "azada_wholesaler_pro_integration
     WHERE active = 1
       AND b2b_login IS NOT NULL AND b2b_login != ''
       AND b2b_password IS NOT NULL AND b2b_password != ''"
);

if (!$integrations) {
    echo "Brak aktywnych hurtowni B2B.\n";
    $skip++;
} else {
    foreach ($integrations as $wholesaler) {
        echo "----------------------------------------------------------\n";
        echo "HURTOWNIA: [" . $wholesaler['name'] . "]\n";

        $worker = azadaGetB2BWorker($wholesaler['name']);
        if (!$worker || !method_exists($worker, 'scrapeOrders')) {
            echo "[ERR] Brak klasy integracji lub metody scrapeOrders().\n\n";
            $err++;
            $hasErrors = true;
            continue;
        }

        echo "[LOGOWANIE] Pobieranie listy... ";
        $pass = base64_decode($wholesaler['b2b_password']);
        $list = $worker->scrapeOrders($wholesaler['b2b_login'], $pass);

        if (!isset($list['status']) || $list['status'] === 'error') {
            echo "BŁĄD POŁĄCZENIA.\n";
            $err++;
            $hasErrors = true;
            continue;
        }

        $rows = isset($list['data']) && is_array($list['data']) ? $list['data'] : [];
        $count = count($rows);
        echo "OK (Znaleziono: {$count})\n";

        foreach ($rows as $row) {
            $number = isset($row['number']) ? $row['number'] : '';
            if ($number === '') {
                $skip++;
                continue;
            }

            // Szukamy CSV UTF-8 do bazy
            $url = '';
            if (!empty($row['options']) && is_array($row['options'])) {
                foreach ($row['options'] as $opt) {
                    $nameLower = isset($opt['name']) ? mb_strtolower($opt['name'], 'UTF-8') : '';
                    if (strpos($nameLower, 'utf8') !== false || strpos($nameLower, 'csv') !== false) {
                        $url = isset($opt['url']) ? $opt['url'] : '';
                        break;
                    }
                }
                if (!$url) {
                    // Fallback: bierz pierwszą opcję
                    $first = $row['options'][0];
                    $url = isset($first['url']) ? $first['url'] : '';
                }
            }

            if (!$url) {
                echo " > {$number} [SKIP - BRAK PLIKU]\n";
                $skip++;
                continue;
            }

            $dbInfo = AzadaDbRepository::getFileByDocNumber($number, (int)$wholesaler['id_wholesaler']);

            $netto = isset($row['netto']) ? $row['netto'] : 0;
            $status = isset($row['status']) ? $row['status'] : '';
            $action = AzadaB2BComparator::compare($number, $netto, $status, $dbInfo);

            if ($action === AzadaB2BComparator::ACTION_NONE) {
                echo " > {$number} [OK]\n";
                $skip++;
                continue;
            }

            echo " > {$number} [POBIERANIE DO BAZY]... ";

            $cookieSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $wholesaler['name']));
            $cookieFile = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/cookies_' . $cookieSlug . '.txt';

            $date = isset($row['date']) ? $row['date'] : date('Y-m-d');
            $res = AzadaWholesaler::processDownload(
                (int)$wholesaler['id_wholesaler'],
                $number,
                $date,
                $netto,
                $status,
                $url,
                $cookieFile
            );

            if (isset($res['status']) && $res['status'] === 'success') {
                echo "OK.\n";
                $ok++;
                $filesDownloaded++;
            } else {
                $msg = isset($res['msg']) ? $res['msg'] : 'unknown error';
                echo "BŁĄD: {$msg}\n";
                $err++;
                $hasErrors = true;
            }
        }

        echo "\n";
    }
}

echo "==========================================================\n";
echo "KONIEC: CRON B2B\n";
echo "OK: {$ok} | ERR: {$err} | SKIP: {$skip}\n";
echo "==========================================================\n";

$output = ob_get_clean();
echo $output;

// Log do DB
$title = "CRON B2B (Zamówienia)";
if ($filesDownloaded > 0) {
    $title .= " (Pobrano: {$filesDownloaded})";
}
$severity = $hasErrors
    ? AzadaLogger::SEVERITY_ERROR
    : (($filesDownloaded > 0) ? AzadaLogger::SEVERITY_SUCCESS : AzadaLogger::SEVERITY_INFO);

AzadaLogger::addLog('CRON_B2B', $title, $output, $severity);
