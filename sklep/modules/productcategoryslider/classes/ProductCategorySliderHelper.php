<?php
/**
 * Helper dla Product Category Slider
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductCategorySliderHelper
{
    public static function getProducts($id_category, $current_id_product, $limit = 16)
    {
        $context = Context::getContext();
        $id_lang = (int)$context->language->id;
        $id_shop = (int)$context->shop->id;

        // SQL: Pobierz produkty z tej samej kategorii, dostępne, aktywne
        $sql = 'SELECT p.*, product_shop.*, pl.*, image_shop.id_image, il.legend, m.name AS manufacturer_name
                FROM `' . _DB_PREFIX_ . 'product` p
                ' . Shop::addSqlAssociation('product', 'p') . '
                
                INNER JOIN `' . _DB_PREFIX_ . 'category_product` cp ON (
                    p.id_product = cp.id_product 
                    AND cp.id_category = ' . (int)$id_category . '
                )
                
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
                /* --- STAN MAGAZYNOWY --- */
                LEFT JOIN `' . _DB_PREFIX_ . 'stock_available` sa ON (
                    sa.id_product = p.id_product 
                    AND sa.id_product_attribute = 0 
                    AND (sa.id_shop = ' . (int)$id_shop . ' OR sa.id_shop = 0)
                )

                WHERE pl.`id_lang` = ' . (int)$id_lang . '
                AND product_shop.`active` = 1
                AND product_shop.`visibility` IN ("both", "catalog")
                
                /* WYKLUCZ OBECNY PRODUKT */
                AND p.id_product != ' . (int)$current_id_product . '
                
                /* TYLKO DOSTĘPNE */
                AND sa.quantity > 0

                GROUP BY p.id_product
                ORDER BY RAND()
                LIMIT ' . (int)$limit;

        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$products) return [];

        // Formatowanie (Presenter)
        $assembler = new ProductAssembler($context);
        $presenterFactory = new ProductPresenterFactory($context);
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