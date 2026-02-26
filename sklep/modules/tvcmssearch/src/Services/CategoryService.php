<?php
/**
 * CategoryService - fixed facet counting using category_product (all assignments),
 * plus helper methods used elsewhere in tvcmssearch.
 *
 * Namespace must match PSR-4 autoload: TvcmsSearch\Services
 */

namespace TvcmsSearch\Services;

use Context;
use Db;
use DbQuery;
use Shop;
use Category; // Import klasy Category

if (!defined('_PS_VERSION_')) {
    exit;
}

class CategoryService
{
    /** @var Context */
    protected $context;

    public function __construct(Context $context = null)
    {
        $this->context = $context ?: Context::getContext();
    }

    /**
     * Backward-compat wrapper if old code calls this name.
     * @param string $query
     * @param array  $filters
     * @return array<int, array{id_category:int,name:string,count:int}>
     */
    public function buildCategoriesFacet($query = '', array $filters = [])
    {
        return $this->getCategoriesFacet($query, $filters);
    }

    /**
     * Returns category facet counts computed from ALL assignments (category_product),
     * not from id_category_default.
     *
     * Supported $filters keys (all optional):
     * - idShop (int)          -> override shop id (default current)
     * - idLang (int)          -> override lang id (default current)
     * - onlyAvailable (bool)  -> join stock_available with quantity > 0
     * - idManufacturer (int)  -> filter by manufacturer
     * - idRootCategory (int)  -> limit to subtree of given category
     * - visibility (array)    -> allowed visibilities, default ['both','catalog']
     * - active (bool)         -> only active products (default true)
     * - extraWhere (string)   -> raw SQL appended to WHERE
     *
     * IMPORTANT: The caller should pass the same WHERE/filters as used for the
     * main product query to keep counts in sync.
     *
     * @param string $query
     * @param array  $filters
     * @return array<int, array{id_category:int,name:string,count:int}>
     */
    public function getCategoriesFacet($query = '', array $filters = [])
    {
        $idShop = isset($filters['idShop']) ? (int)$filters['idShop'] : (int)$this->context->shop->id;
        $idLang = isset($filters['idLang']) ? (int)$filters['idLang'] : (int)$this->context->language->id;

        $visibility = isset($filters['visibility']) && is_array($filters['visibility'])
            ? $filters['visibility']
            : ['both', 'catalog'];

        $activeOnly = array_key_exists('active', $filters) ? (bool)$filters['active'] : true;

        $q = new DbQuery();
        $q->select('cp.id_category, cl.name, COUNT(DISTINCT p.id_product) AS cnt');
        $q->from('product', 'p');

        $q->innerJoin('product_shop', 'ps',
            'ps.id_product = p.id_product
             AND ps.id_shop = '.(int)$idShop.
            ($activeOnly ? ' AND ps.active = 1' : '').
            ' AND ps.visibility IN ("'.pSQL(implode('","', $visibility)).'")'
        );

        $q->innerJoin('category_product', 'cp', 'cp.id_product = p.id_product');
        $q->innerJoin('category', 'c', 'c.id_category = cp.id_category AND c.active = 1');
        $q->innerJoin('category_lang', 'cl',
            'cl.id_category = cp.id_category AND cl.id_lang = '.(int)$idLang.' AND cl.id_shop = '.(int)$idShop
        );

        // Phrase search (name/reference/ean13) - basic alignment with main query
        $phrase = trim((string)$query);
        if ($phrase !== '') {
            $q->innerJoin('product_lang', 'pl',
                'pl.id_product = p.id_product AND pl.id_lang = '.(int)$idLang.' AND pl.id_shop = '.(int)$idShop
            );
            $like = '%'.pSQL($phrase).'%';
            $q->where('(pl.name LIKE "'.$like.'" OR p.reference LIKE "'.$like.'" OR p.ean13 LIKE "'.$like.'")');
        }

        // Manufacturer filter
        if (!empty($filters['idManufacturer'])) {
            $q->where('p.id_manufacturer = '.(int)$filters['idManufacturer']);
        }

        // Limit to a subtree of categories (if provided)
        if (!empty($filters['idRootCategory'])) {
            $root = (int)$filters['idRootCategory'];
            $bq = new DbQuery();
            $bq->select('nleft, nright');
            $bq->from('category');
            $bq->where('id_category = '.$root);
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($bq);
            if ($row && isset($row['nleft'], $row['nright'])) {
                $q->where('c.nleft BETWEEN '.(int)$row['nleft'].' AND '.(int)$row['nright']);
            }
        }

        // Only available products (simplified stock check)
        if (!empty($filters['onlyAvailable'])) {
            $q->innerJoin('stock_available', 'sa',
                'sa.id_product = p.id_product AND sa.id_shop = '.(int)$idShop.' AND sa.quantity > 0'
            );
        }

        // Extra WHERE from caller if needed (must be safe)
        if (!empty($filters['extraWhere']) && is_string($filters['extraWhere'])) {
            $q->where('('.$filters['extraWhere'].')');
        }

        $q->groupBy('cp.id_category');
        $q->having('cnt > 0');
        $q->orderBy('cnt DESC');

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($q);

        $out = [];
        foreach ((array)$rows as $r) {
            $out[] = [
                'id_category' => (int)$r['id_category'],
                'name'        => (string)$r['name'],
                'count'       => (int)$r['cnt'],
            ];
        }
        return $out;
    }

