<?php
/**
 * /modules/dietamamyto/services/DietStep3Service.php
 *
 * Poprawki:
 * 1. Finalne rozwiązanie problemu z Undefined constant (Tools::strtolower).
 * 2. Poprawiona funkcja normalizeDietPathByShopRoots, aby poprawnie zachowywała GŁÓWNE KATEGORIE (Produkty spożywcze, Suplementy diety).
 */
if (!defined('_PS_VERSION_')) { exit; }

require_once _PS_MODULE_DIR_.'dietamamyto/services/DietConfigService.php';

class DietStep3Service
{
    protected $ctx;
    protected $idShop;
    protected $idLang;

    public function __construct(Context $context = null)
    {
        $this->ctx    = $context ?: Context::getContext();
        $this->idShop = (int)$this->ctx->shop->id;
        $this->idLang = (int)$this->ctx->language->id;
    }

    public function sync(int $dietRootId, array $productIds, bool $force = false): array
    {
        $stats = ['processed' => 0, 'created_categories' => 0, 'linked_products' => 0, 'created_category_names' => [], 'failed_products' => [], 'touched_category_ids' => []];
        if (empty($productIds)) { return $stats; }

        $config = DietConfigService::getInstance();
        $root = new Category((int)$dietRootId, $this->idLang, $this->idShop);
        if (!Validate::isLoadedObject($root)) {
            PrestaShopLogger::addLog('[DMTO Step3 Error] Failed to load root category ID: ' . $dietRootId, 3, null, 'DMTO', null, true);
            return $stats;
        }

        // ODCZYTUJEMY Z INSTANCJI CONFIG (teraz z wymuszeniem min. 3)
        $depth = max(1, (int)$config->treeDepth);
        $mode = (string)$config->treeDepthMode;
        if (!in_array($mode, ['root', 'leaf'])) { $mode = 'root'; }

        $idFeatureProductType = 0; // Pominięto logikę dla treeUseTypeCap
        if ($force && $root->id) {
            $bounds = Db::getInstance()->getRow('SELECT nleft, nright FROM `'._DB_PREFIX_.'category` WHERE id_category='.(int)$root->id);
            if ($bounds) { $idsInTree = Db::getInstance()->executeS('SELECT id_category FROM `'._DB_PREFIX_.'category` WHERE nleft > '.(int)$bounds['nleft'].' AND nright < '.(int)$bounds['nright']);
                if ($idsInTree) { $idsToDelete = array_column($idsInTree, 'id_category'); if (!empty($idsToDelete)) { Db::getInstance()->delete('category_product', 'id_category IN ('.implode(',', array_map('intval', $idsToDelete)).') AND id_product IN ('.implode(',', array_map('intval', $productIds)).')'); } } }
        }

        foreach ($productIds as $idProduct) {
            try { $stats['processed']++; $path = [];
                $leaf = Db::getInstance()->getRow('SELECT c.id_category FROM `'._DB_PREFIX_.'category_product` cp JOIN `'._DB_PREFIX_.'category` c ON c.id_category=cp.id_category WHERE cp.id_product='.(int)$idProduct.' ORDER BY c.level_depth DESC, c.id_category DESC');

                if ($leaf) {
                    $raw_path = $this->getPathNames((int)$leaf['id_category'], 0);
                    PrestaShopLogger::addLog('[DMTO Step3 Debug] Product ID ' . $idProduct . ' (Diet Root '.$dietRootId.') - Raw Path: ' . json_encode($raw_path), 1, null, 'DMTO', null, true);
                } else {
                    $raw_path = [];
                     PrestaShopLogger::addLog('[DMTO Step3 Warning] Product ID ' . $idProduct . ' (Diet Root '.$dietRootId.') - Could not find leaf category.', 2, null, 'DMTO', null, true);
                }

                $normalized_path = $this->normalizeDietPathByShopRoots($raw_path);
                PrestaShopLogger::addLog('[DMTO Step3 Debug] Product ID ' . $idProduct . ' - Normalized Path: ' . json_encode($normalized_path), 1, null, 'DMTO', null, true);

                if (!empty($normalized_path)) {
                    if ($mode === 'root') {
                        $final_path = array_slice($normalized_path, 0, $depth);
                    } else { // mode === 'leaf'
                        $final_path = array_slice($normalized_path, -$depth);
                    }
                    PrestaShopLogger::addLog('[DMTO Step3 Debug] Product ID ' . $idProduct . ' - Final Path (Mode: '.$mode.', Depth: '.$depth.'): ' . json_encode($final_path), 1, null, 'DMTO', null, true);
                } else {
                    $final_path = [];
                }
                $path = $final_path;

                if (empty($path)) {
                    PrestaShopLogger::addLog('[DMTO Step3 Warning] Product ID ' . $idProduct . ' - Path empty before ensurePath, skipping.', 2, null, 'DMTO', null, true);
                    $stats['failed_products'][$idProduct] = 'Nie można ustalić ścieżki (pusta po normalizacji/cięciu)'; continue;
                }

                $targetId = $this->ensurePath($root->id, $path, $stats);
                 PrestaShopLogger::addLog('[DMTO Step3 Debug] Product ID ' . $idProduct . ' - ensurePath created/found target ID: ' . $targetId . ' for path: '. json_encode($path), 1, null, 'DMTO', null, true);

                if ($targetId > 0 && $targetId != $root->id) { $stats['touched_category_ids'][] = $targetId;
                    if (!Db::getInstance()->getValue('SELECT 1 FROM `'._DB_PREFIX_.'category_product` WHERE id_category='.(int)$targetId.' AND id_product='.(int)$idProduct)) {
                        Db::getInstance()->insert('category_product', ['id_category' => $targetId, 'id_product' => $idProduct, 'position' => 0]); $stats['linked_products']++; }
                 } else if ($targetId <= 0) {
                     PrestaShopLogger::addLog('[DMTO Step3 Error] Product ID ' . $idProduct . ' - ensurePath returned invalid target ID: ' . $targetId, 3, null, 'DMTO', null, true);
                     $stats['failed_products'][$idProduct] = 'Błąd tworzenia/znajdowania kategorii dla ścieżki: ' . json_encode($path);
                 }

            } catch (Exception $e) {
                 PrestaShopLogger::addLog('[DMTO Step3 Exception] Product ID ' . $idProduct . ' - Exception: ' . $e->getMessage(), 3, null, 'DMTO', null, true);
                 $stats['failed_products'][$idProduct] = 'Wyjątek: ' . $e->getMessage(); continue;
            }
        }
        $stats['touched_category_ids'] = array_unique($stats['touched_category_ids']); return $stats;
    }

