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

include_once 'classes/tvcmscustomsetting_image_upload.class.php';
include_once 'classes/tvcmscustomsetting_status.class.php';
include_once 'classes/tvcustomsetting_common_list.class.php';
include_once 'classes/tvcustomsetting_db_upgrade.class.php';

class TvcmsCustomSetting extends Module
{
    public $id_shop_group = '';

    public $id_shop = '';

    public $hook_linkwidget = 'displayFooterPart1';

    public $is_hook_linkwidget_product = false;

    public $is_hook_linkwidget_our_company = true;

    public function __construct()
    {
        $this->TVDEBUG_DB = false;
        $this->name = 'tvcmscustomsetting';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Custom Setting');
        $this->description = $this->l('It is use of Custom Setting in ThemeVolty Theme');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted. Are you sure you want uninstall this module?');

        $this->id_shop_group = (int) Shop::getContextShopGroupID();
        $this->id_shop = (int) Context::getContext()->shop->id;
    }

    public function install()
    {
        $this->installTab();
        $tvcms_obj = new TvcmsCustomSettingStatus();
        $setting_var = $tvcms_obj->fieldStatusInformation();
        Configuration::updateValue('TVCMSHEADERCUSTOMLAYOUT', 'desk-header-' . $setting_var['header_layout_default']);
        Configuration::updateValue('TVCMSHEADERCUSTOMLAYOUT_MOBILE', 'mobile-header-' . $setting_var['mob_header_layout_default']);
        Configuration::updateValue('TVCMSFOOTERCUSTOMLAYOUT', 'footer-' . $setting_var['footer_layout_default']);
        Configuration::updateValue('TVCMSPRODUCTCUSTOM_LAYOUT', 'product-' . $setting_var['product_layout_default']);
        Configuration::updateValue('TVCMSCAT_BANNER_STATUS', 1);
        $this->createDefaultData();

        $this->makeInslineStyleSheet();
        $this->makeBodyInslineStyleSheet();
        $this->makeCustomFontStyleSheet();
        Tools::clearSmartyCache();

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            // $this->registerHook('displayTopOfferBanner') &&
            && $this->registerHook('displayMobileTopOfferText')
            && $this->registerHook('displayTopOfferText')
            && $this->registerHook('displayCustomsocialblock')
            && $this->registerHook('displayNav1sub1')
            // $this->registerHook('displayTop') &&
            && $this->registerHook('displayHome')
            // $this->registerHook('displayWrapperBottom') &&
            // $this->registerHook('displayFooterBefore') &&
            // $this->registerHook('displayFooterAfter') &&
            // $this->registerHook('displayDownloadApps') &&
            // $this->registerHook('displayFooterPart1') &&
            // $this->registerHook('displayFooterPart3') &&
            // $this->registerHook('displayFooterContact') &&
            && $this->registerHook('displayBodyBackgroundBody')
            && $this->registerHook('displayBackgroundBody')
            && $this->registerHook('displayCopyRightText');
    }

    public function installTab()
    {
        if (!(int) Tab::getIdFromClassName('AdminThemeVolty')) {
            $parent_tab = new Tab();
            // Need a foreach for the language
            foreach (Language::getLanguages() as $language) {
                $parent_tab->name[$language['id_lang']] = $this->l('ThemeVolty Extension');
            }
            $parent_tab->class_name = 'AdminThemeVolty';
            $parent_tab->id_parent = 0; // Home tab
            $parent_tab->module = $this->name;
            $parent_tab->add();
        }
        $tab = new Tab();
        $tab->active = 1;
        foreach (Language::getLanguages() as $language) {
            $tab->name[$language['id_lang']] = $this->l('Custom Setting');
        }
        $tab->class_name = 'Admin' . $this->name;
        $tab->id_parent = (int) Tab::getIdFromClassName('AdminThemeVolty');
        $tab->module = $this->name;
        $tab->add();
    }

