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

include_once _PS_MODULE_DIR_ . 'tvcmstabproducts/tvcmstabproducts.php';
include_once _PS_MODULE_DIR_ . 'tvcmsfooterproduct/classes/tvcmsfooterproduct_status.class.php';

class TvcmsFooterProduct extends Module
{
    public function __construct()
    {
        $this->name = 'tvcmsfooterproduct';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Footer Product');
        $this->description = $this->l('Its Products on Footer in front side.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        // $this->createDefaultData();
        $this->installTab();

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            // && $this->registerHook('displayHome');
            && $this->registerHook('displayWrapperBottom');
        // && $this->registerHook('displayFooter');
    }

    public function createDefaultData()
    {
        $languages = Language::getLanguages();
        $result = [];
        foreach ($languages as $lang) {
            $result['TVCMSFOOTERPRODUCT_FEATURED_TITLE'][$lang['id_lang']] = 'FEATURED ITEMS';
            $result['TVCMSFOOTERPRODUCT_NEW_TITLE'][$lang['id_lang']] = 'NEW ITEMS';
            $result['TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE'][$lang['id_lang']] = 'BEST ITEMS';
        }

        Configuration::updateValue('TVCMSFOOTERPRODUCT_FEATURED_TITLE', $result['TVCMSFOOTERPRODUCT_FEATURED_TITLE']);
        Configuration::updateValue('TVCMSFOOTERPRODUCT_NEW_TITLE', $result['TVCMSFOOTERPRODUCT_NEW_TITLE']);
        Configuration::updateValue(
            'TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE',
            $result['TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE']
        );
        Configuration::updateValue('TVCMSFOOTERPRODUCT_NUM_OF_PRODUCT', 2);
        Configuration::updateValue('TVCMSFOOTERPRODUCT_STATUS', 1);
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
            $tab->name[$lang['id_lang']] = 'Footer Product';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
        $this->deleteVariable();
        $this->uninstallTab();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSFOOTERPRODUCT_FEATURED_TITLE');
        Configuration::deleteByName('TVCMSFOOTERPRODUCT_NEW_TITLE');
        Configuration::deleteByName('TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE');
        Configuration::deleteByName('TVCMSFOOTERPRODUCT_NUM_OF_PRODUCT');
        Configuration::deleteByName('TVCMSFOOTERPRODUCT_STATUS');
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    public function getContent()
    {
        $useSSL = (isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED')) || Tools::usingSecureMode() ? true : false;
        $protocol_content = $useSSL ? 'https://' : 'http://';
        $baseDir = $protocol_content . Tools::getHttpHost() . __PS_BASE_URI__;
        $link = PS_ADMIN_DIR;
        if (Tools::substr(strrchr($link, '/'), 1)) {
            $admin_folder = Tools::substr(strrchr($link, '/'), 1);
        } else {
            $admin_folder = Tools::substr(strrchr($link, "\'"), 1);
        }
        $static_token = Tools::getAdminToken('AdminModules' . (int) Tab::getIdFromClassName('AdminModules') . (int) $this->context->employee->id);
        $url_slidersampleupgrade = $baseDir . $admin_folder . '/index.php?controller=AdminModules&configure=' . $this->name . '&tab_module=front_office_features&module_name=' . $this->name . '&token=' . $static_token;
        $this->context->smarty->assign('tvurlupgrade', $url_slidersampleupgrade);

        if (Tools::isSubmit('submitTvcmsSampleinstall') && '1' == Tools::getValue('tvinstalldata')) {
            $this->createDefaultData();
        }
        $message = $this->postProcess();
        $output = $message . $this->renderForm();

        return $output;
    }

    public function hookDisplayBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }// hookDisplayBackOfficeHeader()

    public function postProcess()
    {
        $message = '';
        $result = [];

        if (Tools::isSubmit('submittvcmsFooterProductForm') && '0' == Tools::getValue('tvinstalldata')) {
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $tmp = Tools::getValue('TVCMSFOOTERPRODUCT_FEATURED_TITLE_' . $lang['id_lang']);
                $result['TVCMSFOOTERPRODUCT_FEATURED_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSFOOTERPRODUCT_NEW_TITLE_' . $lang['id_lang']);
                $result['TVCMSFOOTERPRODUCT_NEW_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE_' . $lang['id_lang']);
                $result['TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE'][$lang['id_lang']] = $tmp;
            }

            Configuration::updateValue(
                'TVCMSFOOTERPRODUCT_FEATURED_TITLE',
                $result['TVCMSFOOTERPRODUCT_FEATURED_TITLE']
            );
            Configuration::updateValue('TVCMSFOOTERPRODUCT_NEW_TITLE', $result['TVCMSFOOTERPRODUCT_NEW_TITLE']);
            Configuration::updateValue(
                'TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE',
                $result['TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE']
            );

            Configuration::updateValue(
                'TVCMSFOOTERPRODUCT_NUM_OF_PRODUCT',
                Tools::getValue('TVCMSFOOTERPRODUCT_NUM_OF_PRODUCT')
            );
            Configuration::updateValue('TVCMSFOOTERPRODUCT_STATUS', Tools::getValue('TVCMSFOOTERPRODUCT_STATUS'));

            $this->clearCustomSmartyCache('tvcmsfooterproduct_display_home.tpl');
            $message .= $this->displayConfirmation($this->l('Footer Product is Updated . '));
        }

        return $message;
    }

    public function clearCustomSmartyCache($cache_id)
    {
        if (Cache::isStored($cache_id)) {
            Cache::clean($cache_id);
        }
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
             . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), // Add values for your inputs
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        $form = [];
        $form[] = $this->tvcmsFooterProduct();

        return $helper->generateForm($form);
    }

