<?php
namespace AllegroPro\Service;

use AllegroPro\Repository\OrderRepository;
use Context;
use Customer;
use Address;
use Cart;
use Product;
use Configuration;
use Validate;
use Db;
use Country;
use Order;
use OrderHistory;
use OrderDetail;
use OrderPayment;
use Carrier;
use StockAvailable;
use Currency;
use Cache;

class OrderProcessor
{
    private $repo;

    public function __construct(OrderRepository $repo)
    {
        $this->repo = $repo;
    }

    public function processSingleOrder(string $cfId, string $step): array
    {
        $row = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order WHERE checkout_form_id = '".pSQL($cfId)."'");
        
        if (!$row) {
            return ['success' => false, 'message' => 'Brak zamówienia w bazie lokalnej.'];
        }

        // Smart Skip: Jeśli oznaczone jako finished, pomijamy.
        if ((int)$row['is_finished'] === 1) {
            return ['success' => true, 'action' => 'skipped_done', 'id_order' => $row['id_order_prestashop'], 'message' => 'Już zakończone.'];
        }

        $items = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."allegropro_order_item WHERE checkout_form_id = '".pSQL($cfId)."'");
        if (empty($items)) return ['success' => false, 'message' => 'Brak produktów.'];

        $existingId = $this->resolveExistingPsOrderId($row, $cfId);

        // =========================================================
        // KROK 2: TWORZENIE
        // =========================================================
        if ($step === 'create') {
            if ($existingId > 0) {
                $this->repo->updatePsOrderId($cfId, $existingId);
                return ['success' => true, 'action' => 'skipped', 'id_order' => $existingId, 'message' => 'Już istnieje.'];
            }

            try {
                $paymentModule = $this->getPaymentModule();
                if (!$paymentModule) throw new \Exception("Brak modułu płatności.");

                // Tworzymy zamówienie (Status: Przetwarzanie lub Brak Wpłaty)
                $newId = $this->createPsOrder($row, $items, $paymentModule);
                
                if ($newId) {
                    $this->repo->updatePsOrderId($cfId, $newId);
                    return ['success' => true, 'action' => 'created', 'id_order' => $newId];
                }
            } catch (\Exception $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
        }

        // =========================================================
        // KROK 3: NAPRAWA I AKTUALIZACJA STATUSU
        // =========================================================
        if ($step === 'fix') {
            // Jeżeli zamówienie w Preście zostało usunięte ręcznie lub nie istnieje,
            // odtwarzamy je automatycznie zamiast kończyć błędem.
            if ($existingId <= 0) {
                try {
                    $paymentModule = $this->getPaymentModule();
                    if (!$paymentModule) {
                        return ['success' => false, 'message' => 'Brak modułu płatności.'];
                    }

                    $newId = $this->createPsOrder($row, $items, $paymentModule);
                    if (!$newId) {
                        return ['success' => false, 'message' => 'Nie udało się odtworzyć zamówienia PrestaShop.'];
                    }

                    $existingId = (int)$newId;
                    $this->repo->updatePsOrderId($cfId, $existingId);
                } catch (\Exception $e) {
                    return ['success' => false, 'message' => 'Błąd odtwarzania zamówienia: ' . $e->getMessage()];
                }
            }

            if (method_exists('Cache', 'clean')) Cache::clean('Order::*');
            if (method_exists('Order', 'clearStaticCache')) Order::clearStaticCache();

            // 1. Sprawdzamy, czy zamówienie jest ANULOWANE
            if ($row['status'] === 'CANCELLED') {
                // Jeśli tak -> ustawiamy status ANULOWANE w Preście
                $this->setCancelledStatus($existingId);
                $action = 'cancelled';
            } else {
                // Jeśli nie (czyli READY lub FILLED_IN) -> Naprawiamy ceny
                $this->fixOrderData($existingId, $row, $items);
                
                // Jeśli status to READY_FOR_PROCESSING (czyli kasa jest) -> ustaw OPŁACONE
                if ($row['status'] === 'READY_FOR_PROCESSING' || $row['status'] === 'BOUGHT') {
                    $this->setFinalStatus($existingId);
                }
                
                $action = 'fixed';
            }

            // 2. Oznaczamy w bazie jako "Obsłużone"
            $this->repo->markAsFinished($cfId);

            return ['success' => true, 'action' => $action, 'id_order' => $existingId];
        }

        return ['success' => false, 'message' => 'Nieznany krok.'];
    }

    // --- POMOCNICZE ---