    public function createDefaultData()
    {
        $this->setLinkWidgetData();
        $tvcms_obj = new TvcmsCustomSettingStatus();
        $setting_var = $tvcms_obj->fieldStatusInformation();
        // Default Variable Change Start
        Configuration::updateValue('PS_NB_DAYS_NEW_PRODUCT', 1000);
        // Default Variable Change End

        $result = [];
        $languages = Language::getLanguages();

        foreach ($languages as $lang) {
            $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE'][$lang['id_lang']] = 'Download Electron App Now';
            $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE'][$lang['id_lang']] = 'Fast, Simple & Delightful. All It takes is 30 Seconds to Download.';
            $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC'][$lang['id_lang']] = 'Fast, Simple & Delightful. All It takes is 30 Seconds to Download.';
            $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE'][$lang['id_lang']] = '#';
            $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE'][$lang['id_lang']] = '#';
            $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT'][$lang['id_lang']] = '#';

            $result['TVCMSCUSTOMSETTING_FOOTER_TAB_FEATURED_PROD_TITLE'][$lang['id_lang']] = 'Featured Product';
            $result['TVCMSCUSTOMSETTING_FOOTER_TAB_NEW_PROD_TITLE'][$lang['id_lang']] = 'New Product';
            $result['TVCMSCUSTOMSETTING_FOOTER_TAB_BEST_SELLER_PROD_TITLE'][$lang['id_lang']] = 'Best Product';
            $result['TVCMSCUSTOMSETTING_NEWSLETTER_TITLE'][$lang['id_lang']] = 'Newsletter';
            $result['TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC'][$lang['id_lang']] = 'Sign up for our newletter to recevie updates an exlusive offers';

            $result['TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE'][$lang['id_lang']] = 'Follow Us';
            $result['TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC'][$lang['id_lang']] = 'Social Icon Short Desc';

            $tmp = 'World\'s Fastest Online Shopping Destination';
            $result['TVCMSCUSTOMSETTING_CUSTOM_TEXT'][$lang['id_lang']] = $tmp;
            $tmp = '© 2019 - Ecommerce software by PrestaShop™';
            $result['TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT'][$lang['id_lang']] = $tmp;
            $tmp = 'ex : watch, laptop, Elecrtronic, hp, lenovo...';
            $result['TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT'][$lang['id_lang']] = $tmp;
            $result['TVCMSCUSTOMSETTING_COPY_RIGHT_LINK'][$lang['id_lang']] = '#';
        }

        // App Links
        Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE', 'demo_img_1.jpg');
        $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_FOOTER_IMAGE', 'demo_img_3.jpg');
        Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT', $tmp);
        Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_STATUS', 1);

        // Main Menu
        Configuration::updateValue('TVCMSCUSTOMSETTING_MAIN_MENU_STICKY', 1);
        Configuration::updateValue('TVCMSCUSTOMSETTING_SUPPLIER_STATUS', 0);
        Configuration::updateValue('TVCMSCUSTOMSETTING_BRAND_STATUS', 0);
        Configuration::updateValue('TVCMSCUSTOMSETTING_RIGHT_STICKY_STATUS', 1);
        Configuration::updateValue('TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS', 0);
        Configuration::updateValue('TVCMSCUSTOMSETTING_LEFT_STICKY_STATUS', 1);
        Configuration::updateValue('TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS', 1);

        // Bottom Sticky
        Configuration::updateValue('TVCMSCUSTOMSETTING_BOTTOM_OPTION', 0);

        // Vertical Menu is Default show
        Configuration::updateValue('TVCMSCUSTOMSETTING_VERTICAL_MENU_OPEN', 0);

        // Copy Right text Footer

        $tmp = $result['TVCMSCUSTOMSETTING_CUSTOM_TEXT'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_CUSTOM_TEXT', $tmp, true);
        Configuration::updateValue('TVCMSCUSTOMSETTING_CUSTOM_TEXT_STATUS', 1);

        $tmp = $result['TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_COPY_RIGHT_LINK'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_COPY_RIGHT_LINK', $tmp);
        Configuration::updateValue('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT_STATUS', 1);
        Configuration::updateValue('TVCMSCUSTOMSETTING_RTL_TEXT_STATUS', 0);
        Configuration::updateValue('TVCMSCUSTOMSETTING_FOOTER_IMG_STATUS', 0);

        // Create Globle Variables
        Configuration::updateValue('TVCMSCUSTOMSETTING_ADD_CONTAINER', 0);
        Configuration::updateValue('TVCMSCUSTOMSETTING_PAGE_LOADER', 1);
        Configuration::updateValue('TVCMSCUSTOMSETTING_WOW_JS', 0);
        Configuration::updateValue('TVCMSCUSTOMSETTING_HOVER_IMG', 1);
        Configuration::updateValue('TVCMSCUSTOMSETTING_TAB_PRODUCT_ROW', 1);
        Configuration::updateValue('TVCMSCUSTOMSETTING_DARK_MODE_INPUT', 0);
        Configuration::updateValue('TVCMSCUSTOMSETTING_FILTER_LEFT_PANEL', 1);
        Configuration::updateValue('TVCMSCUSTOMSETTING_PRODUCT_COLOR', 0);
        Configuration::updateValue('TVCMSCUSTOMSETTING_PRODUCT_LIST_VIEW', 'grid');
        Configuration::updateValue('TVCMSCUSTOMSETTING_CART_VIEW', 'classic');
        Configuration::updateValue('TVCMSCUSTOMSETTING_SMART_SPEED', '600');

        // Theme Option
        Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_COLOR_1', '#ffffff');
        Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_COLOR_2', '#ffffff');

        Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_PATTERN', 'pattern1');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_OLD_PATTERN', 'no_pattern.png');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS', 'pattern');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_STYLE_SHEET', '');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_COLOR', '#ffffff');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_REPEAT', 'repeat');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_ATTACHMENT', 'fixed');

        Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_OPTION', '', false);

        Configuration::updateValue('TVCMSHEADERCUSTOMLAYOUT', 'desk-header-' . $setting_var['header_layout_default']);
        Configuration::updateValue('TVCMSHEADERCUSTOMLAYOUT_MOBILE', 'mobile-header-' . $setting_var['mob_header_layout_default']);
        Configuration::updateValue('TVCMSFOOTERCUSTOMLAYOUT', 'footer-' . $setting_var['footer_layout_default']);
        Configuration::updateValue('TVCMSPRODUCTCUSTOM_LAYOUT', 'product-' . $setting_var['product_layout_default']);
        Configuration::updateValue('TVCMSCAT_BANNER_STATUS', 1);

        Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS', false);
        Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN', 'pattern1');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_OLD_PATTERN', 'no_pattern.png');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS', 'pattern');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_STYLE_SHEET', '');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR', '#ffffff');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_REPEAT', 'repeat');
        Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_ATTACHMENT', 'fixed');

        Configuration::updateValue('TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS', false);
        Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_FONT_COLOR', '#ffffff');
        Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_FONT_TYPE', 0);
        Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_2', 0);

        Configuration::updateValue('TVCMSCUSTOMSETTING_ALL_THEME_CSS_PATH', '');

        // Custom Title
        Configuration::updateValue('TVCMSCUSTOMSETTING_FOOTER_TAB_NUM_PROD', '1');
        Configuration::updateValue('TVCMSCUSTOMSETTING_FOOTER_TAB_STATUS', 1);
        $tmp = $result['TVCMSCUSTOMSETTING_FOOTER_TAB_FEATURED_PROD_TITLE'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_FOOTER_TAB_FEATURED_PROD_TITLE', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_FOOTER_TAB_NEW_PROD_TITLE'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_FOOTER_TAB_NEW_PROD_TITLE', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_FOOTER_TAB_BEST_SELLER_PROD_TITLE'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_FOOTER_TAB_BEST_SELLER_PROD_TITLE', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_NEWSLETTER_TITLE'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_NEWSLETTER_TITLE', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE', $tmp);
        $tmp = $result['TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC'];
        Configuration::updateValue('TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC', $tmp);

        Configuration::updateValue('CustomThemePath', _THEME_IMG_DIR_);
    }

    public function setLinkWidgetData()
    {
        $hook_id = (int) Hook::getIdByName($this->hook_linkwidget);
        if (0 == $hook_id) {
            $max_register_hook_id = 'SELECT MAX(id_hook) as id FROM  `' . _DB_PREFIX_ . 'hook`;';
            $result = Db::getInstance()->executeS($max_register_hook_id);
            $max_id = $result[0]['id'];
            $hook_id = $max_id + 1;

            $register_hook =
                'INSERT INTO `' .
                _DB_PREFIX_ .
                'hook` (`id_hook`, `name`, `title`, `description`, `position`)
                VALUES (' .
                (int) $hook_id .
                ', \'' .
                pSQL($this->hook_linkwidget) .
                '\', \'' .
                pSQL($this->hook_linkwidget) .
                '\', \'\', \'1\');';
            $res = Db::getInstance()->execute($register_hook);
        }

        // $hook_id = (int)Hook::getIdByName($this->hook_linkwidget);
        $queries = [];
        $queries[] = 'TRUNCATE TABLE `' . _DB_PREFIX_ . 'link_block`;';
        $queries[] = 'TRUNCATE TABLE `' . _DB_PREFIX_ . 'link_block_lang`;';

        if (true == $this->is_hook_linkwidget_product) {
            $queries[] =
                'INSERT INTO `' .
                _DB_PREFIX_ .
                'link_block`
                (`id_link_block`, `id_hook`, `position`, `content`) VALUES
                    (1, ' .
                (int) $hook_id .
                ', 1, \'{"cms":[false],
                        "product":["prices-drop","new-products","best-sales"],"static":[false]}\');';
        }

        if (true == $this->is_hook_linkwidget_our_company) {
            $queries[] =
                'INSERT INTO `' .
                _DB_PREFIX_ .
                'link_block` 
                (`id_link_block`, `id_hook`, `position`, `content`) VALUES
                    (2, ' .
                (int) $hook_id .
                ', 2, \'{"cms":["1","2","3","4","5"],
                        "product":[false],"static":["contact","sitemap","stores"]}\');';
        }

        foreach (Language::getLanguages(true, Context::getContext()->shop->id) as $lang) {
            if (true == $this->is_hook_linkwidget_product) {
                $queries[] =
                    'INSERT INTO `' .
                    _DB_PREFIX_ .
                    'link_block_lang`
                    (`id_link_block`, `id_lang`, `name`) VALUES
                    (1, ' .
                    (int) $lang['id_lang'] .
                    ', "Products");';
            }

            if (true == $this->is_hook_linkwidget_our_company) {
                $queries[] =
                    'INSERT INTO `' .
                    _DB_PREFIX_ .
                    'link_block_lang`
                    (`id_link_block`, `id_lang`, `name`) VALUES
                    (2, ' .
                    (int) $lang['id_lang'] .
                    ', "Our company");';
            }
        }

        foreach ($queries as $query) {
            Db::getInstance()->execute($query);
        }
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
        // App Links
        Configuration::deleteByName('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_FOOTER_IMAGE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_STATUS');

        // Main Menu Sticky
        Configuration::deleteByName('TVCMSCUSTOMSETTING_MAIN_MENU_STICKY');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_SUPPLIER_STATUS');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BRAND_STATUS');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_RIGHT_STICKY_STATUS');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTON_STICKY_STATUS');

        // Bottom Sticky
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BOTTOM_OPTION');

        // Vertical Menu is Default show
        Configuration::deleteByName('TVCMSCUSTOMSETTING_VERTICAL_MENU_OPEN');

        // Copy Right text Footer

        Configuration::deleteByName('TVCMSCUSTOMSETTING_CUSTOM_TEXT');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_CUSTOM_TEXT_STATUS');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_COPY_RIGHT_LINK');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_COPY_RTL_STATUS');

        // Create Globle Variables
        Configuration::deleteByName('TVCMSCUSTOMSETTING_ADD_CONTAINER');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_PAGE_LOADER');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_WOW_JS');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_HOVER_IMG');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_TAB_PRODUCT_ROW');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_DARK_MODE_INPUT');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_FILTER_LEFT_PANEL');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_PRODUCT_COLOR');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_PRODUCT_LIST_VIEW');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_CART_VIEW');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_SMART_SPEED');

        // Theme Option
        Configuration::deleteByName('TVCMSCUSTOMSETTING_THEME_OPTION');
        Configuration::deleteByName('TVCMSHEADERCUSTOMLAYOUT');
        Configuration::deleteByName('TVCMSHEADERCUSTOMLAYOUT_MOBILE');
        Configuration::deleteByName('TVCMSFOOTERCUSTOMLAYOUT');
        Configuration::deleteByName('TVCMSPRODUCTCUSTOM_LAYOUT');
        Configuration::deleteByName('TVCMSCAT_BANNER_STATUS');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_THEME_COLOR_1');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_THEME_COLOR_2');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BACKGROUND_COLOR');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_REPEAT');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_ATTACHMENT');

        Configuration::deleteByName('TVCMSCUSTOMSETTING_BACKGROUND_PATTERN');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BACKGROUND_OLD_PATTERN');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BACKGROUND_STYLE_SHEET');
        Configuration::deleteByName('TVCMSFRONTSIDE_THEME_SETTING_SHOW');

        Configuration::deleteByName('TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BODY_BACKGROUND_OLD_PATTERN');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_BODY_BACKGROUND_STYLE_SHEET');

        Configuration::deleteByName('TVCMSCUSTOMSETTING_ALL_THEME_CSS_PATH');

        // Footer Tab Product
        Configuration::deleteByName('TVCMSCUSTOMSETTING_FOOTER_TAB_NUM_PROD');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_FOOTER_TAB_STATUS');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_FOOTER_TAB_FEATURED_PROD_TITLE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_FOOTER_TAB_NEW_PROD_TITLE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_FOOTER_TAB_BEST_SELLER_PROD_TITLE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_NEWSLETTER_TITLE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE');
        Configuration::deleteByName('TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC');
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    public function formShow()
    {
        $tvcms_obj = new TvcmsCustomSettingStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $ppath = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('ppath', $ppath);

        $this->context->smarty->assign('tab_number', '#fieldset_0');

        if (!$show_fields['form_1']) {
            $this->context->smarty->assign('tab_number', '#fieldset_1_1');
        }

        if (!$show_fields['form_1'] && !$show_fields['form_2']) {
            $this->context->smarty->assign('tab_number', '#fieldset_2_2');
        }

        if (!$show_fields['form_1'] && !$show_fields['form_2'] && !$show_fields['form_3']) {
            $this->context->smarty->assign('tab_number', '#fieldset_3_3');
        }

        if (!$show_fields['form_1'] && !$show_fields['form_2'] && !$show_fields['form_3'] && !$show_fields['form_4']) {
            $this->context->smarty->assign('tab_number', '#fieldset_4_4');
        }
        $this->context->smarty->assign('show_fields', $show_fields);
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

        $url_dbupgrade = $baseDir . $admin_folder . '/index.php?controller=AdminModules&configure=tvcmscustomsetting&tab_module=front_office_features&module_name=tvcmscustomsetting&token=' . $static_token;
        $this->context->smarty->assign('tvurldbupgrade', $url_dbupgrade);

        $url_slidersampleupgrade = $baseDir . $admin_folder . '/index.php?controller=AdminModules&configure=' . $this->name . '&tab_module=front_office_features&module_name=' . $this->name . '&token=' . $static_token;
        $this->context->smarty->assign('tvurlupgrade', $url_slidersampleupgrade);

        if (Tools::isSubmit('submitTvcmsSampleinstall') && '1' == Tools::getValue('tvinstalldata')) {
            $this->createDefaultData();
        }

        $message = '';
        // check which form is not show
        $this->formShow();
        $message = $this->postProcess();
        $output = $message . '<div class="tvcmsadmincustom-setting">' . $this->display(__FILE__, 'views/templates/admin/index.tpl') . $this->renderForm() . '</div>';

        return $output;
    }

    public function postProcess()
    {
        $this->registerHook('displayMobileTopOfferText');
        $this->registerHook('displayTopOfferText');
        $message = '';
        $languages = Language::getLanguages();
        $result = [];

        if (Tools::isSubmit('submitTvcmsCustomLayoutForm') && '0' == Tools::getValue('tvinstalldata')) {
            $tmp = Tools::getValue('TVCMSHEADERCUSTOMLAYOUT');
            if ($tmp != Configuration::get('TVCMSHEADERCUSTOMLAYOUT')) {
                Configuration::updateValue('TVCMSHEADERCUSTOMLAYOUT', $tmp);
            }
            $tmp = Tools::getValue('TVCMSHEADERCUSTOMLAYOUT_MOBILE');
            if ($tmp != Configuration::get('TVCMSHEADERCUSTOMLAYOUT_MOBILE')) {
                Configuration::updateValue('TVCMSHEADERCUSTOMLAYOUT_MOBILE', $tmp);
            }
            $tmp = Tools::getValue('TVCMSFOOTERCUSTOMLAYOUT');
            if ($tmp != Configuration::get('TVCMSFOOTERCUSTOMLAYOUT')) {
                Configuration::updateValue('TVCMSFOOTERCUSTOMLAYOUT', $tmp);
            }
            $tmp = Tools::getValue('TVCMSPRODUCTCUSTOM_LAYOUT');
            if ($tmp != Configuration::get('TVCMSPRODUCTCUSTOM_LAYOUT')) {
                Configuration::updateValue('TVCMSPRODUCTCUSTOM_LAYOUT', $tmp);
            }
            $tmp = Tools::getValue('TVCMSCAT_BANNER_STATUS');
            if ($tmp != Configuration::get('TVCMSCAT_BANNER_STATUS')) {
                Configuration::updateValue('TVCMSCAT_BANNER_STATUS', $tmp);
            }
            $this->context->smarty->assign('tab_number', '#fieldset_3_3');
            $message .= $this->displayConfirmation($this->l('Layout are Updated'));
        }

        if (Tools::isSubmit('submitTvcmsThemeOptionForm') && '0' == Tools::getValue('tvinstalldata')) {
            if (!empty($_FILES['tvcmscustomsetting_custom_pattern']['name'])) {
                // Bg color and pattern
                $old_pattern = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_OLD_PATTERN');
                $this->obj_image = new TvcmsCustomSettingImageUpload();
                $ans = $this->obj_image->imageUploading($_FILES['tvcmscustomsetting_custom_pattern'], $old_pattern);

                if ($ans['success']) {
                    $file_name = $ans['name'];
                    Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_OLD_PATTERN', $file_name);
                } else {
                    $old_pattern = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_OLD_PATTERN');
                    Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_OLD_PATTERN', $old_pattern);
                }
            }

            if (!empty($_FILES['tvcmscustomsetting_custom_body_pattern']['name'])) {
                // Body color and pattern
                $old_pattern = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_OLD_PATTERN');
                $this->obj_image = new TvcmsCustomSettingImageUpload();
                $ans = $this->obj_image->imageUploading($_FILES['tvcmscustomsetting_custom_body_pattern'], $old_pattern);
                if ($ans['success']) {
                    $file_name = $ans['name'];
                    Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_OLD_PATTERN', $file_name);
                } else {
                    $old_pattern = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_OLD_PATTERN');
                    Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_OLD_PATTERN', $old_pattern);
                }
            }

            $tmp = Tools::getValue('TVCMSFRONTSIDE_THEME_SETTING_SHOW');
            if ($tmp != Configuration::get('TVCMSFRONTSIDE_THEME_SETTING_SHOW')) {
                Configuration::updateValue('TVCMSFRONTSIDE_THEME_SETTING_SHOW', $tmp);

                // if (Configuration::get('TVCMSFRONTSIDE_THEME_SETTING_SHOW')) {
                //     Module::enableByName('tvcmsthemeoptions');
                // } else {
                //     Module::disableByName('tvcmsthemeoptions');
                // }
            }
            // start font style and color
            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_REPEAT');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_REPEAT')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_REPEAT', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_ATTACHMENT');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_ATTACHMENT')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_ATTACHMENT', $tmp);
            }
            // end font style and color
            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_THEME_OPTION');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_THEME_OPTION')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_OPTION', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_THEME_COLOR_1');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_THEME_COLOR_1')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_COLOR_1', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_THEME_COLOR_2');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_THEME_COLOR_2')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_COLOR_2', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BACKGROUND_COLOR');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_COLOR')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_COLOR', $tmp);
            }

            $tmp = Tools::getValue('tvcmscustomsetting_pattern');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_PATTERN')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_PATTERN', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_ADD_CONTAINER');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_ADD_CONTAINER')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_ADD_CONTAINER', $tmp);
            }

            // start font style and color

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR', $tmp);
            }

            $tmp = Tools::getValue('tvcmscustomsetting_body_pattern');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_REPEAT');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_REPEAT')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_REPEAT', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_ATTACHMENT');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_ATTACHMENT')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_ATTACHMENT', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_THEME_FONT_TYPE');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_FONT_TYPE', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_THEME_FONT_COLOR');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_COLOR')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_FONT_COLOR', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_2');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_2')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_2', $tmp);
            }
            // end font style and color
            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_PAGE_LOADER');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_PAGE_LOADER')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_PAGE_LOADER', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_WOW_JS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_WOW_JS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_WOW_JS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_HOVER_IMG');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_HOVER_IMG')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_HOVER_IMG', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_TAB_PRODUCT_ROW');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_TAB_PRODUCT_ROW')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_TAB_PRODUCT_ROW', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_DARK_MODE_INPUT');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_DARK_MODE_INPUT')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_DARK_MODE_INPUT', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_FILTER_LEFT_PANEL');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_FILTER_LEFT_PANEL')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_FILTER_LEFT_PANEL', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_PRODUCT_COLOR');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_COLOR')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_PRODUCT_COLOR', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_PRODUCT_LIST_VIEW');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_LIST_VIEW')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_PRODUCT_LIST_VIEW', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_CART_VIEW');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_CART_VIEW')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_CART_VIEW', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_SMART_SPEED');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_SMART_SPEED')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_SMART_SPEED', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_MAIN_MENU_STICKY');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_MAIN_MENU_STICKY')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_MAIN_MENU_STICKY', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_SUPPLIER_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_SUPPLIER_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_SUPPLIER_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BRAND_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BRAND_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BRAND_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_RIGHT_STICKY_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_RIGHT_STICKY_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_RIGHT_STICKY_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_LEFT_STICKY_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_LEFT_STICKY_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_LEFT_STICKY_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_BOTTOM_OPTION');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_BOTTOM_OPTION')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_BOTTOM_OPTION', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_VERTICAL_MENU_OPEN');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_VERTICAL_MENU_OPEN')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_VERTICAL_MENU_OPEN', $tmp);
            }

            $ftpThemeDir = _PS_ALL_THEMES_DIR_ . _THEME_NAME_ . '/assets/css';
            $allThemeCssPath = '/all_theme_custom_' . $this->id_shop_group . '_' . $this->id_shop . '.css';
            if (file_exists($ftpThemeDir . $allThemeCssPath)) {
                unlink($ftpThemeDir . $allThemeCssPath);
            }
            Configuration::updateValue('TVCMSCUSTOMSETTING_ALL_THEME_CSS_PATH', '');

            $this->makeInslineStyleSheet();
            $this->makeBodyInslineStyleSheet();
            $this->makeCustomFontStyleSheet();
            $this->context->smarty->assign('tab_number', '#fieldset_0');
            $message .= $this->displayConfirmation($this->l('Theme Configuration is Updates'));
        }

        if (Tools::isSubmit('submitTvcmsAppLinkForm') && '0' == Tools::getValue('tvinstalldata')) {
            if ($_FILES['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE']) {
                $old_img = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE');
                $this->obj_image = new TvcmsCustomSettingImageUpload();
                $ans = $this->obj_image->imageUploading($_FILES['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE'], $old_img);
                if ($ans['success']) {
                    $file_name = $ans['name'];
                    Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE', $file_name);
                } else {
                    $old_img = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE');
                    Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE', $old_img);
                }
            }

            foreach ($languages as $lang) {
                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE', $tmp);

            $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE', $tmp);

            $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC', $tmp);

            $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE', $tmp);

            $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE', $tmp);

            $tmp = $result['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT', $tmp);

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_STATUS', $tmp);
            }

            $this->context->smarty->assign('tab_number', '#fieldset_1_1');

            $this->clearCustomSmartyCache('tvcmscustomsetting_display_download_app.tpl');

            $message .= $this->displayConfirmation($this->l('App Link is Updated'));
        }

        if (Tools::isSubmit('submitTvcmsCustomTitleForm') && '0' == Tools::getValue('tvinstalldata')) {
            if ($_FILES['TVCMSCUSTOMSETTING_FOOTER_IMAGE']) {
                $old_img = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_IMAGE');
                $this->obj_image = new TvcmsCustomSettingImageUpload();
                $ans = $this->obj_image->imageUploading($_FILES['TVCMSCUSTOMSETTING_FOOTER_IMAGE'], $old_img);
                if ($ans['success']) {
                    $file_name = $ans['name'];
                    Configuration::updateValue('TVCMSCUSTOMSETTING_FOOTER_IMAGE', $file_name);
                } else {
                    $old_img = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_IMAGE');
                    Configuration::updateValue('TVCMSCUSTOMSETTING_FOOTER_IMAGE', $old_img);
                }
            }
        }

        if (Tools::isSubmit('submitTvcmsCustomTitleForm') && '0' == Tools::getValue('tvinstalldata')) {
            foreach ($languages as $lang) {
                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_CUSTOM_TEXT_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_CUSTOM_TEXT'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_COPY_RIGHT_LINK_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_COPY_RIGHT_LINK'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_NEWSLETTER_TITLE_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_NEWSLETTER_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = Tools::getValue('TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC_' . $lang['id_lang']);
                $result['TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSCUSTOMSETTING_CUSTOM_TEXT'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_CUSTOM_TEXT', $tmp, true);

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_CUSTOM_TEXT_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_CUSTOM_TEXT_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_CUSTOM_TEXT_STATUS', $tmp);
            }

            $tmp = $result['TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT', $tmp);

            $tmp = $result['TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT', $tmp);

            $tmp = $result['TVCMSCUSTOMSETTING_COPY_RIGHT_LINK'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_COPY_RIGHT_LINK', $tmp);

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_RTL_TEXT_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_RTL_TEXT_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_RTL_TEXT_STATUS', $tmp);
            }

            $tmp = Tools::getValue('TVCMSCUSTOMSETTING_FOOTER_IMG_STATUS');
            if ($tmp != Configuration::get('TVCMSCUSTOMSETTING_FOOTER_IMG_STATUS')) {
                Configuration::updateValue('TVCMSCUSTOMSETTING_FOOTER_IMG_STATUS', $tmp);
            }

            $tmp = $result['TVCMSCUSTOMSETTING_NEWSLETTER_TITLE'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_NEWSLETTER_TITLE', $tmp);

            $tmp = $result['TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC', $tmp);

            $tmp = $result['TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE', $tmp);

            $tmp = $result['TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC'];
            Configuration::updateValue('TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC', $tmp);

            $this->context->smarty->assign('tab_number', '#fieldset_2_2');

            $this->clearCustomSmartyCache('tvcmscustomsetting_display_copy_right_text.tpl');
            $this->clearCustomSmartyCache('tvcmscustomsetting_displaytopoffertext');

            $message .= $this->displayConfirmation($this->l('Custom Titles are Updated'));
        }

        if (Tools::isSubmit('submitTvcmsUpgradeThemeForm') && '' == Tools::getValue('tvcmsInstallDataForm')) {
            if (Tools::version_compare($this->version, '4.0.0', '<=')) {
                // echo _DB_PREFIX_;
                $obj = new TvcmsCustomSettingDbUpgrade();
                $message .= $obj->DBUpgrade360($this);
                Tools::clearSmartyCache();
                Tools::clearXMLCache();
                Media::clearCache();
                Tools::generateIndex();
                // echo "call ".$this->version;
            }
            $this->context->smarty->assign('tab_number', '#fieldset_4_4');
            $message .= $this->displayConfirmation($this->l('Upgrade Database Successfully.'));
        }
        if (Tools::isSubmit('submitTvcmsUpgradeThemeForm') && 'on' == Tools::getValue('tvcmsInstallDataForm')) {
            $obj = new TvcmsCustomSettingStatus();
            $result = $obj->fieldStatusInformation();

            foreach ($result['import_data_module_list'] as $key => $value) {
                try {
                    $include_path = '../modules/' . $value . '/' . $value . '.php';
                    include_once $include_path;
                    $obj = new $value();
                    $obj->createDefaultData();
                    Tools::clearSmartyCache();
                    Tools::clearXMLCache();
                    Media::clearCache();
                    Tools::generateIndex();
                } catch (Exception $e) {
                    if ($this->TVDEBUG_DB) {
                        $message .= $this->displayError('Not found Module Name -> ' . $value);
                    }
                }
            }
            $this->context->smarty->assign('tab_number', '#fieldset_4_4');
            $message .= $this->displayConfirmation($this->l('Import Sample Data Successfully.'));
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

    public function colorLuminance($hex, $percent)
    {
        $hex = preg_replace('/[^0-9a-f]/i', '', $hex);
        $new_hex = '#';

        if (Tools::strlen($hex) < 6) {
            $hex = $hex[0] + $hex[0] + $hex[1] + $hex[1] + $hex[2] + $hex[2];
        }

        // convert to decimal and change luminosity
        for ($i = 0; $i < 3; ++$i) {
            $dec = hexdec(Tools::substr($hex, $i * 2, 2));
            $dec = min(max(0, $dec + $dec * $percent), 255);
            $new_hex .= str_pad(dechex($dec), 2, 0, STR_PAD_LEFT);
        }

        return $new_hex;
    }

    public function getContrastColor($hexColor)
    {
        // hexColor RGB
        $R1 = hexdec(Tools::substr($hexColor, 1, 2));
        $G1 = hexdec(Tools::substr($hexColor, 3, 2));
        $B1 = hexdec(Tools::substr($hexColor, 5, 2));

        // Black RGB
        $blackColor = '#000000';
        $R2BlackColor = hexdec(Tools::substr($blackColor, 1, 2));
        $G2BlackColor = hexdec(Tools::substr($blackColor, 3, 2));
        $B2BlackColor = hexdec(Tools::substr($blackColor, 5, 2));

        // Calc contrast ratio
        $L1 = 0.2126 * pow($R1 / 255, 2.2) + 0.7152 * pow($G1 / 255, 2.2) + 0.0722 * pow($B1 / 255, 2.2);

        $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) + 0.7152 * pow($G2BlackColor / 255, 2.2) + 0.0722 * pow($B2BlackColor / 255, 2.2);

        $contrastRatio = 0;
        if ($L1 > $L2) {
            $contrastRatio = (int) (($L1 + 0.05) / ($L2 + 0.05));
        } else {
            $contrastRatio = (int) (($L2 + 0.05) / ($L1 + 0.05));
        }

        // If contrast is more than 5, return black color
        if ($contrastRatio > 5) {
            return '#000000';
        } else {
            // if not, return white color.
            return '#FFFFFF';
        }
    }

    public function createCustomThemeCss(
        $filename,
        $newfilename,
        $string_to_replace1,
        $replace_with1,
        $string_to_replace2 = null,
        $replace_with2 = null,
        $string_to_replace3 = null,
        $replace_with3 = null,
        $string_to_replace4 = null,
        $replace_with4 = null
    ) {
        $content = Tools::file_get_contents($filename);

        $content_chunks = explode($string_to_replace1, $content);
        $content = implode($replace_with1, $content_chunks);

        if (!empty($string_to_replace2) && !empty($replace_with2)) {
            $content_chunks = explode($string_to_replace2, $content);
            $content = implode($replace_with2, $content_chunks);
        }

        if (!empty($string_to_replace3) && !empty($replace_with3)) {
            $content_chunks = explode($string_to_replace3, $content);
            $content = implode($replace_with3, $content_chunks);
        }

        if (!empty($string_to_replace4) && !empty($replace_with4)) {
            $content_chunks = explode($string_to_replace4, $content);
            $content = implode($replace_with4, $content_chunks);
        }

        file_put_contents($newfilename, $content);
        $ftpThemeDir = _PS_ALL_THEMES_DIR_ . _THEME_NAME_ . '/assets/css';
        $themeCssPath = $ftpThemeDir . '/all_theme_custom_' . $this->id_shop_group . '_' . $this->id_shop . '.css';
        file_put_contents($themeCssPath, $content, FILE_APPEND);

        $allThemeCssPath = '/all_theme_custom_' . $this->id_shop_group . '_' . $this->id_shop . '.css';
        Configuration::updateValue('TVCMSCUSTOMSETTING_ALL_THEME_CSS_PATH', $allThemeCssPath);
    }

    public function makeInslineStyleSheet()
    {
        $style = '';
        if (Configuration::get('TVCMSCUSTOMSETTING_ADD_CONTAINER')) {
            if ('color' == Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS')) {
                $color = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_COLOR');
                $style .= 'background-color:' . $color;
            } else {
                $img = '';
                if ('custompattern' == Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_PATTERN')) {
                    $img = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_OLD_PATTERN');
                    $path = _MODULE_DIR_ . $this->name . '/views/img/' . $img;
                } else {
                    $img = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_PATTERN') . '.png';
                    $path = _THEME_IMG_DIR_ . 'pattern/' . $img;
                }

                // $path = _MODULE_DIR_.$this->name."/views/img/".$img;
                $style .= 'background-image:url(' . $path . ');';
            }
        }
        Configuration::updateValue('TVCMSCUSTOMSETTING_BACKGROUND_STYLE_SHEET', $style);

        if ('theme_custom' == Configuration::get('TVCMSCUSTOMSETTING_THEME_OPTION')) {
            // this is Color
            $color_replace_1 = '#maincolor1';
            $color_1 = Configuration::get('TVCMSCUSTOMSETTING_THEME_COLOR_1');

            $color_replace_text_1 = '#maincolortext1';
            $color_text_1 = $this->getContrastColor($color_1);

            $color_replace_alt_text_1 = '#altcolortext1';
            $color_alt_text_1 = '#000000' == $color_text_1 ? '#ffffff' : '#000000';

            // This is Gredeant Color
            $color_replace_2 = '#maincolor2';
            $color_2 = Configuration::get('TVCMSCUSTOMSETTING_THEME_COLOR_2');

            $color_replace_text_2 = '#maincolortext2';
            $color_text_2 = $this->getContrastColor($color_2);

            $color_replace_alt_text_2 = '#altcolortext2';
            $color_alt_text_2 = '#000000' == $color_text_2 ? '#ffffff' : '#000000';

            $ftpThemeDir = _PS_ALL_THEMES_DIR_ . _THEME_NAME_ . '/assets/css';
            $filename = $ftpThemeDir . '/theme-custom.css';
            $themeCssPath = '/' . Configuration::get('TVCMSCUSTOMSETTING_THEME_OPTION') . '_' . $this->id_shop_group . '_' . $this->id_shop . '.css';
            $newfilename = $ftpThemeDir . $themeCssPath;

            $this->createCustomThemeCss(
                $filename,
                $newfilename,
                $color_replace_1,
                $color_1,
                $color_replace_2,
                $color_2,
                $color_replace_text_1,
                $color_text_1,
                $color_replace_text_2,
                $color_text_2,
                $color_replace_alt_text_1,
                $color_alt_text_1,
                $color_replace_alt_text_2,
                $color_alt_text_2
            );
            // half path for front site.
            Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_CSS_PATH', $themeCssPath);
        }
    }

    public function hookdisplayBackgroundBody()
    {
        return Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_STYLE_SHEET');
    }

    public function makeBodyInslineStyleSheet()
    {
        $style = '';
        $path = '';
        if ('color' == Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS')) {
            $color = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR');
            $style .= 'background-color:' . $color . ';';
        } else {
            $img = '';
            if ('custombodypattern' == Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN')) {
                $img = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_OLD_PATTERN');
                $path .= _MODULE_DIR_ . $this->name . '/views/img/' . $img;
            } else {
                $img = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN') . '.png';
                $path .= _THEME_IMG_DIR_ . 'pattern/' . $img;
            }

            // $path = _MODULE_DIR_.$this->name."/views/img/".$img;
            $style .= 'background-image:url(' . $path . ');';
        }
        Configuration::updateValue('TVCMSCUSTOMSETTING_BODY_BACKGROUND_STYLE_SHEET', $style);
    }

    public function hookdisplayBodyBackgroundBody()
    {
        return Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_STYLE_SHEET');
    }

    public function makeCustomFontStyleSheet()
    {
        if (1 == Configuration::get('TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS')) {
            if ('0' != Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE')) {
                $font_replace_1 = '#fontFamily1';
                $font_replace_2 = '#fontLink1';
                $font_style_1 = Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE');
                $font_link_2 = $this->getFontLinkUsingFontName($font_style_1);
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_LINK', $font_link_2);

                $ftpThemeDir = _PS_ALL_THEMES_DIR_ . _THEME_NAME_ . '/assets/css/';
                $filename = $ftpThemeDir . 'theme-custom-title-font.css';
                $themeCssFontPath = 'theme-custom-title-font_' . $this->id_shop_group . '_' . $this->id_shop . '.css';
                $newfilename = $ftpThemeDir . $themeCssFontPath;

                $this->createCustomThemeCss($filename, $newfilename, $font_replace_1, $font_style_1, $font_replace_2, $font_link_2);
                $file = $themeCssFontPath;
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_LINK_URL', $file);
            }

            if ('0' != Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_2')) {
                $font_replace_1 = '#fontFamily2';
                $font_replace_2 = '#fontLink2';
                $font_style_1 = Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_2');
                $font_link_2 = $this->getFontLinkUsingFontName($font_style_1);
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_LINK_2', $font_link_2);

                $ftpThemeDir = _PS_ALL_THEMES_DIR_ . _THEME_NAME_ . '/assets/css/';
                $filename = $ftpThemeDir . 'theme-custom-body-font.css';
                $themeCssFontPath = 'theme_custom_body_font_' . $this->id_shop_group . '_' . $this->id_shop . '.css';
                $newfilename = $ftpThemeDir . $themeCssFontPath;

                $this->createCustomThemeCss($filename, $newfilename, $font_replace_1, $font_style_1, $font_replace_2, $font_link_2);

                $file = $themeCssFontPath;
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_LINK_2_URL', $file);
            }

            if ('' != Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_COLOR')) {
                $custom_title_color_replace_1 = '#customTitleColor';
                $custom_title_color_1 = Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_COLOR');

                $ftpThemeDir = _PS_ALL_THEMES_DIR_ . _THEME_NAME_ . '/assets/css/';
                $filename = $ftpThemeDir . 'theme-custom-title-color.css';
                $themeCssFontPath = 'theme_custom_title_color_' . $this->id_shop_group . '_' . $this->id_shop . '.css';
                $newfilename = $ftpThemeDir . $themeCssFontPath;

                $this->createCustomThemeCss($filename, $newfilename, $custom_title_color_replace_1, $custom_title_color_1);

                $file = $themeCssFontPath;
                Configuration::updateValue('TVCMSCUSTOMSETTING_THEME_CUSTOM_TITLE_COLOR', $file);
            }
        }
    }

    public function getFontLinkUsingFontName($selected_font_name)
    {
        // $url_font_name = str_replace($font_name, ' ', '+');
        $link = '';
        // $font = array();
        $obj = new tvcmsCustomSettingCommonList();
        $fonts = $obj->titleFontList();
        // $link = $font['name']['link'];
        foreach ($fonts as $font) {
            if ($selected_font_name == $font['name']) {
                $link = $font['link'];

                break;
            }
        }

        return $link;
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
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormValues() /* Add values for your inputs */,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->tvcmsThemeOptionForm(), $this->tvcmsAppLinkForm(), $this->tvcmsFooterProductForm(), $this->tvcmsCustomLayoutForm(), $this->tvcmsInstallDataForm()]);
    }

    protected function tvcmsThemeOptionForm()
    {
        $tvcms_obj = new TvcmsCustomSettingStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        // This is Theme option information
        if ($show_fields['all_theme_option_info']) {
            $input[] = [
                'col' => 3,
                'type' => 'custom_theme_option',
                'name' => 'TVCMSCUSTOMSETTING_THEME_OPTION',
                'label' => $this->l('Theme Options'),
            ];

            $input[] = [
                'col' => 8,
                'type' => 'color',
                'name' => 'TVCMSCUSTOMSETTING_THEME_COLOR_1',
                'label' => $this->l('Custom Theme Color'),
            ];

            $input[] = [
                'col' => 8,
                'type' => 'color',
                'name' => 'TVCMSCUSTOMSETTING_THEME_COLOR_2',
                'label' => $this->l('Custom Theme Color 2'),
            ];

            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Box Layout'),
                'name' => 'TVCMSCUSTOMSETTING_ADD_CONTAINER',
                'desc' => $this->l('Box layout show in front side'),
                'is_bool' => true,
                'class' => 'tvcmsadd-box',
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

            $input[] = [
                'type' => 'radio',
                'label' => $this->l('Background Theme'),
                'name' => 'TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS',
                'desc' => $this->l('Types of background styles'),
                'is_bool' => true,
                'class' => 'tvcmsbackground-type',
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 'color',
                        'label' => $this->l('Color'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 'pattern',
                        'label' => $this->l('Pattern'),
                    ],
                ],
            ];

            $input[] = [
                'col' => 8,
                'type' => 'color',
                'label' => $this->l('Back Ground Theme Color'),
                'name' => 'TVCMSCUSTOMSETTING_BACKGROUND_COLOR',
            ];

            $input[] = [
                'col' => 8,
                'type' => 'file_upload_3',
                'name' => 'TVCMSCUSTOMSETTING_BACKGROUND_PATTERN',
                'label' => $this->l('BackGround Pattern'),
                'lang' => true,
            ];
            $input[] = [
                'col' => 8,
                'type' => 'select',
                'name' => 'TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_REPEAT',
                'label' => $this->l('Background Image Css Repeat'),
                'desc' => $this->l('Select your "background-repeat" css property. its value "repeat" and "no-repeat". this option only work with "background-image" not "background-color".'),
                'options' => [
                    'query' => [
                        [
                            'id_option' => 'repeat',
                            'name' => 'Repeat',
                        ],
                        [
                            'id_option' => 'no-repeat',
                            'name' => 'No Repeat',
                        ],
                    ],
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ];

            $input[] = [
                'col' => 8,
                'type' => 'select',
                'name' => 'TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_ATTACHMENT',
                'label' => $this->l('Background image css attachment'),
                'desc' => $this->l('Select your "background-attachment" css property. Its value "fixed" and "unset". this option only work with "background-image" not "background-color".'),
                'options' => [
                    'query' => [
                        [
                            'id_option' => 'fixed',
                            'name' => 'Fixed',
                        ],
                        [
                            'id_option' => 'unset',
                            'name' => 'Unset',
                        ],
                    ],
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ];
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Theme Option Status'),
                'name' => 'TVCMSFRONTSIDE_THEME_SETTING_SHOW',
                'desc' => $this->l('Theme option show in front side'),
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
        if ($show_fields['theme_background_design']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Body Background Status'),
                'name' => 'TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS',
                'desc' => $this->l('Theme body background color status'),
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => '1',
                        'label' => $this->l('Show'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => '0',
                        'label' => $this->l('Hide'),
                    ],
                ],
            ];

            $input[] = [
                'type' => 'radio',
                'label' => $this->l('Body Background Theme'),
                'name' => 'TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS',
                'desc' => $this->l('Types of body background styles'),
                'is_bool' => true,
                'class' => 'tvcmsbody-background-type',
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 'color',
                        'label' => $this->l('Color'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 'pattern',
                        'label' => $this->l('Pattern'),
                    ],
                ],
            ];

            $input[] = [
                'col' => 8,
                'type' => 'color',
                'label' => $this->l('Body Background Color'),
                'name' => 'TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR',
            ];

            $input[] = [
                'col' => 8,
                'type' => 'file_upload_4',
                'name' => 'TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN',
                'label' => $this->l('Body Background Pattern'),
                'lang' => true,
            ];

            $input[] = [
                'col' => 8,
                'type' => 'select',
                'name' => 'TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_REPEAT',
                'label' => $this->l('Background Image Css Repeat'),
                'desc' => $this->l('Select Your "background-repeat" css Property. Its value "repeat" and "no-repeat". This Option only work with "background-image" not "background-color".'),
                'options' => [
                    'query' => [
                        [
                            'id_option' => 'repeat',
                            'name' => 'Repeat',
                        ],
                        [
                            'id_option' => 'no-repeat',
                            'name' => 'No Repeat',
                        ],
                    ],
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ];

            $input[] = [
                'col' => 8,
                'type' => 'select',
                'name' => 'TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_ATTACHMENT',
                'label' => $this->l('Background Image Css Attachment'),
                'desc' => $this->l('Select Your "background-attachment" css Property. Its value "fixed" and "unset". This Option only work with "background-image" not "background-color".'),
                'options' => [
                    'query' => [
                        [
                            'id_option' => 'fixed',
                            'name' => 'Fixed',
                        ],
                        [
                            'id_option' => 'unset',
                            'name' => 'Unset',
                        ],
                    ],
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ];
        }

        if ($show_fields['theme_font_design']) {
            $obj = new tvcmsCustomSettingCommonList();
            $fontList = $obj->titleFontList();
            $inputTitleFontList = [];
            $inputTitleFontList[0]['name'] = 'Custom Font';
            $inputTitleFontList[0]['id_option'] = '0';
            $i = 1;
            foreach ($fontList as $font) {
                $inputTitleFontList[$i]['name'] = $font['name'];
                $inputTitleFontList[$i]['id_option'] = $font['name'];
                ++$i;
            }

            $fontList = $obj->bodyFontList();
            $inputBodyFontList = [];
            $inputBodyFontList[0]['name'] = 'Custom Font';
            $inputBodyFontList[0]['id_option'] = '0';
            $i = 1;
            foreach ($fontList as $font) {
                $inputBodyFontList[$i]['name'] = $font['name'];
                $inputBodyFontList[$i]['id_option'] = $font['name'];
                ++$i;
            }

            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Custom Font And Color'),
                'name' => 'TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS',
                'desc' => $this->l('Manage custom font and title color in front side'),
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

            $input[] = [
                'col' => 7,
                'type' => 'select',
                'name' => 'TVCMSCUSTOMSETTING_THEME_FONT_TYPE',
                'label' => $this->l('Title Font'),
                'desc' => $this->l('Select font of front title.'),
                'options' => [
                    'query' => $inputTitleFontList,
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ];

            $input[] = [
                'col' => 8,
                'type' => 'color',
                'label' => $this->l('Title Color'),
                'name' => 'TVCMSCUSTOMSETTING_THEME_FONT_COLOR',
            ];

            $input[] = [
                'col' => 7,
                'type' => 'select',
                'name' => 'TVCMSCUSTOMSETTING_THEME_FONT_TYPE_2',
                'label' => $this->l('Other Font'),
                'desc' => $this->l('Select other font of theme.'),
                'options' => [
                    'query' => $inputBodyFontList,
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ];
        }
        if ($show_fields['dark_mode']) {
            $input[] = [
                'type' => 'dark_mode',
                'label' => $this->l('Themes Mode'),
                'name' => 'TVCMSCUSTOMSETTING_DARK_MODE_INPUT',
                'is_bool' => true,
                'class' => 'tvcmsbackground-type',
                'values' => [
                    [
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Invert Theme'),
                    ],
                    [
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Revert Theme'),
                    ],
                ],
            ];
        }
        if ($show_fields['page_loader']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Page Loader'),
                'name' => 'TVCMSCUSTOMSETTING_PAGE_LOADER',
                'desc' => $this->l('Display page loader in front side'),
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

        if ($show_fields['wow_js']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Wow Js'),
                'name' => 'TVCMSCUSTOMSETTING_WOW_JS',
                'desc' => $this->l('Display Wow Js Effect in Front Side'),
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

        if ($show_fields['mouse_hover_image']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Mouse Hover Image'),
                'name' => 'TVCMSCUSTOMSETTING_HOVER_IMG',
                'desc' => $this->l('Display product\'s second image when mouse hover.'),
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

        if (Module::isInstalled('tvcmstabproducts')) {
            if ($show_fields['tab_product_double_row']) {
                $input[] = [
                    'type' => 'switch',
                    'label' => $this->l('Tab Product Double Row'),
                    'name' => 'TVCMSCUSTOMSETTING_TAB_PRODUCT_ROW',
                    'desc' => $this->l('If true then tab products has double row othewise its show in one row'),
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
        }

        if ($show_fields['float_left_column']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Floating Left Panel  Filter Status'),
                'name' => 'TVCMSCUSTOMSETTING_FILTER_LEFT_PANEL',
                'desc' => $this->l('Manage location of filter in category page'),
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

        if ($show_fields['product_color']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Product Color'),
                'name' => 'TVCMSCUSTOMSETTING_PRODUCT_COLOR',
                'desc' => $this->l('If true then products show color othewise not'),
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

        if ($show_fields['product_list_view']) {
            $input[] = [
                'col' => 7,
                'type' => 'select',
                'name' => 'TVCMSCUSTOMSETTING_PRODUCT_LIST_VIEW',
                'label' => $this->l('Product list view'),
                'desc' => $this->l('Its show default view of product list.'),
                'options' => [
                    'query' => [
                        [
                            'id_option' => 'grid',
                            'name' => 'Grid-View',
                        ],
                        [
                            'id_option' => 'grid-2',
                            'name' => 'Grid-View 2',
                        ],
                        [
                            'id_option' => 'list',
                            'name' => 'List-View',
                        ],
                        [
                            'id_option' => 'list-2',
                            'name' => 'List-View 2',
                        ],
                        [
                            'id_option' => 'catelog',
                            'name' => 'Catelog-View',
                        ],
                    ],
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ];
        }

        if (Module::isInstalled('ps_mainmenu')) {
            if ($show_fields['main_menu_sticky']) {
                $input[] = [
                    'type' => 'switch',
                    'label' => $this->l('Main menu sticky status'),
                    'name' => 'TVCMSCUSTOMSETTING_MAIN_MENU_STICKY',
                    'desc' => $this->l('Manage main menu as sticky of front side'),
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
        }
        if ($show_fields['cart_view']) {
            $input[] = [
                'col' => 7,
                'type' => 'select',
                'name' => 'TVCMSCUSTOMSETTING_CART_VIEW',
                'label' => $this->l('Cart style'),
                'desc' => $this->l('Select cart style.'),
                'options' => [
                    'query' => [
                        [
                            'id_option' => 'classic',
                            'name' => 'Classic',
                        ],
                        [
                            'id_option' => 'pop-up',
                            'name' => 'Pop Up',
                        ],
                    ],
                    'id' => 'id_option',
                    'name' => 'name',
                ],
            ];
        }

        if ($show_fields['tvmain_smart_speed']) {
            $input[] = [
                'col' => 7,
                'type' => 'text',
                'name' => 'TVCMSCUSTOMSETTING_SMART_SPEED',
                'label' => $this->l('Slider Smart Speed'),
                'desc' => $this->l('The duration of the smart speed between two slides.'),
                'suffix' => 'milliseconds',
                'class' => 'fixed-width-sm',
            ];
        }
        if ($show_fields['show_all']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Show in all page supplier list'),
                'name' => 'TVCMSCUSTOMSETTING_SUPPLIER_STATUS',
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
        if ($show_fields['show_all_brand']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Show in all page brand list'),
                'name' => 'TVCMSCUSTOMSETTING_BRAND_STATUS',
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

        if ($show_fields['left_sticky']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Left bar sticky status'),
                'name' => 'TVCMSCUSTOMSETTING_LEFT_STICKY_STATUS',
                'desc' => 'Status of left sticky bar',
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

        if ($show_fields['prod_bottom_sticky']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Product page bottom bar sticky status'),
                'name' => 'TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS',
                'desc' => 'Status of Product page bottom sticky bar',
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

        if ($show_fields['right_sticky']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Right bar sticky status'),
                'name' => 'TVCMSCUSTOMSETTING_RIGHT_STICKY_STATUS',
                'desc' => 'Status of right sticky bar',
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
        if ($show_fields['home_pagecategory_show']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Show home page category list'),
                'name' => 'TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS',
                'desc' => 'Note: Yes status means show in all page, no means show in only inner page',
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
        if ($show_fields['bottom_sticky']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Bottom Option'),
                'name' => 'TVCMSCUSTOMSETTING_BOTTOM_OPTION',
                'desc' => $this->l('Display Bottom Option of Front Side'),
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

        if (Module::isInstalled('tvcmsverticalmenu')) {
            if ($show_fields['vertical_menu_open']) {
                $input[] = [
                    'type' => 'switch',
                    'label' => $this->l('Vertical Menu Open'),
                    'name' => 'TVCMSCUSTOMSETTING_VERTICAL_MENU_OPEN',
                    'desc' => $this->l('Vertical Menu is Open Default in Home Page'),
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
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Theme Option'),
                    'icon' => 'icon-cogs',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsThemeOptionForm',
                ],
            ],
        ];
    }

    // App Link Form
    protected function tvcmsAppLinkForm()
    {
        $tvcms_obj = new TvcmsCustomSettingStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        if ($show_fields['app_main_image']) {
            $input[] = [
                'col' => 8,
                'type' => 'file_upload',
                'name' => 'TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE',
                'label' => $this->l('App link image'),
            ];
        }

        if ($show_fields['app_title']) {
            $input[] = [
                'col' => 8,
                'type' => 'text',
                'name' => 'TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE',
                'label' => $this->l('App link title'),
                'lang' => true,
                'desc' => $this->l('Display title of all app link in front side'),
            ];
        }

        if ($show_fields['app_sub_title']) {
            $input[] = [
                'col' => 8,
                'type' => 'text',
                'name' => 'TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE',
                'label' => $this->l('App link title'),
                'lang' => true,
                'desc' => $this->l('Display sub-title of all app link in front side'),
            ];
        }

        if ($show_fields['app_desc']) {
            $input[] = [
                'col' => 8,
                'type' => 'text',
                'name' => 'TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC',
                'label' => $this->l('App link description'),
                'lang' => true,
                'desc' => $this->l('Manage description of all app link in front side'),
            ];
        }

        if ($show_fields['apple_app_link']) {
            $input[] = [
                'col' => 8,
                'type' => 'text',
                'name' => 'TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE',
                'label' => $this->l('Apple app link'),
                'lang' => true,
                'desc' => $this->l('Manage apple app in Front Side'),
            ];
        }

        if ($show_fields['google_app_link']) {
            $input[] = [
                'col' => 8,
                'type' => 'text',
                'name' => 'TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE',
                'label' => $this->l('Google App Link'),
                'lang' => true,
                'desc' => $this->l('Manage google link in front side'),
            ];
        }

        if ($show_fields['microsoft_app_link']) {
            $input[] = [
                'col' => 8,
                'type' => 'text',
                'name' => 'TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT',
                'label' => $this->l('Microsoft App Link'),
                'lang' => true,
                'desc' => $this->l('Manage microsoft link in front side'),
            ];
        }

        if ($show_fields['app_link_status']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Status'),
                'name' => 'TVCMSCUSTOMSETTING_DOWNLOAD_APPS_STATUS',
                'desc' => $this->l('Status of app link in front side'),
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
                    'title' => $this->l('App Link'),
                    'icon' => 'icon-cloud-upload',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsAppLinkForm',
                ],
            ],
        ];
    }

    // App Link Form
    protected function tvcmsFooterProductForm()
    {
        $tvcms_obj = new TvcmsCustomSettingStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        // This is Copyright information
        if ($show_fields['copy_right_info']) {
            if ($show_fields['custom_text']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'textarea',
                    'name' => 'TVCMSCUSTOMSETTING_CUSTOM_TEXT',
                    'label' => $this->l('Custom Text'),
                    'lang' => true,
                    'desc' => $this->l('Display custom text in front side'),
                    'cols' => 40,
                    'rows' => 10,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ];
            }

            if ($show_fields['custom_text_status']) {
                $input[] = [
                    'type' => 'switch',
                    'label' => $this->l('Custom Text Status'),
                    'name' => 'TVCMSCUSTOMSETTING_CUSTOM_TEXT_STATUS',
                    'desc' => $this->l('Manage status for custom text'),
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

            if ($show_fields['copy_right_text']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT',
                    'label' => $this->l('Copy right text'),
                    'lang' => true,
                    'desc' => $this->l('Manage display copy right text in front side'),
                ];
            }

            if ($show_fields['place_holder_search']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT',
                    'label' => $this->l('Search Placeholder'),
                    'lang' => true,
                    'desc' => $this->l('Note: Please Enter Search bar Placeholder'),
                ];
            }

            if ($show_fields['copy_right_link']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMSETTING_COPY_RIGHT_LINK',
                    'label' => $this->l('Copy Right Link'),
                    'lang' => true,
                    'desc' => $this->l('Manage display copy right link in front side'),
                ];
            }

            if ($show_fields['copy_right_text_status']) {
                $input[] = [
                    'type' => 'switch',
                    'label' => $this->l('Copy Right Text Status'),
                    'name' => 'TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT_STATUS',
                    'desc' => $this->l('Status for copy right text'),
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
            if ($show_fields['rtl_text_status']) {
                $input[] = [
                    'type' => 'switch',
                    'label' => $this->l('Arabic font Text Status'),
                    'name' => 'TVCMSCUSTOMSETTING_RTL_TEXT_STATUS',
                    'desc' => $this->l('Status of arabic font in RTL language'),
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
        }

        // This is Footer tab Information
        if ($show_fields['footer_tab_product_info']) {
            if ($show_fields['footer_tab_featured_prod_title']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMSETTING_FOOTER_TAB_FEATURED_PROD_TITLE',
                    'label' => $this->l('Featured product title'),
                    'desc' => $this->l('Manage display testimonial title in from side'),
                    'lang' => true,
                ];
            }

            if ($show_fields['footer_tab_new_prod_title']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMSETTING_FOOTER_TAB_NEW_PROD_TITLE',
                    'label' => $this->l('New product title'),
                    'desc' => $this->l('Manage display testimonial title in from side'),
                    'lang' => true,
                ];
            }

            if ($show_fields['footer_tab_best_seller_prod_title']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMSETTING_FOOTER_TAB_BEST_SELLER_PROD_TITLE',
                    'label' => $this->l('Best seller product title'),
                    'desc' => $this->l('Manage best seller product title in From Side'),
                    'lang' => true,
                ];
            }

            if ($show_fields['footer_tab_num_prod']) {
                $input[] = [
                    'type' => 'select',
                    'label' => $this->l('Number of product'),
                    'desc' => $this->l('Number of product which show in footer tab'),
                    'name' => 'TVCMSCUSTOMSETTING_FOOTER_TAB_NUM_PROD',
                    'options' => [
                        'query' => [
                            [
                                'id_option' => 1,
                                'name' => '1',
                            ],
                            [
                                'id_option' => 2,
                                'name' => '2',
                            ],
                            [
                                'id_option' => 3,
                                'name' => '3',
                            ],
                            [
                                'id_option' => 4,
                                'name' => '4',
                            ],
                            [
                                'id_option' => 5,
                                'name' => '5',
                            ],
                        ],
                        'id' => 'id_option',
                        'name' => 'name',
                    ],
                ];
            }

            if ($show_fields['footer_tab_prod_status']) {
                $input[] = [
                    'type' => 'switch',
                    'label' => $this->l('Footer tab produts status'),
                    'name' => 'TVCMSCUSTOMSETTING_FOOTER_TAB_STATUS',
                    'desc' => $this->l('Show footer tab product in home page'),
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
        }

        if (Module::isInstalled('ps_emailsubscription')) {
            if ($show_fields['news_letter_title']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMSETTING_NEWSLETTER_TITLE',
                    'label' => $this->l('Newsletter title'),
                    'desc' => $this->l('Display newsletter title in from side'),
                    'lang' => true,
                ];
            }

            if ($show_fields['news_letter_short_desc']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC',
                    'label' => $this->l('Newsletter short description'),
                    'desc' => $this->l('Display newsletter description in from side'),
                    'lang' => true,
                ];
            }
        }

        if (Module::isInstalled('ps_socialfollow')) {
            if ($show_fields['social_icon_title']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE',
                    'label' => $this->l('Social icon title'),
                    'desc' => $this->l('Display social icon title in from side'),
                    'lang' => true,
                ];
            }

            if ($show_fields['social_icon_short_desc']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'text',
                    'name' => 'TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC',
                    'label' => $this->l('Social icon short description'),
                    'desc' => $this->l('Display social icon description in from side'),
                    'lang' => true,
                ];
            }
        }
        if ($show_fields['footer_img']) {
            $input[] = [
                'col' => 8,
                'type' => 'footer_img',
                'name' => 'TVCMSCUSTOMSETTING_FOOTER_IMAGE',
                'label' => $this->l('Footer background image'),
            ];
        }
        if ($show_fields['footer_img_status']) {
            $input[] = [
                'type' => 'switch',
                'label' => $this->l('Footer background image status'),
                'name' => 'TVCMSCUSTOMSETTING_FOOTER_IMG_STATUS',
                'desc' => $this->l('Manage footer background image status'),
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
                    'title' => $this->l('Custom Titles'),
                    'icon' => 'icon-cloud-upload',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsCustomTitleForm',
                ],
            ],
        ];
    }

    protected function tvcmsCustomLayoutForm()
    {
        $tvcms_obj = new TvcmsCustomSettingStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        // This is Copyright information
        if ($show_fields['main_form']) {
            if ($show_fields['header_layout']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'header_desktop_layout_radio',
                    'name' => 'TVCMSHEADERCUSTOMLAYOUT',
                    'label' => $this->l('Header Layout\'s'),
                ];
            }
            if ($show_fields['header_layout_mobile']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'header_mobile_layout_radio',
                    'name' => 'TVCMSHEADERCUSTOMLAYOUT_MOBILE',
                    'label' => $this->l('Mobile Header Layout\'s'),
                ];
            }
            if ($show_fields['header_product_layout_radio']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'header_product_layout_radio',
                    'name' => 'TVCMSPRODUCTCUSTOM_LAYOUT',
                    'label' => $this->l('Product Page Layout\'s'),
                ];
            }
            if ($show_fields['footer_layout_radio']) {
                $input[] = [
                    'col' => 8,
                    'type' => 'footer_layout_radio',
                    'name' => 'TVCMSFOOTERCUSTOMLAYOUT',
                    'label' => $this->l('Footer Layout\'s'),
                ];
            }
            if ($show_fields['mobile_search']) {
                $input[] = [
                    'type' => 'switch',
                    'label' => $this->l('Category Banner'),
                    'name' => 'TVCMSCAT_BANNER_STATUS',
                    'desc' => $this->l('Note: If selected yes shown in Top of category else shown bottom of the category'),
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
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('Custom Layout Configuration'),
                    'icon' => 'icon-wrench',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsCustomLayoutForm',
                ],
            ],
        ];
    }

    protected function tvcmsInstallDataForm()
    {
        $tvcms_obj = new TvcmsCustomSettingStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        // This is Copyright information
        $input[] = [
            'col' => 12,
            'type' => 'tvcmsInstallDataForm',
            'name' => 'TVCMSINSTALLDATAFORM',
            'label' => '',
        ];

        return [
            'form' => [
            'legend' => [
                'title' => $this->l('Import Sample Data'),
                'icon' => 'icon-wrench',
            ],
            'input' => $input,
            'submit' => [
                'title' => $this->l('Upgrade Database'),
                'class' => 'btn btn-primary',
                'name' => 'submitTvcmsUpgradeThemeForm',
                'icon' => 'icon-wrench',
            ],
            ],
           ];
    }

    protected function getConfigFormValues()
    {
        $fields = [];
        $languages = Language::getLanguages();
        $path = _MODULE_DIR_ . $this->name . '/views/img/';

        foreach ($languages as $lang) {
            $a = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_CUSTOM_TEXT', $lang['id_lang'], true);
            $fields['TVCMSCUSTOMSETTING_CUSTOM_TEXT'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_SEARCH_PLACEHOLDER_TEXT'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_COPY_RIGHT_LINK', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_COPY_RIGHT_LINK'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_TAB_FEATURED_PROD_TITLE', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_FOOTER_TAB_FEATURED_PROD_TITLE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_TAB_NEW_PROD_TITLE', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_FOOTER_TAB_NEW_PROD_TITLE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_TAB_BEST_SELLER_PROD_TITLE', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_FOOTER_TAB_BEST_SELLER_PROD_TITLE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_NEWSLETTER_TITLE', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_NEWSLETTER_TITLE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_NEWSLETTER_SHORT_DESC'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_SOCIAL_ICON_TITLE'][$lang['id_lang']] = $a;

            $a = Configuration::get('TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC', $lang['id_lang']);
            $fields['TVCMSCUSTOMSETTING_SOCIAL_ICON_SHORT_DESC'][$lang['id_lang']] = $a;
        }

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE');
        $fields['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_IMAGE');
        $fields['TVCMSCUSTOMSETTING_FOOTER_IMAGE'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_STATUS');
        $fields['TVCMSCUSTOMSETTING_DOWNLOAD_APPS_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_CUSTOM_TEXT_STATUS');
        $fields['TVCMSCUSTOMSETTING_CUSTOM_TEXT_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT_STATUS');
        $fields['TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_RTL_TEXT_STATUS');
        $fields['TVCMSCUSTOMSETTING_RTL_TEXT_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_IMG_STATUS');
        $fields['TVCMSCUSTOMSETTING_FOOTER_IMG_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_MAIN_MENU_STICKY');
        $fields['TVCMSCUSTOMSETTING_MAIN_MENU_STICKY'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_SUPPLIER_STATUS');
        $fields['TVCMSCUSTOMSETTING_SUPPLIER_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BRAND_STATUS');
        $fields['TVCMSCUSTOMSETTING_BRAND_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_RIGHT_STICKY_STATUS');
        $fields['TVCMSCUSTOMSETTING_RIGHT_STICKY_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS');
        $fields['TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_LEFT_STICKY_STATUS');
        $fields['TVCMSCUSTOMSETTING_LEFT_STICKY_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS');
        $fields['TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BOTTOM_OPTION');
        $fields['TVCMSCUSTOMSETTING_BOTTOM_OPTION'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_VERTICAL_MENU_OPEN');
        $fields['TVCMSCUSTOMSETTING_VERTICAL_MENU_OPEN'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_TAB_NUM_PROD');
        $fields['TVCMSCUSTOMSETTING_FOOTER_TAB_NUM_PROD'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_TAB_STATUS');
        $fields['TVCMSCUSTOMSETTING_FOOTER_TAB_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_ADD_CONTAINER');
        $fields['TVCMSCUSTOMSETTING_ADD_CONTAINER'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS');
        $fields['TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS');
        $fields['TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_PATTERN_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR');
        $fields['TVCMSCUSTOMSETTING_BODY_BACKGROUND_COLOR'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN');
        $fields['TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN'] = $tmp;
        $this->context->smarty->assign('body_background_pattern', $tmp);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_REPEAT');
        $fields['TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_REPEAT'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_ATTACHMENT');
        $fields['TVCMSCUSTOMSETTING_BODY_BACKGROUND_IMAGE_ATTACHMENT'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS');
        $fields['TVCMSCUSTOMSETTING_CUSTOM_FONT_TITLE_COLOR_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE');
        $fields['TVCMSCUSTOMSETTING_THEME_FONT_TYPE'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_COLOR');
        $fields['TVCMSCUSTOMSETTING_THEME_FONT_COLOR'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_THEME_FONT_TYPE_2');
        $fields['TVCMSCUSTOMSETTING_THEME_FONT_TYPE_2'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_PAGE_LOADER');
        $fields['TVCMSCUSTOMSETTING_PAGE_LOADER'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_WOW_JS');
        $fields['TVCMSCUSTOMSETTING_WOW_JS'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_HOVER_IMG');
        $fields['TVCMSCUSTOMSETTING_HOVER_IMG'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_TAB_PRODUCT_ROW');
        $fields['TVCMSCUSTOMSETTING_TAB_PRODUCT_ROW'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_DARK_MODE_INPUT');
        $fields['TVCMSCUSTOMSETTING_DARK_MODE_INPUT'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_FILTER_LEFT_PANEL');
        $fields['TVCMSCUSTOMSETTING_FILTER_LEFT_PANEL'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_COLOR');
        $fields['TVCMSCUSTOMSETTING_PRODUCT_COLOR'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_LIST_VIEW');
        $fields['TVCMSCUSTOMSETTING_PRODUCT_LIST_VIEW'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_CART_VIEW');
        $fields['TVCMSCUSTOMSETTING_CART_VIEW'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_SMART_SPEED');
        $fields['TVCMSCUSTOMSETTING_SMART_SPEED'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_THEME_OPTION');
        $fields['TVCMSCUSTOMSETTING_THEME_OPTION'] = $tmp;

        $tmp = Configuration::get('TVCMSHEADERCUSTOMLAYOUT');
        $fields['TVCMSHEADERCUSTOMLAYOUT'] = $tmp;

        $tmp = Configuration::get('TVCMSHEADERCUSTOMLAYOUT_MOBILE');
        $fields['TVCMSHEADERCUSTOMLAYOUT_MOBILE'] = $tmp;

        $tmp = Configuration::get('TVCMSFOOTERCUSTOMLAYOUT');
        $fields['TVCMSFOOTERCUSTOMLAYOUT'] = $tmp;

        $tmp = Configuration::get('TVCMSPRODUCTCUSTOM_LAYOUT');
        $fields['TVCMSPRODUCTCUSTOM_LAYOUT'] = $tmp;

        $tmp = Configuration::get('TVCMSCAT_BANNER_STATUS');
        $fields['TVCMSCAT_BANNER_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSFRONTSIDE_THEME_SETTING_SHOW');
        $fields['TVCMSFRONTSIDE_THEME_SETTING_SHOW'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_THEME_COLOR_1');
        $fields['TVCMSCUSTOMSETTING_THEME_COLOR_1'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_THEME_COLOR_2');
        $fields['TVCMSCUSTOMSETTING_THEME_COLOR_2'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_COLOR');
        $fields['TVCMSCUSTOMSETTING_BACKGROUND_COLOR'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_REPEAT');
        $fields['TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_REPEAT'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_ATTACHMENT');
        $fields['TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_ATTACHMENT'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_PATTERN');
        $fields['TVCMSCUSTOMSETTING_BACKGROUND_PATTERN'] = $tmp;
        $this->context->smarty->assign('background_pattern', $tmp);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN');
        $fields['TVCMSCUSTOMSETTING_BODY_BACKGROUND_PATTERN'] = $tmp;
        $this->context->smarty->assign('body_background_pattern', $tmp);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS');
        $fields['TVCMSCUSTOMSETTING_BACKGROUND_IMAGE_PATTERN_STATUS'] = $tmp;

        $tmp = Configuration::get('TVCMSFRONTSIDE_THEME_SETTING_SHOW');
        $fields['TVCMSFRONTSIDE_THEME_SETTING_SHOW'] = $tmp;

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BACKGROUND_OLD_PATTERN');
        $this->context->smarty->assign('custom_pattern', $tmp);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BODY_BACKGROUND_OLD_PATTERN');
        $this->context->smarty->assign('custom_body_pattern', $tmp);

        $this->context->smarty->assign('front_pattern_path', _THEME_IMG_DIR_);
        $this->context->smarty->assign('path', $path);

        $tvcms_obj = new TvcmsCustomSettingStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $fields['header_layout_list'] = $show_fields['header_layout_list'];
        $fields['footer_layout_list'] = $show_fields['footer_layout_list'];
        $fields['product_layout_list'] = $show_fields['product_layout_list'];
        $fields['mob_header_layout_list'] = $show_fields['mob_header_layout_list'];
        $fields['layout_img_path'] = _THEME_IMG_DIR_;

        return $fields;
    }

    public function hookDisplayBackOfficeHeader()
    {
        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);
        if (Tools::version_compare(_PS_VERSION_, '1.7.7', '<')) {
            $this->context->controller->addJS(_PS_JS_DIR_ . 'jquery/jquery-1.11.0.min.js');
        }
        $this->context->controller->addJQueryUI('ui.sortable');
        $this->context->controller->addjqueryPlugin('fancybox');
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    public function hookdisplayTopOfferText()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $html = '';

        if (!Cache::isStored('tvcmscustomsetting_displaytopoffertext')) {
            if (Configuration::get('TVCMSCUSTOMSETTING_CUSTOM_TEXT_STATUS')) {
                $html .= '<div class="tvheader-nav-offer-text"><i class=\'material-icons\'>&#xe8d0;</i>' . Configuration::get('TVCMSCUSTOMSETTING_CUSTOM_TEXT', $id_lang, true) . '</div>';
            }
            $output = $html;
            Cache::store('tvcmscustomsetting_displaytopoffertext', $output);
        }

        return Cache::retrieve('tvcmscustomsetting_displaytopoffertext');
    }

    public function hookdisplayNav1()
    {
        return $this->hookdisplayTopOfferText();
    }

    public function hookdisplayNav1sub1()
    {
        return $this->hookdisplayTopOfferText();
    }

    public function hookdisplayMobileTopOfferText()
    {
        return $this->hookdisplayTopOfferText();
    }

    public function hookdisplayHome()
    {
        return $this->hookdisplayDownloadApps();
    }

    public function hookdisplayDownloadApps()
    {
        $html = '';
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        if (!Cache::isStored('tvcmscustomsetting_display_download_app.tpl')) {
            if (Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_STATUS')) {
                $path = _MODULE_DIR_ . $this->name . '/views/img/';
                $tvcms_obj = new TvcmsCustomSettingStatus();
                $show_fields = $tvcms_obj->fieldStatusInformation();
                $data = [];

                $data['link_image'] = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_IMAGE');
                $data['link_title'] = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_TITLE', $id_lang);
                $data['link_sub_title'] = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_SUB_TITLE', $id_lang);
                $data['link_desc'] = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_DESC', $id_lang);
                $data['apple_link'] = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_APPLE', $id_lang);
                $data['google_link'] = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_GOOLGE', $id_lang);
                $data['microsoft_link'] = Configuration::get('TVCMSCUSTOMSETTING_DOWNLOAD_APPS_MICROSOFT', $id_lang);
                $this->context->smarty->assign('data', $data);
                $this->context->smarty->assign('path', $path);
                $this->context->smarty->assign('show_fields', $show_fields);
                $output = $this->display(__FILE__, 'views/templates/front/display_download_app.tpl');
            } else {
                $output = '';
            }
            Cache::store('tvcmscustomsetting_display_download_app.tpl', $output);
        }

        return Cache::retrieve('tvcmscustomsetting_display_download_app.tpl');
    }

    public function hookdisplayCopyRightText()
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        if (!Cache::isStored('tvcmscustomsetting_display_copy_right_text.tpl')) {
            if (Configuration::get('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT_STATUS') && Configuration::get('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT', $id_lang)) {
                $tmp = Configuration::get('TVCMSCUSTOMSETTING_COPY_RIGHT_TEXT', $id_lang);
                $this->context->smarty->assign('copy_right_text', $tmp);

                $tmp = Configuration::get('TVCMSCUSTOMSETTING_COPY_RIGHT_LINK', $id_lang);
                $this->context->smarty->assign('copy_right_link', $tmp);
                $output = $this->display(__FILE__, 'views/templates/front/display_copy_right_text.tpl');
            } else {
                $output = '';
            }
            Cache::store('tvcmscustomsetting_display_copy_right_text.tpl', $output);
        }

        return Cache::retrieve('tvcmscustomsetting_display_copy_right_text.tpl');
    }

    public function hookdisplayCustomsocialblock()
    {
        if (!Cache::isStored('tvcmscustomsetting_display_custom_social.tpl')) {
            if (Module::isEnabled('tvcmswishlist')) {
                $WishListEnabledStatus = 1;
                $this->context->smarty->assign('WishListEnabledStatus', $WishListEnabledStatus);
            } else {
                $WishListEnabledStatus = 0;
                $this->context->smarty->assign('WishListEnabledStatus', $WishListEnabledStatus);
            }
            if (Module::isEnabled('tvcmsproductcompare')) {
                $CompareEnabledStatus = 1;
                $this->context->smarty->assign('CompareEnabledStatus', $CompareEnabledStatus);
            } else {
                $CompareEnabledStatus = 0;
                $this->context->smarty->assign('CompareEnabledStatus', $CompareEnabledStatus);
            }
            $RightStickyStatus = Configuration::get('TVCMSCUSTOMSETTING_RIGHT_STICKY_STATUS');
            $this->context->smarty->assign('RightStickyStatus', $RightStickyStatus);

            $LeftStickyStatus = Configuration::get('TVCMSCUSTOMSETTING_LEFT_STICKY_STATUS');
            $this->context->smarty->assign('LeftStickyStatus', $LeftStickyStatus);
            $output = $this->display(__FILE__, 'views/templates/front/display_custom_social.tpl');
            Cache::store('tvcmscustomsetting_display_custom_social.tpl', $output);
        }

        return Cache::retrieve('tvcmscustomsetting_display_custom_social.tpl');
    }

    public function hookdisplayHeader()
    {
        $assignVarPage = $this->context->controller->php_self;
        $this->context->smarty->assign('assignVarPage', $assignVarPage);
        $colors_1 = Configuration::get('TVCMSCUSTOMSETTING_THEME_COLOR_1');
        $colors_text_1 = $this->getContrastColor($colors_1);
        $colors_2 = Configuration::get('TVCMSCUSTOMSETTING_THEME_COLOR_2');
        $colors_text_2 = $this->getContrastColor($colors_2);
        $tvMainSmartSpeed = Configuration::get('TVCMSCUSTOMSETTING_SMART_SPEED');
        Media::addJsDef(['tvMainSmartSpeed' => $tvMainSmartSpeed]);
        // layout
        $get_hl = Tools::getValue('hl');
        $get_mhl = Tools::getValue('mhl');
        $get_fl = Tools::getValue('fl');
        $get_pdl = Tools::getValue('pdl');

        $hl = Configuration::get('TVCMSHEADERCUSTOMLAYOUT');
        $this->context->smarty->assign('TVCMSHEADERCUSTOMLAYOUT', $hl);
        if (!empty($get_hl) && is_numeric($get_hl)) {
            $this->context->smarty->assign('TVCMSHEADERCUSTOMLAYOUT', 'desk-header-' . $get_hl);
        }
        $mhl = Configuration::get('TVCMSHEADERCUSTOMLAYOUT_MOBILE');
        $this->context->smarty->assign('TVCMSHEADERCUSTOMLAYOUT_MOBILE', $mhl);
        if (!empty($get_mhl) && is_numeric($get_mhl)) {
            $this->context->smarty->assign('TVCMSHEADERCUSTOMLAYOUT_MOBILE', 'mobile-header-' . $get_mhl);
        }
        $fl = Configuration::get('TVCMSFOOTERCUSTOMLAYOUT');
        $this->context->smarty->assign('TVCMSFOOTERCUSTOMLAYOUT', $fl);
        if (!empty($get_fl) && is_numeric($get_fl)) {
            $this->context->smarty->assign('TVCMSFOOTERCUSTOMLAYOUT', 'footer-' . $get_fl);
        }
        $pdl = Configuration::get('TVCMSPRODUCTCUSTOM_LAYOUT');
        $this->context->smarty->assign('TVCMSPRODUCTCUSTOM_LAYOUT', $pdl);
        if (!empty($get_pdl) && is_numeric($get_pdl)) {
            $this->context->smarty->assign('TVCMSPRODUCTCUSTOM_LAYOUT', 'product-' . $get_pdl);
        }

        // color contrast
        if ('#FFFFFF' == $colors_text_1) {
            $res_1 = 'text1-light';
        } else {
            $res_1 = 'text1-dark';
        }
        if ('#FFFFFF' == $colors_text_2) {
            $res_2 = 'text2-light';
        } else {
            $res_2 = 'text2-dark';
        }
        $this->context->smarty->assign('res_1', $res_1);
        $this->context->smarty->assign('res_2', $res_2);

        $this->context->controller->addjqueryPlugin('fancybox'); // blog module
        $this->context->controller->addJS($this->_path . 'views/js/owl.js');
        $this->context->controller->addJS($this->_path . 'views/js/slick.min.js');
        // $this->context->controller->addJS($this->_path.'views/js/sticksy.min.js');
        $this->context->controller->addJS($this->_path . 'views/js/jquery.storageapi.min.js');
        $this->context->controller->addJS($this->_path . 'views/js/jquery.balance.js');
        $this->context->controller->addJS($this->_path . 'views/js/resize-sensor.min.js');
        $this->context->controller->addJS($this->_path . 'views/js/theia-sticky-sidebar.min.js');
        // $this->context->controller->addJS($this->_path.'views/js/jquery.lazy.min.js');
        $this->context->controller->addJS($this->_path . 'views/js/jquery.elevatezoom.min.js');
        $this->context->controller->addJS($this->_path . 'views/js/jquery.countdown.min.js');
        // $this->context->controller->addJS($this->_path.'views/js/front.js');
        // $this->context->controller->addCSS($this->_path.'views/css/front.css');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        $this->context->controller->addCSS($this->_path . 'views/css/slick-theme.min.css');

        $tvcms_setting = true;
        Media::addJsDef(['tvcms_setting' => $tvcms_setting]);

        $paths = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('paths', $paths);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_WOW_JS');
        Media::addJsDef(['TVCMSCUSTOMSETTING_WOW_JS' => $tmp]);

        $cookie = Context::getContext()->cookie;
        $iso_code_country = $cookie->iso_code_country;
        $tmp = Configuration::get('TVCMSCUSTOMSETTING_iso_code_country');
        Media::addJsDef(['TVCMSCUSTOMSETTING_iso_code_country' => $iso_code_country]);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_HOVER_IMG');
        Media::addJsDef(['TVCMSCUSTOMSETTING_HOVER_IMG' => $tmp]);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_MAIN_MENU_STICKY');
        Media::addJsDef(['TVCMSCUSTOMSETTING_MAIN_MENU_STICKY' => $tmp]);

        $GetStatusSupplier = Configuration::get('TVCMSCUSTOMSETTING_SUPPLIER_STATUS');
        $this->context->smarty->assign('GetStatusSupplier', $GetStatusSupplier);

        $ArabicFontStatus = Configuration::get('TVCMSCUSTOMSETTING_RTL_TEXT_STATUS');
        $this->context->smarty->assign('ArabicFontStatus', $ArabicFontStatus);

        $FooterImageStatus = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_IMG_STATUS');
        $this->context->smarty->assign('FooterImageStatus', $FooterImageStatus);

        $FooterBackgroundImage = Configuration::get('TVCMSCUSTOMSETTING_FOOTER_IMAGE');
        $this->context->smarty->assign('FooterBackgroundImage', $FooterBackgroundImage);

        $GetStatusBrand = Configuration::get('TVCMSCUSTOMSETTING_BRAND_STATUS');
        $this->context->smarty->assign('GetStatusBrand', $GetStatusBrand);

        $CategoryListHomePageStatus = Configuration::get('TVCMSCUSTOMSETTING_CATEGORY_TREE_STATUS');
        $this->context->smarty->assign('CategoryListHomePageStatus', $CategoryListHomePageStatus);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_BOTTOM_OPTION');
        Media::addJsDef(['TVCMSCUSTOMSETTING_BOTTOM_OPTION' => $tmp]);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_DARK_MODE_INPUT');
        Media::addJsDef(['TVCMSCUSTOMSETTING_DARK_MODE_INPUT' => $tmp]);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS');
        Media::addJsDef(['TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS' => $tmp]);

        $tmp = Configuration::get('TVCMSFRONTSIDE_THEME_SETTING_SHOW');
        Media::addJsDef(['TVCMSFRONTSIDE_THEME_SETTING_SHOW' => $tmp]);

        $tmp = Configuration::get('TVCMSCUSTOMSETTING_VERTICAL_MENU_OPEN');
        Media::addJsDef(['TVCMSCUSTOMSETTING_VERTICAL_MENU_OPEN' => $tmp]);

        $useSSL = (isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED')) || Tools::usingSecureMode() ? true : false;
        $protocol_content = $useSSL ? 'https://' : 'http://';
        $baseDir = $protocol_content . Tools::getHttpHost() . __PS_BASE_URI__;
        Media::addJsDef(['baseDir' => $baseDir]);

        $static_token = Tools::getToken(false);
        Media::addJsDef(['static_token' => $static_token]);
    }
}
