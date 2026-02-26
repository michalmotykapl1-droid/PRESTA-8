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
 * @author PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2025 PrestaShop SA
 * @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

include_once _PS_MODULE_DIR_ . 'tvcmsslider/classes/tvcmshomeslide.php';
include_once _PS_MODULE_DIR_ . 'tvcmsslider/classes/tvcmsslider_image_upload.class.php';
include_once _PS_MODULE_DIR_ . 'tvcmscustomsetting/classes/tvcmsresizemasterclass.php';

class TvcmsSlider extends Module implements WidgetInterface
{
    protected $html = '';

    protected $default_width = 779;

    protected $default_speed = 5000;

    protected $default_pause_on_hover = 1;

    protected $default_wrap = 1;

    protected $animation = 1;

    protected $templateFile;

    public function __construct()
    {
        $this->name = 'tvcmsslider';
        $this->tab = 'front_office_features';
        $this->version = '4.0.1';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;
        $this->secure_key = Tools::encrypt($this->name);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->getTranslator()->trans(
            'ThemeVolty - Slider',
            [],
            'Modules.TvcmsSlider.Admin'
        );

        $this->description = $this->getTranslator()->trans(
            'Adds an image slider to your site.',
            [],
            'Modules.TvcmsSlider.Admin'
        );

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l(
            'Warning: all the data saved in your database will be deleted.'
                . ' Are you sure you want uninstall this module?'
        );

        $this->templateFile = 'module:tvcmsslider/views/templates/hook/slider.tpl';
    }

    public function install()
    {
        $this->installTab();
        if (parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayHome')
            && $this->registerHook('displayWrapperTop')
            && $this->registerHook('displayContentWrapperTop')
            && $this->registerHook('displayBackOfficeHeader')
            // && $this->registerHook('displayTopColumn')
            && $this->registerHook('actionShopDataDuplication')) {
            $shops = Shop::getContextListShopID();
            $shop_groups_list = [];

            foreach ($shops as $shop_id) {
                $shop_group_id = (int) Shop::getGroupFromShop($shop_id, true);

                if (!in_array($shop_group_id, $shop_groups_list)) {
                    $shop_groups_list[] = $shop_group_id;
                }

                $tmp = 'TVCMSSLIDER_SPEED';
                $res = Configuration::updateValue($tmp, $this->default_speed, false, $shop_group_id, $shop_id);

                $tmp = 'TVCMSSLIDER_PAUSE_ON_HOVER';
                $tmp_2 = $this->default_pause_on_hover;
                $res &= Configuration::updateValue($tmp, $tmp_2, false, $shop_group_id, $shop_id);

                $tmp = $this->default_wrap;
                $res &= Configuration::updateValue('TVCMSSLIDER_WRAP', $tmp, false, $shop_group_id, $shop_id);

                $tmp = $this->animation;
                $res &= Configuration::updateValue('TVCMSSLIDER_ANIMATION', $tmp, false, $shop_group_id, $shop_id);
            }

            if (count($shop_groups_list)) {
                foreach ($shop_groups_list as $shop_group_id) {
                    $tmp = 'TVCMSSLIDER_SPEED';
                    $res &= Configuration::updateValue($tmp, $this->default_speed, false, $shop_group_id);

                    $tmp = 'TVCMSSLIDER_PAUSE_ON_HOVER';
                    $res &= Configuration::updateValue($tmp, $this->default_pause_on_hover, false, $shop_group_id);

                    $tmp = 'TVCMSSLIDER_WRAP';
                    $res &= Configuration::updateValue($tmp, $this->default_wrap, false, $shop_group_id);

                    $tmp = 'TVCMSSLIDER_ANIMATION';
                    $res &= Configuration::updateValue($tmp, $this->animation, false, $shop_group_id);
                }
            }

            // Sets up Global configuration
            $res &= Configuration::updateValue('TVCMSSLIDER_SPEED', $this->default_speed);
            $res &= Configuration::updateValue('TVCMSSLIDER_PAUSE_ON_HOVER', $this->default_pause_on_hover);
            $res &= Configuration::updateValue('TVCMSSLIDER_WRAP', $this->default_wrap);
            $res &= Configuration::updateValue('TVCMSSLIDER_ANIMATION', $this->animation);

            // Creates tables
            $res &= $this->createTables();

            // Adds samples
            // if ($res) {
            //     $this->createDefaultData();
            // }

            return (bool) $res;
        }

        return false;
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
            $tab->name[$lang['id_lang']] = 'Main Slider';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    /**
     * Adds samples.
     */
    public function createDefaultData()
    {
        $this->reset();
        $this->createTables();
        $languages = Language::getLanguages(false);
        for ($i = 1; $i <= 5; ++$i) {
            $slide = new TvcmsHomeSlide();
            $slide->position = $i;
            $slide->active = 1;
            foreach ($languages as $language) {
                $slide->title[$language['id_lang']] = 'Sale Live Now!';
                $slide->description[$language['id_lang']] = '<p>Up To 30% Off</p>';
                $slide->legend[$language['id_lang']] = 'sample-' . $i;
                $slide->btn_caption[$language['id_lang']] = 'Shop Now';
                $slide->video_width[$language['id_lang']] = 100;
                $slide->video_height[$language['id_lang']] = 100;
                $slide->class_name[$language['id_lang']] = 'tvmain-slider-contant-left';
                $slide->ivr_value[$language['id_lang']] = 'image_upload';
                $slide->url[$language['id_lang']] = '#';
                $slide->image[$language['id_lang']] = 'demo_img_' . $i . '.jpg';
                $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
                $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'demo_img_' . $i . '.jpg');
                $width = $imagedata[0];
                $height = $imagedata[1];
                $slide->width[$language['id_lang']] = $width;
                $slide->height[$language['id_lang']] = $height;
                // Image Resize
                $path = dirname(__FILE__) . '/views/img/';
                $ImageName = 'demo_img_' . $i . '.jpg';
                if (file_exists($path . $ImageName)) {
                    $MediumImgPath = $path . 'medium/';
                    if (!is_dir($MediumImgPath)) {
                        mkdir($MediumImgPath);
                    }
                    $resizeObj = new TvcmsResizeMasterClass($path . $ImageName);
                    $resizeObj->resizeImage(770, 770, 4);
                    $resizeObj->saveImage($MediumImgPath . $ImageName);

                    $SmallImgPath = $path . 'small/';
                    if (!is_dir($SmallImgPath)) {
                        mkdir($SmallImgPath);
                    }

                    $resizeObj = new TvcmsResizeMasterClass($path . $ImageName);
                    $resizeObj->resizeImage(500, 500, 4);
                    $resizeObj->saveImage($SmallImgPath . $ImageName);
                }
                // Image Resize
            }
            $slide->add();
        }
    }

    public function uninstall()
    {
        $this->uninstallTab();
        if (parent::uninstall()) {
            $res = $this->deleteTables();

            $this->deleteOfferBannerVariable();
            $res &= Configuration::deleteByName('TVCMSSLIDER_SPEED');
            $res &= Configuration::deleteByName('TVCMSSLIDER_PAUSE_ON_HOVER');
            $res &= Configuration::deleteByName('TVCMSSLIDER_WRAP');
            $res &= Configuration::deleteByName('TVCMSSLIDER_ANIMATION');

            return (bool) $res;
        }

        return false;
    }

