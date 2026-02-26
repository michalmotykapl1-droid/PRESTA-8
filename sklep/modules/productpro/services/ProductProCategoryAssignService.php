<?php
/**
 * 2007-2023 PrestaShop
 *
 * Serwis do zarządzania logiką przypisywania produktów do kategorii na podstawie wagi.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductProCategoryAssignService
{
    private $module;
    private $context;

    public function __construct($module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
    }

    /**
     * Pobiera produkty, których waga mieści się w określonym zakresie i które NIE SĄ JUŻ przypisane do danej kategorii.
     *
     * @param float $minWeight Minimalna waga (włącznie).
     * @param float $maxWeight Maksymalna waga (włącznie).
     * @param int $categoryId Kategoria, z której produkty mają być wykluczone.
     * @return array Lista produktów.
     */
    public function getProductsByWeightRange($minWeight, $maxWeight, $categoryId = 0)
    {
        $sql = new DbQuery();
        // POPRAWKA: Dodajemy DISTINCT, aby uniknąć duplikowania produktów
        $sql->select('DISTINCT p.id_product, pl.name, p.weight');
        $sql->from('product', 'p');
        $sql->leftJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id);
        
        // Dodaj warunek wykluczający produkty już przypisane do danej kategorii
        if ($categoryId > 0) {
            // Używamy NOT EXISTS, aby upewnić się, że produkt nie jest powiązany z daną kategorią.
            $sql->where('NOT EXISTS (
                SELECT 1
                FROM `' . _DB_PREFIX_ . 'category_product` pc_exclude
                WHERE pc_exclude.id_product = p.id_product
                AND pc_exclude.id_category = ' . (int)$categoryId . '
            )');
        }

        // Dodaj warunek dotyczący zakresu wagi
        $sql->where('p.weight >= ' . (float)$minWeight . ' AND p.weight <= ' . (float)$maxWeight);
        
        $sql->orderBy('pl.name ASC');

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Przypisuje wybrane produkty do określonej kategorii.
     *
     * @param string $categoryType Typ kategorii (np. '5_10kg', '20_25kg').
     * @param array $productIds Lista ID produktów do przypisania.
     * @return array Wynik operacji (success, message).
     */
    public function assignProductsToCategory($categoryType, array $productIds)
    {
        $categoryId = 0;
        $categoryName = '';

        switch ($categoryType) {
            case '5_10kg':
                $categoryId = (int)Configuration::get('PRODUCTPRO_CATEGORY_5_10KG_ID'); // Pobierz ID z konfiguracji
                $categoryName = Configuration::get('PRODUCTPRO_CATEGORY_5_10KG_NAME'); // Pobierz nazwę z konfiguracji
                break;
            case '20_25kg':
                $categoryId = (int)Configuration::get('PRODUCTPRO_CATEGORY_20_25KG_ID'); // Pobierz ID z konfiguracji
                $categoryName = Configuration::get('PRODUCTPRO_CATEGORY_20_25KG_NAME'); // Pobierz nazwę z konfiguracji
                break;
            default:
                return ['success' => false, 'message' => $this->module->l('Nieznany typ kategorii.')];
        }

        if ($categoryId == 0) {
            return ['success' => false, 'message' => $this->module->l('Brak przypisanego ID dla wybranej kategorii. Sprawdź konfigurację modułu.')];
        }

        $assignedCount = 0;
        foreach ($productIds as $id_product) {
            $product = new Product((int)$id_product);
            if (Validate::isLoadedObject($product)) {
                $currentCategories = $product->getCategories();
                
                if (!in_array($categoryId, $currentCategories)) {
                    $currentCategories[] = $categoryId;
                    
                    // POPRAWKA: Używamy updateCategories() zamiast setCategories()
                    // updateCategories() automatycznie usuwa stare i dodaje nowe przypisania
                    if ($product->updateCategories($currentCategories)) {
                        $assignedCount++;
                    } else {
                        error_log('ProductPro: Failed to update categories for product ' . $id_product . ' after assigning to category ' . $categoryId);
                    }
                }
            } else {
                error_log('ProductPro: Failed to load product ' . $id_product . ' for category assignment.');
            }
        }

        if ($assignedCount > 0) {
            return ['success' => true, 'message' => sprintf($this->module->l('Pomyślnie przypisano %d produktów do kategorii "%s".'), $assignedCount, $categoryName)];
        } else {
            return ['success' => false, 'message' => $this->module->l('Nie przypisano żadnych produktów. Mogły już znajdować się w wybranej kategorii lub wystąpił błąd.')];
        }
    }
    
    /**
     * Pobiera produkty dla danego przedziału wagowego, używając konfigurowalnych wartości.
     * Używane w kontrolerze AdminProductProCategoryAssignController.
     *
     * @param string $categoryType Typ kategorii (np. '5_10kg', '20_25kg').
     * @return array Lista produktów.
     */
    public function getProductsForConfiguredRange($categoryType)
    {
        $minWeight = 0.0;
        $maxWeight = 0.0;
        $categoryId = 0; // Inicjalizuj ID kategorii do wykluczenia

        switch ($categoryType) {
            case '5_10kg':
                $minWeight = (float)Configuration::get('PRODUCTPRO_WEIGHT_5_10KG_MIN');
                $maxWeight = (float)Configuration::get('PRODUCTPRO_WEIGHT_5_10KG_MAX');
                $categoryId = (int)Configuration::get('PRODUCTPRO_CATEGORY_5_10KG_ID');
                break;
            case '20_25kg':
                $minWeight = (float)Configuration::get('PRODUCTPRO_WEIGHT_20_25KG_MIN');
                $maxWeight = (float)Configuration::get('PRODUCTPRO_WEIGHT_20_25KG_MAX');
                $categoryId = (int)Configuration::get('PRODUCTPRO_CATEGORY_20_25KG_ID');
                break;
            default:
                return [];
        }
        
        // Jeśli wartości są 0 (np. nieustawione), zwróć pustą tablicę, aby uniknąć pobierania wszystkich produktów
        if ($minWeight == 0.0 && $maxWeight == 0.0) {
            return [];
        }

        // Przekaż categoryId, aby wykluczyć produkty już w niej
        return $this->getProductsByWeightRange($minWeight, $maxWeight, $categoryId);
    }
}
