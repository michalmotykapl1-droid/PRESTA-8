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
 *  @author PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2025 PrestaShop SA
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

include_once _PS_MODULE_DIR_ . 'tvcmsmegamenu/classes/tvcmsmegamenuclass.php';
include_once _PS_MODULE_DIR_ . 'tvcmsmegamenu/classes/tvcmsmegamenurowclass.php';
include_once _PS_MODULE_DIR_ . 'tvcmsmegamenu/classes/tvcmsmegamenucolumnclass.php';
include_once _PS_MODULE_DIR_ . 'tvcmsmegamenu/classes/tvcmsmegamenuitemclass.php';
include_once _PS_MODULE_DIR_ . 'tvcmsmegamenu/sql/sampledatamenu.php';

class TvcmsMegaMenu extends Module implements WidgetInterface
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
        $this->name = 'tvcmsmegamenu';
        $this->tab = 'front_office_features';
        $this->version = '4.0.1';
        $this->author = 'Themevolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Themevolty - Mega Menu');
        $this->description = $this->l('Its Show Mega Menu on Front Side');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted . ' .
            ' Are you sure you want uninstall this module?');

        $this->id_shop_group = (int) Shop::getContextShopGroupID();
        $this->id_shop = (int) Context::getContext()->shop->id;
        $this->secure_key = Tools::encrypt($this->name);
    }

    public function install()
    {
        $this->installTab();
        if (parent::install()
            && $this->registerHook('header')
            && $this->registerHook('displayMegamenu')
            && $this->registerHook('displayMegamenuMobile')
            && $this->registerHook('actionShopDataDuplication')
            && $this->registerHook('actionObjectLanguageAddAfter')) {
            include dirname(__FILE__) . '/sql/install.php'
            && $this->registerHook('displayBackOfficeHeader');

            // $sample_data = new SampleDataMenu();
            // $sample_data->initData();
            return true;
        }

        return false;
    }

    public function createDefaultData()
    {
        $this->reset();
        $sample_data = new SampleDataMenu();
        $sample_data->initData();
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
        Tools::generateIndex();
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
                $parentTab->name[$lang['id_lang']] = 'Themevolty Extension';
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
                $parentTab_2->name[$lang['id_lang']] = 'Themevolty Configure';
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
            $tab->name[$lang['id_lang']] = 'Mega Menu';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
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

    public function getContent()
    {
// enqueue BO assets for dynproducts preview
$this->context->controller->addJS(($this->_path).'views/js/bo_dynproducts.js');
$this->context->controller->addCSS(($this->_path).'views/css/bo_dynproducts.css');

// --- tvdp AJAX preview (no CSRF because BO token is required on the page) ---
if (\Tools::getIsset('ajax') && \Tools::getValue('action') === 'tvdpPreview') {
    $cfgJson = (string)\Tools::getValue('cfg', '');
    $cfg = [];
    if ($cfgJson !== '' && $cfgJson[0] === '{') {
        $tmp = json_decode($cfgJson, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) { $cfg = $tmp; }
    }
    // normalize keys
    if (!isset($cfg['refId']) && isset($cfg['refid'])) $cfg['refId'] = (int)$cfg['refid'];
    // ensure context
    $ctx = $this->context;
    if (!$ctx->language || !(int)$ctx->language->id) { $ctx->language = new \Language((int)\Configuration::get('PS_LANG_DEFAULT')); }
    if (!$ctx->shop || !(int)$ctx->shop->id)       { $defShop=(int)\Configuration::get('PS_SHOP_DEFAULT'); if ($defShop) $ctx->shop=new \Shop($defShop); }
    if (!$ctx->currency || !(int)$ctx->currency->id){ $defCur=(int)\Configuration::get('PS_CURRENCY_DEFAULT'); if ($defCur) $ctx->currency=new \Currency($defCur); }
    $id_lang = (int)$ctx->language->id;
    $id_shop = ($ctx->shop && (int)$ctx->shop->id) ? (int)$ctx->shop->id : null;

    $source = isset($cfg['source']) ? $cfg['source'] : 'new';
    $limit  = isset($cfg['limit']) ? max(1, (int)$cfg['limit']) : 8;
    $products = [];

    switch ($source) {
        case 'category':
            $refId = (int)($cfg['refId'] ?? 0);
            if ($refId>0) {
                $cat = new \Category($refId, $id_lang, $id_shop);
                $products = $cat->getProducts($id_lang, 0, $limit, $cfg['sort'] ?? 'position');
            }
            break;
        case 'tag':
            $refId = (int)($cfg['refId'] ?? 0);
            if ($refId>0) {
                $raw = \Tag::getProducts($id_lang, $refId, true);
                $products = is_array($raw) ? array_slice($raw, 0, $limit) : [];
            }
            break;
        case 'best':
            $products = \ProductSale::getBestSalesLight($id_lang, 0, $limit);
            break;
        case 'special':
            $products = \Product::getPricesDrop($id_lang, 0, $limit, false, $id_shop);
            break;
        case 'new':
        default:
            $products = \Product::getNewProducts($id_lang, 0, $limit, false, 'date_add', 'DESC');
            break;
    }

    // present
    $presented = [];
    foreach ((array)$products as $row) {
        if (is_array($row)) {
            $presented[] = \Product::getProductProperties($id_lang, $row, $ctx);
        }
    }

    $this->context->smarty->assign([
        'sub_menu_info' => [
            'dynproducts' => [
                'products'       => $presented,
                'layout'         => $cfg['layout'] ?? 'grid',
                'showPrice'      => (bool)($cfg['showPrice'] ?? $cfg['show_price'] ?? true),
                'showPromoBadge' => (bool)($cfg['showPromoBadge'] ?? $cfg['show_badge'] ?? true),
            ]
        ]
    ]);
    $html = $this->display(__FILE__, 'views/templates/admin/_dynproducts_preview.tpl');
    die($html);
}

        $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/back.css');
        // Sample Mega menu Entry Url
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
        $url_menusampleupgrade = $baseDir . $admin_folder . '/index.php?controller=AdminModules&configure=tvcmsmegamenu&tab_module=front_office_features&module_name=tvcmsmegamenu&token=' . $static_token;
        $this->context->smarty->assign('tvurlmenusampleupgrade', $url_menusampleupgrade);
        // Sample Mega menu Entry Url

        $this->_clearCache('display_menu.tpl');
        if (Tools::isSubmit('submitMenuItem')
            || Tools::isSubmit('delete_id_menu')
            || Tools::isSubmit('changeStatus')
            || Tools::isSubmit('removeIcon')
            || Tools::isSubmit('removeBackgroundImg')) {
            $this->postProcess();
            $this->html .= $this->renderList();
        } elseif ((Tools::isSubmit('buildMenu')
            || Tools::isSubmit('submitRow')
            || Tools::isSubmit('delete_row'))
            && Tools::isSubmit('id_tvcmsmegamenu')
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
            || (Tools::isSubmit('id_tvcmsmegamenu')
                && $this->menuExists(Tools::getValue('id_tvcmsmegamenu')))) {
            $this->html .= $this->renderAddForm();
        } else {
            $this->postProcess();
            $this->context->smarty->assign('module_dir', $this->_path);
            $this->html .= $this->renderList();
        }

        // $this->_displayAdvertising();
        return $this->html;
    }

    public function renderList()
    {
        $this->context->controller->addJqueryUI('ui.sortable');
        $info_menus = $this->getMenuInfo();
        foreach ($info_menus as $key => $info_menu) {
            $info_menus[$key]['status'] = $this->displayStatus($info_menu['id_tvcmsmegamenu'], $info_menu['active']);
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
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_shop pc
            LEFT JOIN ' . _DB_PREFIX_ . 'tvcmsmegamenu_lang pl ON (pc.id_tvcmsmegamenu = pl.id_tvcmsmegamenu AND '
                 . 'pc.`id_shop` = pl.`id_shop`)
            WHERE pl.id_shop = ' . (int) $id_shop . ' AND pl.id_lang = '
                . (int) $id_lang . ((int) $active ? ' AND pc.`active` = 1' : ' ') . ' ORDER BY pc.position ASC, '
                 . 'pc.id_tvcmsmegamenu ASC');
    }

    public function displayStatus($id_tvcmsmegamenu, $active)
    {
        $title = (0 == (int) $active ? $this->l('Disabled') : $this->l('Enabled'));
        $icon = (0 == (int) $active ? 'icon-remove' : 'icon-check');
        $class = (0 == (int) $active ? 'btn-danger' : 'btn-success');
        $this->context->smarty->assign(
            [
                'title' => $title,
                'icon' => $icon,
                'class' => $class,
                'id_tvcmsmegamenu' => $id_tvcmsmegamenu,
                'link' => AdminController::$currentIndex,
                'name' => $this->name,
                'token' => Tools::getAdminTokenLite('AdminModules'),
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/displaystatus.tpl');
    }

    public function displayStatusRow($id_menu, $id_row, $active)
    {
        $title = (0 == (int) $active ? $this->l('Disabled') : $this->l('Enabled'));
        $icon = (0 == (int) $active ? 'icon-remove' : 'icon-check');
        $class = (0 == (int) $active ? 'btn-danger' : 'btn-success');

        $this->context->smarty->assign(
            [
                'title' => $title,
                'icon' => $icon,
                'class' => $class,
                'id_menu' => $id_menu,
                'id_row' => $id_row,
                'link' => AdminController::$currentIndex,
                'name' => $this->name,
                'token' => Tools::getAdminTokenLite('AdminModules'),
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/displaystatusrow.tpl');
    }

    public function displayStatusSubItem($id_menu, $id_item, $active)
    {
        $title = (0 == (int) $active ? $this->l('Disabled') : $this->l('Enabled'));
        $icon = (0 == (int) $active ? 'icon-remove' : 'icon-check');
        $class = (0 == (int) $active ? 'btn-danger' : 'btn-success');

        $this->context->smarty->assign(
            [
                'title' => $title,
                'icon' => $icon,
                'class' => $class,
                'id_menu' => $id_menu,
                'id_item' => $id_item,
                'link' => AdminController::$currentIndex,
                'name' => $this->name,
                'token' => Tools::getAdminTokenLite('AdminModules'),
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/displaystatussubitem.tpl');
    }

    public function reset()
    {
        $trn_tbl = [];
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsmegamenu`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_shop`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_lang`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_row`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_row_shop`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_column`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_column_shop`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_item`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_item_shop`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsmegamenu_item_lang`';
        foreach ($trn_tbl as $table) {
            Db::getInstance()->execute($table);
        }
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
        Tools::generateIndex();
    }

    protected function postProcess()
    {
        $errors = [];
        if (Tools::isSubmit('submitTvcmsSampleMenu')) {
            $this->createDefaultData();
            $this->_clearCache('display_menu.tpl');
            $this->_clearCache('display_menu_data.tpl');
        }
        if (Tools::isSubmit('submitMenuItem')) {
            $this->_clearCache('display_menu.tpl');
            if (Tools::getValue('id_tvcmsmegamenu')) {
                $menu_item = new TvcmsMegaMenuClass((int) Tools::getValue('id_tvcmsmegamenu'));
                $pos_arr = $menu_item->getPositionByMenu((int) Tools::getValue('id_tvcmsmegamenu'));
                $menu_item->position = $pos_arr['position'];
                if (!Validate::isLoadedObject($menu_item)) {
                    $this->html .= $this->displayError($this->l('Invalid id_tvcmsmegamenu'));

                    return false;
                }
            } else {
                $menu_item = new TvcmsMegaMenuClass();
                $menu_item->position = (int) Tools::getValue('position', 0);
            }

            $menu_item->active = (int) Tools::getValue('active');

            $menu_item->type_link = Tools::getValue('type_link');
            $menu_item->dropdown = Tools::getValue('dropdown');
            $menu_item->type_icon = Tools::getValue('type_icon');
            $menu_item->background_img = Tools::getValue('background_img');
            if (1 == $menu_item->type_icon) {
                $menu_item->icon = Tools::getValue('icon_font');
            } else {
                $image_up = $menu_item->uploadImageIcon('icon_img', $this->name . '/views/img/icons/');
                if (isset($image_up['file_up']) && '' != $image_up['file_up']) {
                    $menu_item->icon = $image_up['file_up'];
                    $menu_item->height_icon = $image_up['height'];
                    $menu_item->width_icon = $image_up['width'];
                }
                $bg_img = $menu_item->uploadImage('background_img', $this->name . '/views/img/banners/');
                if (isset($bg_img) && '' != $bg_img) {
                    $menu_item->background_img = $bg_img;
                }
            }

            $menu_item->align_sub = Tools::getValue('align_sub');
            $menu_item->width_sub = Tools::getValue('width_sub');
            $menu_item->class = Tools::getValue('class');
            $menu_item->active_title = (int) Tools::getValue('active_title');

            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                if (0 == $menu_item->type_link) {
                    $menu_item->title[$language['id_lang']] = Tools::getValue('ps_link');
                    $menu_item->link[$language['id_lang']] = Tools::getValue('ps_link');
                } else {
                    $menu_item->title[$language['id_lang']] = Tools::getValue('title_' . $language['id_lang']);
                    $menu_item->link[$language['id_lang']] = Tools::getValue('link_' . $language['id_lang']);
                }
                $items1 = Tools::getValue('sub_title_stylesheet_' . $language['id_lang']);
                $menu_item->sub_title_stylesheet[$language['id_lang']] = $items1;
                $menu_item->subtitle[$language['id_lang']] = Tools::getValue('subtitle_' . $language['id_lang']);
            }

            if (!$errors) {
                if (!Tools::getValue('id_tvcmsmegamenu')) {
                    if (!$menu_item->add()) {
                        $errors[] = $this->displayError($this->l('The menu_item could not be added.'));
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true)
                             . '&conf=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name='
                            . $this->name);
                    }
                } else {
                    if (!$menu_item->update()) {
                        $errors[] = $this->displayError($this->l('The menu_item could not be updated.'));
                        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true)
                             . '&conf=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name='
                            . $this->name);
                    }
                }
            }

            return $errors;
        } elseif (Tools::isSubmit('changeStatus') && Tools::isSubmit('id_tvcmsmegamenu')) {
            $this->_clearCache('display_menu.tpl');
            $menu = new TvcmsMegaMenuClass((int) Tools::getValue('id_tvcmsmegamenu'));
            if (0 == $menu->active) {
                $menu->active = 1;
            } else {
                $menu->active = 0;
            }

            $pos_arr = $menu->getPositionByMenu((int) Tools::getValue('id_tvcmsmegamenu'));
            $menu->position = $pos_arr['position'];

            $res = $menu->update();
            $tmp = $this->displayConfirmation($this->l('Configuration updated'));
            $tmp_2 = $this->displayError($this->l('The configuration could not be updated.'));
            $this->html .= ($res ? $tmp : $tmp_2);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true)
                 . '&conf=6&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name);
        } elseif (Tools::isSubmit('removeIcon') && Tools::isSubmit('id_tvcmsmegamenu')) {
            $this->_clearCache('display_menu.tpl');
            $menu = new TvcmsMegaMenuClass((int) Tools::getValue('id_tvcmsmegamenu'));
            $icon = $menu->icon;
            if (0 === preg_match('/sample/', $icon)) {
                if ($icon && file_exists(_PS_MODULE_DIR_ . 'tvcmsmegamenu/views/img/icons/' . $icon)) {
                    @unlink(_PS_MODULE_DIR_ . 'tvcmsmegamenu/views/img/icons/' . $icon);
                }
            }
            $menu->icon = '';
            $menu->update();
        } elseif (Tools::isSubmit('removeBackgroundImg') && Tools::isSubmit('id_tvcmsmegamenu')) {
            $this->_clearCache('display_menu.tpl');
            $menu = new TvcmsMegaMenuClass((int) Tools::getValue('id_tvcmsmegamenu'));
            $background_img = $menu->background_img;
            if (0 === preg_match('/sample/', $background_img)) {
                $path = _PS_MODULE_DIR_ . 'tvcmsmegamenu/views/img/banners/' . $background_img;
                if ($background_img && file_exists($path)) {
                    @unlink(_PS_MODULE_DIR_ . 'tvcmsmegamenu/views/img/banners/' . $background_img);
                }
            }
            $menu->background_img = '';
            $menu->update();
        } elseif (Tools::isSubmit('submitRow')) {
            $this->_clearCache('display_menu.tpl');
            if (Tools::getValue('id_row')) {
                $row_item = new TvcmsMegaMenuRowClass(Tools::getValue('id_row'));
                if (!Validate::isLoadedObject($row_item)) {
                    $this->html .= $this->displayError($this->l('Invalid id_row'));

                    return false;
                }
            } else {
                $row_item = new TvcmsMegaMenuRowClass();
            }
            $row_item->active = (int) Tools::getValue('active');
            $row_item->id_tvcmsmegamenu = (int) Tools::getValue('id_tvcmsmegamenu');
            $row_item->class = Tools::getValue('class');

            if (!$errors) {
                if (!Tools::getValue('id_row')) {
                    if (!$row_item->add()) {
                        $errors[] = $this->displayError($this->l('The row_item could not be added.'));
                    }
                } else {
                    if (!$row_item->update()) {
                        $errors[] = $this->displayError($this->l('The row_item could not be updated.'));
                    }
                }
            }

            return $errors;
        } elseif (Tools::isSubmit('delete_id_menu')) {
            $this->_clearCache('display_menu.tpl');
            $menu_item = new TvcmsMegaMenuClass((int) Tools::getValue('delete_id_menu'));
            $res = $menu_item->delete();
            if (!$res) {
                $this->html .= $this->displayError('Could not delete.');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true)
                     . '&conf=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name='
                    . $this->name . '&id_tvcmsmegamenu=' . Tools::getValue('id_tvcmsmegamenu'));
            }
        } elseif (Tools::isSubmit('delete_row')) {
            $this->_clearCache('display_menu.tpl');
            $row_item = new TvcmsMegaMenuRowClass((int) Tools::getValue('id_row'));
            $res = $row_item->delete();
            if (!$res) {
                $this->html .= $this->displayError('Could not delete.');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true)
                     . '&conf=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name='
                    . $this->name . '&id_tvcmsmegamenu=' . Tools::getValue('id_tvcmsmegamenu') . '&buildMenu=1');
            }
        } elseif (Tools::isSubmit('changeStatusRow') && Tools::isSubmit('id_row')) {
            $this->_clearCache('display_menu.tpl');
            $id_shop = (int) $this->context->shop->id;
            $row = new TvcmsMegaMenuRowClass((int) Tools::getValue('id_row'), null, $id_shop);
            if (0 == $row->active) {
                $row->active = 1;
            } else {
                $row->active = 0;
            }
            $res = $row->update();
            $tmp = $this->displayConfirmation($this->l('Configuration updated'));
            $tmp_2 = $this->displayError($this->l('The configuration could not be updated.'));
            $this->html .= ($res ? $tmp : $tmp_2);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true)
                 . '&conf=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name='
                . $this->name . '&id_tvcmsmegamenu=' . Tools::getValue('id_tvcmsmegamenu') . '&buildMenu=1');
        } elseif (Tools::isSubmit('submitCol')) {
            $this->_clearCache('display_menu.tpl');
            if (Tools::getValue('id_column')) {
                $col_item = new TvcmsMegaMenuColumnClass(Tools::getValue('id_column'));
                $pos_arr = $col_item->getPositionByColumn(Tools::getValue('id_column'));
                $col_item->position = $pos_arr['position'];
                if (!Validate::isLoadedObject($col_item)) {
                    $this->html .= $this->displayError($this->l('Invalid id_column'));

                    return false;
                }
            } else {
                $col_item = new TvcmsMegaMenuColumnClass();
                $col_item->position = (int) Tools::getValue('position', 0);
            }

            $col_item->active = (int) Tools::getValue('active');

            $col_item->id_row = (int) Tools::getValue('id_row');
            $col_item->width = Tools::getValue('width');
            $col_item->class = Tools::getValue('class');

            if (!$errors) {
                if (!Tools::getValue('id_column')) {
                    if (!$col_item->add()) {
                        $errors[] = $this->displayError($this->l('The col_item could not be added.'));
                    }
                } else {
                    if (!$col_item->update()) {
                        $errors[] = $this->displayError($this->l('The col_item could not be updated.'));
                    }
                }
            }

            return $errors;
        } elseif (Tools::isSubmit('delete_col')) {
            $this->_clearCache('display_menu.tpl');
            $col_item = new TvcmsMegaMenuColumnClass((int) Tools::getValue('id_column'));
            $res = $col_item->delete();
            if (!$res) {
                $this->html .= $this->displayError('Could not delete.');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true)
                     . '&conf=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name='
                    . $this->name . '&id_tvcmsmegamenu=' . Tools::getValue('id_tvcmsmegamenu') . '&buildMenu=1');
            }
        } elseif (Tools::isSubmit('submitSubItem')) {
            $this->_clearCache('display_menu.tpl');
            if (Tools::getValue('id_item')) {
                $sub_menu_item = new TvcmsMegaMenuItemClass(Tools::getValue('id_item'));
                $pos_arr = $sub_menu_item->getPositionByMenuItem((int) Tools::getValue('id_item'));
                $sub_menu_item->position = $pos_arr['position'];
                if (!Validate::isLoadedObject($sub_menu_item)) {
                    $this->html .= $this->displayError($this->l('Invalid id_item'));

                    return false;
                }
            } else {
                $sub_menu_item = new TvcmsMegaMenuItemClass();
                $sub_menu_item->position = (int) Tools::getValue('position', 0);
            }

            $sub_menu_item->active = (int) Tools::getValue('active');

            $sub_menu_item->id_column = (int) Tools::getValue('id_column');
            $sub_menu_item->type_link = Tools::getValue('type_link');
            $sub_menu_item->type_item = Tools::getValue('type_item');
            $sub_menu_item->id_product = Tools::getValue('id_product');

            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                if (1 == $sub_menu_item->type_link) {
                    $sub_menu_item->title[$language['id_lang']] = Tools::getValue('ps_link');
                    $sub_menu_item->link[$language['id_lang']] = Tools::getValue('ps_link');
                } elseif (6 == $sub_menu_item->type_link) { 
                    // NOWE: Obsługa Custom TPL
                    $sub_menu_item->title[$language['id_lang']] = Tools::getValue('title_' . $language['id_lang']);
                    $sub_menu_item->link[$language['id_lang']] = Tools::getValue('custom_tpl_file'); // Zapisujemy nazwę pliku w linku
                    $sub_menu_item->text[$language['id_lang']] = '';
                } else {
                    $sub_menu_item->title[$language['id_lang']] = Tools::getValue('title_' . $language['id_lang']);
                    $sub_menu_item->link[$language['id_lang']] = Tools::getValue('link_' . $language['id_lang']);
                }
                // ... reszta kodu bez zmian (obsługa text_ i temp_url) ...
                $temp = Tools::getValue('text_' . $language['id_lang']);
                $temp_url = '{custom_mega_menu_h_url}';
                if (isset($temp)) {
                    $temp = str_replace(_PS_BASE_URL_ . __PS_BASE_URI__, $temp_url, $temp);
                    $sub_menu_item->text[$language['id_lang']] = $temp;
                }
            }
            if (!$errors) {
                if (!Tools::getValue('id_item')) {
                    if (!$sub_menu_item->add()) {
                        $errors[] = $this->displayError($this->l('The sub_menu_item could not be added.'));
                    }
                } else {
                    if (!$sub_menu_item->update()) {
                        $errors[] = $this->displayError($this->l('The sub_menu_item could not be updated.'));
                    }
                }
            }

            return $errors;
        } elseif (Tools::isSubmit('delete_submenu_item')) {
            $this->_clearCache('display_menu.tpl');
            $submenu_item = new TvcmsMegaMenuItemClass((int) Tools::getValue('id_item'));
            $res = $submenu_item->delete();
            if (!$res) {
                $this->html .= $this->displayError('Could not delete.');
            } else {
                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true)
                     . '&conf=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name='
                    . $this->name . '&id_tvcmsmegamenu=' . Tools::getValue('id_tvcmsmegamenu') . '&buildMenu=1');
            }
        } elseif (Tools::isSubmit('changeStatusSubItem') && Tools::isSubmit('id_item')) {
            $this->_clearCache('display_menu.tpl');
            $subMenu_item = new TvcmsMegaMenuItemClass((int) Tools::getValue('id_item'));
            if (0 == $subMenu_item->active) {
                $subMenu_item->active = 1;
            } else {
                $subMenu_item->active = 0;
            }

            $pos_arr = $subMenu_item->getPositionByMenuItem((int) Tools::getValue('id_item'));
            $subMenu_item->position = $pos_arr['position'];
            $res = $subMenu_item->update();
            $tmp = $this->displayConfirmation($this->l('Configuration updated'));
            $tmp_2 = $this->displayError($this->l('The configuration could not be updated . '));
            $this->html .= ($res ? $tmp : $tmp_2);
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', true)
                 . '&conf=1&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name='
                . $this->name . '&id_tvcmsmegamenu=' . Tools::getValue('id_tvcmsmegamenu') . '&buildMenu=1');
        }
    }

    public function renderAddForm()
    {
        $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/back.js');
        $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/back.css');
        $id_tvcmsmegamenu = Tools::getValue('id_tvcmsmegamenu');
        if (isset($id_tvcmsmegamenu)) {
            $menu = new TvcmsMegaMenuClass($id_tvcmsmegamenu);
        } else {
            $menu = new TvcmsMegaMenuClass();
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
                'image_background_baseurl' => $this->_path . 'views/img/banners/',
            ]
        );

        return $this->display(__FILE__, 'views/templates/admin/menu_item.tpl');
    }

    public function renderAddMeniItem()
    {
        // Ensure dynamic products BO assets are always loaded (even without hook registration)
        if (isset($this->context, $this->context->controller)) {
            $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/bo_dynproducts.css');
            $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/bo_dynproducts.js');
        }

        $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/back.js');
        $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/back.css');
        $id_tvcmsmegamenu = Tools::getValue('id_tvcmsmegamenu');
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
        
        [
            'id_option' => 5,
            'name' => $this->l('Dynamic products'),
        ],
        [
            'id_option' => 6,
            'name' => $this->l('Custom TPL File'),
        ]
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
        $tpl_files = $this->getCustomMenuTplFiles();
        array_unshift($tpl_files, ['id_file' => '', 'name' => $this->l('Select file...')]);


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
                        'type' => 'select',
                        'label' => $this->l('Select TPL File'),
                        'name' => 'custom_tpl_file',
                        'options' => [
                            'query' => $tpl_files,
                            'id' => 'id_file',
                            'name' => 'name',
                        ],
                        'desc' => $this->l('Upload files to: modules/tvcmsmegamenu/views/templates/front/custom_menus/'),
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
                        . Tools::getAdminTokenLite('AdminModules') . '&id_tvcmsmegamenu='
                        . $id_tvcmsmegamenu . '&buildMenu=1',
                    'title' => $this->l('Cancel'),
                    'icon' => 'process-icon-back',
                    ],
                ],
            ],
        ];
        if (Tools::isSubmit('id_item')) {
            $id_lang = (int) $this->context->language->id;
            $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_item'];
            $subMenu_item = new TvcmsMegaMenuItemClass(Tools::getValue('id_item'));
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
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure='
            . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsmegamenu='
            . $id_tvcmsmegamenu . '&buildMenu=1';
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
        $custom_tpl_val = ''; // Domyślnie puste
        $fields = [];
        $languages = Language::getLanguages(false);
        if (Tools::isSubmit('id_item')) {
            $menu_item = new TvcmsMegaMenuItemClass((int) Tools::getValue('id_item'));
            $fields['id_item'] = (int) Tools::getValue('id_item', $menu_item->id);
            $fields['id_column'] = (int) Tools::getValue('id_column', $menu_item->id);
            $fields['active'] = Tools::getValue('active', $menu_item->active);
            $fields['type_link'] = Tools::getValue('type_link', $menu_item->type_link);
            $fields['type_item'] = Tools::getValue('type_item', $menu_item->type_item);
            $fields['id_product'] = Tools::getValue('id_product', $menu_item->id_product);
            if ($menu_item->type_link == 6) {
                $def_lang = (int)Configuration::get('PS_LANG_DEFAULT');
                $custom_tpl_val = isset($menu_item->link[$def_lang]) ? $menu_item->link[$def_lang] : '';
            }
            foreach ($languages as $lang) {
                $fields['link'][$lang['id_lang']] = Tools::getValue(
                    'link_' . (int) $lang['id_lang'],
                    $menu_item->link[$lang['id_lang']]
                );
                $fields['title'][$lang['id_lang']] = Tools::getValue(
                    'title_' . (int) $lang['id_lang'],
                    $menu_item->title[$lang['id_lang']]
                );
                $fields['text'][$lang['id_lang']] = Tools::getValue(
                    'text_' . (int) $lang['id_lang'],
                    $menu_item->text[$lang['id_lang']]
                );
            }
        } else {
            $menu_item = new TvcmsMegaMenuItemClass();
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

        $fields['custom_tpl_file'] = $custom_tpl_val; // Dodane
                return $fields;
    }

    public function renderAddRow()
    {
        $id_tvcmsmegamenu = Tools::getValue('id_tvcmsmegamenu');
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
                    . Tools::getAdminTokenLite('AdminModules') . '&id_tvcmsmegamenu=' . $id_tvcmsmegamenu . '&buildMenu=1',
                    'title' => $this->l('Cancel'),
                    'icon' => 'process-icon-back',
                    ],
                ],
            ],
        ];
        if (Tools::isSubmit('id_row')) {
            $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_row'];
        }
        $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_tvcmsmegamenu'];

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
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure='
        . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsmegamenu='
        . $id_tvcmsmegamenu . '&buildMenu=1';
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
            $row = new TvcmsMegaMenuRowClass((int) Tools::getValue('id_row'));
            $fields['id_row'] = (int) Tools::getValue('id_row', $row->id);
            $fields['active'] = Tools::getValue('active', $row->active);
            $fields['id_tvcmsmegamenu'] = Tools::getValue('id_tvcmsmegamenu', $row->id_tvcmsmegamenu);
            $fields['class'] = Tools::getValue('class', $row->class);
        } else {
            $row = new TvcmsMegaMenuRowClass();
            $fields['active'] = Tools::getValue('active', 1);
            $fields['id_tvcmsmegamenu'] = Tools::getValue('id_tvcmsmegamenu', 1);
            $fields['class'] = Tools::getValue('class', '');
        }

        return $fields;
    }

    public function renderAddCol()
    {
        $id_tvcmsmegamenu = Tools::getValue('id_tvcmsmegamenu');
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
                    . Tools::getAdminTokenLite('AdminModules') . '&id_tvcmsmegamenu=' . $id_tvcmsmegamenu . '&buildMenu=1',
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
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure='
        . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&id_tvcmsmegamenu='
        . $id_tvcmsmegamenu . '&buildMenu=1';
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
            $col = new TvcmsMegaMenuColumnClass((int) Tools::getValue('id_column'));
            $fields['id_column'] = (int) Tools::getValue('id_column', $col->id);
            $fields['active'] = Tools::getValue('active', $col->active);
            $fields['id_row'] = Tools::getValue('id_row', $col->id_row);
            $fields['width'] = Tools::getValue('width', $col->width);
            $fields['class'] = Tools::getValue('class', $col->class);
        } else {
            $col = new TvcmsMegaMenuColumnClass();
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
        $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/back.js');
        $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/back.css');
        $id_tvcmsmegamenu = Tools::getValue('id_tvcmsmegamenu', 1);
        $info_rows = $this->getRowInfo($id_tvcmsmegamenu);

        foreach ($info_rows as $key => $info_row) {
            $info_rows[$key]['status'] = $this->displayStatusRow(
                $id_tvcmsmegamenu,
                $info_row['id_row'],
                $info_row['active']
            );
            $info_rows[$key]['list_col'] = $this->getColInfo($info_row['id_row']);
        }

        $this->context->smarty->assign(
            [
                'id_tvcmsmegamenu' => $id_tvcmsmegamenu,
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
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_row_shop mr
            WHERE mr.`id_shop` = ' . (int) $id_shop . ' AND mr.id_tvcmsmegamenu = ' . (int) $id_menu . ((int) $active ? ' AND mr.`active` '
                 . '= 1' : ' '));
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
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_column_shop mc
            WHERE mc.`id_shop` = ' . (int) $id_shop . ' AND mc.id_row = ' . (int) $id_row . ((int) $active ? ' AND mc.`active` = 1' : '') .
            ' ORDER BY mc.position ASC, mc.id_column ASC');

        if (is_array($cols_result) && count($cols_result) > 0) {
            $col_infos = $cols_result;
        }

        if (is_array($col_infos) && count($col_infos) > 0) {
            foreach ($col_infos as $key => $col_info) {
                $sub_menu_infos = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                    SELECT smi.*,smil.*
                    FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_item_shop smi
                    LEFT JOIN ' . _DB_PREFIX_ . 'tvcmsmegamenu_item_lang smil ON (smi.id_item = smil.id_item AND '
                         . 'smi.id_shop = smil.id_shop)
                    WHERE smi.id_column = ' . (int) $col_info['id_column'] . ' AND smil.id_shop = ' . (int) $id_shop
                     . ' AND smil.id_lang = ' . (int) $id_lang . ((int) $active ? ' AND smi.`active` = 1' : '') . ' ORDER BY '
                     . 'smi.position ASC, smi.id_item ASC');

                if (is_array($sub_menu_infos) && count($sub_menu_infos) > 0) {
                    foreach ($sub_menu_infos as $key1 => $sub_menu_info) {
                        $id_tvcmsmegamenu = Tools::getValue('id_tvcmsmegamenu', 1);
                        if (isset($id_tvcmsmegamenu) && $id_tvcmsmegamenu > 0 && $is_backend) {
                            $sub_menu_infos[$key1]['status'] = $this->displayStatusSubItem(
                                $id_tvcmsmegamenu,
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
                            // $p = new Product($id_prod);
                            if (isset($id_prod) && $id_prod > 0) {
                                $productarr = [];
                                $productsImages = $this->getProductById($id_prod);
                                foreach ($productsImages as $pi) {
                                    $productarr[$pi['id_product']] = $pi;
                                }

                                $sub_menu_infos[$key1]['product'] = Product::getProductsProperties(
                                    (int) $id_lang,
                                    $productarr
                                );
                            }
                        }
                        if (3 == $sub_menu_info['type_link']) {
                            $temp_url = '{custom_mega_menu_h_url}';
                            $sub_menu_infos[$key1]['text'] = str_replace(
                                $temp_url,
                                _PS_BASE_URL_ . __PS_BASE_URI__,
                                $sub_menu_info['text']
                            );
                        }

// Dynamic products (type 5) - parse JSON from 'link' and build product list
                        if (5 == $sub_menu_info['type_link']) {
                            $cfg_raw = isset($sub_menu_info['link']) ? $sub_menu_info['link'] : '';
                            $cfg = json_decode($cfg_raw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($cfg)) {
                                $limit = isset($cfg['limit']) ? (int)$cfg['limit'] : 8;
                                if ($limit <= 0) { $limit = 8; }
                                $source = isset($cfg['source']) ? $cfg['source'] : 'category';
                                $refId = isset($cfg['refId']) ? (int)$cfg['refId'] : 0;
                                $sort = isset($cfg['sort']) ? $cfg['sort'] : 'position';
                                $layout = isset($cfg['layout']) ? $cfg['layout'] : 'grid';
                                $showPrice = !empty($cfg['showPrice']);
                                $showPromoBadge = !empty($cfg['showPromoBadge']);
                                $id_shop = (int)$this->context->shop->id;
                                $id_lang = (int)$this->context->language->id;
                                $products = [];
                                switch ($source) {
                                    case 'category':
                                        if ($refId > 0) {
                                            $orderBy = 'position';
                                            $orderWay = 'ASC';
                                            if ($sort === 'date_add_desc') { $orderBy = 'date_add'; $orderWay = 'DESC'; }
                                            elseif ($sort === 'price_asc') { $orderBy = 'price'; $orderWay = 'ASC'; }
                                            elseif ($sort === 'price_desc') { $orderBy = 'price'; $orderWay = 'DESC'; }
                                            elseif ($sort === 'name_asc') { $orderBy = 'name'; $orderWay = 'ASC'; }
                                            elseif ($sort === 'name_desc') { $orderBy = 'name'; $orderWay = 'DESC'; }
                                            $category = new Category($refId, $id_lang, $id_shop);
                                            $products = $category->getProducts($id_lang, 1, $limit, $orderBy, $orderWay);
                                        }
                                        break;
                                    case 'tag':
                                        if ($refId > 0 && class_exists('Tag')) {
                                            $prods = Tag::getProducts($id_lang, $refId);
                                            if (is_array($prods)) { $products = array_slice($prods, 0, $limit); }
                                        }
                                        break;
                                    case 'new':
                                        $prods = Product::getNewProducts($id_lang, 0, $limit);
                                        if (is_array($prods)) { $products = $prods; }
                                        break;
                                    case 'best':
                                        if (class_exists('ProductSale')) {
                                            $prods = ProductSale::getBestSalesLight($id_lang, 0, $limit);
                                            if (is_array($prods)) { $products = $prods; }
                                        }
                                        break;
                                    case 'special':
                                        $prods = Product::getPricesDrop($id_lang, 0, $limit, false, 'date_add', 'DESC');
                                        if (is_array($prods)) { $products = $prods; }
                                        break;
                                }
                                // Present products
                                if (!empty($products)) {
                                    require_once dirname(__FILE__) . '/src/Presenter/DynProductPresenter.php';
                                    $presenter = new \TvCmsMegaMenu\Presenter\DynProductPresenter($this->context);
                                    $presented = $presenter->presentMany($products);
                                    $sub_menu_infos[$key1]['dynproducts'] = [
                                        'products' => $presented,
                                        'layout' => $layout,
                                        'showPrice' => (bool)$showPrice,
                                        'showPromoBadge' => (bool)$showPromoBadge,
                                    ];
                                }
                            }
                        }

                    }

                    
                    // Custom alphabetical sorting of submenu items per header (Front only)
                    if (!$is_backend && is_array($sub_menu_infos) && count($sub_menu_infos) > 0) {
                        $sorted_items = [];
                        $countItems = count($sub_menu_infos);
                        $i = 0;
                        while ($i < $countItems) {
                            $item = $sub_menu_infos[$i];
                            $sorted_items[] = $item;
                            $i++;
                            // Treat items with type_item == 0 as headers; following type_item == 1 items are their children
                            if (isset($item['type_item']) && (int) $item['type_item'] === 0) {
                                $children = [];
                                while (
                                    $i < $countItems
                                    && isset($sub_menu_infos[$i]['type_item'])
                                    && (int) $sub_menu_infos[$i]['type_item'] === 1
                                ) {
                                    $children[] = $sub_menu_infos[$i];
                                    $i++;
                                }
                                if (!empty($children)) {
                                    // Only sort simple category links (type_link == 1) that have a title
                                    $allSimpleCategoryLinks = true;
                                    foreach ($children as $child) {
                                        if ((int) $child['type_link'] !== 1 || empty($child['title'])) {
                                            $allSimpleCategoryLinks = false;
                                            break;
                                        }
                                    }
                                    if ($allSimpleCategoryLinks) {
                                        usort($children, function ($a, $b) {
                                            return strcasecmp($a['title'], $b['title']);
                                        });
                                    }
                                    foreach ($children as $child) {
                                        $sorted_items[] = $child;
                                    }
                                }
                            }
                        }
                        $sub_menu_infos = $sorted_items;
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
        $context = Context::getContext();

        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity,
                pl.`description_short`, pl.`available_now`, pl.`available_later`, pl.`link_rewrite`, pl.`name`,
             image_shop.`id_image` id_image, il.`legend`
            ' . (Combination::isFeatureActive() ? ', product_attribute_shop.minimal_quantity '
                . 'AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.`id_product_attribute`,0)'
                . ' id_product_attribute' : '') . '
                FROM ' . _DB_PREFIX_ . 'product p
                ' . Shop::addSqlAssociation('product', 'p') . '
                INNER JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
                    p.`id_product` = pl.`id_product`
                    AND pl.`id_lang` = ' . (int) $id_lang . Shop::addSqlRestrictionOnLang('pl') . '
                )
                ' . (Combination::isFeatureActive() ? 'LEFT JOIN '
                    . '`' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
                ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on`'
                    . ' = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')' : '') . '
                ' . Product::sqlStock('p', 0) . '
                LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
                    ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND'
                        . ' image_shop.id_shop=' . (int) $context->shop->id . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` '
                    . 'AND il.`id_lang` = ' . (int) $id_lang . ')
                WHERE p.id_product =' . (int) $id_prod . '
                GROUP BY product_shop.id_product';

        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        return $products;
    }

    public function getSubMenu($id_tvcmsmegamenu, $isAjax = false)
    {
        $info_rows = [];
        if (is_array($this->getRowInfo($id_tvcmsmegamenu, true))
            && count($this->getRowInfo($id_tvcmsmegamenu, true)) > 0) {
            if ($isAjax) {
                $info_rows = $this->getRowInfo($id_tvcmsmegamenu, true);
                foreach ($info_rows as $key => $info_row) {
                    $info_rows[$key]['list_col'] = $this->getColInfo($info_row['id_row'], true, false);
                }
            } else {
                $info_rows['isNotAjax'] = 'not ajax';
            }
        }

        return $info_rows;
    }

    public function menuExists($id)
    {
        $req = 'SELECT main.`id_tvcmsmegamenu` as id_tvcmsmegamenu
                FROM `' . _DB_PREFIX_ . 'tvcmsmegamenu` main
                WHERE main.`id_tvcmsmegamenu` = ' . (int) $id;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);

        return $row;
    }

   public function hookHeader()
{
    $tmp = $this->context->link->getModuleLink('tvcmsmegamenu', 'default');
    Media::addJsDef(['gettvcmsmegamenulink' => $tmp]);

    $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/front.js');

    // CZYSTY PODZIAŁ: PrestaShop załaduje ten plik TYLKO gdy szerokość > 991px
    $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/front_desktop.css', 'screen and (min-width: 992px)');

    // CZYSTY PODZIAŁ: PrestaShop załaduje ten plik TYLKO gdy szerokość <= 991px
    $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/front_mobile.css', 'screen and (max-width: 991px)');

    $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/dynproducts.css');

    return '';
}



    public function hookdisplayMegamenu($hookName = null, array $configuration = [])
    {
        if (!Cache::isStored('display_menu.tpl')) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration, false));
            $img_url = _THEME_IMG_DIR_;

            $this->context->smarty->assign('img_url', $img_url);
            $link = $this->context->link;
            $this->context->smarty->assign('link', $link);

            $output = $this->fetch(
                'module:' . $this->name . '/views/templates/hook/display_menu.tpl',
                $this->getCacheId()
            );
            Cache::store('display_menu.tpl', $output);
        }

        return Cache::retrieve('display_menu.tpl');
    }

    public function getWidgetVariables($hookName = null, array $configuration = [], $isAjax = false)
    {
        $id_lang = (int) $this->context->language->id;
        $id_shop = (int) Context::getContext()->shop->id;
        // $group_cat_result = array();
        $menu_obj = new TvcmsMegaMenuClass();
        $menus = $menu_obj->getMenus();
        // $languages = Language::getLanguages();

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
                        $menu['sub_title_stylesheet'],
                        $menu['type_icon'],
                        $menu['icon'],
                        $menu['align_sub'],
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
                // if ($isAjax) {
                $sub_menu = $this->getSubMenu($menu['id_tvcmsmegamenu'], $isAjax);
                if (is_array($sub_menu) && count($sub_menu) > 0) {
                    $menu['sub_menu'] = $sub_menu;
                }
                // }
            }
            $new_menus[] = $menu;
        }

        return [
            'menus' => $new_menus,
            'icon_path' => $this->_path . 'views/img/icons/',
            'background_path' => $this->_path . 'views/img/banners/',
            '_path' => $this->_path,
            'PS_CATALOG_MODE' => Configuration::get('PS_CATALOG_MODE'),
        ];
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!Cache::isStored('display_menu_data.tpl')) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration, true));
            $img_url = _THEME_IMG_DIR_;

            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'image_type` WHERE  `name` = "home_default"';
            $result = Db::getInstance()->executeS($sql);
            $width = $result[0]['width'];
            $height = $result[0]['height'];
            $this->context->smarty->assign('width', $width);
            $this->context->smarty->assign('height', $height);

            $this->context->smarty->assign('img_url', $img_url);

            $link = $this->context->link;
            $this->context->smarty->assign('link', $link);

            $output = $this->fetch(
                'module:' . $this->name . '/views/templates/hook/display_menu_data.tpl',
                $this->getCacheId()
            );
            Cache::store('display_menu_data.tpl', $output);
        }

        return Cache::retrieve('display_menu_data.tpl');
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
                $html .= '<option value="'
                    . $this->context->link->getManufacturerLink($manufacturer['id_manufacturer']) . '">'
                    . $manufacturer['name'] . '</option>';
            } else {
                $html .= '<option value="MAN'
                . (int) $manufacturer['id_manufacturer'] . '">' . $manufacturer['name'] . '</option>';
            }
        }
        $html .= '</optgroup>';
        $html .= '<optgroup label="' . $this->l('Supplier') . '">';
        $suppliers = Supplier::getSuppliers(false, $id_lang);
        foreach ($suppliers as $supplier) {
            if ($link) {
                $html .= '<option value="'
                . $this->context->link->getSupplierLink($supplier['id_supplier']) . '">' . $supplier['name']
                 . '</option>';
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
                $html .= '<option value="'
                . $this->context->link->getCategoryLink($category->id) . '">' . (isset($spacer) ? $spacer : '')
                . str_repeat('&nbsp;', 3 * (int) $category->level_depth) . $category->name . '</option>';
            } else {
                $html .= '<option value="CAT'
                . (int) $category->id . '">' . str_repeat('&nbsp;', 3 * (int) $category->level_depth) . $category->name
                 . '</option>';
            }
        } elseif ($category->id != Configuration::get('PS_ROOT_CATEGORY')) {
            $html .= '<optgroup label="'
            . str_repeat('&nbsp;', 3 * (int) $category->level_depth) . $category->name . '">';
        }
        if (isset($children) && count($children)) {
            foreach ($children as $child) {
                $html .= $this->getCategoryOption(
                    (int) $child['id_category'],
                    (int) $id_lang,
                    (int) $child['id_shop'],
                    $recursive,
                    $link
                );
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
                $html .= '<option value="'
                . $this->context->link->getCMSLink($page['id_cms']) . '">' . (isset($spacer) ? $spacer : '')
                . $page['meta_title'] . '</option>';
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
            $sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`, '
             . 'bcp.`position`, cl.`name`, cl.`link_rewrite`
                FROM `' . _DB_PREFIX_ . 'cms_category` bcp
                INNER JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl
                ON (bcp.`id_cms_category` = cl.`id_cms_category`)
                WHERE cl.`id_lang` = ' . (int) $id_lang . '
                AND bcp.`id_parent` = ' . (int) $parent;

            return Db::getInstance()->executeS($sql);
        } else {
            $sql = 'SELECT bcp.`id_cms_category`, bcp.`id_parent`, bcp.`level_depth`, bcp.`active`, '
             . 'bcp.`position`, cl.`name`, cl.`link_rewrite`
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
                $html .= '<option value="'
                . $this->context->link->getPageLink($file['page']) . '">'
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
                    $title = $pag['title'];
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
        $sub_title_stylesheet = '',
        $type_icon = 1,
        $icon = '',
        $sub_align = '',
        $spec_class = '',
        $isAjax = false
    ) {
        $id_lang = $id_lang ? (int) $id_lang : (int) Context::getContext()->language->id;
        $id_shop = (false !== $id_shop) ? (int) $id_shop : (int) Context::getContext()->shop->id;
        $category = new Category((int) $id_category, $id_lang, $id_shop);
        $icon_path = $this->_path . 'views/img/icons/';
        // $background_img = $this->_path . 'views/img/banners/';

        if (is_null($category->id)) {
            return;
        }
        // if ($isAjax) {
        $children = Category::getChildren((int) $id_category, (int) $id_lang, true, (int) $id_shop);
        // }
        $class = '';
        if ($level_root > 0) {
            $new_level = (int) $category->level_depth - (int) $level_root + 1;
            $class .= 'level-' . $new_level . ' ';
        }
        if (isset($children) && count($children) && $category->level_depth > 1) {
            $class .= 'parent ';
            if (!$isAjax) {
                $children = [];
            }
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
            $this->respMenu .= '<a href="' . $cat_link . '">';
            if (1 == $type_icon && '' != $icon) {
                $this->respMenu .= '<i class="' . $icon . '"></i>';
            } elseif (0 == $type_icon && '' != $icon) {
                $this->respMenu .= '<img src="' . $icon_path . $icon . '" alt=""/>';
            }
            $this->respMenu .= '<span>' . $category->name . '</span>';
            if ('' != $sub_title) {
                if ('' != $sub_title_stylesheet) {
                    $sub_title_stylesheet = 'style="' . $sub_title_stylesheet . '"';
                }
                $this->respMenu .= '<span class="menu-subtitle" ' . $sub_title_stylesheet . '>' . $sub_title . '</span>';
            }
            $this->respMenu .= '</a>';
        }

        if (isset($children) && count($children) && $isAjax) {
            $this->respMenu .= '<span class="icon-drop-mobile"></span>';
            $this->respMenu .= '<ul class="menu-dropdown cat-drop-menu ' . $sub_align . '">';

            foreach ($children as $child) {
                $this->getRespCategories(
                    (int) $child['id_category'],
                    (int) $id_lang,
                    (int) $child['id_shop'],
                    $level_root,
                    '',
                    '',
                    1,
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
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsmegamenu_shop (`id_tvcmsmegamenu`, `id_shop`, '
                 . '`type_link`, `dropdown`, `type_icon`, `icon`,`width_icon`,`height_icon` `background_img`, `align_sub`, `width_sub`, '
                 . '`class`, `active`, `active_title`)
            SELECT id_tvcmsmegamenu, ' . (int) $params['new_id_shop'] . ', type_link, dropdown, type_icon, '
             . 'icon,width_icon,height_icon, background_img, align_sub, width_sub, class, active, active_title
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_shop
            WHERE id_shop = ' . (int) $params['old_id_shop']);

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsmegamenu_lang (`id_tvcmsmegamenu`, `id_shop`, `id_lang`, '
                 . '`title`, `link`, `subtitle`)
            SELECT id_tvcmsmegamenu, ' . (int) $params['new_id_shop'] . ', id_lang, title, link, subtitle
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_lang
            WHERE id_shop = ' . (int) $params['old_id_shop']);

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsmegamenu_row_shop (`id_row`, `id_tvcmsmegamenu`, '
                 . '`id_shop`, `class`, `active`)
            SELECT id_row, id_tvcmsmegamenu, ' . (int) $params['new_id_shop'] . ', class, active
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_row_shop
            WHERE id_shop = ' . (int) $params['old_id_shop']);

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsmegamenu_column_shop (`id_column`, `id_row`, `id_shop`, '
                 . '`width`, `class`, `active`)
            SELECT id_column, id_row, ' . (int) $params['new_id_shop'] . ', width, class, active
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_column_shop
            WHERE id_shop = ' . (int) $params['old_id_shop']);

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsmegamenu_item_shop (`id_item`, `id_column`, '
                 . '`id_shop`, `type_link`, `type_item`, `id_product`, `active`)
            SELECT id_item, id_column, ' . (int) $params['new_id_shop'] . ', type_link, type_item, id_product, active
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_item_shop
            WHERE id_shop = ' . (int) $params['old_id_shop']);

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsmegamenu_item_lang (`id_item`, `id_shop`, `id_lang`, '
                 . '`title`, `link`, `text`)
            SELECT id_item, ' . (int) $params['new_id_shop'] . ', id_lang, title, link, text
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_item_lang
            WHERE id_shop = ' . (int) $params['old_id_shop']);
    }

    public function hookActionObjectLanguageAddAfter($params)
    {
        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsmegamenu_lang (`id_tvcmsmegamenu`, `id_shop`, `id_lang`, '
                 . '`title`, `link`, `subtitle`)
            SELECT id_tvcmsmegamenu, id_shop, ' . (int) $params['object']->id . ', title, link, subtitle
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_lang
            WHERE id_lang = ' . (int) Configuration::get('PS_LANG_DEFAULT'));

        Db::getInstance()->execute('
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsmegamenu_item_lang (`id_item`, `id_shop`, `id_lang`, '
                 . '`title`, `link`, `text`)
            SELECT id_item, id_shop, ' . (int) $params['object']->id . ', title, link, text
            FROM ' . _DB_PREFIX_ . 'tvcmsmegamenu_item_lang
            WHERE id_lang = ' . (int) Configuration::get('PS_LANG_DEFAULT'));
    }

    public function clearCache()
    {
        $this->_clearCache('display_menu.tpl');
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (isset($this->context, $this->context->controller)) {
            $this->context->controller->addCSS(_MODULE_DIR_.$this->name.'/views/css/bo_dynproducts.css');
            $this->context->controller->addJS(_MODULE_DIR_.$this->name.'/views/js/bo_dynproducts.js');
        }
    }


    public function getCustomMenuTplFiles()
    {
        $path = _PS_MODULE_DIR_ . 'tvcmsmegamenu/views/templates/front/custom_menus/';
        $files = [];
        if (is_dir($path)) {
            $scanned = scandir($path);
            foreach ($scanned as $file) {
                if (preg_match('/\.tpl$/', $file)) {
                    $files[] = ['id_file' => $file, 'name' => $file];
                }
            }
        }
        return $files;
    }

}
