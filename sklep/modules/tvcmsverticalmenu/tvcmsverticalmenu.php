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

include_once _PS_MODULE_DIR_ . 'tvcmsverticalmenu/classes/tvcmsverticalmenuClass.php';
include_once _PS_MODULE_DIR_ . 'tvcmsverticalmenu/classes/tvcmsverticalmenuRowClass.php';
include_once _PS_MODULE_DIR_ . 'tvcmsverticalmenu/classes/tvcmsverticalmenuColumnClass.php';
include_once _PS_MODULE_DIR_ . 'tvcmsverticalmenu/classes/tvcmsverticalmenuItemClass.php';
include_once _PS_MODULE_DIR_ . 'tvcmsverticalmenu/sql/tvcmsverticalmenuData.php';

class TvcmsVerticalMenu extends Module
{
    protected $config_form = false;

    private $html = '';

    private $width_boostrap = [
        'col-sm-2',
        'col-sm-3',
        'col-sm-4',
        'col-sm-5',
        'col-sm-6',
        'col-sm-7',
        'col-sm-8',
        'col-sm-9',
        'col-sm-10',
        'col-sm-11',
        'col-sm-12',
    ];

    public function __construct()
    {
        $this->name = 'tvcmsverticalmenu';
        $this->tab = 'front_office_features';
        $this->version = '4.0.1';
        $this->author = 'ThemeVolty';
        $this->need_instance = 1;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('ThemeVolty - Vertical Menu');
        $this->description = $this->l('Show Vertical Menu Front Side');
        $this->secure_key = Tools::encrypt($this->name);

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted . ' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        Tools::clearSmartyCache();
        Configuration::updateValue('TVCMSVERTICALMENU_LEFT_COLUMN', 0);
        Configuration::updateValue('TVCMSVERTICALMENU_MENU_ALL_PAGES', 1);
        $this->installTab();
        if (parent::install()
            && $this->registerHook('header')
            // $this->registerHook('displayVerticalMenu') &&
            && $this->registerHook('displayLeftColumn')
            && $this->registerHook('displayHome')             // <--- DODANE
            && $this->registerHook('displayWrapperBottom')    // <--- DODANE
            // $this->registerHook('displayRightColumn') &&
            // $this->registerHook('displayNavFullWidth') &&
            // $this->registerHook('displayNav4') &&
            && $this->registerHook('actionShopDataDuplication')
            && $this->registerHook('actionObjectLanguageAddAfter')) {
            include dirname(__FILE__) . '/sql/install.php';

            // $this->createDefaultData();
            return true;
        }

        return false;
    }

