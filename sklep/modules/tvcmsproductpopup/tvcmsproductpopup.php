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

class TvcmsProductpopup extends Module
{
    protected $config_form = false;

    private $html = '';

    private $type_product = ['New Products', 'Featured Products', 'Bestsellers Products', 'Special Products'];

    protected $templateFile;

    public function __construct()
    {
        $this->name = 'tvcmsproductpopup';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'Themevolty';
        $this->need_instance = 1;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->trans('Themevolty - Product Popup');
        $this->description = $this->trans('Show Product Popup on Front Side');
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];
        $this->templateFile = 'module:tvcmsproductpopup/views/templates/hook/';
    }

    public function install()
    {
        $this->installTab();
        if (!parent::install() || !$this->registerHook('displayHeader') || !$this->registerHook('displayAfterBodyOpeningTag')) {
            return false;
        }
        Configuration::updateGlobalValue('tvcmsshowpopup', 0);
        Configuration::updateGlobalValue('tvcmsnumpopup', 5);

        return true;
    }

    public function uninstall()
    {
        $this->uninstallTab();
        if (!parent::uninstall() || !Configuration::deleteByName('tvcmsshowpopup') || !Configuration::deleteByName('tvcmsnumpopup')) {
            return false;
        }

        return true;
    }

    public function installTab()
    {
        $response = true;
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
            $tab->name[$lang['id_lang']] = 'Product Popup';
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

    public function getContent()
    {
        $html = '';
        if (Tools::isSubmit('save' . $this->name)) {
            Configuration::updateValue('tvcmsshowpopup', Tools::getvalue('tvcmsshowpopup'));
            Configuration::updateValue('tvcmsnumpopup', Tools::getvalue('tvcmsnumpopup'));
            $html = $this->displayConfirmation($this->trans('The settings have been updated successfully.'));
            $helper = $this->settingForm();
            $html .= $helper->generateForm($this->fields_form);

            return $html;
        } else {
            $helper = $this->settingForm();
            $html .= $helper->generateForm($this->fields_form);

            return $html;
        }
    }

    public function getTypeProduct()
    {
        $hooks = [];
        foreach ($this->type_product as $key => $type) {
            $hooks[$key]['key'] = $key;
            $hooks[$key]['name'] = $type;
        }

        return $hooks;
    }

    public function settingForm()
    {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $type_product = $this->getTypeProduct();
        $this->fields_form[0]['form'] = [
        'legend' => [
        'title' => $this->trans('Product PopUp Controller'),
        ],
        'input' => [
            [
                'type' => 'select',
                'label' => $this->trans('Choose Your Products'),
                'desc' => $this->trans(''),
                'name' => 'tvcmsshowpopup',
                'options' => [
                    'query' => $type_product,
                    'id' => 'key',
                    'name' => 'name',
                ],
            ],
            [
                'col' => 1,
                'type' => 'text',
                'label' => $this->trans('Number Of Products To Dispay'),
                'name' => 'tvcmsnumpopup',
                'size' => 15,
                'required' => true,
            ],
        ],
        'submit' => [
            'title' => $this->trans('Submit'),
            'class' => 'btn btn-default pull-right',
        ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = [
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0),
            ];
        }
        $helper->toolbar_btn = [
        'save' => [
            'desc' => $this->trans('Save'),
            'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . 'token=' . Tools::getAdminTokenLite('AdminModules'),
        ],
        ];

        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'save' . $this->name;

        $helper->fields_value['tvcmsshowpopup'] = Configuration::get('tvcmsshowpopup');
        $helper->fields_value['tvcmsnumpopup'] = Configuration::get('tvcmsnumpopup');

        return $helper;
    }

    public function hookdisplayHeader()
    {
        $this->context->controller->registerJavascript('mod-tvcmsprodpopup', 'modules/' . $this->name . '/views/js/front.js', ['position' => 'bottom', 'priority' => 150]);
        $this->context->controller->registerStylesheet('mod-tvcmsprodpopup1', 'modules/' . $this->name . '/views/css/front.css', ['media' => 'all', 'priority' => 150]);
    }

    public function prevHook()
    {
        $tvcmsshowpopup = Configuration::get('tvcmsshowpopup');
        $nb = (int) Configuration::get('tvcmsnumpopup');
        if (0 == $tvcmsshowpopup) {
            $prod_group = [];
            $prod_group['title'] = $this->l('New Arrival');
            $prod_group['class'] = $this->trans('tvcms-new-prod');
            $prod_group['product_list'] = $this->getNewProducts($nb);
        } elseif (1 == $tvcmsshowpopup) {
            $prod_group = [];
            $prod_group['title'] = $this->l('Featured');
            $prod_group['class'] = $this->trans('tvcms-feature-prod');
            $id_cat = (int) Context::getContext()->shop->getCategory();
            $prod_group['product_list'] = $this->getProductFeature($id_cat, $nb);
        } elseif (2 == $tvcmsshowpopup) {
            $prod_group = [];
            $prod_group['title'] = $this->l('Bestsellers');
            $prod_group['class'] = $this->trans('tvcms-bestseller-prod');
            $prod_group['product_list'] = $this->getBestSellers($nb);
        } elseif (3 == $tvcmsshowpopup) {
            $prod_group = [];
            $prod_group['title'] = $this->l('Special');
            $prod_group['class'] = $this->trans('tvcms-specail-prod');
            $prod_group['product_list'] = $this->getSpecialProducts($nb);
        }

        return $prod_group;
    }

    public function getProductFeature($id_cat, $nProducts)
    {
        $id_shop = (int) $this->context->shop->id;
        $category = new Category($id_cat);

        $searchProvider = new CategoryProductSearchProvider(
            $this->context->getTranslator(),
            $category
        );

        $context = new ProductSearchContext($this->context);

        $query = new ProductSearchQuery();
        $query->setResultsPerPage($nProducts)->setPage(1);

        if (Configuration::get('HOME_FEATURED_RANDOMIZE')) {
            $query->setSortOrder(SortOrder::random());
        } else {
            $query->setSortOrder(new SortOrder('product', 'position', 'asc'));
        }
        $result = $searchProvider->runQuery(
            $context,
            $query
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

        $products_for_template = [];

        foreach ($result->getProducts() as $rawProduct) {
            $tmp_prod = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($rawProduct),
                $this->context->language
            );

            $tvcms_stock_available = $this->getStockAvailableByProductId($rawProduct['id_product'], $rawProduct['id_product_attribute'], $id_shop);
            $tmp_prod['stock_quantity'] = $tvcms_stock_available['quantity'];
            $tmp_prod['reserved_quantity'] = $tvcms_stock_available['reserved_quantity'];
            if ($tmp_prod['stock_quantity'] > 0) {
                $tmp_prod['percent'] = $tmp_prod['stock_quantity'] / ($tvcms_stock_available['reserved_quantity'] + $tmp_prod['stock_quantity']) * 100;
            } else {
                $tmp_prod['percent'] = 0;
            }
            $time_ago = $this->timeago($rawProduct['date_upd']);
            $tmp_prod['time_ago'] = $time_ago;
            $products_for_template[] = $tmp_prod;
        }

        return $products_for_template;
    }

    public function timeago($date)
    {
        $timestamp = strtotime($date);
        $strTime = ['second', 'minute', 'hour', 'day', 'month', 'year'];
        $length = ['60', '60', '24', '30', '12', '10'];
        $currentTime = time();
        if ($currentTime >= $timestamp) {
            $diff = time() - $timestamp;
            for ($i = 0; $diff >= $length[$i] && $i < count($length) - 1; ++$i) {
                $diff = $diff / $length[$i];
            }
            $diff = round($diff);

            return $diff . ' ' . $strTime[$i] . '(s) ago ';
        }
    }

    protected function getNewProducts($nb)
    {
        $id_shop = (int) $this->context->shop->id;
        if (Configuration::get('PS_CATALOG_MODE')) {
            return false;
        }
        $newProducts = false;

        if (Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) {
            $newProducts = Product::getNewProducts(
                (int) $this->context->language->id,
                0,
                $nb
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

        $products_for_template = [];

        if (is_array($newProducts)) {
            foreach ($newProducts as $rawProduct) {
                $tmp_prod = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );
                $tvcms_stock_available = $this->getStockAvailableByProductId($rawProduct['id_product'], $rawProduct['id_product_attribute'], $id_shop);
                $tmp_prod['stock_quantity'] = $tvcms_stock_available['quantity'];
                $tmp_prod['reserved_quantity'] = $tvcms_stock_available['reserved_quantity'];
                if ($tmp_prod['stock_quantity'] > 0) {
                    $tmp_prod['percent'] = $tmp_prod['stock_quantity'] / ($tvcms_stock_available['reserved_quantity'] + $tmp_prod['stock_quantity']) * 100;
                } else {
                    $tmp_prod['percent'] = 0;
                }
                $time_ago = $this->timeago($rawProduct['date_upd']);
                $tmp_prod['time_ago'] = $time_ago;
                $products_for_template[] = $tmp_prod;
            }
        }

        return $products_for_template;
    }

    private function getSpecialProducts($nb)
    {
        $id_shop = (int) $this->context->shop->id;
        $products = Product::getPricesDrop(
            (int) Context::getContext()->language->id,
            0,
            $nb
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

        $products_for_template = [];

        if (is_array($products)) {
            foreach ($products as $rawProduct) {
                $tmp_prod = $presenter->present(
                    $presentationSettings,
                    $assembler->assembleProduct($rawProduct),
                    $this->context->language
                );
                $tvcms_stock_available = $this->getStockAvailableByProductId($rawProduct['id_product'], $rawProduct['id_product_attribute'], $id_shop);
                $tmp_prod['stock_quantity'] = $tvcms_stock_available['quantity'];
                $tmp_prod['reserved_quantity'] = $tvcms_stock_available['reserved_quantity'];
                if ($tmp_prod['stock_quantity'] > 0) {
                    $tmp_prod['percent'] = $tmp_prod['stock_quantity'] / ($tvcms_stock_available['reserved_quantity'] + $tmp_prod['stock_quantity']) * 100;
                } else {
                    $tmp_prod['percent'] = 0;
                }
                $time_ago = $this->timeago($rawProduct['date_upd']);
                $tmp_prod['time_ago'] = $time_ago;
                $products_for_template[] = $tmp_prod;
            }
        }

        return $products_for_template;
    }

    public function getStockAvailableByProductId($id_product, $id_product_attribute = null, $id_shop = null)
    {
        if (!Validate::isUnsignedId($id_product)) {
            return false;
        }

        $query = new DbQuery();
        $query->select('quantity');
        $query->select('reserved_quantity');
        $query->from('stock_available');
        $query->where('id_product = ' . (int) $id_product);

        if (null !== $id_product_attribute) {
            $query->where('id_product_attribute = ' . (int) $id_product_attribute);
        }

        $query = StockAvailable::addSqlShopRestriction($query, $id_shop);

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
    }

    protected function getBestSellers($nb)
    {
        $id_shop = (int) $this->context->shop->id;
        if (Configuration::get('PS_CATALOG_MODE')) {
            return false;
        }
        $searchProvider = new BestSalesProductSearchProvider(
            $this->context->getTranslator()
        );

        $context = new ProductSearchContext($this->context);

        $query = new ProductSearchQuery();

        $query->setResultsPerPage($nb)->setPage(1);

        $query->setSortOrder(SortOrder::random());

        $result = $searchProvider->runQuery(
            $context,
            $query
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

        $products_for_template = [];

        foreach ($result->getProducts() as $rawProduct) {
            $tmp_prod = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($rawProduct),
                $this->context->language
            );

            $tvcms_stock_available = $this->getStockAvailableByProductId($rawProduct['id_product'], $rawProduct['id_product_attribute'], $id_shop);
            $tmp_prod['stock_quantity'] = $tvcms_stock_available['quantity'];
            $tmp_prod['reserved_quantity'] = $tvcms_stock_available['reserved_quantity'];
            if ($tmp_prod['stock_quantity'] > 0) {
                $tmp_prod['percent'] = $tmp_prod['stock_quantity'] / ($tvcms_stock_available['reserved_quantity'] + $tmp_prod['stock_quantity']) * 100;
            } else {
                $tmp_prod['percent'] = 0;
            }
            $time_ago = $this->timeago($rawProduct['date_upd']);
            $tmp_prod['time_ago'] = $time_ago;
            $products_for_template[] = $tmp_prod;
        }

        return $products_for_template;
    }

    public function hookdisplayAfterBodyOpeningTag($params)
    {
        $group_prod_fliter = [];
        if (!$this->isCached($this->templateFile . 'tvcmsproductpopup_footer.tpl', $this->getCacheId($this->templateFile . 'tvcmsproductpopup_footer'))) {
            $group_prod_fliter = $this->prevHook($params);
            if (!isset($group_prod_fliter) || count($group_prod_fliter) <= 0) {
                return false;
            }
            $this->context->smarty->assign([
               'group_prod_fliter' => $group_prod_fliter,
            ]);
        }

        return $this->display(__FILE__, 'tvcmsproductpopup_footer.tpl', $this->getCacheId($this->templateFile . 'tvcmsproductpopup_footer'));
    }
}
