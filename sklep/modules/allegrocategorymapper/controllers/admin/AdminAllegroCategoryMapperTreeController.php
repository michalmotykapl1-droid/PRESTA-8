<?php
// /modules/allegrocategorymapper/controllers/admin/AdminAllegroCategoryMapperTreeController.php
/**
 * Renders the PrestaShop categories tree used on the "Skanuj produkty po EAN (AJAX)" screen.
 * Each node contains counters:
 *  - total_count       : number of products in this category (leaf-only, sum for parents)
 *  - done_count        : number of products already marked as ZROBIONE
 *  - todo_count        : number of products left to process (total - done)
 *
 * "todo_count" respects module settings:
 *  - ACM_SKIP_DONE (1)          – skip products marked as done
 *  - ACM_SCAN_INCLUDE_INACTIVE  – include inactive products in scan/counters when enabled
 *
 * Works with PS 8.2.x (uses product_shop for multishop awareness).
 */
class AdminAllegroCategoryMapperTreeController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        parent::initContent();

        // Root category
        $rootId = (int)Configuration::get('ACM_ROOT_CATEGORY_ID');
        if (!$rootId) {
            $rootId = (int)Configuration::get('PS_HOME_CATEGORY', 2);
        }

        $includeInactive = (int)Configuration::get('ACM_SCAN_INCLUDE_INACTIVE') ? true : false;
        $skipDone        = (int)Configuration::get('ACM_SKIP_DONE') ? true : false;
        $idShop          = (int)$this->context->shop->id;
        $idLang          = (int)$this->context->language->id;

        // Preload counters for all leaf categories in one go (fast).
        $counters = $this->loadCountersForAllCategories($idShop, $includeInactive, $skipDone);

        // Build tree
        $tree = $this->buildTree($rootId, $idLang, $idShop, $counters);

        $this->context->smarty->assign([
            'categoriesTree' => $tree,
        ]);

        $this->setTemplate('admin/tree_view.tpl');
    }

    /**
     * Load per-category counters. Returns array [id_category => ['total'=>..,'done'=>..,'todo'=>..]]
     */
    protected function loadCountersForAllCategories($idShop, $includeInactive, $skipDone)
    {
        $db = Db::getInstance();

        // Base visibility filter
        $activeWhere = $includeInactive ? '1=1' : 'ps.active = 1';

        // All product counts per category (leaf level – but summing at build time)
        $sqlTotal = "
            SELECT cp.id_category, COUNT(DISTINCT cp.id_product) AS total_count
            FROM "._DB_PREFIX_."category_product cp
            INNER JOIN "._DB_PREFIX_."product_shop ps ON (ps.id_product = cp.id_product AND ps.id_shop = ".(int)$idShop.")
            WHERE $activeWhere
            GROUP BY cp.id_category
        ";
        $totals = [];
        foreach ($db->executeS($sqlTotal) as $row) {
            $totals[(int)$row['id_category']] = (int)$row['total_count'];
        }

        // 'Done' products table – assume module stores done markers here; fall back if not exists
        $doneTable = _DB_PREFIX_."acm_done";
        $tableExists = (bool)$db->getValue("SHOW TABLES LIKE '".pSQL($doneTable)."'");
        $doneCounts = [];
        if ($tableExists) {
            $sqlDone = "
                SELECT cp.id_category, COUNT(DISTINCT cp.id_product) AS done_count
                FROM "._DB_PREFIX_."category_product cp
                INNER JOIN "._DB_PREFIX_."product_shop ps ON (ps.id_product = cp.id_product AND ps.id_shop = ".(int)$idShop.")
                INNER JOIN ".$doneTable." d ON (d.id_product = cp.id_product)
                WHERE $activeWhere
                GROUP BY cp.id_category
            ";
            foreach ($db->executeS($sqlDone) as $row) {
                $doneCounts[(int)$row['id_category']] = (int)$row['done_count'];
            }
        }

        // Compose final counters
        $out = [];
        foreach ($totals as $idCat => $total) {
            $done = isset($doneCounts[$idCat]) ? $doneCounts[$idCat] : 0;
            $todo = $skipDone ? max(0, $total - $done) : $total;
            $out[$idCat] = [
                'total' => (int)$total,
                'done'  => (int)$done,
                'todo'  => (int)$todo,
            ];
        }
        return $out;
    }

    /**
     * Build recursive tree with counters aggregated to parents.
     */
    protected function buildTree($rootId, $idLang, $idShop, array $leafCounters)
    {
        $children = $this->getChildren($rootId, $idLang, $idShop);

        $nodes = [];
        foreach ($children as $row) {
            $nodes[] = $this->buildNode((int)$row['id_category'], $row['name'], $idLang, $idShop, $leafCounters);
        }
        return $nodes;
    }

    protected function buildNode($idCategory, $name, $idLang, $idShop, array $leafCounters)
    {
        $childRows = $this->getChildren($idCategory, $idLang, $idShop);
        $children  = [];
        $total = 0; $done = 0; $todo = 0;

        if (!empty($childRows)) {
            foreach ($childRows as $row) {
                $child = $this->buildNode((int)$row['id_category'], $row['name'], $idLang, $idShop, $leafCounters);
                $children[] = $child;
                $total += $child['total_count'];
                $done  += $child['done_count'];
                $todo  += $child['todo_count'];
            }
        } else {
            // leaf
            if (isset($leafCounters[$idCategory])) {
                $total = (int)$leafCounters[$idCategory]['total'];
                $done  = (int)$leafCounters[$idCategory]['done'];
                $todo  = (int)$leafCounters[$idCategory]['todo'];
            }
        }

        return [
            'id'            => $idCategory,
            'name'          => $name,
            'children'      => $children,
            'total_count'   => (int)$total,
            'done_count'    => (int)$done,
            'todo_count'    => (int)$todo,
        ];
    }

    protected function getChildren($idParent, $idLang, $idShop)
    {
        $sql = "
            SELECT c.id_category, cl.name
            FROM "._DB_PREFIX_."category c
            INNER JOIN "._DB_PREFIX_."category_lang cl ON (cl.id_category = c.id_category AND cl.id_lang = ".(int)$idLang." AND cl.id_shop = ".(int)$idShop.")
            INNER JOIN "._DB_PREFIX_."category_shop cs ON (cs.id_category = c.id_category AND cs.id_shop = ".(int)$idShop.")
            WHERE c.id_parent = ".(int)$idParent."
            ORDER BY c.position ASC
        ";
        return Db::getInstance()->executeS($sql);
    }
}
