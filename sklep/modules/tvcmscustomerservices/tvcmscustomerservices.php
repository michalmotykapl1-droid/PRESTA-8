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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

include_once 'classes/tvcmscustomer_services_image_upload.class.php';
include_once 'classes/tvcmscustomer_service_status.class.php';

class TvcmsCustomerServices extends Module
{
    public function __construct()
    {
        $this->name = 'tvcmscustomerservices';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Customer Services');
        $this->description = $this->l('Its Show Customer Services in Front Side');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        $this->installTab();
        // $this->createDefaultData();
        Tools::clearSmartyCache();

        return parent::install()
            && $this->registerHook('displayHeader')
            // && $this->registerHook('displayHome')
            && $this->registerHook('displayWrapperBottom')
            && $this->registerHook('displayLeftColumn')
            // $this->registerHook('displayLeftColumn');
            && $this->registerHook('displayBackOfficeHeader');
        // $this->registerHook('displayWrapperTop');
        // $this->registerHook('displayTopColumn');
        // $this->registerHook('displayFooterBefore');
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
            $tab->name[$lang['id_lang']] = 'Customer Services';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function createDefaultData()
    {
        $result = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $result['TVCMSCUSTOMERSERVICES_TITLE'][$lang['id_lang']] = 'Our Services';
            $result['TVCMSCUSTOMERSERVICES_TITLE_LEFT'][$lang['id_lang']] = 'Our Services';
            $result['TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION'][$lang['id_lang']] = 'Sub Description';
            $result['TVCMSCUSTOMERSERVICES_DESCRIPTION'][$lang['id_lang']] = 'Description';
            $result['TVCMSCUSTOMERSERVICES_IMG'][$lang['id_lang']] = 'demo_main_img.jpg';

            $result['TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE'][$lang['id_lang']] = 'demo_img_1.png';
            $result['TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION'][$lang['id_lang']] = '100% Secure Payments';
            $result['TVCMSCUSTOMERSERVICES_SERVICES_1_DESC'][$lang['id_lang']] = 'Money your card details to a '
            . 'much more sequred place';

            $result['TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE'][$lang['id_lang']] = 'demo_img_2.png';
            $result['TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION'][$lang['id_lang']] = 'Trustpay';
            $result['TVCMSCUSTOMERSERVICES_SERVICES_2_DESC'][$lang['id_lang']] = '100% Payment protection'
            . ' easy return policy';

            $result['TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE'][$lang['id_lang']] = 'demo_img_3.png';
            $result['TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION'][$lang['id_lang']] = 'Support 24/7';
            $result['TVCMSCUSTOMERSERVICES_SERVICES_3_DESC'][$lang['id_lang']] = 'Got a qustion? Look no further.'
            . 'Browse ourFAQs or submit your query here.';

            $result['TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE'][$lang['id_lang']] = 'demo_img_4.png';
            $result['TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION'][$lang['id_lang']] = 'Shop on the go';
            $result['TVCMSCUSTOMERSERVICES_SERVICES_4_DESC'][$lang['id_lang']] = 'Download the app and get exciting app'
            . 'only offers at your fingertips';

            $result['TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE'][$lang['id_lang']] = 'demo_img_5.png';
            $result['TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION'][$lang['id_lang']] = 'Payment Method';
            $result['TVCMSCUSTOMERSERVICES_SERVICES_5_DESC'][$lang['id_lang']] = 'Secure payment';
        }
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_TITLE', $result['TVCMSCUSTOMERSERVICES_TITLE']);
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_TITLE_LEFT', $result['TVCMSCUSTOMERSERVICES_TITLE_LEFT']);
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SHOW', 0);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION', $tmp);
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_DESCRIPTION', $result['TVCMSCUSTOMERSERVICES_DESCRIPTION']);
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_IMG', $result['TVCMSCUSTOMERSERVICES_IMG']);

