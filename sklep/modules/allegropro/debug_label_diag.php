<?php
/**
 * AllegroPro — debug_label_diag.php (ADMIN folder runner)
 *
 * Umieść ten plik w katalogu admina PrestaShop (tym losowym, np. /tfue58uqcdvmmh0v/):
 *   /<ADMIN_DIR>/debug_label_diag.php
 *
 * Uruchom:
 *   https://TWOJA-DOMENA/<ADMIN_DIR>/debug_label_diag.php?accountId=2&checkoutFormId=...
 *
 * Dlaczego tak?
 * - Często cookie sesji BO ma ścieżkę ograniczoną do katalogu admina,
 *   więc wywołania spod /modules/* nie dostają cookie BO i nie widzą zalogowanego pracownika.
 *
 * Po diagnostyce usuń plik z serwera.
 */

@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/config.inc.php';
require_once __DIR__ . '/../init.php';

header('Content-Type: text/html; charset=utf-8');

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function is_uuid($v) { return is_string($v) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $v); }

function try_base64_decode($v)
{
    if (!is_string($v)) return null;
    $v = trim($v);
    if ($v === '') return null;
    if (preg_match('/[^A-Za-z0-9\+\/\=]/', $v)) return null;
    $decoded = base64_decode($v, true);
    if ($decoded === false) return null;
    $decoded = trim($decoded);
    if ($decoded === '') return null;
    if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $decoded)) return null;
    return $decoded;
}