    protected function normalizeDietPathByShopRoots(array $path)
    {
        if (empty($path)) { return []; }
        
        // ⭐ NOWA LOGIKA ZACHOWANIA GŁÓWNYCH ROOTÓW
        $retainedRoots = ['produkty spożywcze','produkty spozywcze','suplementy diety', 'kosmetyki i higiena', 'aromaterapia', 'dom', 'dom / sprzątanie', 'dom/sprzątanie', 'dom i sprzątanie', 'akcesoria'];
        $removableRoots = ['supermarket', 'zdrowie', 'domowa apteczka'];

        // Używamy Tools::strtolower jako ciągu znaków (ciągłe problemy z ładowaniem klasy Tools)
        $lower = array_map('Tools::strtolower', $path);
        
        $startIndex = 0;
        
        // 1. Opcjonalnie usuń "Supermarket", "Zdrowie" etc. z początku
        while(isset($lower[$startIndex]) && in_array($lower[$startIndex], $removableRoots, true)) {
            $startIndex++;
        }

        // 2. Znajdź pierwszą GŁÓWNĄ kategorię (Produkty spożywcze, Suplementy diety) i zacznij od niej.
        for ($i = $startIndex; $i < count($lower); $i++) {
            if (in_array($lower[$i], $retainedRoots, true)) {
                return array_slice($path, $i);
            }
        }

        // 3. Jeśli nie znaleziono żadnego znanego roota, zwróć to, co zostało po usunięciu RemovableRoots.
        return array_slice($path, $startIndex);
    }