    protected function tvcmsFooterProduct()
    {
        $tvcms_obj = new TvcmsFooterProductStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        $input[] = [
            'col' => 12,
            'type' => 'BtnInstallData',
            'name' => 'BtnInstallData',
            'label' => '',
        ];

        if ($show_fields['featured_prod_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSFOOTERPRODUCT_FEATURED_TITLE',
                    'label' => $this->l('Feature Product Title'),
                    'lang' => true,
                ];
        }

        if ($show_fields['new_prod_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSFOOTERPRODUCT_NEW_TITLE',
                    'label' => $this->l('New Product Title'),
                    'lang' => true,
                ];
        }

        if ($show_fields['best_seller_prod_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE',
                    'label' => $this->l('Best Seller Product Title'),
                    'lang' => true,
                ];
        }

        if ($show_fields['num_of_product']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'select',
                    'name' => 'TVCMSFOOTERPRODUCT_NUM_OF_PRODUCT',
                    'label' => $this->l('Number Of Product'),
                    'options' => [
                        'query' => [
                            [
                                'id_option' => 1,
                                'name' => '1',
                            ],
                            [
                                'id_option' => 2,
                                'name' => '2',
                            ],
                            [
                                'id_option' => 3,
                                'name' => '3',
                            ],
                            [
                                'id_option' => 4,
                                'name' => '4',
                            ],
                            [
                                'id_option' => 5,
                                'name' => '5',
                            ],
                            [
                                'id_option' => 6,
                                'name' => '6',
                            ],
                            [
                                'id_option' => 7,
                                'name' => '7',
                            ],
                            [
                                'id_option' => 8,
                                'name' => '8',
                            ],
                            [
                                'id_option' => 9,
                                'name' => '9',
                            ],
                            [
                                'id_option' => 10,
                                'name' => '10',
                            ],
                        ],
                        'id' => 'id_option',
                        'name' => 'name',
                    ],
                ];
        }

        if ($show_fields['status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Status'),
                        'name' => 'TVCMSFOOTERPRODUCT_STATUS',
                        'desc' => $this->l('Hide or show footer product in front side . '),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Show'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Hide'),
                            ],
                        ],
                    ];
        }

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Footer Product'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submittvcmsFooterProductForm',
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $fields = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $tmp = Configuration::get('TVCMSFOOTERPRODUCT_FEATURED_TITLE', $lang['id_lang']);
            $fields['TVCMSFOOTERPRODUCT_FEATURED_TITLE'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSFOOTERPRODUCT_NEW_TITLE', $lang['id_lang']);
            $fields['TVCMSFOOTERPRODUCT_NEW_TITLE'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE', $lang['id_lang']);
            $fields['TVCMSFOOTERPRODUCT_BEST_SELLER_TITLE'][$lang['id_lang']] = $tmp;
        }

        $fields['TVCMSFOOTERPRODUCT_NUM_OF_PRODUCT'] = Configuration::get('TVCMSFOOTERPRODUCT_NUM_OF_PRODUCT');
        $fields['TVCMSFOOTERPRODUCT_STATUS'] = Configuration::get('TVCMSFOOTERPRODUCT_STATUS');

        return $fields;
    }

    public function hookdisplayHeader()
    {
        $tmp = $this->context->link->getModuleLink('tvcmsfooterproduct', 'default');
        Media::addJsDef(['gettvcmsfooterproductlink' => $tmp]);
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        // $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }// hookDisplayHeader()

    public function hookdisplayWrapperBottom()
    {
        return $this->hookdisplayHome();
    }

    public function showFrontSideResult()
    {
        $status = Configuration::get('TVCMSFOOTERPRODUCT_STATUS');
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $tvcms_obj = new TvcmsTabProductsStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();

        $tv_obj_prod = new TvcmsTabProducts();
        $footer_product_list = [];

        $footer_product_list['featured_product'] = $tv_obj_prod->displayFeaturedProducts();
        $cat_id = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_CAT');
        if (!empty($cat_id)) {
            $footer_product_list['featured_link'] = Context::getContext()->link->getCategoryLink($cat_id);
        }

        $footer_product_list['new_product'] = $tv_obj_prod->displayNewProducts();
        $footer_product_list['new_link'] = Context::getContext()->link->getPageLink('new-products');

        $footer_product_list['best_seller_product'] = $tv_obj_prod->displayBestSellers();
        $footer_product_list['best_seller_link'] = Context::getContext()->link->getPageLink('best-sales');

        $this->context->smarty->assign('id_lang', $id_lang);
        $this->context->smarty->assign('show_fields', $show_fields);
        $this->context->smarty->assign('footer_product_list', $footer_product_list);

        return $status;
    }

    public function hookdisplayHome()
    {
        if (!Cache::isStored('tvcmsfooterproduct_display_home.tpl')) {
            // $result = $this->showFrontSideResult();
            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            Cache::store('tvcmsfooterproduct_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmsfooterproduct_display_home.tpl');
    }

    public function hookdisplayHomefooterproduct()
    {
        if (!Cache::isStored('tvcmsfooterproduct_display_home_ajax.tpl')) {
            $result = $this->showFrontSideResult();
            if ($result) {
                $static_token = Tools::getToken(false);
                $url = ['pages' => ['cart' => $this->context->link->getPageLink('cart')]];
                $this->context->smarty->assign('urls', $url);
                $this->context->smarty->assign('static_token', $static_token);
                $output = $this->display(__FILE__, 'views/templates/front/display_home-data.tpl');
            } else {
                $output = '';
            }
            Cache::store('tvcmsfooterproduct_display_home_ajax.tpl', $output);
        }

        return Cache::retrieve('tvcmsfooterproduct_display_home_ajax.tpl');
    }
}