    private function resolveExistingPsOrderId(array $row, string $cfId): int
    {
        $idFromRow = isset($row['id_order_prestashop']) ? (int)$row['id_order_prestashop'] : 0;
        if ($this->isValidPsOrderId($idFromRow)) {
            return $idFromRow;
        }

        $idFromPayment = (int)Db::getInstance()->getValue(
            "SELECT o.id_order
             FROM "._DB_PREFIX_."orders o
             INNER JOIN "._DB_PREFIX_."order_payment op ON o.reference = op.order_reference
             WHERE op.transaction_id = '".pSQL($cfId)."'
             ORDER BY o.id_order DESC"
        );

        if ($this->isValidPsOrderId($idFromPayment)) {
            return $idFromPayment;
        }

        return 0;
    }

    private function isValidPsOrderId(int $idOrder): bool
    {
        if ($idOrder <= 0) {
            return false;
        }

        $order = new Order($idOrder);
        return Validate::isLoadedObject($order);
    }

    private function getPaymentModule()
    {
        $mod = \Module::getInstanceByName('ps_wirepayment');
        if (!$mod) $mod = \Module::getInstanceByName('bankwire');
        if (!$mod) {
            $m = \PaymentModule::getInstalledPaymentModules();
            if ($m) $mod = \Module::getInstanceByName($m[0]['name']);
        }
        return $mod;
    }

    private function createPsOrder($orderRow, $items, $paymentModule)
    {
        $buyerData = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order_buyer WHERE checkout_form_id = '".pSQL($orderRow['checkout_form_id'])."'");
        if (!$buyerData) throw new \Exception("Brak danych buyer.");

        $email = $buyerData['email'];
        if (!Validate::isEmail($email)) $email = 'allegro_'.uniqid().'@temp.pl';

        $customer = new Customer();
        $customer->getByEmail($email);
        if (!Validate::isLoadedObject($customer)) {
            $customer->email = $email;
            
            $fName = $this->cleanName($buyerData['firstname']);
            $lName = $this->cleanName($buyerData['lastname']);
            
            if (empty($fName)) $fName = 'Klient';
            if (empty($lName)) $lName = 'Allegro';

            $customer->firstname = $fName;
            $customer->lastname = $lName;
            $customer->passwd = md5(time()._COOKIE_KEY_);
            $customer->is_guest = 1;
            $customer->active = 1;
            if (!$customer->add()) throw new \Exception("Błąd tworzenia klienta.");
        }

        $shipData = Db::getInstance()->getRow("SELECT * FROM "._DB_PREFIX_."allegropro_order_shipping WHERE checkout_form_id = '".pSQL($orderRow['checkout_form_id'])."'");
        $address = new Address();
        $address->id_customer = $customer->id;
        
        $fullShipName = trim($shipData['addr_name'] ?? '');
        $parts = explode(' ', $fullShipName, 2);
        $sFirst = $this->cleanName($parts[0] ?? '');
        $sLast = $this->cleanName($parts[1] ?? '');
        
        if (empty($sFirst)) $sFirst = $customer->firstname; 
        if (empty($sLast)) $sLast = $customer->lastname;

        $address->firstname = $sFirst;
        $address->lastname = $sLast;
        $address->address1 = $shipData['addr_street'] ?: 'Brak ulicy';
        $address->city = $shipData['addr_city'] ?: 'Nieznane';
        $address->postcode = $shipData['addr_zip'];
        $address->phone = preg_replace('/[^0-9]/', '', $shipData['addr_phone'] ?? '000000000');
        $address->id_country = Country::getByIso($shipData['addr_country'] ?? 'PL');
        if (!$address->id_country) $address->id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
        $address->alias = 'Allegro ' . substr($orderRow['checkout_form_id'], 0, 8);
        $address->save();

        $cart = new Cart();
        $cart->id_customer = $customer->id;
        $cart->id_address_delivery = $address->id;
        $cart->id_address_invoice = $address->id;
        $cart->id_lang = Context::getContext()->language->id;
        
        $currencyIso = $orderRow['currency'] ?: 'PLN';
        $idCurrency = (int)Currency::getIdByIsoCode($currencyIso);
        if (!$idCurrency) $idCurrency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
        $cart->id_currency = $idCurrency;
        
        $genericCarrierId = (int)Configuration::get('ALLEGROPRO_CARRIER_ID');
        if (!$genericCarrierId) $genericCarrierId = (int)Configuration::get('PS_CARRIER_DEFAULT');
        $cart->id_carrier = $genericCarrierId;
        
        if (!$cart->add()) throw new \Exception("Błąd koszyka.");

        foreach ($items as $item) {
            $qty = (int)$item['quantity'];
            $prodId = (int)$item['id_product'];
            $attrId = (int)$item['id_product_attribute'];
            $res = $cart->updateQty($qty, $prodId, $attrId, false, 'up', 0, null, true, true);
            
            if ($res !== true) {
                $exists = Db::getInstance()->getValue("SELECT quantity FROM "._DB_PREFIX_."cart_product WHERE id_cart=".(int)$cart->id." AND id_product=".$prodId." AND id_product_attribute=".$attrId);
                if ($exists) {
                    Db::getInstance()->execute("UPDATE "._DB_PREFIX_."cart_product SET quantity = quantity + $qty WHERE id_cart=".(int)$cart->id." AND id_product=".$prodId." AND id_product_attribute=".$attrId);
                } else {
                    Db::getInstance()->insert('cart_product', [
                        'id_cart' => (int)$cart->id, 'id_product' => $prodId, 'id_product_attribute' => $attrId,
                        'id_shop' => (int)Context::getContext()->shop->id, 'quantity' => $qty, 'date_add' => date('Y-m-d H:i:s')
                    ]);
                }
                $cart->update();
            }
        }

        $cart->setDeliveryOption([(int)$address->id => (int)$cart->id_carrier . ',']);
        $cart->update();

        // --- ZMIANA: Wybór statusu na podstawie danych z Allegro ---
        $initialStatus = (int)Configuration::get('ALLEGROPRO_OS_PROCESSING'); // Domyślnie PRZETWARZANIE
        
        if ($orderRow['status'] === 'FILLED_IN') {
            $noPayStatus = (int)Configuration::get('ALLEGROPRO_OS_NO_PAYMENT');
            if ($noPayStatus) {
                $initialStatus = $noPayStatus;
            }
        }
        
        if (!$initialStatus) $initialStatus = (int)Configuration::get('PS_OS_PREPARATION');

        $paymentModule->validateOrder(
            (int)$cart->id, 
            $initialStatus, 
            (float)$orderRow['total_amount'], 
            'Allegro', 
            NULL, 
            ['transaction_id' => $orderRow['checkout_form_id']], 
            (int)$cart->id_currency, 
            false, 
            $customer->secure_key
        );

        return $paymentModule->currentOrder;
    }

