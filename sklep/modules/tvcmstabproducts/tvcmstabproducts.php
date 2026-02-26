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

use PrestaShop\PrestaShop\Adapter\BestSales\BestSalesProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

include_once 'classes/tvcmstabproducts_image_upload.class.php';
include_once 'classes/tvcmstabproducts_status.class.php';

class TvcmsTabProducts extends Module
{
    public $js_files = [
        [
            'key' => 'tvcmstabproductfrontJs',
            'src' => 'front.js',
            'priority' => 250,
            'position' => 'bottom',
            'load_theme' => false,
            'attributes' => 'defer',
        ],
    ];

    public function __construct()
    {
        $this->name = 'tvcmstabproducts';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Tab Product Settings');
        $this->description = $this->l('It is use of Tab Setting in ThemeVolty Theme');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
    }

    public function install()
    {
        $this->_clearCache('*');
        $this->installTab();
        // $this->createDefaultData();
        Tools::clearSmartyCache();

        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('displayWrapperTop')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionProductDelete')
            && $this->registerHook('actionOrderStatusPostUpdate')
            && $this->registerHook('displayHome');
    }

    public function createDefaultData()
    {
        $languages = Language::getLanguages();
        $result = [];

        foreach ($languages as $lang) {
            $result['TVCMSTABPRODUCTS_NEW_PROD_TITLE'][$lang['id_lang']] = 'Latest Products';
            $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE'][$lang['id_lang']] = 'Latest Products';
            $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = 'Our New Products';
            $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC'][$lang['id_lang']] = 'Our New Products';
            $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE'][$lang['id_lang']] = 'new_offer_img_1.jpg';
            $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
            $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'new_offer_img_1.jpg');
            $width = $imagedata[0];
            $height = $imagedata[1];
            $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $width;
            $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $height;
            $result['TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE'][$lang['id_lang']] = 'Latest Products';
            $result['TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE'][$lang['id_lang']] = 'Latest Products';

            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE'][$lang['id_lang']] = 'special_offer_img_1.jpg';
            $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
            $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'special_offer_img_1.jpg');
            $width = $imagedata[0];
            $height = $imagedata[1];
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $width;
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $height;
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE'][$lang['id_lang']] = 'Special Products';
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE'][$lang['id_lang']] = 'Special Trend Products';
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = 'Our Special Products';
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC'][$lang['id_lang']] = 'Our Special Products';
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE'][$lang['id_lang']] = 'Special Trend Products';
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE'][$lang['id_lang']] = 'Special Trend Products';

