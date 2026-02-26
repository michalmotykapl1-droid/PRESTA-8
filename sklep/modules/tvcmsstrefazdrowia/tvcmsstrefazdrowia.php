<?php
/**
 * 2007-2025 PrestaShop
 * ThemeVolty - Strefa Zdrowia Module
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

if (file_exists(_PS_MODULE_DIR_ . 'tvcmstabproducts/tvcmstabproducts.php')) {
    include_once _PS_MODULE_DIR_ . 'tvcmstabproducts/tvcmstabproducts.php';
    if (file_exists(_PS_MODULE_DIR_ . 'tvcmstabproducts/classes/tvcmstabproducts_status.class.php')) {
        include_once _PS_MODULE_DIR_ . 'tvcmstabproducts/classes/tvcmstabproducts_status.class.php';
    }
}

class TvcmsStrefaZdrowia extends Module
{
    public $num_of_prod = 14;

    public function __construct()
    {
        $this->name = 'tvcmsstrefazdrowia';
        $this->tab = 'front_office_features';
        $this->version = '4.2.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Strefa zdrowia - Usługi i Landing Page');
        $this->description = $this->l('Wyświetla kafelki Strefa zdrowia oraz dedykowaną podstronę z usługami.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        // Ustawiamy domyślną kwotę dopłaty za dojazd (200 zł)
        Configuration::updateValue('STREFA_TRAVEL_FEE', 200);

        $this->_clearCache('*');

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('displayRightColumn')
            && $this->registerHook('displayWrapperTop')
            && $this->registerHook('displayContentWrapperTop')
            && $this->registerHook('displayHome')
            && $this->installPage();
    }

    public function uninstall()
    {
        Configuration::deleteByName('STREFA_TRAVEL_FEE');
        $this->_clearCache('*');
        return parent::uninstall();
    }

    /**
     * FORMULARZ KONFIGURACJI W PANELU ADMINA
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitStrefaConf')) {
            $travel_fee = (string)Tools::getValue('STREFA_TRAVEL_FEE');
            Configuration::updateValue('STREFA_TRAVEL_FEE', $travel_fee);
            $output .= $this->displayConfirmation($this->l('Ustawienia zapisane'));
        }

        return $output . $this->renderForm();
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Konfiguracja Cen - Strefa Zdrowia'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Kwota dopłaty za dojazd (PLN)'),
                        'name' => 'STREFA_TRAVEL_FEE',
                        'desc' => $this->l('Ta kwota zostanie doliczona do cen na stronie Fizjoterapii po wybraniu opcji "Dojazd do pacjenta".'),
                        'col' => 3,
                        'suffix' => 'zł'
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Zapisz'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitStrefaConf';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => [
                'STREFA_TRAVEL_FEE' => Configuration::get('STREFA_TRAVEL_FEE', 200),
            ],
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function installPage()
    {
        $page_name = 'module-' . $this->name . '-display';
        $meta = Meta::getMetaByPage($page_name, $this->context->language->id);

        if ((int)$meta['id_meta'] > 0) {
            return true;
        }

        $meta = new Meta();
        $meta->page = $page_name;
        $meta->configurable = 1;

        $languages = Language::getLanguages(false);
        foreach ($languages as $language) {
            $meta->title[$language['id_lang']] = 'Strefa Zdrowia & Równowagi';
            $meta->url_rewrite[$language['id_lang']] = 'strefa-zdrowia';
            $meta->description[$language['id_lang']] = 'Kompleksowa opieka specjalistów: Fizjoterapia, Naturopatia, Kosmetologia. Umów wizytę online.';
        }

        return $meta->save();
    }

    public function hookActionProductAdd($params) { $this->_clearCache('*'); }
    public function hookActionProductUpdate($params) { $this->_clearCache('*'); }
    public function hookActionProductDelete($params) { $this->_clearCache('*'); }
    public function hookActionOrderStatusPostUpdate($params) { $this->_clearCache('*'); }

    public function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        parent::_clearCache('tvcmsstrefazdrowia_display_home.tpl');
        parent::_clearCache('tvcmsstrefazdrowia_display_left.tpl');
    }

    public function getArrMainTitle($main_heading, $main_heading_data)
    {
        if (!$main_heading['main_title'] || empty($main_heading_data['title'])) { $main_heading['main_title'] = false; }
        if (!$main_heading['main_sub_title'] || empty($main_heading_data['short_desc'])) { $main_heading['main_sub_title'] = false; }
        if (!$main_heading['main_description'] || empty($main_heading_data['desc'])) { $main_heading['main_description'] = false; }
        if (!$main_heading['main_image'] || empty($main_heading_data['image'])) { $main_heading['main_image'] = false; }

        $main_heading['main_image_side'] = $main_heading_data['image_side'];
        $main_heading['main_image_status'] = $main_heading_data['image_status'];
        if (!$main_heading['main_left_title'] || empty($main_heading_data['left_title'])) { $main_heading['main_left_title'] = false; }
        if (!$main_heading['main_right_title'] || empty($main_heading_data['right_title'])) { $main_heading['main_right_title'] = false; }

        if (!$main_heading['main_title'] && !$main_heading['main_sub_title'] && !$main_heading['main_description'] && !$main_heading['main_image']) {
            $main_heading['main_status'] = false;
        }

        return $main_heading;
    }

    public function showFrontSideResult($num_of_prod = '', $hookName = 'home_status')
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;
        $disArrResult = [];
        $tv_obj_prod = new TvcmsTabProducts();
        
        $disArrResult['home_status'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_HOME');
        $disArrResult['left_status'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_LEFT');
        $disArrResult['right_status'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_RIGHT');
        $disArrResult['status'] = false;

        if ($disArrResult[$hookName]) {
            $disArrResult['data'] = $tv_obj_prod->displayNewProducts($num_of_prod);
            $disArrResult['status'] = empty($disArrResult['data']) ? false : true;
            $disArrResult['path'] = _MODULE_DIR_ . 'tvcmstabproducts/views/img/';
            $disArrResult['id_lang'] = $id_lang;
            $disArrResult['link'] = Context::getContext()->link->getPageLink('new-products');

            $tvcms_obj = new TvcmsTabProductsStatus();
            $all_prod_status = $tvcms_obj->fieldStatusInformation();
            $main_heading = [];
            $main_heading['main_status'] = $all_prod_status['new_prod']['main_status'];
            // (Skrócone przypisania - logika bez zmian)
            $main_heading['main_title'] = $all_prod_status['new_prod']['home_title'];
            $main_heading['main_sub_title'] = $all_prod_status['new_prod']['home_sub_title'];
            $main_heading['main_description'] = $all_prod_status['new_prod']['home_description'];
            $main_heading['main_image'] = $all_prod_status['new_prod']['home_image'];
            $main_heading['main_image_side'] = $all_prod_status['new_prod']['home_image_side'];
            $main_heading['main_image_status'] = $all_prod_status['new_prod']['home_image_status'];
            $main_heading['main_left_title'] = $all_prod_status['new_prod']['left_title'];
            $main_heading['main_right_title'] = $all_prod_status['new_prod']['right_title'];

            if ($main_heading['main_status']) {
                $main_heading_data = [];
                $main_heading_data['title'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE', $id_lang);
                $main_heading_data['short_desc'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE', $id_lang);
                $main_heading_data['desc'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC', $id_lang);
                $main_heading_data['image'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE', $id_lang);
                $main_heading_data['width'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH', $id_lang);
                $main_heading_data['height'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT', $id_lang);
                $main_heading_data['image_side'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_SIDE');
                $main_heading_data['image_status'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_STATUS');
                $main_heading_data['left_title'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE', $id_lang);
                $main_heading_data['right_title'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE', $id_lang);

                $main_heading = $this->getArrMainTitle($main_heading, $main_heading_data);
                $main_heading['data'] = $main_heading_data;
            }
            $this->context->smarty->assign('main_heading', $main_heading);
            $this->context->smarty->assign('dis_arr_result', $disArrResult);
        }
        return $disArrResult['status'];
    }

    public function hookdisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
    }

    public function hookdisplayHome()
    {
        // Przekazanie zmiennej dopłaty do widoków
        $this->context->smarty->assign([
            'strefa_travel_fee' => Configuration::get('STREFA_TRAVEL_FEE', 200)
        ]);

        if (!Cache::isStored('tvcmsstrefazdrowia_display_home.tpl')) {
            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            Cache::store('tvcmsstrefazdrowia_display_home.tpl', $output);
        }
        return Cache::retrieve('tvcmsstrefazdrowia_display_home.tpl');
    }

    public function hookdisplayWrapperTop() { return $this->hookdisplayHome(); }
    public function hookdisplayContentWrapperTop() { return $this->hookdisplayHome(); }

    public function hookdisplayLeftColumn()
    {
        if (!Cache::isStored('tvcmsstrefazdrowia_display_left.tpl')) {
            $output = $this->display(__FILE__, 'views/templates/front/display_left.tpl');
            Cache::store('tvcmsstrefazdrowia_display_left.tpl', $output);
        }
        return Cache::retrieve('tvcmsstrefazdrowia_display_left.tpl');
    }

    public function hookdisplayRightColumn() { return ''; }
}