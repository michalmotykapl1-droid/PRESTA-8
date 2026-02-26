<?php
namespace TvcmsSearch\Services;

use Context;
use Search;
use Product;
use Category;
use Db;
use Link; // Upewnij się, że Link jest zaimportowany
use StockAvailable; // DODANO import dla sprawdzania stanu magazynowego
use Configuration; // DODANO import dla obsługi konfiguracji

class ProductSearchService
{
    private $context;
    private $link; // Dodajemy właściwość na obiekt Link

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->link = new Link(); // Inicjalizujemy obiekt Link
    }

    /**
     * Pobiera produkty na podstawie terminu wyszukiwania.
     *
     * @param string $searchTerm Termin wyszukiwania.
     * @param int $page Numer strony.
     * @param int $pageSize Rozmiar strony (liczba produktów na stronę).
     * @param string $orderBy Pole do sortowania.
     * @param string $orderWay Kierunek sortowania (ASC/DESC).
     * @param bool $getTotal Czy pobrać całkowitą liczbę wyników.
     * @param bool $active Czy pobierać only aktywne produkty.
     * @param bool $onlyAvailable Czy pobierać tylko dostępne produkty
     * @return array Tablica produktów.
     */
    public function getProducts(
        $searchTerm,
        $page = 1,
        $pageSize = 99999, // Duża wartość, aby pobrać wszystkie produkty do filtrowania
        $orderBy = 'position',
        $orderWay = 'desc',
        $getTotal = false,
        $active = true,
        $onlyAvailable = false 
    ) {
        \TvcmsSearchLogger::debug('ProductSearchService::getProducts called with searchTerm: "' . $searchTerm . '"');
        $id_lang = (int)$this->context->language->id;
        $id_shop = (int)$this->context->shop->id;

        // ================================================================
        // START POPRAWKI: Wymuszenie logiki "AND" oraz ustawień z modułu
        // ================================================================
        
        // 1. Wczytaj ustawienia z modułu tvcmssearch
        $fuzzy_level = (int)Configuration::get('TVCMSSEARCH_FUZZY_LEVEL');
        $within_word = (int)Configuration::get('TVCMSSEARCH_WITHIN_WORD');
        
        // 2. Zapisz oryginalne ustawienia PrestaShop, aby je później przywrócić
        $original_fuzzy = Configuration::get('PS_SEARCH_FUZZY');
        $original_within_word = Configuration::get('PS_SEARCH_WORD_LIKE');
        $original_search_type = Configuration::get('PS_SEARCH_TYPE'); // <--- NOWA LINIA

        // 3. Tymczasowo nadpisz ustawienia PrestaShop (używając updateValue)
        Configuration::updateValue('PS_SEARCH_FUZZY', $fuzzy_level > 0 ? 1 : 0);
        Configuration::updateValue('PS_SEARCH_WORD_LIKE', $within_word);
        Configuration::updateValue('PS_SEARCH_TYPE', 0); // <--- NOWA LINIA (Wymusza logikę "AND")
        
        \TvcmsSearchLogger::debug('Tymczasowo ustawiono PS_SEARCH_FUZZY na: ' . ($fuzzy_level > 0 ? 1 : 0));
        \TvcmsSearchLogger::debug('Tymczasowo ustawiono PS_SEARCH_WORD_LIKE na: ' . $within_word);
        \TvcmsSearchLogger::debug('Tymczasowo ustawiono PS_SEARCH_TYPE na: 0 (AND)');

        // ================================================================

        // 4. Wykonaj wyszukiwanie (teraz Search::find() użyje poprawnych ustawień)
        $result = Search::find($id_lang, $searchTerm, $page, $pageSize, $orderBy, $orderWay, $getTotal, $active, $this->context);
        
        // 5. Przywróć oryginalne ustawienia PrestaShop
        Configuration::updateValue('PS_SEARCH_FUZZY', $original_fuzzy);
        Configuration::updateValue('PS_SEARCH_WORD_LIKE', $original_within_word);
        Configuration::updateValue('PS_SEARCH_TYPE', $original_search_type); // <--- NOWA LINIA

        \TvcmsSearchLogger::debug('Przywrócono oryginalne ustawienia wyszukiwania.');
        // ================================================================
        // KONIEC POPRAWKI
        // ================================================================
        
        $products = isset($result['result']) ? $result['result'] : [];

        // Filtrowanie produktów niedostępnych (Bardziej rygorystyczne)
        if ($onlyAvailable && !empty($products)) {
            \TvcmsSearchLogger::debug('Opcja "Tylko dostępne" jest WŁĄCZONA. Filtrowanie ' . count($products) . ' produktów.');
            
            $filteredProducts = [];
            foreach ($products as $product) {
                
                $quantity = StockAvailable::getQuantityAvailableByProduct($product['id_product']);
                
                if ($quantity > 0) {
                    $filteredProducts[] = $product;
                }
            }
            \TvcmsSearchLogger::debug('Po filtrowaniu zostało ' . count($filteredProducts) . ' produktów.');
            // Zwracamy tylko przefiltrowaną listę
            return $filteredProducts;
        }

        return $products;
    }

    /**
     * Pobiera unikalne kategorie główne z listy produktów i zlicza produkty w każdej z nich.
     *
     * @param array $products
     * @return array
     */
    public function getCategoriesFromProducts(array $products): array
    {
        if (empty($products)) {
            return [];
        }

        $categoryCounts = [];
        foreach ($products as $product) {
            if (isset($product['id_category_default'])) {
                $catId = (int)$product['id_category_default'];
                if (!isset($categoryCounts[$catId])) {
                    $categoryCounts[$catId] = 0;
                }
                $categoryCounts[$catId]++;
            }
        }

        $categoryIds = array_keys($categoryCounts);
        if (empty($categoryIds)) {
            return [];
        }

        $id_lang = (int) $this->context->language->id;
        $id_shop = (int) $this->context->shop->id;
        $prefix = _DB_PREFIX_;

        $sql = 'SELECT c.id_category, cl.name, cl.link_rewrite
                FROM `' . $prefix . 'category` c
                INNER JOIN `' . $prefix . 'category_lang` cl ON (c.id_category = cl.id_category AND cl.id_lang = '.(int)$id_lang.' AND cl.id_shop = '.(int)$id_shop.')
                WHERE c.id_category IN ('.implode(',', $categoryIds).') AND c.active = 1';

        $results = Db::getInstance()->executeS($sql);
        
        $categories = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $catId = (int)$row['id_category'];
                $categories[] = [
                    'id_category' => $catId,
                    'name' => $row['name'],
                    'product_count' => $categoryCounts[$catId] ?? 0,
                    'url' => $this->link->getCategoryLink($catId, $row['link_rewrite'], $id_lang), // DODAJEMY URL
                ];
            }
        }
        return $categories;
    }

    /**
     * Pobiera unikalne kategorie dietetyczne z listy produktów wraz z liczbą produktów.
     *
     * @param array $products
     * @return array
     */
    public function getDietaryPreferencesFromProducts(array $products): array
    {
        if (empty($products)) {
            return [];
        }

        $mainDietParentId = \TvcmsSearch::DIET_CATEGORY_ID;
        $allDietCategoryIds = [];

        foreach ($products as $product) {
            $productCategories = \Product::getProductCategories($product['id_product']);
            if (empty($productCategories)) {
                continue;
            }

            foreach ($productCategories as $idCat) {
                
                // Wspinanie się po drzewie kategorii
                $category = new \Category((int)$idCat, $this->context->language->id, $this->context->shop->id);
                $current_cat = $category;

                // Pętla bezpieczeństwa (max 10 poziomów w górę)
                for ($i = 0; $i < 10; $i++) {
                    
                    if (empty($current_cat) || $current_cat->id_parent == 0 || $current_cat->id == $current_cat->id_parent) {
                        break;
                    }

                    // SPRAWDZENIE: Czy rodzicem obecnej kategorii ($current_cat) jest
                    // nasza główna kategoria dietetyczna (ID 167)?
                    if ($current_cat->id_parent == $mainDietParentId) {
                        
                        $allDietCategoryIds[] = (int)$current_cat->id;
                        break;
                    }
                    
                    // Nie, idziemy poziom wyżej
                    $current_cat = new \Category((int)$current_cat->id_parent, $this->context->language->id, $this->context->shop->id);
                }
            }
        }
        
        $categoryCounts = array_count_values($allDietCategoryIds);
        $uniqueDietIds = array_keys($categoryCounts);

        if (empty($uniqueDietIds)) {
            return [];
        }

        $id_lang = (int) $this->context->language->id;
        $id_shop = (int) $this->context->shop->id;
        $prefix = _DB_PREFIX_;

        $sql = 'SELECT c.id_category, cl.name, cl.link_rewrite
                FROM `' . $prefix . 'category` c
                INNER JOIN `' . $prefix . 'category_lang` cl ON (c.id_category = cl.id_category AND cl.id_lang = '.(int)$id_lang.' AND cl.id_shop = '.(int)$id_shop.')
                WHERE c.id_category IN ('.implode(',', array_map('intval', $uniqueDietIds)).') AND c.active = 1';
        
        $results = \Db::getInstance()->executeS($sql);

        $categories = [];
        if (!empty($results)) {
            foreach ($results as $row) {
                $catId = (int)$row['id_category'];
                $categories[] = [
                    'id_category' => $catId,
                    'name' => $row['name'],
                    'product_count' => $categoryCounts[$catId] ?? 0,
                    'url' => $this->link->getCategoryLink($catId, $row['link_rewrite'], $id_lang), // DODAJEMY URL
                ];
            }
        }
        return $categories;
    }
}