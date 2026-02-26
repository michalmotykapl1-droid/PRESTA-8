<?php

/**
 * CRON: Faktury Zakupu (FV) - pobieranie CSV do bazy (systemowy format).
 *
 * Endpoint uruchamiany zewnętrznie (URL z tokenem).
 *
 * Logowanie:
 * - zapisuje pełny output do tabeli azada_wholesaler_pro_logs (source=CRON_FV)
 * - severity zależne od błędów / ilości pobranych plików
 */

require_once(dirname(__FILE__) . '/cron_init.php');

// Token + lock + środowisko
AzadaCronRunner::init('invoices');

require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaLogger.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaDbRepository.php');
require_once(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/services/AzadaInvoiceComparator.php');

// Integracje
foreach (glob(_PS_MODULE_DIR_ . 'azada_wholesaler_pro/classes/integrations/*.php') as $f) {
    require_once($f);
}

$hasErrors = false;
$filesDownloaded = 0;
$ok = 0;
$err = 0;
$skip = 0;

ob_start();

echo "==========================================================\n";
echo "   CRON FV (CSV -> BAZA) - START: " . date('Y-m-d H:i:s') . "\n";
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

if ($integrations) {
    foreach ($integrations as $wholesaler) {
        echo "----------------------------------------------------------\n";
        echo "HURTOWNIA: [" . $wholesaler['name'] . "]\n";

        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', ucwords($wholesaler['name']));
        $className = 'Azada' . $cleanName . 'B2B';
        $worker = null;
        if (class_exists($className)) {
            $worker = new $className();
        }

        if (!$worker || !method_exists($worker, 'scrapeInvoices')) {
            echo "[SKIP] Brak klasy integracji lub metody scrapeInvoices().\n\n";
            $skip++;
            continue;
        }

        echo "[LOGOWANIE] Pobieranie listy... ";
        $pass = base64_decode($wholesaler['b2b_password']);
        $list = $worker->scrapeInvoices($wholesaler['b2b_login'], $pass);

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
                    $first = $row['options'][0];
                    $url = isset($first['url']) ? $first['url'] : '';
                }
            }

            if (!$url) {
                echo " > {$number} [SKIP - BRAK PLIKU]\n";
                $skip++;
                continue;
            }

            $dbInfo = AzadaDbRepository::getInvoiceByNumber($number);

            $netto = isset($row['netto']) ? $row['netto'] : 0;
            $isPaid = isset($row['is_paid']) ? $row['is_paid'] : 0;

            $action = AzadaInvoiceComparator::compare($number, $netto, $isPaid, $dbInfo);
            if ($action === AzadaInvoiceComparator::ACTION_NONE) {
                echo " > {$number} [OK]\n";
                $skip++;
                continue;
            }

            echo " > {$number} [POBIERANIE DO BAZY]... ";

            $cookieSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $wholesaler['name']));
            $cookieFile = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/cookies_' . $cookieSlug . '.txt';

            $date = isset($row['date']) ? $row['date'] : date('Y-m-d');
            $deadline = isset($row['deadline']) ? $row['deadline'] : '';

            $res = AzadaWholesaler::processInvoiceDownload(
                (int)$wholesaler['id_wholesaler'],
                $number,
                $date,
                $netto,
                $deadline,
                $isPaid,
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
} else {
    echo "Brak aktywnych hurtowni B2B.\n";
    $skip++;
}

echo "==========================================================\n";
echo "KONIEC: CRON FV\n";
echo "OK: {$ok} | ERR: {$err} | SKIP: {$skip}\n";
echo "==========================================================\n";

$output = ob_get_clean();
echo $output;

$title = "CRON FV";
if ($filesDownloaded > 0) {
    $title .= " (Pobrano: {$filesDownloaded})";
}
$severity = $hasErrors
    ? AzadaLogger::SEVERITY_ERROR
    : (($filesDownloaded > 0) ? AzadaLogger::SEVERITY_SUCCESS : AzadaLogger::SEVERITY_INFO);

AzadaLogger::addLog('CRON_FV', $title, $output, $severity);
