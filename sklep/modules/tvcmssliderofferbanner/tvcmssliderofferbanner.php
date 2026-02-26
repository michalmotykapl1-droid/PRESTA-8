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

include_once 'classes/tvcmssliderofferbanner_image_upload.class.php';

class TvcmsSliderOfferBanner extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'tvcmssliderofferbanner';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Slider Offer Banner');
        $this->description = $this->l('This is Show Slider Offer Banner in Front Side');

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
            && $this->registerHook('header')
            && $this->registerHook('backOfficeHeader');
        // $this->registerHook('displayHome');
    }

    public function createDefaultData()
    {
        $result = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $result['TVCMSSLIDEROFFERBANNER_CAPTION'][$lang['id_lang']] = 'This is Caption';
            $result['TVCMSSLIDEROFFERBANNER_CAPTION_2'][$lang['id_lang']] = 'This is Caption';
        }
        $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
        $imagedata_1 = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'demo_img_1.jpg');
        $width_1 = $imagedata_1[0];
        $height_1 = $imagedata_1[1];
        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME', 'demo_img_1.jpg');
        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_WIDTH_1', $width_1);
        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_HEIGHT_1', $height_1);

        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_CAPTION', $result['TVCMSSLIDEROFFERBANNER_CAPTION']);

        // Default option is :- "left", "center", "right", "none".
        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE', 'none');
        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_LINK', '#');

        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2', 'demo_img_2.jpg');
        $imagedata_2 = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'demo_img_2.jpg');
        $width_2 = $imagedata_2[0];
        $height_2 = $imagedata_2[1];
        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_WIDTH', $width_2);
        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_HEIGHT', $height_2);
        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_CAPTION_2', $result['TVCMSSLIDEROFFERBANNER_CAPTION_2']);

        // Default option is :- "left", "center", "right", "none".
        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE_2', 'none');
        Configuration::updateValue('TVCMSSLIDEROFFERBANNER_LINK_2', '#');
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
            $tab->name[$lang['id_lang']] = 'Slider Offer Banner';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_IMAGE_NAME');
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_WIDTH_1');
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_HEIGHT_1');
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_CAPTION');
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE');
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_LINK');

        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2');
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_WIDTH');
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_HEIGHT');
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_CAPTION_2');
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE_2');
        Configuration::deleteByName('TVCMSSLIDEROFFERBANNER_LINK_2');

        $this->uninstallTab();

        return parent::uninstall();
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
        $messages = '';
        $tmp = [];
        $result = [];
        if (Tools::isSubmit('submitTvcmsSampleinstall') && '1' == Tools::getValue('tvinstalldata')) {
            $this->createDefaultData();
        }
        if (((bool) Tools::isSubmit('submittvcmssliderofferbanner')) == true && '0' == Tools::getValue('tvinstalldata')) {
            $obj_image = new TvcmsSliderOfferBannerImageUpload();
            $languages = Language::getLanguages(false);

            if ($_FILES['TVCMSSLIDEROFFERBANNER_IMAGE_NAME']) {
                $old_img = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME');
                $ans = $obj_image->imageUploading($_FILES['TVCMSSLIDEROFFERBANNER_IMAGE_NAME'], $old_img);
                if ($ans['success']) {
                    $file_name = $ans['name'];
                    $width_1 = $ans['width'];
                    $height_1 = $ans['height'];
                    Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME', $file_name);
                    Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_WIDTH_1', $width_1);
                    Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_HEIGHT_1', $height_1);
                } else {
                    $old_img = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME');
                    Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME', $old_img);
                }
            }

            if ($_FILES['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2']) {
                $old_img = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2');
                $ans = $obj_image->imageUploading($_FILES['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2'], $old_img);
                if ($ans['success']) {
                    $file_name = $ans['name'];
                    $width_2 = $ans['width'];
                    $height_2 = $ans['height'];
                    Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2', $file_name);
                    Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_WIDTH', $width_2);
                    Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_HEIGHT', $height_2);
                } else {
                    $old_img = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2');
                    Configuration::updateValue('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2', $old_img);
                }
            }

            foreach ($languages as $lang) {
                $tmp = Tools::getValue('TVCMSSLIDEROFFERBANNER_CAPTION_' . $lang['id_lang']);
                $result['TVCMSSLIDEROFFERBANNER_CAPTION'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSSLIDEROFFERBANNER_CAPTION_2_' . $lang['id_lang']);
                $result['TVCMSSLIDEROFFERBANNER_CAPTION_2'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSSLIDEROFFERBANNER_CAPTION'];
            Configuration::updateValue('TVCMSSLIDEROFFERBANNER_CAPTION', $tmp);

            $tmp = Tools::getValue('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE');
            Configuration::updateValue('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE', $tmp);

            $tmp = Tools::getValue('TVCMSSLIDEROFFERBANNER_LINK');
            Configuration::updateValue('TVCMSSLIDEROFFERBANNER_LINK', $tmp);

            $tmp = $result['TVCMSSLIDEROFFERBANNER_CAPTION_2'];
            Configuration::updateValue('TVCMSSLIDEROFFERBANNER_CAPTION_2', $tmp);

            $tmp = Tools::getValue('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE_2');
            Configuration::updateValue('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE_2', $tmp);

            $tmp = Tools::getValue('TVCMSSLIDEROFFERBANNER_LINK_2');
            Configuration::updateValue('TVCMSSLIDEROFFERBANNER_LINK_2', $tmp);

            $this->clearCustomSmartyCache('tvcmssliderofferbanner_display_home.tpl');

            $messages .= $this->displayConfirmation($this->l('Offer Banner Information is Updated'));
        }

        $output = $messages . $this->renderForm();

        return $output;
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
        $helper->submit_action = 'submittvcmssliderofferbanner';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
             . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(), // Add values for your inputs
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Slider Offer Banner'),
                'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'col' => 12,
                        'type' => 'BtnInstallData',
                        'name' => 'BtnInstallData',
                        'label' => '',
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSSLIDEROFFERBANNER_IMAGE_NAME',
                        'type' => 'file_upload',
                        'label' => $this->l('Image'),
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSSLIDEROFFERBANNER_CAPTION',
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->l('Image Caption'),
                        'desc' => $this->l('Enter banner image caption'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Display Banner Side'),
                        'name' => 'TVCMSSLIDEROFFERBANNER_CAPTION_SIDE',
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 'left',
                                    'name' => 'Left',
                                ],
                                [
                                    'id_option' => 'top-left',
                                    'name' => 'Top Left',
                                ],
                                [
                                    'id_option' => 'bottom-left',
                                    'name' => 'Top Bottom',
                                ],
                                [
                                    'id_option' => 'center',
                                    'name' => 'Center',
                                ],
                                [
                                    'id_option' => 'top-center',
                                    'name' => 'Top Center',
                                ],
                                [
                                    'id_option' => 'bottom-center',
                                    'name' => 'Bottom Center',
                                ],
                                [
                                    'id_option' => 'right',
                                    'name' => 'Right',
                                ],
                                [
                                    'id_option' => 'top-right',
                                    'name' => 'Top Right',
                                ],
                                [
                                    'id_option' => 'bottom-right',
                                    'name' => 'Bottom Right',
                                ],
                                [
                                    'id_option' => 'none',
                                    'name' => 'None',
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSSLIDEROFFERBANNER_LINK',
                        'type' => 'text',
                        'label' => $this->l('Link'),
                        'desc' => $this->l('Enter Image Link'),
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2',
                        'type' => 'file_upload_2',
                        'label' => $this->l('Image 2'),
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSSLIDEROFFERBANNER_CAPTION_2',
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->l('Image Caption 2'),
                        'desc' => $this->l('Enter banner image caption 2'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Display Banner 2 Side '),
                        'name' => 'TVCMSSLIDEROFFERBANNER_CAPTION_SIDE_2',
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 'left',
                                    'name' => 'Left',
                                ],
                                [
                                    'id_option' => 'top-left',
                                    'name' => 'Top Left',
                                ],
                                [
                                    'id_option' => 'bottom-left',
                                    'name' => 'Top Bottom',
                                ],
                                [
                                    'id_option' => 'center',
                                    'name' => 'Center',
                                ],
                                [
                                    'id_option' => 'top-center',
                                    'name' => 'Top Center',
                                ],
                                [
                                    'id_option' => 'bottom-center',
                                    'name' => 'Bottom Center',
                                ],
                                [
                                    'id_option' => 'right',
                                    'name' => 'Right',
                                ],
                                [
                                    'id_option' => 'top-right',
                                    'name' => 'Top Right',
                                ],
                                [
                                    'id_option' => 'bottom-right',
                                    'name' => 'Bottom Right',
                                ],
                                [
                                    'id_option' => 'none',
                                    'name' => 'None',
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSSLIDEROFFERBANNER_LINK_2',
                        'type' => 'text',
                        'label' => $this->l('Link 2'),
                        'desc' => $this->l('Enter Image 2 Link'),
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        $fields = [];

        $languages = Language::getLanguages();
        foreach ($languages as $lang) {
            $a = Configuration::get('TVCMSSLIDEROFFERBANNER_CAPTION', $lang['id_lang']);
            $fields['TVCMSSLIDEROFFERBANNER_CAPTION'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSSLIDEROFFERBANNER_CAPTION_2', $lang['id_lang']);
            $fields['TVCMSSLIDEROFFERBANNER_CAPTION_2'][$lang['id_lang']] = $a;
        }

        $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME');
        $fields['TVCMSSLIDEROFFERBANNER_IMAGE_NAME'] = $tmp;

        $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_WIDTH_1');
        $fields['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_WIDTH_1'] = $tmp;

        $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_HEIGHT_1');
        $fields['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_HEIGHT_1'] = $tmp;

        $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE');
        $fields['TVCMSSLIDEROFFERBANNER_CAPTION_SIDE'] = $tmp;

        $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_LINK');
        $fields['TVCMSSLIDEROFFERBANNER_LINK'] = $tmp;

        $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2');
        $fields['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2'] = $tmp;

        $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_WIDTH');
        $fields['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_WIDTH'] = $tmp;

        $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_HEIGHT');
        $fields['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_HEIGHT'] = $tmp;

        $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE_2');
        $fields['TVCMSSLIDEROFFERBANNER_CAPTION_SIDE_2'] = $tmp;

        $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_LINK_2');
        $fields['TVCMSSLIDEROFFERBANNER_LINK_2'] = $tmp;

        return $fields;
    }

    public function hookBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function hookDisplayHome()
    {
        return $this->showResult();
    }

    public function showResult()
    {
        $data = [];

        if (!Cache::isStored('tvcmssliderofferbanner_display_home.tpl')) {
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $a = Configuration::get('TVCMSSLIDEROFFERBANNER_CAPTION', $lang['id_lang']);
                $data['TVCMSSLIDEROFFERBANNER_CAPTION'][$lang['id_lang']] = $a;

                $a = Configuration::get('TVCMSSLIDEROFFERBANNER_CAPTION_2', $lang['id_lang']);
                $data['TVCMSSLIDEROFFERBANNER_CAPTION_2'][$lang['id_lang']] = $a;
            }
            $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME');
            $data['TVCMSSLIDEROFFERBANNER_IMAGE_NAME'] = $tmp;

            $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_WIDTH_1');
            $data['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_WIDTH_1'] = $tmp;

            $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_HEIGHT_1');
            $data['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_HEIGHT_1'] = $tmp;

            $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE');
            $data['TVCMSSLIDEROFFERBANNER_CAPTION_SIDE'] = $tmp;

            $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_LINK');
            $data['TVCMSSLIDEROFFERBANNER_LINK'] = $tmp;

            $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2');
            $data['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2'] = $tmp;

            $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_WIDTH');
            $data['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_WIDTH'] = $tmp;

            $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_HEIGHT');
            $data['TVCMSSLIDEROFFERBANNER_IMAGE_NAME_2_HEIGHT'] = $tmp;

            $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_CAPTION_SIDE_2');
            $data['TVCMSSLIDEROFFERBANNER_CAPTION_SIDE_2'] = $tmp;

            $tmp = Configuration::get('TVCMSSLIDEROFFERBANNER_LINK_2');
            $data['TVCMSSLIDEROFFERBANNER_LINK_2'] = $tmp;

            $cookie = Context::getContext()->cookie;
            $id_lang = $cookie->id_lang;

            $path = _MODULE_DIR_ . $this->name . '/views/img/';
            $this->context->smarty->assign('path', $path);

            $this->context->smarty->assign('language_id', $id_lang);
            $this->context->smarty->assign('data', $data);

            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            Cache::store('tvcmssliderofferbanner_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmssliderofferbanner_display_home.tpl');
    }
}
