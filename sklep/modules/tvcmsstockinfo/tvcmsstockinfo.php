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

class TvcmsStockInfo extends Module
{
    // show design Types
    protected static $indicatorDesigns = ['none', 'bar', 'bar.sm', 'bar2', 'signal', 'battery', 'ring', 'pie'];

    // module position
    protected static $productDisplayTypes = ['old_price', 'price', 'after_price'];

    // Varible List
    protected $configs = [
        'TV_DISPLAY_WHEN_BELOW_LIMIT' => 0,
        'TV_DISPLAY_IN_CATS' => '',
        'TV_DISPLAY_IN_PRODUCT_LISTS' => true,
        'TV_DISPLAY_ON_PRODUCT_PAGE' => true,
        'TV_DISPLAY_NR_OF_ITEMS' => true,
        'TV_INDICATOR_FULL_AT' => 300,
        'TV_INDICATOR_DESIGN' => 'bar',
        'TV_ENABLE_PROGRESSIVE_COLORS' => true,
    ];

    // Hook List
    protected $hooks = [
        'displayBackOfficeHeader',
        'displayProductListReviews',
        'displayProductPriceBlock',
        'header',
    ];

    public function __construct()
    {
        $this->name = 'tvcmsstockinfo';
        $this->tab = 'front_office_features';
        $this->version = '4.0.0';
        $this->author = 'ThemeVolty';
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('ThemeVolty - Stock Indicator');
        $this->description = $this->l('Display stock indicator on your products.');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted.' .
            ' Are you sure you want uninstall this module?');
    }

    public function createVariable()
    {
        $all_categories = Category::getAllCategoriesName();
        unset($all_categories[0]);
        unset($all_categories[1]);

        $i = 1;
        $category_list = [];
        foreach ($all_categories as $category) {
            $category_list[] = $category['id_category'];
            ++$i;
        }

        $category_list = implode(',', $category_list);

        Configuration::updateValue('TV_DISPLAY_WHEN_BELOW_LIMIT', 0);
        Configuration::updateValue('TV_DISPLAY_IN_CATS', $category_list);
        Configuration::updateValue('TV_DISPLAY_IN_PRODUCT_LISTS', true);
        Configuration::updateValue('TV_DISPLAY_ON_PRODUCT_PAGE', true);
        Configuration::updateValue('TV_DISPLAY_NR_OF_ITEMS', 'true');
        Configuration::updateValue('TV_INDICATOR_FULL_AT', 300);
        Configuration::updateValue('TV_INDICATOR_DESIGN', 'bar');
        Configuration::updateValue('TV_ENABLE_PROGRESSIVE_COLORS', true);
    }

    public function install()
    {
        $this->installTab();
        $this->createVariable();

        return parent::install()
            && $this->registerHook('displayBackOfficeHeader')
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayProductListStockIndicator')
            && $this->registerHook('displayProductPageStockIndicator');
        // && $this->registerHook('displayProductListReviews');
        // && $this->registerHook('displayProductPriceBlock');
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
            $tab->name[$lang['id_lang']] = 'Stock Indicator';
        }
        $tab->id_parent = $parentTab_2->id;
        $tab->module = $this->name;
        $response &= $tab->add();

        return $response;
    }

    public function uninstall()
    {
        $this->uninstallTab();

        $this->deleteVariable();

        return parent::uninstall();
    }

    public function deleteVariable()
    {
        Configuration::deleteByName('TV_DISPLAY_WHEN_BELOW_LIMIT');
        Configuration::deleteByName('TV_DISPLAY_IN_CATS');
        Configuration::deleteByName('TV_DISPLAY_IN_PRODUCT_LISTS');
        Configuration::deleteByName('TV_DISPLAY_ON_PRODUCT_PAGE');
        Configuration::deleteByName('TV_DISPLAY_NR_OF_ITEMS');
        Configuration::deleteByName('TV_INDICATOR_FULL_AT');
        Configuration::deleteByName('TV_INDICATOR_DESIGN');
        Configuration::deleteByName('TV_ENABLE_PROGRESSIVE_COLORS');
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
        return $this->uninstall()
            && $this->install();
    }

    public function getContent()
    {
        $content = false === Tools::isSubmit('settings') ? '' : $this->processSettingsForm();

        $content .= $this->renderSettingsForm();
        $this->context->smarty->assign(['mainContent' => $content]);

        return $this->display(__FILE__, 'views/templates/admin/master.tpl');
    }

    // Create Form
    protected function formFactory($fields = [])
    {
        $helper = new HelperForm();

        // General settings
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
             . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        foreach ($fields as $name => $value) {
            $helper->$name = $value;
        }

        if (!array_key_exists('languages', $helper->tpl_vars)) {
            $helper->tpl_vars['languages'] = $this->context->controller->getLanguages();
        }

        if (!array_key_exists('id_language', $helper->tpl_vars)) {
            $helper->tpl_vars['id_language'] = $this->context->language->id;
        }

        return $helper;
    }

    // get variable's value for form
    protected function renderSettingsForm()
    {
        $helper = $this->formFactory([
            'submit_action' => 'settings',
            'tpl_vars' => ['fields_value' => $this->getSettingsFormValues()],
        ]);

        return $helper->generateForm(
            [['form' => $this->getSettingsForm()]]
        );
    }

    protected function getSettingsFormValues()
    {
        $configs = [];

        // Skip configurations that can't be used
        // when assigning them to FormHelper "fields_value" field.
        $skip = ['TV_DISPLAY_IN_CATS'];

        foreach ($this->configs as $config => $default) {
            if (in_array($config, $skip)) {
                continue;
            }

            $value = Configuration::get($config);

            $configs[$config] = false !== $value ? $value : $default;
        }

        return $configs;
    }

    protected function getSettingsForm()
    {
        $this->context->smarty->assign($this->getCommonIndicatorViewVars());

        $TV_DISPLAY_NR_OF_ITEMS_DESC = $this->l('If the number of items is not displayed, you must select '
             . 'a visible stock indicator design . ');
        $TV_INDICATOR_FULL_AT_DESC = $this->l('The indicator will be displayed as full when the product '
             . 'stock is at the specified value . ');

        $all_category = Category::getAllCategoriesName();
        $value = [];

        foreach ($all_category as $category) {
            $value['id'] = $category['id_category'];
            $value['use_checkbox'] = true;

            $tmp = Configuration::get('TV_DISPLAY_IN_CATS');
            // $tmp = str_replace("[", "", $tmp);
            // $tmp = str_replace("]", "", $tmp);

            $selected_categories = explode(',', $tmp);
            $value['selected_categories'] = $selected_categories;
        }

        return [
            'legend' => ['title' => $this->l('Settings'), 'icon' => 'icon-cog'],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->l('Display indicator on the product page'),
                    'name' => 'TV_DISPLAY_ON_PRODUCT_PAGE',
                    'values' => $this->getYesOrNoValues(),
                    'col' => 3,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Display Indicator In Product Listings'),
                    'name' => 'TV_DISPLAY_IN_PRODUCT_LISTS',
                    'values' => $this->getYesOrNoValues(),
                    'col' => 3,
                ],
                [
                    'type' => 'categories',
                    'label' => $this->l('Display Indicator For Products In These Categories'),
                    'name' => 'TV_DISPLAY_IN_CATS',
                    'col' => 6,
                    'tree' => $value,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Display Number Of Items'),
                    'desc' => $TV_DISPLAY_NR_OF_ITEMS_DESC,
                    'name' => 'TV_DISPLAY_NR_OF_ITEMS',
                    'values' => $this->getYesOrNoValues(),
                    'col' => 3,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Display Indicator When Stock Is Below'),
                    'desc' => $this->l('If 0 is specified, stock indicator will always be displayed . '),
                    'hint' => $this->l('Display indicator when product stock is below the specified value . '),
                    'name' => 'TV_DISPLAY_WHEN_BELOW_LIMIT',
                    'col' => 3,
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Mark Indicator As Full When Product Stock Is At'),
                    'desc' => $TV_INDICATOR_FULL_AT_DESC,
                    'name' => 'TV_INDICATOR_FULL_AT',
                    'col' => 3,
                ],
                [
                    'type' => 'design',
                    'label' => $this->l('Stock Indicator Design'),
                    'name' => 'TV_INDICATOR_DESIGN',
                    'values' => $this->getIndicatorDesigns(),
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Enable Progressive Colors For Stock Levels'),
                    'name' => 'TV_ENABLE_PROGRESSIVE_COLORS',
                    'values' => $this->getYesOrNoValues(),
                    'col' => 3,
                ],
            ],
            'submit' => ['title' => $this->l('Save')],
        ];
    }

    protected function getYesOrNoValues()
    {
        static $switchValues;

        if (!$switchValues) {
            $switchValues = [
                ['id' => 'active_on', 'value' => 1, 'label' => $this->l('Yes')],
                ['id' => 'active_off', 'value' => 0, 'label' => $this->l('No')],
            ];
        }

        return $switchValues;
    }

    protected function processSettingsForm()
    {
        if (count($errors = $this->validateSettingsForm()) > 0) {
            $alerts = '';

            foreach ($errors as $error) {
                $alerts .= $this->displayError($error);
            }

            return $alerts;
        }

        $successMessage = $this->l('Configuration updated . ');
        $errorMessage = $this->l('Something went wrong, please try again . ');

        foreach (array_keys($this->configs) as $config) {
            $value = Tools::getValue($config);

            if ('TV_DISPLAY_IN_CATS' == $config) {
                if (!empty($value)) {
                    $value = implode(',', $value);
                    Configuration::updateValue($config, $value);
                } else {
                    $value = '';
                    Configuration::updateValue($config, $value);
                }
            } elseif ('TV_DISPLAY_WHEN_BELOW_LIMIT' == $config || 'TV_INDICATOR_FULL_AT' == $config) {
                if (!Configuration::updateValue($config, (int) $value)) {
                    return $this->displayError($errorMessage);
                }
            } else {
                if (!Configuration::updateValue($config, $value)) {
                    return $this->displayError($errorMessage);
                }
            }
        }

        return $this->displayConfirmation($successMessage);
    }

    protected function validateSettingsForm()
    {
        $errors = [];

        foreach (array_keys($this->configs) as $config) {
            $value = Tools::getValue($config);

            if ('TV_DISPLAY_IN_CATS' == $config) {
                // If no category was selected, "0" is returned, otherwise check if is array.
                if (0 != $value && !is_array($value)) {
                    $errors[] = $this->l('Invalid selected categories . ');
                }
            }

            if ('TV_DISPLAY_WHEN_BELOW_LIMIT' == $config && !Validate::isUnsignedInt($value)) {
                $errors[] = $this->l('Show below limit must be a positive number . ');
            }

            if ('TV_INDICATOR_FULL_AT' == $config) {
                if (!Validate::isInt($value)) {
                    $errors[] = $this->l('Mark indicator as full must be a number . ');
                } elseif ((int) $value < 5) {
                    $errors[] = $this->l('Mark indicator as full can\'t be lower than 5 . ');
                }
            }

            if ('TV_INDICATOR_DESIGN' == $config) {
                if (!in_array($value, $this->getIndicatorDesigns())
                    || (!(bool) Tools::getValue('TV_DISPLAY_NR_OF_ITEMS') && 'none' == $value)) {
                    $errors[] = $this->l('Invalid stock indicator design . ');
                }
            }
        }

        return $errors;
    }

    protected function getIndicatorDesigns()
    {
        return static::$indicatorDesigns;
    }

    protected function getProductPageDisplayTypes()
    {
        return static::$productDisplayTypes;
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        if (!$this->isModuleAdminPage()) {
            return '';
        }
        if ($this->name == Tools::getValue('configure')) {
            $this->context->controller->addCSS([
               $this->getPathUri() . 'views/css/indicators.css',
               $this->getPathUri() . 'views/css/admin.css',
            ]);
        }
    }

    protected function isModuleAdminPage()
    {
        if ($this->context->controller instanceof AdminModulesController
            && (Tools::getValue('configure') == $this->name || Tools::getValue('module_name') == $this->name)) {
            return true;
        }

        return false;
    }

    public function hookHeader($params)
    {
        $this->context->controller->addCSS([
            $this->getPathUri() . 'views/css/indicators.css',
            $this->getPathUri() . 'views/css/front.css',
        ]);

        // $this->context->controller->addJS(array(
        //     $this->getPathUri() . 'views/js/front.js',
        // ));
    }

    public function hookdisplayProductListStockIndicator($params)
    {
        if (!$this->isDisplayableInProductList()
            || ($this->isCategoryPage() && !$this->isDisplayableInCurrentCategory())) {
            return '';
        }

        $product = $params['product'];

        $productId = (int) $product['id_product'];
        $quantity = (int) $product['quantity_all_versions'];
        $hasMixedQty = 0 !== (int) $product['cache_default_attribute'];

        if (!$this->isStockBelowLimit($quantity)) {
            return '';
        }

        return $this->context->smarty
            ->assign($this->getIndicatorViewVars($productId, $quantity, $hasMixedQty))
            ->fetch($this->getLocalPath() . 'views/templates/front/hook/product_list.tpl');
    }

    public function hookdisplayProductPageStockIndicator($params)
    {
        if (!$this->isDisplayableOnProductPage()) {
            return '';
        }

        $product = $params['product'];

        // In 1.6 this hook is used in the product list as well...
        // we don't want that, to distinguish between product list and product page
        // the product param is an object only when the hook is used on the product page.
        if (Tools::version_compare(_PS_VERSION_, '1.7', '<')) {
            if (!is_object($product)) {
                return;
            }

            // Product object quantity includes "all versions".
            $productId = (int) $product->id;
            $quantity = (int) $product->quantity;
            $hasMixedQty = 0 !== (int) $product->cache_default_attribute;
        } else {
            $productId = (int) $product['id'];
            $quantity = (int) $product['quantity'];

            // For 1.7, product combination quantities are loaded individually.
            $hasMixedQty = false;
        }

        if (!$this->isStockBelowLimit($quantity)) {
            return;
        }

        if (Tools::version_compare(_PS_VERSION_, '1.7', '<') && $hasMixedQty) {
            return $this->context->smarty
                ->assign($this->getIndicatorViewVarsForLegacyProductPage($productId))
                ->fetch($this->getLocalPath() . 'views/templates/front/hook/product_page_legacy.tpl');
        }

        $productId = (int) $product['id'];
        $quantity = (int) $product['quantity'];
        $hasMixedQty = false;

        return $this->context->smarty
            ->assign($this->getIndicatorViewVars($productId, $quantity, $hasMixedQty))
            ->fetch($this->getLocalPath() . 'views/templates/front/hook/product_page.tpl');
    }

    protected function isDisplayableInProductList()
    {
        return (bool) Configuration::get('TV_DISPLAY_IN_PRODUCT_LISTS');
    }

    protected function isDisplayableOnProductPage()
    {
        return (bool) Configuration::get('TV_DISPLAY_ON_PRODUCT_PAGE');
    }

    protected function isCategoryPage()
    {
        return $this->context->controller instanceof CategoryController;
    }

    protected function isDisplayableInCurrentCategory()
    {
        $category_list = Configuration::get('TV_DISPLAY_IN_CATS');
        if (empty($category_list)) {
            return false;
        }
        $category_list = explode(',', $category_list);
        $id = Tools::getValue('id_category');

        return in_array($id, $category_list);
    }

    protected function isStockBelowLimit($quantity)
    {
        $showBelowLimit = (int) Configuration::get('TV_DISPLAY_WHEN_BELOW_LIMIT');

        // If limit is 0, skip this check.
        if (0 == $showBelowLimit) {
            return true;
        }

        return $quantity < $showBelowLimit;
    }

    protected function getLevelByQuantity($quantity)
    {
        // If zero or below, level will always be 0.
        if ($quantity <= 0) {
            return 0;
        }

        $fullLimit = (int) Configuration::get('TV_INDICATOR_FULL_AT');

        // If equal or greater than the full value, level will always be 5.
        if ($quantity >= $fullLimit) {
            return 5;
        }

        // Determine what % of "full" is "quantity"
        $percentage = ($quantity / $fullLimit) * 100;

        // Divide the percentage by 20 so we can calculate the level
        // 100 / 20 is 5, "percentage" / 20 will return the stock level,
        // but since we may get 0 and we only use level 0 when
        // below or equal to zero, we increment the result by one.

        return (int) ($percentage / 20) + 1;
    }

    protected function getCommonIndicatorViewVars()
    {
        return [
            'viewsPath' => $this->getLocalPath() . 'views',
            'indicatorDesign' => Configuration::get('TV_INDICATOR_DESIGN'),
            'useProgressiveColors' => Configuration::get('TV_ENABLE_PROGRESSIVE_COLORS'),
            'isItemsDisplayable' => (bool) Configuration::get('TV_DISPLAY_NR_OF_ITEMS'),
            'stockIndicatorTrans' => [
                'stockStatus' => $this->l('Stock status'),
                'items' => $this->l(' items'),
                'mixedItems' => $this->l('Mixed items'),
            ],
        ];
    }

    protected function getIndicatorViewVars($productId, $quantity, $hasMixedQty = false)
    {
        if ($hasMixedQty) {
            $quantity = (int) ($quantity / $this->getProductVariantsCount($productId));
        }

        $level = $this->getLevelByQuantity($quantity);

        return array_merge(
            $this->getCommonIndicatorViewVars(),
            [
                'productItems' => $quantity,
                'hasMixedQty' => $hasMixedQty,
                'stockLevel' => $level,
                'stockLevelStatus' => $this->getStatusByQuantity($level),
            ]
        );
    }

    protected function getIndicatorViewVarsForLegacyProductPage($productId)
    {
        $productVariants = $this->getProductVariantsQuantity($productId);

        if (empty($productVariants)) {
            return compact('productVariants');
        }

        $viewVars = $this->getCommonIndicatorViewVars();

        foreach ($productVariants as $key => $variant) {
            $level = $this->getLevelByQuantity($variant['quantity']);
            $productVariants[$key]['stockLevel'] = $level;
            $productVariants[$key]['stockLevelStatus'] = $this->getStatusByQuantity($level);
        }

        return array_merge($viewVars, compact('productVariants'));
    }

    protected function getProductVariantsCount($productId)
    {
        $table = _DB_PREFIX_ . 'product_attribute';
        $join = _DB_PREFIX_ . 'product_attribute_shop';

        return (int) Db::getInstance()->getValue(
            "select count(pa.`id_product_attribute`) as `total`
             from `{$table}` pa inner join `{$join}` pas on pa.id_product_attribute = pas.id_product_attribute
             where pas.`id_shop` = " . (int) $this->context->shop->id . '
             and pa.`id_product` = ' . (int) $productId
        );
    }

    protected function getProductVariantsQuantity($productId)
    {
        $table = _DB_PREFIX_ . 'product_attribute';
        $join = _DB_PREFIX_ . 'product_attribute_shop';

        if (!$combinations = Db::getInstance()->executeS(
            "select pa.`id_product`, pa.`id_product_attribute`, pa.`quantity`
             from `{$table}` pa inner join `{$join}` pas on pa.id_product_attribute = pas.id_product_attribute
             where pas.`id_shop` = " . (int) $this->context->shop->id . '
             and pa.`id_product` = ' . (int) $productId
        )) {
            return [];
        }

        // Pulled straight from Product@getAttributesResume method.
        foreach ($combinations as $key => $row) {
            $cache_key = $row['id_product'] . '_' . $row['id_product_attribute'] . '_quantity';

            if (!Cache::isStored($cache_key)) {
                $result = StockAvailable::getQuantityAvailableByProduct(
                    (int) $row['id_product'],
                    (int) $row['id_product_attribute']
                );
                Cache::store($cache_key, $result);

                $combinations[$key]['quantity'] = $result;
            } else {
                $combinations[$key]['quantity'] = Cache::retrieve($cache_key);
            }
        }

        return $combinations;
    }

    protected function getStatusByQuantity($level)
    {
        static $levels;

        if (!$levels) {
            $levels = [
                0 => $this->l('Out of stock'),
                1 => $this->l('Very low'),
                2 => $this->l('Low'),
                3 => $this->l('Moderate'),
                4 => $this->l('High'),
                5 => $this->l('Very high'),
            ];
        }

        return $levels[$level];
    }
}
