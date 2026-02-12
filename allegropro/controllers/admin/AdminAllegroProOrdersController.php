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

    // ============================================================
    // AJAX: KONSOLA IMPORTU
    // ============================================================

    // Krok 1: Pobieranie z Allegro (INCREMENTAL FETCH)
    public function displayAjaxImportFetch() {
        list($accRepo, $fetcher, ) = $this->getServices();
        $scope = Tools::getValue('scope'); 
        
        $accounts = $accRepo->all();
        $totalFetched = 0;
        
        try {
            foreach ($accounts as $acc) {
                if (!$acc['active']) continue;
                
                // ZMIANA: Rozróżnienie trybów pobierania
                if ($scope === 'history') {
                    $dateTo = date('Y-m-d');
                    $dateFrom = date('Y-m-d', strtotime('-30 days'));
                    $res = $fetcher->fetchHistory($acc, $dateFrom, $dateTo, 100);
                } else {
                    // Tryb Standard: Inteligentne pobieranie tylko NOWYCH
                    $res = $fetcher->fetchRecent($acc, 50);
                }
                
                $totalFetched += $res['fetched_count'];
            }
            $this->ajaxDie(json_encode(['success' => true, 'count' => $totalFetched]));
        } catch (Exception $e) {
            $this->ajaxDie(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }

    // Krok 2: Pobranie listy ID (SMART SKIP!)
    public function displayAjaxImportGetPending() {
        // Używamy metody, która zwraca tylko ID gdzie is_finished=0
        // Czyli Twoje "ALLEGRO PRO - ZAMÓWIENIE PRZETWARZANE"
        $ids = $this->repo->getPendingIds(50);
        
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
        $accId = (int)Tools::getValue('id_allegropro_account');
        $sizeCode = Tools::getValue('size_code');
        $weight = Tools::getValue('weight');
        $isSmart = (int)Tools::getValue('is_smart');
        $account = $this->getAccount($accId);
        $manager = $this->getShipmentManager();
        $res = $manager->createShipment($account, $cfId, ['size_code'=>$sizeCode, 'weight'=>$weight, 'smart'=>$isSmart]);
        if ($res['ok']) $this->ajaxDie(json_encode(['success' => true]));
        else $this->ajaxDie(json_encode(['success' => false, 'message' => $res['message']]));
    }

    public function displayAjaxCancelShipment() {
        $shipmentId = Tools::getValue('shipment_id');
        $accId = (int)Tools::getValue('id_allegropro_account');
        $account = $this->getAccount($accId);
        $manager = $this->getShipmentManager();
        $res = $manager->cancelShipment($account, $shipmentId);
        if ($res['ok']) $this->ajaxDie(json_encode(['success' => true]));
        else $this->ajaxDie(json_encode(['success' => false, 'message' => $res['message']]));
    }
    
    public function displayAjaxGetLabel() {
        $shipmentId = Tools::getValue('shipment_id');
        $cfId = Tools::getValue('checkout_form_id');
        $accId = (int)Tools::getValue('id_allegropro_account');
        $account = $this->getAccount($accId);
        $manager = $this->getShipmentManager();
        $res = $manager->downloadLabel($account, $cfId, $shipmentId);
        if ($res['ok']) $this->ajaxDie(json_encode(['success' => true, 'url' => $res['url']]));
        else $this->ajaxDie(json_encode(['success' => false, 'message' => 'Błąd pobierania']));
    }
    
    public function displayAjaxUpdateAllegroStatus() {
        $cfId = Tools::getValue('checkout_form_id');
        $accId = (int)Tools::getValue('id_allegropro_account');
        $status = Tools::getValue('new_status');
        $account = $this->getAccount($accId);
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
        if (!$selectedAccount && !empty($accounts)) $selectedAccount = $accounts[0]['id_allegropro_account'];

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
