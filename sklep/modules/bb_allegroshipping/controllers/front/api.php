<?php
/**
 * API Controller dla BB Allegro Shipping (Multi-Account Ready)
 */

class Bb_allegroshippingApiModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $ajax = true;

    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json');

        if (!$this->checkAccess()) {
            echo json_encode(['success' => false, 'error' => 'Brak uprawnień.']);
            die();
        }

        try {
            $action = Tools::getValue('action');

            if ($action === 'create_shipment') {
                $this->createShipment();
            } else {
                throw new Exception('Nieznana akcja.');
            }

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            die();
        }
    }

    private function checkAccess()
    {
        $cookie = new Cookie('psAdmin');
        return ($cookie->id_employee && $cookie->id_employee > 0);
    }

    private function createShipment()
    {
        $id_order = (int) Tools::getValue('id_order');
        if (!$id_order) throw new Exception('Brak ID zamówienia');

        $order = new Order($id_order);
        if (!Validate::isLoadedObject($order)) throw new Exception('Zamówienie nie istnieje');

        // 1. Wybór konta Allegro
        // TODO: W przyszłości pobierzemy z modułu x13allegro, z którego konta pochodzi zamówienie.
        // Na razie pobieramy pierwsze aktywne konto z naszej nowej tabeli.
        $account = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "bb_allegro_accounts` ORDER BY id_account ASC");
        
        if (!$account) {
            throw new Exception('Nie skonfigurowano żadnego konta Allegro w module bb_allegroshipping.');
        }

        // Tutaj w przyszłości użyjemy $account['client_id'] i $account['client_secret'] do autoryzacji

        // 2. Symulacja tworzenia przesyłki
        $fakeTracking = 'ALE' . date('dmYHis') . '-ACC' . $account['id_account'];
        
        $this->saveTrackingNumber($id_order, $fakeTracking, $order->id_carrier);

        echo json_encode([
            'success' => true,
            'tracking_number' => $fakeTracking,
            'message' => 'Przesyłka utworzona (Konto: ' . $account['name'] . ')'
        ]);
        die();
    }

    private function saveTrackingNumber($id_order, $number, $id_carrier)
    {
        $db = Db::getInstance();
        $exists = $db->getValue("SELECT id_order_carrier FROM `" . _DB_PREFIX_ . "order_carrier` WHERE id_order = $id_order AND tracking_number = '" . pSQL($number) . "'");
        
        if (!$exists) {
            $new_oc = new OrderCarrier();
            $new_oc->id_order = $id_order;
            $new_oc->id_carrier = $id_carrier;
            $new_oc->tracking_number = $number;
            $new_oc->date_add = date('Y-m-d H:i:s');
            $new_oc->add();

            $order = new Order($id_order);
            if (empty($order->shipping_number)) {
                $order->shipping_number = $number;
                $order->update();
            }
        }
    }
}