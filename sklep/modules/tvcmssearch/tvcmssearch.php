<?php
/**
 * 2007-2025 PrestaShop
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

// START Debug Logger Inclusion
require_once(_PS_MODULE_DIR_ . 'tvcmssearch/classes/TvcmsSearchLogger.php');
TvcmsSearchLogger::info('TvcmsSearch module main file loaded.');
// END Debug Logger Inclusion

use TvcmsSearch\Services\DietFeatureService;
use TvcmsSearch\Services\CategoryService;
use TvcmsSearch\Services\ManufacturerService;
use TvcmsSearch\Services\ProductSearchService;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class TvcmsSearch extends Module
{
    private $templateFile;
    public $options; 
    private $optionsCount = 0;
    const DIET_CATEGORY_ID = 167; 

    /** @var DietFeatureService */
    private $dietFeatureService;
    /** @var CategoryService */
    private $categoryService;
    /** @var ManufacturerService */
    private $manufacturerService;
    /** @var ProductSearchService */
    private $productSearchService;

    public function __construct()
    {
        if (!defined('_PS_VERSION_')) {
            exit;
        }

        // Manual PSR-4 autoloader
        spl_autoload_register(function ($class) {
            $prefix = 'TvcmsSearch\\';
            if (0 !== strpos($class, $prefix)) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });

        $this->name = 'tvcmssearch';
        $this->tab = 'front_office_features';
        $this->author = 'ThemeVolty';
        $this->version = '4.0.0';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = 'ThemeVolty - Quick Search';
        $this->description = 'Adds a quick search field to your website.';

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->module_key = '';

        $this->confirmUninstall = $this->l('Warning: all the data saved in your database will be deleted. Are you sure you want uninstall this module?');
        
        $context = Context::getContext();
        $this->dietFeatureService = new DietFeatureService($context);
        $this->categoryService = new CategoryService($context);
        $this->manufacturerService = new ManufacturerService($context);
        $this->productSearchService = new ProductSearchService($context);
    }

    public function install()
    {
        \Configuration::updateValue('TVCMSSEARCH_DEBUG_LOG', 0);
        Configuration::updateValue('TVCMSSEARCH_DROPDOWN_THEME', 'classic');
        Configuration::updateValue('TVCMSSEARCH_DROPDOWN_ALIGN', 'left');
        Configuration::updateValue('TVCMSSEARCH_INSTANT_SEARCH', 1);
        Configuration::updateValue('TVCMSSEARCH_SHOW_PRICES', 1);
        Configuration::updateValue('TVCMSSEARCH_SHOW_IMAGES', 1);
        Configuration::updateValue('TVCMSSEARCH_MAX_RESULTS', 8);
        Configuration::updateValue('TVCMSSEARCH_SHOW_CATEGORIES', 1);
        Configuration::updateValue('TVCMSSEARCH_FUZZY_LEVEL', 1);
        Configuration::updateValue('TVCMSSEARCH_WITHIN_WORD', 0);
        Configuration::updateValue('TVCMSSEARCH_ONLY_AVAILABLE', 0);
        Configuration::updateValue('TVCMSSEARCH_CAT_CLICK_MODE', 'ajax');
        Configuration::updateValue('TVCMSSEARCH_SHOW_CAT_COUNT', 1);
        Configuration::updateValue('TVCMSSEARCH_SHOW_DIET', 1);
        Configuration::updateValue('TVCMSSEARCH_SHOW_MANUFACTURER', 1);
        Configuration::updateValue('TVCMSSEARCH_SHOW_DIET_FILTER', 1);

        if (!$this->installTab('AdminTvCmsSearchConfig', 'Konfiguracja wyszukiwarki', 'IMPROVE')) {
            return false;
        }

        return parent::install()
            && $this->registerHook('displayNavSearchBlock')
            && $this->registerHook('displaySearch')
            && $this->registerHook('displayMobileSearchBlock')
            && $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        \Configuration::deleteByName('TVCMSSEARCH_DEBUG_LOG');
        $id_tab = (int)Tab::getIdFromClassName('AdminTvCmsSearchConfig');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            $tab->delete();
        }

        Configuration::deleteByName('TVCMSSEARCH_DROPDOWN_THEME');
        Configuration::deleteByName('TVCMSSEARCH_DROPDOWN_ALIGN');
        Configuration::deleteByName('TVCMSSEARCH_INSTANT_SEARCH');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_PRICES');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_IMAGES');
        Configuration::deleteByName('TVCMSSEARCH_MAX_RESULTS');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_CATEGORIES');
        Configuration::deleteByName('TVCMSSEARCH_FUZZY_LEVEL');
        Configuration::deleteByName('TVCMSSEARCH_WITHIN_WORD');
        Configuration::deleteByName('TVCMSSEARCH_ONLY_AVAILABLE');
        Configuration::deleteByName('TVCMSSEARCH_CAT_CLICK_MODE');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_CAT_COUNT');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_DIET');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_MANUFACTURER');
        Configuration::deleteByName('TVCMSSEARCH_SHOW_DIET_FILTER');

        return parent::uninstall();
    }
    
    private function installTab($className, $tabName, $parent = 'IMPROVE')
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }
        $id_parent = Tab::getIdFromClassName($parent);
        if (!$id_parent) {
            return false;
        }
        $tab->id_parent = $id_parent;
        $tab->module = $this->name;
        
        return $tab->add();
    }
    
    public function getContent()
    {
        $redirect_link = Context::getContext()->link->getAdminLink('AdminTvCmsSearchConfig', false) . '&token=' . Tools::getAdminTokenLite('AdminTvCmsSearchConfig') . '&conf=4';
        Tools::redirectAdmin($redirect_link);
    }
    
    public function hookdisplayHeader()
    {
        if (isset($this->context->controller)) {
            $this->context->controller->registerStylesheet(
                $this->name . '-mobile-css',
                'modules/' . $this->name . '/views/css/tvcmssearch.mobile.css',
                ['media' => 'only screen and (max-width: 768px)', 'priority' => 60]
            );
        }

        if (isset($this->context->controller)) {
            $this->context->controller->registerJavascript(
                $this->name . '-mobile-js',
                'modules/' . $this->name . '/views/js/tvcmssearch.mobile.js',
                ['position' => 'bottom', 'priority' => 60]
            );
        }

        $this->context->controller->addJqueryUI('ui.autocomplete');
        $this->context->controller->registerJavascript('modules-tvcmssearch', 'modules/'
            . $this->name . '/views/js/tvcmssearch.js', ['position' => 'bottom', 'priority' => 150]);

        Media::addJsDef([
            'tvcmssearch_instant' => (bool)Configuration::get('TVCMSSEARCH_INSTANT_SEARCH'),
            'tvcmssearch_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax', [], true),
            'tvcmssearch_click_mode' => Configuration::get('TVCMSSEARCH_CAT_CLICK_MODE'),
            'tvcmssearch_results_url' => $this->context->link->getModuleLink($this->name, 'results', [], true),
        ]);
        Media::addJsDef(['tvcmssearch_min_chars' => (int)Configuration::get('PS_SEARCH_MINWORDLEN', 5)]);

        $this->context->controller->addCSS($this->_path . 'views/css/front.css');
    }

    public function getAjaxResult()
    {
        $maxResults = (int)Configuration::get('TVCMSSEARCH_MAX_RESULTS', 8);
        $showPrices = (bool)Configuration::get('TVCMSSEARCH_SHOW_PRICES');
        $showImages = (bool)Configuration::get('TVCMSSEARCH_SHOW_IMAGES');
        $showCategories = (bool) Configuration::get('TVCMSSEARCH_SHOW_CATEGORIES'); 
        $showCatCount = (bool) Configuration::get('TVCMSSEARCH_SHOW_CAT_COUNT');
        $showDiet = (bool) Configuration::get('TVCMSSEARCH_SHOW_DIET');
        $showDietFilter = (bool)Configuration::get('TVCMSSEARCH_SHOW_DIET_FILTER');
        $onlyAvailable = (bool)Configuration::get('TVCMSSEARCH_ONLY_AVAILABLE', 0);
        
        $context = Context::getContext();
        $search_words = Tools::getValue('search_words');
        $category_id = Tools::getValue('category_id');
        $cat_id = trim($category_id);

        $id_lang = $this->context->language->id;
        
        // Pobieranie produktów (standardowe sortowanie PrestaShop)
        $products = $this->productSearchService->getProducts(
            $search_words, 1, 99999, 'weight', 'desc', false, true, $onlyAvailable 
        );

        // =================================================================
        // SMART SORT V2: Poprawa trafności wyników (Pełne słowa)
        // =================================================================
        if (!empty($products) && Tools::strlen($search_words) > 2) {
            $term = mb_strtolower(trim($search_words));
            
            // Regex: Szuka pełnego słowa (otoczonego spacją, początkiem linii lub interpunkcją)
            // Dzięki temu "Czekolada" znajdzie "Bio Czekolada", ale "Czekoladowy" uzna za gorszy wynik.
            $patternWholeWord = '/(^|\s|[[:punct:]])' . preg_quote($term, '/') . '($|\s|[[:punct:]])/u';
            
            usort($products, function($a, $b) use ($term, $patternWholeWord) {
                $nameA = mb_strtolower($a['name']);
                $nameB = mb_strtolower($b['name']);

                // Sprawdź czy występuje PEŁNE SŁOWO
                $matchWholeA = preg_match($patternWholeWord, $nameA);
                $matchWholeB = preg_match($patternWholeWord, $nameB);

                // Jeśli A zawiera pełne słowo, a B nie -> A wyżej
                if ($matchWholeA && !$matchWholeB) return -1;
                // Jeśli B zawiera pełne słowo, a A nie -> B wyżej
                if (!$matchWholeA && $matchWholeB) return 1;

                // Jeśli oba mają pełne słowo (lub oba nie mają), sortuj standardowo (wg wagi Presta)
                return 0;
            });
        }
        // =================================================================

        $productCategories = $this->productSearchService->getCategoriesFromProducts($products);
        
        // =================================================================
        // SORTOWANIE KATEGORII: 45 -> 180 -> Reszta
        // =================================================================
        if (is_array($productCategories)) {
            // Sortowanie malejące po liczbie produktów
            usort($productCategories, function($a, $b) {
                return ((int)$b['product_count'] <=> (int)$a['product_count']);
            });

            // Wyłuskujemy specjalne kategorie
            $cat45 = null;  // Strefa Okazji
            $cat180 = null; // Krótka Data
            $others = [];

            foreach ($productCategories as $cat) {
                if ((int)$cat['id_category'] === 45) {
                    $cat45 = $cat;
                } elseif ((int)$cat['id_category'] === 180) {
                    $cat180 = $cat;
                } else {
                    $others[] = $cat;
                }
            }

            // Sklejamy w nowej kolejności
            $sortedCats = [];
            if ($cat45) $sortedCats[] = $cat45;
            if ($cat180) $sortedCats[] = $cat180;
            $productCategories = array_merge($sortedCats, $others);
        }

        $dietCategories = $this->productSearchService->getDietaryPreferencesFromProducts($products);
        
        $diet_features_data = [];
        if ($showDietFilter) {
            $diet_features_data = $this->dietFeatureService->getFeaturesForProducts($products);
        }

        $search_controller_url = $this->context->link->getPageLink('search', null, null, null, false, null, true);
        $this->context->smarty->assign('search_controller_url', $search_controller_url);

        $this->context->smarty->assign([
            'products' => $products,
            'options'  => [
                'categories'      => $productCategories,
                'diet_categories' => $dietCategories,
            ],
            'showCategories' => $showCategories,
            'showCatCount'   => $showCatCount,
            'showDiet'       => $showDiet,
            'showDietFilter' => $showDietFilter,
            'diet_features'  => $diet_features_data['unique_features'] ?? [],
        ]);

        $return_data = [];
        if (!empty($products)) { 
            foreach ($products as $product) {
                $add_product_to_results = false; 

                if ('undefined' != $cat_id && '0' != $cat_id) {
                    $target_category_id = (int)$cat_id;
                    $product_direct_categories = Product::getProductCategories($product['id_product']);
                    if (in_array($target_category_id, $product_direct_categories)) {
                        $add_product_to_results = true;
                    } else {
                        foreach ($product_direct_categories as $prod_cat_id) {
                            $category_obj = new Category($prod_cat_id, $context->language->id, $context->shop->id);
                            $category_parents = $category_obj->getParentsCategories($context->language->id);
                            foreach ($category_parents as $parent_cat) {
                                if ((int)$parent_cat['id_category'] === $target_category_id) {
                                    $add_product_to_results = true;
                                    break 2; 
                                }
                            }
                        }
                    }
                } else {
                    $add_product_to_results = true;
                }

                if ($add_product_to_results) {
                    $return_data[$product['id_product']] = $product;
                    if ($showImages) {
                        $image = Image::getCover($product['id_product']);
                        if ($image && isset($image['id_image'])) {
                            // WAŻNE: 'home' = większy format zdjęcia
                            $img_type = ImageType::getFormattedName('home'); 
                            $tmp = $context->link->getImageLink($product['link_rewrite'], $image['id_image'], $img_type);
                            $return_data[$product['id_product']]['cover_image'] = $tmp;
                        }
                    }
                }
            }
        }
        
        $html = ''; 
        $result_data = [];
        $result_data['total'] = count($return_data); 
        
        if (!empty($return_data)) {
            $i = 1;
            foreach ($return_data as $data) {
                if ($i <= $maxResults) {
                    $prod_name = $data['name'];
                    $prod_link = $data['link'];
                    
                    // Bez listy kategorii (usunięto)
                    
                    $image_html = '';
                    if ($showImages && isset($data['cover_image'])) {
                        $image_html = '<div class=\'tvsearch-dropdown-img-block\'><img src=\'' . $data['cover_image'] . '\' alt=\'' . $prod_name . '\' /></div>';
                    }

                    // --- BADGE LOGIC (NAKLEJKI) ---
                    $badge_html = '';
                    $prod_cats = Product::getProductCategories($data['id_product']);
                    
                    if (in_array(180, $prod_cats)) {
                        // Kategoria 180 = Krótka Data (CZERWONY)
                        $badge_html = '<span class="badge-custom badge-shortdate">KRÓTKA DATA</span>';
                    } elseif (isset($data['specific_prices']) && !empty($data['specific_prices'])) {
                        // Promocja = Łap Okazje (POMARAŃCZOWY)
                        $badge_html = '<span class="badge-custom badge-sale">ŁAP OKAZJE</span>';
                    }
                    // ------------------------------

                    $price_html = '';
                    if ($showPrices) {
                        if (isset($data['specific_prices']) && !empty($data['specific_prices'])) {
                            $new_price = Tools::displayPrice($data['price']);
                            $old_price = Tools::displayPrice($data['price_without_reduction']);
                            $price_html = '<span class=\'price\'>' . $new_price . '</span><span class=\'regular-price\'>' . $old_price . '</span>';
                        } else {
                            $new_price = Tools::displayPrice($data['price']);
                            $price_html = '<div class=\'price\'>' . $new_price . '</div>';
                        }
                        $price_html = '<div class=\'product-price-and-shipping\'>' . $price_html . '</div>';
                    }

                    $feature_values_string = '';
                    if ($showDietFilter && isset($diet_features_data['product_features_map'][$data['id_product']])) {
                        $feature_values_string = implode(',', $diet_features_data['product_features_map'][$data['id_product']]);
                    }
                    $data_attribute = 'data-feature-values=\'' . $feature_values_string . '\'';

                    $html .= '<div class=\'tvsearch-dropdown-wrapper clearfix\' ' . $data_attribute . '>
                                <a href=\'' . $prod_link . '\'>' 
                                    . $badge_html 
                                    . $image_html . 
                                    '<div class=\'tvsearch-dropdown-content-box\'><div class=\'tvsearch-dropdown-title\'>' . $prod_name . '</div>' . $price_html . '</div>
                                </a>
                              </div>';
                    ++$i;
                } else {
                    break; 
                }
            }
        }

        $result_data['html'] = !empty($html) ? $html : ''; 
        $this->context->smarty->assign('result_data', $result_data);
        return $this->display(__FILE__, 'views/templates/front/display_ajax_result.tpl');
    }

    private function assignTemplateVariables()
    {
        $showCategories = (bool) Configuration::get('TVCMSSEARCH_SHOW_CATEGORIES');
        $showDiet = (bool) Configuration::get('TVCMSSEARCH_SHOW_DIET');
        $search_controller_url = $this->context->link->getPageLink('search', null, null, null, false, null, true);

        $this->context->smarty->assign([
            'options' => ['categories' => [], 'diet_categories' => []],
            'search_controller_url' => $search_controller_url,
            'showCategories'        => $showCategories,
            'showDiet'              => $showDiet,
        ]);
    }

    public function hookdisplayNavSearchBlock() { $this->assignTemplateVariables(); return $this->display(__FILE__, 'views/templates/front/display_search.tpl'); }
    public function hookdisplaySearch() { return $this->hookdisplayNavSearchBlock(); }
    public function hookdisplayMobileSearchBlock() { $this->assignTemplateVariables(); return $this->display(__FILE__, 'views/templates/front/display_mobile_search.tpl'); }
}