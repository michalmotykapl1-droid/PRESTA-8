<?php
/**
 * Helper dla Strefy Produktowej
 * Wersja: Filtracja stanów magazynowych (tylko dostępne) + Sortowanie nowości.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class StrefaHelper
{
    // Pobiera produkty do listy (Grid - prawa strona)
    public static function getProducts($type, $limit)
    {
        // Grid po prawej ma być w pełni losowy (chyba że to 'new')
        return self::fetchProducts($type, $limit, false);
    }

    /**
     * Pobiera PRODUKTY DNIA dla każdej zakładki (Lewa strona)
     * Zablokowane na 24h dzięki użyciu daty jako seeda.
     */
    public static function getDealProducts()
    {
        // Generujemy bazowy seed z dzisiejszej daty (np. 20231125)
        $todaySeed = (int)date('Ymd');

        return [
            // Dla każdej zakładki inny seed, żeby produkty były różne, ale stałe przez dobę
            'featured'    => self::fetchOneDeal($todaySeed + 1),
            'bestsellers' => self::fetchOneDeal($todaySeed + 2),
            'new'         => self::fetchOneDeal($todaySeed + 3),
        ];
    }

    // Pobiera jeden produkt z blokadą losowania (SEED)
    private static function fetchOneDeal($seed)
    {
        $products = self::fetchProducts('deal', 1, $seed);
        return !empty($products) ? $products[0] : null;
    }

    // Wspólna funkcja prywatna do zapytań SQL
    private static function fetchProducts($type, $limit, $fixedSeed = false)
    {
        $context = Context::getContext();
        $id_lang = (int)$context->language->id;
        $id_shop = (int)$context->shop->id;
        
        $excluded_categories = '45, 180'; // Kategorie wykluczone

        // 1. USTAWIENIE SORTOWANIA
        if ($type == 'new') {
            // Jeśli to nowości -> Sortuj od najnowszych (data dodania)
            $orderBy = 'product_shop.date_add DESC';
        } else {
            // W przeciwnym razie -> Losowo (z seedem lub bez)
            $orderBy = $fixedSeed ? 'RAND(' . (int)$fixedSeed . ')' : 'RAND()';
        }

        // 2. ZAPYTANIE SQL
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
                /* --- JOIN DO TABELI STANÓW MAGAZYNOWYCH --- */
                LEFT JOIN `' . _DB_PREFIX_ . 'stock_available` sa ON (
                    sa.id_product = p.id_product 
                    AND sa.id_product_attribute = 0 /* 0 = główny stan produktu */
                    AND (sa.id_shop = ' . (int)$id_shop . ' OR sa.id_shop = 0)
                )
                
                WHERE pl.`id_lang` = ' . (int)$id_lang . '
                AND product_shop.`active` = 1
                AND product_shop.`visibility` IN ("both", "catalog")
                
                /* --- FILTR ILOŚCI: TYLKO DOSTĘPNE --- */
                AND sa.quantity > 0
                
                /* BLOKADA KATEGORII */
                AND p.`id_product` NOT IN (
                    SELECT cp.id_product 
                    FROM `' . _DB_PREFIX_ . 'category_product` cp 
                    WHERE cp.id_category IN (' . $excluded_categories . ')
                )

                ORDER BY ' . $orderBy . '
                LIMIT ' . (int)$limit;

        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!$products) {
            return [];
        }

        // 3. FORMATOWANIE DANYCH (Presta Presenter)
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