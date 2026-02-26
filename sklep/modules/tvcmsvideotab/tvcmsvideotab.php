<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
class TvcmsVideoTab extends Module
{
    public $demoMode = false;

    public function __construct()
    {
        $this->name = 'tvcmsvideotab';
        $this->tab = 'front_office_features';
        $this->version = '4.0.4';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->displayName = $this->l('ThemeVolty - Product Video Tab');
        $this->description = $this->l('Display video on Product Detail, Work on Desktop, Tablet, Mobile');
        parent::__construct();
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
            $tab->name[$lang['id_lang']] = 'Product Video';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    public function install()
    {
        $this->installTab();
        $url = _PS_ROOT_DIR_ . '/media';
        if (!file_exists($url)) {
            @mkdir($url, 0777, true);
        }
        $logMedia = '';
        $outputFlie = _PS_ROOT_DIR_ . '/media/index.php';
        file_put_contents($outputFlie, $logMedia, FILE_APPEND);
        if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
            $url_cover = _PS_THEME_DIR_ . 'templates/catalog/_partials/product-cover-thumbnails.tpl';
            $fileContents = Tools::file_get_contents($url_cover);
            $replacement = '{hook h="displayAfterProductVideoPopup"}';
            if (false == strpos($fileContents, $replacement)) {
                $contents = file($url_cover, FILE_IGNORE_NEW_LINES);
                $specific_line = sizeof($contents) + 1;
                array_splice($contents, $specific_line - 1, 0, [$replacement]);
                $contents = implode("\n", $contents);
                file_put_contents($url_cover, $contents);
            }
        } else {
            $url_cover_16 = _PS_THEME_DIR_ . 'product.tpl';
            $fileContents16 = Tools::file_get_contents($url_cover_16);
            $replacement16 = '{hook h="displayProductTabVideo"}';
            $str = '<!-- end thumbnails -->';
            $lineNumber = '';
            $line = file($url_cover_16);
            foreach ($line as $key => $v) {
                if (false !== strpos($v, $str)) {
                    $lineNumber = $key + 1;
                }
            }
            if (false == strpos($fileContents16, $replacement16)) {
                // str_replace($str,'<!-- end thumbnails -->' .  $replacement16,$fileContents16);
                $contents16 = file($url_cover_16, FILE_IGNORE_NEW_LINES);
                $specific_line16 = $lineNumber + 1;
                array_splice($contents16, $specific_line16 - 1, 0, [
                    $replacement16,
                ]);
                $contents16 = implode("\n", $contents16);
                file_put_contents($url_cover_16, $contents16);
            }
        }
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $sql = 'CREATE TABLE IF NOT EXISTS ' . _DB_PREFIX_ . 'url_video(
            id_video int(11) AUTO_INCREMENT,
            id_product int(11) NOT NULL,
            id_store int(11) NOT NULL,
            id_lang int(11) NOT NULL,
            text_url text  NULL,
            language text  NULL,
            shop text  NULL,
            name_product VARCHAR(255) NULL,
            type int(11)  NULL,
            PRIMARY KEY(id_video))';
        $db->query($sql);
        if (false == parent::install()) {
            return false;
        }

        $list_id_shop = Shop::getCompleteListOfShopsID();
        foreach ($list_id_shop as $key => $value) {
            Configuration::updateValue('productab', '1', false, '', $list_id_shop[$key]);
            Configuration::updateValue('producpopup', '1', false, '', $list_id_shop[$key]);
            Configuration::updateValue('videoextension', 'mp4, webm, ogg, 3gp, flv', false, '', $list_id_shop[$key]);
        }
        $this->registerHook('displayAdminProductsExtra');
        $this->registerHook('displayProductTabContent');
        $this->registerHook('displayAfterProductVideoPopup');
        $this->registerHook('displayProductTabVideo');
        $this->registerHook('displayBackofficeTop');
        $this->registerHook('displayProductExtraContent');
        $this->registerHook('displayTop');

