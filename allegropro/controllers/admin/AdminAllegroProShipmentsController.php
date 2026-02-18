<?php
/**
 * ALLEGRO PRO - Back Office controller
 */

use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\DeliveryServiceRepository;
use AllegroPro\Repository\ShipmentRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\ShipmentsService;
use AllegroPro\Service\ShipmentManager;
use AllegroPro\Service\LabelConfig;
use AllegroPro\Service\LabelStorage;

class AdminAllegroProShipmentsController extends ModuleAdminController
{
    private AccountRepository $accounts;
    private OrderRepository $orders;
    private DeliveryServiceRepository $delivery;
    private ShipmentRepository $shipments;

    public function __construct()
    {
        parent::__construct();
        $this->bootstrap = true;
        $this->accounts = new AccountRepository();
        $this->orders = new OrderRepository();
        $this->delivery = new DeliveryServiceRepository();
        $this->shipments = new ShipmentRepository();
    }

    public function initContent()
    {
        parent::initContent();

        if (isset($this->module) && method_exists($this->module, 'ensureTabs')) {
            $this->module->ensureTabs();
        }

        $this->handleActions();

        $accounts = $this->accounts->all();
        $defaultAccountId = $this->resolveDefaultAccountId($accounts);
        $selectedAccounts = $this->readSelectedAccounts($accounts, $defaultAccountId);

        $perPage = (int)Tools::getValue('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $pendingFilters = $this->readSectionFilters('pending_', $selectedAccounts, false);
        $labeledFilters = $this->readSectionFilters('labeled_', $selectedAccounts, true);

        $pendingPage = max(1, (int)Tools::getValue('pending_page', 1));
        $labeledPage = max(1, (int)Tools::getValue('labeled_page', 1));

        $pendingTotalRows = $this->orders->countShipmentListFiltered($selectedAccounts, $pendingFilters, false);
        $pendingTotalPages = max(1, (int)ceil($pendingTotalRows / $perPage));
        if ($pendingPage > $pendingTotalPages) {
            $pendingPage = $pendingTotalPages;
        }

        $labeledTotalRows = $this->orders->countShipmentListFiltered($selectedAccounts, $labeledFilters, true);
        $labeledTotalPages = max(1, (int)ceil($labeledTotalRows / $perPage));
        if ($labeledPage > $labeledTotalPages) {
            $labeledPage = $labeledTotalPages;
        }

        $pendingList = $this->orders->getShipmentListFiltered($selectedAccounts, $pendingFilters, $perPage, ($pendingPage - 1) * $perPage, false);
        $labeledList = $this->orders->getShipmentListFiltered($selectedAccounts, $labeledFilters, $perPage, ($labeledPage - 1) * $perPage, true);

        foreach ($pendingList as &$row) {
            $row['module_status_label'] = $this->mapModuleStatusLabel((string)($row['status'] ?? ''));
            $row['module_status_class'] = $this->mapModuleStatusClass((string)$row['module_status_label']);
            $shipmentUi = $this->mapShipmentStatus((string)($row['shipment_status'] ?? ''));
            $row['shipment_status_label'] = $shipmentUi['label'];
            $row['shipment_status_class'] = $shipmentUi['class'];
            $row['account_title'] = $this->formatAccountTitle($row);
        }
        unset($row);

        foreach ($labeledList as &$row) {
            $row['module_status_label'] = $this->mapModuleStatusLabel((string)($row['status'] ?? ''));
            $row['module_status_class'] = $this->mapModuleStatusClass((string)$row['module_status_label']);
            $shipmentUi = $this->mapShipmentStatus((string)($row['shipment_status'] ?? ''));
            $row['shipment_status_label'] = $shipmentUi['label'];
            $row['shipment_status_class'] = $shipmentUi['class'];
            $row['account_title'] = $this->formatAccountTitle($row);
        }
        unset($row);

        $statusGroups = $this->getModuleStatusGroups($this->orders->getDistinctStatusesForAccounts($selectedAccounts));
        $shipmentStatuses = $this->buildShipmentStatusOptions($this->orders->getDistinctShipmentStatusesForAccounts($selectedAccounts));

        $dsCount = 0;
        if (count($selectedAccounts) === 1) {
            $dsCount = $this->delivery->countForAccount((int)$selectedAccounts[0]);
        }

        $queryBase = $this->buildQueryBase($selectedAccounts, $perPage, $pendingFilters, $labeledFilters);

        $this->context->smarty->assign([
            'allegropro_accounts' => $accounts,
            'allegropro_selected_accounts' => $selectedAccounts,
            'allegropro_action_account' => (int)$defaultAccountId,
            'allegropro_pending_orders' => $pendingList,
            'allegropro_labeled_orders' => $labeledList,
            'allegropro_pending_filters' => $pendingFilters,
            'allegropro_labeled_filters' => $labeledFilters,
            'allegropro_per_page' => $perPage,
            'allegropro_status_options' => $statusGroups,
            'allegropro_shipment_status_options' => $shipmentStatuses,
            'allegropro_delivery_services_count' => $dsCount,
            'allegropro_query_base' => $queryBase,
            'allegropro_pending_pagination' => [
                'page' => $pendingPage,
                'per_page' => $perPage,
                'total_rows' => $pendingTotalRows,
                'total_pages' => $pendingTotalPages,
            ],
            'allegropro_labeled_pagination' => [
                'page' => $labeledPage,
                'per_page' => $perPage,
                'total_rows' => $labeledTotalRows,
                'total_pages' => $labeledTotalPages,
            ],
            'admin_link' => $this->context->link->getAdminLink('AdminAllegroProShipments'),
        ]);

        $this->setTemplate('shipments.tpl');
    }

    private function resolveDefaultAccountId(array $accounts): int
    {
        $selected = (int)Tools::getValue('id_allegropro_account');
        if ($selected > 0) {
            return $selected;
        }

        foreach ($accounts as $a) {
            if ((int)$a['is_default'] === 1) {
                return (int)$a['id_allegropro_account'];
            }
        }

        if (!empty($accounts)) {
            return (int)$accounts[0]['id_allegropro_account'];
        }

        return 0;
    }

    private function readSelectedAccounts(array $accounts, int $fallbackId): array
    {
        $raw = Tools::getValue('filter_accounts', []);
        if (!is_array($raw)) {
            $raw = [$raw];
        }

        $allowed = [];
        foreach ($accounts as $a) {
            $allowed[(int)$a['id_allegropro_account']] = true;
        }

        $selected = [];
        foreach ($raw as $id) {
            $id = (int)$id;
            if ($id > 0 && isset($allowed[$id])) {
                $selected[] = $id;
            }
        }

        $selected = array_values(array_unique($selected));
        if (empty($selected) && $fallbackId > 0) {
            $selected[] = $fallbackId;
        }

        return $selected;
    }

    private function readSectionFilters(string $prefix, array $selectedAccounts, bool $includeShipmentStatuses): array
    {
        $statusCodes = Tools::getValue($prefix . 'status_codes', []);
        if (!is_array($statusCodes)) {
            $statusCodes = [$statusCodes];
        }
        $statusCodes = array_values(array_filter(array_map('trim', array_map('strval', $statusCodes))));

        $statusGroups = $this->getModuleStatusGroups($this->orders->getDistinctStatusesForAccounts($selectedAccounts));
        $rawStatuses = [];
        foreach ($statusCodes as $code) {
            if (!isset($statusGroups[$code])) {
                continue;
            }
            foreach ($statusGroups[$code]['raw'] as $raw) {
                $rawStatuses[] = (string)$raw;
            }
        }

        $shipmentStatuses = [];
        if ($includeShipmentStatuses) {
            $shipmentStatuses = Tools::getValue($prefix . 'shipment_statuses', []);
            if (!is_array($shipmentStatuses)) {
                $shipmentStatuses = [$shipmentStatuses];
            }
            $shipmentStatuses = array_values(array_filter(array_map('trim', array_map('strval', $shipmentStatuses))));
        }

        $filters = [
            'query' => trim((string)Tools::getValue($prefix . 'query')),
            'date_from' => trim((string)Tools::getValue($prefix . 'date_from')),
            'date_to' => trim((string)Tools::getValue($prefix . 'date_to')),
            'statuses' => array_values(array_unique($rawStatuses)),
            'selected_status_codes' => $statusCodes,
            'shipment_statuses' => $shipmentStatuses,
        ];

        if ($filters['date_from'] !== '' && strtotime($filters['date_from']) === false) {
            $filters['date_from'] = '';
        }

        if ($filters['date_to'] !== '' && strtotime($filters['date_to']) === false) {
            $filters['date_to'] = '';
        }

        return $filters;
    }

    private function buildQueryBase(array $selectedAccounts, int $perPage, array $pendingFilters, array $labeledFilters): string
    {
        $params = [];
        foreach ($selectedAccounts as $id) {
            $params[] = 'filter_accounts[]=' . urlencode((string)(int)$id);
        }

        $params[] = 'per_page=' . (int)$perPage;

        $this->appendSectionParams($params, 'pending_', $pendingFilters, false);
        $this->appendSectionParams($params, 'labeled_', $labeledFilters, true);

        return implode('&', $params);
    }

    private function appendSectionParams(array &$params, string $prefix, array $filters, bool $includeShipmentStatuses): void
    {
        if ($filters['query'] !== '') {
            $params[] = $prefix . 'query=' . urlencode($filters['query']);
        }
        if ($filters['date_from'] !== '') {
            $params[] = $prefix . 'date_from=' . urlencode($filters['date_from']);
        }
        if ($filters['date_to'] !== '') {
            $params[] = $prefix . 'date_to=' . urlencode($filters['date_to']);
        }

        foreach ($filters['selected_status_codes'] as $code) {
            $params[] = $prefix . 'status_codes[]=' . urlencode((string)$code);
        }

        if ($includeShipmentStatuses) {
            foreach ($filters['shipment_statuses'] as $status) {
                $params[] = $prefix . 'shipment_statuses[]=' . urlencode((string)$status);
            }
        }
    }

    private function formatAccountTitle(array $row): string
    {
        $label = trim((string)($row['account_label'] ?? ''));
        $login = trim((string)($row['allegro_login'] ?? ''));

        if ($label !== '' && $login !== '') {
            return $label . ' (' . $login . ')';
        }

        if ($label !== '') {
            return $label;
        }

        if ($login !== '') {
            return $login;
        }

        return '#'.(int)($row['id_allegropro_account'] ?? 0);
    }

    private function mapModuleStatusLabel(string $status): string
    {
        $status = strtoupper(trim($status));

        if (in_array($status, ['READY_FOR_PROCESSING', 'BOUGHT'], true)) {
            return 'ALLEGRO PRO - OPŁACONE';
        }

        if ($status === 'FILLED_IN') {
            return 'ALLEGRO PRO - BRAK WPŁATY';
        }

        if ($status === 'CANCELLED') {
            return 'ALLEGRO PRO - ANULOWANE';
        }

        return 'ALLEGRO PRO - PRZETWARZANIE';
    }

    private function mapModuleStatusClass(string $label): string
    {
        if ($label === 'ALLEGRO PRO - OPŁACONE') {
            return 'success';
        }

        if ($label === 'ALLEGRO PRO - ANULOWANE') {
            return 'danger';
        }

        if ($label === 'ALLEGRO PRO - BRAK WPŁATY') {
            return 'default';
        }

        return 'warning';
    }

    private function getModuleStatusGroups(array $rawStatuses): array
    {
        $groups = [
            'PAID' => ['label' => 'ALLEGRO PRO - OPŁACONE', 'raw' => []],
            'NO_PAYMENT' => ['label' => 'ALLEGRO PRO - BRAK WPŁATY', 'raw' => []],
            'PROCESSING' => ['label' => 'ALLEGRO PRO - PRZETWARZANIE', 'raw' => []],
            'CANCELLED' => ['label' => 'ALLEGRO PRO - ANULOWANE', 'raw' => []],
        ];

        foreach ($rawStatuses as $raw) {
            $label = $this->mapModuleStatusLabel((string)$raw);
            if ($label === 'ALLEGRO PRO - OPŁACONE') {
                $groups['PAID']['raw'][] = (string)$raw;
            } elseif ($label === 'ALLEGRO PRO - BRAK WPŁATY') {
                $groups['NO_PAYMENT']['raw'][] = (string)$raw;
            } elseif ($label === 'ALLEGRO PRO - ANULOWANE') {
                $groups['CANCELLED']['raw'][] = (string)$raw;
            } else {
                $groups['PROCESSING']['raw'][] = (string)$raw;
            }
        }

        foreach ($groups as $key => $meta) {
            $groups[$key]['raw'] = array_values(array_unique($meta['raw']));
        }

        return $groups;
    }

    private function buildShipmentStatusOptions(array $rawStatuses): array
    {
        $out = [];
        foreach ($rawStatuses as $rawStatus) {
            $rawStatus = strtoupper(trim((string)$rawStatus));
            if ($rawStatus === '') {
                continue;
            }

            $mapped = $this->mapShipmentStatus($rawStatus);
            $label = (string)$mapped['label'];
            if ($label === '' || $label === '—') {
                $label = $rawStatus;
            }

            $out[] = [
                'value' => $rawStatus,
                'label' => $label,
            ];
        }

        return $out;
    }

    private function mapShipmentStatus(string $status): array
    {
        $status = strtoupper(trim($status));

        if ($status === 'CREATED' || $status === 'PENDING') {
            return ['label' => 'UTWORZONA', 'class' => 'success'];
        }

        if ($status === 'NEW') {
            return ['label' => 'W TOKU', 'class' => 'info'];
        }

        if ($status === 'IN_PROGRESS') {
            return ['label' => 'PRZETWARZANIE', 'class' => 'info'];
        }

        if ($status === 'SENT') {
            return ['label' => 'NADANA', 'class' => 'primary'];
        }

        if ($status === 'IN_TRANSIT' || $status === 'OUT_FOR_DELIVERY') {
            return ['label' => 'W DRODZE', 'class' => 'primary'];
        }

        if ($status === 'READY_FOR_PICKUP') {
            return ['label' => 'DO ODBIORU', 'class' => 'primary'];
        }

        if ($status === 'DELIVERED') {
            return ['label' => 'DORĘCZONA', 'class' => 'primary'];
        }

        if ($status === 'CANCELLED') {
            return ['label' => 'ANULOWANA', 'class' => 'default'];
        }

        if ($status === '') {
            return ['label' => '—', 'class' => 'default'];
        }

        return ['label' => $status, 'class' => 'warning'];
    }

    private function getShipmentManager(): ShipmentManager
    {
        $http = new HttpClient();
        $api = new AllegroApiClient($http, $this->accounts);

        return new ShipmentManager(
            $api,
            new LabelConfig(),
            new LabelStorage(),
            $this->orders,
            $this->delivery,
            $this->shipments
        );
    }

    private function handleActions()
    {
        if (Tools::getValue('action') === 'downloadLabel') {
            $this->handleDownloadLabel();
            return;
        }

        if (Tools::getValue('action') === 'downloadLabelCheck') {
            $this->handleDownloadLabelCheck();
            return;
        }

        if (Tools::isSubmit('allegropro_refresh_delivery_services')) {
            $id = (int)Tools::getValue('id_allegropro_account');
            $acc = $this->accounts->get($id);
            if (!$acc) {
                $this->errors[] = $this->l('Wybierz konto.');
                return;
            }
            if (empty($acc['access_token']) || empty($acc['refresh_token'])) {
                $this->errors[] = $this->l('Konto nie jest autoryzowane.');
                return;
            }

            $api = new AllegroApiClient(new HttpClient(), $this->accounts);
            $svc = new ShipmentsService($api, $this->delivery, $this->orders, $this->shipments);
            $resp = $svc->refreshDeliveryServices($acc);

            if ($resp['ok']) {
                $this->confirmations[] = $this->l('Zaktualizowano listę delivery services.');
            } else {
                $this->errors[] = $this->l('Nie udało się pobrać delivery services.') . ' HTTP ' . (int)$resp['code'] . ' ' . Tools::substr($resp['raw'], 0, 400);
            }
            return;
        }

        if (Tools::isSubmit('allegropro_create_shipment')) {
            $id = (int)Tools::getValue('id_allegropro_account');
            $checkoutFormId = (string)Tools::getValue('checkout_form_id');

            $acc = $this->accounts->get($id);
            if (!$acc) {
                $this->errors[] = $this->l('Wybierz konto.');
                return;
            }
            if (!$checkoutFormId) {
                $this->errors[] = $this->l('Brak checkoutFormId.');
                return;
            }
            if (empty($acc['access_token']) || empty($acc['refresh_token'])) {
                $this->errors[] = $this->l('Konto nie jest autoryzowane.');
                return;
            }

            $api = new AllegroApiClient(new HttpClient(), $this->accounts);
            $svc = new ShipmentsService($api, $this->delivery, $this->orders, $this->shipments);
            $resp = $svc->createShipmentCommand($acc, $checkoutFormId);

            if ($resp['ok']) {
                $this->confirmations[] = $this->l('Wysłano komendę utworzenia przesyłki.');
            } else {
                $msg = is_string($resp['raw']) ? $resp['raw'] : '';
                $this->errors[] = $this->l('Nie udało się utworzyć przesyłki.') . ' HTTP ' . (int)$resp['code'] . ' ' . Tools::substr($msg, 0, 400);
            }
            return;
        }

        if (Tools::isSubmit('allegropro_fix_custom_wza_uuid')) {
            $id = (int)Tools::getValue('id_allegropro_account');
            if ($id <= 0) {
                $this->errors[] = $this->l('Wybierz konto.');
                return;
            }

            if (!method_exists($this->shipments, 'backfillWzaUuidFromShipmentIdForCustom')) {
                $this->errors[] = $this->l('Brak funkcji naprawy w tej wersji modułu.');
                return;
            }

            $updated = (int)$this->shipments->backfillWzaUuidFromShipmentIdForCustom($id, null);
            $this->confirmations[] = $this->l('Zaktualizowano rekordy: ') . (int)$updated;
            return;
        }
    }

    private function handleDownloadLabelCheck()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)Tools::getValue('id_allegropro_account');
        $checkoutFormId = (string)Tools::getValue('checkout_form_id');
        $shipmentId = (string)Tools::getValue('shipment_id');

