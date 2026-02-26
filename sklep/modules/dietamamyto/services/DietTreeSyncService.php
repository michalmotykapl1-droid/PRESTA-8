<?php
if (!defined('_PS_VERSION_')) { exit; }

class DietTreeSyncService
{
    protected $context;
    protected $idLang;
    protected $idShop;
    protected $lastWasCreated = false;

    public function __construct(Context $context = null)
    {
        $this->context = $context ?: Context::getContext();
        $this->idLang = (int)$this->context->language->id;
        $this->idShop = (int)$this->context->shop->id;
        $this->ensureMapTable();
    }

    /** $force = true → przetwarzaj wszystkie produkty na rootach diet;
     *  $force = false → TYLKO te, które nie mają jeszcze kategorii w poddrzewie danej diety (są tylko na roocie).
     */
    public function sync(array $dietRootIds, int $depth = 1, bool $force = true): array
    {
        $depth = max(1, min(6, (int)$depth));
        $createdCats = 0; $linkedProducts = 0; $processed = 0;

        $useCap = (int)Configuration::get('DIETAMAMYTO_TREE_USE_TYPE_CAP', 1);
        $mode = (string)Configuration::get('DIETAMAMYTO_DEPTH_MODE', 'leaf');
        $typeDepth = (int)Configuration::get('DIETAMAMYTO_TYPE_DEPTH', 2);

        foreach ($dietRootIds as $idDietRoot) {
            $idDietRoot = (int)$idDietRoot;
            if ($idDietRoot <= 0) { continue; }
            $processed += $this->syncForDietRoot($idDietRoot, $depth, $createdCats, $linkedProducts, $mode, $typeDepth, $useCap, $force);
        }

        if (method_exists('Category', 'regenerateEntireNtree')) { Category::regenerateEntireNtree(); }

        return [
            'diet_roots' => count($dietRootIds),
            'processed' => $processed,
            'created_categories' => $createdCats,
            'linked_products' => $linkedProducts,
        ];
    }

    protected function syncForDietRoot(int $idDietRoot, int $depth, int &$createdCats, int &$linkedProducts, string $mode, int $typeDepth, int $useCap, bool $force): int
    {
        $root = new Category((int)$idDietRoot, $this->idLang, $this->idShop);
        if (!Validate::isLoadedObject($root)) { return 0; }

        $sql = new DbQuery();
        $sql->select('cp.id_product, p.id_category_default');
        $sql->from('category_product', 'cp');
        $sql->innerJoin('product', 'p', 'p.id_product = cp.id_product');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . (int)$this->idShop);
        $sql->where('cp.id_category = ' . (int)$idDietRoot);
        $sql->where('ps.active = 1');
        $rows = Db::getInstance()->executeS($sql);

        $count = 0;
        foreach ($rows as $row) {
            $idProduct = (int)$row['id_product'];
            $idCatDefault = (int)$row['id_category_default'];
            if ($idCatDefault <= 0) { continue; }

            if (!$force) {
                // Skip if product already has ANY category inside this diet subtree (other than the root itself)
                $hasDeep = (bool)Db::getInstance()->getValue('
                    SELECT 1
                    FROM `'._DB_PREFIX_.'category_product` cp2
                    INNER JOIN `'._DB_PREFIX_.'category` c2 ON c2.id_category = cp2.id_category
                    WHERE cp2.id_product='.(int)$idProduct.'
                      AND c2.id_category!='.(int)$idDietRoot.'
                      AND c2.nleft BETWEEN '.(int)$root->nleft.' AND '.(int)$root->nright.'
                    LIMIT 1
                ');
                if ($hasDeep) { continue; }
            }

            $path = $this->getCleanPath($idCatDefault);
            if (!$path) { continue; }

            if ($useCap) {
                if ($mode === 'root') {
                    $endIdx = min(max(0, $typeDepth - 1), count($path) - 1);
                } else {
                    $endIdx = max(0, count($path) - $typeDepth);
                }
            } else {
                $endIdx = max(0, count($path) - $depth);
            }

            $parentId = $idDietRoot;
            for ($i = 0; $i <= $endIdx; $i++) {
                $node = $path[$i];
                $parentId = $this->ensureChildCategory($parentId, $node['name'], $node['id']);
                if ($parentId > 0) { $createdCats += (int)$this->lastWasCreated; }
            }

            if ($parentId > 0) {
                $linked = $this->linkProductToCategory($idProduct, $parentId);
                if ($linked) { $linkedProducts++; }
                $count++;
            }
        }
        return $count;
    }

