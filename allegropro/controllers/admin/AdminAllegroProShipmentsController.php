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

        $list = $selected ? $this->orders->list($selected, 50) : [];
        $without = $selected ? $this->orders->listWithoutShipment($selected, 50) : [];
        $dsCount = $selected ? $this->delivery->countForAccount($selected) : 0;

        $this->context->smarty->assign([
            'allegropro_accounts' => $accounts,
            'allegropro_selected_account' => $selected,
            'allegropro_orders' => $list,
            'allegropro_orders_without_shipment' => $without,
            'allegropro_delivery_services_count' => $dsCount,
            'admin_link' => $this->context->link->getAdminLink('AdminAllegroProShipments'),
        ]);

        $this->setTemplate('shipments.tpl');
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
            echo "Label download failed. HTTP " . (int)$pdf['code'] . "\n";
            echo $pdf['raw'];
            exit;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="allegro_label_'.$checkoutFormId.'.pdf"');
        echo $pdf['raw'];
        exit;
    }
}
