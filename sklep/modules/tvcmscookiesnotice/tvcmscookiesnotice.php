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

include_once 'classes/tvcmscookiesnotice_image_upload.class.php';
include_once 'classes/tvcmscookiesnotice_status.class.php';

class TvcmsCookiesNotice extends Module
{
    public function __construct()
    {
        $this->name = 'tvcmscookiesnotice';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Cookies Notice');
        $this->description = $this->l('Its Show Cookie Notice on Front Side');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted . ' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        // $this->installTab();
        // $this->createDefaultData();

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayAfterBodyOpeningTag');
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
            $tab->name[$lang['id_lang']] = 'Cookie Text';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function createDefaultData()
    {
        $languages = Language::getLanguages();
        $result = [];
        foreach ($languages as $lang) {
            $result['TVCMSCOOKIESNOTICE_TITLE'][$lang['id_lang']] = '<div>By continuing use this site,'
             . ' you agree to the <a href="#">Terms & Conditions</a> and our use of cookies. </div>';
        }

        Configuration::updateValue('TVCMSCOOKIESNOTICE_TITLE', $result['TVCMSCOOKIESNOTICE_TITLE'], true);
        Configuration::updateValue('TVCMSCOOKIESNOTICE_STATUS', 1);
    }

    public function uninstall()
    {
        $this->uninstallTab();
        $this->deleteVariable();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSCOOKIESNOTICE_TITLE');
        Configuration::deleteByName('TVCMSCOOKIESNOTICE_STATUS');
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    public function hookDisplayBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }// hookDisplayBackOfficeHeader()

    public function getContent()
    {
        if (Tools::isSubmit('submitTvcmsSampleinstall') && '1' == Tools::getValue('tvinstalldata')) {
            $this->createDefaultData();
        }
        $message = $this->postProcess();
        $output = $message . $this->renderForm();

        return $output;
    }

    public function postProcess()
    {
        $message = '';
        $result = [];

        if (Tools::isSubmit('submittvcmsCookiesNoticeForm') && '0' == Tools::getValue('tvinstalldata')) {
            $languages = Language::getLanguages();
            // $obj_image = new TvcmsCookiesNoticeImageUpload();
            foreach ($languages as $lang) {
                $tmp = Tools::getValue('TVCMSCOOKIESNOTICE_TITLE_' . $lang['id_lang']);
                $result['TVCMSCOOKIESNOTICE_TITLE'][$lang['id_lang']] = $tmp;
            }

            Configuration::updateValue('TVCMSCOOKIESNOTICE_TITLE', $result['TVCMSCOOKIESNOTICE_TITLE'], true);

            $tmp = Tools::getValue('TVCMSCOOKIESNOTICE_STATUS');
            Configuration::updateValue('TVCMSCOOKIESNOTICE_STATUS', $tmp);

            $this->clearCustomSmartyCache('tvcmscookiesnotice_display_home.tpl');

            $message .= $this->displayConfirmation($this->l('Cookies Notice is Updated . '));
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
        $form[] = $this->tvcmsCookiesNoticeForm();

        return $helper->generateForm($form);
    }

    protected function tvcmsCookiesNoticeForm()
    {
        $tvcms_obj = new TvcmsCookiesNoticeStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        $input[] = [
                    'col' => 12,
                    'type' => 'BtnInstallData',
                    'name' => 'BtnInstallData',
                    'label' => '',
                ];
        if ($show_fields['title']) {
            $input[] = [
                    'col' => 8,
                    'type' => 'textarea',
                    'name' => 'TVCMSCOOKIESNOTICE_TITLE',
                    'label' => $this->l('Title'),
                    'lang' => true,
                    'cols' => 40,
                    'rows' => 10,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ];
        }

        if ($show_fields['status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Status'),
                        'name' => 'TVCMSCOOKIESNOTICE_STATUS',
                        'desc' => $this->l('Hide or show icons.'),
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
                'title' => $this->l('Cookies Notice'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submittvcmsCookiesNoticeForm',
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $fields = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $tmp = Configuration::get('TVCMSCOOKIESNOTICE_TITLE', $lang['id_lang'], true);
            $fields['TVCMSCOOKIESNOTICE_TITLE'][$lang['id_lang']] = $tmp;
        }

        $tmp = Configuration::get('TVCMSCOOKIESNOTICE_STATUS');
        $fields['TVCMSCOOKIESNOTICE_STATUS'] = $tmp;

        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        return $fields;
    }

    public function hookdisplayHeader()
    {
        $static_token = Tools::getToken(false);
        Media::addJsDef(['static_token' => $static_token]);

        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }// hookDisplayHeader()

    public function hookDisplayTopColumn()
    {
        return $this->hookDisplayHome();
    }

    public function hookdisplayNav2()
    {
        return $this->hookDisplayHome();
    }

    public function showFrontData()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $result = [];

        $tmp = Configuration::get('TVCMSCOOKIESNOTICE_TITLE', $id_lang, true);
        $result['title'] = $tmp;

        $tmp = Configuration::get('TVCMSCOOKIESNOTICE_STATUS');
        $result['status'] = $tmp;

        return $result;
    }

    public function showFrontSideResult()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $disArrResult = [];

        $tvcms_obj = new TvcmsCookiesNoticeStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();

        $disArrResult['title'] = $show_fields['title'];

        $disArrResult['data'] = $this->showFrontData();

        $disArrResult['status'] = Configuration::get('TVCMSCOOKIESNOTICE_STATUS');

        $disArrResult['path'] = _MODULE_DIR_ . $this->name . '/views/img/';
        $disArrResult['id_lang'] = $id_lang;

        $this->context->smarty->assign('dis_arr_result', $disArrResult);

        return $disArrResult['status'];
    }

    public function hookdisplayAfterBodyOpeningTag()
    {
        if (!Cache::isStored('tvcmscookiesnotice_display_home.tpl')) {
            $result = $this->showFrontSideResult();
            $tmp = $this->context->cookie->__get('cokkie_set');
            if ($result && ('' == $tmp || 'false' == $tmp)) {
                $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            } else {
                $output = '';
            }
            Cache::store('tvcmscookiesnotice_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmscookiesnotice_display_home.tpl');
    }
}