    private function fixOrderData($id_order, $orderRow, $items)
    {
        $orderDetails = OrderDetail::getList($id_order);
        $realProductsTotalGross = 0.00; 

        foreach ($orderDetails as $detail) {
            $detailObj = new OrderDetail($detail['id_order_detail']);
            $matchedItem = null;
            foreach ($items as $item) {
                if ($item['id_product'] == $detailObj->product_id && $item['id_product_attribute'] == $detailObj->product_attribute_id) {
                    $matchedItem = $item;
                    break;
                }
            }

            if ($matchedItem) {
                $priceGross = (float)$matchedItem['price']; 
                $quantity = (int)$matchedItem['quantity'];
                $totalGross = $priceGross * $quantity;
                $taxRate = $detailObj->tax_rate; 
                $priceNet = $priceGross / (1 + ($taxRate / 100));
                $totalNet = $totalGross / (1 + ($taxRate / 100));

                Db::getInstance()->execute("UPDATE "._DB_PREFIX_."order_detail SET 
                    product_price = $priceNet,
                    unit_price_tax_incl = $priceGross,
                    unit_price_tax_excl = $priceNet,
                    total_price_tax_incl = $totalGross,
                    total_price_tax_excl = $totalNet,
                    original_product_price = $priceGross,
                    product_name = '".pSQL($matchedItem['name'])."' 
                    WHERE id_order_detail = ".(int)$detailObj->id);
                
                $realProductsTotalGross += $totalGross;
            }
        }

        $totalPaidAllegro = (float)$orderRow['total_amount'];
        $shippingGross = $totalPaidAllegro - $realProductsTotalGross;
        if ($shippingGross < 0) {
            $shippingGross = 0.00;
        }

        $productsTotalNet = 0.00;
        foreach ($orderDetails as $detail) {
            $productsTotalNet += (float)$detail['total_price_tax_excl'];
        }

        $effectiveTaxMultiplier = 1.0;
        if ($productsTotalNet > 0.0 && $realProductsTotalGross >= $productsTotalNet) {
            $effectiveTaxMultiplier = $realProductsTotalGross / $productsTotalNet;
        }

        if ($effectiveTaxMultiplier < 1.0) {
            $effectiveTaxMultiplier = 1.0;
        }

        $shippingNet = $shippingGross / $effectiveTaxMultiplier;
        if ($shippingNet > $shippingGross) {
            $shippingNet = $shippingGross;
        }

        $totalPaidNet = max(0.00, $productsTotalNet + $shippingNet);

        // 1. POBIERZ ID TECHNICZNEGO PRZEWOŹNIKA "WYSYŁKA ALLEGRO"
        $targetCarrierId = (int)Configuration::get('ALLEGROPRO_CARRIER_ID');
        if (!$targetCarrierId) {
            $targetCarrierId = (int)Configuration::get('PS_CARRIER_DEFAULT');
        }

        // 2. AKTUALIZACJA GŁÓWNEJ TABELI ZAMÓWIEŃ
        Db::getInstance()->execute("UPDATE "._DB_PREFIX_."orders SET 
            total_paid = $totalPaidAllegro,
            total_paid_tax_incl = $totalPaidAllegro,
            total_paid_real = $totalPaidAllegro,
            total_products = " . (float)$productsTotalNet . ",
            total_products_wt = $realProductsTotalGross,
            total_shipping = $shippingGross,
            total_shipping_tax_incl = $shippingGross,
            total_shipping_tax_excl = $shippingNet,
            id_carrier = " . $targetCarrierId . ",
            payment = 'Allegro',
            module = 'allegropro'
            WHERE id_order = ".(int)$id_order);

        // 3. AKTUALIZACJA TABELI order_carrier
        $id_order_carrier = Db::getInstance()->getValue("SELECT id_order_carrier FROM "._DB_PREFIX_."order_carrier WHERE id_order = ".(int)$id_order);
        if ($id_order_carrier) {
            Db::getInstance()->execute("UPDATE "._DB_PREFIX_."order_carrier SET 
                shipping_cost_tax_incl = $shippingGross,
                shipping_cost_tax_excl = $shippingNet,
                id_carrier = " . $targetCarrierId . "
                WHERE id_order_carrier = ".(int)$id_order_carrier);
        }

        $id_invoice = Db::getInstance()->getValue("SELECT id_order_invoice FROM "._DB_PREFIX_."order_invoice WHERE id_order = ".(int)$id_order);
        if ($id_invoice) {
            Db::getInstance()->execute("UPDATE "._DB_PREFIX_."order_invoice SET 
                total_paid_tax_incl = $totalPaidAllegro,
                total_paid_tax_excl = " . (float)$totalPaidNet . ",
                total_products = " . (float)$productsTotalNet . ",
                total_products_wt = $realProductsTotalGross,
                total_shipping_tax_incl = $shippingGross,
                total_shipping_tax_excl = $shippingNet
                WHERE id_order_invoice = ".(int)$id_invoice);
        }

        $orderObj = new Order($id_order);
        $ref = $orderObj->reference;
        Db::getInstance()->delete('order_payment', "order_reference = '$ref'");
        Db::getInstance()->insert('order_payment', [
            'order_reference' => $ref,
            'id_currency' => (int)$orderObj->id_currency,
            'amount' => $totalPaidAllegro,
            'payment_method' => 'Allegro',
            'date_add' => date('Y-m-d H:i:s'),
            'transaction_id' => pSQL($orderRow['checkout_form_id']),
            'card_number' => '', 'card_brand' => '', 'card_expiration' => '', 'card_holder' => ''
        ]);
    }

    private function setCancelledStatus($id_order)
    {
        $orderObj = new Order($id_order);
        $targetStatus = (int)Configuration::get('ALLEGROPRO_OS_CANCELLED');
        
        if ($targetStatus && $orderObj->current_state != $targetStatus) {
            $history = new OrderHistory();
            $history->id_order = $id_order;
            $history->changeIdOrderState($targetStatus, $id_order);
            $history->addWithemail();
        }
    }

    private function setFinalStatus($id_order)
    {
        $orderObj = new Order($id_order);
        $targetStatus = (int)Configuration::get('ALLEGROPRO_OS_PAID');
        
        if ($targetStatus && $orderObj->current_state != $targetStatus) {
            $history = new OrderHistory();
            $history->id_order = $id_order;
            $history->changeIdOrderState($targetStatus, $id_order);
            $history->addWithemail();
        }
    }

    private function cleanName($str) {
        $clean = preg_replace('/[^a-zA-Z0-9 \-\p{L}]/u', '', $str);
        return trim(substr($clean, 0, 32));
    }
}
