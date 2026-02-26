<?php
/**
 * 2007-2023 PrestaShop
 *
 * Kontroler strony do podglądu cen historycznych modułu Historia Ceny Omnibus.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Dołączamy serwis, ponieważ będzie on teraz aktywnie używany do pobierania danych.
require_once _PS_MODULE_DIR_ . 'omnibuspricehistory/services/OmnibusPriceHistoryService.php';

// Dodajemy potrzebne klasy PrestaShop, jeśli nie zostały wcześniej załadowane w głównym pliku modułu.
// Zazwyczaj te klasy są już dostępne, ale ich jawne dołączenie zapewnia kompatybilność.
// Jeśli masz je już w omnibuspricehistory.php, nie ma potrzeby duplikowania.
// require_once _PS_CORE_DIR_ . '/classes/Product.php';
// require_once _PS_CORE_DIR_ . '/classes/Image.php';
// require_once _PS_CORE_DIR_ . '/classes/Group.php'; // Potrzebne dla Group::getCurrent()

class AdminOmnibusPriceHistoryDebugController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->toolbar_title = $this->l('Podgląd cen historycznych produktów');
    }

    /**
     * Inicjalizacja i renderowanie widoku.
     */
    public function initContent()
    {
        parent::initContent();

        $omnibusService = new OmnibusPriceHistoryService($this->module);
        $id_product = (int)Tools::getValue('id_product');
        $product_selected = false;
        $product_info = [];
        $price_history = [];

        // Logowanie dla debugowania (można usunąć po zakończeniu prac)
        PrestaShopLogger::addLog('DebugController: initContent - id_product z URL: ' . $id_product, 1, null, 'OmnibusPriceHistory');

        if ($id_product > 0) {
            $product = new Product($id_product, false, $this->context->language->id);
            if (Validate::isLoadedObject($product)) {
                $product_selected = true;
                $cover = Image::getCover($id_product);
                $product_info = [
                    'id_product' => $product->id,
                    'name'       => $product->name,
                    'reference'  => $product->reference,
                    // Sprawdzamy, czy obrazek istnieje, aby uniknąć błędów
                    'image_url'  => $this->context->link->getImageLink($product->link_rewrite, (isset($cover['id_image']) ? $cover['id_image'] : 'default'), 'small_default'),
                    'current_price' => Tools::displayPrice(Product::getPriceStatic($id_product)),
                ];
                PrestaShopLogger::addLog('DebugController: Pobrana informacja o produkcie: ' . $product->name, 1, null, 'OmnibusPriceHistory', $id_product);

                // Pobieranie historii cen dla wybranego produktu
                $price_history = $omnibusService->getProductPriceHistory(
                    $id_product,
                    null, // id_product_attribute (null oznacza wszystkie lub prosty produkt)
                    (int)$this->context->shop->id,
                    (int)$this->context->currency->id,
                    // Warunki dla kraju i grupy, zgodne z konfiguracją modułu
                    (bool)Configuration::get('OMNIBUS_INDEX_COUNTRIES') ? (int)$this->context->country->id : 0,
                    (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS') ? (int)Group::getCurrent()->id : 0
                );
                PrestaShopLogger::addLog('DebugController: Pobrana historia: ' . count($price_history) . ' wpisów.', 1, null, 'OmnibusPriceHistory', $id_product);

            } else {
                PrestaShopLogger::addLog('DebugController: Nie można załadować obiektu produktu dla ID: ' . $id_product, 3, null, 'OmnibusPriceHistory', $id_product);
            }
        }
        
        // ZMODYFIKOWANE ZAPYTANIE SQL - POBIERA TYLKO PRODUKTY Z HISTORIĄ CEN
        $products_for_select = Db::getInstance()->executeS('
            SELECT DISTINCT p.id_product, pl.name, p.reference
            FROM `'._DB_PREFIX_.'product` p
            '.Shop::addSqlAssociation('product', 'p').'
            LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.id_product = pl.id_product AND pl.id_lang = '.(int)$this->context->language->id.')
            INNER JOIN `'._DB_PREFIX_.'omnibus_price_history` oph ON (p.id_product = oph.id_product AND oph.id_shop = '.(int)$this->context->shop->id.')
            GROUP BY p.id_product, pl.name, p.reference
            ORDER BY pl.name ASC
            LIMIT 50
        ');
        
        // Generujemy token administracyjny dla bieżącego kontrolera
        $admin_token = Tools::getAdminTokenLite('AdminOmnibusPriceHistoryDebug');
        
        // Generujemy bazowy URL do tego kontrolera (bez parametrów id_product itp.)
        $base_admin_url = $this->context->link->getAdminLink('AdminOmnibusPriceHistoryDebug');

        // Przypisujemy zmienne do Smarty
        $this->context->smarty->assign([
            'product_selected' => $product_selected,
            'product_info' => $product_info,
            'price_history' => $price_history,
            'products_for_select' => $products_for_select,
            'current_selected_product_id' => $id_product,
            'base_admin_url' => $base_admin_url, // Podstawowy URL strony
            'admin_token' => $admin_token,       // Token zabezpieczający
        ]);

        // Ustawiamy szablon do wyświetlenia
        $this->setTemplate('debug_history.tpl');
    }
    
    /**
     * Metoda do obsługi zapytań AJAX w celu wyszukiwania produktów.
     * Ta metoda nie jest używana w uproszczonej wersji z prostą listą rozwijaną,
     * ale jest zachowana, jeśli w przyszłości zdecydujesz się wrócić do Select2.
     */
    public function ajaxProcessSearchProducts()
    {
        $query = Tools::getValue('q', false);
        if (!$query || strlen($query) < 2) {
            die(json_encode(['results' => []]));
        }

        $sql = new DbQuery();
        $sql->select('p.`id_product`, pl.`name`, p.`reference`');
        $sql->from('product', 'p');
        $sql->join(Shop::addSqlAssociation('product', 'p'));
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . Shop::addSqlRestrictionOnLang('pl'));
        $sql->where('pl.name LIKE \'%' . pSQL($query) . '%\' OR p.reference LIKE \'%' . pSQL($query) . '%\' OR p.id_product = '.(int)$query);
        $sql->groupBy('p.id_product')->limit(20);

        $items = Db::getInstance()->executeS($sql);
        $results = [];
        if ($items) {
            foreach ($items as $item) {
                $results[] = [
                    'id' => (int)$item['id_product'],
                    'text' => $item['name'] . ($item['reference'] ? ' (ref: ' . $item['reference'] . ')' : ''),
                ];
            }
        }

        die(json_encode(['results' => $results]));
    }
}