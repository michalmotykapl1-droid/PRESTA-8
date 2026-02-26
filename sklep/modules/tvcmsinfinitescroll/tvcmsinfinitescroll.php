<?php
/**
 * 2007-2025 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2025 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class TvcmsInfiniteScroll extends Module
{
    private $html = '';

    private $post_errors = [];

    private $templateFile;

    public function __construct()
    {
        $this->name = 'tvcmsinfinitescroll';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Infinite Scroll');
        $this->description = $this->l('Show infinite scroll in product list page instead of the pagination.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');

        $this->templateFile = 'views/templates/front/' . $this->name . '_header.tpl';
    }

    public function install()
    {
        $this->installTab();
        Configuration::updateValue('TV_ACTIVE_CATEGORY', 1);
        Configuration::updateValue('TV_ACTIVE_NEW_PRODUCTS', 1);
        Configuration::updateValue('TV_ACTIVE_PRICES_DROP', 1);
        Configuration::updateValue('TV_ACTIVE_BEST_SALES', 1);
        Configuration::updateValue('TV_ACTIVE_SEARCH', 1);
        Configuration::updateValue('TV_ACTIVE_MANUFACTURER', 1);
        Configuration::updateValue('TV_ACTIVE_SUPPLIER', 1);
        Configuration::updateValue('TV_ACTIVE_LAYERED', 1);
        Configuration::updateValue('TV_METHOD', 0);
        Configuration::updateValue('TV_BUTTON_START_N_PAGE', 1);
        Configuration::updateValue('TV_BUTTON_N_PAGES', 1);
        Configuration::updateValue('TV_PRODUCT_WRAPPER', '#js-product-list .products');
        Configuration::updateValue('TV_PRODUCT_ELEM', '.product-miniature');
        Configuration::updateValue('TV_PAGINATION_WRAPPER', '.pagination .page-list');
        Configuration::updateValue('TV_NEXT_BUTTON', 'a.next');
        Configuration::updateValue('TV_VIEWS_BUTTONS_CHECK', 0);
        Configuration::updateValue('TV_VIEWS_BUTTONS', '');
        Configuration::updateValue('TV_SELECTED_VIEW', '');

        return parent::install()
            && $this->registerHook('header');
    }

    public function installTab()
    {
        $response = true;

        // First check for parent tab
        $parentTabID = Tab::getIdFromClassName('AdminThemeVolty');

        if ($parentTabID) {
            $parentTab = new Tab($parentTabID);
        } else {
            $parentTab = new Tab();
            $parentTab->active = 1;
            $parentTab->name = [];
            $parentTab->class_name = 'AdminThemeVolty';
            foreach (Language::getLanguages() as $lang) {
                $parentTab->name[$lang['id_lang']] = 'ThemeVolty Extension';
            }
            $parentTab->id_parent = 0;
            $parentTab->module = $this->name;
            $response &= $parentTab->add();
        }

        // Check for parent tab2
        $parentTab_2ID = Tab::getIdFromClassName('AdminThemeVoltyModules');
        if ($parentTab_2ID) {
            $parentTab_2 = new Tab($parentTab_2ID);
        } else {
            $parentTab_2 = new Tab();
            $parentTab_2->active = 1;
            $parentTab_2->name = [];
            $parentTab_2->class_name = 'AdminThemeVoltyModules';
            foreach (Language::getLanguages() as $lang) {
                $parentTab_2->name[$lang['id_lang']] = 'ThemeVolty Configure';
            }
            $parentTab_2->id_parent = $parentTab->id;
            $parentTab_2->module = $this->name;
            $response &= $parentTab_2->add();
        }
        // Created tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'Admin' . $this->name;
        $tab->name = [];
        foreach (Language::getLanguages() as $lang) {
            $tab->name[$lang['id_lang']] = 'Infinite Scroll';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
        $this->uninstallTab();

        Configuration::deleteByName('TV_ACTIVE_CATEGORY');
        Configuration::deleteByName('TV_ACTIVE_NEW_PRODUCTS');
        Configuration::deleteByName('TV_ACTIVE_PRICES_DROP');
        Configuration::deleteByName('TV_ACTIVE_BEST_SALES');
        Configuration::deleteByName('TV_ACTIVE_SEARCH');
        Configuration::deleteByName('TV_ACTIVE_MANUFACTURER');
        Configuration::deleteByName('TV_ACTIVE_SUPPLIER');
        Configuration::deleteByName('TV_ACTIVE_LAYERED');
        Configuration::deleteByName('TV_METHOD');
        Configuration::deleteByName('TV_BUTTON_START_N_PAGE');
        Configuration::deleteByName('TV_BUTTON_N_PAGES');
        Configuration::deleteByName('TV_PRODUCT_WRAPPER');
        Configuration::deleteByName('TV_PRODUCT_ELEM');
        Configuration::deleteByName('TV_PAGINATION_WRAPPER');
        Configuration::deleteByName('TV_NEXT_BUTTON');
        Configuration::deleteByName('TV_VIEWS_BUTTONS_CHECK');
        Configuration::deleteByName('TV_VIEWS_BUTTONS');
        Configuration::deleteByName('TV_SELECTED_VIEW');

        return parent::uninstall();
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    public function hookdisplayHeader($params)
    {
        return $this->hookHeader($params);
    }

    public function hookHeader($params)
    {
        // Get the current page
        $page_name = Dispatcher::getInstance()->getController();
        $pages_active = $this->getProductsPageActive();
        $assign = [
            'tv_texts' => [
                'loading_prev_text' => $this->l('Loading previous results...'),
                'loading_text' => $this->l('Loading next results...'),
                'button_text' => $this->l('Display more results...'),
                'end_text' => $this->l('No more results to display...'),
                'go_top_text' => $this->l('Back to top'),
            ],
        ];

        // Defines the Next && Prev url for <link rel="prev"> && <link rel="next">
        $current_p = Tools::getIsset('p') ? (int) Tools::getValue('p') : 1;
        $current_url = $this->context->link->getPaginationLink(false, false, false, true);
        $current_url_array = explode('?', $current_url);
        $base_url = $current_url_array[0];

        if (1 === $current_p) {
            $prev_url = $current_url;
            $next_url = $current_url . (isset($current_url_array[1]) ? '&' : '?') . 'p=2';
        } else {
            // Get all arguments in the url...
            $args = explode('&', $current_url_array[1]);
            $url_without_p = '';

            // ... and remove the "&p="...
            foreach ($args as $v) {
                if (false === strpos($v, 'p=')) {
                    $url_without_p .= $v . '&';
                }
            }
            $url_without_p = trim($url_without_p, '&');
            $url_without_p = $base_url . ('' != $url_without_p ? '?' : '') . $url_without_p;

            if ($current_p > 2) {
                // ... and add our "&p=" argument
                $prev_url = $url_without_p . '&p=' . ($current_p - 1);
                $next_url = $url_without_p . '&p=' . ($current_p + 1);
            } else { // Current : Page 2
                $prev_url = $url_without_p;
                $next_url = $url_without_p . ('' != $url_without_p ? '&' : '?') . 'p=' . ($current_p + 1);
            }
        }

        // Infini Scroll on PRODUCTS
        if (in_array($page_name, $pages_active)) {
            $initialize = true;

            if ('category' == $page_name && !Tools::getIsset('id_category')) {
                $initialize = false;
            }

            if ($initialize) {
                // Includes JS && CSS Front files
                if (version_compare(_PS_VERSION_, '1.7', '>')) {
                    $this->context->controller->registerStylesheet(
                        'modules-' . $this->name,
                        'modules/' . $this->name . '/views/css/front.css',
                        ['media' => 'all', 'priority' => 500]
                    );
                    $this->context->controller->registerJavascript(
                        'modules-' . $this->name,
                        'modules/' . $this->name . '/views/js/front.js',
                        ['position' => 'bottom', 'priority' => 500]
                    );
                } else {
                    $this->context->controller->addCSS($this->_path . 'views/css/front.css');
                    $this->context->controller->addJS($this->_path . 'views/js/front.js');
                }

                // Defines the options for the JS
                $assign['tv_options'] = [
                    'product_wrapper' => Configuration::get('TV_PRODUCT_WRAPPER'),
                    'product_elem' => Configuration::get('TV_PRODUCT_ELEM'),
                    'pagination_wrapper' => Configuration::get('TV_PAGINATION_WRAPPER'),
                    'next_button' => Configuration::get('TV_NEXT_BUTTON'),
                    'views_buttons' => Configuration::get('TV_VIEWS_BUTTONS'),
                    'selected_view' => Configuration::get('TV_SELECTED_VIEW'),
                    'method' => 1 == Configuration::get('TV_METHOD') ? 'button' : 'scroll',
                    'button_start_page' => Configuration::get('TV_BUTTON_START_N_PAGE'),
                    'button_n_pages' => Configuration::get('TV_BUTTON_N_PAGES'),
                    'active_with_layered' => Configuration::get('TV_ACTIVE_LAYERED'),
                    'ps_16' => version_compare(_PS_VERSION_, '1.6', '>='),
                    'has_facetedSearch' => Module::isEnabled('ps_facetedsearch'),
                    'tvcmsinfinitescrollqv_enabled' => Module::isEnabled('tvcmsinfinitescroll_quick_view'),
                ];
                $assign['prev_page_value'] = $prev_url;
                $assign['next_page_value'] = $next_url;
            }
        }
        $this->context->smarty->assign('tvcmsinfinitescroll_status', '1');
        $this->smarty->assign($assign);

        return $this->display(__FILE__, $this->templateFile);
    }

    /**
     * Return an array of active pages for Products Infinite Scroll.
     */
    public function getProductsPageActive()
    {
        $pages = [];

        if (Configuration::get('TV_ACTIVE_CATEGORY')) {
            $pages[] = 'category';
        }
        if (Configuration::get('TV_ACTIVE_NEW_PRODUCTS')) {
            $pages[] = 'new-products';
            $pages[] = 'newproducts';
        }
        if (Configuration::get('TV_ACTIVE_PRICES_DROP')) {
            $pages[] = 'prices-drop';
            $pages[] = 'pricesdrop';
        }
        if (Configuration::get('TV_ACTIVE_BEST_SALES')) {
            $pages[] = 'best-sales';
            $pages[] = 'bestsales';
        }
        if (Configuration::get('TV_ACTIVE_SEARCH')) {
            $pages[] = 'search';
        }
        if (Configuration::get('TV_ACTIVE_MANUFACTURER')) {
            $pages[] = 'manufacturer';
        }
        if (Configuration::get('TV_ACTIVE_SUPPLIER')) {
            $pages[] = 'supplier';
        }

        return $pages;
    }

    /**
     * Display the admin forms.
     */
    public function getContent()
    {
        $errors = [];

        // Add CSS && JS for Admin
        $this->context->controller->addCSS($this->_path . 'views/css/tvcmsinfinitescroll-admin.css');
        $this->context->controller->addJS($this->_path . 'views/js/tvcmsinfinitescroll-admin.js');

        $this->html = '<div id="tv-wrapper-settings" class="tv-wrapper-settings">';

        // Form Process
        // Products Forms
        if (Tools::isSubmit('submitTvcmsInfiniteScroll')) {
            Configuration::updateValue('TV_ACTIVE_CATEGORY', Tools::getValue('TV_ACTIVE_CATEGORY'));
            Configuration::updateValue('TV_ACTIVE_NEW_PRODUCTS', Tools::getValue('TV_ACTIVE_NEW_PRODUCTS'));
            Configuration::updateValue('TV_ACTIVE_PRICES_DROP', Tools::getValue('TV_ACTIVE_PRICES_DROP'));
            Configuration::updateValue('TV_ACTIVE_BEST_SALES', Tools::getValue('TV_ACTIVE_BEST_SALES'));
            Configuration::updateValue('TV_ACTIVE_SEARCH', Tools::getValue('TV_ACTIVE_SEARCH'));
            Configuration::updateValue('TV_ACTIVE_MANUFACTURER', Tools::getValue('TV_ACTIVE_MANUFACTURER'));
            Configuration::updateValue('TV_ACTIVE_SUPPLIER', Tools::getValue('TV_ACTIVE_SUPPLIER'));
            Configuration::updateValue('TV_ACTIVE_LAYERED', Tools::getValue('TV_ACTIVE_LAYERED'));
            Configuration::updateValue('TV_METHOD', Tools::getValue('TV_METHOD'));
            Configuration::updateValue('TV_BUTTON_START_N_PAGE', Tools::getValue('TV_BUTTON_START_N_PAGE'));
            Configuration::updateValue('TV_BUTTON_N_PAGES', Tools::getValue('TV_BUTTON_N_PAGES'));

            if (isset($errors) && count($errors)) {
                $this->html .= $this->displayError(implode('<br />', $errors));
            } else {
                $this->html .= $this->displayConfirmation($this->l('Your settings have been successfully updated.'));
            }
        }
        if (Tools::isSubmit('submitTvcmsInfiniteScrollAdvanced')) {
            Configuration::updateValue('TV_PRODUCT_WRAPPER', Tools::getValue('TV_PRODUCT_WRAPPER'));
            Configuration::updateValue('TV_PRODUCT_ELEM', Tools::getValue('TV_PRODUCT_ELEM'));
            Configuration::updateValue('TV_PAGINATION_WRAPPER', Tools::getValue('TV_PAGINATION_WRAPPER'));
            Configuration::updateValue('TV_NEXT_BUTTON', Tools::getValue('TV_NEXT_BUTTON'));
            Configuration::updateValue('TV_VIEWS_BUTTONS_CHECK', Tools::getValue('TV_VIEWS_BUTTONS_CHECK'));
            Configuration::updateValue('TV_VIEWS_BUTTONS', Tools::getValue('TV_VIEWS_BUTTONS'));
            Configuration::updateValue('TV_SELECTED_VIEW', Tools::getValue('TV_SELECTED_VIEW'));

            if (isset($errors) && count($errors)) {
                $this->html .= $this->displayError(implode('<br />', $errors));
            } else {
                $this->html .= $this->displayConfirmation($this->l('Your settings have been successfully updated.'));
            }
        }

        $this->html .= '<div id="tv-settings" class="tv-settings"><div class="tv-settings-inner">';
        $this->html .= '<div id="tv-settings-products" class="tv-settings-content clearfix">';
        $this->displayFormProducts();
        $this->displayFormProductsSelector();
        $this->html .= '</div>';

        $this->html .= '</div></div>';

        $this->html .= '</div>';

        $this->html .= '<script type="text/javascript">var tv_ps_16 = 1 </script>';

        return $this->html;
    }

    /**
     * Display the form of the module's PRODUCTS settings.
     */
    public function displayFormProducts()
    {
        $on_infinite_scroll = $this->l('Turn on infinite scroll on page');

        $tv_method_desc = $this->l('This allows your customers to view and read your page footer. By default,' .
            ' displaying results is fires by scrolling.');

        $tv_button_start_n_page_desc = $this->l('The button to display next results will be visible from'
            . ' the page N. Before, next results will be displayed by scrolling the page.');
        $fields_form_input = [
            [
                'type' => 'switch',
                'label' => $on_infinite_scroll . ' : category <br />(' . $this->l('Category') . ')',
                'name' => 'TV_ACTIVE_CATEGORY',
                'class' => 'tv-input-active-category t',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'TV_ACTIVE_CATEGORY_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'TV_ACTIVE_CATEGORY_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $on_infinite_scroll . ' : new-products <br />(' . $this->l('New products') . ')',
                'name' => 'TV_ACTIVE_NEW_PRODUCTS',
                'class' => 'tv-input-active-category t',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'TV_ACTIVE_NEW_PRODUCTS_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'TV_ACTIVE_NEW_PRODUCTS_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $on_infinite_scroll . ' : prices-drop <br />(' . $this->l('Prices drop') . ')',
                'name' => 'TV_ACTIVE_PRICES_DROP',
                'class' => 'tv-input-active-category t',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'TV_ACTIVE_PRICES_DROP_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'TV_ACTIVE_PRICES_DROP_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $on_infinite_scroll . ' : best-sales <br />(' . $this->l('Best sales') . ')',
                'name' => 'TV_ACTIVE_BEST_SALES',
                'class' => 'tv-input-active-category t',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'TV_ACTIVE_BEST_SALES_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'TV_ACTIVE_BEST_SALES_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $on_infinite_scroll . ' : search <br />(' . $this->l('Search') . ')',
                'name' => 'TV_ACTIVE_SEARCH',
                'class' => 'tv-input-active-category t',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'TV_ACTIVE_SEARCH_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'TV_ACTIVE_SEARCH_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $on_infinite_scroll . ' : manufacturer <br />(' . $this->l('Manufacturer') . ')',
                'name' => 'TV_ACTIVE_MANUFACTURER',
                'class' => 'tv-input-active-category t',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'TV_ACTIVE_MANUFACTURER_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'TV_ACTIVE_MANUFACTURER_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $on_infinite_scroll . ' : supplier <br />(' . $this->l('Supplier') . ')',
                'name' => 'TV_ACTIVE_SUPPLIER',
                'class' => 'tv-input-active-category t',
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'TV_ACTIVE_SUPPLIER_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'TV_ACTIVE_SUPPLIER_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $this->l('Turn on infinite scroll with layared module'),
                'name' => 'TV_ACTIVE_LAYERED',
                'class' => 'tv-input-active-category t',
                'desc' => $this->l('show or hide the infinite scroll when a filter is show on the layered module.'),
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'TV_ACTIVE_LAYERED_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'TV_ACTIVE_LAYERED_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ],
            [
                'type' => 'switch',
                'label' => $this->l('Display a button to load next results'),
                'name' => 'TV_METHOD',
                'class' => 'tv-input-method t',
                'desc' => $tv_method_desc,
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'TV_METHOD_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ],
                    [
                        'id' => 'TV_METHOD_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    ],
                ],
            ],
            [
                'type' => 'text',
                'label' => $this->l('Start displaying the button in the page N'),
                'name' => 'TV_BUTTON_START_N_PAGE',
                'class' => 'tv-input-button-start-page',
                'desc' => $tv_button_start_n_page_desc,
            ],
            [
                'type' => 'text',
                'label' => $this->l('Display the button every N page'),
                'name' => 'TV_BUTTON_N_PAGES',
                'class' => 'tv-input-button-pages',
                'desc' => $this->l('The button will be displayed only every N pages.'),
            ],
        ];

        // Specific variable for PS 1.5
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $fields_form_input[0]['type'] = 'radio';
            $fields_form_input[1]['type'] = 'radio';
            $fields_form_input[2]['type'] = 'radio';
            $fields_form_input[3]['type'] = 'radio';
            $fields_form_input[4]['type'] = 'radio';
            $fields_form_input[5]['type'] = 'radio';
            $fields_form_input[6]['type'] = 'radio';
            $fields_form_input[7]['type'] = 'radio';
            $fields_form_input[8]['type'] = 'radio';
        }

        $fields_form = [
            'form' => [
                'input' => $fields_form_input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default tv-input-submit',
                ],
            ],
        ];

        // Get default language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $tmp = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->allow_employee_form_lang = $tmp ? $tmp : $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submitTvcmsInfiniteScroll';
        $this->fields_form = [];
        $helper->tpl_vars = [
            'fields_value' => [
                'TV_ACTIVE_CATEGORY' => Tools::getValue(
                    'TV_ACTIVE_CATEGORY',
                    Configuration::get('TV_ACTIVE_CATEGORY')
                ),
                'TV_ACTIVE_NEW_PRODUCTS' => Tools::getValue(
                    'TV_ACTIVE_NEW_PRODUCTS',
                    Configuration::get('TV_ACTIVE_NEW_PRODUCTS')
                ),
                'TV_ACTIVE_PRICES_DROP' => Tools::getValue(
                    'TV_ACTIVE_PRICES_DROP',
                    Configuration::get('TV_ACTIVE_PRICES_DROP')
                ),
                'TV_ACTIVE_BEST_SALES' => Tools::getValue(
                    'TV_ACTIVE_BEST_SALES',
                    Configuration::get('TV_ACTIVE_BEST_SALES')
                ),
                'TV_ACTIVE_SEARCH' => Tools::getValue(
                    'TV_ACTIVE_SEARCH',
                    Configuration::get('TV_ACTIVE_SEARCH')
                ),
                'TV_ACTIVE_MANUFACTURER' => Tools::getValue(
                    'TV_ACTIVE_MANUFACTURER',
                    Configuration::get('TV_ACTIVE_MANUFACTURER')
                ),
                'TV_ACTIVE_SUPPLIER' => Tools::getValue(
                    'TV_ACTIVE_SUPPLIER',
                    Configuration::get('TV_ACTIVE_SUPPLIER')
                ),
                'TV_ACTIVE_LAYERED' => Tools::getValue(
                    'TV_ACTIVE_LAYERED',
                    Configuration::get('TV_ACTIVE_LAYERED')
                ),
                'TV_METHOD' => Tools::getValue('TV_METHOD', Configuration::get('TV_METHOD')),
                'TV_BUTTON_START_N_PAGE' => Tools::getValue(
                    'TV_BUTTON_START_N_PAGE',
                    Configuration::get('TV_BUTTON_START_N_PAGE')
                ),
                'TV_BUTTON_N_PAGES' => Tools::getValue(
                    'TV_BUTTON_N_PAGES',
                    Configuration::get('TV_BUTTON_N_PAGES')
                ),
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $this->html .= '<h2 class="tv-options-title">' . $this->l('Display Settings')
            . ' <span class="tv-title-toggle"></span></h2>';
        $this->html .= $helper->generateForm([$fields_form]);
    }

    /**
     * Display the form of the module's selector PRODUCTS settings.
     */
    public function displayFormProductsSelector()
    {
        $tv_next_button_desc = $this->l('Element containing the link to next page of the pagination '
            . '(inside the "Pagination Selector") . ');
        $fields_form_input = [
            [
                'type' => 'text',
                'label' => $this->l('Products List Selector'),
                'name' => 'TV_PRODUCT_WRAPPER',
                'class' => 'tv-input-product-wrapper',
                'desc' => $this->l('Element containing your theme\'s products list . '),
            ],
            [
                'type' => 'text',
                'label' => $this->l('Product Selector'),
                'name' => 'TV_PRODUCT_ELEM',
                'class' => 'tv-input-product-elem',
                'desc' => $this->l('Element containing a product (inside the "Products List Selector").'),
            ],
            [
                'type' => 'text',
                'label' => $this->l('Pagination Selector'),
                'name' => 'TV_PAGINATION_WRAPPER',
                'class' => 'tv-input-pagination-wrapper',
                'desc' => $this->l('Element containing your theme\'s products pagination.'),
            ],
            [
                'type' => 'text',
                'label' => $this->l('Next Page Button Selector'),
                'name' => 'TV_NEXT_BUTTON',
                'class' => 'tv-input-next-button',
                'desc' => $tv_next_button_desc,
            ],
            // array(
            //     'type' => 'switch',
            //     'label' => $this->l('My theme uses different products list views (grid/list)'),
            //     'name' => 'TV_VIEWS_BUTTONS_CHECK',
            //     'class' => 'tv-input-views-button-check t',
            //     // 'desc' => '',
            //     'is_bool' => true,
            //     'values' => array(
            //         array(
            //             'id' => 'TV_VIEWS_BUTTONS_CHECK_on',
            //             'value' => 1,
            //             'label' => $this->l('Enabled')
            //         ),
            //         array(
            //             'id' => 'TV_VIEWS_BUTTONS_CHECK_off',
            //             'value' => 0,
            //             'label' => $this->l('Disabled')
            //         )
            //     )
            // ),
            [
                'type' => 'text',
                'label' => $this->l('Grid/list views buttons selector'),
                'name' => 'TV_VIEWS_BUTTONS',
                'class' => 'tv-input-views-button',
                'desc' => $this->l('Selector for buttons of the different products list views.'),
            ],
            [
                'type' => 'text',
                'label' => $this->l('Selected grid/list view button selector'),
                'name' => 'TV_SELECTED_VIEW',
                'class' => 'tv-input-views-button-selected',
                'desc' => $this->l('Selector for the button of the selected view.'),
            ],
        ];

        // Specific variable for PS 1.5
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $fields_form_input[4]['type'] = 'radio';
        }

        $fields_form = [
            'form' => [
                'input' => $fields_form_input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default tv-input-submit',
                ],
            ],
        ];

        // Get default language
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->table = $this->table;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;

        // Language
        $helper->default_form_language = $default_lang;
        $tmp = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->allow_employee_form_lang = $tmp ? $tmp : $default_lang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submitTvcmsInfiniteScrollAdvanced';
        $this->fields_form = [];
        $helper->tpl_vars = [
            'fields_value' => [
                'TV_PRODUCT_WRAPPER' => Tools::getValue(
                    'TV_PRODUCT_WRAPPER',
                    Configuration::get('TV_PRODUCT_WRAPPER')
                ),
                'TV_PRODUCT_ELEM' => Tools::getValue(
                    'TV_PRODUCT_ELEM',
                    Configuration::get('TV_PRODUCT_ELEM')
                ),
                'TV_PAGINATION_WRAPPER' => Tools::getValue(
                    'TV_PAGINATION_WRAPPER',
                    Configuration::get('TV_PAGINATION_WRAPPER')
                ),
                'TV_NEXT_BUTTON' => Tools::getValue('TV_NEXT_BUTTON', Configuration::get('TV_NEXT_BUTTON')),
                'TV_VIEWS_BUTTONS_CHECK' => Tools::getValue(
                    'TV_VIEWS_BUTTONS_CHECK',
                    Configuration::get('TV_VIEWS_BUTTONS_CHECK')
                ),
                'TV_VIEWS_BUTTONS' => Tools::getValue(
                    'TV_VIEWS_BUTTONS',
                    Configuration::get('TV_VIEWS_BUTTONS')
                ),
                'TV_SELECTED_VIEW' => Tools::getValue('TV_SELECTED_VIEW', Configuration::get('TV_SELECTED_VIEW')),
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $this->html .= '<h2 class="tv-options-title with-mgt">' . $this->l('Advanced Settings')
         . ' <span class="tv-title-toggle"></span></h2>';
        $this->html .= $helper->generateForm([$fields_form]);
    }
}
