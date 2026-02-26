<?php
/**
 * Helper Class for Nieskonczone Produkty
 * Obsługuje logikę drzewa kategorii i pobierania produktów
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class NieskonczoneHelper
{
    /**
     * Znajduje kategorię nadrzędną na zadanym poziomie głębokości (np. 5).
     * Jeśli obecna kategoria jest płytsza, zwraca obecną.
     */
    public static function findBroadCategory($id_current, $target_depth = 5)
    {
        // 1. Pobierz dane obecnej kategorii
        $current = Db::getInstance()->getRow('
            SELECT id_category, nleft, nright, level_depth 
            FROM ' . _DB_PREFIX_ . 'category 
            WHERE id_category = ' . (int)$id_current
        );

        if (!$current) {
            return (int)$id_current;
        }

        // Jeśli jesteśmy wyżej lub równo z celem (np. Supermarket - poziom 3),
        // to nie schodzimy głębiej, bierzemy to co jest.
        if ((int)$current['level_depth'] <= $target_depth) {
            return (int)$id_current;
        }

        // 2. Szukamy przodka (rodzica/dziadka), który ma level_depth = 5
        // Przodek to kategoria, która "obejmuje" naszą (nleft mniejszy, nright większy)
        $sql = 'SELECT id_category 
                FROM ' . _DB_PREFIX_ . 'category 
                WHERE nleft < ' . (int)$current['nleft'] . ' 
                AND nright > ' . (int)$current['nright'] . ' 
                AND level_depth = ' . (int)$target_depth . '
                AND active = 1';
        
        $result = Db::getInstance()->getValue($sql);

        return $result ? (int)$result : (int)$id_current;
    }

    /**
     * Pobiera produkty z CAŁEGO DRZEWA (kategoria główna + wszystkie podkategorie)
     * Używa nleft/nright dla maksymalnej wydajności.
     */
    public static function getTreeProducts($id_root_category, $id_product_current, $page, $limit)
    {
        $context = Context::getContext();
        $offset = ($page - 1) * $limit;

        // 1. Pobierz zakres drzewa (nleft, nright) dla kategorii-korzenia
        $root = Db::getInstance()->getRow('
            SELECT nleft, nright 
            FROM ' . _DB_PREFIX_ . 'category 
            WHERE id_category = ' . (int)$id_root_category
        );

        if (!$root) return [];

        // 2. Pobierz ID produktów z całej gałęzi
        $sql = 'SELECT p.id_product
                FROM ' . _DB_PREFIX_ . 'product p
                ' . Shop::addSqlAssociation('product', 'p') . '
                
                INNER JOIN ' . _DB_PREFIX_ . 'category_product cp ON (p.id_product = cp.id_product)
                INNER JOIN ' . _DB_PREFIX_ . 'category c ON (cp.id_category = c.id_category)
                
                /* WARUNEK DRZEWA: Kategorie mieszczące się w zakresie nleft-nright */
                WHERE c.nleft >= ' . (int)$root['nleft'] . ' 
                AND c.nright <= ' . (int)$root['nright'] . '
                AND c.active = 1
                
                AND p.id_product != ' . (int)$id_product_current . '
                AND product_shop.active = 1
                AND product_shop.visibility IN ("both", "catalog")
                
                /* Grupujemy, bo jeden produkt może być w kilku podkategoriach tej gałęzi */
                GROUP BY p.id_product
                
                ORDER BY RAND()
                LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;

        $result = Db::getInstance()->executeS($sql);
        
        if (!$result) return [];

        // 3. Zbuduj obiekty produktów (Presta Presenter)
        $assembler = new ProductAssembler($context);
        $presenterFactory = new ProductPresenterFactory($context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = $presenterFactory->getPresenter();

        $products = [];
        foreach ($result as $row) {
            $products[] = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct(['id_product' => $row['id_product']]),
                $context->language
            );
        }

        return $products;
    }
}