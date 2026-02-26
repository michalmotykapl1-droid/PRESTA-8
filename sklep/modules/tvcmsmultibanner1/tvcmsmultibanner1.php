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

include_once 'classes/tvcmsmultibanner1_image_upload.class.php';
include_once 'classes/tvcmsmultibanner1_status.class.php';

class TvcmsMultiBanner1 extends Module
{
    protected $config_form = false;

    public $n = 3;

    public function __construct()
    {
        $this->name = 'tvcmsmultibanner1';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - MultiBanner1');
        $this->description = $this->l('This is Show Multi-Banner1 in Front Side');

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
            && $this->registerHook('backOfficeHeader')
            && $this->registerHook('displayWrapperTop')
            && $this->registerHook('displayContentWrapperTop')
            && $this->registerHook('displayHome');
    }

    public function createDefaultData()
    {
        $result = [];
        $languages = Language::getLanguages();
        for ($i = 1; $i <= $this->n; ++$i) {
            foreach ($languages as $lang) {
                $result['TVCMSMULTIBANNER1_IMAGE_NAME_' . $i][$lang['id_lang']] = 'demo_img_' . $i . '.jpg';
                $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
                $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'demo_img_' . $i . '.jpg');
                $width = $imagedata[0];
                $height = $imagedata[1];
                $result['TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i][$lang['id_lang']] = $width;
                $result['TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i][$lang['id_lang']] = $height;
                $result['TVCMSMULTIBANNER1_CAPTION_' . $i][$lang['id_lang']] = '<h4>Laptops & Acessories</h4>'
                     . '<h6>Upto 80% Off</h6><div  class="tvmultibanner-btn"><a href=\'#\'>Shop Now'
                     . '</a></div>';
                // Default option is :- "left", "center", "right", "none".
                $result['TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i][$lang['id_lang']] = 'none';
                $result['TVCMSMULTIBANNER1_LINK_' . $i][$lang['id_lang']] = '#';
            }
            $tmp = $result['TVCMSMULTIBANNER1_IMAGE_NAME_' . $i];
            Configuration::updateValue('TVCMSMULTIBANNER1_IMAGE_NAME_' . $i, $tmp);

            $tmp = $result['TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i];
            Configuration::updateValue('TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i, $tmp);

            $tmp = $result['TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i];
            Configuration::updateValue('TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i, $tmp);

            $tmp = $result['TVCMSMULTIBANNER1_CAPTION_' . $i];
            Configuration::updateValue('TVCMSMULTIBANNER1_CAPTION_' . $i, $tmp, true);

            $tmp = $result['TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i];
            Configuration::updateValue('TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i, $tmp, true);

            $tmp = $result['TVCMSMULTIBANNER1_LINK_' . $i];
            Configuration::updateValue('TVCMSMULTIBANNER1_LINK_' . $i, $tmp, true);
        }
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
            $tab->name[$lang['id_lang']] = 'MultiBanner 1';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
        for ($i = 1; $i <= $this->n; ++$i) {
            Configuration::deleteByName('TVCMSMULTIBANNER1_IMAGE_NAME_' . $i);
            Configuration::deleteByName('TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i);
            Configuration::deleteByName('TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i);
            Configuration::deleteByName('TVCMSMULTIBANNER1_CAPTION_' . $i);
            Configuration::deleteByName('TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i);
            Configuration::deleteByName('TVCMSMULTIBANNER1_LINK_' . $i);
        }
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
        $result = [];
        $img = '';
        $notic = '';
        if (Tools::isSubmit('submitTvcmsSampleinstall') && '1' == Tools::getValue('tvinstalldata')) {
            $this->createDefaultData();
        }
        if (((bool) Tools::isSubmit('submittvcmsmultibanner1')) == true && '0' == Tools::getValue('tvinstalldata')) {
            for ($i = 1; $i <= $this->n; ++$i) {
                $languages = Language::getLanguages();
                foreach ($languages as $lang) {
                    $img = 'TVCMSMULTIBANNER1_IMAGE_NAME_' . $i . '_' . $lang['id_lang'];

                    if (!empty($_FILES[$img]['name'])) {
                        $this->tvcmsobj = new TvcmsMultiBanner1ImageUpload();
                        $old_file = Configuration::get('TVCMSMULTIBANNER1_IMAGE_NAME_' . $i, $lang['id_lang']);
                        $result = $this->tvcmsobj->imageUploading($_FILES[$img], $old_file);
                        if ($result['success']) {
                            $notic = $this->l('Image  ' . $i . ' is Save in ' . $lang['name'] . '.');
                            $messages .= $this->displayConfirmation($notic);
                            $result['TVCMSMULTIBANNER1_IMAGE_NAME_' . $i][$lang['id_lang']] = $result['name'];
                            $result['TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i][$lang['id_lang']] = $result['width'];
                            $result['TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i][$lang['id_lang']] = $result['height'];
                        } else {
                            $notic = $this->l('Image Upload Problem in Image ' . $i . ' in ' . $lang['name'] . '.');
                            $messages .= $this->displayError($notic);
                            $name = Configuration::get('TVCMSMULTIBANNER1_IMAGE_NAME_' . $i, $lang['id_lang']);
                            $result['TVCMSMULTIBANNER1_IMAGE_NAME_' . $i][$lang['id_lang']] = $name;
                            $width = Configuration::get('TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i, $lang['id_lang']);
                            $result['TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i][$lang['id_lang']] = $width;
                            $height = Configuration::get('TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i, $lang['id_lang']);
                            $result['TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i][$lang['id_lang']] = $height;
                        }
                    } else {
                        $name = Configuration::get('TVCMSMULTIBANNER1_IMAGE_NAME_' . $i, $lang['id_lang']);
                        $result['TVCMSMULTIBANNER1_IMAGE_NAME_' . $i][$lang['id_lang']] = $name;
                        $width = Configuration::get('TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i, $lang['id_lang']);
                        $result['TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i][$lang['id_lang']] = $width;
                        $height = Configuration::get('TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i, $lang['id_lang']);
                        $result['TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i][$lang['id_lang']] = $height;
                    }

                    $a = Tools::getValue('TVCMSMULTIBANNER1_CAPTION_' . $i . '_' . $lang['id_lang']);
                    $result['TVCMSMULTIBANNER1_CAPTION_' . $i][$lang['id_lang']] = $a;

                    $a = Tools::getValue('TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i . '_' . $lang['id_lang']);
                    $result['TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i][$lang['id_lang']] = $a;

                    $a = Tools::getValue('TVCMSMULTIBANNER1_LINK_' . $i . '_' . $lang['id_lang']);
                    $result['TVCMSMULTIBANNER1_LINK_' . $i][$lang['id_lang']] = $a;
                }

                $tmp = $result['TVCMSMULTIBANNER1_IMAGE_NAME_' . $i];
                Configuration::updateValue('TVCMSMULTIBANNER1_IMAGE_NAME_' . $i, $tmp);

                $tmp = $result['TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i];
                Configuration::updateValue('TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i, $tmp);

                $tmp = $result['TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i];
                Configuration::updateValue('TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i, $tmp);

                Configuration::updateValue(
                    'TVCMSMULTIBANNER1_CAPTION_' . $i,
                    $result['TVCMSMULTIBANNER1_CAPTION_' . $i],
                    true
                );
                Configuration::updateValue('TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i, $result['TVCMSMUL'
                     . 'TIBANNER1_CAPTION_SIDE_' . $i]);
                Configuration::updateValue('TVCMSMULTIBANNER1_LINK_' . $i, $result['TVCMSMULTIBANNER1_LINK_' . $i]);

                $messages .= $this->displayConfirmation($this->l('Record is Save in Number ' . $i));
            }
            $this->clearCustomSmartyCache('tvcmsmultibanner1_display_home.tpl');
        }
        $this->context->smarty->assign('total_image', $this->n);
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
        $helper->submit_action = 'submittvcmsmultibanner1';
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
        $tvcms_obj = new TvcmsMultiBanner1Status();
        $show_fields = $tvcms_obj->fieldStatusInformation();

        $tmp = [];
        $tmp[] = [
                    'col' => 12,
                    'type' => 'BtnInstallData',
                    'name' => 'BtnInstallData',
                    'label' => '',
                ];
        for ($i = 1; $i <= $this->n; ++$i) {
            $tmp[] = [
                        'col' => 8,
                        'name' => 'TVCMSMULTIBANNER1_IMAGE_NAME_' . $i,
                        'type' => 'file_upload_' . $i,
                        'lang' => false,
                        'label' => $this->l('Image ' . $i),
                    ];

            if ($show_fields['title']) {
                $tmp[] = [
                            'col' => 7,
                            'name' => 'TVCMSMULTIBANNER1_CAPTION_' . $i,
                            'type' => 'textarea',
                            'lang' => true,
                            'label' => $this->l('Image Caption ' . $i),
                            'desc' => $this->l('Enter image caption ' . $i),
                            'cols' => 40,
                            'rows' => 10,
                            'class' => 'rte',
                            'autoload_rte' => true,
                        ];
            }

            if ($show_fields['title']) {
                $tmp[] = [
                            'col' => 8,
                            'name' => 'TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i,
                            'type' => 'multiple_select',
                            'lang' => true,
                            'label' => $this->l('Image Caption Side ' . $i),
                        ];
            }

            if ($show_fields['link']) {
                $tmp[] = [
                            'col' => 8,
                            'name' => 'TVCMSMULTIBANNER1_LINK_' . $i,
                            'type' => 'text',
                            'lang' => true,
                            'label' => $this->l('Link ' . $i),
                            'desc' => $this->l('Enter Image Link ' . $i),
                        ];
            }
        }

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('MultiBanner 1 Settings'),
                'icon' => 'icon-cogs',
                ],
                'input' => $tmp,
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $this->context->smarty->assign('total_image', $this->n);
        $fields = [];
        $languages = Language::getLanguages();

        for ($i = 1; $i <= $this->n; ++$i) {
            $path = _MODULE_DIR_ . $this->name . '/views/img/';
            foreach ($languages as $lang) {
                $a = Configuration::get('TVCMSMULTIBANNER1_IMAGE_NAME_' . $i, $lang['id_lang']);
                $fields['TVCMSMULTIBANNER1_IMAGE_NAME_' . $i][$lang['id_lang']] = $a;

                $a = Configuration::get('TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i, $lang['id_lang']);
                $fields['TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i][$lang['id_lang']] = $a;

                $a = Configuration::get('TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i, $lang['id_lang']);
                $fields['TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i][$lang['id_lang']] = $a;

                $a = Configuration::get('TVCMSMULTIBANNER1_CAPTION_' . $i, $lang['id_lang'], true);
                $fields['TVCMSMULTIBANNER1_CAPTION_' . $i][$lang['id_lang']] = $a;

                $a = Configuration::get('TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i, $lang['id_lang']);
                $fields['TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i][$lang['id_lang']] = $a;

                $a = Configuration::get('TVCMSMULTIBANNER1_LINK_' . $i, $lang['id_lang']);
                $fields['TVCMSMULTIBANNER1_LINK_' . $i][$lang['id_lang']] = $a;
            }
        }
        $this->context->smarty->assign('path', $path);

        return $fields;
    }

    public function hookBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    public function hookdisplayHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function hookDisplayHome()
    {
        return $this->showResult();
    }

    public function hookdisplayWrapperTop()
    {
        return $this->showResult();
    }

    public function hookdisplayContentWrapperTop()
    {
        return $this->showResult();
    }

    public function showResult()
    {
        $data = [];
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        if (!Cache::isStored('tvcmsmultibanner1_display_home.tpl')) {
            for ($i = 1; $i <= $this->n; ++$i) {
                $languages = Language::getLanguages();
                foreach ($languages as $lang) {
                    $a = Configuration::get('TVCMSMULTIBANNER1_IMAGE_NAME_' . $i, $lang['id_lang']);
                    $data['TVCMSMULTIBANNER1_IMAGE_NAME_' . $i][$lang['id_lang']] = $a;

                    $a = Configuration::get('TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i, $lang['id_lang']);
                    $data['TVCMSMULTIBANNER1_IMAGE_WIDTH_' . $i][$lang['id_lang']] = $a;

                    $a = Configuration::get('TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i, $lang['id_lang']);
                    $data['TVCMSMULTIBANNER1_IMAGE_HEIGHT_' . $i][$lang['id_lang']] = $a;

                    $a = Configuration::get('TVCMSMULTIBANNER1_CAPTION_' . $i, $lang['id_lang'], true);
                    $data['TVCMSMULTIBANNER1_CAPTION_' . $i][$lang['id_lang']] = $a;

                    $a = Configuration::get('TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i, $lang['id_lang']);
                    $data['TVCMSMULTIBANNER1_CAPTION_SIDE_' . $i][$lang['id_lang']] = $a;

                    $a = Configuration::get('TVCMSMULTIBANNER1_LINK_' . $i, $lang['id_lang']);
                    $data['TVCMSMULTIBANNER1_LINK_' . $i][$lang['id_lang']] = $a;
                }
            }
            $this->context->smarty->assign('language_id', $id_lang);
            $this->context->smarty->assign('data', $data);

            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            Cache::store('tvcmsmultibanner1_display_home.tpl', $output);
        }

        // return $output;
        return Cache::retrieve('tvcmsmultibanner1_display_home.tpl');
    }
}