echo '<style>
body{font-family:Arial,Helvetica,sans-serif;font-size:14px}
pre{background:#f6f6f6;padding:10px;border:1px solid #ddd;overflow:auto}
code{background:#f2f2f2;padding:2px 4px}
.ok{color:#0a0}.bad{color:#b00}.box{border:1px solid #ddd;padding:10px;margin:10px 0}
</style>';

echo '<h2>AllegroPro — debug_label_diag.php (ADMIN folder)</h2>';

$ctx = Context::getContext();
$cookieNames = array_keys($_COOKIE);

echo '<div class="box">';
echo '<b>Host:</b> ' . h($_SERVER['HTTP_HOST'] ?? '') . '<br>';
echo '<b>URI:</b> ' . h($_SERVER['REQUEST_URI'] ?? '') . '<br>';
echo '<b>Cookie names:</b> ' . h(implode(', ', $cookieNames)) . '<br>';
echo '</div>';

// Spróbuj wykryć id_employee z dowolnego cookie
$employeeId = 0;
$employeeSource = '';

if (!empty($ctx->employee) && (int)$ctx->employee->id > 0) {
    $employeeId = (int)$ctx->employee->id;
    $employeeSource = 'Context::employee';
} elseif (!empty($ctx->cookie) && isset($ctx->cookie->id_employee) && (int)$ctx->cookie->id_employee > 0) {
    $employeeId = (int)$ctx->cookie->id_employee;
    $employeeSource = 'Context::cookie';
} else {
    foreach ($cookieNames as $n) {
        try {
            $c = new Cookie($n);
            if (isset($c->id_employee) && (int)$c->id_employee > 0) {
                $employeeId = (int)$c->id_employee;
                $employeeSource = 'Cookie(' . $n . ')';
                $ctx->cookie = $c;
                break;
            }
        } catch (Exception $e) {
            // ignore
        }
    }
}

if ($employeeId <= 0) {
    echo '<p class="bad"><b>Brak sesji BO (nie wykryto id_employee w żadnym cookie).</b></p>';
    echo '<div class="box">';
    echo 'Jeśli ten plik jest uruchamiany z katalogu admina i nadal brak sesji, to prawdopodobnie jesteś zalogowany na innym hoście (www vs bez www) lub BO działa w innym profilu przeglądarki.<br>';
    echo '</div>';
    exit;
}

$employee = new Employee((int)$employeeId);
if (!Validate::isLoadedObject($employee)) {
    echo '<p class="bad"><b>id_employee=' . h($employeeId) . '</b>, ale nie udało się załadować obiektu Employee.</p>';
    exit;
}
$ctx->employee = $employee;

echo '<div class="box">';
echo '<b class="ok">OK:</b> sesja BO wykryta. <b>employeeId:</b> ' . h($employeeId) . ' (<b>źródło:</b> ' . h($employeeSource) . ')<br>';
echo '<b>Employee:</b> ' . h($employee->firstname . ' ' . $employee->lastname) . ' / ' . h($employee->email) . '<br>';
echo '</div>';

// Autoloader modułu
spl_autoload_register(function ($className) {
    if (strpos($className, 'AllegroPro\\') === 0) {
        $path = _PS_MODULE_DIR_ . 'allegropro/src/' . str_replace('\\', '/', substr($className, 11)) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Repository\AccountRepository;

$accountId = (int)Tools::getValue('accountId', 2);
$checkoutFormId = (string)Tools::getValue('checkoutFormId', '');
$givenShipmentId = (string)Tools::getValue('shipmentId', '');
$pageSize = (string)Tools::getValue('pageSize', 'A6');
$labelFormat = (string)Tools::getValue('labelFormat', 'PDF');

echo '<div class="box">';
echo '<b>accountId:</b> ' . h($accountId) . '<br>';
echo '<b>checkoutFormId:</b> ' . h($checkoutFormId) . '<br>';
echo '<b>shipmentId (opcjonalnie):</b> ' . h($givenShipmentId) . '<br>';
echo '<b>pageSize:</b> ' . h($pageSize) . ', <b>labelFormat:</b> ' . h($labelFormat) . '<br>';
echo '</div>';

if ($checkoutFormId === '') {
    echo '<p class="bad"><b>Brak checkoutFormId.</b> Dopisz parametr w URL: <code>?accountId=2&checkoutFormId=...</code></p>';
    exit;
}

try {
    $accRepo = new AccountRepository();
    $account = $accRepo->get($accountId);
    if (!$account) {
        echo '<p class="bad"><b>Nie znaleziono konta w bazie.</b> accountId=' . h($accountId) . '</p>';
        exit;
    }

    $api = new AllegroApiClient(new HttpClient(), $accRepo);

    echo '<div class="box"><b>Konto:</b> ' . h($account['username'] ?? ('ID=' . $accountId)) . '</div>';

    echo '<h3>1) GET /order/checkout-forms/{id}</h3>';
    $cf = $api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId));
    echo '<p>HTTP: <b>' . h($cf['code']) . '</b>, ok=' . (!empty($cf['ok']) ? '<b class="ok">1</b>' : '<b class="bad">0</b>') . '</p>';
    echo '<pre>' . h(json_encode($cf['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';

    echo '<h3>2) GET /order/checkout-forms/{id}/shipments</h3>';
    $ship = $api->get($account, '/order/checkout-forms/' . rawurlencode($checkoutFormId) . '/shipments');
    echo '<p>HTTP: <b>' . h($ship['code']) . '</b>, ok=' . (!empty($ship['ok']) ? '<b class="ok">1</b>' : '<b class="bad">0</b>') . '</p>';
    echo '<pre>' . h(json_encode($ship['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';

    $rows = [];
    if (is_array($ship['json'])) {
        if (!empty($ship['json']['shipments']) && is_array($ship['json']['shipments'])) {
            $rows = $ship['json']['shipments'];
        } elseif (!empty($ship['json']['items']) && is_array($ship['json']['items'])) {
            $rows = $ship['json']['items'];
        } elseif (isset($ship['json'][0]) && is_array($ship['json'][0])) {
            $rows = $ship['json'];
        }
    }

    echo '<h3>3) Kandydaci identyfikatorów</h3>';
    $candidates = [];
    if ($givenShipmentId !== '') $candidates[] = $givenShipmentId;

    foreach ($rows as $r) {
        if (!is_array($r)) continue;
        if (!empty($r['id'])) $candidates[] = (string)$r['id'];
        if (!empty($r['waybill'])) {
            $candidates[] = 'ALLEGRO:' . (string)$r['waybill'];
            $candidates[] = (string)$r['waybill'];
        }
        if (!empty($r['trackingNumber'])) {
            $candidates[] = 'ALLEGRO:' . (string)$r['trackingNumber'];
            $candidates[] = (string)$r['trackingNumber'];
        }
    }

    $tmp = [];
    foreach ($candidates as $c) {
        $c = trim((string)$c);
        if ($c === '') continue;
        $tmp[$c] = true;
        $d = try_base64_decode($c);
        if ($d !== null) $tmp[$d] = true;
    }
    $candidates = array_keys($tmp);

    echo '<pre>' . h(implode("\n", $candidates)) . '</pre>';

    $uuidCandidates = [];
    foreach ($candidates as $c) if (is_uuid($c)) $uuidCandidates[] = $c;

    echo '<div class="box">';
    echo '<b>UUID shipmentId (kandydaci):</b> ' . (empty($uuidCandidates) ? '<span class="bad">BRAK</span>' : '<span class="ok">' . h(implode(', ', $uuidCandidates)) . '</span>');
    echo '</div>';

    if (!empty($uuidCandidates)) {
        echo '<h3>4) GET /shipment-management/shipments/{shipmentId}</h3>';
        foreach ($uuidCandidates as $uuid) {
            $d = $api->get($account, '/shipment-management/shipments/' . rawurlencode($uuid));
            echo '<p><b>' . h($uuid) . '</b> → HTTP ' . h($d['code']) . ', ok=' . (!empty($d['ok']) ? '<b class="ok">1</b>' : '<b class="bad">0</b>') . '</p>';
            echo '<pre>' . h(json_encode($d['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
        }

        echo '<h3>5) POST /shipment-management/label</h3>';
        foreach ($uuidCandidates as $uuid) {
            $payload = [
                'shipmentIds' => [$uuid],
                'pageSize' => $pageSize,
                'labelFormat' => $labelFormat,
                'cutLine' => false,
            ];
            $bin = $api->postBinary($account, '/shipment-management/label', $payload, 'application/octet-stream');
            echo '<p><b>' . h($uuid) . '</b> → HTTP ' . h($bin['code']) . ', ok=' . (!empty($bin['ok']) ? '<b class="ok">1</b>' : '<b class="bad">0</b>') . '</p>';
            if (!empty($bin['ok'])) {
                $raw = (string)$bin['raw'];
                echo '<p class="ok"><b>Odebrano dane binarne etykiety.</b> Pierwsze 16 bajtów (hex): <code>' . h(bin2hex(substr($raw, 0, 16))) . '</code></p>';
                echo '<p><i>Uwaga:</i> Ten skrypt tylko testuje pobranie. Jeśli chcesz zapisać PDF, dodamy tryb zapisu w kolejnym kroku.</p>';
            } else {
                echo '<pre>' . h(substr((string)$bin['raw'], 0, 2000)) . '</pre>';
            }
        }
    }

    echo '<h3>6) Wniosek</h3>';
    if (empty($uuidCandidates)) {
        echo '<p class="bad"><b>Brak shipmentId (UUID) w danych zamówienia</b> — widzisz tylko identyfikatory techniczne/waybill (np. <code>ALLEGRO:WAYBILL</code> lub Base64).<br>';
        echo 'Publiczne API Allegro do etykiet (<code>/shipment-management/label</code>) wymaga <b>shipmentId (UUID)</b> z procesu create-commands (Wysyłam z Allegro). Jeśli przesyłka/etykieta była utworzona w SalesCenter, Allegro może nie udostępniać shipmentId w REST API zamówień.</p>';
    } else {
        echo '<p class="ok"><b>Znaleziono UUID shipmentId</b> — jeśli POST /shipment-management/label zwrócił 200, etykietę da się pobrać przez API.</p>';
    }

} catch (Exception $e) {
    echo '<p class="bad"><b>Wyjątek:</b> ' . h($e->getMessage()) . '</p>';
}