    public function dietLabelFromRootName($name)
    {
        $name = trim(Tools::strtolower((string)$name));
        $map = ['bez glutenu' => 'Dieta: Bez glutenu', 'bez laktozy' => 'Bez: Laktozy', 'wegańskie' => 'Dieta: Wegańska', 'wegańska' => 'Dieta: Wegańska', 'wegetariańskie' => 'Dieta: Wegetariańska', 'wegetariańska' => 'Dieta: Wegetariańska', 'keto / low-carb' => 'Dieta: Keto / Low-Carb', 'keto & low-carb' => 'Dieta: Keto / Low-Carb', 'low-carb' => 'Dieta: Keto / Low-Carb', 'niski indeks glikemiczny' => 'Dieta: Niski Indeks Glikemiczny', 'bio / organic' => 'Certyfikat: BIO', 'bio' => 'Certyfikat: BIO', 'certyfikat: bio' => 'Certyfikat: BIO', 'bez cukru' => 'Bez: Cukru'];
        return isset($map[$name]) ? $map[$name] : null;
    }

    public function resolveFeatureIdForRoot($rootName)
    {
        $featureName = $this->dietLabelFromRootName($rootName);
        if ($featureName === null) { return 0; }
        $idFeature = (int)Db::getInstance()->getValue('SELECT id_feature FROM `'._DB_PREFIX_.'feature_lang` WHERE name = "'.pSQL($featureName).'" AND id_lang = '.(int)$this->idLang);
        return $idFeature;
    }

    public function getPathNames(int $idCategory, int $depth): array
    {
        $current = new Category($idCategory, $this->idLang, $this->idShop);
        if (!Validate::isLoadedObject($current)) { return []; }

        $parents = $current->getParentsCategories($this->idLang);
        $clean = [];

        if (is_array($parents)) {
            $parents = array_reverse($parents);
            foreach ($parents as $c) {
                $nm = trim(isset($c['name']) ? $c['name'] : '');
                $low = Tools::strtolower($nm);
                if ($c['id_category'] == Configuration::get('PS_ROOT_CATEGORY') || $c['id_category'] == Configuration::get('PS_HOME_CATEGORY') || $nm === '' || $low === 'home' || $low === 'strona główna' || $low === 'homepage') {
                    continue;
                }
                $clean[] = $nm;
            }
        }

        $currentName = trim($current->name);
        $currentId = (int)$current->id;
        if ($currentId != Configuration::get('PS_ROOT_CATEGORY') && $currentId != Configuration::get('PS_HOME_CATEGORY') && $currentName !== '' && Tools::strtolower($currentName) !== 'home' && Tools::strtolower($currentName) !== 'strona główna' && Tools::strtolower($currentName) !== 'homepage') {
            $clean[] = $currentName;
        }

        if ($depth > 0) {
            $clean = array_slice($clean, 0, $depth);
        }

        return $clean;
    }

    protected function ensurePath(int $rootId, array $path, array &$stats): int
    {
        $parentId = (int)$rootId;
        $fullPathStringForLog = [];

        foreach ($path as $nm) {
            $nm = trim((string)$nm);
            if ($nm === '') { continue; }
            $fullPathStringForLog[] = $nm;

            $q = new DbQuery();
            $q->select('c.id_category')
              ->from('category', 'c')
              ->innerJoin('category_lang', 'cl', 'cl.id_category=c.id_category AND cl.id_lang='.(int)$this->idLang.' AND cl.id_shop='.(int)$this->idShop)
              ->innerJoin('category_shop', 'cs', 'cs.id_category=c.id_category AND cs.id_shop='.(int)$this->idShop)
              ->where('c.id_parent='.(int)$parentId)
              ->where("cl.name = '".pSQL($nm)."'");
            $childId = (int)Db::getInstance()->getValue($q);

            if ($childId <= 0) {
                $cat = new Category(null, null, $this->idShop);
                foreach (Language::getLanguages(false) as $lang) {
                    $cat->name[(int)$lang['id_lang']] = $nm;
                    $cat->link_rewrite[(int)$lang['id_lang']] = Tools::link_rewrite($nm);
                }
                $cat->id_parent = $parentId;
                $cat->active = 1;
                $cat->id_shop_default = $this->idShop;

                if (!$cat->add()) {
                    PrestaShopLogger::addLog('[DMTO Step3 Error] Failed to add category: "' . $nm . '" under parent ID: ' . $parentId, 3, null, 'DMTO', null, true);
                    return 0;
                }
                $childId = (int)$cat->id;
                $cat->associateTo($this->idShop);

                $stats['created_categories'] = isset($stats['created_categories']) ? (int)$stats['created_categories'] + 1 : 1;
                $stats['created_category_names'][] = implode(' > ', $fullPathStringForLog);
            }
            $parentId = $childId;
        }
        return (int)$parentId;
    }
}