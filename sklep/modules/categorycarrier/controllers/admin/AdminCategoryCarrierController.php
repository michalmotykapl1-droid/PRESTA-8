<?php
/**
 * Kontroler obsługujący stronę konfiguracji modułu
 */
class AdminCategoryCarrierController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view'; // Używamy szablonu .tpl
        parent::__construct();
        $this->meta_title = $this->l('Konfiguracja Przewoźników');
    }

    public function initContent()
    {
        parent::initContent();

        // Ładowanie CSS/JS Admina z podkatalogów
        $this->context->controller->addCSS($this->module->getPathUri() . 'views/css/admin/style.css');
        $this->context->controller->addJS($this->module->getPathUri() . 'views/js/admin/script.js');

        // Generowanie formularza
        $generatedForm = $this->renderMyForm();

        // Przekazanie zmiennych do Smarty (TPL)
        $this->context->smarty->assign(array(
            'form_content' => $generatedForm,
            'module_dir' => $this->module->getPathUri()
        ));

        // Ustawienie własnego szablonu konfiguracji modułu
        $this->setTemplate('configure.tpl');
    }

    /**
     * Obsługa formularza po kliknięciu Zapisz
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitCategoryCarrier')) {
            $categories = Tools::getValue('categoryBox');
            $carriers = Tools::getValue('carriers');
            $applyNow = Tools::getValue('apply_now');

            if (!$categories || !$carriers) {
                $this->errors[] = $this->l('Musisz wybrać przynajmniej jedną kategorię i jednego przewoźnika.');
            } else {
                // Pobranie obecnych reguł
                $currentRules = json_decode(Configuration::get('CATCARRIER_RULES'), true);
                if (!is_array($currentRules)) {
                    $currentRules = [];
                }

                foreach ($categories as $catId) {
                    // Zapisujemy: ID Kategorii => Tablica ID Przewoźników
                    $currentRules[$catId] = $carriers;

                    // Jeśli wybrano opcję masowej aktualizacji
                    if ($applyNow) {
                        $this->updateProductsInCategory((int)$catId, $carriers);
                    }
                }

                // Zapis do bazy konfiguracji
                Configuration::updateValue('CATCARRIER_RULES', json_encode($currentRules));
                $this->confirmations[] = $this->l('Ustawienia zostały zapisane.');
            }
        }
        parent::postProcess();
    }

    /**
     * Generator formularza HelperForm
     */
    protected function renderMyForm()
    {
        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->token = Tools::getAdminTokenLite('AdminCategoryCarrier');
        $helper->currentIndex = self::$currentIndex;
        $helper->submit_action = 'submitCategoryCarrier';

        // Pobranie listy przewoźników
        $carriers = Carrier::getCarriers($this->context->language->id, true, false, false, null, Carrier::ALL_CARRIERS);
        $carriersList = [];
        foreach ($carriers as $c) {
            $carriersList[] = [
                'id' => $c['id_reference'], // ID referencyjne jest kluczowe!
                'name' => $c['name'] . ' (ID Ref: ' . $c['id_reference'] . ')',
                'val' => $c['id_reference']
            ];
        }

                // Domyślne wartości pól formularza
        $helper->fields_value = [
            'categoryBox' => [],
            'carriers' => [],
            'apply_now' => 0,
        ];

$fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Dodaj regułę dostawy'),
                    'icon' => 'icon-truck'
                ],
                'input' => [
                    [
                        'type' => 'categories',
                        'label' => $this->l('Wybierz Kategorię'),
                        'name' => 'categoryBox',
                        'tree' => [
                            'id' => 'categories-tree',
                            'use_checkbox' => true,
                            'use_search' => true,
                            'selected_categories' => []
                        ],
                    ],
                    [
                        'type' => 'checkbox',
                        'label' => $this->l('Dostępni Przewoźnicy'),
                        'name' => 'carriers',
                        'values' => [
                            'query' => $carriersList,
                            'id' => 'id',
                            'name' => 'name'
                        ],
                        'desc' => $this->l('Zaznacz, którzy przewoźnicy mają być włączeni dla wybranych kategorii.')
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Zastosuj do starych produktów?'),
                        'name' => 'apply_now',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Tak')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->l('Nie')]
                        ],
                        'desc' => $this->l('UWAGA: Wybranie TAK nadpisze ustawienia dostawy dla wszystkich produktów znajdujących się już w tej kategorii.')
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Zapisz regułę'),
                ],
            ],
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Masowa aktualizacja SQL dla istniejących produktów
     */
    protected function updateProductsInCategory($catId, $carrierIds)
    {
        // 1. Pobierz produkty z kategorii
        $products = Db::getInstance()->executeS(
            'SELECT id_product FROM ' . _DB_PREFIX_ . 'category_product WHERE id_category = ' . (int)$catId
        );

        if (empty($products)) return;

        foreach ($products as $row) {
            $pId = (int)$row['id_product'];

            // 2. Usuń stare przypisania
            Db::getInstance()->delete(_DB_PREFIX_ . 'product_carrier', 'id_product = ' . $pId . ' AND id_shop = ' . (int) $this->context->shop->id);

            // 3. Dodaj nowe przypisania
            foreach ($carrierIds as $cId) {
                Db::getInstance()->insert(_DB_PREFIX_ . 'product_carrier', [
                    'id_product' => $pId,
                    'id_carrier_reference' => (int)$cId,
                    'id_shop' => (int)$this->context->shop->id
                ]);
            }
        }
    }
}