        $acc = $this->accounts->get($id);
        if (!$acc || $checkoutFormId === '') {
            echo json_encode([
                'ok' => false,
                'message' => 'Brak konta lub checkoutFormId.',
            ]);
            exit;
        }

        if ($shipmentId === '') {
            $row = $this->orders->getRaw($id, $checkoutFormId);
            if (!$row || empty($row['shipment_id'])) {
                echo json_encode([
                    'ok' => false,
                    'message' => 'Brak shipmentId dla tego zamówienia.',
                ]);
                exit;
            }
            $shipmentId = (string)$row['shipment_id'];
        }

        $manager = $this->getShipmentManager();
        $res = $manager->downloadLabel($acc, $checkoutFormId, $shipmentId);

        if (empty($res['ok']) || empty($res['path']) || !is_file((string)$res['path'])) {
            echo json_encode([
                'ok' => false,
                'message' => (string)($res['message'] ?? 'Nie udało się pobrać etykiety.'),
                'http_code' => (int)($res['http_code'] ?? 0),
            ]);
            exit;
        }

        $downloadUrl = $this->context->link->getAdminLink('AdminAllegroProShipments')
            . '&action=downloadLabel&id_allegropro_account=' . (int)$id
            . '&checkout_form_id=' . rawurlencode($checkoutFormId)
            . '&shipment_id=' . rawurlencode($shipmentId);

