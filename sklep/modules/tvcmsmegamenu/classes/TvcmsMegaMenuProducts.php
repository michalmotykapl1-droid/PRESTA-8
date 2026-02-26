<?php
// /modules/tvcmsmegamenu/classes/TvcmsMegaMenuProducts.php

if (!defined('_PS_VERSION_')) {
    exit;
}

class TvcmsMegaMenuProducts
{
    /**
     * Pobiera losowe produkty z danej kategorii.
     *
     * @param int $id_category ID kategorii, z której mają być losowane produkty.
     * @param int $limit Liczba produktów do wylosowania.
     * @return array Tablica z danymi produktów.
     */
    public static function getRandomProducts($id_category, $limit = 3)
    {
        $context = Context::getContext();
        $id_lang = (int)$context->language->id;

        $category = new Category($id_category, $id_lang);

        // Sprawdzamy, czy kategoria istnieje i jest aktywna
        if (!Validate::isLoadedObject($category) || !$category->active) {
            return [];
        }

        // Pobieramy do 100 produktów z kategorii, aby mieć z czego losować
        // Używamy metody PrestaShop, która jest bezpieczniejsza niż surowy SQL
        $products = $category->getProducts($id_lang, 1, 100, 'position', 'ASC');

        if (!$products) {
            return [];
        }

        // Mieszamy tablicę produktów, aby uzyskać losową kolejność
        shuffle($products);

        // Wybieramy tylko tyle produktów, ile ustawiliśmy w limicie
        $random_products = array_slice($products, 0, (int)$limit);

        // Zwracamy produkty z pełnymi danymi (w tym linkami i obrazkami)
        return Product::getProductsProperties($id_lang, $random_products);
    }
}