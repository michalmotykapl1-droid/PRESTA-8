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

include_once 'classes/tvcmssingleblock_status.class.php';
include_once 'classes/tvcmssingleblock_image_upload.class.php';

class TvcmsSingleBlock extends Module
{
    public function __construct()
    {
        $this->name = 'tvcmssingleblock';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Single Block');
        $this->description = $this->l('Its show single block on front-side.');

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
            && $this->registerHook('displayWrapperTop')
            && $this->registerHook('displayContentWrapperTop')
            && $this->registerHook('displayHome');
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
            $tab->name[$lang['id_lang']] = 'Single Block';
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
            $result['TVCMSSINGLEBLOCK_IMG'][$lang['id_lang']] = 'demo_img_1.jpg';
            $result['TVCMSSINGLEBLOCK_TITLE'][$lang['id_lang']] = 'Main Title';
            $result['TVCMSSINGLEBLOCK_SUB_DESCRIPTION'][$lang['id_lang']] = 'Sub Description';
            $result['TVCMSSINGLEBLOCK_DESCRIPTION'][$lang['id_lang']] = 'We are a global housewares product'
                 . ' design company. We bring thought and creativity to everyday items through original design . ';

            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG'][$lang['id_lang']] = 'demo_img_2.png';
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2'][$lang['id_lang']] = 'demo_img_3.png';
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3'][$lang['id_lang']] = 'demo_img_4.png';
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4'][$lang['id_lang']] = 'demo_img_5.png';
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE'][$lang['id_lang']] = '$500 Cashback*';
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION'][$lang['id_lang']] = 'Shop Above $500 & Get';
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION'][$lang['id_lang']] = 'For Your Next Purchase';
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION'][$lang['id_lang']] = 'Explore More >';
        }

        Configuration::updateValue('TVCMSSINGLEBLOCK_IMG', $result['TVCMSSINGLEBLOCK_IMG']);
        Configuration::updateValue('TVCMSSINGLEBLOCK_TITLE', $result['TVCMSSINGLEBLOCK_TITLE']);
        Configuration::updateValue('TVCMSSINGLEBLOCK_SUB_DESCRIPTION', $result['TVCMSSINGLEBLOCK_SUB_DESCRIPTION']);
        Configuration::updateValue('TVCMSSINGLEBLOCK_DESCRIPTION', $result['TVCMSSINGLEBLOCK_DESCRIPTION']);

