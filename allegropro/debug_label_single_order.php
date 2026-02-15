<?php
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/allegropro.php';

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\DeliveryServiceRepository;
use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\ShipmentRepository;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\LabelConfig;
use AllegroPro\Service\LabelStorage;
use AllegroPro\Service\ShipmentManager;

header('Content-Type: text/plain; charset=utf-8');

if (Tools::getValue('key') !== 'BIGBIO_DEBUG') {
    http_response_code(403);
    echo "Brak dostępu.\n";
    exit;
}

$checkoutFormId = trim((string)Tools::getValue('checkout_form_id', '23187951-06c0-11f1-affc-474203c8f157'));
$requestedShipmentId = trim((string)Tools::getValue('shipment_id', ''));
$accountId = (int)Tools::getValue('account_id', 2);

$accounts = new AccountRepository();
$shipments = new ShipmentRepository();
$orderRepo = new OrderRepository();
$account = $accounts->get($accountId);

if (!is_array($account)) {
    echo "[ERR] Nie znaleziono konta account_id={$accountId}.\n";
    exit;
}

$manager = new ShipmentManager(
    new AllegroApiClient(new HttpClient(), $accounts),
    new LabelConfig(),
    new LabelStorage(),
    $orderRepo,
    new DeliveryServiceRepository(),
    $shipments
);

echo "=== DEBUG LABEL SINGLE ORDER ===\n";
echo 'time=' . date('Y-m-d H:i:s') . "\n";
echo 'account_id=' . (int)$account['id_allegropro_account'] . "\n";
echo 'checkout_form_id=' . $checkoutFormId . "\n";
echo 'requested_shipment_id=' . ($requestedShipmentId !== '' ? $requestedShipmentId : '[auto]') . "\n\n";

$localRows = $shipments->findAllByOrderForAccount((int)$account['id_allegropro_account'], $checkoutFormId);
echo "--- LOCAL SHIPMENTS (before sync) ---\n";
if (empty($localRows)) {
    echo "(brak)\n";
} else {
    foreach ($localRows as $idx => $row) {
        echo sprintf(
            "#%d id=%s shipment_id=%s tracking=%s status=%s created=%s updated=%s\n",
            $idx + 1,
            (string)($row['id_allegropro_shipment'] ?? '-'),
            (string)($row['shipment_id'] ?? '-'),
            (string)($row['tracking_number'] ?? '-'),
            (string)($row['status'] ?? '-'),
            (string)($row['created_at'] ?? '-'),
            (string)($row['updated_at'] ?? '-')
        );
    }
}

echo "\n--- STEP 1: syncOrderShipments(force=1, debug=1) ---\n";
$sync = $manager->syncOrderShipments($account, $checkoutFormId, 0, true, true);
echo 'sync.ok=' . (!empty($sync['ok']) ? '1' : '0') . "\n";
echo 'sync.synced=' . (int)($sync['synced'] ?? 0) . "\n";
echo 'sync.duplicates_removed=' . (int)($sync['duplicates_removed'] ?? 0) . "\n";
if (!empty($sync['debug_lines']) && is_array($sync['debug_lines'])) {
    foreach ($sync['debug_lines'] as $line) {
        echo $line . "\n";
    }
}

$localRowsAfter = $shipments->findAllByOrderForAccount((int)$account['id_allegropro_account'], $checkoutFormId);
echo "\n--- LOCAL SHIPMENTS (after sync) ---\n";
if (empty($localRowsAfter)) {
    echo "(brak)\n";
} else {
    foreach ($localRowsAfter as $idx => $row) {
        echo sprintf(
            "#%d id=%s shipment_id=%s tracking=%s status=%s created=%s updated=%s\n",
            $idx + 1,
            (string)($row['id_allegropro_shipment'] ?? '-'),
            (string)($row['shipment_id'] ?? '-'),
            (string)($row['tracking_number'] ?? '-'),
            (string)($row['status'] ?? '-'),
            (string)($row['created_at'] ?? '-'),
            (string)($row['updated_at'] ?? '-')
        );
    }
}

$shipmentForDownload = $requestedShipmentId;
if ($shipmentForDownload === '') {
    $shipmentForDownload = isset($localRowsAfter[0]['shipment_id']) ? trim((string)$localRowsAfter[0]['shipment_id']) : '';
}

if ($shipmentForDownload === '') {
    echo "\n[ERR] Brak shipment_id do testu downloadLabel.\n";
    exit;
}

echo "\n--- STEP 2: downloadLabel(debug) ---\n";
echo 'download.input_shipment_id=' . $shipmentForDownload . "\n";
$download = $manager->downloadLabel($account, $checkoutFormId, $shipmentForDownload);
echo 'download.ok=' . (!empty($download['ok']) ? '1' : '0') . "\n";
echo 'download.http_code=' . (int)($download['http_code'] ?? 0) . "\n";
echo 'download.message=' . (string)($download['message'] ?? '-') . "\n";
echo 'download.path=' . (string)($download['path'] ?? '-') . "\n";

if (!empty($download['debug_lines']) && is_array($download['debug_lines'])) {
    foreach ($download['debug_lines'] as $line) {
        echo $line . "\n";
    }
}

echo "\n=== END ===\n";
