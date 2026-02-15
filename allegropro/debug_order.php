<?php
/**
 * SKRYPT DEBUGUJĄCY: Uruchamiany z głównego katalogu (root)
 * Lokalizacja: /public_html/debug_order.php
 */

// 1. Inicjalizacja środowiska - ścieżki są teraz w tym samym folderze lub podfolderach
require_once('./config/config.inc.php');
require_once('./init.php');

// 2. Obsługa autoloadera modułu (plik src jest nadal w modules)
spl_autoload_register(function ($className) {
    if (strpos($className, 'AllegroPro\\') === 0) {
        $path = _PS_MODULE_DIR_ . 'allegropro/src/' . str_replace('\\', '/', substr($className, 11)) . '.php';
        if (file_exists($path)) {
            require_once($path);
        }
    }
});

use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Repository\AccountRepository;

// Parametry testu
$checkoutFormId = '23187951-06c0-11f1-affc-474203c8f157';
$accountId = 2;
$commandId = 'QUxMRUdSTzpBRDBYRDU4UzU0V0pWV0o4OA==';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Diagnostyka Allegro Pro - Uruchomienie z Roota</h2>";

try {
    $accRepo = new AccountRepository();
    $account = $accRepo->get($accountId);
    $api = new AllegroApiClient(new HttpClient(), $accRepo);
    
    echo "<b>Konto:</b> " . htmlspecialchars($account['username'] ?? 'Nie znaleziono') . "<br><hr>";

    // KROK 1: Sprawdzenie przesyłek
    echo "<h3>1. GET /order/checkout-forms/{id}/shipments</h3>";
    $res1 = $api->get($account, "/order/checkout-forms/$checkoutFormId/shipments");
    echo "<pre style='background:#eee; padding:10px;'>" . json_encode($res1['json'], JSON_PRETTY_PRINT) . "</pre>";

    // KROK 2: Sprawdzenie komendy
    echo "<h3>2. GET /shipment-management/shipments/create-commands/...</h3>";
    $res2 = $api->get($account, "/shipment-management/shipments/create-commands/" . rawurlencode($commandId));
    echo "<pre style='background:#eee; padding:10px;'>" . json_encode($res2['json'], JSON_PRETTY_PRINT) . "</pre>";

    $uuid = $res2['json']['shipmentId'] ?? null;

    if ($uuid) {
        echo "<h3>3. Próba pobrania PDF dla UUID: $uuid</h3>";
        $payload = [
            'shipmentIds' => [$uuid],
            'pageSize' => 'A6',
            'labelFormat' => 'PDF'
        ];

        // Kluczowe: Nagłówek application/pdf rozwiązuje błąd 406
        $resPdf = $api->postBinary($account, '/shipment-management/label', $payload, 'application/pdf');
        
        echo "Kod HTTP: " . $resPdf['code'] . "<br>";
        if ($resPdf['ok']) {
            echo "<b style='color:green'>SUKCES! Odebrano dane PDF.</b>";
        } else {
            echo "<b style='color:red'>BŁĄD Allegro:</b> " . htmlspecialchars($resPdf['raw']);
        }
    }

} catch (Exception $e) {
    echo "Błąd: " . $e->getMessage();
}