    public function deleteOfferBannerVariable()
    {
        Configuration::deleteByName('TVCMSSLIDER_OFFER_BANNER_1');
        Configuration::deleteByName('TVCMSSLIDER_OFFER_BANNER_2');
        Configuration::deleteByName('TVCMSSLIDER_OFFER_BANNER_3');
        Configuration::deleteByName('TVCMSSLIDER_OFFER_BANNER_4');
        Configuration::deleteByName('TVCMSSLIDER_OFFER_BANNER_STATUS');
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    /**
     * Creates tables.
     */
    protected function createTables()
    {
        // Slides
        $res = (bool) Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tvcmsslider` (
                `id_tvcmsslider_slides` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_shop` int(10) unsigned NOT NULL,
                PRIMARY KEY (`id_tvcmsslider_slides`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;'
        );

        // Slides configuration
        $res &= Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tvcmsslider_slides` (
              `id_tvcmsslider_slides` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `position` int(10) unsigned NOT NULL DEFAULT \'0\',
              `active` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
              PRIMARY KEY (`id_tvcmsslider_slides`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;'
        );

        // Slides lang configuration
        $res &= Db::getInstance()->execute(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'tvcmsslider_slides_lang`(
              `id_tvcmsslider_slides` int(10) unsigned NOT NULL,
              `id_lang` int(10) unsigned NOT NULL,
              `title` varchar(255) NOT NULL,
              `description` text NOT NULL,
              `legend` varchar(255) NOT NULL,
              `url` varchar(255) NOT NULL,
              `btn_caption` varchar(255) NOT NULL,
              `video_width` int(10) unsigned NULL,
              `video_height` int(10) unsigned NULL,
              `class_name` varchar(255) NOT NULL,
              `ivr_value` varchar(255) NOT NULL,
              `image` varchar(255) NOT NULL,
              `width` int(100) unsigned NULL,
              `height` int(100) unsigned NULL,
              PRIMARY KEY (`id_tvcmsslider_slides`,`id_lang`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;'
        );

        return $res;
    }

    /**
     * deletes tables.
     */
    protected function deleteTables()
    {
        $slides = $this->getSlides();
        foreach ($slides as $slide) {
            $to_del = new TvcmsHomeSlide($slide['id_slide']);
            $to_del->delete();
        }

        return Db::getInstance()->execute(
            '
            DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'tvcmsslider`, `' . _DB_PREFIX_ . 'tvcmsslider_slides`, `' . _DB_PREFIX_
                . 'tvcmsslider_slides_lang`;
        '
        );
    }

    public function reset()
    {
        $trn_tbl = [];
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsslider`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsslider_slides_lang`';
        $trn_tbl[] = 'TRUNCATE `' . _DB_PREFIX_ . 'tvcmsslider_slides`';
        foreach ($trn_tbl as $table) {
            Db::getInstance()->execute($table);
        }
    }

    public function getContent()
    {
        // --- URUCHAMIAMY SYNCHRONIZACJĘ ---
        $this->syncCustomSlides(); 
        // ----------------------------------

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

        $this->html .= $this->headerHTML();
        if (Tools::isSubmit('submitSlide') || Tools::isSubmit('delete_id_slide')
            || Tools::isSubmit('submitSlider')
            || Tools::isSubmit('changeStatus')) {
            if ($this->postValidation()) {
                $this->postProcess();
                $this->html .= $this->renderForm();
                $this->html .= $this->renderList();
            } else {
                $this->html .= $this->renderAddForm();
            }

            $this->clearCustomSmartyCache('tvcmsslider_display_home.tpl');
        } elseif (Tools::isSubmit('addSlide')
            || (Tools::isSubmit('id_slide') && $this->slideExists((int) Tools::getValue('id_slide')))) {
            if (Tools::isSubmit('addSlide')) {
                $mode = 'add';
            } else {
                $mode = 'edit';
            }

            if ('add' == $mode) {
                if (Shop::CONTEXT_GROUP != Shop::getContext() && Shop::CONTEXT_ALL != Shop::getContext()) {
                    $this->html .= $this->renderAddForm();
                } else {
                    $this->html .= $this->getShopContextError(null, $mode);
                }
            } else {
                $associated_shop_ids = TvcmsHomeSlide::getAssociatedIdsShop((int) Tools::getValue('id_slide'));
                $context_shop_id = (int) Shop::getContextShopID();

                if (false === $associated_shop_ids) {
                    $this->html .= $this->getShopAssociationError((int) Tools::getValue('id_slide'));
                } elseif (Shop::CONTEXT_GROUP != Shop::getContext() && Shop::CONTEXT_ALL != Shop::getContext()
                    && in_array($context_shop_id, $associated_shop_ids)) {
                    if (count($associated_shop_ids) > 1) {
                        $this->html = $this->getSharedSlideWarning();
                    }
                    $this->html .= $this->renderAddForm();
                } else {
                    $shops_name_list = [];
                    foreach ($associated_shop_ids as $shop_id) {
                        $associated_shop = new Shop((int) $shop_id);
                        $shops_name_list[] = $associated_shop->name;
                    }
                    $this->html .= $this->getShopContextError($shops_name_list, $mode);
                }
            }
            $this->clearCustomSmartyCache('tvcmsslider_display_home.tpl');
        } else {
            $this->html .= $this->getWarningMultishopHtml()
                . $this->getCurrentShopInfoMsg()
                . $this->renderForm();

            if (Shop::CONTEXT_GROUP != Shop::getContext() && Shop::CONTEXT_ALL != Shop::getContext()) {
                $this->html .= $this->renderList();
            }
        }

        return $this->html;
    }

    public function clearCustomSmartyCache($cache_id)
    {
        if (Cache::isStored($cache_id)) {
            Cache::clean($cache_id);
        }
    }

    protected function postValidation()
    {
        $errors = [];

        // Validation for Slider configuration
        if (Tools::isSubmit('submitSlider')) {
            if (!Validate::isInt(Tools::getValue('TVCMSSLIDER_SPEED'))) {
                $errors[] = $this->getTranslator()->trans('Invalid values', [], 'Modules.TvcmsSlider.Admin');
            }
        } elseif (Tools::isSubmit('changeStatus')) {
            if (!Validate::isInt(Tools::getValue('id_slide'))) {
                $errors[] = $this->getTranslator()->trans('Invalid slide', [], 'Modules.TvcmsSlider.Admin');
            }
        } elseif (Tools::isSubmit('submitSlide')) {
            // Checks state (active)
            if (!Validate::isInt(Tools::getValue('active_slide')) || (0 != Tools::getValue('active_slide')
                && 1 != Tools::getValue('active_slide'))) {
                $errors[] = $this->getTranslator()->trans(
                    'Invalid slide state.',
                    [],
                    'Modules.TvcmsSlider.Admin'
                );
            }
            // Checks position
            if (!Validate::isInt(Tools::getValue('position')) || (Tools::getValue('position') < 0)) {
                $tmp = 'Invalid slide position.';
                $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
            }
            // If edit : checks id_slide
            if (Tools::isSubmit('id_slide')) {
                if (!Validate::isInt(Tools::getValue('id_slide')) && !$this->slideExists(Tools::getValue('id_slide'))) {
                    $errors[] = $this->getTranslator()->trans(
                        'Invalid slide ID',
                        [],
                        'Modules.TvcmsSlider.Admin'
                    );
                }
            }
            // Checks title/url/legend/description/image
            $languages = Language::getLanguages(false);
            foreach ($languages as $language) {
                if (Tools::strlen(Tools::getValue('title_' . $language['id_lang'])) > 255) {
                    $tmp = 'The title is too long.';
                    $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                }
                if (Tools::strlen(Tools::getValue('legend_' . $language['id_lang'])) > 255) {
                    $tmp = 'The caption is too long.';
                    $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                }

                if (Tools::strlen(Tools::getValue('btn_caption_' . $language['id_lang'])) > 255) {
                    $tmp = 'The caption is too long.';
                    $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                }

                if (Tools::strlen(Tools::getValue('class_name_' . $language['id_lang'])) > 255) {
                    $tmp = 'The caption is too long.';
                    $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                }

                if (Tools::strlen(Tools::getValue('ivr_value_' . $language['id_lang'])) > 255) {
                    $tmp = 'The caption is too long.';
                    $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                }

                if (Tools::strlen(Tools::getValue('url_' . $language['id_lang'])) > 255) {
                    $tmp = 'The URL is too long.';
                    $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                }
                
                if (Tools::strlen(Tools::getValue('url_' . $language['id_lang'])) > 0
                    && !Validate::isUrl(Tools::getValue('url_' . $language['id_lang']))) {
                    $tmp = 'The URL format is not correct.';
                    $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                }
                if (null != Tools::getValue('image_' . $language['id_lang'])
                    && !Validate::isFileName(Tools::getValue('image_' . $language['id_lang']))) {
                    $tmp = 'Invalid filename.';
                    $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                }
                if (null != Tools::getValue('image_old_' . $language['id_lang'])
                    && !Validate::isFileName(Tools::getValue('image_old_' . $language['id_lang']))) {
                    $tmp = 'Invalid filename.';
                    $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                }
                // 27-9-2025

                // start check if select wrong data if existing data//
                if (Tools::getValue('id_slide')) {
                    $slide = new TvcmsHomeSlide((int) Tools::getValue('id_slide'));
                    $dec = 1;
                } else {
                    $dec = 0;
                    $slide = new TvcmsHomeSlide();
                }
                $data = [];
                $data['exist_image'] = $slide->image;
                
                // FIX: Check if image exists before accessing
                $checkexistimage = isset($data['exist_image'][$language['id_lang']]) ? $data['exist_image'][$language['id_lang']] : '';

                // check image if exist
                $checkimgtype = Tools::substr(strrchr($checkexistimage, '.'), 1);
                $existing = Tools::strtolower($checkimgtype);
                // check image if exist

                // upload fresh image
                if (isset($_FILES['image_' . $language['id_lang']]['name'])) {
                    $tmp = Tools::substr(strrchr($_FILES['image_' . $language['id_lang']]['name'], '.'), 1);
                    $fresh = Tools::strtolower($tmp);
                } else {
                    $fresh = '';
                }
                // upload fresh image

                if ($dec = 1 || 'mp4' == $existing || 'jpg' == $existing) {
                    if ('mp4' == $existing && 'image_upload' == Tools::getValue('ivr_value_' . $language['id_lang'])) {
                        $tmp = 'Please select valid type';
                        $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                    }
                    if ('jpg' == $existing && 'video_upload' == Tools::getValue('ivr_value_' . $language['id_lang'])) {
                        $tmp = 'Please select valid type ';
                        $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                    }
                }

                if ('mp4' == $fresh || 'jpg' == $fresh) {
                    if ('mp4' == $fresh && 'image_upload' == Tools::getValue('ivr_value_' . $language['id_lang'])) {
                        $tmp = 'Please select valid type';
                        $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                    }
                    if ('jpg' == $fresh && 'video_upload' == Tools::getValue('ivr_value_' . $language['id_lang'])) {
                        $tmp = 'Please select valid type ';
                        $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
                    }
                }
            }
            $id_lang_default = (int) Configuration::get('PS_LANG_DEFAULT');
            if ('image_upload' == Tools::getValue('ivr_value_' . $id_lang_default)) {
                if (0 == Tools::strlen(Tools::getValue('url_' . $id_lang_default))) {
                    $errors[] = $this->getTranslator()->trans('The URL is not set for default language', [], 'Modules.TvcmsSlider.Admin');
                }
            }

            // ==========================================================================
            // POPRAWKA: Walidacja obrazka (Pomijamy dla HTML Only ORAZ dla Auto-Slajdu)
            // ==========================================================================
            $current_type = Tools::getValue('ivr_value_' . $id_lang_default);
            
            if ('html_only' != $current_type 
                && 'custom_tpl' != $current_type // <--- Tutaj dodano wyjątek dla plików TPL
                && !Tools::isSubmit('has_picture') 
                && (!isset($_FILES['image_' . $id_lang_default]) || empty($_FILES['image_' . $id_lang_default]['tmp_name']))) {
                
                $tmp = 'The image is not set.';
                $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
            }

            if (Tools::getValue('image_old_' . $id_lang_default)
                && !Validate::isFileName(Tools::getValue('image_old_' . $id_lang_default))) {
                $tmp = 'The image is not set.';
                $errors[] = $this->getTranslator()->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
            }
        } elseif (Tools::isSubmit('delete_id_slide') && (!Validate::isInt(Tools::getValue('delete_id_slide'))
            || !$this->slideExists((int) Tools::getValue('delete_id_slide')))) {
            $errors[] = $this->getTranslator()->trans('Invalid slide ID', [], 'Modules.TvcmsSlider.Admin');
        }

        if (count($errors)) {
            $this->html .= $this->displayError(implode('<br />', $errors));

            return false;
        }

        return true;
    }

    protected function postProcess()
    {
        $errors = [];
        $shop_context = Shop::getContext();

        // Processes Slider

        if (Tools::isSubmit('submitSlider')) {
            $shop_groups_list = [];
            $shops = Shop::getContextListShopID();

            foreach ($shops as $shop_id) {
                $shop_group_id = (int) Shop::getGroupFromShop($shop_id, true);

                if (!in_array($shop_group_id, $shop_groups_list)) {
                    $shop_groups_list[] = $shop_group_id;
                }

                $tmp = (int) Tools::getValue('TVCMSSLIDER_SPEED');
                $res = Configuration::updateValue('TVCMSSLIDER_SPEED', $tmp, false, $shop_group_id, $shop_id);

                $tmp = (int) Tools::getValue('TVCMSSLIDER_PAUSE_ON_HOVER');
                $res &= Configuration::updateValue(
                    'TVCMSSLIDER_PAUSE_ON_HOVER',
                    $tmp,
                    false,
                    $shop_group_id,
                    $shop_id
                );

                $tmp = (int) Tools::getValue('TVCMSSLIDER_WRAP');
                $res &= Configuration::updateValue('TVCMSSLIDER_WRAP', $tmp, false, $shop_group_id, $shop_id);

                $tmp = (int) Tools::getValue('TVCMSSLIDER_ANIMATION');
                $res &= Configuration::updateValue('TVCMSSLIDER_ANIMATION', $tmp, false, $shop_group_id, $shop_id);
            }

            // Update global shop context if needed
            switch ($shop_context) {
                case Shop::CONTEXT_ALL:
                    $res &= Configuration::updateValue(
                        'TVCMSSLIDER_SPEED',
                        (int) Tools::getValue('TVCMSSLIDER_SPEED')
                    );

                    $tmp = (int) Tools::getValue('TVCMSSLIDER_PAUSE_ON_HOVER');
                    $res &= Configuration::updateValue('TVCMSSLIDER_PAUSE_ON_HOVER', $tmp);
                    $res &= Configuration::updateValue('TVCMSSLIDER_WRAP', (int) Tools::getValue('TVCMSSLIDER_WRAP'));
                    $tmp = (int) Tools::getValue('TVCMSSLIDER_ANIMATION');
                    $res &= Configuration::updateValue('TVCMSSLIDER_ANIMATION', $tmp);
                    if (count($shop_groups_list)) {
                        foreach ($shop_groups_list as $shop_group_id) {
                            $tmp = (int) Tools::getValue('TVCMSSLIDER_SPEED');
                            $res &= Configuration::updateValue('TVCMSSLIDER_SPEED', $tmp, false, $shop_group_id);

                            $tmp = 'TVCMSSLIDER_PAUSE_ON_HOVER';
                            $tmp_2 = (int) Tools::getValue('TVCMSSLIDER_PAUSE_ON_HOVER');
                            $res &= Configuration::updateValue($tmp, $tmp_2, false, $shop_group_id);

                            $tmp = (int) Tools::getValue('TVCMSSLIDER_WRAP');
                            $res &= Configuration::updateValue('TVCMSSLIDER_WRAP', $tmp, false, $shop_group_id);

                            $tmp = (int) Tools::getValue('TVCMSSLIDER_ANIMATION');
                            $res &= Configuration::updateValue('TVCMSSLIDER_ANIMATION', $tmp, false, $shop_group_id);
                        }
                    }

                    break;

                case Shop::CONTEXT_GROUP:
                    if (count($shop_groups_list)) {
                        foreach ($shop_groups_list as $shop_group_id) {
                            $tmp = (int) Tools::getValue('TVCMSSLIDER_SPEED');
                            $res &= Configuration::updateValue('TVCMSSLIDER_SPEED', $tmp, false, $shop_group_id);

                            $tmp = 'TVCMSSLIDER_PAUSE_ON_HOVER';
                            $tmp_2 = (int) Tools::getValue('TVCMSSLIDER_PAUSE_ON_HOVER');
                            $res &= Configuration::updateValue($tmp, $tmp_2, false, $shop_group_id);

                            $tmp = (int) Tools::getValue('TVCMSSLIDER_WRAP');
                            $res &= Configuration::updateValue('TVCMSSLIDER_WRAP', $tmp, false, $shop_group_id);

                            $tmp = (int) Tools::getValue('TVCMSSLIDER_ANIMATION');
                            $res &= Configuration::updateValue('TVCMSSLIDER_ANIMATION', $tmp, false, $shop_group_id);
                        }
                    }

                    break;
            }

            $this->clearCustomSmartyCache('tvcmsslider_display_home.tpl');

            if (!$res) {
                $tmp = 'The configuration could not be updated.';
                $tmp_2 = 'Modules.TvcmsSlider.Admin';
                $errors[] = $this->displayError($this->getTranslator()->trans($tmp, [], $tmp_2));
            } else {
                Tools::redirectAdmin(
                    $this->context->link->getAdminLink('AdminModules', true)
                        . '&conf=6&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name
                );
            }
        } elseif (Tools::isSubmit('changeStatus') && Tools::isSubmit('id_slide')) {
            $slide = new TvcmsHomeSlide((int) Tools::getValue('id_slide'));
            if (0 == $slide->active) {
                $slide->active = 1;
            } else {
                $slide->active = 0;
            }
            $res = $slide->update();
            $this->clearCustomSmartyCache('tvcmsslider_display_home.tpl');

            if ($res) {
                $this->html .= $this->displayConfirmation(
                    $this->getTranslator()->trans(
                        'Configuration updated',
                        [],
                        'Admin.Notifications.Success'
                    )
                );
            } else {
                $this->html .= $this->displayError(
                    $this->getTranslator()->trans(
                        'The configuration could not be updated.',
                        [],
                        'Modules.TvcmsSlider.Admin'
                    )
                );
            }
        } elseif (Tools::isSubmit('submitSlide')) {
            // Sets ID if needed
            if (Tools::getValue('id_slide')) {
                $slide = new TvcmsHomeSlide((int) Tools::getValue('id_slide'));
                if (!Validate::isLoadedObject($slide)) {
                    $tmp = $this->getTranslator()->trans('Invalid slide ID', [], 'Modules.TvcmsSlider.Admin');
                    $this->html .= $this->displayError($tmp);
                    $editopen = 0;

                    return false;
                }
            } else {
                $editopen = 1;
                $slide = new TvcmsHomeSlide();
            }
            // Sets position
            $slide->position = (int) Tools::getValue('position');
            // Sets active
            $slide->active = (int) Tools::getValue('active_slide');
            // Sets each langue fields
            $languages = Language::getLanguages(false);

            foreach ($languages as $language) {
                $slide->title[$language['id_lang']] = Tools::getValue('title_' . $language['id_lang']);
                $slide->url[$language['id_lang']] = Tools::getValue('url_' . $language['id_lang']);
                $slide->legend[$language['id_lang']] = Tools::getValue('legend_' . $language['id_lang']);
                $slide->class_name[$language['id_lang']] = Tools::getValue('class_name_' . $language['id_lang']);
                $slide->ivr_value[$language['id_lang']] = Tools::getValue('ivr_value_' . $language['id_lang']);
                $slide->btn_caption[$language['id_lang']] = Tools::getValue('btn_caption_' . $language['id_lang']);
                $slide->video_width[$language['id_lang']] = Tools::getValue('video_width_' . $language['id_lang']);
                $slide->video_height[$language['id_lang']] = Tools::getValue('video_height_' . $language['id_lang']);
                $slide->description[$language['id_lang']] = Tools::getValue('description_' . $language['id_lang']);

                // Uploads image and sets slide
                $tmp = Tools::substr(strrchr($_FILES['image_' . $language['id_lang']]['name'], '.'), 1);
                $type = Tools::strtolower($tmp);

                if ('mp4' != $type && !empty($_FILES['image_' . $language['id_lang']]['tmp_name'])) {
                    $imagesize = @getimagesize($_FILES['image_' . $language['id_lang']]['tmp_name']);
                    $width = $imagesize[0];
                    $height = $imagesize[1];
                } else {
                    $imagesize = 1;
                    $width = '100';
                    $height = '100';
                }
                // $tmp = Tools::substr(strrchr($imagesize['mime'], '/'), 1);
                if (isset($_FILES['image_' . $language['id_lang']])
                    && isset($_FILES['image_' . $language['id_lang']]['tmp_name'])
                    && !empty($_FILES['image_' . $language['id_lang']]['tmp_name'])
                    && !empty($imagesize)
                    && in_array(Tools::strtolower($tmp), ['jpg', 'gif', 'jpeg', 'png', 'mp4', 'webp']) // Added webp support
                    && in_array($type, ['jpg', 'gif', 'jpeg', 'png', 'mp4', 'webp'])) { // Added webp support
                    $temp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
                    $salt = Tools::substr(sha1(microtime()), 0, 20);
                    $path = dirname(__FILE__) . '/views/img/';
                    $file_name = $salt . '_' . $_FILES['image_' . $language['id_lang']]['name'];

                    if ('mp4' != $type) {
                        if ($error = ImageManager::validateUpload($_FILES['image_' . $language['id_lang']])) {
                            $errors[] = $error;
                        } elseif (!$temp_name
                            || !move_uploaded_file($_FILES['image_' . $language['id_lang']]['tmp_name'], $temp_name)) {
                            return false;
                        } elseif (!ImageManager::resize($temp_name, $path . $file_name, null, null, $type)) {
                            $errors[] = $this->displayError(
                                $this->getTranslator()->trans(
                                    'An error occurred during the image upload process.',
                                    [],
                                    'Admin.Notifications.Error'
                                )
                            );
                        }
                    } else {
                        if ('mp4' == $type) {
                            $savePath = dirname(__FILE__) . '/views/img/';
                            $save_destination = $savePath . $file_name;
                            $resultUpload = move_uploaded_file($_FILES['image_' . $language['id_lang']]['tmp_name'], $save_destination);
                        } else {
                            $errors[] = $this->displayError(
                                $this->getTranslator()->trans(
                                    'Please insert mp4 Valid Format For video.',
                                    [],
                                    'Admin.Notifications.Error'
                                )
                            );
                        }
                    }

                    if (file_exists($path . 'fileType')) {
                        @unlink($path . 'fileType');
                    }

                    if (isset($temp_name)) {
                        @unlink($temp_name);
                    }

                    // Start Remove Old Image
                    $id = Tools::getValue('id_slide');
                    if (isset($id) && !empty($id)) {
                        $query = 'SELECT * FROM ' . _DB_PREFIX_ . 'tvcmsslider_slides_lang WHERE id_tvcmsslider_slides = '
                            . Tools::getValue('id_slide') . ' AND id_lang = ' . (int) $language['id_lang'];
                        $ans = Db::getInstance()->executeS($query);

                        if (isset($ans[0]['image']) && !empty($ans[0]['image'])) {
                            $res = preg_match('/^demo_img_.*$/', $ans[0]['image']);
                            if (file_exists($path . $ans[0]['image']) && '1' != $res) {
                                @unlink($path . $ans[0]['image']);
                            }
                            // Remove tv resize images
                            $tvpath = dirname(__FILE__) . '/views/img/';
                            $MediumImgPath = $tvpath . 'medium/' . $ans[0]['image'];
                            if (file_exists($MediumImgPath)) {
                                @unlink($MediumImgPath);
                            }
                            $SmallImgPath = $tvpath . 'small/' . $ans[0]['image'];
                            if (file_exists($SmallImgPath)) {
                                @unlink($SmallImgPath);
                            }
                            // Remove tv resize images
                        }
                    }
                    // End Remove Old Image

                    $slide->image[$language['id_lang']] = $salt . '_' . $_FILES['image_' . $language['id_lang']]['name'];
                    $slide->width[$language['id_lang']] = $width;
                    $slide->height[$language['id_lang']] = $height;
                } elseif ('' != Tools::getValue('image_old_' . $language['id_lang'])) {
                    $slide->image[$language['id_lang']] = Tools::getValue('image_old_' . $language['id_lang']);
                }

                // Image Resize
                $path = dirname(__FILE__) . '/views/img/';
                $ImageName = isset($slide->image[$language['id_lang']]) ? $slide->image[$language['id_lang']] : '';
                if (!empty($ImageName) && file_exists($path . $ImageName)) {
                    $MediumImgPath = $path . 'medium/';
                    if (!is_dir($MediumImgPath)) {
                        mkdir($MediumImgPath);
                    }

                  $resizeObj = new TvcmsResizeMasterClass($path . $ImageName);
                    // NOWE: Desktop - Panorama (1920x600)
                    // Ustawiamy np. 1920x600. Parametr '4' to tryb skalowania. 
                    // Jeśli Twoje grafiki mają inne proporcje, zmienią się, ale to jest bezpieczny max.
                    $resizeObj->resizeImage(1920, 600, 4); 
                    $resizeObj->saveImage($MediumImgPath . $ImageName);

                    $SmallImgPath = $path . 'small/';
                    if (!is_dir($SmallImgPath)) {
                        mkdir($SmallImgPath);
                    }

                    $resizeObj = new TvcmsResizeMasterClass($path . $ImageName);
                    // NOWE: Mobile - Kwadrat lub lekki pion (800x800)
                    $resizeObj->resizeImage(800, 800, 4); 
                    $resizeObj->saveImage($SmallImgPath . $ImageName);
                }
                // Image Resize
            }

            // Processes if no errors
            if (!$errors) {
                // Adds
                if (!Tools::getValue('id_slide')) {
                    if (!$slide->add()) {
                        $errors[] = $this->displayError(
                            $this->getTranslator()->trans(
                                'The slide could not be added.',
                                [],
                                'Modules.TvcmsSlider.Admin'
                            )
                        );
                    }
                } elseif (!$slide->update()) {
                    $tmp = 'The slide could not be updated.';
                    $tmp_2 = 'Modules.TvcmsSlider.Admin';
                    $errors[] = $this->displayError($this->getTranslator()->trans($tmp, [], $tmp_2));
                }
                $this->clearCustomSmartyCache('tvcmsslider_display_home.tpl');
            }
        } elseif (Tools::isSubmit('delete_id_slide')) {
            $slide = new TvcmsHomeSlide((int) Tools::getValue('delete_id_slide'));
            $file_name = $slide->image;
            foreach ($file_name as $file) {
                $path = dirname(__FILE__) . '/views/img/';
                $res = preg_match('/^demo_img_.*$/', $ans[0]['image']);

                if (file_exists($path . $file) && '0' != $res) {
                    @unlink($path . $file);
                }
                // Remove tv resize images
                $tvpath = dirname(__FILE__) . '/views/img/';
                $MediumImgPath = $tvpath . 'medium/' . $file;
                if (file_exists($MediumImgPath)) {
                    @unlink($MediumImgPath);
                }
                $SmallImgPath = $tvpath . 'small/' . $file;
                if (file_exists($SmallImgPath)) {
                    @unlink($SmallImgPath);
                }
                // Remove tv resize images
            }

            $res = $slide->delete();
            $this->clearCustomSmartyCache('tvcmsslider_display_home.tpl');
            if (!$res) {
                $this->html .= $this->displayError('Could not delete.');
            } else {
                Tools::redirectAdmin(
                    $this->context->link->getAdminLink('AdminModules', true) . '&conf=1&configure='
                        . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name
                );
            }
        }

        // Display errors if needed
        if (count($errors)) {
            $this->html .= $this->displayError(implode('<br />', $errors));
        } elseif (Tools::isSubmit('submitSlide') && Tools::getValue('id_slide')) {
            Tools::redirectAdmin(
                $this->context->link->getAdminLink('AdminModules', true) . '&conf=4&configure='
                    . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name
            );
        } elseif (Tools::isSubmit('submitSlide')) {
            Tools::redirectAdmin(
                $this->context->link->getAdminLink('AdminModules', true) . '&conf=3&configure='
                    . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name
            );
        }
    }

    public function hookdisplayBackOfficeHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
    }

    public function hookdisplayHeader($params)
    {
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
        // $this->context->controller->addJS($this->_path.'views/js/nivo.js');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');

        // $id_shop_group = Shop::getContextShopGroupID();
        // $id_shop = Shop::getContextShopID();

        // $tmp_1 = Configuration::get('TVCMSSLIDER_SPEED', null, $id_shop_group, $id_shop);
        // $tmp_2 = Configuration::get('TVCMSSLIDER_PAUSE_ON_HOVER', null, $id_shop_group, $id_shop);
        // $tmp_3 = Configuration::get('TVCMSSLIDER_WRAP', null, $id_shop_group, $id_shop);
        // $tmp_4 = Configuration::get('TVCMSSLIDER_ANIMATION', null, $id_shop_group, $id_shop);

        // Media::addJsDef(array('TVCMSSLIDER_SPEED' => $tmp_1,
        //                     'TVCMSSLIDER_PAUSE_ON_HOVER' => $tmp_2,
        //                     'TVCMSSLIDER_WRAP' => $tmp_3,
        //                     'TVCMSSLIDER_ANIMATION' => $tmp_4
        //                 ));
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        $data = $this->getWidgetVariables($hookName, $configuration);

        if (!Cache::isStored('tvcmsslider_display_home.tpl')) {
            if ($data) {
                $offer_banner = '';
                if (Module::isEnabled('tvcmssliderofferbanner')) {
                    $obj = new TvcmsSliderOfferBanner();
                    $offer_banner = $obj->showResult();
                }
                $this->context->smarty->assign('offer_banner', $offer_banner);
                $this->context->smarty->assign('data', $data['slides']);
                $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            } else {
                $output = '';
            }

            Cache::store('tvcmsslider_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmsslider_display_home.tpl');
    }

    public function hookdisplayWrapperTop($hookName = null, array $configuration = [])
    {
        $data = $this->getWidgetVariables($hookName, $configuration);

        if (!Cache::isStored('tvcmsslider_display_home.tpl')) {
            if ($data) {
                $offer_banner = '';
                if (Module::isEnabled('tvcmssliderofferbanner')) {
                    $obj = new TvcmsSliderOfferBanner();
                    $offer_banner = $obj->showResult();
                }
                $this->context->smarty->assign('offer_banner', $offer_banner);
                $this->context->smarty->assign('data', $data['slides']);
                $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            } else {
                $output = '';
            }

            Cache::store('tvcmsslider_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmsslider_display_home.tpl');
    }

    public function hookdisplayContentWrapperTop($hookName = null, array $configuration = [])
    {
        $data = $this->getWidgetVariables($hookName, $configuration);

        if (!Cache::isStored('tvcmsslider_display_home.tpl')) {
            if ($data) {
                $offer_banner = '';
                if (Module::isEnabled('tvcmssliderofferbanner')) {
                    $obj = new TvcmsSliderOfferBanner();
                    $offer_banner = $obj->showResult();
                }
                $this->context->smarty->assign('offer_banner', $offer_banner);
                $this->context->smarty->assign('data', $data['slides']);
                $output = $this->display(__FILE__, 'views/templates/front/display_home.tpl');
            } else {
                $output = '';
            }

            Cache::store('tvcmsslider_display_home.tpl', $output);
        }

        return Cache::retrieve('tvcmsslider_display_home.tpl');
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $slides = $this->getSlides(true);

        if (is_array($slides)) {
            foreach ($slides as &$slide) {
                $slide['sizes'] = @getimagesize(
                    dirname(__FILE__) . DIRECTORY_SEPARATOR . 'images'
                        . DIRECTORY_SEPARATOR . $slide['image']
                );
                if (isset($slide['sizes'][3]) && $slide['sizes'][3]) {
                    $slide['size'] = $slide['sizes'][3];
                }
            }
        }

        $config = $this->getConfigFieldsValues();

        $data_slider_js = [
            'speed' => $config['TVCMSSLIDER_SPEED'],
            'pause' => $config['TVCMSSLIDER_PAUSE_ON_HOVER'] ? 'true' : 'false',
            'wrap' => $config['TVCMSSLIDER_WRAP'] ? 'true' : 'false',
            'animation' => $config['TVCMSSLIDER_ANIMATION'] ? 'true' : 'false',
        ];
        $this->context->smarty->assign('main_slider_js', $data_slider_js);

        $a = [
            'slides' => $slides,
        ];

        return $a;
    }

    private function updateUrl($link)
    {
        if ('http://' !== Tools::substr($link, 0, 7) && 'https://' !== Tools::substr($link, 0, 8)) {
            $link = 'http://' . $link;
        }

        return $link;
    }

    public function hookActionShopDataDuplication($params)
    {
        Db::getInstance()->execute(
            '
            INSERT IGNORE INTO ' . _DB_PREFIX_ . 'tvcmsslider (id_tvcmsslider_slides, id_shop)
            SELECT id_tvcmsslider_slides, ' . (int) $params['new_id_shop'] . '
            FROM ' . _DB_PREFIX_ . 'tvcmsslider
            WHERE id_shop = ' . (int) $params['old_id_shop']
        );
        $this->clearCustomSmartyCache('tvcmsslider_display_home.tpl');
    }

    public function headerHTML()
    {
        if ('AdminModules' != Tools::getValue('controller') && Tools::getValue('configure') != $this->name) {
            return;
        }

        $this->context->controller->addJqueryUI('ui.sortable');
        // Style & js for fieldset 'slides configuration'
        $script = '<script type="text/javascript">
            $(function() {
                var $mySlides = $("#slides");
                $mySlides.sortable({
                    opacity: 0.6,
                    cursor: "move",
                    update: function() {
                        var order = $(this).sortable("serialize") + "&action=updateSlidesPosition";
                        $.post("' . $this->context->shop->physical_uri . $this->context->shop->virtual_uri
            . 'modules/' . $this->name . '/classes/tvcmsajaxslider.php?secure_key=' . $this->secure_key . '", order);
                        }
                    });
                $mySlides.hover(function() {
                    $(this).css("cursor","move");
                    },
                    function() {
                    $(this).css("cursor","auto");
                });
            });
        </script>';

        return $script;
    }

    public function getNextPosition()
    {
        $tmp = (int) $this->context->shop->id;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            '
            SELECT MAX(hss.`position`) AS `next_position`
            FROM `' . _DB_PREFIX_ . 'tvcmsslider_slides` hss, `' . _DB_PREFIX_ . 'tvcmsslider` hs
            WHERE hss.`id_tvcmsslider_slides` = hs.`id_tvcmsslider_slides` AND hs.`id_shop` = ' . (int) $tmp
        );

        return ++$row['next_position'];
    }

    public function getSlides($active = null)
    {
        $this->context = Context::getContext();
        $id_shop = $this->context->shop->id;
        $id_lang = $this->context->language->id;

        $query = 'SELECT hs.`id_tvcmsslider_slides` as id_slide, hss.`position`, hss.`active`, hssl.`title`,
            hssl.`url`, hssl.`legend`, hssl.`btn_caption`, hssl.`video_width`, hssl.`video_height`, hssl.`class_name`, hssl.`ivr_value`, hssl.`description`, hssl.`image`,
            hssl.`width`, hssl.`height`
            FROM ' . _DB_PREFIX_ . 'tvcmsslider hs
            LEFT JOIN ' . _DB_PREFIX_ . 'tvcmsslider_slides hss ON (hs.id_tvcmsslider_slides = hss.id_tvcmsslider_slides)
            LEFT JOIN ' . _DB_PREFIX_ . 'tvcmsslider_slides_lang hssl ON (hss.id_tvcmsslider_slides = '
            . 'hssl.id_tvcmsslider_slides)
            WHERE id_shop = ' . (int) $id_shop . '
            AND hssl.id_lang = ' . (int) $id_lang
            . ($active ? ' AND hss.`active` = 1' : ' ') . '
            ORDER BY hss.position';
        $slides = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);

        foreach ($slides as &$slide) {
            // Large
            $slide['url'] = $slide['url'];
            // [1 – for computer] [2 – for tablet] [4 – for mobile]
            // if (Context::getContext()->getDevice() == 1) {
            //     $slide['image_url'] = $this->context->link->getMediaLink(_MODULE_DIR_.'tvcmsslider/views/img/'.
            //     $slide['image']);
            //     $slide['height'] = $slide['height'];
            // } else if (Context::getContext()->getDevice() == 2) {
            //     $slide['image_url'] = $this->context->link->getMediaLink(_MODULE_DIR_.'tvcmsslider/views/img/medium/'.
            //     $slide['image']);
            //     $slide['height'] = '342';
            // } else {
            //     $slide['image_url'] = $this->context->link->getMediaLink(_MODULE_DIR_.'tvcmsslider/views/img/small/'.
            //     $slide['image']);
            //     $slide['height'] = '160';
            // }

            $slide['image_url'] = $this->context->link->getMediaLink(
                _MODULE_DIR_ . 'tvcmsslider/views/img/'
                    . $slide['image']
            );
            $slide['height'] = $slide['height'];

            $slide['med_image_url'] = $this->context->link->getMediaLink(
                _MODULE_DIR_ . 'tvcmsslider/views/img/medium/'
                    . $slide['image']
            );
            $slide['med_height'] = '342';

            $slide['sml_image_url'] = $this->context->link->getMediaLink(
                _MODULE_DIR_ . 'tvcmsslider/views/img/small/'
                    . $slide['image']
            );
            $slide['sml_height'] = '160';
        }

        return $slides;
    }

    public function getAllImagesBySlidesId($id_slides, $active = null, $id_shop = null)
    {
        $this->context = Context::getContext();
        $images = [];

        if (!isset($id_shop)) {
            $id_shop = $this->context->shop->id;
        }

        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            '
            SELECT hssl.`image`, hssl.`id_lang`
            FROM ' . _DB_PREFIX_ . 'tvcmsslider hs
            LEFT JOIN ' . _DB_PREFIX_ . 'tvcmsslider_slides hss ON (hs.id_tvcmsslider_slides = hss.id_tvcmsslider_slides)
            LEFT JOIN ' . _DB_PREFIX_ . 'tvcmsslider_slides_lang hssl ON (hss.id_tvcmsslider_slides = '
                . 'hssl.id_tvcmsslider_slides)
            WHERE hs.`id_tvcmsslider_slides` = ' . (int) $id_slides . ' AND hs.`id_shop` = ' . (int) $id_shop
                . ($active ? ' AND hss.`active` = 1' : ' ')
        );

        foreach ($results as $result) {
            $images[$result['id_lang']] = $result['image'];
        }

        return $images;
    }

    public function displayStatus($id_slide, $active)
    {
        if (0 == (int) $active) {
            $title = $this->getTranslator()->trans('Disabled', [], 'Admin.Global');
        } else {
            $title = $this->getTranslator()->trans('Enabled', [], 'Admin.Global');
        }

        $icon = (0 == (int) $active ? 'icon-remove' : 'icon-check');
        $class = (0 == (int) $active ? 'btn-danger' : 'btn-success');
        $script = '<a class="btn ' . $class . '" href="' . AdminController::$currentIndex
            . '&configure=' . $this->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules')
            . '&changeStatus&id_slide=' . (int) $id_slide . '" title="' . $title . '"><i class="' . $icon . '"></i> ' . $title
            . '</a>';

        return $script;
    }

    public function slideExists($id_slide)
    {
        $req = 'SELECT hs.`id_tvcmsslider_slides` as id_slide
                FROM `' . _DB_PREFIX_ . 'tvcmsslider` hs
                WHERE hs.`id_tvcmsslider_slides` = ' . (int) $id_slide;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);

        return $row;
    }

    public function renderList()
    {
        $slides = $this->getSlides();
        foreach ($slides as $key => $slide) {
            $slides[$key]['status'] = $this->displayStatus($slide['id_slide'], $slide['active']);
            $associated_shop_ids = TvcmsHomeSlide::getAssociatedIdsShop((int) $slide['id_slide']);
            if ($associated_shop_ids && count($associated_shop_ids) > 1) {
                $slides[$key]['is_shared'] = true;
            } else {
                $slides[$key]['is_shared'] = false;
            }
        }

        $this->context->smarty->assign(
            [
                'link' => $this->context->link,
                'slides' => $slides,
                'image_baseurl' => $this->_path . 'views/img/',
            ]
        );

        return $this->display(__FILE__, 'list.tpl');
    }

    public function renderAddForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->getTranslator()->trans(
                        'Slide information',
                        [],
                        'Modules.TvcmsSlider.Admin'
                    ),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'custom_radio_btn',
                        'label' => $this->getTranslator()->trans('Select Type', [], 'Admin.Global'),
                        'name' => 'ivr_value',
                        'lang' => true,
                    ],
                    [
                        'type' => 'file_lang',
                        'label' => $this->getTranslator()->trans('Image/Video', [], 'Admin.Global'),
                        'name' => 'image',
                        'required' => true,
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Title', [], 'Admin.Global'),
                        'name' => 'title',
                        // 'class' => 'fixed-width-xl',
                        'col' => 6,
                        'lang' => true,
                    ],
                    [
                        'type' => 'radio_btn',
                        'label' => $this->getTranslator()->trans('Text Alignment', [], 'Admin.Global'),
                        'name' => 'class_name',
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Button Caption', [], 'Admin.Global'),
                        'name' => 'btn_caption',
                        'col' => 4,
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'suffix' => 'PX',
                        'col' => 2,
                        'desc' => $this->getTranslator()->trans('Note: Leave blank for auto adjust width', [], 'Admin.Global'),
                        'label' => $this->getTranslator()->trans('Video Width', [], 'Admin.Global'),
                        'name' => 'video_width',
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'suffix' => 'PX',
                        'col' => 2,
                        'desc' => $this->getTranslator()->trans('Note: Leave blank for auto adjust height', [], 'Admin.Global'),
                        'label' => $this->getTranslator()->trans('Video Height', [], 'Admin.Global'),
                        'name' => 'video_height',
                        'lang' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Target URL', [], 'Modules.TvcmsSlider.Admin'),
                        'name' => 'url',
                        'col' => 6,
                        'desc' => $this->getTranslator()->trans('Note: Must input url,leave blank if you choose video', [], 'Admin.Global'),
                        'required' => true,
                        'lang' => true,
                    ],
                    // array(
                    //     'type' => 'hidden', // This is Not Use in This Theme
                    //     'label' => $this->getTranslator()->trans('Caption', array(), 'Modules.TvcmsSlider.Admin'),
                    //     'name' => 'legend',
                    //     'lang' => true,
                    // ),
                    [
                        'type' => 'textarea',
                        'label' => $this->getTranslator()->trans('Description', [], 'Admin.Global'),
                        'name' => 'description',
                        'autoload_rte' => true,
                        'col' => 7,
                        'lang' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('Enabled', [], 'Admin.Global'),
                        'name' => 'active_slide',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->getTranslator()->trans('Yes', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->getTranslator()->trans('No', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->getTranslator()->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        if (Tools::isSubmit('id_slide') && $this->slideExists((int) Tools::getValue('id_slide'))) {
            $slide = new TvcmsHomeSlide((int) Tools::getValue('id_slide'));
            $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'id_slide'];
            $fields_form['form']['images'] = $slide->image;
            $fields_form['form']['ivr_value'] = $slide->ivr_value;

            $has_picture = true;

            foreach (Language::getLanguages(false) as $lang) {
                if (!isset($slide->image[$lang['id_lang']])) {
                    $has_picture &= false;
                }
            }

            if ($has_picture) {
                $fields_form['form']['input'][] = ['type' => 'hidden', 'name' => 'has_picture'];
            }
        }

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
        $helper->submit_action = 'submitSlide';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name
            . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
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
            'image_baseurl' => $this->_path . 'views/img/',
        ];

        $helper->override_folder = '/';

        $languages = Language::getLanguages(false);

        if (count($languages) > 1) {
            return $this->getMultiLanguageInfoMsg() . $helper->generateForm([$fields_form]);
        } else {
            return $helper->generateForm([$fields_form]);
        }
    }

    public function renderForm()
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
                        'type' => 'text',
                        'label' => $this->getTranslator()->trans('Speed', [], 'Modules.TvcmsSlider.Admin'),
                        'name' => 'TVCMSSLIDER_SPEED',
                        'suffix' => 'milliseconds',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->getTranslator()->trans(
                            'The duration of the transition between two slides.',
                            [],
                            'Modules.TvcmsSlider.Admin'
                        ),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans(
                            'Pause On Hover',
                            [],
                            'Modules.TvcmsSlider.Admin'
                        ),
                        'name' => 'TVCMSSLIDER_PAUSE_ON_HOVER',
                        'desc' => $this->getTranslator()->trans(
                            'Stop sliding when the mouse cursor is over the slideshow.',
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
                    // array(
                    //     'type' => 'switch',
                    //     'label' => $wrap_lable,
                    //     'name' => 'TVCMSSLIDER_WRAP',
                    //     'desc' => $this->getTranslator()->trans(
                    //         'Loop or stop after the last slide.',
                    //         array(),
                    //         'Modules.TvcmsSlider.Admin'
                    //     ),
                    //     'values' => array(
                    //         array(
                    //             'id' => 'active_on',
                    //             'value' => 1,
                    //             'label' => $this->getTranslator()->trans('Enabled', array(), 'Admin.Global')
                    //         ),
                    //         array(
                    //             'id' => 'active_off',
                    //             'value' => 0,
                    //             'label' => $this->getTranslator()->trans('Disabled', array(), 'Admin.Global')
                    //         )
                    //     ),
                    // ),
                    // array(
                    //     'type' => 'switch',
                    //     'label' => $this->getTranslator()->trans(
                    //         'Slider Animation',
                    //         array(),
                    //         'Modules.TvcmsSlider.Admin'
                    //     ),
                    //     'name' => 'TVCMSSLIDER_ANIMATION',
                    //     'desc' => $this->getTranslator()->trans(
                    //         'Show Front Side Slider Animation.',
                    //         array(),
                    //         'Modules.TvcmsSlider.Admin'
                    //     ),
                    //     'values' => array(
                    //         array(
                    //             'id' => 'active_on',
                    //             'value' => 1,
                    //             'label' => $this->getTranslator()->trans('Enabled', array(), 'Admin.Global')
                    //         ),
                    //         array(
                    //             'id' => 'active_off',
                    //             'value' => 0,
                    //             'label' => $this->getTranslator()->trans('Disabled', array(), 'Admin.Global')
                    //         )
                    //     ),
                    // ),
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
        $helper->submit_action = 'submitSlider';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure='
            . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        $id_shop_group = Shop::getContextShopGroupID();
        $id_shop = Shop::getContextShopID();

        $tmp = Configuration::get('TVCMSSLIDER_SPEED', null, $id_shop_group, $id_shop);
        $tmp_2 = Configuration::get('TVCMSSLIDER_PAUSE_ON_HOVER', null, $id_shop_group, $id_shop);
        $tmp_3 = Configuration::get('TVCMSSLIDER_WRAP', null, $id_shop_group, $id_shop);
        $tmp_4 = Configuration::get('TVCMSSLIDER_ANIMATION', null, $id_shop_group, $id_shop);

        return [
            'TVCMSSLIDER_SPEED' => Tools::getValue('TVCMSSLIDER_SPEED', $tmp),
            'TVCMSSLIDER_PAUSE_ON_HOVER' => Tools::getValue('TVCMSSLIDER_PAUSE_ON_HOVER', $tmp_2),
            'TVCMSSLIDER_WRAP' => Tools::getValue('TVCMSSLIDER_WRAP', $tmp_3),
            'TVCMSSLIDER_ANIMATION' => Tools::getValue('TVCMSSLIDER_ANIMATION', $tmp_4),
        ];
    }

    public function getAddFieldsValues()
    {
        $fields = [];

        if (Tools::isSubmit('id_slide') && $this->slideExists((int) Tools::getValue('id_slide'))) {
            $slide = new TvcmsHomeSlide((int) Tools::getValue('id_slide'));
            $fields['id_slide'] = (int) Tools::getValue('id_slide', $slide->id);
        } else {
            $slide = new TvcmsHomeSlide();
        }

        $fields['active_slide'] = Tools::getValue('active_slide', $slide->active);
        $fields['has_picture'] = true;

        $languages = Language::getLanguages(false);

        foreach ($languages as $lang) {
            $id_lang = (int) $lang['id_lang'];

            // FIX: Check if keys exist before accessing them to prevent "access array offset on value of type null"
            
            $fields['image'][$id_lang] = Tools::getValue('image_' . $id_lang);

            $val = isset($slide->title[$id_lang]) ? $slide->title[$id_lang] : '';
            $fields['title'][$id_lang] = Tools::getValue('title_' . $id_lang, $val);

            $val = isset($slide->url[$id_lang]) ? $slide->url[$id_lang] : '';
            $fields['url'][$id_lang] = Tools::getValue('url_' . $id_lang, $val);

            $val = isset($slide->legend[$id_lang]) ? $slide->legend[$id_lang] : '';
            $fields['legend'][$id_lang] = Tools::getValue('legend_' . $id_lang, $val);

            $val = isset($slide->btn_caption[$id_lang]) ? $slide->btn_caption[$id_lang] : '';
            $fields['btn_caption'][$id_lang] = Tools::getValue('btn_caption_' . $id_lang, $val);

            $val = isset($slide->video_width[$id_lang]) ? $slide->video_width[$id_lang] : '';
            $fields['video_width'][$id_lang] = Tools::getValue('video_width_' . $id_lang, $val);

            $val = isset($slide->video_height[$id_lang]) ? $slide->video_height[$id_lang] : '';
            $fields['video_height'][$id_lang] = Tools::getValue('video_height_' . $id_lang, $val);

            $val = isset($slide->class_name[$id_lang]) ? $slide->class_name[$id_lang] : '';
            $fields['class_name'][$id_lang] = Tools::getValue('class_name_' . $id_lang, $val);

            $val = isset($slide->ivr_value[$id_lang]) ? $slide->ivr_value[$id_lang] : '';
            $fields['ivr_value'][$id_lang] = Tools::getValue('ivr_value_' . $id_lang, $val);

            $val = isset($slide->description[$id_lang]) ? $slide->description[$id_lang] : '';
            $fields['description'][$id_lang] = Tools::getValue('description_' . $id_lang, $val);
        }

        return $fields;
    }

    protected function getMultiLanguageInfoMsg()
    {
        return '<p class="alert alert-warning">'
            . $this->getTranslator()->trans(
                'Since multiple languages are activated on your shop, please mind'
                    . ' to upload your image for each one of them',
                [],
                'Modules.TvcmsSlider.Admin'
            )
            . '</p>';
    }

    protected function getWarningMultishopHtml()
    {
        if (Shop::CONTEXT_GROUP == Shop::getContext() || Shop::CONTEXT_ALL == Shop::getContext()) {
            return '<p class="alert alert-warning">'
                . $this->getTranslator()->trans(
                    'You cannot manage slides items from a "All Shops" or a "Group Shop" context,'
                        . ' select directly the shop you want to edit',
                    [],
                    'Modules.TvcmsSlider.Admin'
                )
                . '</p>';
        } else {
            return '';
        }
    }

    protected function getShopContextError($shop_contextualized_name, $mode)
    {
        if (is_array($shop_contextualized_name)) {
            $shop_contextualized_name = implode('<br/>', $shop_contextualized_name);
        }

        if ('edit' == $mode) {
            $tmp = 'You can only edit this slide from the shop(s) context: %s';

            return '<p class="alert alert-danger">'
                . $this->trans($tmp, [$shop_contextualized_name], 'Modules.TvcmsSlider.Admin')
                . '</p>';
        } else {
            $tmp = 'You cannot add slides from a "All Shops" or a "Group Shop" context';

            return '<p class="alert alert-danger">'
                . $this->trans($tmp, [], 'Modules.TvcmsSlider.Admin')
                . '</p>';
        }
    }

    protected function getShopAssociationError($id_slide)
    {
        return '<p class="alert alert-danger">'
            . $this->trans(
                'Unable to get slide shop association information (id_slide: %d)',
                [(int) $id_slide],
                'Modules.TvcmsSlider.Admin'
            )
            . '</p>';
    }

    protected function getCurrentShopInfoMsg()
    {
        $shop_info = null;

        if (Shop::isFeatureActive()) {
            if (Shop::CONTEXT_SHOP == Shop::getContext()) {
                $tmp = 'The modifications will be applied to shop: %s';
                $shop_info = $this->trans($tmp, [$this->context->shop->name], 'Modules.TvcmsSlider.Admin');
            } elseif (Shop::CONTEXT_GROUP == Shop::getContext()) {
                $tmp = 'The modifications will be applied to this group: %s';
                $shop_info = $this->trans($tmp, [Shop::getContextShopGroup()->name], 'Modules.TvcmsSlider.Admin');
            } else {
                $tmp = 'The modifications will be applied to all shops and shop groups';
                $shop_info = $this->trans($tmp, [], 'Modules.TvcmsSlider.Admin');
            }

            return '<div class="alert alert-info">'
                . $shop_info
                . '</div>';
        } else {
            return '';
        }
    }

    protected function getSharedSlideWarning()
    {
        return '<p class="alert alert-warning">'
            . $this->trans(
                'This slide is shared with other shops! All shops associated to this slide will apply'
                    . ' modifications made here',
                [],
                'Modules.TvcmsSlider.Admin'
            )
            . '</p>';
    }
// --- NOWA FUNKCJA: AUTO-SYNCHRONIZACJA PLIKÓW TPL ---
    public function syncCustomSlides()
    {
        $custom_slides_dir = _PS_MODULE_DIR_ . 'tvcmsslider/views/templates/front/custom_slides/';
        $shop_id = (int) Context::getContext()->shop->id;
        $languages = Language::getLanguages(false);

        // 1. Pobierz listę plików z serwera
        $files_on_disk = [];
        if (is_dir($custom_slides_dir)) {
            $scanned = scandir($custom_slides_dir);
            foreach ($scanned as $file) {
                if (preg_match('/\.tpl$/', $file)) {
                    $files_on_disk[] = $file;
                }
            }
        }

        // 2. Pobierz listę slajdów TPL z bazy danych
        // Szukamy tylko tych, które mają ivr_value = 'custom_tpl'
        $slides_in_db = [];
        $db_slides = Db::getInstance()->executeS('
            SELECT s.id_tvcmsslider_slides, sl.class_name 
            FROM ' . _DB_PREFIX_ . 'tvcmsslider_slides s
            JOIN ' . _DB_PREFIX_ . 'tvcmsslider_slides_lang sl ON s.id_tvcmsslider_slides = sl.id_tvcmsslider_slides
            WHERE sl.ivr_value = "custom_tpl" AND sl.id_lang = ' . (int)Configuration::get('PS_LANG_DEFAULT')
        );

        if ($db_slides) {
            foreach ($db_slides as $slide) {
                $slides_in_db[$slide['class_name']] = $slide['id_tvcmsslider_slides'];
            }
        }

        // 3. DODAWANIE: Jeśli plik jest na dysku, a nie ma go w bazie -> Dodaj go
        foreach ($files_on_disk as $file) {
            if (!array_key_exists($file, $slides_in_db)) {
                // Tworzymy nowy slajd
                $slide = new TvcmsHomeSlide();
                $slide->active = 0; // Domyślnie wyłączony
                $slide->position = $this->getNextPosition(); // Na koniec listy
                
                foreach ($languages as $lang) {
                    $id_lang = $lang['id_lang'];
                    $slide->title[$id_lang] = 'PLIK: ' . $file; // Tytuł roboczy
                    $slide->url[$id_lang] = '#';
                    $slide->legend[$id_lang] = 'Auto-Detected';
                    $slide->btn_caption[$id_lang] = 'Zobacz';
                    $slide->class_name[$id_lang] = $file; // TU ZAPISUJEMY NAZWĘ PLIKU!
                    $slide->ivr_value[$id_lang] = 'custom_tpl'; // Typ slajdu
                    $slide->description[$id_lang] = '';
                    $slide->image[$id_lang] = ''; // Bez obrazka
                }
                $slide->add();
            }
        }

        // 4. USUWANIE: Jeśli slajd jest w bazie, a pliku nie ma -> Usuń go (opcjonalne, dla porządku)
        foreach ($slides_in_db as $filename => $id_slide) {
            if (!in_array($filename, $files_on_disk)) {
                $slide = new TvcmsHomeSlide($id_slide);
                $slide->delete();
            }
        }
    }
}