    protected function ensureChildCategory(int $idParent, string $name, int $idSourceCategory): int
    {
        $db = Db::getInstance();
        $sql = 'SELECT id_diet_subcategory FROM `'._DB_PREFIX_.'dmto_category_map`'
             . ' WHERE id_diet_root='.(int)$idParent.' AND id_source_category='.(int)$idSourceCategory;
        $mapId = (int)$db->getValue($sql);
        if ($mapId > 0) { $this->lastWasCreated = false; return $mapId; }

        $children = Category::getChildren($idParent, $this->idLang);
        foreach ($children as $child) {
            if (Tools::strtolower(trim($child['name'])) === Tools::strtolower(trim($name))) {
                $this->saveMap($idParent, $idSourceCategory, (int)$child['id_category']);
                $this->lastWasCreated = false;
                return (int)$child['id_category'];
            }
        }

        $cat = new Category();
        foreach (Language::getLanguages(false) as $lang) {
            $cat->name[$lang['id_lang']] = $name;
            $cat->link_rewrite[$lang['id_lang']] = Tools::link_rewrite($name);
        }
        $cat->id_parent = $idParent;
        $cat->active = 1;
        $cat->is_root_category = 0;
        $cat->add();
        if (method_exists($cat, 'addShop')) { $cat->addShop($this->idShop); }
        $this->saveMap($idParent, $idSourceCategory, (int)$cat->id);
        $this->lastWasCreated = true;
        return (int)$cat->id;
    }

    protected function saveMap(int $idDietRoot, int $idSourceCategory, int $idDietSubcategory): void
    {
        Db::getInstance()->insert(
            'dmto_category_map',
            [
                'id_diet_root'       => (int)$idDietRoot,
                'id_source_category' => (int)$idSourceCategory,
                'id_diet_subcategory'=> (int)$idDietSubcategory,
            ],
            false,
            true
        );
    }

    protected function linkProductToCategory(int $idProduct, int $idCategory): bool
    {
        $db = Db::getInstance(); $changed = false;
        $exists = (bool)$db->getValue('SELECT 1 FROM `'._DB_PREFIX_.'category_product` WHERE id_category='.(int)$idCategory.' AND id_product='.(int)$idProduct);
        if (!$exists) {
            $db->insert('category_product', ['id_category'=>$idCategory,'id_product'=>$idProduct,'position'=>0], false, true);
            $changed = true;
        }
        $existsShop = (bool)$db->getValue('SELECT 1 FROM `'._DB_PREFIX_.'category_product_shop` WHERE id_category='.(int)$idCategory.' AND id_product='.(int)$idProduct.' AND id_shop='.(int)$this->idShop);
        if (!$existsShop) {
            $db->insert('category_product_shop', ['id_category'=>$idCategory,'id_product'=>$idProduct,'id_shop'=>$this->idShop,'position'=>0], false, true);
            $changed = true;
        }
        return $changed;
    }

    protected function getCleanPath(int $idCategory): array
    {
        $cat = new Category($idCategory, $this->idLang, $this->idShop);
        if (!Validate::isLoadedObject($cat)) { return []; }
        $parents = $cat->getParentsCategories($this->idLang);
        $path = array_reverse($parents);
        $out = [];
        foreach ($path as $p) {
            $name = trim((string)$p['name']);
            if ($name === '' || Tools::strtolower($name) === 'home') { continue; }
            $out[] = ['id' => (int)$p['id_category'], 'name' => $name];
        }
        return $out;
    }

    protected function ensureMapTable(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'dmto_category_map` ('
             . ' `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
             . ' `id_diet_root` INT UNSIGNED NOT NULL,'
             . ' `id_source_category` INT UNSIGNED NOT NULL,'
             . ' `id_diet_subcategory` INT UNSIGNED NOT NULL,'
             . ' PRIMARY KEY (`id`),'
             . ' UNIQUE KEY `uniq_diet_source` (`id_diet_root`,`id_source_category`),'
             . ' KEY `id_diet_subcategory` (`id_diet_subcategory`)'
             . ') ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8mb4;';
        Db::getInstance()->execute($sql);
    }
}
