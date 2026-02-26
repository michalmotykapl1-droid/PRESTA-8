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

include_once 'classes/tvcmsleftsideofferbanner_image_upload.class.php';

class TvcmsLeftSideOfferBanner extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'tvcmsleftsideofferbanner';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Left Side Offer Banner');
        $this->description = $this->l('This is Show Left Side Offer Banner in Front Side');

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
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('displayHome')            // <--- DODANE
            && $this->registerHook('displayWrapperBottom');  // <--- DODANE
    }

    public function createDefaultData()
    {
        $result = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $result['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$lang['id_lang']] = 'This is Caption';
        }
        Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME', 'demo_img_1.jpg');
        $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
        $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'demo_img_1.jpg');
        $width = $imagedata[0];
        $height = $imagedata[1];
        Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH', $width);
        Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT', $height);
        Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_CAPTION', $result['TVCMSLEFTSIDEOFFERBANNER_CAPTION']);
        // Default option is :- "left", "center", "right", "none".
        Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE', 'none');
        Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_LINK', '#');
        Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_STATUS_ALL', 0);
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
            $tab->name[$lang['id_lang']] = 'Left Side Offer Banner';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
        Configuration::deleteByName('TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME');
        Configuration::deleteByName('TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH');
        Configuration::deleteByName('TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT');
        Configuration::deleteByName('TVCMSLEFTSIDEOFFERBANNER_CAPTION');
        Configuration::deleteByName('TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE');
        Configuration::deleteByName('TVCMSLEFTSIDEOFFERBANNER_LINK');
        Configuration::deleteByName('TVCMSLEFTSIDEOFFERBANNER_STATUS_ALL');

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
        if (((bool) Tools::isSubmit('submittvcmsleftsideofferbanner')) == true && '0' == Tools::getValue('tvinstalldata')) {
            $obj_image = new TvcmsLeftSideOfferBannerImageUpload();
            $languages = Language::getLanguages(false);

            if ($_FILES['TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME']) {
                $old_img = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME');
                $old_width = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH');
                $old_height = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT');
                $ans = $obj_image->imageUploading($_FILES['TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME'], $old_img);
                if ($ans['success']) {
                    $file_name = $ans['name'];
                    $width = $ans['width'];
                    $height = $ans['height'];
                    Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME', $file_name);
                    Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH', $width);
                    Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT', $height);
                } else {
                    $old_img = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME');
                    Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME', $old_img);
                    Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH', $old_width);
                    Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT', $old_height);
                }
            }

            foreach ($languages as $lang) {
                $tmp = Tools::getValue('TVCMSLEFTSIDEOFFERBANNER_CAPTION_' . $lang['id_lang']);
                $result['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSLEFTSIDEOFFERBANNER_CAPTION'];
            Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_CAPTION', $tmp);

            $tmp = Tools::getValue('TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE');
            Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE', $tmp);

            $tmp = Tools::getValue('TVCMSLEFTSIDEOFFERBANNER_LINK');
            Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_LINK', $tmp);

            $tmp = Tools::getValue('TVCMSLEFTSIDEOFFERBANNER_STATUS_ALL');
            Configuration::updateValue('TVCMSLEFTSIDEOFFERBANNER_STATUS_ALL', $tmp);

            $this->clearCustomSmartyCache('tvcmsleftsideofferbanner_display_home.tpl');

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
        $helper->submit_action = 'submittvcmsleftsideofferbanner';
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
                'title' => $this->l('Left Side Offer Banner Settings'),
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
                        'name' => 'TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME',
                        'type' => 'file_upload',
                        'label' => $this->l('Image'),
                    ],
                    [
                        'col' => 6,
                        'name' => 'TVCMSLEFTSIDEOFFERBANNER_CAPTION',
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->l('Image Caption'),
                        'desc' => $this->l('Enter image caption'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Display Banner Side'),
                        'name' => 'TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE',
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
                        'name' => 'TVCMSLEFTSIDEOFFERBANNER_LINK',
                        'type' => 'text',
                        'label' => $this->l('Link'),
                        'desc' => $this->l('Enter image link'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Show In All Page'),
                        'name' => 'TVCMSLEFTSIDEOFFERBANNER_STATUS_ALL',
                        'desc' => $this->l('Note: Yes status means show in all pages, No means show in only homepage'),
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
            $a = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_CAPTION', $lang['id_lang']);
            $fields['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$lang['id_lang']] = $a;
        }

        $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME');
        $fields['TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME'] = $tmp;

        $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH');
        $fields['TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH'] = $tmp;

        $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT');
        $fields['TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT'] = $tmp;

        $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE');
        $fields['TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE'] = $tmp;

        $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_LINK');
        $fields['TVCMSLEFTSIDEOFFERBANNER_LINK'] = $tmp;

        $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_STATUS_ALL');
        $fields['TVCMSLEFTSIDEOFFERBANNER_STATUS_ALL'] = $tmp;

        return $fields;
    }

    public function hookdisplayBackOfficeHeader()
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

    public function hookdisplayRightColumn()
    {
        return $this->showResult();
    }

    public function hookdisplayLeftColumn()
    {
        return $this->showResult();
    }

    public function showResult()
    {
        $data = [];

        if (!Cache::isStored('tvcmsleftsideofferbanner_display_home.tpl')) {
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $a = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_CAPTION', $lang['id_lang']);
                $data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$lang['id_lang']] = $a;
            }
            $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME');
            $data['TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME'] = $tmp;

            $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH');
            $data['TVCMSLEFTSIDEOFFERBANNER_IMAGE_WIDTH'] = $tmp;

            $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT');
            $data['TVCMSLEFTSIDEOFFERBANNER_IMAGE_HEIGHT'] = $tmp;

            $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE');
            $data['TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE'] = $tmp;

            $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_LINK');
            $data['TVCMSLEFTSIDEOFFERBANNER_LINK'] = $tmp;

            $tmp = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_STATUS_ALL');
            $data['TVCMSLEFTSIDEOFFERBANNER_STATUS_ALL'] = $tmp;

            $AllPageShow = Configuration::get('TVCMSLEFTSIDEOFFERBANNER_STATUS_ALL');
            $this->context->smarty->assign('AllPageShow', $AllPageShow);
            $cookie = Context::getContext()->cookie;
            $id_lang = $cookie->id_lang;

            $path = _MODULE_DIR_ . $this->name . '/views/img/';
            $this->context->smarty->assign('path', $path);

            $this->context->smarty->assign('language_id', $id_lang);
            $this->context->smarty->assign('data', $data);

            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            Cache::store('tvcmsleftsideofferbanner_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmsleftsideofferbanner_display_home.tpl');
    }
public function hookDisplayHome($params)
    {
        return $this->showResult();
    }

    public function hookDisplayWrapperBottom($params)
    {
        return $this->showResult();
    }
}