        echo json_encode([
            'ok' => true,
            'download_url' => $downloadUrl,
            'message' => 'Etykieta gotowa do pobrania.',
        ]);
        exit;
    }

    private function handleDownloadLabel()
    {
        $id = (int)Tools::getValue('id_allegropro_account');
        $checkoutFormId = (string)Tools::getValue('checkout_form_id');
        $shipmentId = (string)Tools::getValue('shipment_id');

        $acc = $this->accounts->get($id);
        if (!$acc || !$checkoutFormId) {
            header('HTTP/1.1 400 Bad Request');
            echo 'Missing account or checkoutFormId';
            exit;
        }

        if ($shipmentId === '') {
            $row = $this->orders->getRaw($id, $checkoutFormId);
            if (!$row || empty($row['shipment_id'])) {
                header('HTTP/1.1 404 Not Found');
                echo 'No shipmentId for this order';
                exit;
            }
            $shipmentId = (string)$row['shipment_id'];
        }

        $manager = $this->getShipmentManager();
        $res = $manager->downloadLabel($acc, $checkoutFormId, $shipmentId);

        if (empty($res['ok']) || empty($res['path']) || !is_file((string)$res['path'])) {
            header('HTTP/1.1 502 Bad Gateway');
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Label download failed. ' . (string)($res['message'] ?? 'Unknown error');
            if (!empty($res['http_code'])) {
                echo ' HTTP ' . (int)$res['http_code'];
            }
            exit;
        }

        $format = (string)($res['format'] ?? 'PDF');
        $mime = (new LabelStorage())->getMimeType($format);
        $ext = (strtoupper($format) === 'ZPL') ? 'zpl' : 'pdf';
        $fileName = 'allegro_label_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $checkoutFormId) . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $shipmentId) . '.' . $ext;

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($res['path']));
        header('Content-Disposition: inline; filename="' . $fileName . '"');
        header('X-Content-Type-Options: nosniff');

        readfile($res['path']);
        exit;
    }

}
