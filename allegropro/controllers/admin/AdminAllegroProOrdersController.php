<?php
/**
 * KONTROLER ZAMÓWIEŃ - Wersja PRO (Smart Skip & Incremental Fetch)
 */
require_once dirname(__FILE__) . '/../../src/Model/Order.php';

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
use AllegroPro\Model\Order;

class AdminAllegroProOrdersController extends ModuleAdminController
{
    private $repo;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'allegropro_order';
        $this->className = Order::class;
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
        return $scope === 'history' ? 100 : 50;
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
                $res = $fetcher->fetchRecent($account, $limit);
            }

            $this->ajaxDie(json_encode([
                'success' => true,
                'count' => (int)$res['fetched_count'],
                'account_id' => (int)$account['id_allegropro_account'],
                'fetched_ids' => $res['fetched_ids'] ?? [],
                'limit' => $limit,
            ]));
        } catch (Exception $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    // Krok 2: Pobranie listy ID (SMART SKIP, per konto, spójność batcha)
    public function displayAjaxImportGetPending() {
        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

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
        } catch (Exception $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    // ============================================================
    // AJAX: WYSYŁKA
    // ============================================================

    public function displayAjaxCreateShipment() {
        $cfId = Tools::getValue('checkout_form_id');
        $sizeCode = Tools::getValue('size_code');
        $weight = Tools::getValue('weight');
        $isSmart = (int)Tools::getValue('is_smart');

        if (!$cfId) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Brak checkout_form_id.']));
        }

        $account = $this->getValidAccountFromRequest();
        if (!$account) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => 'Nieprawidłowe konto Allegro.']));
        }

        $manager = $this->getShipmentManager();
        $res = $manager->createShipment($account, $cfId, ['size_code' => $sizeCode, 'weight' => $weight, 'smart' => $isSmart]);
        if ($res['ok']) $this->ajaxDie(json_encode(['success' => true]));
        else $this->ajaxDie(json_encode(['success' => false, 'message' => $res['message']]));
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

        $manager = $this->getShipmentManager();
        $res = $manager->downloadLabel($account, $cfId, $shipmentId);
        if ($res['ok']) $this->ajaxDie(json_encode(['success' => true, 'url' => $res['url']]));
        else $this->ajaxDie(json_encode(['success' => false, 'message' => 'Błąd pobierania']));
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
        parent::initContent();
    }

    public function renderList()
    {
        $orders = $this->repo->getPaginated(50);
        $accounts = (new AccountRepository())->all();
        $selectedAccount = (int)Tools::getValue('id_allegropro_account');
        if (!$selectedAccount && !empty($accounts)) $selectedAccount = (int)$accounts[0]['id_allegropro_account'];

        $this->context->smarty->assign([
            'allegropro_orders' => $orders,
            'allegropro_accounts' => $accounts,
            'allegropro_selected_account' => $selectedAccount,
            'admin_link' => $this->context->link->getAdminLink('AdminAllegroProOrders')
        ]);

        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'allegropro/views/templates/admin/orders.tpl');
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
