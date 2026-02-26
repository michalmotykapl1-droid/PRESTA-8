<?php
/**
 * Helper dla Modułu Fresh (Hybrid Logic + Stock Filter)
 * ZMODYFIKOWANY: Pobiera losowe produkty z drzew kategorii 270184 i 261677
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class FreshHelper
{
    public static function getProductsForCategory($id_category, $limit_grid = 10)
    {
        $context = Context::getContext();
        $id_lang = (int)$context->language->id;
        $id_shop = (int)$context->shop->id;

        // ---------------------------------------------------------
        // KROK 1: Zdefiniowanie kategorii źródłowych (HARDCODED)
        // ---------------------------------------------------------
        // ID: 270184 oraz 261677
        $target_root_ids = [270184, 261677];
        $categories_ids = $target_root_ids; // Startujemy z głównych ID

        // Pobieramy podkategorie dla obu głównych kategorii
        foreach ($target_root_ids as $root_id) {
            $category = new Category((int)$root_id);
            
            // Sprawdzamy czy kategoria istnieje i jest aktywna
            if (Validate::isLoadedObject($category) && $category->active) {
                $subcategories = $category->getAllChildren($id_lang);
                
                if (!empty($subcategories)) {
                    foreach ($subcategories as $sub) {
                        if (isset($sub->id_category)) {
                            $categories_ids[] = (int)$sub->id_category;
                        } elseif (isset($sub['id_category'])) {
                            $categories_ids[] = (int)$sub['id_category'];
                        }
                    }
                }
            }
        }

        // Usuwamy duplikaty (jeśli by się zdarzyły) i tworzymy ciąg do zapytania SQL
        $categories_ids = array_unique($categories_ids);
        
        // Zabezpieczenie: jeśli lista pusta (błąd ID), nie robimy zapytania
        if (empty($categories_ids)) {
            return [];
        }

        $ids_string = implode(',', $categories_ids);

        // ---------------------------------------------------------
        // KROK A: Pobieramy PRODUKT DNIA (Hero) - Stały przez 24h
        // ---------------------------------------------------------
        $sql_hero = 'SELECT p.*, product_shop.*, pl.*, image_shop.id_image, il.legend, m.name AS manufacturer_name
                FROM `' . _DB_PREFIX_ . 'product` p
                ' . Shop::addSqlAssociation('product', 'p') . '
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.id_product = pl.id_product ' . Shop::addSqlRestrictionOnLang('pl') . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop ON (image_shop.id_product = p.id_product AND image_shop.cover = 1 AND image_shop.id_shop = ' . (int)$id_shop . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.id_image = il.id_image AND il.id_lang = ' . (int)$id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.id_manufacturer = p.id_manufacturer)
                LEFT JOIN `' . _DB_PREFIX_ . 'stock_available` sa ON (sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND (sa.id_shop = ' . (int)$id_shop . ' OR sa.id_shop = 0))
                INNER JOIN `' . _DB_PREFIX_ . 'category_product` cp ON (p.id_product = cp.id_product)
                WHERE cp.id_category IN (' . $ids_string . ')
                AND pl.id_lang = ' . (int)$id_lang . '
                AND product_shop.active = 1
                AND product_shop.visibility IN ("both", "catalog")
                AND sa.quantity > 0
                ORDER BY RAND(TO_DAYS(NOW())) 
                LIMIT 1';

        $hero_products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_hero);
        
        if (!$hero_products) return []; // Brak produktów = pusto

        $hero_id = (int)$hero_products[0]['id_product'];
        $all_products = $hero_products; // Startujemy z Hero

        // ---------------------------------------------------------
        // KROK B: Pobieramy GRID (Losowy, wykluczając Hero)
        // ---------------------------------------------------------
        $sql_grid = 'SELECT p.*, product_shop.*, pl.*, image_shop.id_image, il.legend, m.name AS manufacturer_name
                FROM `' . _DB_PREFIX_ . 'product` p
                ' . Shop::addSqlAssociation('product', 'p') . '
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.id_product = pl.id_product ' . Shop::addSqlRestrictionOnLang('pl') . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop ON (image_shop.id_product = p.id_product AND image_shop.cover = 1 AND image_shop.id_shop = ' . (int)$id_shop . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.id_image = il.id_image AND il.id_lang = ' . (int)$id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.id_manufacturer = p.id_manufacturer)
                LEFT JOIN `' . _DB_PREFIX_ . 'stock_available` sa ON (sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND (sa.id_shop = ' . (int)$id_shop . ' OR sa.id_shop = 0))
                INNER JOIN `' . _DB_PREFIX_ . 'category_product` cp ON (p.id_product = cp.id_product)
                WHERE cp.id_category IN (' . $ids_string . ')
                AND pl.id_lang = ' . (int)$id_lang . '
                AND p.id_product != ' . $hero_id . ' 
                AND product_shop.active = 1
                AND product_shop.visibility IN ("both", "catalog")
                AND sa.quantity > 0
                GROUP BY p.id_product
                ORDER BY RAND()
                LIMIT ' . (int)$limit_grid;

        $grid_products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_grid);

        if ($grid_products) {
            $all_products = array_merge($all_products, $grid_products);
        }

        // ---------------------------------------------------------
        // KROK C: Prezentacja
        // ---------------------------------------------------------
        $assembler = new ProductAssembler($context);
        $presenterFactory = new ProductPresenterFactory($context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        
        $presenter = new PrestaShop\PrestaShop\Core\Product\ProductListingPresenter(
            new PrestaShop\PrestaShop\Adapter\Image\ImageRetriever($context->link),
            $context->link,
            new PrestaShop\PrestaShop\Adapter\Product\PriceFormatter(),
            new PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever(),
            $context->getTranslator()
        );

        $finalProducts = [];
        foreach ($all_products as $raw) {
            $finalProducts[] = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($raw),
                $context->language
            );
        }

        return $finalProducts;
    }
}