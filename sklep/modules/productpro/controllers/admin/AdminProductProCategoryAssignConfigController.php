<?php
/**
 * 2007-2023 PrestaShop
 *
 * Kontroler strony konfiguracyjnej dla przypisywania produktów do kategorii wagowych.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminProductProCategoryAssignConfigController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $this->toolbar_title = $this->l('Konfiguracja kategorii wagowych');
    }

    /**
     * Przetwarzanie akcji POST (zapisywanie konfiguracji).
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitCategoryAssignConfig')) {
            // Zapisz konfigurację dla kategorii 5-10 kg
            $categoryId5_10kg = (int)Tools::getValue('PRODUCTPRO_CATEGORY_5_10KG_ID_SELECTED');
            $category5_10kg = new Category($categoryId5_10kg, (int)$this->context->language->id);
            Configuration::updateValue('PRODUCTPRO_CATEGORY_5_10KG_ID', $categoryId5_10kg);
            Configuration::updateValue('PRODUCTPRO_CATEGORY_5_10KG_NAME', Validate::isLoadedObject($category5_10kg) ? $category5_10kg->name : '');
            Configuration::updateValue('PRODUCTPRO_WEIGHT_5_10KG_MIN', (float)str_replace(',', '.', Tools::getValue('PRODUCTPRO_WEIGHT_5_10KG_MIN')));
            Configuration::updateValue('PRODUCTPRO_WEIGHT_5_10KG_MAX', (float)str_replace(',', '.', Tools::getValue('PRODUCTPRO_WEIGHT_5_10KG_MAX')));

            // Zapisz konfigurację dla kategorii 20-25 kg
            $categoryId20_25kg = (int)Tools::getValue('PRODUCTPRO_CATEGORY_20_25KG_ID_SELECTED');
            $category20_25kg = new Category($categoryId20_25kg, (int)$this->context->language->id);
            Configuration::updateValue('PRODUCTPRO_CATEGORY_20_25KG_ID', $categoryId20_25kg);
            Configuration::updateValue('PRODUCTPRO_CATEGORY_20_25KG_NAME', Validate::isLoadedObject($category20_25kg) ? $category20_25kg->name : '');
            Configuration::updateValue('PRODUCTPRO_WEIGHT_20_25KG_MIN', (float)str_replace(',', '.', Tools::getValue('PRODUCTPRO_WEIGHT_20_25KG_MIN')));
            Configuration::updateValue('PRODUCTPRO_WEIGHT_20_25KG_MAX', (float)str_replace(',', '.', Tools::getValue('PRODUCTPRO_WEIGHT_20_25KG_MAX')));

            $this->confirmations[] = $this->l('Ustawienia zostały zapisane.');
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminProductProCategoryAssignConfig'));
        }
    }
    
    /**
     * Inicjalizacja i renderowanie widoku.
     */
    public function initContent()
    {
        parent::initContent();

        // Pobierz kategorie w formie zagnieżdżonej struktury drzewa
        // ID 1 to zazwyczaj kategoria główna (Home)
        $nestedCategories = Category::getNestedCategories(null, (int)$this->context->language->id, true);
        
        // Funkcja pomocnicza do spłaszczania zagnieżdżonej struktury w listę z wcięciami
        $formattedCategories = [];
        $this->formatCategoriesRecursive($nestedCategories, $formattedCategories);

        $this->context->smarty->assign([
            'module_name' => $this->module->displayName,
            'current_url' => $this->context->link->getAdminLink('AdminProductProCategoryAssignConfig'),
            
            // Lista wszystkich kategorii do wyboru, teraz w hierarchicznej kolejności
            'all_categories' => $formattedCategories,

            // Pobierz aktualne wartości konfiguracyjne
            'selected_category_5_10kg_id'   => (int)Configuration::get('PRODUCTPRO_CATEGORY_5_10KG_ID'),
            'weight_5_10kg_min'    => (float)Configuration::get('PRODUCTPRO_WEIGHT_5_10KG_MIN'),
            'weight_5_10kg_max'    => (float)Configuration::get('PRODUCTPRO_WEIGHT_5_10KG_MAX'),

            'selected_category_20_25kg_id'   => (int)Configuration::get('PRODUCTPRO_CATEGORY_20_25KG_ID'),
            'weight_20_25kg_min'    => (float)Configuration::get('PRODUCTPRO_WEIGHT_20_25KG_MIN'),
            'weight_20_25kg_max'    => (float)Configuration::get('PRODUCTPRO_WEIGHT_20_25KG_MAX'),
        ]);

        $this->setTemplate('category_assign_config.tpl');
    }

    /**
     * Rekurencyjna funkcja pomocnicza do formatowania kategorii z wcięciami.
     *
     * @param array $categories Zagnieżdżona tablica kategorii.
     * @param array $formattedCategories Tablica, do której dodawane są sformatowane kategorie.
     * @param int $level Bieżący poziom zagnieżdżenia.
     */
    private function formatCategoriesRecursive($categories, &$formattedCategories, $level = 0)
    {
        foreach ($categories as $category) {
            // Pomijamy kategorię główną "Home" (ID 1), chyba że jest to jedyna kategoria.
            // Zazwyczaj nie chcemy przypisywać produktów bezpośrednio do niej.
            if ($category['id_category'] == 1 && $level == 0) {
                // Jeśli chcesz wyświetlać kategorię główną, usuń ten warunek.
                // Możesz też dodać warunek, aby wyświetlać ją tylko, jeśli nie ma innych kategorii.
                // Na potrzeby czytelności dropdowna, często pomija się główną kategorię.
                if (empty($category['children'])) { // Jeśli kategoria główna nie ma dzieci, wyświetl ją
                     $formattedCategories[] = [
                        'id_category' => (int)$category['id_category'],
                        'name' => $category['name'],
                        'level_depth' => $level,
                        'spacer' => ''
                    ];
                }
                if (!empty($category['children'])) {
                    $this->formatCategoriesRecursive($category['children'], $formattedCategories, $level);
                }
                continue;
            }

            $indentation = '';
            if ($level > 0) {
                // Zmniejszamy liczbę spacji do 2 na poziom, co powinno być bardziej kompaktowe
                $indentation = str_repeat('&nbsp;&nbsp;', $level) . '→ '; 
            }

            $formattedCategories[] = [
                'id_category' => (int)$category['id_category'],
                'name' => $category['name'],
                'level_depth' => $level,
                'spacer' => $indentation
            ];

            // Jeśli kategoria ma dzieci, wywołaj rekurencyjnie dla dzieci
            if (!empty($category['children'])) {
                $this->formatCategoriesRecursive($category['children'], $formattedCategories, $level + 1);
            }
        }
    }
}