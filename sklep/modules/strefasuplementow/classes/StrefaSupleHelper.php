<?php
/**
 * Helper - Pobieranie LOSOWYCH produktów z konkretnej kategorii
 * Zmiana: Dodano filtrację po stanach magazynowych (tylko dostępne produkty).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class StrefaSupleHelper
{
    public static function getProductsByCategory($id_category, $limit = 8)
    {
        $context = Context::getContext();
        $id_lang = (int)$context->language->id;
        $id_shop = (int)$context->shop->id;
        
        // Sprawdzamy czy kategoria istnieje
        $category = new Category((int)$id_category);
        if (!Validate::isLoadedObject($category) || !$category->active) {
            return [];
        }

        // --- ZAPYTANIE SQL Z LOSOWANIEM (RAND) ---
        
        $sql = 'SELECT p.*, product_shop.*, pl.*, image_shop.id_image, il.legend, m.name AS manufacturer_name
                FROM `' . _DB_PREFIX_ . 'product` p
                ' . Shop::addSqlAssociation('product', 'p') . '
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
                    p.`id_product` = pl.`id_product` 
                    ' . Shop::addSqlRestrictionOnLang('pl') . '
                )
                LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop ON (
                    image_shop.`id_product` = p.`id_product` 
                    AND image_shop.cover = 1 
                    AND image_shop.id_shop = ' . (int)$id_shop . '
                )
                LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (
                    image_shop.`id_image` = il.`id_image` 
                    AND il.`id_lang` = ' . (int)$id_lang . '
                )
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (
                    m.`id_manufacturer` = p.`id_manufacturer`
                )
                /* --- NOWOŚĆ: JOIN DO TABELI STANÓW --- */
                LEFT JOIN `' . _DB_PREFIX_ . 'stock_available` sa ON (
                    sa.id_product = p.id_product 
                    AND sa.id_product_attribute = 0 /* 0 oznacza główny stan produktu */
                    AND (sa.id_shop = ' . (int)$id_shop . ' OR sa.id_shop = 0)
                )
                /* Łączymy z tabelą kategorii, aby filtrować po ID */
                INNER JOIN `' . _DB_PREFIX_ . 'category_product` cp ON (
                    p.`id_product` = cp.`id_product`
                )
                WHERE pl.`id_lang` = ' . (int)$id_lang . '
                AND product_shop.`active` = 1
                AND product_shop.`visibility` IN ("both", "catalog")
                
                /* --- NOWOŚĆ: FILTR ILOŚCI --- */
                AND sa.quantity > 0
                
                /* ŚCISŁY FILTR KATEGORII */
                AND cp.`id_category` = ' . (int)$id_category . '
                
                ORDER BY RAND()
                LIMIT ' . (int)$limit;

        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$products) return [];

        // --- FORMATOWANIE (Ceny, zdjęcia, linki) ---
        $assembler = new \ProductAssembler($context);
        $presenterFactory = new \ProductPresenterFactory($context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        
        $presenter = new \PrestaShop\PrestaShop\Core\Product\ProductListingPresenter(
            new \PrestaShop\PrestaShop\Adapter\Image\ImageRetriever($context->link),
            $context->link,
            new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter(),
            new \PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever(),
            $context->getTranslator()
        );

        $finalProducts = [];
        foreach ($products as $raw) {
            $finalProducts[] = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($raw),
                $context->language
            );
        }

        return $finalProducts;
    }
}