            $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE'][$lang['id_lang']] = 'featured_offer_img_1.jpg';
            $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
            $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'featured_offer_img_1.jpg');
            $width = $imagedata[0];
            $height = $imagedata[1];
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $width;
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $height;
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_TITLE'][$lang['id_lang']] = 'Featured Products';
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE'][$lang['id_lang']] = 'Featured Products';
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = 'Our Products';
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC'][$lang['id_lang']] = 'Our Products';
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE'][$lang['id_lang']] = 'Featured Products';
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE'][$lang['id_lang']] = 'Featured Products';

            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE'][$lang['id_lang']] = 'Best Seller Products';
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE'][$lang['id_lang']] = 'Best Seller Products';
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = 'Our Best Seller';
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC'][$lang['id_lang']] = 'Our Best Seller';
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE'][$lang['id_lang']] = 'best_seller_offer_img_1.jpg';
            $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
            $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'best_seller_offer_img_1.jpg');
            $width = $imagedata[0];
            $height = $imagedata[1];
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $width;
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $height;
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE'][$lang['id_lang']] = 'Best Seller Products';
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE'][$lang['id_lang']] = 'Best Seller Products';

            $result['TVCMSTABPRODUCTS_MAIN_TITLE'][$lang['id_lang']] = 'We Love Trend';
            $result['TVCMSTABPRODUCTS_MAIN_SUB_TITLE'][$lang['id_lang']] = 'Excepteur Sint occaecat';
            $result['TVCMSTABPRODUCTS_MAIN_DESCRIPTION'][$lang['id_lang']] = 'Excepteur Sint occaecat';
            $result['TVCMSTABPRODUCTS_MAIN_IMAGE'][$lang['id_lang']] = 'main_offer_img_1.jpg';
            $ImageSizePath = _MODULE_DIR_ . $this->name . '/views/img/';
            $imagedata = getimagesize(_PS_BASE_URL_ . $ImageSizePath . 'main_offer_img_1.jpg');
            $width = $imagedata[0];
            $height = $imagedata[1];
            $result['TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH'][$lang['id_lang']] = $width;
            $result['TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT'][$lang['id_lang']] = $height;
        }

        $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH'];
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT'];
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC'];
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC', $tmp);

        $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE', $tmp);

        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_NBR', 20);
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_CAT', 2);
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_RAND', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_TAB', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_SHOW', 0);
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_LEFT', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_SIDE', 'left');
        Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_STATUS', 0);

        $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC'];
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH'];
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT'];
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT', $tmp);
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_NBR', 20);
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_TAB', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_SHOW', 0);
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_HOME', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_LEFT', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_RIGHT', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_SIDE', 'left');
        Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_STATUS', 0);

        $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC'];
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT'];
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH'];
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH', $tmp);

        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_NBR', 20);
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_SHOW', 0);
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_SIDE', 'left');
        Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_STATUS', 0);

        $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH'];
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT'];
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC'];
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE', $tmp);
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_NBR', 12);
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB', 0);
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_SHOW', 0);
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT', 1);
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_SIDE', 'left');
        Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_STATUS', 1);

        $tmp = $result['TVCMSTABPRODUCTS_MAIN_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_MAIN_SUB_TITLE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_SUB_TITLE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_MAIN_DESCRIPTION'];
        Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_DESCRIPTION', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_MAIN_IMAGE'];
        Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH'];
        Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH', $tmp);
        $tmp = $result['TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT'];
        Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT', $tmp);
        Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE_SIDE', 'left');
        Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE_STATUS', 0);
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
            $tab->name[$lang['id_lang']] = 'Tab Products';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
        $this->_clearCache('*');
        $this->uninstallTab();
        $this->deleteVariable();
        Tools::clearSmartyCache();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_NBR');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_CAT');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_RAND');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_TAB');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_SHOW');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_SIDE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_STATUS');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_HOME');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_LEFT');
        Configuration::deleteByName('TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT');

        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_IMAGE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_SIDE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_STATUS');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_NBR');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_TAB');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_SHOW');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_HOME');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_LEFT');
        Configuration::deleteByName('TVCMSTABPRODUCTS_NEW_PROD_RIGHT');

        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_SIDE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_STATUS');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_NBR');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_SHOW');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT');
        Configuration::deleteByName('TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT');

        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_SIDE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_STATUS');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_NBR');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_SHOW');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT');
        Configuration::deleteByName('TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT');

        Configuration::deleteByName('TVCMSTABPRODUCTS_MAIN_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_MAIN_SUB_TITLE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_MAIN_DESCRIPTION');
        Configuration::deleteByName('TVCMSTABPRODUCTS_MAIN_IMAGE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH');
        Configuration::deleteByName('TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT');
        Configuration::deleteByName('TVCMSTABPRODUCTS_MAIN_IMAGE_SIDE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_MAIN_IMAGE_SIDE');
        Configuration::deleteByName('TVCMSTABPRODUCTS_MAIN_IMAGE_STATUS');
    }

    public function uninstallTab()
    {
        $id_tab = Tab::getIdFromClassName('Admin' . $this->name);
        $tab = new Tab($id_tab);
        $tab->delete();

        return true;
    }

    public function hookActionProductAdd($params)
    {
        $this->_clearCache('*');
    }

    public function hookActionProductUpdate($params)
    {
        $this->_clearCache('*');
    }

    public function hookActionProductDelete($params)
    {
        $this->_clearCache('*');
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        $this->_clearCache('*');
    }

    public function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        $this->clearCustomSmartyCache('tvcmstabproducts_display_index.tpl');
        $this->clearCustomSmartyCache('tvcmstabproducts_display_index_ajax.tpl');
        $this->clearCustomSmartyCache('tvcmsfeaturedproducts_display_home.tpl');
        $this->clearCustomSmartyCache('tvcmsfeaturedproducts_display_left.tpl');
        $this->clearCustomSmartyCache('tvcmsfeaturedproducts_display_right.tpl');
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

        if (Tools::isSubmit('submitTvcmsSampleinstall') && '1' == Tools::getValue('tvinstalldata')) {
            $this->createDefaultData();
        }
        $this->context->smarty->assign('tab_number', '#fieldset_0');
        $message = $this->postProcess();

        $output = $message . '<div class="tvcmsadmintab-product">'
            . $this->display(__FILE__, 'views/templates/admin/index.tpl')
            . $this->renderForm()
             . '</div>';

        return $output;
    }

    public function postProcess()
    {
        $message = '';
        $result = [];
        if (Tools::getIsset('submitTvcmsCustomFormCustomSetting') && '0' == Tools::getValue('tvinstalldata')) {
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $new_file = '';
                if (isset($_FILES['TVCMSTABPRODUCTS_MAIN_IMAGE_' . $lang['id_lang']])) {
                    $new_file = $_FILES['TVCMSTABPRODUCTS_MAIN_IMAGE_' . $lang['id_lang']];
                }
                $old_file = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE', $lang['id_lang']);
                $old_width = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH', $lang['id_lang']);
                $old_height = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT', $lang['id_lang']);
                if (!empty($new_file['name'])) {
                    $this->tvcmsobj = new TvcmsTabProductsImageUpload();
                    $return = $this->tvcmsobj->imageUploading($new_file, $old_file);
                    if ($return['success']) {
                        $result['TVCMSTABPRODUCTS_MAIN_IMAGE'][$lang['id_lang']] = $return['name'];
                        $result['TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH'][$lang['id_lang']] = $return['width'];
                        $result['TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT'][$lang['id_lang']] = $return['height'];
                    } else {
                        $message .= $return['error'];
                        $result['TVCMSTABPRODUCTS_MAIN_IMAGE'][$lang['id_lang']] = $old_file;
                        $result['TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH'][$lang['id_lang']] = $old_width;
                        $result['TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT'][$lang['id_lang']] = $old_height;
                    }
                } else {
                    $result['TVCMSTABPRODUCTS_MAIN_IMAGE'][$lang['id_lang']] = $old_file;
                    $result['TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH'][$lang['id_lang']] = $old_width;
                    $result['TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT'][$lang['id_lang']] = $old_height;
                }

                $tmp = Tools::getValue('TVCMSTABPRODUCTS_MAIN_TITLE_' . $lang['id_lang']);
                $result['TVCMSTABPRODUCTS_MAIN_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSTABPRODUCTS_MAIN_SUB_TITLE_' . $lang['id_lang']);
                $result['TVCMSTABPRODUCTS_MAIN_SUB_TITLE'][$lang['id_lang']] = $tmp;

                $tmp = Tools::getValue('TVCMSTABPRODUCTS_MAIN_DESCRIPTION_' . $lang['id_lang']);
                $result['TVCMSTABPRODUCTS_MAIN_DESCRIPTION'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSTABPRODUCTS_MAIN_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_MAIN_DESCRIPTION'];
            Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_DESCRIPTION', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_MAIN_SUB_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_SUB_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_MAIN_IMAGE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH'];
            Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT'];
            Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT', $tmp);
            $tmp = Tools::getValue('TVCMSTABPRODUCTS_MAIN_IMAGE_SIDE');
            Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE_SIDE', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_MAIN_IMAGE_STATUS');
            Configuration::updateValue('TVCMSTABPRODUCTS_MAIN_IMAGE_STATUS', $tmp);

            $this->clearCustomSmartyCache('tvcmstabproducts_display_index.tpl');

            $this->context->smarty->assign('tab_number', '#fieldset_0');
            $message .= $this->displayConfirmation($this->l('Record Save Custom Product Setting Successfully'));
        }

        if (Tools::getIsset('submitTvcmsTabFeaturedProdSettings') && '0' == Tools::getValue('tvinstalldata')) {
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $new_file = '';
                if (isset($_FILES['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_' . $lang['id_lang']])) {
                    $new_file = $_FILES['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_' . $lang['id_lang']];
                    $old_width = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH', $lang['id_lang']);
                    $old_height = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT', $lang['id_lang']);
                }
                $old_file = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE', $lang['id_lang']);
                if (!empty($new_file['name'])) {
                    $this->tvcmsobj = new TvcmsTabProductsImageUpload();
                    $return = $this->tvcmsobj->imageUploading($new_file, $old_file);
                    if ($return['success']) {
                        $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE'][$lang['id_lang']] = $return['name'];
                        $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $return['width'];
                        $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $return['height'];
                    } else {
                        $message .= $return['error'];
                        $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE'][$lang['id_lang']] = $old_file;
                        $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $old_width;
                        $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $old_height;
                    }
                } else {
                    $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE'][$lang['id_lang']] = $old_file;
                    $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $old_width;
                    $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $old_height;
                }

                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_FEATURED_PROD_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_NBR');
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_NBR', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_CAT');
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_CAT', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_RAND');
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_RAND', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_TAB');
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_TAB', $tmp);

            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_SHOW');
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_SHOW', $tmp);

            $tmp = Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_SIDE');
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_SIDE', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_STATUS');
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_STATUS', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME');
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_HOME', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_LEFT');
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_LEFT', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT');
            Configuration::updateValue('TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT', $tmp);

            $this->clearCustomSmartyCache('tvcmsfeaturedproducts_display_home.tpl');
            $this->clearCustomSmartyCache('tvcmsfeaturedproducts_display_left.tpl');
            $this->clearCustomSmartyCache('tvcmsfeaturedproducts_display_right.tpl');

            $this->context->smarty->assign('tab_number', '#fieldset_1_1');
            $message .= $this->displayConfirmation($this->l('Record Save Featured Product Setting Successfully'));
        }

        if (Tools::getIsset('submitTvcmsTabNewProdSettings') && '0' == Tools::getValue('tvinstalldata')) {
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $new_file = '';
                if (isset($_FILES['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_' . $lang['id_lang']])) {
                    $new_file = $_FILES['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_' . $lang['id_lang']];
                    $old_width = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH', $lang['id_lang']);
                    $old_height = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT', $lang['id_lang']);
                }
                $old_file = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE', $lang['id_lang']);
                if (!empty($new_file['name'])) {
                    $this->tvcmsobj = new TvcmsTabProductsImageUpload();
                    $return = $this->tvcmsobj->imageUploading($new_file, $old_file);
                    if ($return['success']) {
                        $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE'][$lang['id_lang']] = $return['name'];
                        $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $return['width'];
                        $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $return['height'];
                    } else {
                        $message .= $return['error'];
                        $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE'][$lang['id_lang']] = $old_file;
                        $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $old_width;
                        $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $old_height;
                    }
                } else {
                    $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE'][$lang['id_lang']] = $old_file;
                    $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $old_width;
                    $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $old_height;
                }

                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_NEW_PROD_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT'];
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT', $tmp);
            $tmp = Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_SIDE');
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_SIDE', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_STATUS');
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_STATUS', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_NBR');
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_NBR', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_TAB');
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_TAB', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_SHOW');
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_SHOW', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_HOME');
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_HOME', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_LEFT');
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_LEFT', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_NEW_PROD_RIGHT');
            Configuration::updateValue('TVCMSTABPRODUCTS_NEW_PROD_RIGHT', $tmp);
            Configuration::updateValue('PS_NB_DAYS_NEW_PRODUCT', (int) Tools::getValue('PS_NB_DAYS_NEW_PRODUCT'));

            $this->clearCustomSmartyCache('tvcmsnewproducts_display_home.tpl');
            $this->clearCustomSmartyCache('tvcmsnewproducts_display_left.tpl');
            $this->clearCustomSmartyCache('tvcmsnewproducts_display_right.tpl');

            $this->context->smarty->assign('tab_number', '#fieldset_2_2');
            $message .= $this->displayConfirmation($this->l('Record Save New Product Setting Successfully'));
        }

        if (Tools::getIsset('submitTvcmsBestSellerProdTabSettings') && '0' == Tools::getValue('tvinstalldata')) {
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $new_file = '';
                if (isset($_FILES['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_' . $lang['id_lang']])) {
                    $new_file = $_FILES['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_' . $lang['id_lang']];
                }
                $old_file = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE', $lang['id_lang']);
                $old_width = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH', $lang['id_lang']);
                $old_height = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT', $lang['id_lang']);
                if (!empty($new_file['name'])) {
                    $this->tvcmsobj = new TvcmsTabProductsImageUpload();
                    $return = $this->tvcmsobj->imageUploading($new_file, $old_file);
                    if ($return['success']) {
                        $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE'][$lang['id_lang']] = $return['name'];
                        $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $return['width'];
                        $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $return['height'];
                    } else {
                        $message .= $return['error'];
                        $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE'][$lang['id_lang']] = $old_file;
                        $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $old_width;
                        $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $old_height;
                    }
                } else {
                    $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE'][$lang['id_lang']] = $old_file;
                    $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $old_width;
                    $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $old_height;
                }
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT'];
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT', $tmp);
            $tmp = Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_SIDE');
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_SIDE', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_STATUS');
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_STATUS', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_NBR');
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_NBR', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB');
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB', $tmp);

            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_SHOW');
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_SHOW', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME');
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT');
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT');
            Configuration::updateValue('TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT', $tmp);

            $this->clearCustomSmartyCache('tvcmsbestsellerproducts_display_home.tpl');
            $this->clearCustomSmartyCache('tvcmsbestsellerproducts_display_left.tpl');
            $this->clearCustomSmartyCache('tvcmsbestsellerproducts_display_right.tpl');

            $this->context->smarty->assign('tab_number', '#fieldset_3_3');
            $message .= $this->displayConfirmation($this->l('Record Save Best Seller Product Setting Successfully'));
        }

        if (Tools::getIsset('submitTvcmsTabSpecialProdSettings') && '0' == Tools::getValue('tvinstalldata')) {
            $languages = Language::getLanguages();
            foreach ($languages as $lang) {
                $new_file = '';
                if (isset($_FILES['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_' . $lang['id_lang']])) {
                    $new_file = $_FILES['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_' . $lang['id_lang']];
                }
                $old_file = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE', $lang['id_lang']);
                $old_width = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH', $lang['id_lang']);
                $old_height = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT', $lang['id_lang']);
                if (!empty($new_file['name'])) {
                    $obj_image = new TvcmsTabProductsImageUpload();
                    $return = $obj_image->imageUploading($new_file, $old_file);
                    if ($return['success']) {
                        $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE'][$lang['id_lang']] = $return['name'];
                        $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $return['width'];
                        $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $return['height'];
                    } else {
                        $message .= $return['error'];
                        $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE'][$lang['id_lang']] = $old_file;
                        $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $old_width;
                        $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $old_height;
                    }
                } else {
                    $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE'][$lang['id_lang']] = $old_file;
                    $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $old_width;
                    $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $old_height;
                }
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE'][$lang['id_lang']] = $tmp;
                $tmp = pSQL(Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE_' . $lang['id_lang']));
                $result['TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE'][$lang['id_lang']] = $tmp;
            }

            $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH', $tmp);
            $tmp = $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT'];
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_STATUS');
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_STATUS', $tmp);
            $tmp = Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_SIDE');
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_SIDE', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_NBR');
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_NBR', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB');
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_SHOW');
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_SHOW', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME');
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT');
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT', $tmp);
            $tmp = (int) Tools::getValue('TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT');
            Configuration::updateValue('TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT', $tmp);

            $this->clearCustomSmartyCache('tvcmsspecialproducts_display_home.tpl');
            $this->clearCustomSmartyCache('tvcmsspecialproducts_display_left.tpl');
            $this->clearCustomSmartyCache('tvcmsspecialproducts_display_right.tpl');

            $this->context->smarty->assign('tab_number', '#fieldset_4_4');
            $message .= $this->displayConfirmation($this->l('Record Save Special Product Setting Successfully'));
        }

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

        $tvcms_obj = new TvcmsTabProductsStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        if ($show_fields['main_status']) {
            $form[] = $this->tvcmsCustomFormCustomSetting();
        }

        $form[] = $this->tvcmsCustomFormFeatureProduct();
        $form[] = $this->tvcmsCustomFormNewProduct();
        $form[] = $this->tvcmsCustomFormBestSellerProduct();
        $form[] = $this->tvcmsCustomFormSpecialProduct();

        return $helper->generateForm($form);
    }

    protected function tvcmsCustomFormCustomSetting()
    {
        $tvcms_obj = new TvcmsTabProductsStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        $input[] = [
                'col' => 12,
                'type' => 'BtnInstallData',
                'name' => 'BtnInstallData',
                'label' => '',
            ];
        if ($show_fields['main_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_MAIN_TITLE',
                        'label' => $this->l('Main Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['main_sub_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_MAIN_SUB_TITLE',
                        'label' => $this->l('Sub Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['main_description']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_MAIN_DESCRIPTION',
                        'label' => $this->l('Description'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['main_image']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'main_file',
                        'name' => 'TVCMSTABPRODUCTS_MAIN_IMAGE',
                        'label' => $this->l('Main Banner'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['main_image_side']) {
            $input[] = [
                        'type' => 'select',
                        'label' => $this->l('Display Banner Side'),
                        'name' => 'TVCMSTABPRODUCTS_MAIN_IMAGE_SIDE',
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 'left',
                                    'name' => 'Left Side',
                                ],
                                [
                                    'id_option' => 'right',
                                    'name' => 'Right Side',
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ];
        }

        if ($show_fields['main_image_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display Banner'),
                        'name' => 'TVCMSTABPRODUCTS_MAIN_IMAGE_STATUS',
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
                'title' => $this->l('Custom Tab Setting'),
                'icon' => 'icon-cogs',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsCustomFormCustomSetting',
                ],
            ],
        ];
    }

    protected function tvcmsCustomFormFeatureProduct()
    {
        $tvcms_obj = new TvcmsTabProductsStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        if ($show_fields['featured_prod']['tab_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_TITLE',
                        'label' => $this->l('Tab Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['featured_prod']['home_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE',
                        'label' => $this->l('Home Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['featured_prod']['home_sub_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE',
                        'label' => $this->l('Home Sub Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['featured_prod']['home_description']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC',
                        'label' => $this->l('Home Description'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['featured_prod']['left_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE',
                        'label' => $this->l('Left Column Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['featured_prod']['right_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE',
                        'label' => $this->l('Right Column Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['featured_prod']['home_image']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'featured_file',
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE',
                        'label' => $this->l('Home Featured Products Banner'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['featured_prod']['home_image_side']) {
            $input[] = [
                        'type' => 'select',
                        'label' => $this->l('Display Banner Side'),
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_SIDE',
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 'left',
                                    'name' => 'Left Side',
                                ],
                                [
                                    'id_option' => 'right',
                                    'name' => 'Right Side',
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ];
        }

        if ($show_fields['featured_prod']['home_image_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display Banner'),
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_STATUS',
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

        if ($show_fields['featured_prod']['num_of_prod']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_NBR',
                        'label' => $this->l('Number Of New Product'),
                    ];
        }

        if ($show_fields['featured_prod']['prod_cat']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_CAT',
                        'label' => $this->l('Category from which to pick products to be displayed'),
                    ];
        }

        if ($show_fields['featured_prod']['random_prod']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display Randomly Product'),
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_RAND',
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

        if ($show_fields['featured_prod']['display_in_tab']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Tab'),
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_TAB',
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

        if ($show_fields['featured_prod']['display_in_home']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Home Page'),
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_HOME',
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

        if ($show_fields['featured_prod']['display_in_left']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Left Column'),
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_LEFT',
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

        if ($show_fields['featured_prod']['display_in_right']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Right Column'),
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT',
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

        if ($show_fields['featured_prod']['display_all']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Show In All Page'),
                        'name' => 'TVCMSTABPRODUCTS_FEATURED_PROD_SHOW',
                        'desc' => 'Note: Yes status means show in all pages, No means show in only homepage',
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
                'title' => $this->l('Feature Product Tab Setting'),
                'icon' => 'icon-cogs',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsTabFeaturedProdSettings',
                ],
            ],
        ];
    }

    protected function tvcmsCustomFormNewProduct()
    {
        $tvcms_obj = new TvcmsTabProductsStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        if ($show_fields['new_prod']['tab_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_TITLE',
                        'label' => $this->l('Tab Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['new_prod']['home_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE',
                        'label' => $this->l('Home Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['new_prod']['home_sub_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE',
                        'label' => $this->l('Home Sub Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['new_prod']['home_description']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC',
                        'label' => $this->l('Home Description'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['new_prod']['left_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE',
                        'label' => $this->l('Left Column Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['new_prod']['right_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE',
                        'label' => $this->l('Right Column Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['new_prod']['home_image']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'new_file',
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_IMAGE',
                        'label' => $this->l('Home New Products Banner'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['new_prod']['home_image_side']) {
            $input[] = [
                        'type' => 'select',
                        'label' => $this->l('Display Banner Side'),
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_IMAGE_SIDE',
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 'left',
                                    'name' => 'Left Side',
                                ],
                                [
                                    'id_option' => 'right',
                                    'name' => 'Right Side',
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ];
        }

        if ($show_fields['new_prod']['home_image_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display Banner'),
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_IMAGE_STATUS',
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

        if ($show_fields['new_prod']['num_of_prod']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_NBR',
                        'label' => $this->l('Number Of Product'),
                    ];
        }

        if ($show_fields['new_prod']['num_of_days']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'PS_NB_DAYS_NEW_PRODUCT',
                        'label' => $this->l('Number of days for which the product is considered \'new\''),
                    ];
        }

        if ($show_fields['new_prod']['display_in_tab']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Tab'),
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_TAB',
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
        if ($show_fields['new_prod']['display_in_all']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Show In All Page'),
                        'desc' => $this->l('Note: Yes status means show in all pages, No means show in only homepage'),
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_SHOW',
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

        if ($show_fields['new_prod']['display_in_home']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Home Page'),
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_HOME',
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

        if ($show_fields['new_prod']['display_in_left']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Left Column'),
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_LEFT',
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

        if ($show_fields['new_prod']['display_in_right']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Right Column'),
                        'name' => 'TVCMSTABPRODUCTS_NEW_PROD_RIGHT',
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
                'title' => $this->l('New Product Tab Setting'),
                'icon' => 'icon-cogs',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsTabNewProdSettings',
                ],
            ],
        ];
    }

    protected function tvcmsCustomFormBestSellerProduct()
    {
        $tvcms_obj = new TvcmsTabProductsStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        if ($show_fields['best_seller_prod']['tab_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE',
                        'label' => $this->l('Tab Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['best_seller_prod']['home_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE',
                        'label' => $this->l('Home Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['best_seller_prod']['home_sub_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE',
                        'label' => $this->l('Home Sub Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['best_seller_prod']['home_description']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC',
                        'label' => $this->l('Home Description'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['best_seller_prod']['left_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE',
                        'label' => $this->l('Left Column Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['best_seller_prod']['right_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE',
                        'label' => $this->l('Right Column Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['best_seller_prod']['home_image']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'best_seller_file',
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE',
                        'label' => $this->l('Home Best Seller Products Banner'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['best_seller_prod']['home_image_side']) {
            $input[] = [
                        'type' => 'select',
                        'label' => $this->l('Display Banner Side'),
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_SIDE',
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 'left',
                                    'name' => 'Left Side',
                                ],
                                [
                                    'id_option' => 'right',
                                    'name' => 'Right Side',
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ];
        }

        if ($show_fields['best_seller_prod']['home_image_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display Banner'),
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_STATUS',
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

        if ($show_fields['best_seller_prod']['num_of_prod']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_NBR',
                        'label' => $this->l('Number Of Product'),
                    ];
        }

        if ($show_fields['best_seller_prod']['display_in_tab']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Tab'),
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB',
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

        if ($show_fields['best_seller_prod']['display_in_all']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Show In All Page'),
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_SHOW',
                        'desc' => 'Note: Yes status means show in all pages, No means show in only homepage',
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

        if ($show_fields['best_seller_prod']['display_in_home']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Home Page'),
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME',
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

        if ($show_fields['best_seller_prod']['display_in_left']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Left Column'),
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT',
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

        if ($show_fields['best_seller_prod']['display_in_right']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Right Column'),
                        'name' => 'TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT',
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
                'title' => $this->l('Best Seller Tab Setting'),
                'icon' => 'icon-cogs',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsBestSellerProdTabSettings',
                ],
            ],
        ];
    }

    protected function tvcmsCustomFormSpecialProduct()
    {
        $tvcms_obj = new TvcmsTabProductsStatus();
        $show_fields = $tvcms_obj->fieldStatusInformation();
        $input = [];

        if ($show_fields['special_prod']['tab_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE',
                        'label' => $this->l('Tab Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['special_prod']['home_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE',
                        'label' => $this->l('Home Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['special_prod']['home_sub_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE',
                        'label' => $this->l('Home sub Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['special_prod']['home_description']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC',
                        'label' => $this->l('Home Description'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['special_prod']['left_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE',
                        'label' => $this->l('Left Column Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['special_prod']['right_title']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE',
                        'label' => $this->l('Right Column Title'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['special_prod']['home_image']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'special_file',
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE',
                        'label' => $this->l('Special Products Banner'),
                        'lang' => true,
                    ];
        }

        if ($show_fields['special_prod']['home_image_side']) {
            $input[] = [
                        'type' => 'select',
                        'label' => $this->l('Display Banner Side'),
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_SIDE',
                        'options' => [
                            'query' => [
                                [
                                    'id_option' => 'left',
                                    'name' => 'Left Side',
                                ],
                                [
                                    'id_option' => 'right',
                                    'name' => 'Right Side',
                                ],
                            ],
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                    ];
        }

        if ($show_fields['special_prod']['home_image_status']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display Banner'),
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_STATUS',
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

        if ($show_fields['special_prod']['num_of_prod']) {
            $input[] = [
                        'col' => 6,
                        'type' => 'text',
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_NBR',
                        'label' => $this->l('Number Of Product'),
                    ];
        }

        if ($show_fields['special_prod']['display_in_tab']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Tab'),
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_TAB',
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

        if ($show_fields['special_prod']['display_in_home']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Home Page'),
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_HOME',
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

        if ($show_fields['special_prod']['display_in_left']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Left Column'),
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT',
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

        if ($show_fields['special_prod']['display_in_right']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Display In Right Column'),
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT',
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

        if ($show_fields['special_prod']['display_all']) {
            $input[] = [
                        'type' => 'switch',
                        'label' => $this->l('Show In All Page'),
                        'name' => 'TVCMSTABPRODUCTS_SPECIAL_PROD_SHOW',
                        'desc' => 'Note: Yes status means show in all pages, No means show in only homepage',
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
                'title' => $this->l('Special Product Tab Setting'),
                'icon' => 'icon-cogs',
                ],
                'input' => $input,
                'submit' => [
                    'title' => $this->l('Save'),
                    'name' => 'submitTvcmsTabSpecialProdSettings',
                ],
            ],
        ];
    }

    public function getConfigFormValues()
    {
        $languages = Language::getLanguages();
        $result = [];
        foreach ($languages as $lang) {
            $tmp = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_NEW_PROD_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT'][$lang['id_lang']] = $tmp;

            $tmp = Configuration::get('TVCMSTABPRODUCTS_MAIN_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_MAIN_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_MAIN_SUB_TITLE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_MAIN_SUB_TITLE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_MAIN_DESCRIPTION', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_MAIN_DESCRIPTION'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_MAIN_IMAGE'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH'][$lang['id_lang']] = $tmp;
            $tmp = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT', $lang['id_lang']);
            $result['TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT'][$lang['id_lang']] = $tmp;
        }

        $path = _MODULE_DIR_ . $this->name . '/views/img/';
        $this->context->smarty->assign('path', $path);

        $special_prod_img_side = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_SIDE');
        $special_prod_img_status = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_STATUS');

        $featured_prod_home_sub_title = $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE'];
        $featured_prod_img_side = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_SIDE');
        $featured_prod_img_status = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_STATUS');

        $best_seller_prod_home_title = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE'];
        $best_seller_prod_home_sub_title = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE'];
        $best_seller_prod_right_title = $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE'];
        $best_seller_prod_img_side = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_SIDE');
        $best_seller_prod_img_status = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_STATUS');
        $best_seller_prod_right = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT');

        return [
            'TVCMSTABPRODUCTS_NEW_PROD_TITLE' => $result['TVCMSTABPRODUCTS_NEW_PROD_TITLE'],
            'TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE' => $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_TITLE'],
            'TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE' => $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_SUB_TITLE'],
            'TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC' => $result['TVCMSTABPRODUCTS_NEW_PROD_HOME_DESC'],
            'TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE' => $result['TVCMSTABPRODUCTS_NEW_PROD_LEFT_TITLE'],
            'TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE' => $result['TVCMSTABPRODUCTS_NEW_PROD_RIGHT_TITLE'],
            'TVCMSTABPRODUCTS_NEW_PROD_IMAGE' => $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE'],
            'TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH' => $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_WIDTH'],
            'TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT' => $result['TVCMSTABPRODUCTS_NEW_PROD_IMAGE_HEIGHT'],
            'TVCMSTABPRODUCTS_NEW_PROD_IMAGE_SIDE' => Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_SIDE'),
            'TVCMSTABPRODUCTS_NEW_PROD_IMAGE_STATUS' => Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_IMAGE_STATUS'),
            'TVCMSTABPRODUCTS_NEW_PROD_NBR' => Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_NBR'),
            'TVCMSTABPRODUCTS_NEW_PROD_TAB' => Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_TAB'),
            'TVCMSTABPRODUCTS_NEW_PROD_SHOW' => Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_SHOW'),
            'TVCMSTABPRODUCTS_NEW_PROD_HOME' => Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_HOME'),
            'TVCMSTABPRODUCTS_NEW_PROD_LEFT' => Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_LEFT'),
            'TVCMSTABPRODUCTS_NEW_PROD_RIGHT' => Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_RIGHT'),
            'PS_NB_DAYS_NEW_PRODUCT' => Configuration::get('PS_NB_DAYS_NEW_PRODUCT'),

            'TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE' => $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE'],
            'TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH' => $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_WIDTH'],
            'TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT' => $result['TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_HEIGHT'],
            'TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_SIDE' => $special_prod_img_side,
            'TVCMSTABPRODUCTS_SPECIAL_PROD_IMAGE_STATUS' => $special_prod_img_status,
            'TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE' => $result['TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE'],
            'TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE' => $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_TITLE'],
            'TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE' => $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_SUB_TITLE'],
            'TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC' => $result['TVCMSTABPRODUCTS_SPECIAL_PROD_HOME_DESC'],
            'TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE' => $result['TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT_TITLE'],
            'TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE' => $result['TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT_TITLE'],
            'TVCMSTABPRODUCTS_SPECIAL_PROD_NBR' => Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_NBR'),
            'TVCMSTABPRODUCTS_SPECIAL_PROD_TAB' => Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB'),
            'TVCMSTABPRODUCTS_SPECIAL_PROD_SHOW' => Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_SHOW'),
            'TVCMSTABPRODUCTS_SPECIAL_PROD_HOME' => Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_HOME'),
            'TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT' => Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_LEFT'),
            'TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT' => Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_RIGHT'),

            'TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE' => $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE'],
            'TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH' => $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_WIDTH'],
            'TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT' => $result['TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_HEIGHT'],
            'TVCMSTABPRODUCTS_FEATURED_PROD_TITLE' => $result['TVCMSTABPRODUCTS_FEATURED_PROD_TITLE'],
            'TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE' => $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_TITLE'],
            'TVCMSTABPRODUCTS_FEATURED_PROD_HOME_SUB_TITLE' => $featured_prod_home_sub_title,
            'TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC' => $result['TVCMSTABPRODUCTS_FEATURED_PROD_HOME_DESC'],
            'TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE' => $result['TVCMSTABPRODUCTS_FEATURED_PROD_LEFT_TITLE'],
            'TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE' => $result['TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT_TITLE'],
            'TVCMSTABPRODUCTS_FEATURED_PROD_NBR' => Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_NBR'),
            'TVCMSTABPRODUCTS_FEATURED_PROD_CAT' => Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_CAT'),
            'TVCMSTABPRODUCTS_FEATURED_PROD_RAND' => Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_RAND'),
            'TVCMSTABPRODUCTS_FEATURED_PROD_TAB' => Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_TAB'),
            'TVCMSTABPRODUCTS_FEATURED_PROD_SHOW' => Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_SHOW'),
            'TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_SIDE' => $featured_prod_img_side,
            'TVCMSTABPRODUCTS_FEATURED_PROD_IMAGE_STATUS' => $featured_prod_img_status,
            'TVCMSTABPRODUCTS_FEATURED_PROD_HOME' => Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_HOME'),
            'TVCMSTABPRODUCTS_FEATURED_PROD_LEFT' => Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_LEFT'),
            'TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT' => Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_RIGHT'),

            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE' => $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE'],
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_TITLE' => $best_seller_prod_home_title,
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_SUB_TITLE' => $best_seller_prod_home_sub_title,
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC' => $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME_DESC'],
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE' => $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT_TITLE'],
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT_TITLE' => $best_seller_prod_right_title,
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE' => $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE'],
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH' => $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_WIDTH'],
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT' => $result['TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_HEIGHT'],
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_SIDE' => $best_seller_prod_img_side,
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_IMAGE_STATUS' => $best_seller_prod_img_status,
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_NBR' => Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_NBR'),
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB' => Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB'),
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_SHOW' => Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_SHOW'),
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME' => Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_HOME'),
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT' => Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_LEFT'),
            'TVCMSTABPRODUCTS_BEST_SELLER_PROD_RIGHT' => $best_seller_prod_right,

            'TVCMSTABPRODUCTS_MAIN_TITLE' => $result['TVCMSTABPRODUCTS_MAIN_TITLE'],
            'TVCMSTABPRODUCTS_MAIN_SUB_TITLE' => $result['TVCMSTABPRODUCTS_MAIN_SUB_TITLE'],
            'TVCMSTABPRODUCTS_MAIN_DESCRIPTION' => $result['TVCMSTABPRODUCTS_MAIN_DESCRIPTION'],
            'TVCMSTABPRODUCTS_MAIN_SUB_TITLE' => $result['TVCMSTABPRODUCTS_MAIN_SUB_TITLE'],
            'TVCMSTABPRODUCTS_MAIN_IMAGE' => $result['TVCMSTABPRODUCTS_MAIN_IMAGE'],
            'TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH' => $result['TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH'],
            'TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT' => $result['TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT'],
            'TVCMSTABPRODUCTS_MAIN_IMAGE_SIDE' => Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE_SIDE'),
            'TVCMSTABPRODUCTS_MAIN_IMAGE_STATUS' => Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE_STATUS'),
        ];
    }

    public function registerJs()
    {
        if (isset($this->js_files) && !empty($this->js_files)) {
            foreach ($this->js_files as $js_file) {
                if (isset($js_file['key'])
                        && !empty($js_file['key'])
                        && isset($js_file['src'])
                        && !empty($js_file['src'])
                ) {
                    $tmp = $js_file['position'];
                    $position = (isset($js_file['position']) && !empty($tmp)) ? $js_file['position'] : 'bottom';
                    $tmp = $js_file['priority'];
                    $priority = (isset($js_file['priority']) && !empty($tmp)) ? $js_file['priority'] : 50;
                    $tmp = $js_file['attributes'];
                    $attributes = (isset($tmp) && !empty($tmp)) ? $tmp : '';

                    if (isset($js_file['load_theme']) && (true == $js_file['load_theme'])) {
                        $this->context->controller->registerJavascript($js_file['key'], _THEME_DIR_
                             . 'assets/js/' . $js_file['src'], ['position' => $position, 'priority' => $priority, 'attributes' => $attributes]);
                    } else {
                        $this->context->controller->registerJavascript($js_file['key'], 'modules/' . $this->name
                             . '/views/js/' . $js_file['src'], ['position' => $position, 'priority' => $priority, 'attributes' => $attributes]);
                    }
                }
            }
        }

        return true;
    }

    public function hookDisplayBackOfficeHeader()
    {
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    public function hookdisplayHeader()
    {
        $ImgDir = _THEME_PROD_DIR_;
        $this->context->smarty->assign('ImgDir', $ImgDir);
        $iso_code = $this->context->language->iso_code;
        $this->context->smarty->assign('iso_code', $iso_code);

        $AllStatus = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_SHOW');
        $this->context->smarty->assign('AllStatus', $AllStatus);

        $AllStatusFeature = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_SHOW');
        $this->context->smarty->assign('AllStatusFeature', $AllStatusFeature);

        $AllStatusSpecial = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_SHOW');
        $this->context->smarty->assign('AllStatusSpecial', $AllStatusSpecial);

        $AllStatusbest = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_SHOW');
        $this->context->smarty->assign('AllStatusbest', $AllStatusbest);

        $tmp = $this->context->link->getModuleLink('tvcmstabproducts', 'default');
        Media::addJsDef(['gettvcmstabproductslink' => $tmp]);
        // $this->registerJs();
        // this->context->controller->unregisterJavascript('tvcmstabproductfrontJs');
        $this->context->controller->addJS($this->_path . 'views/js/front.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function showFrontData($status_config)
    {
        $tab_product_list = [];
        // display Feature Product
        if ('feature_prod' == $status_config) {
            $status = (int) Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_TAB');
            if ($status) {
                $product_info = $this->displayFeaturedProducts();
                $total_prod = count($product_info['product_list']);
                if ($total_prod > 0) {
                    $tab_product_list[] = $product_info;
                }
            }
        } elseif ('new_prod' == $status_config) {
            // display New Product
            $status = (int) Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_TAB');
            if ($status) {
                $product_info = $this->displayNewProducts();
                $total_prod = count($product_info['product_list']);
                if ($total_prod > 0) {
                    $tab_product_list[] = $product_info;
                }
            }
        } elseif ('best_prod' == $status_config) {
            // display Best Seller Product
            $status = (int) Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB');
            if ($status) {
                $product_info = $this->displayBestSellers();
                $total_prod = count($product_info['product_list']);
                if ($total_prod > 0) {
                    $tab_product_list[] = $product_info;
                }
            }
        } elseif ('special_prod' == $status_config) {
            // display Special Product
            $status = (int) Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB');
            if ($status) {
                $product_info = $this->displaySpecialProducts();
                $total_prod = count($product_info['product_list']);
                if ($total_prod > 0) {
                    $tab_product_list[] = $product_info;
                }
            }
        }

        return $tab_product_list;
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

        $main_heading['main_image_side'] = $main_heading_data['image_side'];
        $main_heading['main_image_status'] = $main_heading_data['image_status'];

        if (!$main_heading['main_title']
            && !$main_heading['main_sub_title']
            && !$main_heading['main_description']
            && !$main_heading['main_image']) {
            $main_heading['main_status'] = false;
        }

        return $main_heading;
    }

    public function showFrontSideResult($status_config)
    {
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;

        $tvcms_obj = new TvcmsTabProductsStatus();
        $all_prod_status = $tvcms_obj->fieldStatusInformation();

        $main_heading = [];
        $main_heading['main_status'] = $all_prod_status['main_status'];
        $main_heading['main_title'] = $all_prod_status['main_title'];
        $main_heading['main_sub_title'] = $all_prod_status['main_sub_title'];
        $main_heading['main_description'] = $all_prod_status['main_description'];
        $main_heading['main_image'] = $all_prod_status['main_image'];
        $main_heading['main_image_side'] = $all_prod_status['main_image_side'];
        $main_heading['main_image_status'] = $all_prod_status['main_image_status'];

        if ($main_heading['main_status']) {
            $main_heading_data = [];

            $main_heading_data['title'] = Configuration::get('TVCMSTABPRODUCTS_MAIN_TITLE', $id_lang);
            $main_heading_data['short_desc'] = Configuration::get('TVCMSTABPRODUCTS_MAIN_SUB_TITLE', $id_lang);
            $main_heading_data['desc'] = Configuration::get('TVCMSTABPRODUCTS_MAIN_DESCRIPTION', $id_lang);
            $main_heading_data['image'] = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE', $id_lang);
            $main_heading_data['width'] = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE_WIDTH', $id_lang);
            $main_heading_data['height'] = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE_HEIGHT', $id_lang);
            $main_heading_data['image_side'] = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE_SIDE');
            $main_heading_data['image_status'] = Configuration::get('TVCMSTABPRODUCTS_MAIN_IMAGE_STATUS');

            $main_heading = $this->getArrMainTitle($main_heading, $main_heading_data);
            $main_heading['data'] = $main_heading_data;
        }

        $disArrResult = [];
        $disArrResult['data'] = $this->showFrontData($status_config);
        $disArrResult['status'] = empty($disArrResult['data']) ? false : true;
        $disArrResult['path'] = _MODULE_DIR_ . $this->name . '/views/img/';
        $disArrResult['id_lang'] = $id_lang;
        $this->context->smarty->assign('main_heading', $main_heading);
        $this->context->smarty->assign('dis_arr_result', $disArrResult);

        return $disArrResult['status'] ? true : false;
    }

    public function hookdisplayHome()
    {
        $StatusSpecialTabSetting = (int) Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB');
        $StatusNewTabSetting = (int) Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_TAB');
        $StatusBestTabSetting = (int) Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB');
        $StatuFeatureTabSetting = (int) Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_TAB');

        if ($StatusSpecialTabSetting || $StatusNewTabSetting || $StatusBestTabSetting || $StatuFeatureTabSetting) {
            if (!Cache::isStored('tvcmstabproducts_display_index.tpl')) {
                // $result = $this->showFrontSideResult();
                $output = $this->display(__FILE__, 'views/templates/front/display_index.tpl');
                Cache::store('tvcmstabproducts_display_index.tpl', $output);
            }

            return Cache::retrieve('tvcmstabproducts_display_index.tpl');
        } else {
            return '';
        }
    }

    public function hookdisplayWrapperTop()
    {
        return $this->hookdisplayHome();
    }

    public function featurecheck()
    {
        if (1 == (int) Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_TAB')) {
            $output = [];
            $cookie = Context::getContext()->cookie;
            $id_lang = $cookie->id_lang;
            $output['tab_name'] = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_TITLE', $id_lang);
            $output['tab_name_id'] = 'tvcmstab-featured-product';
            $output['tab_name_class_slider'] = 'tvtab-featured-product';
            $output['tab_name_class_pagination'] = 'tvtab-featured';
            $output['status_config'] = 'feature_prod';

            return $output;
        } else {
            return '';
        }
    }

    public function newcheck()
    {
        if (1 == (int) Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_TAB')) {
            $output = [];
            $cookie = Context::getContext()->cookie;
            $id_lang = $cookie->id_lang;
            $output['tab_name'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_TITLE', $id_lang);
            $output['tab_name_id'] = 'tvcmstab-new-product';
            $output['tab_name_class_slider'] = 'tvtab-new-product';
            $output['tab_name_class_pagination'] = 'tvtab-new';
            $output['status_config'] = 'new_prod';

            return $output;
        } else {
            return '';
        }
    }

    public function bestcheck()
    {
        if (1 == (int) Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB')) {
            $output = [];
            $cookie = Context::getContext()->cookie;
            $id_lang = $cookie->id_lang;
            $output['tab_name'] = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE', $id_lang);
            $output['tab_name_id'] = 'tvcmstab-best-seller-product';
            $output['tab_name_class_slider'] = 'tvtab-best-seller-product';
            $output['tab_name_class_pagination'] = 'tvtab-best-seller';
            $output['status_config'] = 'best_prod';

            return $output;
        } else {
            return '';
        }
    }

    public function specialcheck()
    {
        if (1 == (int) Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB')) {
            $output = [];
            $cookie = Context::getContext()->cookie;
            $id_lang = $cookie->id_lang;
            $output['tab_name'] = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE', $id_lang);
            $output['tab_name_id'] = 'tvcmstab-special-product';
            $output['tab_name_class_slider'] = 'tvtab-special-product';
            $output['tab_name_class_pagination'] = 'tvtab-special';
            $output['status_config'] = 'special_prod';

            return $output;
        } else {
            return '';
        }
    }

    public function checktabname()
    {
        $tab_product_list = [];
        // display Feature Product
        if (1 == (int) Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_TAB')) {
            $product_info = $this->featurecheck();
            $tab_product_list[] = $product_info;
        }

        if (1 == (int) Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_TAB')) {
            $product_info = $this->newcheck();
            $tab_product_list[] = $product_info;
        }

        if (1 == (int) Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB')) {
            $product_info = $this->bestcheck();
            $tab_product_list[] = $product_info;
        }

        if (1 == (int) Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB')) {
            $product_info = $this->specialcheck();
            $tab_product_list[] = $product_info;
        }

        return $tab_product_list;
    }

    public function finalres()
    {
        $disArrResult = [];
        $disArrResult['data'] = $this->checktabname();
        $this->context->smarty->assign('dis_arr_tab_list', $disArrResult);

        // echo "<pre>";
        // print_r($disArrResult);
        return $disArrResult;
    }

    public function displayHomeAjax($status_config = '')
    {
        if (!Cache::isStored('tvcmstabproducts_display_index_ajax.tpl')) {
            $param_Status_config = $status_config;
            if (empty($status_config)) {
                if ((int) Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_TAB')) {
                    $status_config = 'feature_prod';
                } elseif ((int) Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_TAB')) {
                    $status_config = 'new_prod';
                } elseif ((int) Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB')) {
                    $status_config = 'best_prod';
                } elseif ((int) Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB')) {
                    $status_config = 'special_prod';
                }
                $checktabname = $this->finalres();
                $this->context->smarty->assign('CheckTabname', $checktabname);
            }
            $result = $this->showFrontSideResult($status_config);

            if ($result) {
                $static_token = Tools::getToken(false);
                $url = ['pages' => ['cart' => $this->context->link->getPageLink('cart')]];
                $this->context->smarty->assign('urls', $url);
                $this->context->smarty->assign('static_token', $static_token);

                if (empty($param_Status_config)) {
                    $output = $this->display(__FILE__, 'views/templates/front/display_index_data.tpl');
                } else {
                    $output = $this->display(__FILE__, 'views/templates/front/display_index_data_ajax.tpl');
                }
            } else {
                $output = '';
            }
            Cache::store('tvcmstabproducts_display_index_ajax' . $status_config . '.tpl', $output);
        }

        return Cache::retrieve('tvcmstabproducts_display_index_ajax' . $status_config . '.tpl');
    }

    public function displayFeaturedProducts($num_of_prod = '')
{

// $nProducts = (int) Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_NBR');
$nProducts = 18; // WYMUSZENIE 18 PRODUKTÓW DLA UKŁADU 6 KOLUMN
if (!empty($num_of_prod)) {
    $nProducts = $num_of_prod;
}
$idShop = (int) $this->context->shop->id;
$idLang = (int) $this->context->language->id;
$catId = (int) Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_CAT');

$rawProducts = [];


if ($catId === 0) {
    $rawProducts = [];
    $pool = [];

    $idShop = (int) $this->context->shop->id;

    // Big random pool – prefer non-sale, add a smaller portion of sale
    $limitNonSale = max(60, $nProducts * 12);
    $limitSale    = max(30, $nProducts * 6);

    $baseWhere = "ps.active = 1 AND ps.visibility IN ('both','catalog')";
    $stockJoin = 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND (sa.id_shop='.(int)$idShop.' OR sa.id_shop = 0)';
    $spJoin    = 'sp.id_product = p.id_product AND (sp.from IS NULL OR sp.from <= NOW()) AND (sp.to IS NULL OR sp.to >= NOW()) AND (sp.id_shop=0 OR sp.id_shop='.(int)$idShop.')';

    // 1) Non-sale pool (random)
    $sql = new DbQuery();
    $sql->select('p.id_product');
    $sql->from('product', 'p');
    $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = '.(int)$idShop);
    $sql->innerJoin('stock_available', 'sa', $stockJoin);
    $sql->leftJoin('specific_price', 'sp', $spJoin);
    $sql->where($baseWhere);
    $sql->where('sp.id_specific_price IS NULL');
    $sql->orderBy('RAND()');
    $sql->limit($limitNonSale);
    $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    if (is_array($rows)) {
        foreach ($rows as $r) {
            $pool[(int)$r['id_product']] = ['id_product' => (int)$r['id_product']];
        }
    }

    // 2) Sale pool (random)
    $sql = new DbQuery();
    $sql->select('p.id_product');
    $sql->from('product', 'p');
    $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = '.(int)$idShop);
    $sql->innerJoin('stock_available', 'sa', $stockJoin);
    $sql->innerJoin('specific_price', 'sp', $spJoin);
    $sql->where($baseWhere);
    $sql->orderBy('RAND()');
    $sql->limit($limitSale);
    $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    if (is_array($rows)) {
        foreach ($rows as $r) {
            $pool[(int)$r['id_product']] = ['id_product' => (int)$r['id_product']];
        }
    }

    $rawProducts = array_values($pool);
} else {

    $category = new Category($catId);
    $searchProvider = new CategoryProductSearchProvider(
        $this->context->getTranslator(),
        $category
    );
    $context = new ProductSearchContext($this->context);
    $query = new ProductSearchQuery();
    $query->setResultsPerPage($nProducts * 5);
    $query->setPage(1);
    if (Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_RAND')) {
        $query->setSortOrder(SortOrder::random());
    } else {
        $query->setSortOrder(new SortOrder('product', 'position', 'asc'));
    }
    $result = $searchProvider->runQuery($context, $query);
    $rawProducts = $result->getProducts();
}

$filtered = [];
if (is_array($rawProducts)) {
    foreach ($rawProducts as $raw) {
        if (!isset($raw['id_product'])) { continue; }
        $idp = (int) $raw['id_product'];
        $qty = (int) StockAvailable::getQuantityAvailableByProduct($idp, 0, (int) $this->context->shop->id);
        $oosp = (int) StockAvailable::outOfStock($idp, (int) $this->context->shop->id);
        $availableForOrder = isset($raw['available_for_order']) ? (bool)$raw['available_for_order'] : true;
        if (($qty > 1 || $oosp == 1) && $availableForOrder) {
            $filtered[] = $raw;
        }
    }
}

if (count($filtered) === 0 && is_array($rawProducts)) {
    foreach ($rawProducts as $raw) {
        if (!isset($raw['id_product'])) { continue; }
        $idp = (int) $raw['id_product'];
        $qty = (int) StockAvailable::getQuantityAvailableByProduct($idp, 0, (int) $this->context->shop->id);
        $oosp = (int) StockAvailable::outOfStock($idp, (int) $this->context->shop->id);
        $availableForOrder = isset($raw['available_for_order']) ? (bool)$raw['available_for_order'] : true;
        if (($qty >= 1 || $oosp == 1) && $availableForOrder) {
            $filtered[] = $raw;
        }
    }
}
if (count($filtered) === 0 && is_array($rawProducts)) {
    $filtered = $rawProducts;
}

if (count($filtered) > 1) {
    shuffle($filtered);
}
if (count($filtered) > $nProducts) {
    $filtered = array_slice($filtered, 0, $nProducts);
}

$assembler = new ProductAssembler($this->context);
$presenterFactory = new ProductPresenterFactory($this->context);
$presentationSettings = $presenterFactory->getPresentationSettings();
$presenter = new ProductListingPresenter(
    new ImageRetriever($this->context->link),
    $this->context->link,
    new PriceFormatter(),
    new ProductColorsRetriever(),
    $this->context->getTranslator()
);

$output = [];
$product_list = [];
$cookie = Context::getContext()->cookie;
$id_lang = $cookie->id_lang;
$output['tab_name'] = Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_TITLE', $id_lang);

$output['tab_name_id'] = 'tvcmstab-featured-product';
$output['tab_name_class_slider'] = 'tvtab-featured-product';
$output['tab_name_class_pagination'] = 'tvtab-featured';

$tmp = (int) Configuration::get('TVCMSTABPRODUCTS_FEATURED_PROD_TAB');
$output['status'] = $tmp;

foreach ($filtered as $rawProduct) {
    $product_list[] = $presenter->present(
        $presentationSettings,
        $assembler->assembleProduct($rawProduct),
        $this->context->language
    );
}

$output['num_of_prod'] = $nProducts;
$output['product_list'] = $product_list;

return $output;

}


    public function displayNewProducts($num_of_prod = '')
    {
        $newProducts = false;
// $nProducts = (int) Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_NBR');
$nProducts = 18; // WYMUSZENIE 18 PRODUKTÓW
if (!empty($num_of_prod)) {
    $nProducts = $num_of_prod;
}
        if (Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) {
            $newProducts = Product::getNewProducts(
                (int) $this->context->language->id,
                0,
                $nProducts
            );
        }
        $assembler = new ProductAssembler($this->context);
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $output = [];
        $product_list = [];
        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;
        $output['tab_name'] = Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_TITLE', $id_lang);

        $output['tab_name_id'] = 'tvcmstab-new-product';
        $output['tab_name_class_slider'] = 'tvtab-new-product';
        $output['tab_name_class_pagination'] = 'tvtab-new';

        $tmp = (int) Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_TAB');
        $output['status'] = $tmp;

        if (is_array($newProducts)) {
            foreach ($newProducts as $rawProduct) {
                $product_list[] = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );
            }
        }

        $output['num_of_prod'] = $nProducts;
        $output['product_list'] = $product_list;

        return $output;
    }

    public function displaySpecialProducts($num_of_prod = '')
    {
        $nProducts = (int) Configuration::get('TVCMSTABPRODUCTS_NEW_PROD_NBR');
        if (!empty($num_of_prod)) {
            $nProducts = $num_of_prod;
        }
        $products = Product::getPricesDrop(
            (int) Context::getContext()->language->id,
            0,
            $nProducts
        );

        $assembler = new ProductAssembler($this->context);
        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->context->getTranslator()
        );

        $output = [];
        $product_list = [];

        $cookie = Context::getContext()->cookie;
        $id_lang = $cookie->id_lang;
        $output['tab_name'] = Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_TITLE', $id_lang);

        $output['tab_name_id'] = 'tvcmstab-special-product';
        $output['tab_name_class_slider'] = 'tvtab-special-product';
        $output['tab_name_class_pagination'] = 'tvtab-special';

        $tmp = (int) Configuration::get('TVCMSTABPRODUCTS_SPECIAL_PROD_TAB');
        $output['status'] = $tmp;
        if (is_array($products)) {
            foreach ($products as $rawProduct) {
                $product_list[] = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );
            }
        }
        $output['num_of_prod'] = $nProducts;
        $output['product_list'] = $product_list;

        return $output;
    }

public function displayBestSellers($num_of_prod = '')
{

// $nProducts = (int) Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_NBR');
$nProducts = 18; // WYMUSZENIE 18 PRODUKTÓW
if (!empty($num_of_prod)) {
    $nProducts = $num_of_prod;
}
$idShop = (int) $this->context->shop->id;


$sql = null;
$pool = [];
$idShop = (int) $this->context->shop->id;

// Large mixed pool: prefer non-sale, add some sale
$limitNonSale = max(60, $nProducts * 12);
$limitSale    = max(30, $nProducts * 6);

$baseWhere = "ps.active = 1 AND ps.visibility IN ('both','catalog')";
$stockJoin = 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND (sa.id_shop='.(int)$idShop.' OR sa.id_shop = 0)';
$spJoin    = 'sp.id_product = p.id_product AND (sp.from IS NULL OR sp.from <= NOW()) AND (sp.to IS NULL OR sp.to >= NOW()) AND (sp.id_shop=0 OR sp.id_shop='.(int)$idShop.')';

// 1) Non-sale pool (random)
$sql = new DbQuery();
$sql->select('p.id_product');
$sql->from('product', 'p');
$sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = '.(int)$idShop);
$sql->innerJoin('stock_available', 'sa', $stockJoin);
$sql->leftJoin('specific_price', 'sp', $spJoin);
$sql->where($baseWhere);
$sql->where('sp.id_specific_price IS NULL');
$sql->orderBy('RAND()');
$sql->limit($limitNonSale);
$rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
if (is_array($rows)) {
    foreach ($rows as $r) {
        $pool[(int)$r['id_product']] = ['id_product' => (int)$r['id_product']];
    }
}

// 2) Sale pool (random)
$sql = new DbQuery();
$sql->select('p.id_product');
$sql->from('product', 'p');
$sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = '.(int)$idShop);
$sql->innerJoin('stock_available', 'sa', $stockJoin);
$sql->innerJoin('specific_price', 'sp', $spJoin);
$sql->where($baseWhere);
$sql->orderBy('RAND()');
$sql->limit($limitSale);
$rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
if (is_array($rows)) {
    foreach ($rows as $r) {
        $pool[(int)$r['id_product']] = ['id_product' => (int)$r['id_product']];
    }
}

$ids = array_values($pool);
$rawProducts = [];
if (is_array($ids)) {
    foreach ($ids as $row) {
        $rawProducts[] = ['id_product' => (int)$row['id_product']];
    }
}

$filtered = [];
if (is_array($rawProducts)) {
    foreach ($rawProducts as $raw) {
        if (!isset($raw['id_product'])) { continue; }
        $idp = (int) $raw['id_product'];
        $qty = (int) StockAvailable::getQuantityAvailableByProduct($idp, 0, (int) $this->context->shop->id);
        $oosp = (int) StockAvailable::outOfStock($idp, (int) $this->context->shop->id);
        $availableForOrder = isset($raw['available_for_order']) ? (bool)$raw['available_for_order'] : true;
        if (($qty > 1 || $oosp == 1) && $availableForOrder) {
            $filtered[] = $raw;
        }
    }
}

if (count($filtered) === 0 && is_array($rawProducts)) {
    foreach ($rawProducts as $raw) {
        if (!isset($raw['id_product'])) { continue; }
        $idp = (int) $raw['id_product'];
        $qty = (int) StockAvailable::getQuantityAvailableByProduct($idp, 0, (int) $this->context->shop->id);
        $oosp = (int) StockAvailable::outOfStock($idp, (int) $this->context->shop->id);
        $availableForOrder = isset($raw['available_for_order']) ? (bool)$raw['available_for_order'] : true;
        if (($qty >= 1 || $oosp == 1) && $availableForOrder) {
            $filtered[] = $raw;
        }
    }
}
if (count($filtered) === 0 && is_array($rawProducts)) {
    $filtered = $rawProducts;
}

if (count($filtered) > 1) {
    shuffle($filtered);
}
if (count($filtered) > $nProducts) {
    $filtered = array_slice($filtered, 0, $nProducts);
}

$assembler = new ProductAssembler($this->context);
$presenterFactory = new ProductPresenterFactory($this->context);
$presentationSettings = $presenterFactory->getPresentationSettings();
$presenter = new ProductListingPresenter(
    new ImageRetriever($this->context->link),
    $this->context->link,
    new PriceFormatter(),
    new ProductColorsRetriever(),
    $this->context->getTranslator()
);

$output = [];
$product_list = [];
$cookie = Context::getContext()->cookie;
$id_lang = $cookie->id_lang;
$output['tab_name'] = Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TITLE', $id_lang);

$output['tab_name_id'] = 'tvcmstab-best-seller-product';
$output['tab_name_class_slider'] = 'tvtab-best-seller-product';
$output['tab_name_class_pagination'] = 'tvtab-best-seller';

$tmp = (int) Configuration::get('TVCMSTABPRODUCTS_BEST_SELLER_PROD_TAB');
$output['status'] = $tmp;

foreach ($filtered as $rawProduct) {
    $product_list[] = $presenter->present(
        $presentationSettings,
        $assembler->assembleProduct($rawProduct),
        $this->context->language
    );
}

$output['num_of_prod'] = $nProducts;
$output['product_list'] = $product_list;

return $output;

}
}
