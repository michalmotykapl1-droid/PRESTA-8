<?php
/**
 * KONTROLER ZAMÓWIEŃ - Wersja PRO (Smart Skip & Incremental Fetch)
 */
use AllegroPro\Repository\OrderRepository;
use AllegroPro\Repository\AccountRepository;
use AllegroPro\Repository\DeliveryServiceRepository;
use AllegroPro\Repository\ShipmentRepository;
use AllegroPro\Service\HttpClient;
use AllegroPro\Service\AllegroApiClient;
use AllegroPro\Service\OrderImporter;
use AllegroPro\Service\OrderFetcher;
use AllegroPro\Service\OrderProcessor;
use AllegroPro\Service\ShipmentManager;
use AllegroPro\Service\LabelConfig;
use AllegroPro\Service\LabelStorage;

class AdminAllegroProOrdersController extends ModuleAdminController
{
    private $repo;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'allegropro_order';
        $this->className = 'Order';
        $this->identifier = 'id_allegropro_order';
        $this->default_order_by = 'updated_at_allegro';
        $this->default_order_way = 'DESC';

        parent::__construct();
        $this->repo = new OrderRepository();

        $this->fields_list = [
            'id_allegropro_order' => ['title' => 'ID', 'width' => 30],
            'checkout_form_id' => ['title' => 'Allegro ID'],
            'total_amount' => ['title' => 'Kwota'],
            'shipping_method_name' => ['title' => 'Metoda Dostawy', 'havingFilter' => true],
        ];
    }

    // --- HELPER DI ---
    private function getServices() {
        $accRepo = new AccountRepository();
        $http = new HttpClient();
        $api = new AllegroApiClient($http, $accRepo);
        $fetcher = new OrderFetcher($api, $this->repo);
        $processor = new OrderProcessor($this->repo);

        return [$accRepo, $fetcher, $processor];
    }

    private function getShipmentManager() {
        $accRepo = new AccountRepository();
        $http = new HttpClient();
        $api = new AllegroApiClient($http, $accRepo);

        return new ShipmentManager(
            $api,
            new LabelConfig(),
            new LabelStorage(),
            new OrderRepository(),
            new DeliveryServiceRepository(),
            new ShipmentRepository()
        );
    }

    private function getAccount($id) {
        return (new AccountRepository())->get((int)$id);
    }

    private function getValidAccountFromRequest(): ?array
    {
        $accId = (int)Tools::getValue('id_allegropro_account');
        if ($accId <= 0) {
            return null;
        }

        $account = $this->getAccount($accId);
        if (!is_array($account)) {
            return null;
        }

        return $account;
    }

    private function getImportLimit(string $scope): int
    {
        $default = ($scope === 'history') ? 100 : 50;
        $limit = (int)Tools::getValue('fetch_limit');

        if ($limit <= 0) {
            return $default;
        }

        if ($limit > 1000) {
            return 1000;
        }

        return $limit;
    }

    // ============================================================
    // AJAX: KONSOLA IMPORTU
    // ============================================================

    // Krok 1: Pobieranie z Allegro (INCREMENTAL FETCH / HISTORY)
    public function displayAjaxImportFetch() {
        list(, $fetcher, ) = $this->getServices();
        $scope = (string)Tools::getValue('scope');

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }
        if ((int)$account['active'] !== 1) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Wybrane konto Allegro jest nieaktywne.']));
        }

        $limit = $this->getImportLimit($scope);

        $includeOlderRaw = Tools::getValue('include_older', Tools::getValue('include_backfill', 1));
        $includeOlder = !in_array((string)$includeOlderRaw, ['0', 'false', 'off', 'no'], true);

        try {
            if ($scope === 'history') {
                $dateFrom = (string)Tools::getValue('date_from');
                $dateTo = (string)Tools::getValue('date_to');

                if (!$dateFrom || !$dateTo) {
                    $this->ajaxDie(json_encode(['success' => false, 'message' => 'Dla trybu historii podaj date_from i date_to.']));
                }

                $fromTs = strtotime($dateFrom);
                $toTs = strtotime($dateTo);
                if ($fromTs === false || $toTs === false || $fromTs > $toTs) {
                    $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowy zakres dat historii.']));
                }

                $res = $fetcher->fetchHistory($account, $dateFrom, $dateTo, $limit);
            } else {
                $res = $fetcher->fetchRecent($account, $limit, $includeOlder);
            }

            $this->ajaxDie(json_encode([
                'success' => true,
                'count' => (int)$res['fetched_count'],
                'account_id' => (int)$account['id_allegropro_account'],
                'fetched_ids' => $res['fetched_ids'] ?? [],
                'limit' => $limit,
            ]));
        } catch (\Throwable $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    // Krok 2: Pobranie listy ID (SMART SKIP, per konto, spójność batcha)
    public function displayAjaxImportGetPending() {
        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $onlyFetchedRaw = Tools::getValue('only_fetched', 0);
        $onlyFetched = in_array((string)$onlyFetchedRaw, ['1', 'true', 'on', 'yes'], true);

        $limit = (int)Tools::getValue('limit');
        if ($limit <= 0) {
            $limit = 50;
        }

        $rawFetchedIds = Tools::getValue('fetched_ids');
        $fetchedIds = [];
        if (is_array($rawFetchedIds)) {
            $fetchedIds = $rawFetchedIds;
        } elseif (is_string($rawFetchedIds) && $rawFetchedIds !== '') {
            $decoded = json_decode($rawFetchedIds, true);
            if (is_array($decoded)) {
                $fetchedIds = $decoded;
            }
        }

        if (!empty($fetchedIds)) {
            $ids = $this->repo->filterPendingIdsForAccount((int)$account['id_allegropro_account'], $fetchedIds);
        } elseif ($onlyFetched) {
            // Tryb "tylko nowe": jeżeli nic nie pobrano w fetchu, nie schodzimy do starszych pendingów.
            $ids = [];
        } else {
            $ids = $this->repo->getPendingIdsForAccount((int)$account['id_allegropro_account'], $limit);
        }

        $this->ajaxDie(json_encode(['success' => true, 'ids' => $ids]));
    }

    // Krok 3 i 4: Przetwarzanie (Create lub Fix)
    public function displayAjaxImportProcessSingle() {
        $cfId = Tools::getValue('checkout_form_id');
        $step = Tools::getValue('step'); // 'create' lub 'fix'

        if (!$cfId) $this->ajaxDie(json_encode(['success' => false, 'message' => 'No ID']));
        if (!$step) $this->ajaxDie(json_encode(['success' => false, 'message' => 'No Step']));

        list(, , $processor) = $this->getServices();

        try {
            $result = $processor->processSingleOrder($cfId, $step);
            $this->ajaxDie(json_encode($result));
        } catch (\Throwable $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    // ============================================================
    // AJAX: WYSYŁKA
    // ============================================================

    public function displayAjaxCreateShipment() {
        $cfId = (string)Tools::getValue('checkout_form_id');
        $sizeCode = (string)Tools::getValue('size_code');
        $weight = (string)Tools::getValue('weight');
        $isSmart = (int)Tools::getValue('is_smart');
        $debug = in_array((string)Tools::getValue('debug', '0'), ['1', 'true', 'on', 'yes'], true);

        if ($cfId === '') {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Brak checkout_form_id.',
                'debug_enabled' => $debug,
                'debug_lines' => $debug ? ['[CREATE] brak checkout_form_id'] : [],
            ]));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode([
                'success' => false,
                'message' => 'Nieprawidłowe konto Allegro.',
                'debug_enabled' => $debug,
                'debug_lines' => $debug ? ['[CREATE] nieprawidłowe konto Allegro'] : [],
            ]));
        }

        $manager = $this->getShipmentManager();
        $res = $manager->createShipment($account, $cfId, [
            'size_code' => $sizeCode,
            'weight' => $weight,
            'smart' => $isSmart,
            'debug' => $debug ? 1 : 0,
        ]);

        if (!empty($res['ok'])) {
            $this->ajaxDie(json_encode([
                'success' => true,
                'message' => (string)($res['message'] ?? 'Przesyłka została utworzona.'),
                'shipment_id' => (string)($res['shipmentId'] ?? ''),
                'debug_enabled' => $debug,
                'debug_lines' => is_array($res['debug_lines'] ?? null) ? array_values($res['debug_lines']) : [],
            ]));
        }

        $this->ajaxDie(json_encode([
            'success' => false,
            'message' => (string)($res['message'] ?? 'Nie udało się utworzyć przesyłki.'),
            'debug_enabled' => $debug,
            'debug_lines' => is_array($res['debug_lines'] ?? null) ? array_values($res['debug_lines']) : [],
        ]));
    }

    public function displayAjaxCancelShipment() {
        $shipmentId = (string)Tools::getValue('shipment_id');
        if ($shipmentId === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak shipment_id.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $manager = $this->getShipmentManager();
        $res = $manager->cancelShipment($account, $shipmentId);
        if ($res['ok']) $this->ajaxDie(json_encode(['success' => true]));
        else $this->ajaxDie(json_encode(['success' => false, 'message' => $res['message']]));
    }

    public function displayAjaxSyncShipments() {
        $cfId = (string)Tools::getValue('checkout_form_id');
        $debug = in_array((string)Tools::getValue('debug', '0'), ['1', 'true', 'on', 'yes'], true);

        if ($cfId === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak checkout_form_id.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $manager = $this->getShipmentManager();
        $res = $manager->syncOrderShipments($account, $cfId, 0, true, $debug);

        if (!empty($res['ok'])) {
            $this->ajaxDie(json_encode([
                'success' => true,
                'synced' => (int)($res['synced'] ?? 0),
                'skipped' => !empty($res['skipped']),
                'debug_enabled' => $debug,
                'debug_lines' => is_array($res['debug_lines'] ?? null) ? array_values($res['debug_lines']) : [],
            ]));
        }

        $this->ajaxDie(json_encode([
            'success' => false,
            'message' => (string)($res['message'] ?? 'Błąd synchronizacji przesyłek.'),
            'debug_enabled' => $debug,
            'debug_lines' => is_array($res['debug_lines'] ?? null) ? array_values($res['debug_lines']) : [],
        ]));
    }

    public function displayAjaxGetLabel() {
        $shipmentId = (string)Tools::getValue('shipment_id');
        $cfId = (string)Tools::getValue('checkout_form_id');

        if ($shipmentId === '' || $cfId === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak shipment_id lub checkout_form_id.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $downloadUrl = $this->context->link->getAdminLink('AdminAllegroProOrders', true, [], [
            'action' => 'download_label',
            'id_allegropro_account' => (int)$account['id_allegropro_account'],
            'checkout_form_id' => $cfId,
            'shipment_id' => $shipmentId,
        ]);

        $this->ajaxDie(json_encode(['success' => true, 'url' => $downloadUrl]));
    }

    public function displayAjaxUpdateAllegroStatus() {
        $cfId = (string)Tools::getValue('checkout_form_id');
        $status = (string)Tools::getValue('new_status');

        if ($cfId === '' || $status === '') {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak checkout_form_id lub statusu.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $http = new HttpClient();
        $api = new AllegroApiClient($http, new AccountRepository());
        $resp = $api->postJson($account, '/order/checkout-forms/' . $cfId . '/fulfillment', ['status' => $status]);
        if ($resp['ok']) $this->ajaxDie(json_encode(['success' => true]));
        else $this->ajaxDie(json_encode(['success' => false, 'message' => 'Błąd API']));
    }

    public function initContent()
    {
        if (Tools::getValue('action') === 'get_order_details') {
            $this->ajaxGetOrderDetails();
            return;
        }

        if (Tools::getValue('action') === 'download_label') {
            $this->downloadLabelFile();
            return;
        }

        parent::initContent();
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



    private function getModuleStatusGroups(array $rawStatuses): array
    {
        $groups = [
            'PAID' => [
                'label' => 'ALLEGRO PRO - OPŁACONE',
                'raw' => [],
            ],
            'NO_PAYMENT' => [
                'label' => 'ALLEGRO PRO - BRAK WPŁATY',
                'raw' => [],
            ],
            'PROCESSING' => [
                'label' => 'ALLEGRO PRO - PRZETWARZANIE',
                'raw' => [],
            ],
            'CANCELLED' => [
                'label' => 'ALLEGRO PRO - ANULOWANE',
                'raw' => [],
            ],
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

    public function renderList()
    {
        $accounts = (new AccountRepository())->all();

        $perPage = (int)Tools::getValue('per_page', 50);
        $allowedPerPage = [20, 50, 100, 200];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 50;
        }

        $page = (int)Tools::getValue('page', 1);
        if ($page < 1) {
            $page = 1;
        }

        $deliveryMethods = Tools::getValue('filter_delivery_methods', []);
        if (!is_array($deliveryMethods)) {
            $deliveryMethods = [$deliveryMethods];
        }
        $deliveryMethods = array_values(array_filter(array_map('trim', array_map('strval', $deliveryMethods))));

        $statusCodes = Tools::getValue('filter_statuses', []);
        if (!is_array($statusCodes)) {
            $statusCodes = [$statusCodes];
        }
        $statusCodes = array_values(array_filter(array_map('trim', array_map('strval', $statusCodes))));

        $statusGroups = $this->getModuleStatusGroups($this->repo->getDistinctStatuses());
        $statuses = [];
        foreach ($statusCodes as $code) {
            if (!isset($statusGroups[$code])) {
                continue;
            }
            foreach ($statusGroups[$code]['raw'] as $rawStatus) {
                $statuses[] = (string)$rawStatus;
            }
        }
        $statuses = array_values(array_unique($statuses));

        $filters = [
            'id_allegropro_account' => (int)Tools::getValue('filter_account'),
            'date_from' => (string)Tools::getValue('filter_date_from'),
            'date_to' => (string)Tools::getValue('filter_date_to'),
            'delivery_methods' => $deliveryMethods,
            'statuses' => $statuses,
            'checkout_form_id' => trim((string)Tools::getValue('filter_checkout_form_id')),
        ];

        if ($filters['id_allegropro_account'] <= 0) {
            $filters['id_allegropro_account'] = 0;
        }

        if ($filters['date_from'] !== '' && strtotime($filters['date_from']) === false) {
            $filters['date_from'] = '';
        }

        if ($filters['date_to'] !== '' && strtotime($filters['date_to']) === false) {
            $filters['date_to'] = '';
        }

        $totalRows = $this->repo->countFiltered($filters);
        $totalPages = max(1, (int)ceil($totalRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $orders = $this->repo->getPaginatedFiltered($filters, $perPage, $offset);
        foreach ($orders as &$order) {
            $order['module_status_label'] = $this->mapModuleStatusLabel((string)($order['status'] ?? ''));
            $order['module_status_class'] = $this->mapModuleStatusClass((string)$order['module_status_label']);
        }
        unset($order);

        $selectedAccount = (int)Tools::getValue('id_allegropro_account');
        if (!$selectedAccount && !empty($accounts)) {
            $selectedAccount = (int)$accounts[0]['id_allegropro_account'];
        }

        $this->context->smarty->assign([
            'allegropro_orders' => $orders,
            'allegropro_accounts' => $accounts,
            'allegropro_selected_account' => $selectedAccount,
            'allegropro_filters' => $filters,
            'allegropro_pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_rows' => $totalRows,
                'total_pages' => $totalPages,
                'allowed_per_page' => $allowedPerPage,
            ],
            'allegropro_delivery_options' => $this->repo->getDistinctDeliveryMethods(),
            'allegropro_status_options' => $statusGroups,
            'allegropro_selected_status_codes' => $statusCodes,
            'admin_link' => $this->context->link->getAdminLink('AdminAllegroProOrders')
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'allegropro/views/templates/admin/orders.tpl');
    }

    private function downloadLabelFile(): void
    {
        $shipmentId = (string)Tools::getValue('shipment_id');
        $cfId = (string)Tools::getValue('checkout_form_id');
        $debug = in_array((string)Tools::getValue('debug', '0'), ['1', 'true', 'on', 'yes'], true);
        $debugLines = [];

        if ($shipmentId === '' || $cfId === '') {
            if ($debug) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Brak shipment_id lub checkout_form_id.',
                    'debug_lines' => ['[LABEL] brak shipment_id lub checkout_form_id'],
                ]);
                exit;
            }

            header('HTTP/1.1 400 Bad Request');
            echo 'Brak shipment_id lub checkout_form_id.';
            exit;
        }

        $debugLines[] = '[LABEL] start checkout_form_id=' . $cfId . ', shipment_id=' . $shipmentId;

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            if ($debug) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Nieprawidłowe konto Allegro.',
                    'debug_lines' => array_merge($debugLines, ['[LABEL] nieprawidłowe konto Allegro']),
                ]);
                exit;
            }

            header('HTTP/1.1 403 Forbidden');
            echo 'Nieprawidłowe konto Allegro.';
            exit;
        }

        $debugLines[] = '[LABEL] account_id=' . (int)$account['id_allegropro_account'];

        $exists = (int)Db::getInstance()->getValue(
            'SELECT id_allegropro_shipment FROM `' . _DB_PREFIX_ . 'allegropro_shipment` '
            . 'WHERE id_allegropro_account = ' . (int)$account['id_allegropro_account']
            . " AND checkout_form_id = '" . pSQL($cfId) . "'"
            . " AND shipment_id = '" . pSQL($shipmentId) . "'"
        );

        $debugLines[] = '[LABEL] local shipment row exists=' . ($exists > 0 ? '1' : '0');

        if ($exists <= 0) {
            if ($debug) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Nie znaleziono przesyłki dla wskazanego zamówienia.',
                    'debug_lines' => array_merge($debugLines, ['[LABEL] brak rekordu w tabeli allegropro_shipment']),
                ]);
                exit;
            }

            header('HTTP/1.1 404 Not Found');
            echo 'Nie znaleziono przesyłki dla wskazanego zamówienia.';
            exit;
        }

        $manager = $this->getShipmentManager();
        $res = $manager->downloadLabel($account, $cfId, $shipmentId);

        if (!empty($res['debug_lines']) && is_array($res['debug_lines'])) {
            $debugLines = array_merge($debugLines, array_values($res['debug_lines']));
        }

        if (empty($res['ok']) || empty($res['path']) || !is_file($res['path'])) {
            if ($debug) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => (string)($res['message'] ?? 'Błąd pobierania etykiety.'),
                    'http_code' => (int)($res['http_code'] ?? 0),
                    'debug_lines' => $debugLines,
                ]);
                exit;
            }

            header('HTTP/1.1 502 Bad Gateway');
            echo 'Błąd pobierania etykiety.';
            exit;
        }

        $format = (string)($res['format'] ?? 'PDF');
        $mime = (new LabelStorage())->getMimeType($format);
        $ext = (strtoupper($format) === 'ZPL') ? 'zpl' : 'pdf';
        $fileName = 'allegro_label_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $cfId) . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $shipmentId) . '.' . $ext;

        $debugLines[] = '[LABEL] success format=' . $format . ', mime=' . $mime . ', file=' . $res['path'];

        if ($debug) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Etykieta pobrana poprawnie (tryb debug - bez streamu pliku).',
                'file_name' => $fileName,
                'file_path' => $res['path'],
                'file_size' => (int)filesize($res['path']),
                'mime' => $mime,
                'debug_lines' => $debugLines,
            ]);
            exit;
        }

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


    private function ajaxGetOrderDetails()
    {
        $cfId = Tools::getValue('checkout_form_id');
        $items = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."allegropro_order_item WHERE checkout_form_id = '".pSQL($cfId)."'");
        header('Content-Type: application/json');
        echo json_encode($items);
        exit;
    }
}
