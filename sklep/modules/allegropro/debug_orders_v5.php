<?php
/**
 * INSPEKTOR ZAMÓWIEŃ V5 - FINAL FIX
 * 1. Obsługuje strukturę 'productSet' (tam gdzie naprawdę jest EAN).
 * 2. Ignoruje ID 11323 (Stan / Nowy).
 * 3. Wyświetla poprawny EAN.
 */

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/allegropro.php';

if (Tools::getValue('key') !== 'BIGBIO_DEBUG') { die('Brak dostępu.'); }

$module = new AllegroPro();
use AllegroPro\Repository\AccountRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;

$repo = new AccountRepository();
$account = null;
foreach ($repo->all() as $a) { if ($a['active']) { $account = $a; break; } }
if (!$account) die('<h1>Brak aktywnego konta Allegro.</h1>');

$http = new HttpClient();
$api = new AllegroApiClient($http, $repo);

// Pobieramy zamówienia
$response = $api->get($account, '/order/checkout-forms', ['limit' => 5, 'sort' => '-updatedAt']);
$orders = $response['json']['checkoutForms'] ?? [];

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Inspektor V5 (Fix ProduktSet)</title>
    <style>
        body { font-family: sans-serif; background: #eef2f5; padding: 20px; font-size:13px; }
        .card { background: #fff; border: 1px solid #ccd; padding: 20px; margin-bottom: 25px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        h2 { margin-top: 0; color: #0056b3; font-size: 16px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top:5px; }
        th, td { border: 1px solid #dde; padding: 8px; text-align: left; vertical-align: middle; }
        th { background: #f0f4f8; font-weight:bold; color:#444; }
        .ean-box { font-weight:bold; color:#fff; background:#28a745; padding:5px 10px; border-radius:4px; display:inline-block; font-size:14px; letter-spacing:1px; }
        .ean-source { font-size:10px; color:#666; display:block; margin-top:3px; }
    </style>
</head>
<body>

<h1>Inspektor V5 (Ostateczna weryfikacja)</h1>

<?php foreach ($orders as $o): ?>
    <div class="card">
        <h2>Zamówienie: <?php echo $o['id']; ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Produkt</th>
                    <th style="width:250px; background:#e8f5e9;">EAN (Wymuszony)</th>
                    <th>Cena</th>
                    <th>Ilość</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($o['lineItems'] as $item): ?>
                <?php 
                    $offerId = $item['offer']['id'];
                    $prodResp = $api->get($account, '/sale/product-offers/' . $offerId);
                    
                    $eanDisplay = '<span style="color:orange">...</span>';
                    
                    if ($prodResp['ok']) {
                        $d = $prodResp['json'];
                        $found = null;
                        $src = '';

                        // --- 1. SPRAWDZANIE GŁĘBOKIEJ STRUKTURY (ProductSet) ---
                        // To tutaj był ukryty Twój EAN z bajgli!
                        if (isset($d['productSet'][0]['product']['parameters'])) {
                            foreach ($d['productSet'][0]['product']['parameters'] as $p) {
                                if (stripos($p['name'], 'EAN') !== false || stripos($p['name'], 'GTIN') !== false) {
                                    $val = implode('', $p['values'] ?? []);
                                    if (is_numeric($val)) { 
                                        $found = $val; $src = 'ProductSet > Parametry'; break; 
                                    }
                                }
                            }
                        }

                        // --- 2. SPRAWDZANIE ZWYKŁYCH PARAMETRÓW (Fallback) ---
                        if (!$found && isset($d['parameters'])) {
                            foreach ($d['parameters'] as $p) {
                                // WAŻNE: Ignorujemy ID 11323 (Stan)
                                if ($p['id'] == '11323') continue; 

                                if (stripos($p['name'], 'EAN') !== false || stripos($p['name'], 'GTIN') !== false) {
                                    $val = implode('', $p['values'] ?? []);
                                    // Dodatkowe zabezpieczenie: EAN musi być liczbą
                                    if (is_numeric($val) && strlen($val) > 5) {
                                        $found = $val; $src = 'Oferta > Parametry'; break;
                                    }
                                }
                            }
                        }

                        // WYNIK
                        if ($found) {
                            $eanDisplay = '<div class="ean-box">' . $found . '</div><span class="ean-source">(' . $src . ')</span>';
                        } else {
                            $eanDisplay = '<span style="color:#aaa">BRAK w Allegro</span>';
                        }

                    } else {
                        $eanDisplay = '<span style="color:red">BŁĄD API</span>';
                    }
                ?>
                <tr>
                    <td>
                        <strong><?php echo $item['offer']['name']; ?></strong><br>
                        <small style="color:#888"><?php echo $offerId; ?></small>
                    </td>
                    <td style="background:#f1f8e9; border-left:2px solid #c8e6c9;">
                        <?php echo $eanDisplay; ?>
                    </td>
                    <td><?php echo $item['price']['amount']; ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

</body>
</html>