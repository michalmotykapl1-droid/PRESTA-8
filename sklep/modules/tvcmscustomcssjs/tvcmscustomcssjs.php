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

include_once 'classes/tvcmscustomcssjs_status.class.php';

class TvcmsCustomCssJs extends Module
{
    public function __construct()
    {
        $this->name = 'tvcmscustomcssjs';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Custom Css And Js');
        $this->description = $this->l('Here admin can add Css and Js of Front Side.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        $this->installTab();
        $this->createVariable();

        return parent::install()
            && $this->registerHook('displayCustomCss')
            && $this->registerHook('displayCustomJs');
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
            $tab->name[$lang['id_lang']] = 'Custom Css And Js';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function createVariable()
    {
        Configuration::updateValue('TVCMSCUSTOMCSSJS_CSS', '');
        Configuration::updateValue('TVCMSOFFERTEXT_CSS_STATUS', '0');
        Configuration::updateValue('TVCMSCUSTOMCSSJS_JS', '');
        Configuration::updateValue('TVCMSOFFERTEXT_JS_STATUS', '0');
    }

    public function uninstall()
    {
        $this->uninstallTab();
        $this->deleteVariable();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSCUSTOMCSSJS_CSS');
        Configuration::deleteByName('TVCMSOFFERTEXT_CSS_STATUS');
        Configuration::deleteByName('TVCMSCUSTOMCSSJS_JS');
        Configuration::deleteByName('TVCMSOFFERTEXT_JS_STATUS');
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
        $message = $this->postProcess();
        $output = $message . $this->renderForm();

        return $output;
    }

    public function postProcess()
    {
        $message = '';
        if (Tools::isSubmit('submittvcmsCustomCssJsMainTitleForm')) {
            $tmp = Tools::getValue('TVCMSCUSTOMCSSJS_CSS');
            Configuration::updateValue('TVCMSCUSTOMCSSJS_CSS', $tmp);
            $tmp = Tools::getValue('TVCMSOFFERTEXT_CSS_STATUS');
            Configuration::updateValue('TVCMSOFFERTEXT_CSS_STATUS', $tmp);
            $tmp = Tools::getValue('TVCMSCUSTOMCSSJS_JS');
            Configuration::updateValue('TVCMSCUSTOMCSSJS_JS', $tmp);
            $tmp = Tools::getValue('TVCMSOFFERTEXT_JS_STATUS');
            Configuration::updateValue('TVCMSOFFERTEXT_JS_STATUS', $tmp);

            $this->clearCustomSmartyCache('tvcmscustomcssjs_display_home.tpl');
            $message .= $this->displayConfirmation($this->l('Custom Css And Js is Updated.'));
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

        $tvcms_obj = new TvcmsCustomCssJsStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        if ($show_fields['main_status']) {
            $form[] = $this->tvcmsCustomCssJsMainTitleForm();
        }

        return $helper->generateForm($form);
    }

    protected function tvcmsCustomCssJsMainTitleForm()
    {
        $tvcms_obj = new TvcmsCustomCssJsStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        if ($show_fields['main_css']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'textarea',
                    'name' => 'TVCMSCUSTOMCSSJS_CSS',
                    'label' => $this->l('Custom Css'),
                    'cols' => 40,
                    'rows' => 20,
                ];
        }

        if ($show_fields['main_css_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Custom Css Status'),
                        'name' => 'TVCMSOFFERTEXT_CSS_STATUS',
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

        if ($show_fields['main_js']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'textarea',
                    'name' => 'TVCMSCUSTOMCSSJS_JS',
                    'label' => $this->l('Custom Js'),
                    'cols' => 40,
                    'rows' => 20,
                ];
        }

        if ($show_fields['main_js_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Custom Js Status'),
                        'name' => 'TVCMSOFFERTEXT_JS_STATUS',
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
                'title' => $this->l('Custom Css And Js'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submittvcmsCustomCssJsMainTitleForm',
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $fields = [];
        // $languages = Language::getLanguages();

        $fields['TVCMSCUSTOMCSSJS_CSS'] = Configuration::get('TVCMSCUSTOMCSSJS_CSS');
        $fields['TVCMSOFFERTEXT_CSS_STATUS'] = Configuration::get('TVCMSOFFERTEXT_CSS_STATUS');
        $fields['TVCMSCUSTOMCSSJS_JS'] = Configuration::get('TVCMSCUSTOMCSSJS_JS');
        $fields['TVCMSOFFERTEXT_JS_STATUS'] = Configuration::get('TVCMSOFFERTEXT_JS_STATUS');

        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        return $fields;
    }

    public function hookdisplayCustomCss()
    {
        if (!Cache::isStored('tvcmscustomcssjs_css')) {
            $output = '';
            if ('1' == Configuration::get('TVCMSOFFERTEXT_CSS_STATUS')) {
                $css = Configuration::get('TVCMSCUSTOMCSSJS_CSS');
                $output = '<style>' . $css . '</style>';
            }
            Cache::store('tvcmscustomcssjs_css', $output);
        }

        return Cache::retrieve('tvcmscustomcssjs_css');
    }

    public function hookdisplayCustomJs()
    {
        if (!Cache::isStored('tvcmscustomcssjs_js')) {
            $output = '';
            if ('1' == Configuration::get('TVCMSOFFERTEXT_JS_STATUS')) {
                $js = Configuration::get('TVCMSCUSTOMCSSJS_JS');
                $output = '<script>' . $js . '</script>';
            }
            Cache::store('tvcmscustomcssjs_js', $output);
        }

        return Cache::retrieve('tvcmscustomcssjs_js');
    }
}
