<?php
/**
 * Kontroler Info Page - Optimized Source Detection
 *
 * Zmiana (PS 8.2.1): renderowanie standalone.
 * order_info.tpl zawiera pełny dokument HTML, więc nie może być wstrzykiwany w layout FO.
 */

require_once _PS_MODULE_DIR_ . 'bb_ordermanager/classes/BbOrderManagerFolderStates.php';

class Bb_ordermanagerInfoModuleFrontController extends ModuleFrontController
{
    public $auth = false;
    public $guestAllowed = true;

    // Standalone (bez layoutu motywu)
    public $display_header = false;
    public $display_footer = false;
    public $content_only = true;

    /**
     * Pobierz mapę folderów -> ID statusów (konfiguracja / autoutworzenie).
     *
     * @return array<string,int>
     */
    private function getFolderStateMap()
    {
        $idLang = 0;
        if (isset($this->context) && isset($this->context->language) && (int) $this->context->language->id > 0) {
            $idLang = (int) $this->context->language->id;
        }
        return BbOrderManagerFolderStates::getMap($idLang);
    }

    /**
     * Statusy uznawane za "opłacone" w kontekście strony Info (dla rabatów).
     * Zawiera statusy core PrestaShop + statusy folderów z Managera.
     *
     * @return int[]
     */
    private function getPaidStatuses()
    {
        $map = $this->getFolderStateMap();

        $paid = [
            2, 12, // Płatność zaakceptowana (Standard + Zdalna)
            3, 9, 11, 15, 20, 21, // W realizacji (core)
            4, 5, // Wysłane / Doręczone (core)
        ];

        // Foldery/Statusy używane przez Manager (wcześniej były to sztywne ID)
        $paidFolderNames = [
            'Nowe (Do zamówienia)',
            'Odbiór osobisty',
            'Dostawa do klienta',
            'Dostawa: JUTRO',
            'Dostawa: POJUTRZE',
            'Czeka na brakujący towar',
            'MAGAZYN (Własne)',
            'BP',
            'BP(1 poz) - Szybkie',
            'EKOWITAL',
            'EKOWITAL(1 poz)',
            'BP + EKOWITAL',
            'BP + EKO <10',
            'NATURA',
            'STEWIARNIA',
            'MIX',
            'MIX < 10',
            'Spakowane / Gotowe',
            'Wysłane (Historia)',
        ];

        foreach ($paidFolderNames as $fname) {
            if (!empty($map[$fname])) {
                $paid[] = (int) $map[$fname];
            }
        }

        $paid = array_values(array_unique(array_filter(array_map('intval', $paid))));
        return $paid;
    }

public function initContent()
    {
        if (Tools::getValue('action') === 'generate_discount') {
            $this->ajaxGenerateDiscount();
            return;
        }

        parent::initContent();

        $id_order = (int)Tools::getValue('id_order');
        $token = Tools::getValue('token');

        if (!$id_order || !$token) Tools::redirect('index.php');

        $db = Db::getInstance();
        $order = $db->getRow('SELECT o.*, c.firstname, c.lastname, c.email FROM `' . _DB_PREFIX_ . 'orders` o LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON o.id_customer = c.id_customer WHERE o.id_order = ' . $id_order);

        if (!$order) Tools::redirect('index.php');
        if ($token !== md5($order['reference'] . $order['secure_key'])) Tools::redirect('index.php');

        $shopUrl = Tools::getShopDomainSsl(true) . __PS_BASE_URI__;

        $products = $db->executeS('SELECT product_id, product_name, product_quantity, product_price, total_price_tax_incl FROM `' . _DB_PREFIX_ . 'order_detail` WHERE id_order = ' . $id_order);
        foreach ($products as &$p) {
            $imgId = $db->getValue('SELECT id_image FROM `' . _DB_PREFIX_ . 'image` WHERE id_product = ' . (int)$p['product_id'] . ' AND cover = 1');
            $p['image_url'] = $imgId ? $shopUrl . 'img/p/' . implode('/', str_split((string)$imgId)) . '/' . $imgId . '-small_default.jpg' : null;
        }

        $tracking = $db->getValue('SELECT tracking_number FROM `' . _DB_PREFIX_ . 'order_carrier` WHERE id_order = ' . $id_order);
        $address = $db->getRow('SELECT address1, postcode, city, other FROM `' . _DB_PREFIX_ . 'address` WHERE id_address = ' . (int)$order['id_address_delivery']);
        $carrierName = $db->getValue('SELECT name FROM `' . _DB_PREFIX_ . 'carrier` WHERE id_carrier = ' . (int)$order['id_carrier']);
        $logoUrl = $shopUrl . 'img/' . Configuration::get('PS_LOGO');

        // --- ZMIANA: Wybór Integracji na podstawie źródła zamówienia ---
        $pickupPoint = null;
        $orderModule = isset($order['module']) ? $order['module'] : '';

        // Ładowanie klas
        $pathX13 = _PS_MODULE_DIR_ . 'bb_ordermanager/integrations/AllegroX13.php';
        $pathPro = _PS_MODULE_DIR_ . 'bb_ordermanager/integrations/AllegroPro.php';
        if (file_exists($pathX13)) require_once $pathX13;
        if (file_exists($pathPro)) require_once $pathPro;

        // PRZYPADEK A: Moduł AllegroPro
        if ($orderModule === 'allegropro' && class_exists('BbAllegroPro')) {
            $proData = BbAllegroPro::getDeliveryInfo($id_order);
            if ($proData && $proData['point_id']) {
                $pickupPoint = [
                    'id' => $proData['point_id'],
                    'name' => $proData['point_name'] ?? 'Punkt Odbioru',
                    'address' => $proData['point_addr'] ?? ''
                ];
            }
        }

        // PRZYPADEK B: Moduł X13 Allegro
        elseif ($orderModule === 'x13allegro' && class_exists('BbAllegroX13')) {
            $x13Data = BbAllegroX13::getDeliveryInfo($id_order);
            if ($x13Data && $x13Data['point_id']) {
                $pickupPoint = [
                    'id' => $x13Data['point_id'],
                    'name' => $x13Data['point_name'] ?? 'Punkt Odbioru',
                    'address' => $x13Data['point_addr'] ?? ''
                ];
            }
        }

        // PRZYPADEK C: Fallback (Standardowy Adres PrestaShop)
        if (!$pickupPoint && !empty($address['other'])) {
            if (preg_match('/[A-Z0-9]{5,7}/', $address['other'], $matches)) {
                $pickupPoint = ['id' => $matches[0], 'name' => 'Punkt Odbioru', 'address' => $address['other']];
            }
        }
        // ---------------------------------------------

        // --- ETAPY ZAMÓWIENIA ---
        $step = 1;
        $desc = "Zamówienie przyjęte do systemu.";
        $s = (int)$order['current_state'];
        $hoursSinceUpdate = (time() - strtotime($order['date_upd'])) / 3600;

        // Statusy folderów (bez sztywnych ID)
        $folderMap = $this->getFolderStateMap();
        $idNowe = (int)($folderMap['Nowe (Do zamówienia)'] ?? 0);
        $idNoPay = (int)($folderMap['Nieopłacone'] ?? 0);
        $idExplain = (int)($folderMap['Do wyjaśnienia'] ?? 0);
        $idPickup = (int)($folderMap['Odbiór osobisty'] ?? 0);
        $idDeliveryClient = (int)($folderMap['Dostawa do klienta'] ?? 0);

        $idTomorrow = (int)($folderMap['Dostawa: JUTRO'] ?? 0);
        $idAfterTomorrow = (int)($folderMap['Dostawa: POJUTRZE'] ?? 0);
        $idWaitMissing = (int)($folderMap['Czeka na brakujący towar'] ?? 0);

        $packingIds = array_values(array_filter([
            (int)($folderMap['MAGAZYN (Własne)'] ?? 0),
            (int)($folderMap['BP'] ?? 0),
            (int)($folderMap['BP(1 poz) - Szybkie'] ?? 0),
            (int)($folderMap['EKOWITAL'] ?? 0),
            (int)($folderMap['EKOWITAL(1 poz)'] ?? 0),
            (int)($folderMap['BP + EKOWITAL'] ?? 0),
            (int)($folderMap['BP + EKO <10'] ?? 0),
            (int)($folderMap['NATURA'] ?? 0),
            (int)($folderMap['STEWIARNIA'] ?? 0),
            (int)($folderMap['MIX'] ?? 0),
            (int)($folderMap['MIX < 10'] ?? 0),
        ]));

        $idPackedReady = (int)($folderMap['Spakowane / Gotowe'] ?? 0);
        $idShippedHistory = (int)($folderMap['Wysłane (Historia)'] ?? 0);

        $idCancelClient = (int)($folderMap['Anulowane (Klient)'] ?? 0);
        $idCancelShop = (int)($folderMap['Anulowane (Sklep)'] ?? 0);

        // ETAP 1: zamówienie wstępne / oczekuje na wpłatę / do wyjaśnienia
        $stage1Statuses = array_values(array_filter([1, 2, 10, 11, 12, $idNowe, $idNoPay, $idExplain, $idPickup, $idDeliveryClient]));
        if (in_array($s, $stage1Statuses)) {
            $step = 1;

            $paidStage1 = array_values(array_filter([2, 12, $idNowe, $idPickup, $idDeliveryClient]));
            if (in_array($s, $paidStage1)) {
                $desc = "Płatność potwierdzona. Kompletujemy zamówienie.";
            } elseif ($s == $idNoPay || $s == 10) {
                $desc = "Oczekujemy na zaksięgowanie wpłaty.";
            } else {
                $desc = "Zamówienie wpłynęło do systemu.";
            }
        }

        // ETAP 2: w realizacji / przygotowanie
        $stage2Statuses = array_values(array_filter([3, 9, 15, 20, 21, $idTomorrow, $idAfterTomorrow, $idWaitMissing]));
        if (in_array($s, $stage2Statuses)) {
            $step = 2;
            $desc = "Rozpoczęliśmy realizację Twojego zamówienia.";
        }

        // ETAP 3: pakowanie (foldery magazynowe)
        if (!empty($packingIds) && in_array($s, $packingIds)) {
            $step = 3;
            $desc = "Twoje produkty są właśnie pakowane.";
        }

        // ETAP 4/5: wysyłka
        $stage4Statuses = array_values(array_filter([4, $idShippedHistory, $idPackedReady]));
        if (in_array($s, $stage4Statuses)) {
            $step = 4;
            $desc = "Paczka jest gotowa i czeka na odbiór.";
            $stage5Statuses = array_values(array_filter([4, $idShippedHistory]));
            if (in_array($s, $stage5Statuses) && $hoursSinceUpdate >= 2) {
                $step = 5;
                $desc = "Przesyłka została odebrana przez kuriera.";
            }
        }

        // Anulowane
        $cancelStatuses = array_values(array_filter([6, 7, 8, 22, $idCancelClient, $idCancelShop]));
        $isCancelled = in_array($s, $cancelStatuses);
        if ($isCancelled) $desc = "Zamówienie zostało anulowane.";

        // --- LOGIKA RABATOWA ---
        $discount_code = null;
        $days_left = 0;
        $reduction_percent = 0;
        $is_paid = false;
        $is_allegro = false;

        if (preg_match('/@allegromail\.pl$/', $order['email'])) {
            $is_allegro = true;
        }

        $paidStatuses = $this->getPaidStatuses();
        if (in_array($s, $paidStatuses)) {
            $is_paid = true;
            $uniqueDesc = 'Auto-Rabat-' . $order['reference'];
            $existingCodeRow = $db->getRow("SELECT code, date_to, reduction_percent FROM `" . _DB_PREFIX_ . "cart_rule` WHERE description = '$uniqueDesc' AND active = 1");

            if ($existingCodeRow) {
                $discount_code = $existingCodeRow['code'];
                $reduction_percent = (float)$existingCodeRow['reduction_percent'];
                $diff = strtotime($existingCodeRow['date_to']) - time();
                $days_left = ceil($diff / (60 * 60 * 24));
                if ($days_left < 0) $days_left = 0;
            } else {
                $reduction_percent = $is_allegro ? 5 : 2;
            }
        }

        $this->context->smarty->assign([
            'order' => $order, 'products' => $products, 'status_desc' => $desc, 'tracking' => $tracking, 'address' => $address, 'pickup_point' => $pickupPoint, 'carrier_name' => $carrierName, 'current_step' => $step, 'is_cancelled' => $isCancelled, 'shop_name' => Configuration::get('PS_SHOP_NAME'), 'shop_url' => $shopUrl, 'logo_url' => $logoUrl,
            'is_paid' => $is_paid,
            'is_allegro' => $is_allegro,
            'discount_code' => $discount_code,
            'reduction_percent' => $reduction_percent,
            'days_left' => $days_left,
            'ajax_url' => $shopUrl . 'index.php?fc=module&module=bb_ordermanager&controller=info'
        ]);

        // Standalone output
        echo $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'bb_ordermanager/views/templates/front/order_info.tpl');
        exit;
    }

    /**
     * API: GENEROWANIE KODU
     */
    protected function ajaxGenerateDiscount()
    {
        header('Content-Type: application/json');
        $db = Db::getInstance();
        $id_order = (int)Tools::getValue('id_order');
        $token = Tools::getValue('token');

        if (!$id_order || !$token) { echo json_encode(['success' => false, 'error' => 'Brak danych']); die(); }

        $order = $db->getRow('SELECT o.reference, o.secure_key, o.id_customer, o.id_currency, o.current_state, c.email FROM `' . _DB_PREFIX_ . 'orders` o LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON o.id_customer = c.id_customer WHERE o.id_order = ' . $id_order);

        if (!$order) { echo json_encode(['success' => false, 'error' => 'Brak zam.']); die(); }
        if ($token !== md5($order['reference'] . $order['secure_key'])) { echo json_encode(['success' => false, 'error' => 'Token error']); die(); }

        // 1. Walidacja Płatności (Uwzględnia mapowanie folderów)
        $paidStatuses = $this->getPaidStatuses();
        if (!in_array((int)$order['current_state'], $paidStatuses)) {
            echo json_encode(['success' => false, 'error' => 'Zamówienie nie jest opłacone.']);
            die();
        }

        // 2. Walidacja Duplikatu
        $uniqueDesc = 'Auto-Rabat-' . $order['reference'];
        $existingCode = $db->getValue("SELECT code FROM `" . _DB_PREFIX_ . "cart_rule` WHERE description = '$uniqueDesc'");
        if ($existingCode) {
            echo json_encode(['success' => true, 'code' => $existingCode, 'message' => 'Kod już istnieje']);
            die();
        }

        // 3. Wysokość Rabatu
        $percent = 2.00;
        if (preg_match('/@allegromail\.pl$/', $order['email'])) {
            $percent = 5.00;
        }

        // 4. Insert
        $code = 'RABAT' . (int)$percent . '-' . strtoupper(substr(md5(uniqid() . $order['reference']), 0, 5));
        $dateFrom = date('Y-m-d H:i:s');
        $dateTo = date('Y-m-d H:i:s', strtotime('+30 days'));

        $sql = "INSERT INTO `" . _DB_PREFIX_ . "cart_rule` (
            id_customer, date_from, date_to, description, quantity, quantity_per_user, priority, partial_use, code,
            minimum_amount, minimum_amount_tax, minimum_amount_currency, minimum_amount_shipping,
            country_restriction, carrier_restriction, group_restriction, cart_rule_restriction, product_restriction, shop_restriction,
            free_shipping, reduction_percent, reduction_amount, reduction_tax, reduction_currency, reduction_product, reduction_exclude_special,
            gift_product, gift_product_attribute, highlight, active, date_add, date_upd
        ) VALUES (
            ".(int)$order['id_customer'].", '$dateFrom', '$dateTo', '$uniqueDesc', 1, 1, 1, 0, '$code',
            0.00, 0, ".(int)$order['id_currency'].", 0,
            0, 0, 0, 0, 0, 0,
            0, $percent, 0.00, 1, ".(int)$order['id_currency'].", 0, 1,
            0, 0, 0, 1, NOW(), NOW()
        )";

        if ($db->execute($sql)) {
            $id_cart_rule = $db->Insert_ID();
            $languages = Language::getLanguages(false);
            foreach ($languages as $lang) {
                $name = pSQL('Rabat ' . (int)$percent . '% (' . $order['reference'] . ')');
                $db->execute("INSERT INTO `" . _DB_PREFIX_ . "cart_rule_lang` (id_cart_rule, id_lang, name) VALUES ($id_cart_rule, ".(int)$lang['id_lang'].", '$name')");
            }
            echo json_encode(['success' => true, 'code' => $code]);
        } else {
            echo json_encode(['success' => false, 'error' => 'SQL: ' . $db->getMsgError()]);
        }
        die();
    }
}
