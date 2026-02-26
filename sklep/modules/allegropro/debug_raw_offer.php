<?php
/**
 * SKRYPT "RAW DUMP" - NAPRAWIONY (Z AUTOLOADEREM)
 * Wyświetla surowe dane oferty bez domysłów.
 */

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/allegropro.php';

if (Tools::getValue('key') !== 'BIGBIO_DEBUG') { die('Brak dostępu.'); }

// --- KLUCZOWA POPRAWKA: Uruchomienie modułu (rejestracja klas) ---
$module = new AllegroPro();
// -----------------------------------------------------------------

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;

$repo = new AccountRepository();
$account = null;

// Szukamy aktywnego konta
foreach ($repo->all() as $a) { 
    if ($a['active']) { $account = $a; break; } 
}

if (!$account) {
    die('<h2 style="color:red">Błąd: Nie znaleziono aktywnego konta w module.</h2>');
}

$http = new HttpClient();
$api = new AllegroApiClient($http, $repo);

echo '<body style="background:#222; color:#0f0; font-family:monospace; padding:20px;">';
echo '<h1>PEŁNY ZRZUT OFERTY (RAW DATA)</h1>';
echo '<p style="color:#fff">Wciśnij <strong>CTRL+F</strong> i wpisz szukany numer EAN (np. z pudełka), aby zobaczyć w jakim polu jest ukryty.</p>';

// 1. Pobieramy jakiekolwiek zamówienie, żeby zdobyć ID oferty
$respOrders = $api->get($account, '/order/checkout-forms', ['limit' => 1]);

if (!$respOrders['ok']) {
    die('<h2 style="color:red">Błąd pobierania zamówień: ' . $respOrders['code'] . '</h2><pre>' . htmlspecialchars($respOrders['raw']) . '</pre>');
}

$orders = $respOrders['json']['checkoutForms'] ?? [];
if (empty($orders)) {
    die('<h2 style="color:orange">Lista zamówień jest pusta. Nie mam ID oferty do sprawdzenia. Wystaw testowe zamówienie lub poczekaj na sprzedaż.</h2>');
}

// Bierzemy pierwszy produkt z pierwszego zamówienia
$firstItem = $orders[0]['lineItems'][0];
$offerId = $firstItem['offer']['id'];
$offerName = $firstItem['offer']['name'];

echo '<h3 style="color:#fff; border-bottom:1px solid #555; padding-bottom:10px;">Analizowany produkt: ' . $offerName . ' (ID: '.$offerId.')</h3>';

// 2. Pobieramy PEŁNE dane tej oferty
// Allegro przeniosło oferty do /sale/product-offers.
$offerResp = $api->get($account, '/sale/product-offers/' . $offerId);

if (!$offerResp['ok']) {
    echo '<h2 style="color:red">Błąd pobierania oferty (Kod ' . $offerResp['code'] . ')</h2>';
    echo '<p style="color:#fff">Jeśli widzisz 403, to znaczy że token nadal nie ma uprawnień.</p>';
} else {
    // 3. Wyświetlamy BEZ ŻADNEJ OBRÓBKI
    echo '<pre>';
    print_r($offerResp['json']);
    echo '</pre>';
}