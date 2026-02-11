<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Diagnostyka B2B Natura Med</h2>";

// DANE DO LOGOWANIA - WPISZ SWOJE
$login = 'zamowienia@bigbio.pl';
$password = 'Big123456';

$cookieFile = __DIR__ . '/debug_cookie_naturamed.txt';
if (file_exists($cookieFile)) {
    @unlink($cookieFile);
}

function debugRequest($url, $postData = null, $headers = [], $cookieFile) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/115.0.0.0 Safari/537.36');
    
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($postData) ? http_build_query($postData) : $postData);
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $body = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    return ['body' => $body, 'info' => $info, 'error' => $error];
}

// 1. Inicjalizacja formularza logowania (pobranie ukrytych tokenów)
echo "<b>1. GET /logowanie</b><br>";
$res1 = debugRequest('https://b2b.natura-med.pl/logowanie', null, [], $cookieFile);
echo "HTTP Code: " . $res1['info']['http_code'] . "<br><br>";

// Wyszukujemy ukryte pola (CSRF token itp.)
$hiddenFields = [];
if (preg_match_all('/<input[^>]+type="hidden"[^>]+name="([^"]+)"[^>]+value="([^"]*)"/i', $res1['body'], $matches)) {
    foreach ($matches[1] as $index => $name) {
        $hiddenFields[$name] = $matches[2][$index];
    }
}

// 2. Logowanie
echo "<b>2. POST /logowanie</b><br>";
$postData = array_merge($hiddenFields, [
    'Uzytkownik' => $login,
    'Haslo' => $password,
    'login' => $login,
    'password' => $password
]);
$res2 = debugRequest('https://b2b.natura-med.pl/logowanie', $postData, [], $cookieFile);
echo "HTTP Code: " . $res2['info']['http_code'] . "<br><br>";

// 3. Sprawdzenie, czy logowanie się udało
echo "<b>3. GET /faktury</b><br>";
$res3 = debugRequest('https://b2b.natura-med.pl/faktury', null, [], $cookieFile);
echo "HTTP Code: " . $res3['info']['http_code'] . "<br>";
$isLoggedIn = (strpos($res3['body'], 'Wyloguj') !== false || strpos($res3['body'], 'logout') !== false || strpos($res3['body'], 'Wylogowanie') !== false);
echo "Czy poprawnie zalogowano? " . ($isLoggedIn ? '<span style="color:green;font-weight:bold;">TAK</span>' : '<span style="color:red;font-weight:bold;">NIE</span>') . "<br><br>";

file_put_contents(__DIR__ . '/natura_01_faktury_baza.html', $res3['body']);

// 4. Pobieranie danych faktur (symulacja tak, jak to robi moduł)
echo "<b>4. Próba pobrania AJAX (Wszystkie faktury)</b><br>";
$ajaxHeaders = [
    'X-Requested-With: XMLHttpRequest',
    'Referer: https://b2b.natura-med.pl/faktury'
];
$dateFrom = date('Y-m-d\T00:00:00', strtotime("-30 days"));
$dateTo = date('Y-m-d\T23:59:59');

$queryAll = http_build_query([
    'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'modeType' => 10, 'orderColumn' => 'CreationDate', 'isDescendingOrder' => 'true', 'page' => 1
]);
$res4 = debugRequest('https://b2b.natura-med.pl/faktury?' . $queryAll, null, $ajaxHeaders, $cookieFile);
echo "HTTP Code: " . $res4['info']['http_code'] . " | Rozmiar: " . strlen($res4['body']) . " bajtów<br>";
file_put_contents(__DIR__ . '/natura_02_wszystkie_faktury.txt', $res4['body']);

// 5. Pobieranie nieopłaconych
echo "<br><b>5. Próba pobrania AJAX (Do zapłaty)</b><br>";
$queryUnpaid = http_build_query([
    'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'modeType' => 0, 'orderColumn' => 'CreationDate', 'isDescendingOrder' => 'true', 'page' => 1
]);
$res5 = debugRequest('https://b2b.natura-med.pl/faktury?' . $queryUnpaid, null, $ajaxHeaders, $cookieFile);
echo "HTTP Code: " . $res5['info']['http_code'] . " | Rozmiar: " . strlen($res5['body']) . " bajtów<br>";
file_put_contents(__DIR__ . '/natura_03_nieoplacone_faktury.txt', $res5['body']);

echo "<h3>Wykonano!</h3>";
?>