    /**
     * Returns direct children (subcategories) of a given category for current shop/lang.
     * @param int  $id_parent
     * @param bool $active
     * @return array<int, array{id_category:int,name:string}>
     */
    public function getSubcategories($id_parent, $active = true)
    {
        $idShop = (int)$this->context->shop->id;
        $idLang = (int)$this->context->language->id;

        $q = new DbQuery();
        $q->select('c.id_category, cl.name');
        $q->from('category', 'c');
        $q->innerJoin('category_lang', 'cl', 'cl.id_category = c.id_category AND cl.id_shop = '.$idShop.' AND cl.id_lang = '.$idLang);
        $q->innerJoin('category_shop', 'cs', 'cs.id_category = c.id_category AND cs.id_shop = '.$idShop);
        $q->where('c.id_parent = '.(int)$id_parent);
        if ($active) {
            $q->where('c.active = 1');
        }
        $q->orderBy('c.position ASC');

        return (array)Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($q);
    }

    /**
     * Returns category names for product: Default Category + Ancestor Categories (Lvl 3)
     * @param int $id_product
     * @return string[]
     */
    public function getCategoryNamesForProduct($id_product)
    {
        $idShop = (int)$this->context->shop->id;
        $idLang = (int)$this->context->language->id;
        $names = []; // Tablica na wszystkie nazwy

        // ================================================================
        // Krok 1 - Pobierz Kategorię Domyślną
        // ================================================================
        try {
            $product = new \Product($id_product, false, $idLang);
            if (\Validate::isLoadedObject($product)) {
                $defaultCat = new \Category($product->id_category_default, $idLang);
                // Dodaj ją, TYLKO jeśli jest załadowana I NIE jest to kategoria "Home"
                if (\Validate::isLoadedObject($defaultCat) && $defaultCat->id != (int)\Configuration::get('PS_HOME_CATEGORY')) {
                    $names[] = $defaultCat->name;
                }
            }
        } catch (\Exception $e) {
            // błąd, ale kontynuuj
        }
        
        // ================================================================
        // Krok 2 - Pobierz kategorie-przodków (TYLKO DIETETYCZNE z Poziomu 3)
        // ================================================================

        // Najpierw pobierz granice (nleft, nright) kategorii "Produkty dopasowane do diety" (ID 167)
        $dietRootId = (int)\TvcmsSearch::DIET_CATEGORY_ID; // 167
        $dietRootBounds = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new DbQuery())
            ->select('nleft, nright')
            ->from('category')
            ->where('id_category = ' . $dietRootId)
        );

        // Kontynuuj tylko jeśli mamy granice kategorii dietetycznej
        if ($dietRootBounds) {
            $q = new DbQuery();
            $q->select('DISTINCT cl_ancestor.name');
            
            // 1. Zacznij od przypisań produktu
            $q->from('category_product', 'cp');
            
            // 2. Połącz z kategorią-liściem (do której produkt jest FIZYCZNIE przypisany)
            $q->innerJoin('category', 'c_leaf', 'c_leaf.id_category = cp.id_category AND c_leaf.active = 1');
            
            // 3. Połącz z kategorią-przodkiem (używając nleft/nright)
            $q->innerJoin('category', 'c_ancestor', 
                'c_ancestor.nleft < c_leaf.nleft AND c_ancestor.nright > c_leaf.nright'
            );
            
            // 4. Połącz z nazwą kategorii-przodka
            $q->innerJoin('category_lang', 'cl_ancestor', 
                'cl_ancestor.id_category = c_ancestor.id_category AND cl_ancestor.id_lang = '.$idLang.' AND cl_ancestor.id_shop = '.$idShop
            );

            // 5. Warunki
            $q->where('cp.id_product = '.(int)$id_product);
            $q->where('c_ancestor.level_depth = 3'); // Poziom 3 (np. "Bio / Organic")
            
            // POPRAWKA: Upewnij się, że przodek (Poziom 3) jest DZIECKIEM kategorii dietetycznej (ID 167)
            $q->where('c_ancestor.nleft > ' . (int)$dietRootBounds['nleft']);
            $q->where('c_ancestor.nright < ' . (int)$dietRootBounds['nright']);

            $q->orderBy('cl_ancestor.name ASC');

            $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($q);
            
            $ancestor_names = [];
            foreach ((array)$rows as $r) {
                $ancestor_names[] = (string)$r['name'];
            }
            
            // ================================================================
            // Krok 3 - Połącz i usuń duplikaty
            // ================================================================
            
            // Połącz kategorię domyślną (już jest w $names) z kategoriami-przodkami
            $all_names = array_merge($names, $ancestor_names);
            
            // Zwróć unikalne wartości
            return array_unique($all_names);

        }
        
        // Fallback - jeśli nie znaleziono granic kategorii 167, zwróć tylko domyślną
        return $names;
    }
}