        // Services 1
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE', $tmp);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION', $tmp);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_1_DESC'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_1_DESC', $tmp);
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_1_STATUS', 1);

        // Services 2
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE', $tmp);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION', $tmp);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_2_DESC'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_2_DESC', $tmp);
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_2_STATUS', 1);

        // Services 3
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE', $tmp);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION', $tmp);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_3_DESC'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_3_DESC', $tmp);
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_3_STATUS', 1);

        // Services 4
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE', $tmp);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION', $tmp);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_4_DESC'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_4_DESC', $tmp);
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_4_STATUS', 1);

        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE', $tmp);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION', $tmp);
        $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_5_DESC'];
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_5_DESC', $tmp);
        Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_5_STATUS', 1);
    }

    public function uninstall()
    {
        Tools::clearSmartyCache();
        $this->uninstallTab();
        $this->deleteVariable();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_TITLE');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SHOW');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_TITLE_LEFT');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_DESCRIPTION');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_IMG');

        // Services 1
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_1_DESC');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_1_STATUS');

        // Services 2
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_2_DESC');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_2_STATUS');

        // Services 3
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_3_DESC');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_3_STATUS');

        // Services 4
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_4_DESC');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_4_STATUS');

        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_5_DESC');
        Configuration::deleteByName('TVCMSCUSTOMERSERVICES_SERVICES_5_STATUS');
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
        $message = '';
        $message = $this->postProcess();

        return $message . $this->renderForm();
    }

    public function postProcess()
    {
        $message = '';
        $languages = Language::getLanguages();
        $result = [];

        if (Tools::isSubmit('submitTvcmsCustomerServicesForm') && '0' == Tools::getValue('tvinstalldata')) {
            $obj_image = new TvcmsCustomerServicesImageUpload();
            foreach ($languages as $lang) {
                if (!empty($_FILES['TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE', $lang['id_lang']);
                    $result['TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE'][$lang['id_lang']] = $old_file;
                }

                if (!empty($_FILES['TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE', $lang['id_lang']);
                    $result['TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE'][$lang['id_lang']] = $old_file;
                }

                if (!empty($_FILES['TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE', $lang['id_lang']);
                    $result['TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE'][$lang['id_lang']] = $old_file;
                }

                if (!empty($_FILES['TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE', $lang['id_lang']);
                    $result['TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE'][$lang['id_lang']] = $old_file;
                }

                if (!empty($_FILES['TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE', $lang['id_lang']);
                    $result['TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE'][$lang['id_lang']] = $old_file;
                }

                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_1_DESC_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SERVICES_1_DESC'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_2_DESC_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SERVICES_2_DESC'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_3_DESC_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SERVICES_3_DESC'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_4_DESC_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SERVICES_4_DESC'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_5_DESC_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SERVICES_5_DESC'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE', $tmp);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION', $tmp);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_1_DESC'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_1_DESC', $tmp);
            $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_1_STATUS');
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_1_STATUS', $tmp);

            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE', $tmp);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION', $tmp);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_2_DESC'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_2_DESC', $tmp);
            $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_2_STATUS');
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_2_STATUS', $tmp);

            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE', $tmp);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION', $tmp);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_3_DESC'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_3_DESC', $tmp);
            $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_3_STATUS');
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_3_STATUS', $tmp);

            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE', $tmp);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION', $tmp);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_4_DESC'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_4_DESC', $tmp);
            $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_4_STATUS');
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_4_STATUS', $tmp);

            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE', $tmp);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION', $tmp);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SERVICES_5_DESC'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_5_DESC', $tmp);
            $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SERVICES_5_STATUS');
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SERVICES_5_STATUS', $tmp);

            $this->clearCustomSmartyCache('tvcmscustomerservices_display_home.tpl');
            $this->clearCustomSmartyCache('tvcmscustomerservices_display_left_column.tpl');
            $message .= $this->displayConfirmation($this->l('Customer Services is Updated'));
        }

        if (Tools::isSubmit('submittvcmsCustomerServicesMainTitleForm') && '0' == Tools::getValue('tvinstalldata')) {
            $languages = Language::getLanguages();
            $obj_image = new TvcmsCustomerServicesImageUpload();
            foreach ($languages as $lang) {
                if (!empty($_FILES['TVCMSCUSTOMERSERVICES_IMG_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_IMG', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSCUSTOMERSERVICES_IMG_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSCUSTOMERSERVICES_IMG'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSCUSTOMERSERVICES_IMG'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSCUSTOMERSERVICES_IMG', $lang['id_lang']);
                    $result['TVCMSCUSTOMERSERVICES_IMG'][$lang['id_lang']] = $old_file;
                }

                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_TITLE_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_TITLE_LEFT_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_TITLE_LEFT'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSCUSTOMERSERVICES_DESCRIPTION'][$lang['id_lang']] = $tmp;
            }
            $tmp = Tools::getValue('TVCMSCUSTOMERSERVICES_SHOW');
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SHOW', $tmp);
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_IMG', $result['TVCMSCUSTOMERSERVICES_IMG']);
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_TITLE', $result['TVCMSCUSTOMERSERVICES_TITLE']);
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_TITLE_LEFT', $result['TVCMSCUSTOMERSERVICES_TITLE_LEFT']);
            $tmp = $result['TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION', $tmp);

            $tmp = $result['TVCMSCUSTOMERSERVICES_DESCRIPTION'];
            Configuration::updateValue('TVCMSCUSTOMERSERVICES_DESCRIPTION', $tmp);
            $message .= $this->displayConfirmation($this->l('Customer Service Main Title is Updated.'));
        }
        Tools::clearSmartyCache();

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

        $tvcms_obj = new TvcmsCustomerServicesStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        if ($show_fields['main_status']) {
            $form[] = $this->tvcmsCustomerServicesMainTitleForm();
        }

        if ($show_fields['record_form']) {
            $form[] = $this->tvcmsCustomerServicesForm();
        }

        return $helper->generateForm($form);
    }

    protected function tvcmsCustomerServicesMainTitleForm()
    {
        $tvcms_obj = new TvcmsCustomerServicesStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        if ($show_fields['main_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMERSERVICES_TITLE',
                    'label' => $this->l('Homepage Main Title'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_title_left']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMERSERVICES_TITLE_LEFT',
                    'label' => $this->l('Left Column Title '),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_sub_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION',
                    'label' => $this->l('Short Description'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_description']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMERSERVICES_DESCRIPTION',
                    'label' => $this->l('Description'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_image']) {
            $input[] = [
                        'type' => 'image_file',
                        'name' => 'TVCMSCUSTOMERSERVICES_IMG',
                        'label' => $this->l('Image'),
                ];
        }
        if ($show_fields['show_all']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Show In All Page'),
                        'name' => 'TVCMSCUSTOMERSERVICES_SHOW',
                        'desc' => 'Note: Yes status means show in all pages, no means show in only homepage',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Yes'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('No'),
                            ],
                        ],
            ];
        }

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Customer Service Main Title'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submittvcmsCustomerServicesMainTitleForm',
                ],
            ],
        ];
    }

    protected function tvcmsCustomerServicesForm()
    {
        $tvcms_obj = new TvcmsCustomerServicesStatus();
        $result = $tvcms_obj->fieldStatusInformation();
        $input = [];
        $input[] = [
                        'col' => 12,
                        'type' => 'BtnInstallData',
                        'name' => 'BtnInstallData',
                        'label' => '',
                    ];

        for ($i = 1; $i <= $result['num_services']; ++$i) {
            if (1 == $result['image_upload']) {
                $input[] = [
                        'type' => 'image_file',
                        'name' => 'TVCMSCUSTOMERSERVICES_SERVICES_' . $i . '_IMAGE',
                        'label' => $this->l('Services Image ' . $i),
                    ];
            }

            $input[] = [
                        'col' => 8,
                        'type' => 'text',
                        'name' => 'TVCMSCUSTOMERSERVICES_SERVICES_' . $i . '_CAPTION',
                        'label' => $this->l('Services caption ' . $i),
                        'lang' => true,
                        'desc' => $this->l('Display Caption Of Service ' . $i),
                    ];

            $input[] = [
                        'col' => 8,
                        'type' => 'text',
                        'name' => 'TVCMSCUSTOMERSERVICES_SERVICES_' . $i . '_DESC',
                        'label' => $this->l('Services Description ' . $i),
                        'lang' => true,
                        'desc' => $this->l('Display description of service ' . $i),
                    ];

            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Service Status ' . $i),
                        'name' => 'TVCMSCUSTOMERSERVICES_SERVICES_' . $i . '_STATUS',
                        'desc' => $this->l('Hide or show service ' . $i),
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
                'title' => $this->l('CUSTOMER SERVICE'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsCustomerServicesForm',
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $fields = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_TITLE', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_TITLE'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_TITLE_LEFT', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_TITLE_LEFT'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_DESCRIPTION'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_IMG', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_IMG'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_1_DESC', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_1_DESC'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_2_DESC', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_2_DESC'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_3_DESC', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_3_DESC'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_4_DESC', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_4_DESC'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION'][$lang['id_lang']] = $a;
            $a = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_5_DESC', $lang['id_lang']);
            $fields['TVCMSCUSTOMERSERVICES_SERVICES_5_DESC'][$lang['id_lang']] = $a;
        }

        $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_1_STATUS');
        $fields['TVCMSCUSTOMERSERVICES_SERVICES_1_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SHOW');
        $fields['TVCMSCUSTOMERSERVICES_SHOW'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_2_STATUS');
        $fields['TVCMSCUSTOMERSERVICES_SERVICES_2_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_3_STATUS');
        $fields['TVCMSCUSTOMERSERVICES_SERVICES_3_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_4_STATUS');
        $fields['TVCMSCUSTOMERSERVICES_SERVICES_4_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_5_STATUS');
        $fields['TVCMSCUSTOMERSERVICES_SERVICES_5_STATUS'] = $tmp;

        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        return $fields;
    }

    public function showFrontData()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $result = [];
        $tempStatus = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_1_STATUS');
        if (!empty($tempStatus) && 1 == $tempStatus) {
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_1_IMAGE', $id_lang);
            $result['service_1']['image'] = $tmp;
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_1_CAPTION', $id_lang);
            $result['service_1']['title'] = $tmp;
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_1_DESC', $id_lang);
            $result['service_1']['desc'] = $tmp;
            $result['service_1']['status'] = $tempStatus;
        }

        $tempStatus = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_2_STATUS');
        if (!empty($tempStatus) && 1 == $tempStatus) {
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_2_IMAGE', $id_lang);
            $result['service_2']['image'] = $tmp;
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_2_CAPTION', $id_lang);
            $result['service_2']['title'] = $tmp;
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_2_DESC', $id_lang);
            $result['service_2']['desc'] = $tmp;
            $result['service_2']['status'] = $tempStatus;
        }

        $tempStatus = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_3_STATUS');
        if (!empty($tempStatus) && 1 == $tempStatus) {
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_3_IMAGE', $id_lang);
            $result['service_3']['image'] = $tmp;
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_3_CAPTION', $id_lang);
            $result['service_3']['title'] = $tmp;
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_3_DESC', $id_lang);
            $result['service_3']['desc'] = $tmp;
            $result['service_3']['status'] = $tempStatus;
        }

        $tempStatus = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_4_STATUS');
        if (!empty($tempStatus) && 1 == $tempStatus) {
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_4_IMAGE', $id_lang);
            $result['service_4']['image'] = $tmp;
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_4_CAPTION', $id_lang);
            $result['service_4']['title'] = $tmp;
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_4_DESC', $id_lang);
            $result['service_4']['desc'] = $tmp;
            $result['service_4']['status'] = $tempStatus;
        }

        $tempStatus = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_5_STATUS');
        if (!empty($tempStatus) && 1 == $tempStatus) {
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_5_IMAGE', $id_lang);
            $result['service_5']['image'] = $tmp;
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_5_CAPTION', $id_lang);
            $result['service_5']['title'] = $tmp;
            $tmp = Configuration::get('TVCMSCUSTOMERSERVICES_SERVICES_5_DESC', $id_lang);
            $result['service_5']['desc'] = $tmp;
            $result['service_5']['status'] = $tempStatus;
        }

        return $result;
    }

    public function getArrMainTitle($main_heading, $main_heading_data)
    {
        if (!$main_heading['main_title'] || empty($main_heading_data['title'])) {
            $main_heading['main_title'] = false;
        }
        if (!$main_heading['main_sub_title'] || empty($main_heading_data['short_desc'])) {
            $main_heading['main_sub_title'] = false;
        }
        if (!$main_heading['main_description'] || empty($main_heading_data['desc'])) {
            $main_heading['main_description'] = false;
        }
        if (!$main_heading['main_image'] || empty($main_heading_data['image'])) {
            $main_heading['main_image'] = false;
        }
        if (!$main_heading['main_title']
            && !$main_heading['main_sub_title']
            && !$main_heading['main_description']
            && !$main_heading['main_image']) {
            $main_heading['main_status'] = false;
        }

        return $main_heading;
    }

    public function showFrontSideResult()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $tvcms_obj = new TvcmsCustomerServicesStatus();
        $main_heading = $tvcms_obj->fieldStatusInformation();

        if ($main_heading['main_status']) {
            $main_heading_data = [];
            $main_heading_data['title'] = Configuration::get('TVCMSCUSTOMERSERVICES_TITLE', $id_lang);
            $main_heading_data['title_left'] = Configuration::get('TVCMSCUSTOMERSERVICES_TITLE_LEFT', $id_lang);
            $main_heading_data['short_desc'] = Configuration::get('TVCMSCUSTOMERSERVICES_SUB_DESCRIPTION', $id_lang);
            $main_heading_data['desc'] = Configuration::get('TVCMSCUSTOMERSERVICES_DESCRIPTION', $id_lang);
            $main_heading_data['image'] = Configuration::get('TVCMSCUSTOMERSERVICES_IMG', $id_lang);
            $main_heading = $this->getArrMainTitle($main_heading, $main_heading_data);
            $main_heading['data'] = $main_heading_data;
        }

        $disArrResult = [];
        $disArrResult['data'] = $this->showFrontData();
        $disArrResult['status'] = empty($disArrResult['data']) ? false : true;
        $disArrResult['path'] = _MODULE_DIR_ . $this->name . '/views/img/';
        $disArrResult['id_lang'] = $id_lang;

        $this->context->smarty->assign('main_heading', $main_heading);
        $getLefttitle = Configuration::get('TVCMSCUSTOMERSERVICES_TITLE_LEFT', $id_lang);
        $this->context->smarty->assign('getLefttitle', $getLefttitle);
        $this->context->smarty->assign('dis_arr_result', $disArrResult);

        return $disArrResult['status'] ? true : false;
    }

    public function hookdisplayHeader()
    {
        $getSTATUS = Configuration::get('TVCMSCUSTOMERSERVICES_SHOW');
        $this->context->smarty->assign('getSTATUS', $getSTATUS);
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }// hookDisplayHeader()

    public function hookdisplayBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }// hookDisplayBackOfficeHeader()

    public function hookdisplayWrapperBottom()
    {
        return $this->hookDisplayHome();
    }

    public function hookdisplayHome()
    {
        // Cache wylaczony - modyfikacja
        $output = $this->showFrontSideResult();
        if ($output) {
            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
        } else {
            $output = '';
        }
        
        return $output;
    }

    public function hookdisplayRightColumn()
    {
        return $this->hookdisplayLeftColumn();
    }

    public function hookdisplayLeftColumn()
    {
        // Cache wylaczony - modyfikacja
        $output = $this->showFrontSideResult();
        if ($output) {
            $output = $this->display(__FILE__, 'views/templates/front/display_left_column.tpl');
        } else {
            $output = '';
        }
        
        return $output;
    }
}