<?php
/**
 * RENTGEN ZAMÓWIENIA ALLEGRO (Poprawka)
 */

// 1. Ładowanie PrestaShop (Zależy gdzie wgrałeś plik - zakładamy folder modułu)
require(dirname(__FILE__) . '/../../config/config.inc.php');

// 2. Ładowanie klas modułu
require_once _PS_MODULE_DIR_ . 'allegropro/src/Service/HttpClient.php';
require_once _PS_MODULE_DIR_ . 'allegropro/src/Service/AllegroApiClient.php';
require_once _PS_MODULE_DIR_ . 'allegropro/src/Service/AllegroEndpoints.php';
require_once _PS_MODULE_DIR_ . 'allegropro/src/Service/Config.php';
require_once _PS_MODULE_DIR_ . 'allegropro/src/Repository/AccountRepository.php';

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;

// 3. Konfiguracja
$checkoutFormId = '38091510-f46c-11f0-b15c-2b7dec1edf95'; // Twoje ID

echo '<div style="font-family:monospace;">';
echo '<h1>Rentgen Zamówienia: ' . $checkoutFormId . '</h1>';

// 4. Pobieranie konta
$repo = new AccountRepository();
$accounts = $repo->all();
if (empty($accounts)) die('Brak kont w module.');
$account = $accounts[0]; 

$http = new HttpClient();
$api = new AllegroApiClient($http, $repo);

// 5. Pobieranie danych
$path = '/order/checkout-forms/' . $checkoutFormId;
$resp = $api->get($account, $path);

if ($resp['ok']) {
    echo '<h3 style="color:green">SUKCES - Otrzymano dane:</h3>';
    echo '<pre style="background:#f4f4f4; padding:15px; border:1px solid #ccc;">';
    
    // Używamy gotowego JSON z klienta API
    $data = $resp['json']; 
    print_r($data);
    
    echo '</pre>';
} else {
    echo '<h3 style="color:red">BŁĄD API:</h3>';
    echo 'Kod HTTP: ' . $resp['code'] . '<br>';
    echo 'Treść błędu: ' . htmlspecialchars($resp['raw']);
}
echo '</div>';