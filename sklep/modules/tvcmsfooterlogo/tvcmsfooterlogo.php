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

include_once 'classes/tvcmsfooterlogo_status.class.php';
include_once 'classes/tvcmsfooterlogo_image_upload.class.php';

class TvcmsFooterLogo extends Module
{
    public function __construct()
    {
        $this->name = 'tvcmsfooterlogo';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Footer Logo');
        $this->description = $this->l('Its Show Footer Logo And Description on Front Side');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        $this->installTab();
        // $this->createDefaultData();

        return parent::install()
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayHeader')
            // && $this->registerHook('displayFooterPart1');
            && $this->registerHook('displayFooterPart2');
        // && $this->registerHook('displayFooterBefore');
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
            $tab->name[$lang['id_lang']] = 'Footer Logo';
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
            $result['TVCMSFOOTERLOGO_TITLE'][$lang['id_lang']] = 'Main Title';
            $result['TVCMSFOOTERLOGO_SUB_DESCRIPTION'][$lang['id_lang']] = 'Sub Description';
            $result['TVCMSFOOTERLOGO_DESCRIPTION'][$lang['id_lang']] = 'We are a global housewares product'
                . ' design company. We bring thought and creativity to everyday items through original design.';
        }

        Configuration::updateValue('TVCMSFOOTERLOGO_IMG', 'demo_img_1.png');
        Configuration::updateValue('TVCMSFOOTERLOGO_TITLE', $result['TVCMSFOOTERLOGO_TITLE']);
        Configuration::updateValue('TVCMSFOOTERLOGO_SUB_DESCRIPTION', $result['TVCMSFOOTERLOGO_SUB_DESCRIPTION']);
        Configuration::updateValue('TVCMSFOOTERLOGO_DESCRIPTION', $result['TVCMSFOOTERLOGO_DESCRIPTION']);
    }

    public function uninstall()
    {
        $this->uninstallTab();
        $this->deleteVariable();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSFOOTERLOGO_TITLE');
        Configuration::deleteByName('TVCMSFOOTERLOGO_SUB_DESCRIPTION');
        Configuration::deleteByName('TVCMSFOOTERLOGO_DESCRIPTION');
        Configuration::deleteByName('TVCMSFOOTERLOGO_IMG');
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

        if (Tools::isSubmit('submittvcmsFooterLogoMainTitleForm') && '0' == Tools::getValue('tvinstalldata')) {
            $obj_image = new TvcmsFooterLogoImageUpload();
            if (!empty($_FILES['TVCMSFOOTERLOGO_IMG']['name'])) {
                $old_file = Configuration::get('TVCMSFOOTERLOGO_IMG');
                $new_file = $_FILES['TVCMSFOOTERLOGO_IMG'];
                $ans = $obj_image->imageUploading($new_file, $old_file);
                if ($ans['success']) {
                    Configuration::updateValue('TVCMSFOOTERLOGO_IMG', $ans['name']);
                } else {
                    $message .= $ans['error'];
                }
            }

            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $tmp = Tools::getValue('TVCMSFOOTERLOGO_TITLE_' . $lang['id_lang']);
                $result['TVCMSFOOTERLOGO_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSFOOTERLOGO_SUB_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSFOOTERLOGO_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSFOOTERLOGO_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSFOOTERLOGO_DESCRIPTION'][$lang['id_lang']] = $tmp;
            }
            Configuration::updateValue('TVCMSFOOTERLOGO_TITLE', $result['TVCMSFOOTERLOGO_TITLE']);
            $tmp = $result['TVCMSFOOTERLOGO_SUB_DESCRIPTION'];
            Configuration::updateValue('TVCMSFOOTERLOGO_SUB_DESCRIPTION', $tmp);

            $tmp = $result['TVCMSFOOTERLOGO_DESCRIPTION'];
            Configuration::updateValue('TVCMSFOOTERLOGO_DESCRIPTION', $tmp);

            $this->clearCustomSmartyCache('tvcmsfooterlogo_display_home.tpl');

            $message .= $this->displayConfirmation($this->l('Footer Logo is Updated.'));
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

        $tvcms_obj = new TvcmsFooterLogoStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        if ($show_fields['main_status']) {
            $form[] = $this->tvcmsFooterLogoMainTitleForm();
        }

        return $helper->generateForm($form);
    }

    protected function tvcmsFooterLogoMainTitleForm()
    {
        $tvcms_obj = new TvcmsFooterLogoStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        $input[] = [
            'col' => 12,
            'type' => 'BtnInstallData',
            'name' => 'BtnInstallData',
            'label' => '',
        ];
        if ($show_fields['main_image']) {
            $input[] = [
                        'type' => 'image_file',
                        'name' => 'TVCMSFOOTERLOGO_IMG',
                        'label' => $this->l('Footer Logo Image'),
                ];
        }

        if ($show_fields['main_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSFOOTERLOGO_TITLE',
                    'label' => $this->l('Main Title'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_short_description']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSFOOTERLOGO_SUB_DESCRIPTION',
                    'label' => $this->l('Short Description'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_description']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSFOOTERLOGO_DESCRIPTION',
                    'label' => $this->l('Description'),
                    'lang' => true,
                ];
        }

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Footer Logo'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submittvcmsFooterLogoMainTitleForm',
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $fields = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $tmp = Configuration::get('TVCMSFOOTERLOGO_TITLE', $lang['id_lang']);
            $fields['TVCMSFOOTERLOGO_TITLE'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSFOOTERLOGO_SUB_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSFOOTERLOGO_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSFOOTERLOGO_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSFOOTERLOGO_DESCRIPTION'][$lang['id_lang']] = $tmp;
        }
        $fields['TVCMSFOOTERLOGO_IMG'] = Configuration::get('TVCMSFOOTERLOGO_IMG');

        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        return $fields;
    }

    public function hookdisplayHeader()
    {
         $this->context->controller->addJS($this->_path . 'views/js/front.js');
         $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function hookDisplayBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }// hookDisplayBackOfficeHeader()

    public function hookDisplayTopColumn()
    {
        return $this->hookdisplayFooter();
    }

    public function hookDisplayFooterBefore()
    {
        return $this->hookdisplayFooter();
    }

    public function hookdisplayFooterPart1()
    {
        return $this->hookdisplayFooter();
    }

    public function hookdisplayFooterPart2()
    {
        return $this->hookdisplayFooter();
    }

    public function hookdisplayFooter()
    {
        if (!Cache::isStored('tvcmsfooterlogo_display_home.tpl')) {
            $tvcms_obj = new TvcmsFooterLogoStatus();
            $show_fields = $tvcms_obj->fieldStatusInformation();

            $cookie = Context::getContext()->cookie;
            $id_lang = $cookie->id_lang;

            $path = _MODULE_DIR_ . $this->name . '/views/img/';
            $this->context->smarty->assign('path', $path);

            $this->context->smarty->assign('id_lang', $id_lang);
            $this->context->smarty->assign('show_fields', $show_fields);
            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            Cache::store('tvcmsfooterlogo_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmsfooterlogo_display_home.tpl');
    }
}