        return true;
    }

    public function uninstall()
    {
        $this->uninstallTab();
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $sql = 'DROP TABLE IF EXISTS ' . _DB_PREFIX_ . 'url_video';
        $db->query($sql);
        if (false == parent::uninstall()) {
            return false;
        }

        return true;
    }

    public function hookdisplayAdminProductsExtra($params)
    {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $html = '';
        $id_shop = (int) $this->context->shop->id;
        $sql5 = 'SELECT * FROM ' . _DB_PREFIX_ . "shop WHERE id_shop='" . (int) $id_shop . "'";
        $name_shop_array = $db->ExecuteS($sql5);
        $name_shop = $name_shop_array[0]['name'];
        $languages = Language::getLanguages();
        $id_language = Context::getContext()->language->id;
        foreach ($languages as $key => $value) {
            $languages[$key]['checked'] = 0;
            if ($id_language == $value['id_lang']) {
                $languages[$key]['checked'] = 1;
            }
        }
        $this->context->smarty->assign('languages', $languages);
        $videoextension = Configuration::get('videoextension', null, '', $id_shop);
        $this->context->smarty->assign('videotype', $videoextension);
        $url = Tools::getShopProtocol() . Tools::getServerName() . __PS_BASE_URI__;
        $this->context->smarty->assign('url', $url);
        $link = new Link();
        $url1 = $_SERVER['PHP_SELF'];
        $url1 = rtrim($url1, 'index.php');
        $url2 = $url1 . $link->getAdminLink('AdminModules', true)
         . '&configure=tvcmsvideotab&tab_module=shipping_logistics&module_name=tvcmsvideotab&task=VideoList';
        $this->context->smarty->assign('url2', $url2);
        $id_product = (int) Tools::getValue('id_product');
        $this->context->smarty->assign('ver', '0');
        if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
            $id_product = (int) $params['id_product'];
            $this->context->smarty->assign('ver', '1');
        }
        if (empty($id_product)) {
            return $this->display(__FILE__, 'views/templates/admin/new_product_warning.tpl');
        }
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . (int) $id_product . "'";
        $sql .= " AND id_store='" . (int) $id_shop . "' AND type=0";
        $embed = $db->ExecuteS($sql);
        $sql2 = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . (int) $id_product . "'";
        $sql2 .= " AND id_store='" . (int) $id_shop . "'";
        $total = $db->ExecuteS($sql2);
        $sql1 = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . (int) $id_product . "'";
        $sql1 .= " AND id_store='" . (int) $id_shop . "' AND type=1";
        $file = $db->ExecuteS($sql1);
        $adminControllers = 'index.php?controller=AdminProducts';
        $token = '&token=' . Tools::getAdminTokenLite('AdminProducts');
        $cancel = $adminControllers . $token;
        $this->context->smarty->assign('cancel', $cancel);
        $this->context->smarty->assign('id_product', $id_product);
        $this->context->smarty->assign('embed', $embed);
        $this->context->smarty->assign('file', $file);
        $this->context->smarty->assign('total', $total);
        if (1 == $this->context->cookie->{'editfrommodule'}) {
            $this->context->smarty->assign('editfrommodule', $this->context->cookie->{'editfrommodule'});
            $this->context->cookie->{'editfrommodule'} = 0;
        } else {
            $this->context->cookie->{'type'} = 0;
        }
        if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
            $html .= $this->display(__FILE__, 'views/templates/admin/showcss17.tpl');
        }
        $this->context->smarty->assign('type', $this->context->cookie->type);
        $this->context->controller->addCSS($this->_path . 'views/css/videoadmin.css');
        $js_var = [
            'update_successful' => $this->l('Update successful'),
            'are_you_sure' => $this->l('Are you sure you want to delete video?'),
            'video_removed' => $this->l('Video successful removed'),
        ];
        $this->smarty->assign($js_var);
        $html .= $this->display(__FILE__, 'views/templates/admin/videotab.tpl');
        $html .= '<script>
            var id_shop = ' . $id_shop . ';
            var id_shop_new = ' . $id_shop . ';
            var name_shop = "' . $name_shop . '";
        </script>';

        return $html;
    }

    public function hookdisplayProductTabVideo($params)
    {
        // stop reload when change combination, PrestaShop automatic update product-cover-thumbnails.tpl, product.tpl
        $action = Tools::getValue('action');
        if ('refresh' == $action) {
            return '';
        }
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $html = '';
        $idlang = $this->context->language->id;
        $id_shop = $this->context->shop->id;
        $position_popup = Configuration::get('producpopup', null, '', $id_shop);
        $this->context->smarty->assign('position_popup', $position_popup);
        $url1 = Tools::getShopProtocol() . Tools::getServerName() . __PS_BASE_URI__;
        $this->context->smarty->assign('url1', $url1);
        if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->context->smarty->assign('ver', '1');
        }
        $id_product = Tools::getValue('id_product');
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . (int) $id_product . "'";
        $sql .= " AND id_lang='" . (int) $idlang . "' AND id_store='" . (int) $id_shop . "'";
        $infor = $db->ExecuteS($sql);
        if (empty($infor)) {
        } else {
            $this->context->smarty->assign('infor', $infor);
            if (0 == $infor['0']['type']) {
                preg_match('/src="([^"]+)"/', $infor['0']['text_url'], $match);
                $url = $match[1];
                $this->context->smarty->assign('url', $url);
            } else {
                $url = Tools::getShopProtocol() . Tools::getServerName() . __PS_BASE_URI__ . 'media/'
                . $id_shop . '/' . $id_product . '/' . $idlang . '/' . $infor['0']['text_url'];
                $this->context->smarty->assign('url', $url);
            }
        }
        $this->context->smarty->assign('vercheck', '0');
        if (Tools::version_compare(_PS_VERSION_, '1.7.4.4', '>=')) {
            $this->context->smarty->assign('vercheck', '1');
        }
        $html .= $this->display(__FILE__, 'views/templates/admin/popupvideo.tpl');
        $this->context->controller->addJS($this->_path . 'views/js/videopopup.js');
        $this->context->controller->addCSS($this->_path . 'views/css/videopopup.css');

        return $html;
    }

    public function hookDisplayProductTabContent($params)
    {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $html = '';
        $idlang = $this->context->language->id;
        $id_shop = $this->context->shop->id;
        $url1 = Tools::getShopProtocol() . Tools::getServerName() . __PS_BASE_URI__;
        $this->context->smarty->assign('url1', $url1);
        $position_tab = Configuration::get('productab', null, '', $id_shop);
        $this->context->smarty->assign('position_tab', $position_tab);
        $id_product = Tools::getValue('id_product');
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . (int) $id_product . "'";
        $sql .= " AND id_lang='" . (int) $idlang . "' AND id_store='" . (int) $id_shop . "'";
        $infor = $db->ExecuteS($sql);
        if (empty($infor)) {
        } else {
            $this->context->smarty->assign('infor', $infor);
            $url = Tools::getShopProtocol() . Tools::getServerName() . __PS_BASE_URI__ . 'media/'
            . $id_shop . '/' . $id_product . '/' . $idlang . '/' . $infor['0']['text_url'];
            $this->context->smarty->assign('url', $url);
        }
        $html .= $this->display(__FILE__, 'views/templates/admin/videotabhook.tpl');

        return $html;
    }

    public function hookdisplayProductExtraContent($params)
    {
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $array = [];
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $url1 = Tools::getShopProtocol() . Tools::getServerName() . __PS_BASE_URI__;
        $this->context->smarty->assign('url1', $url1);
        $idlang = $this->context->language->id;
        $id_shop = $this->context->shop->id;
        $position_tab = Configuration::get('productab', null, '', $id_shop);
        $this->context->smarty->assign('position_tab', $position_tab);
        if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->context->smarty->assign('ver', '1');
        }
        $id_product = Tools::getValue('id_product');
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . (int) $id_product . "'";
        $sql .= " AND id_lang='" . (int) $idlang . "' AND id_store='" . (int) $id_shop . "'";
        $infor = $db->ExecuteS($sql);
        $titles = 'Video';
        $iso_code = $this->context->language->iso_code;
        if ('en' == $iso_code) {
            $titles = 'Video';
        } elseif ('ar' == $iso_code) {
            $titles = 'فيديو';
        } elseif ('fr' == $iso_code) {
            $titles = 'Vidéo';
        } elseif ('de' == $iso_code) {
            $titles = 'Video';
        } elseif ('it' == $iso_code) {
            $titles = 'video';
        } elseif ('ru' == $iso_code) {
            $titles = 'видео';
        } else {
            $titles = 'vídeo';
        }
        if (empty($infor)) {
        } else {
            $this->context->smarty->assign('infor', $infor);
            $url = Tools::getShopProtocol() . Tools::getServerName() . __PS_BASE_URI__ . 'media/'
            . $id_shop . '/' . $id_product . '/' . $idlang . '/' . $infor['0']['text_url'];
            $this->context->smarty->assign('url', $url);
            $content = $this->display(__FILE__, 'views/templates/admin/videotabhook17.tpl');
            $array[] = (new PrestaShop\PrestaShop\Core\Product\ProductExtraContent())
            ->setTitle($titles)
            ->setContent($content);
        }

        return $array;
    }

    public function hookdisplayAfterProductVideoPopup($params)
    {
        return $this->hookdisplayProductTabVideo($params);
    }

    public function getContent()
    {
        $id_shop = $this->context->shop->id;
        $html = '';
        $bamodule = AdminController::$currentIndex;
        $this->smarty->assign('bamodule', $bamodule);
        $token = Tools::getAdminTokenLite('AdminModules');
        $buttonDemoArr = [
            'saveposition',
        ];
        if (true == $this->demoMode) {
            foreach ($buttonDemoArr as $buttonDemo) {
                if (Tools::isSubmit($buttonDemo)) {
                    Tools::redirectAdmin($bamodule . '&token=' . $token . '&configure=' . $this->name . '&demoMode=1');
                }
            }
        }
        $demoMode = 0;
        if ('1' == Tools::getValue('demoMode')) {
            $demoMode = Tools::getValue('demoMode');
        }
        $this->smarty->assign('demoMode', $demoMode);

        $this->caseVideo();
        $this->registerHook('displayProductExtraContent');
        if (Tools::isSubmit('saveposition')) {
            $position_tab = Tools::getValue('position_tab');
            $position_popup = Tools::getValue('position_popup');
            Configuration::updateValue('productab', '' . $position_tab . '', false, '', $id_shop);
            Configuration::updateValue('producpopup', '' . $position_popup . '', false, '', $id_shop);
            $videoextension = Tools::getValue('videoextension');
            Configuration::updateValue('videoextension', '' . $videoextension . '', false, '', $id_shop);
            $html .= $this->displayConfirmation('Successful update');
        }
        $position_tab = Configuration::get('productab', null, '', $id_shop);
        $this->context->smarty->assign('position_tab', $position_tab);
        $position_popup = Configuration::get('producpopup', null, '', $id_shop);
        $this->context->smarty->assign('position_popup', $position_popup);
        $videoextension = Configuration::get('videoextension', null, '', $id_shop);
        $this->context->smarty->assign('videoextension', $videoextension);

        $url = Tools::getShopProtocol() . Tools::getServerName() . __PS_BASE_URI__;
        $this->context->smarty->assign('url', $url);
        $taskbar = 'Settings';
        if (Tools::getValue('task')) {
            $taskbar = Tools::getValue('task');
        }
        $this->smarty->assign('taskbar', $taskbar);
        $this->smarty->assign('configure', $this->name);
        $token = '&token=' . Tools::getAdminTokenLite('AdminModules');
        $this->context->smarty->assign('token', $token);
        $html .= $this->display(__FILE__, 'views/templates/admin/videoadmin.tpl');

        if ('' == Tools::getValue('task')) {
            $html .= $this->settings();
        }
        if ('Settings' == Tools::getValue('task')) {
            $html .= $this->settings();
        }
        if ('VideoList' == Tools::getValue('task')) {
            $del = Tools::getValue('del');
            if (1 == $del) {
                $html .= $this->displayError('You must upload at least a file for default language');
            }
            $html .= $this->videoList();
        }

        return $html;
    }

    public function settings()
    {
        $html = $this->display(__FILE__, 'views/templates/admin/settings.tpl');
        $this->context->controller->addJS($this->_path . 'views/js/videotab.js');

        return $html;
    }

    public function videoList()
    {
        $langhelp = [];
        $languages = Language::getLanguages();
        $shophelp = [];
        $shophelps = Shop::getShops();
        foreach ($languages as $key_help => $value_help) {
            $langhelp[$value_help['id_lang']] = $value_help['name'];
        }
        foreach ($shophelps as $key_help => $value_help) {
            $shophelp[$value_help['id_shop']] = $value_help['name'];
        }
        $helper = new HelperList();
        $helper->shopLinkType = '';
        $helper->simple_header = false;
        $helper->actions = ['edit', 'delete'];
        $helper->toolbar_btn = ['back' => false];
        $helper->identifier = 'id_video';
        $helper->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];
        $helper->show_toolbar = true;
        $helper->title = $this->l('Video List');
        $helper->table = $this->name . 'url_video';
        $helper->list_id = $this->name . 'url_video';
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name . '&task=VideoList';
        $fields_list = [
            'id_video' => [
                'title' => $this->l('ID'),
                'width' => 50,
                'type' => 'int',
                'align' => 'left',
            ],
            'id_product' => [
                'title' => $this->l('Product ID'),
                'width' => 50,
                'type' => 'int',
                'align' => 'left',
            ],
            'shop' => [
                'title' => $this->l('Shop Name'),
                'type' => 'select',
                'list' => $shophelp,
                'filter_key' => 'shop',
                'align' => 'left',
            ],
            'name_product' => [
                'title' => $this->l('Product Name'),
                'type' => 'text',
                'align' => 'left',
                'callback' => 'productName',
                'callback_object' => $this,
            ],
            'text_url' => [
                'title' => $this->l('URL'),
                'type' => 'text',
                'align' => 'left',
            ],
            'type' => [
                'title' => $this->l('Type'),
                'width' => 100,
                'type' => 'select',
                'list' => [
                    0 => $this->l('Embed'),
                    1 => $this->l('Video File'),
                ],
                'filter_key' => 'type',
                'callback' => 'changeName',
                'callback_object' => $this,
            ],
            'language' => [
                'title' => $this->l('Languages'),
                'type' => 'select',
                'list' => $langhelp,
                'filter_key' => 'language',
                'align' => 'left',
            ],
        ];
        $helper->listTotal = $this->getNumberTotalList($helper);
        $rows = $this->getListContentVideo($helper);
        $html = $helper->generateList($rows, $fields_list);
        $this->context->controller->addJS($this->_path . 'views/js/videotab.js');
        $this->context->controller->addCSS($this->_path . 'views/css/videoadmin.css');

        return $html;
    }

    public function productName($name)
    {
        $this->context->smarty->assign('name', $name);

        return $this->display(__FILE__, 'views/templates/admin/productname.tpl');
    }

    public function hookdisplayBackofficeTop($params)
    {
        $html = '';
        $url = Tools::getShopProtocol() . Tools::getServerName() . __PS_BASE_URI__;
        $this->context->smarty->assign('url', $url);
        if (1 == $this->context->cookie->{'editfrommodule'}) {
            $html .= $this->display(__FILE__, 'views/templates/admin/showhide.tpl');
        }

        return $html;
    }

    public function hookdisplayTop($params)
    {
        $html = '';
        $url = Tools::getShopProtocol() . Tools::getServerName() . __PS_BASE_URI__;
        $this->context->smarty->assign('url', $url);
        if (Tools::version_compare(_PS_VERSION_, '1.7', '>=')) {
            $html .= $this->display(__FILE__, 'views/templates/admin/hookcss17.tpl');
            $html .= $this->display(__FILE__, 'views/templates/admin/hookpopupjs.tpl');
        } else {
            $html .= $this->display(__FILE__, 'views/templates/admin/hookcss16.tpl');
            $html .= $this->display(__FILE__, 'views/templates/admin/hookpopupjs.tpl');
        }

        return $html;
    }

    public function getNumberTotalList($helper)
    {
        $id_shop = $this->context->shop->id;
        $allShop = Context::getContext()->cookie->shopContext;
        if (false == $allShop) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $sql = 'SELECT count(*) FROM ' . _DB_PREFIX_ . 'url_video ';
            $total = $db->getValue($sql, false);
        } else {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $sql = 'SELECT count(*) FROM ' . _DB_PREFIX_ . 'url_video WHERE id_store="' . (int) $id_shop . '"';
            $total = $db->getValue($sql, false);
        }

        return $total;
    }

    public function getListContentVideo($helper)
    {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $id_shop = $this->context->shop->id;
        $allShop = Context::getContext()->cookie->shopContext;
        if (false == $allShop) {
            $sql = 'SELECT count(*) FROM ' . _DB_PREFIX_ . 'url_video ';
        } else {
            $sql = 'SELECT count(*) FROM ' . _DB_PREFIX_ . 'url_video WHERE id_store="' . (int) $id_shop . '"';
        }
        if (Tools::isSubmit('submitFilter')) {
            $sql .= $this->setWhereClause($helper);
        }
        $rows = $db->ExecuteS($sql);
        if ('' != Tools::getValue($helper->list_id . 'Orderby')) {
            $this->context->cookie->{$helper->list_id . 'Orderby'} = Tools::getValue($helper->list_id . 'Orderby');
        }
        if (empty($this->context->cookie->{$helper->list_id . 'Orderby'})) {
            $this->context->cookie->{$helper->list_id . 'Orderby'} = 'id_video';
        }
        if ('' != Tools::getValue($helper->list_id . 'Orderway')) {
            $this->context->cookie->{$helper->list_id . 'Orderway'} = Tools::getValue($helper->list_id . 'Orderway');
        }
        if (empty($this->context->cookie->{$helper->list_id . 'Orderway'})) {
            $this->context->cookie->{$helper->list_id . 'Orderway'} = 'asc';
        }
        $orderby = $this->context->cookie->{$helper->list_id . 'Orderby'};
        $orderway = $this->context->cookie->{$helper->list_id . 'Orderway'};
        $helper->orderBy = $orderby;
        $helper->orderWay = Tools::strtoupper($orderway);
        // pagination
        $cookiePagination = $this->context->cookie->{$helper->list_id . '_pagination'};
        $selected_pagination = (int) Tools::getValue($helper->list_id . '_pagination', $cookiePagination);
        // var_dump($selected_pagination);die;
        if ($selected_pagination <= 0) {
            $selected_pagination = 20;
        }
        $this->context->cookie->{$helper->list_id . '_pagination'} = $selected_pagination;
        $page = (int) Tools::getValue('submitFilter' . $helper->list_id, 1);
        if (!$page) {
            $page = 1;
        }
        $start = ($page - 1) * $selected_pagination;
        $allShop = Context::getContext()->cookie->shopContext;
        if (false == $allShop) {
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'url_video ';
            $sql .= $this->setWhereClause($helper);
            $sql .= ' ORDER BY ' . pSQL($orderby) . ' ' . pSQL($orderway) . ' LIMIT ' . (int) $start . ',' . (int) $selected_pagination;
            // var_dump($sql);
            $rows = $db->ExecuteS($sql, true, false);
        } else {
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'url_video WHERE id_store="' . (int) $id_shop . '"';
            $sql .= $this->setWhereClause($helper);
            $sql .= ' ORDER BY ' . pSQL($orderby) . ' ' . pSQL($orderway) . ' LIMIT ' . (int) $start . ',' . (int) $selected_pagination;
            // var_dump($sql);
            $rows = $db->ExecuteS($sql, true, false);
        }

        foreach ($rows as $key => $value) {
            if (1 != $rows[$key]['type']) {
                $rows[$key]['text_url'] = '--';
            }
        }

        return $rows;
    }

    public function setWhereClause($helper)
    {
        $array_where = [];
        $allShop = Context::getContext()->cookie->shopContext;
        if (false == $allShop) {
            $where = ' WHERE ';
        } else {
            $where = ' AND ';
        }
        if (null !== Tools::getValue($helper->list_id . 'Filter_id_video', null)) {
            $sql_id_video = pSQL(Tools::getValue($helper->list_id . 'Filter_id_video'));
            $this->context->cookie->{$helper->list_id . 'Filter_id_video'} = $sql_id_video;
        }
        if (isset($sql_id_video)) {
            $array_where[] = "id_video LIKE '%" . $sql_id_video . "%'";
        }
        if (null !== Tools::getValue($helper->list_id . 'Filter_id_product', null)) {
            $sql_id_product = pSQL(Tools::getValue($helper->list_id . 'Filter_id_product'));
            $this->context->cookie->{$helper->list_id . 'Filter_id_product'} = $sql_id_product;
        }

        if (isset($sql_id_product)) {
            $array_where[] = "id_product LIKE '%" . $sql_id_product . "%'";
        }

        if (null !== Tools::getValue($helper->list_id . 'Filter_id_store', null)) {
            $sql_id_store = pSQL(Tools::getValue($helper->list_id . 'Filter_id_store'));
            $this->context->cookie->{$helper->list_id . 'Filter_id_store'} = $sql_id_store;
        }

        if (isset($sql_id_store)) {
            $array_where[] = "id_store LIKE '%" . $sql_id_store . "%'";
        }

        if (null !== Tools::getValue($helper->list_id . 'Filter_name_product', null)) {
            $sql_name_product = pSQL(Tools::getValue($helper->list_id . 'Filter_name_product'));
            $this->context->cookie->{$helper->list_id . 'Filter_name_product'} = $sql_name_product;
        }
        if (isset($sql_name_product)) {
            $array_where[] = "name_product LIKE '%" . $sql_name_product . "%'";
        }
        if (null !== Tools::getValue($helper->list_id . 'Filter_shop', null)) {
            $sql_name_shop = pSQL(Tools::getValue($helper->list_id . 'Filter_shop'));
            $this->context->cookie->{$helper->list_id . 'Filter_shop'} = $sql_name_shop;
        }
        if (isset($sql_name_shop)) {
            $array_where[] = "id_store LIKE '%" . $sql_name_shop . "%'";
        }
        if (null !== Tools::getValue($helper->list_id . 'Filter_text_url', null)) {
            $sql_text_url = pSQL(Tools::getValue($helper->list_id . 'Filter_text_url'));
            $this->context->cookie->{$helper->list_id . 'Filter_text_url'} = $sql_text_url;
        }
        if (isset($sql_text_url)) {
            $array_where[] = "text_url LIKE '%" . $sql_text_url . "%'";
        }
        if (null !== Tools::getValue($helper->list_id . 'Filter_language', null)) {
            $sql_language = pSQL(Tools::getValue($helper->list_id . 'Filter_language'));
            $this->context->cookie->{$helper->list_id . 'Filter_language'} = $sql_language;
        }
        if (isset($sql_language)) {
            $array_where[] = "id_lang LIKE '%" . $sql_language . "%'";
        }
        if (null !== Tools::getValue($helper->list_id . 'Filter_type', null)) {
            $sql_type = pSQL(Tools::getValue($helper->list_id . 'Filter_type'));
            $this->context->cookie->{$helper->list_id . 'Filter_type'} = $sql_type;
        }
        if (isset($sql_type)) {
            $array_where[] = "type LIKE '%" . $sql_type . "%'";
        }

        if (empty($array_where)) {
            return false;
        } else {
            $where .= implode(' AND ', $array_where);
        }

        return $where;
    }

    public function changeName($objname)
    {
        $name_type = '';
        if (0 == $objname) {
            $name_type = 'Embed';
        }
        if (1 == $objname) {
            $name_type = 'Video File';
        }

        return $name_type;
    }

    public function resetVideo()
    {
        $search_field = [
            'id_video',
            'id_product',
            'id_store',
            'name_product',
            'shop',
            'id_lang',
            'language',
            'text_url',
            'type',
        ];
        foreach ($search_field as $v) {
            $this->context->cookie->{'tvcmsvideotaburl_videoFilter_' . $v} = null;
        }

        return true;
    }

    public function deletea()
    {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $id_video = Tools::getValue('id_video');
        $id_shop = $this->context->shop->id;
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_video='" . (int) $id_video . "' AND id_store='" . (int) $id_shop . "'";
        $data = $db->ExecuteS($sql);
        $id_product = $data['0']['id_product'];
        $id_lang = $data['0']['id_lang'];
        $type = $data['0']['type'];
        if (1 == $type) {
            $sql = 'SELECT text_url FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . (int) $id_product . "'";
            $sql .= " AND id_lang ='" . (int) $id_lang . "' AND id_store ='" . (int) $id_shop . "' AND type = 1 ";
            $data = $db->ExecuteS($sql);
            $url_dele = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product . '/' . $id_lang . '/';
            $file = $url_dele . $data['0']['text_url'];
            unlink($file);
            $sql1 = 'DELETE FROM ' . _DB_PREFIX_ . "url_video WHERE  id_product='" . (int) $id_product . "'";
            $sql1 .= ' AND id_lang=' . (int) $id_lang . ' AND id_store=' . (int) $id_shop . ' AND type = 1 ';
            $db->query($sql1);
        } else {
            $sql = 'DELETE FROM ' . _DB_PREFIX_ . "url_video WHERE  id_product='" . (int) $id_product . "'";
            $sql .= ' AND id_product=' . (int) $id_product . '';
            $db->query($sql);
        }

        return true;
    }

    public function delete()
    {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $idArray = Tools::getValue('tvcmsvideotaburl_videoBox');
        $id_shop = $this->context->shop->id;
        foreach ($idArray as $key => $value) {
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_video='" . (int) $value . "' AND id_store='" . (int) $id_shop . "'";
            $data = $db->ExecuteS($sql);
            $id_product = $data['0']['id_product'];
            $id_lang = $data['0']['id_lang'];
            $type = $data['0']['type'];
            if (1 == $type) {
                $sql = 'SELECT text_url FROM ' . _DB_PREFIX_ . "url_video WHERE id_product='" . (int) $id_product . "'";
                $sql .= " AND id_lang='" . (int) $id_lang . "' AND id_store='" . (int) $id_shop . "' AND type = 1 ";
                $data = $db->ExecuteS($sql);
                $url_dele = _PS_ROOT_DIR_ . '/media/' . $id_shop . '/' . $id_product . '/' . $id_lang . '/';
                $file = $url_dele . $data['0']['text_url'];
                unlink($file);
                $sql1 = 'DELETE FROM ' . _DB_PREFIX_ . "url_video WHERE  id_product='" . (int) $id_product . "'";
                $sql1 .= " AND id_lang='" . (int) $id_lang . "' AND id_store='" . (int) $id_shop . "' AND type = 1";
                $db->query($sql1);
            } else {
                $sql = 'DELETE FROM ' . _DB_PREFIX_ . "url_video WHERE  id_product='" . (int) $id_product . "'";
                $sql .= ' AND id_lang=' . (int) $id_lang . ' AND id_store=' . (int) $id_shop . '';
                $db->query($sql);
                // die("aa");
            }
        }

        return true;
    }

    public function caseVideo()
    {
        $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
        $adminControllers = AdminController::$currentIndex;
        $token = '&token=' . Tools::getAdminTokenLite('AdminModules');
        $configAndTask = '&configure=' . $this->name;
        $id_shop = $this->context->shop->id;
        if (Tools::isSubmit('submitResettvcmsvideotaburl_video')) {
            $this->resetVideo();
            Tools::redirectAdmin($adminControllers . $token . $configAndTask . '&task=VideoList');
        } elseif (Tools::isSubmit('deletetvcmsvideotaburl_video')) {
            $id_video = Tools::getValue('id_video');
            $this->deletea();
            Tools::redirectAdmin($adminControllers . $token . $configAndTask . '&task=VideoList');
        } elseif (Tools::isSubmit('updatetvcmsvideotaburl_video')) {
            $db = Db::getInstance(_PS_USE_SQL_SLAVE_);
            $id_video = Tools::getValue('id_video');
            $sql = 'SELECT * FROM ' . _DB_PREFIX_ . "url_video WHERE id_video='" . (int) $id_video . "' AND id_store='" . (int) $id_shop . "' ";
            $data = $db->ExecuteS($sql);
            $id_product = $data['0']['id_product'];
            $type = $data['0']['type'];
            $adminControllers = 'index.php?controller=AdminProducts';
            $token = '&token=' . Tools::getAdminTokenLite('AdminProducts');
            $url_id_product = '&id_product=' . $id_product . '&updateproduct&type=' . $type;
            $this->context->cookie->{'editfrommodule'} = 1;
            $this->context->cookie->{'type'} = $type;
            Tools::redirectAdmin($adminControllers . $token . $url_id_product);
        } elseif (Tools::isSubmit('submitBulkdeletetvcmsvideotaburl_video')) {
            $this->delete();
            Tools::redirectAdmin($adminControllers . $token . $configAndTask . '&task=VideoList');
        }
    }
}