    public function createDefaultData()
    {
        $this->reset();
        $sample_data = new TvcmsVerticalMenuData();
        $sample_data->initData();
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
            $tab->name[$lang['id_lang']] = 'Vertical Menu';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
        Tools::clearSmartyCache();
        Configuration::deleteByName('TVCMSVERTICALMENU_LEFT_COLUMN');
        Configuration::deleteByName('TVCMSVERTICALMENU_MENU_ALL_PAGES');
        $this->uninstallTab();
        include dirname(__FILE__) . '/sql/uninstall.php';

        return parent::uninstall();
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    public function reset()
    {
        $trn_tbl = [];
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsverticalmenu`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsverticalmenu_shop`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsverticalmenu_lang`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsverticalmenu_row`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsverticalmenu_row_shop`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsverticalmenu_column`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsverticalmenu_column_shop`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsverticalmenu_item`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_shop`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_lang`';
        foreach ($trn_tbl as $table) {
            Db::getInstance()->execute($table);
        }
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

        if (Tools::isSubmit('submitTvcmsSampleinstall')) {
            $this->createDefaultData();
        }

        if (Tools::isSubmit('submitMenuItem')
            || Tools::isSubmit('delete_id_menu')
            || Tools::isSubmit('changeStatus')
            || Tools::isSubmit('removeIcon')) {
            $this->postProcess();
            $this->html .= $this->renderList();
        } elseif ((Tools::isSubmit('buildMenu')
                || Tools::isSubmit('submitRow')
                || Tools::isSubmit('delete_row'))
            && Tools::isSubmit('id_tvcmsverticalmenu')
            || Tools::isSubmit('changeStatusRow')
            || Tools::isSubmit('submitCol')
            || Tools::isSubmit('delete_col')
            || Tools::isSubmit('submitSubItem')
            || Tools::isSubmit('delete_submenu_item')
            || Tools::isSubmit('changeStatusSubItem')) {
            $this->postProcess();
            $this->html .= $this->renderBuildMenu();
        } elseif (Tools::isSubmit('addMenuItem') || Tools::isSubmit('id_item')) {
            $this->html .= $this->renderAddMeniItem();
        } elseif (Tools::isSubmit('addCol') || Tools::isSubmit('id_column')) {
            $this->html .= $this->renderAddCol();
        } elseif (Tools::isSubmit('addRow') || Tools::isSubmit('id_row')) {
            $this->html .= $this->renderAddRow();
        } elseif (Tools::isSubmit('addMenu')
            || (Tools::isSubmit('id_tvcmsverticalmenu')
                && $this->menuExists(Tools::getValue('id_tvcmsverticalmenu')))) {
            $this->html .= $this->renderAddForm();
        } else {
            $this->postProcess();
            $this->context->smarty->assign('module_dir', $this->_path);
            $this->html .= $this->renderCustomForm();
            $this->html .= $this->renderList();
        }

        return $this->html;
    }

    public function renderCustomForm()
    {
        // $wrap_lable = $this->getTranslator()->trans('Loop forever', array(), 'Modules.TvcmsSlider.Admin');
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->getTranslator()->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans(
                            'Show in Left Column [Homepage]',
                            [],
                            'Modules.TvcmsSlider.Admin'
                        ),
                        'name' => 'TVCMSVERTICALMENU_LEFT_COLUMN',
                        'desc' => $this->getTranslator()->trans(
                            'Manage to show/hide menu in left Column in Homepage . ',
                            [],
                            'Modules.TvcmsSlider.Admin'
                        ),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->getTranslator()->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->getTranslator()->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans(
                            'Show in all pages',
                            [],
                            'Modules.TvcmsSlider.Admin'
                        ),
                        'name' => 'TVCMSVERTICALMENU_MENU_ALL_PAGES',
                        'desc' => $this->getTranslator()->trans(
                            'Manage to Show in all pages . ',
                            [],
                            'Modules.TvcmsSlider.Admin'
                        ),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->getTranslator()->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->getTranslator()->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->getTranslator()->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $tmp = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->allow_employee_form_lang = $tmp ? $tmp : 0;
        $this->fields_form = [];

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitVerticalmenuconfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' .
            $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    protected function getConfigFormValues()
    {
        return [
            'TVCMSVERTICALMENU_LEFT_COLUMN' => Configuration::get('TVCMSVERTICALMENU_LEFT_COLUMN', true),
            'TVCMSVERTICALMENU_MENU_ALL_PAGES' => Configuration::get('TVCMSVERTICALMENU_MENU_ALL_PAGES', true),
        ];
    }

    public function renderList()
    {
        $this->context->controller->addJqueryUI('ui.sortable');
        $info_menus = $this->getMenuInfo();
        foreach ($info_menus as $key => $info_menu) {
            $tmp = $this->displayStatus($info_menu['id_tvcmsverticalmenu'], $info_menu['active']);
            $info_menus[$key]['status'] = $tmp;
            if (0 == $info_menu['type_link']) {
                $menu_info = $this->fomartLink($info_menu);
                $info_menus[$key]['title'] = $menu_info['title'];
            }
        }

        $this->context->smarty->assign(
            [
                'link' => $this->context->link,
                'info_menus' => $info_menus,
                'url_base' => $this->context->shop->physical_uri . $this->context->shop->virtual_uri,
                'secure_key' => $this->secure_key,
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/list.tpl');
    }

    public function getMenuInfo($active = null)
    {
        $this->context = Context::getContext();
        $id_shop = (int) $this->context->shop->id;
        $id_lang = (int) $this->context->language->id;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT pc.*, pl.*
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_shop pc
            LEFT JOIN '
                . _DB_PREFIX_ . 'tvcmsverticalmenu_lang pl ON (pc.id_tvcmsverticalmenu = pl.id_tvcmsverticalmenu '
                 . 'AND pc.`id_shop` = pl.`id_shop`)
            WHERE pl.id_shop = ' . (int) $id_shop . ' AND pl.id_lang = ' . (int) $id_lang . ($active ? ' AND pc.`active` = 1' : ' ')
                 . ' ORDER BY pc.position ASC, pc.id_tvcmsverticalmenu ASC');
    }

    public function displayStatus($id_tvcmsverticalmenu, $active)
    {
        $title = (0 == (int) $active ? $this->l('Disabled') : $this->l('Enabled'));
        $icon = (0 == (int) $active ? 'icon-remove' : 'icon-check');
        $class = (0 == (int) $active ? 'btn-danger' : 'btn-success');
        $html = '<a class="btn ' . $class . '" href="' . AdminController::$currentIndex .
            '&configure=' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules') .
                '&changeStatus&id_tvcmsverticalmenu=' . (int) $id_tvcmsverticalmenu . '" title="' . $title . '"><i class="'
                    . $icon . '"></i> ' . $title . '</a>';

        return $html;
    }

    public function displayStatusRow($id_menu, $id_row, $active)
    {
        $title = (0 == (int) $active ? $this->l('Disabled') : $this->l('Enabled'));
        $icon = (0 == (int) $active ? 'icon-remove' : 'icon-check');
        $class = (0 == (int) $active ? 'btn-danger' : 'btn-success');
        $html = '<a class="btn ' . $class . '" href="' . AdminController::$currentIndex .
            '&configure=' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules') .
                '&changeStatusRow&id_tvcmsverticalmenu=' . $id_menu . '&id_row=' . (int) $id_row . '" title="' . $title
                     . '"><i class="' . $icon . '"></i> ' . $title . '</a>';

        return $html;
    }

    public function displayStatusSubItem($id_menu, $id_item, $active)
    {
        $title = (0 == (int) $active ? $this->l('Disabled') : $this->l('Enabled'));
        $icon = (0 == (int) $active ? 'icon-remove' : 'icon-check');
        $class = (0 == (int) $active ? 'btn-danger' : 'btn-success');
        $html = '<a class="btn ' . $class . '" href="' . AdminController::$currentIndex .
            '&configure=' . $this->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules') .
                '&changeStatusSubItem&id_tvcmsverticalmenu=' . $id_menu . '&id_item=' . (int) $id_item . '" title="'
                    . $title . '"><i class="' . $icon . '"></i></a>';

        return $html;
    }

    protected function postProcess()
    {
        $errors = [];
        if (((bool) Tools::isSubmit('submitVerticalmenuconfig')) == true) {
            $form_values = $this->getConfigFormValues();
            foreach (array_keys($form_values) as $key) {
                Configuration::updateValue($key, Tools::getValue($key));
            }
            $this->html .= $this->displayConfirmation($this->l('Theme Configuration is Updated'));
        }
        if (Tools::isSubmit('submitMenuItem')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            if (Tools::getValue('id_tvcmsverticalmenu')) {
                $menu_item = new TvcmsVerticalMenuClass((int) Tools::getValue('id_tvcmsverticalmenu'));
                if (!Validate::isLoadedObject($menu_item)) {
                    $this->html .= $this->displayError($this->l('Invalid id_tvcmsverticalmenu'));

                    return false;
                }
            } else {
                $menu_item = new TvcmsVerticalMenuClass();
            }
            $menu_item->active = (int) Tools::getValue('active');
            $menu_item->position = (int) Tools::getValue('position', 0);
            $menu_item->type_link = Tools::getValue('type_link');
            $menu_item->dropdown = Tools::getValue('dropdown');
            $menu_item->type_icon = Tools::getValue('type_icon');
            if (1 == $menu_item->type_icon) {
                $menu_item->icon = Tools::getValue('icon_font');
            } else {
                $image_up = $menu_item->uploadImage('icon_img', $this->name . '/views/img/icons/');
                if (isset($image_up) && '' != $image_up) {
                    $menu_item->icon = $image_up;
                }
            }
            $menu_item->align_sub = Tools::getValue('align_sub');
            $menu_item->width_sub = Tools::getValue('width_sub');
            $menu_item->class = Tools::getValue('class');

            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                if (0 == $menu_item->type_link) {
                    $menu_item->title[$language['id_lang']] = Tools::getValue('ps_link');
                    $menu_item->link[$language['id_lang']] = Tools::getValue('ps_link');
                } else {
                    $menu_item->title[$language['id_lang']] = Tools::getValue('title_' . $language['id_lang']);
                    $menu_item->link[$language['id_lang']] = Tools::getValue('link_' . $language['id_lang']);
                }
                $menu_item->subtitle[$language['id_lang']] = Tools::getValue('subtitle_' . $language['id_lang']);
            }
            if (!$errors) {
                if (!Tools::getValue('id_tvcmsverticalmenu')) {
                    if (!$menu_item->add()) {
                        $errors[] = $this->displayError($this->l('The menu_item could not be added . '));
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=1&'
                             . 'configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
                    }
                } else {
                    if (!$menu_item->update()) {
                        $errors[] = $this->displayError($this->l('The menu_item could not be updated . '));
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=1&'
                             . 'configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
                    }
                }
            }

            return $errors;
        } elseif (Tools::isSubmit('changeStatus') && Tools::isSubmit('id_tvcmsverticalmenu')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            $menu = new TvcmsVerticalMenuClass((int) Tools::getValue('id_tvcmsverticalmenu'));
            if (0 == $menu->active) {
                $menu->active = 1;
            } else {
                $menu->active = 0;
            }
            $res = $menu->update();
            $tmp = $this->displayConfirmation($this->l('Configuration updated'));
            $tmp_2 = $this->displayError($this->l('The configuration could not be updated . '));
            $this->html .= ($res ? $tmp : $tmp_2);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=6&configure='
                . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
        } elseif (Tools::isSubmit('removeIcon') && Tools::isSubmit('id_tvcmsverticalmenu')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            $menu = new TvcmsVerticalMenuClass((int) Tools::getValue('id_tvcmsverticalmenu'));
            $icon = $menu->icon;
            if (0 === preg_match('/sample/', $icon)) {
                if ($icon && file_exists(_PS_MODULE_DIR_ . 'tvcmsverticalmenu/views/img/icons/' . $icon)) {
                    @unlink(_PS_MODULE_DIR_ . 'tvcmsverticalmenu/views/img/icons/' . $icon);
                }
            }
            $menu->icon = '';
            $menu->update();
        } elseif (Tools::isSubmit('submitRow')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            if (Tools::getValue('id_row')) {
                $row_item = new TvcmsVerticalMenuRowClass(Tools::getValue('id_row'));
                if (!Validate::isLoadedObject($row_item)) {
                    $this->html .= $this->displayError($this->l('Invalid id_row'));

                    return false;
                }
            } else {
                $row_item = new TvcmsVerticalMenuRowClass();
            }
            $row_item->active = (int) Tools::getValue('active');
            $row_item->id_tvcmsverticalmenu = (int) Tools::getValue('id_tvcmsverticalmenu');
            $row_item->class = Tools::getValue('class');

            if (!$errors) {
                if (!Tools::getValue('id_row')) {
                    if (!$row_item->add()) {
                        $errors[] = $this->displayError($this->l('The row_item could not be added . '));
                    }
                } else {
                    if (!$row_item->update()) {
                        $errors[] = $this->displayError($this->l('The row_item could not be updated . '));
                    }
                }
            }

            return $errors;
        } elseif (Tools::isSubmit('delete_id_menu')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            $menu_item = new TvcmsVerticalMenuClass((int) Tools::getValue('delete_id_menu'));
            $res = $menu_item->delete();
            if (!$res) {
                $this->html .= $this->displayError('Could not delete . ');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=1&configure='
                    . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsverticalmenu='
                    . Tools::getValue('id_tvcmsverticalmenu'));
            }
        } elseif (Tools::isSubmit('delete_row')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            $row_item = new TvcmsVerticalMenuRowClass((int) Tools::getValue('id_row'));
            $res = $row_item->delete();
            if (!$res) {
                $this->html .= $this->displayError('Could not delete . ');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=1&configure='
                    . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsverticalmenu='
                    . Tools::getValue('id_tvcmsverticalmenu') . '&buildMenu=1');
            }
        } elseif (Tools::isSubmit('changeStatusRow') && Tools::isSubmit('id_row')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            $id_shop = (int) $this->context->shop->id;
            $row = new TvcmsVerticalMenuRowClass((int) Tools::getValue('id_row'), null, $id_shop);
            if (0 == $row->active) {
                $row->active = 1;
            } else {
                $row->active = 0;
            }
            $res = $row->update();
            $tmp = $this->displayConfirmation($this->l('Configuration updated'));
            $tmp_2 = $this->displayError($this->l('The configuration could not be updated . '));
            $this->html .= ($res ? $tmp : $tmp_2);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=1&configure='
                . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsverticalmenu='
                . Tools::getValue('id_tvcmsverticalmenu') . '&buildMenu=1');
        } elseif (Tools::isSubmit('submitCol')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            if (Tools::getValue('id_column')) {
                $col_item = new TvcmsVerticalMenuColumnClass(Tools::getValue('id_column'));
                if (!Validate::isLoadedObject($col_item)) {
                    $this->html .= $this->displayError($this->l('Invalid id_column'));

                    return false;
                }
            } else {
                $col_item = new TvcmsVerticalMenuColumnClass();
            }
            $col_item->active = (int) Tools::getValue('active');
            $col_item->position = (int) Tools::getValue('position', 0);
            $col_item->id_row = (int) Tools::getValue('id_row');
            $col_item->width = Tools::getValue('width');
            $col_item->class = Tools::getValue('class');

            if (!$errors) {
                if (!Tools::getValue('id_column')) {
                    if (!$col_item->add()) {
                        $errors[] = $this->displayError($this->l('The col_item could not be added . '));
                    }
                } else {
                    if (!$col_item->update()) {
                        $errors[] = $this->displayError($this->l('The col_item could not be updated . '));
                    }
                }
            }

            return $errors;
        } elseif (Tools::isSubmit('delete_col')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            $col_item = new TvcmsVerticalMenuColumnClass((int) Tools::getValue('id_column'));
            $res = $col_item->delete();
            if (!$res) {
                $this->html .= $this->displayError('Could not delete . ');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=1&configure='
                    . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsverticalmenu='
                    . Tools::getValue('id_tvcmsverticalmenu') . '&buildMenu=1');
            }
        } elseif (Tools::isSubmit('submitSubItem')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            if (Tools::getValue('id_item')) {
                $sub_menu_item = new TvcmsVerticalMenuItemClass(Tools::getValue('id_item'));
                if (!Validate::isLoadedObject($sub_menu_item)) {
                    $this->html .= $this->displayError($this->l('Invalid id_item'));

                    return false;
                }
            } else {
                $sub_menu_item = new TvcmsVerticalMenuItemClass();
            }
            $sub_menu_item->active = (int) Tools::getValue('active');
            $sub_menu_item->position = (int) Tools::getValue('position', 0);
            $sub_menu_item->id_column = (int) Tools::getValue('id_column');
            $sub_menu_item->type_link = Tools::getValue('type_link');
            $sub_menu_item->type_item = Tools::getValue('type_item');
            $sub_menu_item->id_product = Tools::getValue('id_product');

            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                if (1 == $sub_menu_item->type_link) {
                    $sub_menu_item->title[$language['id_lang']] = Tools::getValue('ps_link');
                    $sub_menu_item->link[$language['id_lang']] = Tools::getValue('ps_link');
                } else {
                    $sub_menu_item->title[$language['id_lang']] = Tools::getValue('title_' . $language['id_lang']);
                    $sub_menu_item->link[$language['id_lang']] = Tools::getValue('link_' . $language['id_lang']);
                }
                $temp = Tools::getValue('text_' . $language['id_lang']);
                $temp_url = '{lab_menu_h_url}';
                if (isset($temp)) {
                    $temp = str_replace(_PS_BASE_URL_ . __PS_BASE_URI__, $temp_url, $temp);
                    $sub_menu_item->text[$language['id_lang']] = $temp;
                }
            }

            if (!$errors) {
                if (!Tools::getValue('id_item')) {
                    if (!$sub_menu_item->add()) {
                        $errors[] = $this->displayError($this->l('The sub_menu_item could not be added . '));
                    }
                } else {
                    if (!$sub_menu_item->update()) {
                        $errors[] = $this->displayError($this->l('The sub_menu_item could not be updated . '));
                    }
                }
            }

            return $errors;
        } elseif (Tools::isSubmit('delete_submenu_item')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            $submenu_item = new TvcmsVerticalMenuItemClass((int) Tools::getValue('id_item'));
            $res = $submenu_item->delete();
            if (!$res) {
                $this->html .= $this->displayError('Could not delete . ');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=1&configure='
                    . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsverticalmenu='
                    . Tools::getValue('id_tvcmsverticalmenu') . '&buildMenu=1');
            }
        } elseif (Tools::isSubmit('changeStatusSubItem') && Tools::isSubmit('id_item')) {
            $this->clearCustomSmartyCache('tvcmsverticalmenu.tpl');
            $subMenu_item = new TvcmsVerticalMenuItemClass((int) Tools::getValue('id_item'));
            if (0 == $subMenu_item->active) {
                $subMenu_item->active = 1;
            } else {
                $subMenu_item->active = 0;
            }
            $res = $subMenu_item->update();
            $tmp = $this->displayConfirmation($this->l('Configuration updated'));
            $tmp_2 = $this->displayError($this->l('The configuration could not be updated . '));
            $this->html .= ($res ? $tmp : $tmp_2);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true) . '&conf=1&configure='
                . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsverticalmenu='
                . Tools::getValue('id_tvcmsverticalmenu') . '&buildMenu=1');
        }
    }

    public function renderAddForm()
    {
        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        $id_tvcmsverticalmenu = Tools::getValue('id_tvcmsverticalmenu');
        if (isset($id_tvcmsverticalmenu)) {
            $menu = new TvcmsVerticalMenuClass($id_tvcmsverticalmenu);
        } else {
            $menu = new TvcmsVerticalMenuClass();
        }

        $languages = $this->context->controller->getLanguages();
        $this->context->smarty->assign(
            [
                'languages' => $languages,
                'id_language' => $this->context->language->id,
                'token' => Tools::getAdminTokenLite('AdminModules'),
                'all_options' => $this->getAllDefaultLink(),
                'menu' => $menu,
                'image_baseurl' => $this->_path . 'views/img/icons/',
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/menu_item.tpl');
    }

    public function renderAddMeniItem()
    {
        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        $id_tvcmsverticalmenu = Tools::getValue('id_tvcmsverticalmenu');
        // $id_column = Tools::getValue('id_column');
        $options = [
                        [
                            'id_option' => 1,
                            'name' => $this->l('PrestaShop Link'),
                        ],
                        [
                            'id_option' => 2,
                            'name' => $this->l('Custom Link'),
                        ],
                        [
                            'id_option' => 3,
                            'name' => $this->l('HTML Block'),
                        ],
                        [
                            'id_option' => 4,
                            'name' => $this->l('Product'),
                        ],
                    ];

        $options_type_item = [
        [
            'id_option' => 1,
            'name' => $this->l('Group Header'),
        ],
        [
            'id_option' => 2,
            'name' => $this->l('Line'),
        ],
        ];

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Sub Menu Item'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Type Item'),
                        'name' => 'type_link',
                        'options' => [
                            'query' => $options,
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Title'),
                        'name' => 'title',
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Link'),
                        'name' => 'link',
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Id Product'),
                        'name' => 'id_product',
                    ],
                    [
                        'type' => 'select_link',
                        'label' => $this->l('Prestashop Link'),
                        'name' => 'ps_link',
                    ],
                    [
                    'type' => 'textarea',
                    'label' => $this->l('HTML'),
                    'name' => 'text',
                    'lang' => true,
                    'autoload_rte' => true,
                    'cols' => 40,
                    'rows' => 10,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->l('Defined show'),
                        'name' => 'type_item',
                        'options' => [
                            'query' => $options_type_item,
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'active',
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
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
                'buttons' => [
                    [
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&token='
                        . Tools::getAdminTokenLite('AdminModules') . '&id_tvcmsverticalmenu=' . $id_tvcmsverticalmenu
                         . '&buildMenu=1',
                    'title' => $this->l('Cancel'),
                    'icon' => 'process-icon-back',
                    ],
                ],
            ],
        ];
        if (Tools::isSubmit('id_item')) {
            $id_lang = (int) $this->context->language->id;
            $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_item'];
            $subMenu_item = new TvcmsVerticalMenuItemClass(Tools::getValue('id_item'));
            $type_link = $subMenu_item->type_link;
            $ps_link_value = $subMenu_item->link[$id_lang];
        } else {
            $type_link = 1;
            $ps_link_value = 'CAT3';
        }
        $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_column'];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $tmp = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->allow_employee_form_lang = $tmp ? $tmp : 0;
        $this->fields_form = [];
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSubItem';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name
             . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsverticalmenu=' . $id_tvcmsverticalmenu
             . '&buildMenu=1';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->tpl_vars = [
            'base_url' => $this->context->shop->getBaseURL(),
            'language' => [
                'id_lang' => $language->id,
                'iso_code' => $language->iso_code,
            ],
            'fields_value' => $this->getAddFieldsValuesLink(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'all_options' => $this->getAllDefaultLink(),
            'type_link' => $type_link,
            'ps_link_value' => $ps_link_value,
        ];
        $helper->override_folder = '/';

        return $helper->generateForm([$fields_form]);
    }

    public function getAddFieldsValuesLink()
    {
        $fields = [];
        $languages = Language::getLanguages(false);
        if (Tools::isSubmit('id_item')) {
            $menu_item = new TvcmsVerticalMenuItemClass((int) Tools::getValue('id_item'));
            $fields['id_item'] = (int) Tools::getValue('id_item', $menu_item->id);
            $fields['id_column'] = (int) Tools::getValue('id_column', $menu_item->id);
            $fields['active'] = Tools::getValue('active', $menu_item->active);
            $fields['type_link'] = Tools::getValue('type_link', $menu_item->type_link);
            $fields['type_item'] = Tools::getValue('type_item', $menu_item->type_item);
            $fields['id_product'] = Tools::getValue('id_product', $menu_item->id_product);
            foreach ($languages as $lang) {
                $fields['link'][$lang['id_lang']] = Tools::getValue('link_'
                    . (int) $lang['id_lang'], $menu_item->link[$lang['id_lang']]);
                $fields['title'][$lang['id_lang']] = Tools::getValue('title_'
                    . (int) $lang['id_lang'], $menu_item->title[$lang['id_lang']]);
                $fields['text'][$lang['id_lang']] = Tools::getValue('text_'
                    . (int) $lang['id_lang'], $menu_item->text[$lang['id_lang']]);
            }
        } else {
            $menu_item = new TvcmsVerticalMenuItemClass();
            $fields['active'] = Tools::getValue('active', 1);
            $fields['id_column'] = Tools::getValue('id_column', 1);
            $fields['type_link'] = Tools::getValue('type_link', 1);
            $fields['type_item'] = Tools::getValue('type_item', 1);
            $fields['id_product'] = Tools::getValue('id_product', 0);
            foreach ($languages as $lang) {
                $fields['link'][$lang['id_lang']] = Tools::getValue('link_' . (int) $lang['id_lang'], '#');
                $fields['title'][$lang['id_lang']] = Tools::getValue('title_' . (int) $lang['id_lang'], '');
                $fields['text'][$lang['id_lang']] = Tools::getValue('text_' . (int) $lang['id_lang'], '');
            }
        }

        return $fields;
    }

    public function renderAddRow()
    {
        $id_tvcmsverticalmenu = Tools::getValue('id_tvcmsverticalmenu');
        // $w_boostrap = $this->getWidthList();
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Row Item'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->l('Special Class'),
                        'name' => 'class',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'active',
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
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
                'buttons' => [
                    [
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&token='
                        . Tools::getAdminTokenLite('AdminModules') . '&id_tvcmsverticalmenu=' . $id_tvcmsverticalmenu
                         . '&buildMenu=1',
                    'title' => $this->l('Cancel'),
                    'icon' => 'process-icon-back',
                    ],
                ],
            ],
        ];
        if (Tools::isSubmit('id_row')) {
            $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_row'];
        }
        $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_tvcmsverticalmenu'];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $tmp = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->allow_employee_form_lang = $tmp ? $tmp : 0;
        $this->fields_form = [];
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitRow';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name
             . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsverticalmenu=' . $id_tvcmsverticalmenu
             . '&buildMenu=1';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->tpl_vars = [
            'base_url' => $this->context->shop->getBaseURL(),
            'language' => [
                'id_lang' => $language->id,
                'iso_code' => $language->iso_code,
            ],
            'fields_value' => $this->getAddFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];
        $helper->override_folder = '/';

        return $helper->generateForm([$fields_form]);
    }

    public function getAddFieldsValues()
    {
        $fields = [];
        // $languages = Language::getLanguages(false);
        if (Tools::isSubmit('id_row')) {
            $row = new TvcmsVerticalMenuRowClass((int) Tools::getValue('id_row'));
            $fields['id_row'] = (int) Tools::getValue('id_row', $row->id);
            $fields['active'] = Tools::getValue('active', $row->active);
            $fields['id_tvcmsverticalmenu'] = Tools::getValue('id_tvcmsverticalmenu', $row->id_tvcmsverticalmenu);
            $fields['class'] = Tools::getValue('class', $row->class);
        } else {
            $row = new TvcmsVerticalMenuRowClass();
            $fields['active'] = Tools::getValue('active', 1);
            $fields['id_tvcmsverticalmenu'] = Tools::getValue('id_tvcmsverticalmenu', 1);
            $fields['class'] = Tools::getValue('class', '');
        }

        return $fields;
    }

    public function renderAddCol()
    {
        $id_tvcmsverticalmenu = Tools::getValue('id_tvcmsverticalmenu');
        $w_boostrap = $this->getWidthList();
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Column'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Width'),
                        'name' => 'width',
                        'options' => [
                            'query' => $w_boostrap,
                            'id' => 'key',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Special Class'),
                        'name' => 'class',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->l('Active'),
                        'name' => 'active',
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
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
                'buttons' => [
                    [
                    'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&token='
                        . Tools::getAdminTokenLite('AdminModules') . '&id_tvcmsverticalmenu=' . $id_tvcmsverticalmenu
                         . '&buildMenu=1',
                    'title' => $this->l('Cancel'),
                    'icon' => 'process-icon-back',
                    ],
                ],
            ],
        ];
        if (Tools::isSubmit('id_column')) {
            $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_column'];
        }
        $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_row'];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $tmp = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->allow_employee_form_lang = $tmp ? $tmp : 0;
        $this->fields_form = [];
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCol';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name
             . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsverticalmenu=' . $id_tvcmsverticalmenu
             . '&buildMenu=1';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->tpl_vars = [
            'base_url' => $this->context->shop->getBaseURL(),
            'language' => [
                'id_lang' => $language->id,
                'iso_code' => $language->iso_code,
            ],
            'fields_value' => $this->getAddFieldsValuesCol(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];
        $helper->override_folder = '/';

        return $helper->generateForm([$fields_form]);
    }

    public function getWidthList()
    {
        $hooks = [];

        foreach ($this->width_boostrap as $key => $width) {
            $hooks[$key]['key'] = $width;
            $hooks[$key]['name'] = $width;
        }

        return $hooks;
    }

    public function getAddFieldsValuesCol()
    {
        $fields = [];
        // $languages = Language::getLanguages(false);
        if (Tools::isSubmit('id_column')) {
            $col = new TvcmsVerticalMenuColumnClass((int) Tools::getValue('id_column'));
            $fields['id_column'] = (int) Tools::getValue('id_column', $col->id);
            $fields['active'] = Tools::getValue('active', $col->active);
            $fields['id_row'] = Tools::getValue('id_row', $col->id_row);
            $fields['width'] = Tools::getValue('width', $col->width);
            $fields['class'] = Tools::getValue('class', $col->class);
        } else {
            $col = new TvcmsVerticalMenuColumnClass();
            $fields['active'] = Tools::getValue('active', 1);
            $fields['id_row'] = Tools::getValue('id_row', 1);
            $fields['width'] = Tools::getValue('width', 'col-sm-3');
            $fields['class'] = Tools::getValue('class', '');
        }

        return $fields;
    }

    public function renderBuildMenu()
    {
        $this->context->controller->addJqueryUI('ui.sortable');
        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        $id_tvcmsverticalmenu = Tools::getValue('id_tvcmsverticalmenu', 1);
        $info_rows = $this->getRowInfo($id_tvcmsverticalmenu);

        foreach ($info_rows as $key => $info_row) {
            $info_rows[$key]['status'] = $this->displayStatusRow(
                $id_tvcmsverticalmenu,
                $info_row['id_row'],
                $info_row['active']
            );
            $info_rows[$key]['list_col'] = $this->getColInfo($info_row['id_row']);
        }
        $this->context->smarty->assign(
            [
                'id_tvcmsverticalmenu' => $id_tvcmsverticalmenu,
                'token' => Tools::getAdminTokenLite('AdminModules'),
                'info_rows' => $info_rows,
                'link' => $this->context->link,
                'url_base' => $this->context->shop->physical_uri . $this->context->shop->virtual_uri,
                'secure_key' => $this->secure_key,
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/build_menu.tpl');
    }

    public function getRowInfo($id_menu, $active = null)
    {
        $id_shop = (int) $this->context->shop->id;
        $row_infos = [];
        $row_infos_rs = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT mr.*
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_row_shop mr
            WHERE mr.`id_shop` = ' . (int) $id_shop . ' AND mr.id_tvcmsverticalmenu = ' . (int) $id_menu
                . ($active ? ' AND mr.`active` = 1' : ' '));
        if (is_array($row_infos_rs) && count($row_infos_rs) > 0) {
            $row_infos = $row_infos_rs;
        }

        return $row_infos;
    }

    public function getColInfo($id_row, $active = null, $is_backend = true)
    {
        $id_shop = (int) $this->context->shop->id;
        $id_lang = (int) $this->context->language->id;
        $col_infos = [];

        $cols_result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
            SELECT mc.*
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_column_shop mc
            WHERE mc.`id_shop` = ' . (int) $id_shop . ' AND mc.id_row = ' . (int) $id_row . ($active ? ' AND mc.`active` = 1' : '') .
            ' ORDER BY mc.position ASC, mc.id_column ASC');

        if (is_array($cols_result) && count($cols_result) > 0) {
            $col_infos = $cols_result;
        }

        if (is_array($col_infos) && count($col_infos) > 0) {
            foreach ($col_infos as $key => $col_info) {
                $sub_menu_infos = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                    SELECT smi.*,smil.*
                    FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_shop smi
                    LEFT JOIN ' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_lang smil ON (smi.id_item = smil.id_item AND '
                         . 'smi.id_shop = smil.id_shop)
                    WHERE smi.id_column = ' . (int) $col_info['id_column'] . ' AND smil.id_shop = ' . (int) $id_shop
                         . ' AND smil.id_lang = ' . (int) $id_lang . ($active ? ' AND smi.`active` = 1' : '')
                         . ' ORDER BY smi.position ASC, smi.id_item ASC');

                if (is_array($sub_menu_infos) && count($sub_menu_infos) > 0) {
                    foreach ($sub_menu_infos as $key1 => $sub_menu_info) {
                        $id_tvcmsverticalmenu = Tools::getValue('id_tvcmsverticalmenu', 1);
                        if (isset($id_tvcmsverticalmenu) && $id_tvcmsverticalmenu > 0 && $is_backend) {
                            $sub_menu_infos[$key1]['status'] = $this->displayStatusSubItem(
                                $id_tvcmsverticalmenu,
                                $sub_menu_info['id_item'],
                                $sub_menu_info['active']
                            );
                        }
                        if (1 == $sub_menu_info['type_link']) {
                            $menu_info = $this->fomartLink($sub_menu_info);
                            $sub_menu_infos[$key1]['link'] = $menu_info['link'];
                            $sub_menu_infos[$key1]['title'] = $menu_info['title'];
                        }
                        if (4 == $sub_menu_info['type_link']) {
                            $id_prod = (int) $sub_menu_info['id_product'];
                            if (isset($id_prod) && $id_prod > 0) {
                                $productarr = [];
                                $productsImages = $this->getProductById($id_prod);

                                foreach ($productsImages as $pi) {
                                    $productarr[$pi['id_product']] = $pi;
                                }
                                $product_detail = Product::getProductsProperties($id_lang, $productarr);
                                $sub_menu_infos[$key1]['product'] = $product_detail;
                            }
                        }

                        if (3 == $sub_menu_info['type_link']) {
                            $temp_url = '{lab_menu_h_url}';
                            $sub_menu_infos[$key1]['text'] = str_replace($temp_url, _PS_BASE_URL_
                                . __PS_BASE_URI__, $sub_menu_info['text']);
                        }
                    }

                    $col_infos[$key]['list_menu_item'] = $sub_menu_infos;
                } else {
                    $col_infos[$key]['list_menu_item'] = [];
                }
            }
        }

        return $col_infos;
    }

    public function getProductById($id_prod)
    {
        $id_lang = (int) $this->context->language->id;
        $productsImages = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
        SELECT MAX(image_shop.id_image) id_image, p.id_product, p.unit_price_ratio, il.legend, product_shop.active, 
            pl.name, '
             . 'pl.description_short, pl.link_rewrite, cl.link_rewrite AS category_rewrite, p.out_of_stock, p.ean13, '
             . 'p.id_category_default, product_shop.available_for_order, product_shop.minimal_quantity, '
             . 'product_shop.customizable, product_shop.show_price
        FROM ' . _DB_PREFIX_ . 'product p
        ' . Shop::addSqlAssociation('product', 'p') . '
        LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (pl.id_product = p.id_product' . Shop::addSqlRestrictionOnLang('pl')
             . ')
        LEFT JOIN ' . _DB_PREFIX_ . 'image i ON (i.id_product = p.id_product)' .
        Shop::addSqlAssociation('image', 'i', false, 'image_shop.cover=1') . '
        LEFT JOIN ' . _DB_PREFIX_ . 'image_lang il ON (il.id_image = image_shop.id_image)
        LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cl ON (cl.id_category = product_shop.id_category_default'
            . Shop::addSqlRestrictionOnLang('cl') . ')
        WHERE p.id_product =' . (int) $id_prod . '
        AND pl.id_lang = ' . (int) $id_lang . '
        AND cl.id_lang = ' . (int) $id_lang . '
        GROUP BY product_shop.id_product');

        return $productsImages;
    }

    public function getSubMenu($id_tvcmsverticalmenu)
    {
        $info_rows = [];
        if (is_array($this->getRowInfo($id_tvcmsverticalmenu, true))
            && count($this->getRowInfo($id_tvcmsverticalmenu, true)) > 0) {
            $info_rows = $this->getRowInfo($id_tvcmsverticalmenu, true);
            foreach ($info_rows as $key => $info_row) {
                $info_rows[$key]['list_col'] = $this->getColInfo($info_row['id_row'], true, false);
            }
        }

        return $info_rows;
    }

    public function menuExists($id)
    {
        $req = 'SELECT wt.`id_tvcmsverticalmenu` as id_tvcmsverticalmenu
                FROM `' . _DB_PREFIX_ . 'tvcmsverticalmenu` wt
                WHERE wt.`id_tvcmsverticalmenu` = ' . (int) $id;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);

        return $row;
    }

    public function hookHeader()
    {
        $tmp = $this->context->link->getModuleLink('tvcmsverticalmenu', 'default');
        Media::addJsDef(['gettvcmsverticalmenulink' => $tmp]);

        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/font-awesome.css');
        $this->context->controller->addCSS($this->_path . 'views/css/Pe-icon-7-stroke.css');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function hookdisplayNavFullWidth()
    {
        if (!Cache::isStored('showVerticalMenuData')) {
            $output = $this->showVerticalMenuData(true);
            Cache::store('showVerticalMenuData', $output);
        }

        return Cache::retrieve('showVerticalMenuData');
    }

    public function hookdisplayNav4()
    {
        if (!Cache::isStored('showVerticalMenuData')) {
            $output = $this->showVerticalMenuData(true);
            Cache::store('showVerticalMenuData', $output);
        }

        return Cache::retrieve('showVerticalMenuData');
    }

    public function hookdisplayLeftColumn()
    {
        if (!Cache::isStored('showVerticalMenuData')) {
            $currentPage = $this->context->controller->php_self;
            $LeftColumSts = Configuration::get('TVCMSVERTICALMENU_LEFT_COLUMN');
            $showAllPgs = Configuration::get('TVCMSVERTICALMENU_MENU_ALL_PAGES');
            if ($LeftColumSts && 'index' == $currentPage) {
                $output = $this->showVerticalMenuData(true);
            } elseif ($showAllPgs) {
                $output = $this->showVerticalMenuData(true);
            } else {
                $output = '';
            }

            Cache::store('showVerticalMenuData', $output);
        }

        return Cache::retrieve('showVerticalMenuData');
    }

    public function showVerticalMenuData($isAjax = true)
    {
        $id_lang = (int) $this->context->language->id;
        $id_shop = (int) Context::getContext()->shop->id;
        // $group_cat_result = array();

        $menu_obj = new TvcmsVerticalMenuClass();
        $menus = $menu_obj->getMenus();
        // $languages = Language::getLanguages();

        $total_category = count($menus);
        $new_menus = [];
        foreach ($menus as $menu) {
            if (0 == $menu['type_link'] && 1 == $menu['dropdown']) {
                $type = Tools::substr($menu['link'], 0, 3);
                $id = (int) Tools::substr($menu['link'], 3, Tools::strlen($menu['link']) - 3);
                if (1 == $menu['dropdown'] && 'CAT' == $type) {
                    $this->respMenu = '';
                    $category = new Category((int) $id, $id_lang, $id_shop);
                    $menu['sub_menu'] = $this->getRespCategories(
                        $id,
                        false,
                        false,
                        $category->level_depth,
                        $menu['subtitle'],
                        $menu['type_icon'],
                        $menu['icon'],
                        $menu['align_sub'],
                        $menu['width_sub'],
                        $menu['class'],
                        $isAjax
                    );
                } else {
                    $menu['sub_menu'] = [];
                }
                $menu['type'] = $type;
                $menu_info = $this->fomartLink($menu);
                $menu['link'] = $menu_info['link'];
                $menu['title'] = $menu_info['title'];
                $menu['selected_item'] = $menu_info['selected_item'];
            } else {
                if (0 == $menu['type_link']) {
                    $type = Tools::substr($menu['link'], 0, 3);
                    $menu['type'] = $type;
                    $menu_info = $this->fomartLink($menu);
                    $menu['link'] = $menu_info['link'];
                    $menu['title'] = $menu_info['title'];
                    $menu['selected_item'] = $menu_info['selected_item'];
                }
                $menu['sub_menu'] = [];
                if ($isAjax) {
                    $sub_menu = $this->getSubMenu($menu['id_tvcmsverticalmenu']);
                    if (is_array($sub_menu) && count($sub_menu) > 0) {
                        $menu['sub_menu'] = $sub_menu;
                    }
                }
            }
            $new_menus[] = $menu;
        }

        $this->context->smarty->assign([
            'menus' => $new_menus,
            'icon_path' => $this->_path . 'views/img/icons/',
            'total_category' => $total_category,
            'PS_CATALOG_MODE' => Configuration::get('PS_CATALOG_MODE'),
        ]);

        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;
        $this->context->smarty->assign('id_lang', $id_lang);

        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        $link = $this->context->link;
        $this->context->smarty->assign('link', $link);

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'image_type` WHERE  `name` = "home_default"';
        $result = Db::getInstance()->executeS($sql);
        $width = $result[0]['width'];
        $height = $result[0]['height'];
        $this->context->smarty->assign('width', $width);
        $this->context->smarty->assign('height', $height);

        return $this->display(__FILE__, 'views/templates/hook/tvcmsverticalmenu-data.tpl');
    }

    private function getAllDefaultLink($id_lang = null, $link = false)
    {
        if (is_null($id_lang)) {
            $id_lang = (int) $this->context->language->id;
        }
        $html = '<optgroup label="' . $this->l('Category') . '">';
        $html .= $this->getCategoryOption(1, $id_lang, false, true, $link);
        $html .= '</optgroup>';
        $html .= '<optgroup label="' . $this->l('Cms') . '">';
        $html .= $this->getCMSOptions(0, 0, $id_lang, $link);
        $html .= '</optgroup>';
        $html .= '<optgroup label="' . $this->l('Manufacturer') . '">';
        $manufacturers = Manufacturer::getManufacturers(false, $id_lang);
        foreach ($manufacturers as $manufacturer) {
            if ($link) {
                $html .= '<option value="' . $this->context->link->getManufacturerLink($manufacturer['id_manufacturer'])
                     . '">' . $manufacturer['name'] . '</option>';
            } else {
                $html .= '<option value="MAN' . (int) $manufacturer['id_manufacturer'] . '">' . $manufacturer['name']
                     . '</option>';
            }
        }
        $html .= '</optgroup>';
        $html .= '<optgroup label="' . $this->l('Supplier') . '">';
        $suppliers = Supplier::getSuppliers(false, $id_lang);
        foreach ($suppliers as $supplier) {
            if ($link) {
                $html .= '<option value="' . $this->context->link->getSupplierLink($supplier['id_supplier']) . '">'
                    . $supplier['name'] . '</option>';
            } else {
                $html .= '<option value="SUP' . (int) $supplier['id_supplier'] . '">' . $supplier['name'] . '</option>';
            }
        }
        $html .= '</optgroup>';
        $html .= '<optgroup label="' . $this->l('Page') . '">';
        $html .= $this->getPagesOption($id_lang, $link);
        $shoplink = Shop::getShops();
        if (count($shoplink) > 1) {
            $html .= '<optgroup label="' . $this->l('Shops') . '">';
            foreach ($shoplink as $sh) {
                $html .= '<option value="SHO' . (int) $sh['id_shop'] . '">' . $sh['name'] . '</option>';
            }
        }
        $html .= '</optgroup>';

        return $html;
    }

    public function getCategoryOption(
        $id_category = 1,
        $id_lang = false,
        $id_shop = false,
        $recursive = true,
        $link = false
    ) {
        $html = '';
        $id_lang = $id_lang ? (int) $id_lang : (int) Context::getContext()->language->id;
        $id_shop = $id_shop ? (int) $id_shop : (int) Context::getContext()->shop->id;
        $category = new Category((int) $id_category, (int) $id_lang, (int) $id_shop);
        if (is_null($category->id)) {
            return;
        }
        if ($recursive) {
            $children = Category::getChildren((int) $id_category, (int) $id_lang, true, (int) $id_shop);
            $spacer = str_repeat('&nbsp;', 3 * (int) $category->level_depth);
        }
        // $shop = (object)Shop::getShop((int)$category->getShopID());
        if (!in_array($category->id, [
                Configuration::get('PS_HOME_CATEGORY'),
                Configuration::get('PS_ROOT_CATEGORY'),
            ])) {
            if ($link) {
                $html .= '<option value="' . $this->context->link->getCategoryLink($category->id) . '">'
                    . (isset($spacer) ? $spacer : '') . str_repeat('&nbsp;', 3 * (int) $category->level_depth)
                    . $category->name . '</option>';
            } else {
                $html .= '<option value="CAT' . (int) $category->id . '">'
                    . str_repeat('&nbsp;', 3 * (int) $category->level_depth) . $category->name . '</option>';
            }
        } elseif ($category->id != Configuration::get('PS_ROOT_CATEGORY')) {
            $html .= '<optgroup label="' . str_repeat('&nbsp;', 3 * (int) $category->level_depth) . $category->name . '">';
        }
        if (isset($children) && count($children)) {
            foreach ($children as $child) {
                $tmp = (int) $child['id_category'];
                $html .= $this->getCategoryOption($tmp, (int) $id_lang, (int) $child['id_shop'], $recursive, $link);
            }
        }

        return $html;
    }

    public function getCMSOptions($parent = 0, $depth = 0, $id_lang = false, $link = false)
    {
        $html = '';
        $id_lang = $id_lang ? (int) $id_lang : (int) Context::getContext()->language->id;
        $categories = $this->getCMSCategories(false, (int) $parent, (int) $id_lang);
        $pages = $this->getCMSPages((int) $parent, false, $id_lang);
        $spacer = str_repeat('&nbsp;', 3 * (int) $depth);
        foreach ($categories as $category) {
            $html .= $this->getCMSOptions($category['id_cms_category'], (int) $depth + 1, (int) $id_lang, $link);
        }

        foreach ($pages as $page) {
            if ($link) {
                $html .= '<option value="' . $this->context->link->getCMSLink($page['id_cms']) . '">'
                    . (isset($spacer) ? $spacer : '') . $page['meta_title'] . '</option>';
            } else {
                $html .= '<option value="CMS' . $page['id_cms'] . '">' . $page['meta_title'] . '</option>';
            }
        }

        return $html;
    }

    public function getCMSCategories($recursive = false, $parent = 1, $id_lang = false)
    {
        $categories = [];
        $id_lang = $id_lang ? (int) $id_lang : (int) Context::getContext()->language->id;
        if (false === $recursive) {
            $sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`,
                    bcp.`position`, cl.`name`, cl.`link_rewrite`
                FROM `' . _DB_PREFIX_ . 'cms_category` bcp
                INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
                ON (bcp.`id_cms_category` = cl.`id_cms_category`)
                WHERE cl.`id_lang` = ' . (int) $id_lang . '
                AND bcp.`id_parent` = ' . (int) $parent;

            return Db::getInstance()->executeS($sql);
        } else {
            $sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`,
                    bcp.`position`, cl.`name`, cl.`link_rewrite`
                FROM `' . _DB_PREFIX_ . 'cms_category` bcp
                INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
                ON (bcp.`id_cms_category` = cl.`id_cms_category`)
                WHERE cl.`id_lang` = ' . (int) $id_lang . '
                AND bcp.`id_parent` = ' . (int) $parent;
            $results = Db::getInstance()->executeS($sql);
            foreach ($results as $result) {
                $sub_categories = $this->getCMSCategories(true, $result['id_cms_category'], (int) $id_lang);
                if ($sub_categories && count($sub_categories) > 0) {
                    $result['sub_categories'] = $sub_categories;
                }
                $categories[] = $result;
            }

            return isset($categories) ? $categories : false;
        }
    }

    public function getCMSPages($id_cms_category, $id_shop = false, $id_lang = false)
    {
        $id_shop = (false !== $id_shop) ? (int) $id_shop : (int) Context::getContext()->shop->id;
        $id_lang = $id_lang ? (int) $id_lang : (int) Context::getContext()->language->id;
        $sql = 'SELECT c.`id_cms`, cl.`meta_title`, cl.`link_rewrite`
            FROM `' . _DB_PREFIX_ . 'cms` c
            INNER JOIN `' . _DB_PREFIX_ . 'cms_shop` cs
            ON (c.`id_cms` = cs.`id_cms`)
            INNER JOIN `' . _DB_PREFIX_ . 'cms_lang` cl
            ON (c.`id_cms` = cl.`id_cms` AND cs.`id_shop` = cl.`id_shop`)
            WHERE c.`id_cms_category` = ' . (int) $id_cms_category . '
            AND cl.`id_shop` = ' . (int) $id_shop . '
            AND cl.`id_lang` = ' . (int) $id_lang . '
            AND c.`active` = 1
            ORDER BY `position`';

        return Db::getInstance()->executeS($sql);
    }

    public function getPagesOption($id_lang = null, $link = false)
    {
        if (is_null($id_lang)) {
            $id_lang = (int) $this->context->cookie->id_lang;
        }
        $files = Meta::getMetasByIdLang($id_lang);
        $html = '';
        foreach ($files as $file) {
            if ($link) {
                $html .= '<option value="' . $this->context->link->getPageLink($file['page']) . '">'
                    . (('' != $file['title']) ? $file['title'] : $file['page']) . '</option>';
            } else {
                $html .= '<option value="PAG' . $file['page'] . '">'
                    . (('' != $file['title']) ? $file['title'] : $file['page']) . '</option>';
            }
        }

        return $html;
    }

    public function fomartLink($item = null, $id_lang = null)
    {
        if (is_null($item)) {
            return;
        }
        if (!empty($this->context->controller->php_self)) {
            $page_name = $this->context->controller->php_self;
        } else {
            $page_name = Dispatcher::getInstance()->getController();
            $page_name = (preg_match('/^[0-9]/', $page_name) ? 'page_' . $page_name : $page_name);
        }
        $link = '';
        $selected_item = false;
        if (is_null($id_lang)) {
            $id_lang = (int) $this->context->language->id;
        }
        $type = Tools::substr($item['link'], 0, 3);
        $key = Tools::substr($item['link'], 3, Tools::strlen($item['link']) - 3);
        $title = '';
        switch ($type) {
            case 'CAT':
                if ('category' == $page_name && (int) Tools::getValue('id_category') == (int) $key) {
                    $selected_item = true;
                }
                $link = $this->context->link->getCategoryLink((int) $key, null, $id_lang);
                $category = new Category($key, $id_lang);
                $title = $category->name;

                break;
            case 'CMS':
                if ('cms' == $page_name && (int) Tools::getValue('id_cms') == (int) $key) {
                    $selected_item = true;
                }
                $id_shop = (int) Context::getContext()->shop->id;
                $link = $this->context->link->getCMSLink((int) $key, null, $id_lang, $id_shop);
                $cms = new CMS($key, $id_lang, $id_shop);
                $title = $cms->meta_title;

                break;
            case 'MAN':
                if ('manufacturer' == $page_name && (int) Tools::getValue('id_manufacturer') == (int) $key) {
                    $selected_item = true;
                }
                $man = new Manufacturer((int) $key, $id_lang);
                $link = $this->context->link->getManufacturerLink($man->id, $man->link_rewrite, $id_lang);
                $title = $man->name;

                break;
            case 'SUP':
                if ('supplier' == $page_name && (int) Tools::getValue('id_supplier') == (int) $key) {
                    $selected_item = true;
                }
                $sup = new Supplier((int) $key, $id_lang);
                $link = $this->context->link->getSupplierLink($sup->id, $sup->link_rewrite, $id_lang);
                $title = $sup->name;

                break;
            case 'PAG':
                $pag = Meta::getMetaByPage($key, $id_lang);
                $link = $this->context->link->getPageLink($pag['page'], true, $id_lang);
                if ($page_name == $pag['page']) {
                    $selected_item = true;
                }
                if ('index' == $pag['page']) {
                    $title = $this->l('Home');
                } else {
                    $title = $pag['page'];
                }

                break;
            case 'SHO':
                $shop = new Shop((int) $key);
                $link = $shop->getBaseURL();
                $title = $shop->name;

                break;
            default:
                $link = $item['link'];

                break;
        }

        return ['title' => $title, 'link' => $link, 'selected_item' => $selected_item];
    }

    public function getRespCategories(
        $id_category = 1,
        $id_lang = false,
        $id_shop = false,
        $level_root = 1,
        $sub_title = '',
        $type_icon = 1,
        $icon = '',
        $sub_align = '',
        $width_sub = '',
        $spec_class = '',
        $isAjax = false
    ) {
        $id_lang = $id_lang ? (int) $id_lang : (int) Context::getContext()->language->id;
        $id_shop = (false !== $id_shop) ? (int) $id_shop : (int) Context::getContext()->shop->id;
        $category = new Category((int) $id_category, $id_lang, $id_shop);
        $icon_path = $this->_path . 'views/img/icons/';
        if (is_null($category->id)) {
            return;
        }
        if ($isAjax) {
            $children = Category::getChildren((int) $id_category, (int) $id_lang, true, (int) $id_shop);
            // SORT: alphabetical by localized category name
            if (isset($children) && is_array($children) && count($children) > 1) {
                usort($children, function($a, $b) use ($id_lang, $id_shop) {
                    $ca = new Category((int)$a['id_category'], (int)$id_lang, (int)$id_shop);
                    $cb = new Category((int)$b['id_category'], (int)$id_lang, (int)$id_shop);
                    return strcasecmp((string)$ca->name, (string)$cb->name);
                });
            }

        }
        $class = '';
        if ($level_root > 0) {
            $new_level = (int) $category->level_depth - (int) $level_root + 1;
            $class .= 'level-' . $new_level . ' ';
        }
        if (isset($children) && count($children) && $category->level_depth > 1) {
            $class .= 'parent ';
        }
        if ('' != $spec_class) {
            $class .= $spec_class;
        }

        if ($category->level_depth > 1) {
            $cat_link = $category->getLink();
        } else {
            $cat_link = $this->context->link->getPageLink('index');
        }

        $user_groups = $this->context->customer->getGroups();
        $is_intersected = array_intersect($category->getGroups(), $user_groups);

        if (!empty($is_intersected)) {
            $this->respMenu .= '<li class="' . $class . '">';

            $this->respMenu .= '<div class="tv-vertical-menu-text-wrapper">';

            $class = 'tvvertical-menu-all-text-block';
            if (1 == $type_icon && '' != $icon) {
                $this->respMenu .= '<div class="tvvertical-menu-img-block"><i class="icon-font ' . $icon . '"></i></div>';
                $class = '';
            } elseif (0 == $type_icon && '' != $icon) {
                $this->respMenu .= '<div class="tvvertical-menu-img-block"><img'
                 . ' src="' . $icon_path . $icon . '" alt=""/></div>';
                $class = '';
            }

            $this->respMenu .= '<a href="' . $cat_link . '" class=\'' . $class . '\'>';

            $this->respMenu .= '<div class="tvvertical-menu-dropdown-icon1"></div>';

            $this->respMenu .= '<div class="tvvertical-menu-category">' . $category->name;

            $this->respMenu .= '</div>';

            if ('' != $sub_title) {
                $this->respMenu .= '<div class="tvmenu-subtitle">' . $sub_title . '</div>';
            }

            $this->respMenu .= '</a>';
            if (isset($children) && count($children) && $category->level_depth > 1) {
                $this->respMenu .= '<span class="tv-vertical-menu-icon-wrapper">';
                $this->respMenu .= '<i class="material-icons tvvertical-menu-dropdown-icon right">&#xe315;</i>';
                $this->respMenu .= '<i class="material-icons tvvertical-menu-dropdown-icon down">&#xe5c5;</i>';
                $this->respMenu .= '</span>';
            }
            $this->respMenu .= '</div>';
        }

        if (isset($children) && count($children) && $isAjax) {
            $this->respMenu .= '<ul class="menu-dropdown cat-drop-vegamenu ' . $sub_align . ' ' . $width_sub . '">';

            foreach ($children as $child) {
                $tmp = (int) $child['id_category'];
                $this->getRespCategories(
                    $tmp,
                    (int) $id_lang,
                    (int) $child['id_shop'],
                    $level_root,
                    '',
                    1,
                    '',
                    '',
                    '',
                    '',
                    $isAjax
                );
            }
            $this->respMenu .= '</ul>';
        }
        if (!empty($is_intersected)) {
            $this->respMenu .= '</li>';
        }

        return $this->respMenu;
    }

    public function hookActionShopDataDuplication($params)
    {
        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsverticalmenu_shop (`id_tvcmsverticalmenu`, `id_shop`,
                `type_link`, `dropdown`, `type_icon`, `icon`, `align_sub`, `width_sub`, `class`, `active`)
            SELECT id_tvcmsverticalmenu, ' . (int) $params['new_id_shop'] . ', type_link, dropdown, type_icon,
                icon, align_sub, width_sub, class, active
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_shop
            WHERE id_shop = ' . (int) $params['old_id_shop']);

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsverticalmenu_lang (`id_tvcmsverticalmenu`, `id_shop`,
                `id_lang`, `title`, `link`, `subtitle`)
            SELECT id_tvcmsverticalmenu, ' . (int) $params['new_id_shop'] . ', id_lang, title, link, subtitle
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_lang
            WHERE id_shop = ' . (int) $params['old_id_shop']);

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsverticalmenu_row_shop (`id_row`, `id_tvcmsverticalmenu`,
                `id_shop`, `class`, `active`)
            SELECT id_row, id_tvcmsverticalmenu, ' . (int) $params['new_id_shop'] . ', class, active
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_row_shop
            WHERE id_shop = ' . (int) $params['old_id_shop']);

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsverticalmenu_column_shop (`id_column`, `id_row`, `id_shop`,
                `width`, `class`, `active`)
            SELECT id_column, id_row, ' . (int) $params['new_id_shop'] . ', width, class, active
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_column_shop
            WHERE id_shop = ' . (int) $params['old_id_shop']);

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_shop (`id_item`, `id_column`, `id_shop`,
                `type_link`, `type_item`, `id_product`, `active`)
            SELECT id_item, id_column, ' . (int) $params['new_id_shop'] . ', type_link, type_item, id_product, active
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_shop
            WHERE id_shop = ' . (int) $params['old_id_shop']);

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_lang (`id_item`, `id_shop`, `id_lang`, `title`,
                `link`, `text`)
            SELECT id_item, ' . (int) $params['new_id_shop'] . ', id_lang, title, link, text
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_lang
            WHERE id_shop = ' . (int) $params['old_id_shop']);
    }

    public function hookActionObjectLanguageAddAfter($params)
    {
        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsverticalmenu_lang (`id_tvcmsverticalmenu`, `id_shop`, `id_lang`,
                `title`, `link`, `subtitle`)
            SELECT id_tvcmsverticalmenu, id_shop, ' . (int) $params['object']->id . ', title, link, subtitle
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_lang
            WHERE id_lang = ' . (int) Configuration::get('PS_LANG_DEFAULT'));

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_lang (`id_item`, `id_shop`, `id_lang`, `title`,
                `link`, `text`)
            SELECT id_item, id_shop, ' . (int) $params['object']->id . ', title, link, text
            FROM ' . _DB_PREFIX_ . 'tvcmsverticalmenu_item_lang
            WHERE id_lang = ' . (int) Configuration::get('PS_LANG_DEFAULT'));
    }

    public function clearCustomSmartyCache($cache_id)
    {
        if (Cache::isStored($cache_id)) {
            Cache::clean($cache_id);
        }
    }
public function hookDisplayHome($params)
    {
        // Wywołujemy funkcję generującą dane menu
        // Używamy showVerticalMenuData bezpośrednio, aby pominąć sprawdzanie konfiguracji "Show in Left Column"
        return $this->showVerticalMenuData(true);
    }

    public function hookDisplayWrapperBottom($params)
    {
        return $this->showVerticalMenuData(true);
    }
}
