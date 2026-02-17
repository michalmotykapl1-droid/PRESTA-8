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
        $selected = (int)Tools::getValue('id_allegropro_account');
        if (!$selected) {
            foreach ($accounts as $a) {
                if ((int)$a['is_default'] === 1) {
                    $selected = (int)$a['id_allegropro_account'];
                    break;
                }
            }
            if (!$selected && !empty($accounts)) {
                $selected = (int)$accounts[0]['id_allegropro_account'];
            }
        }

        $filters = $this->readFilters($selected);
        $perPage = $filters['per_page'];

        $pendingPage = max(1, (int)Tools::getValue('pending_page', 1));
        $labeledPage = max(1, (int)Tools::getValue('labeled_page', 1));

        $pendingTotalRows = $selected ? $this->orders->countShipmentListFiltered($selected, $filters, false) : 0;
        $pendingTotalPages = max(1, (int)ceil($pendingTotalRows / $perPage));
        if ($pendingPage > $pendingTotalPages) {
            $pendingPage = $pendingTotalPages;
        }

        $labeledTotalRows = $selected ? $this->orders->countShipmentListFiltered($selected, $filters, true) : 0;
        $labeledTotalPages = max(1, (int)ceil($labeledTotalRows / $perPage));
        if ($labeledPage > $labeledTotalPages) {
            $labeledPage = $labeledTotalPages;
        }

        $pendingList = $selected
            ? $this->orders->getShipmentListFiltered($selected, $filters, $perPage, ($pendingPage - 1) * $perPage, false)
            : [];

        $labeledList = $selected
            ? $this->orders->getShipmentListFiltered($selected, $filters, $perPage, ($labeledPage - 1) * $perPage, true)
            : [];

        foreach ($pendingList as &$row) {
            $row['module_status_label'] = $this->mapModuleStatusLabel((string)($row['status'] ?? ''));
            $row['module_status_class'] = $this->mapModuleStatusClass((string)$row['module_status_label']);
        }
        unset($row);

        foreach ($labeledList as &$row) {
            $row['module_status_label'] = $this->mapModuleStatusLabel((string)($row['status'] ?? ''));
            $row['module_status_class'] = $this->mapModuleStatusClass((string)$row['module_status_label']);
        }
        unset($row);

        $statusGroups = $this->getModuleStatusGroups($selected ? $this->orders->getDistinctStatusesForAccount($selected) : []);
        $shipmentStatuses = $selected ? $this->orders->getDistinctShipmentStatusesForAccount($selected) : [];
        $dsCount = $selected ? $this->delivery->countForAccount($selected) : 0;

        $this->context->smarty->assign([
            'allegropro_accounts' => $accounts,
            'allegropro_selected_account' => $selected,
            'allegropro_pending_orders' => $pendingList,
            'allegropro_labeled_orders' => $labeledList,
            'allegropro_filters' => $filters,
            'allegropro_status_options' => $statusGroups,
            'allegropro_shipment_status_options' => $shipmentStatuses,
            'allegropro_delivery_services_count' => $dsCount,
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

    private function readFilters(int $selectedAccount): array
    {
        $statusCodes = Tools::getValue('filter_status_codes', []);
        if (!is_array($statusCodes)) {
            $statusCodes = [$statusCodes];
        }
        $statusCodes = array_values(array_filter(array_map('trim', array_map('strval', $statusCodes))));

        $statusGroups = $this->getModuleStatusGroups($selectedAccount ? $this->orders->getDistinctStatusesForAccount($selectedAccount) : []);
        $rawStatuses = [];
        foreach ($statusCodes as $code) {
            if (!isset($statusGroups[$code])) {
                continue;
            }
            foreach ($statusGroups[$code]['raw'] as $raw) {
                $rawStatuses[] = (string)$raw;
            }
        }

        $shipmentStatuses = Tools::getValue('filter_shipment_statuses', []);
        if (!is_array($shipmentStatuses)) {
            $shipmentStatuses = [$shipmentStatuses];
        }
        $shipmentStatuses = array_values(array_filter(array_map('trim', array_map('strval', $shipmentStatuses))));

        $perPage = (int)Tools::getValue('per_page', 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $filters = [
            'query' => trim((string)Tools::getValue('filter_query')),
            'date_from' => trim((string)Tools::getValue('filter_date_from')),
            'date_to' => trim((string)Tools::getValue('filter_date_to')),
            'statuses' => array_values(array_unique($rawStatuses)),
            'selected_status_codes' => $statusCodes,
            'shipment_statuses' => $shipmentStatuses,
            'per_page' => $perPage,
        ];

        if ($filters['date_from'] !== '' && strtotime($filters['date_from']) === false) {
            $filters['date_from'] = '';
        }

        if ($filters['date_to'] !== '' && strtotime($filters['date_to']) === false) {
            $filters['date_to'] = '';
        }

        return $filters;
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

    private function handleActions()
    {
        // Download label (PDF)
        if (Tools::getValue('action') === 'downloadLabel') {
            $this->handleDownloadLabel();
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

        // Bulk fix: backfill wza_shipment_uuid from shipment_id for size_details=CUSTOM
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

    private function handleDownloadLabel()
    {
        $id = (int)Tools::getValue('id_allegropro_account');
        $checkoutFormId = (string)Tools::getValue('checkout_form_id');

        $acc = $this->accounts->get($id);
        if (!$acc || !$checkoutFormId) {
            die('Missing account or checkoutFormId');
        }

        $row = $this->orders->getRaw($id, $checkoutFormId);
        if (!$row || empty($row['shipment_id'])) {
            die('No shipmentId for this order');
        }

        $shipmentId = (string)$row['shipment_id'];

        $api = new AllegroApiClient(new HttpClient(), $this->accounts);
        $svc = new ShipmentsService($api, $this->delivery, $this->orders, $this->shipments);
        $pdf = $svc->fetchLabelPdf($acc, [$shipmentId]);

        if (!$pdf['ok']) {
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Label download failed. HTTP ' . (int)$pdf['code'] . "\n";
            echo $pdf['raw'];
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="allegro_label_' . $checkoutFormId . '.pdf"');
        echo $pdf['raw'];
        exit;
    }
}