        Configuration::updateValue(
            'TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG',
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG']
        );
        Configuration::updateValue(
            'TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2',
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2']
        );
        Configuration::updateValue(
            'TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3',
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3']
        );
        Configuration::updateValue(
            'TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4',
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4']
        );
        Configuration::updateValue(
            'TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE',
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE']
        );
        Configuration::updateValue(
            'TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION',
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION']
        );
        Configuration::updateValue(
            'TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION',
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION']
        );
        Configuration::updateValue(
            'TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION',
            $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION']
        );
        Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK', '#');
        Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_STATUS', '1');
    }

    public function uninstall()
    {
        $this->uninstallTab();
        $this->deleteVariable();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSSINGLEBLOCK_IMG');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_TITLE');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_SUB_DESCRIPTION');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_DESCRIPTION');

        Configuration::deleteByName('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK');
        Configuration::deleteByName('TVCMSSINGLEBLOCK_SINGLE_BLOCK_STATUS');
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
        $output = $message
                . $this->renderForm();

        return $output;
    }

    public function postProcess()
    {
        $message = '';
        $result = [];
        $obj_image = new TvcmsSingleBlockImageUpload();
        $languages = Language::getLanguages();
        if (Tools::isSubmit('submittvcmsSingleBlockMainTitleForm') && '0' == Tools::getValue('tvinstalldata')) {
            foreach ($languages as $lang) {
                if (!empty($_FILES['TVCMSSINGLEBLOCK_IMG_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSSINGLEBLOCK_IMG', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSSINGLEBLOCK_IMG_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSSINGLEBLOCK_IMG'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSSINGLEBLOCK_IMG'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSSINGLEBLOCK_IMG', $lang['id_lang']);
                    $result['TVCMSSINGLEBLOCK_IMG'][$lang['id_lang']] = $old_file;
                }

                $tmp = Tools::getValue('TVCMSSINGLEBLOCK_TITLE_' . $lang['id_lang']);
                $result['TVCMSSINGLEBLOCK_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSSINGLEBLOCK_SUB_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSSINGLEBLOCK_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSSINGLEBLOCK_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSSINGLEBLOCK_DESCRIPTION'][$lang['id_lang']] = $tmp;
            }
            Configuration::updateValue('TVCMSSINGLEBLOCK_IMG', $result['TVCMSSINGLEBLOCK_IMG']);

            $tmp = $result['TVCMSSINGLEBLOCK_TITLE'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_TITLE', $tmp);

            $tmp = $result['TVCMSSINGLEBLOCK_SUB_DESCRIPTION'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_SUB_DESCRIPTION', $tmp);

            $tmp = $result['TVCMSSINGLEBLOCK_DESCRIPTION'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_DESCRIPTION', $tmp);

            $message .= $this->displayConfirmation($this->l('Footer Logo is Updated . '));
        }

        if (Tools::isSubmit('submittvcmsSingleBlockSingleBlockForm') && '0' == Tools::getValue('tvinstalldata')) {
            foreach ($languages as $lang) {
                if (!empty($_FILES['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG', $lang['id_lang']);
                    $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG'][$lang['id_lang']] = $old_file;
                }

                if (!empty($_FILES['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2', $lang['id_lang']);
                    $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2'][$lang['id_lang']] = $old_file;
                }

                if (!empty($_FILES['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3', $lang['id_lang']);
                    $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3'][$lang['id_lang']] = $old_file;
                }

                if (!empty($_FILES['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4_' . $lang['id_lang']]['name'])) {
                    $old_file = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4', $lang['id_lang']);
                    $new_file = $_FILES['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4_' . $lang['id_lang']];
                    $ans = $obj_image->imageUploading($new_file, $old_file);
                    if ($ans['success']) {
                        $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4'][$lang['id_lang']] = $ans['name'];
                    } else {
                        $message .= $ans['error'];
                        $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4'][$lang['id_lang']] = $old_file;
                    }
                } else {
                    $old_file = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4', $lang['id_lang']);
                    $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4'][$lang['id_lang']] = $old_file;
                }

                $tmp = Tools::getValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE_' . $lang['id_lang']);
                $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION_' . $lang['id_lang']);
                $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG', $tmp);

            $tmp = $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2', $tmp);

            $tmp = $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3', $tmp);

            $tmp = $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4', $tmp);

            $tmp = $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE', $tmp);
            $tmp = $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION', $tmp);
            $tmp = $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION', $tmp);
            $tmp = $result['TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION'];
            Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION', $tmp);

            $tmp = Tools::getValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK');
            Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK', $tmp);

            $tmp = Tools::getValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_STATUS');
            Configuration::updateValue('TVCMSSINGLEBLOCK_SINGLE_BLOCK_STATUS', $tmp);
        }

        $this->clearCustomSmartyCache('tvcmssingleblock_display_home.tpl');

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

        $tvcms_obj = new TvcmsSingleBlockStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        if ($show_fields['main_status']) {
            $form[] = $this->tvcmsSingleBlockMainTitleForm();
        }

        if ($show_fields['record_form']) {
            $form[] = $this->tvcmsSingleBlockSingleBlockForm();
        }

        return $helper->generateForm($form);
    }

    protected function tvcmsSingleBlockMainTitleForm()
    {
        $tvcms_obj = new TvcmsSingleBlockStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        if ($show_fields['main_image']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'image_file',
                    'name' => 'TVCMSSINGLEBLOCK_IMG',
                    'label' => $this->l('Image'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSSINGLEBLOCK_TITLE',
                    'label' => $this->l('Main Title'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_sub_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSSINGLEBLOCK_SUB_DESCRIPTION',
                    'label' => $this->l('Short Description'),
                    'lang' => true,
                ];
        }

        if ($show_fields['main_description']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSSINGLEBLOCK_DESCRIPTION',
                    'label' => $this->l('Description'),
                    'lang' => true,
                ];
        }

        return [
            'form' => [
                'legend' => [
                'title' => $this->l('Main Title'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submittvcmsSingleBlockMainTitleForm',
                ],
            ],
        ];
    }

    protected function tvcmsSingleBlockSingleBlockForm()
    {
        $tvcms_obj = new TvcmsSingleBlockStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        $input[] = [
                'col' => 12,
                'type' => 'BtnInstallData',
                'name' => 'BtnInstallData',
                'label' => '',
            ];
        if ($show_fields['single_block_image']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'image_file',
                    'name' => 'TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG',
                    'label' => $this->l('Image'),
                    'lang' => true,
                ];
        }

        if ($show_fields['single_block_image_2']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'image_file_2',
                    'name' => 'TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2',
                    'label' => $this->l('Image 2'),
                    'lang' => true,
                ];
        }

        if ($show_fields['single_block_image_3']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'image_file_3',
                    'name' => 'TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3',
                    'label' => $this->l('Image 3'),
                    'lang' => true,
                ];
        }

        if ($show_fields['single_block_image_4']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'image_file_4',
                    'name' => 'TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4',
                    'label' => $this->l('Image 4'),
                    'lang' => true,
                ];
        }

        if ($show_fields['single_block_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE',
                    'label' => $this->l('Main Title'),
                    'lang' => true,
                ];
        }

        if ($show_fields['single_block_sub_title']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION',
                    'label' => $this->l('Short Description'),
                    'lang' => true,
                ];
        }

        if ($show_fields['single_block_description']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION',
                    'label' => $this->l('Description'),
                    'lang' => true,
                ];
        }

        if ($show_fields['single_block_btn_caption']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION',
                    'label' => $this->l('Button Caption'),
                    'lang' => true,
                ];
        }

        if ($show_fields['single_block_link']) {
            $input[] = [
                    'col' => 7,
                    'type' => 'text',
                    'name' => 'TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK',
                    'label' => $this->l('Link'),
                ];
        }

        if ($show_fields['single_block_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Status'),
                        'name' => 'TVCMSSINGLEBLOCK_SINGLE_BLOCK_STATUS',
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
                'title' => $this->l('Footer Logo'),
                'icon' => 'icon-support',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submittvcmsSingleBlockSingleBlockForm',
                ],
            ],
        ];
    }

    protected function getConfigFormValues()
    {
        $fields = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $tmp = Configuration::get('TVCMSSINGLEBLOCK_IMG', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_IMG'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_TITLE', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_TITLE'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SUB_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_DESCRIPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK', $lang['id_lang']);
            $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK'][$lang['id_lang']] = $tmp;
        }

        $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK');
        $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK'] = $tmp;

        $tmp = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_STATUS');
        $fields['TVCMSSINGLEBLOCK_SINGLE_BLOCK_STATUS'] = $tmp;

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

    public function showFrontData()
    {
        $result = [];
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $result['image'] = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG', $id_lang);
        $result['image_2'] = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_2', $id_lang);
        $result['image_3'] = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_3', $id_lang);
        $result['image_4'] = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_IMG_4', $id_lang);
        $result['title'] = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_TITLE', $id_lang);
        $result['sub_description'] = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_SUB_DESCRIPTION', $id_lang);
        $result['description'] = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_DESCRIPTION', $id_lang);
        $result['btn_caption'] = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_BTN_CAPTION', $id_lang);
        $result['link'] = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_LINK');
        $result['status'] = Configuration::get('TVCMSSINGLEBLOCK_SINGLE_BLOCK_STATUS');

        // --- NOWY KOD: Pobieranie danych z modułu lojalnościowego ---
        $result['loyalty_rate'] = (int)Configuration::get('PS_LOYALTY_POINT_RATE');
        $result['loyalty_value'] = (float)Configuration::get('PS_LOYALTY_POINT_VALUE');
        $result['loyalty_validity'] = (int)Configuration::get('PS_LOYALTY_VALIDITY_PERIOD');
        // ----------------------------------------------------------

        return $result;
    }

    public function showFrontSideResult()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $tvcms_obj = new TvcmsSingleBlockStatus();
        $main_heading = $tvcms_obj->fieldStatusInformation();

        if ($main_heading['main_status']) {
            $main_heading_data = [];
            $main_heading_data['title'] = Configuration::get('TVCMSSINGLEBLOCK_TITLE', $id_lang);
            $main_heading_data['short_desc'] = Configuration::get('TVCMSSINGLEBLOCK_SUB_DESCRIPTION', $id_lang);
            $main_heading_data['desc'] = Configuration::get('TVCMSSINGLEBLOCK_DESCRIPTION', $id_lang);
            $main_heading_data['image'] = Configuration::get('TVCMSSINGLEBLOCK_IMG', $id_lang);
            $main_heading = $this->getArrMainTitle($main_heading, $main_heading_data);
            $main_heading['data'] = $main_heading_data;
        }

        $disArrResult = [];
        $disArrResult['data'] = $this->showFrontData();
        $disArrResult['status'] = $disArrResult['data']['status'];
        $disArrResult['path'] = _MODULE_DIR_ . $this->name . '/views/img/';
        $disArrResult['id_lang'] = $id_lang;

        $this->context->smarty->assign('main_heading', $main_heading);
        $this->context->smarty->assign('dis_arr_result', $disArrResult);

        return $disArrResult['status'] ? true : false;
    }

    public function hookdisplayHome()
    {
        // Usunięto sprawdzanie cache, aby dane o punktach aktualizowały się na bieżąco
        $result = $this->showFrontSideResult();

        if ($result) {
            $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
        } else {
            $output = '';
        }

        return $output;
    }

    public function hookdisplayWrapperTop()
    {
        return $this->hookdisplayHome();
    }

    public function hookdisplayContentWrapperTop()
    {
        return $this->hookdisplayHome();
    }
}
