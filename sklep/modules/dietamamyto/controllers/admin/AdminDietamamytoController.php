<?php
/**
 * /modules/dietamamyto/controllers/admin/AdminDietamamytoController.php
 *
 * Poprawki:
 * 1. Dodano `Configuration::loadConfiguration();` i `DietConfigService::resetInstance();`
 * NA POCZĄTKU `ajaxProcessStep3Chunk`, aby zapewnić odczyt świeżej konfiguracji
 * w KAŻDYM żądaniu przetwarzającym paczkę produktów.
 */

if (!defined('_PS_VERSION_')) { exit; }

require_once _PS_MODULE_DIR_.'dietamamyto/services/DietConfigService.php';
require_once _PS_MODULE_DIR_.'dietamamyto/services/DietStep1Service.php';
require_once _PS_MODULE_DIR_.'dietamamyto/services/DietStep2Service.php';
require_once _PS_MODULE_DIR_.'dietamamyto/services/DietStep3Service.php';
require_once _PS_MODULE_DIR_.'dietamamyto/services/DietStatsService.php';
require_once _PS_MODULE_DIR_.'dietamamyto/services/ProductCategorizer.php';

class AdminDietamamytoController extends ModuleAdminController
{
    private const DMTO_FORCED_DIET_PARENT_ID = 167;

    private function dmtoGetForcedDietParentId(): int
    {
        return (int) self::DMTO_FORCED_DIET_PARENT_ID;
    }

    protected function dmtoFindFeatureIdByDietLabel(string $dietLabel): int
    {
        $dl = Tools::strtolower(trim($dietLabel));
        switch ($dl) {
            case Tools::strtolower('BIO'): case Tools::strtolower('Bio / Organic'):
                $candidates = ['Certyfikat: BIO']; break;
            case Tools::strtolower('Bez cukru'):
                $candidates = ['Bez: Cukru']; break;
            case Tools::strtolower('Bez laktozy'):
                $candidates = ['Bez: Laktozy']; break;
            case Tools::strtolower('Bez glutenu'):
                $candidates = ['Dieta: Bez glutenu']; break;
            case Tools::strtolower('Wegańskie'): case Tools::strtolower('Wegańska'):
                $candidates = ['Dieta: Wegańska']; break;
            case Tools::strtolower('Wegetariańskie'): case Tools::strtolower('Wegetariańska'):
                $candidates = ['Dieta: Wegetariańska']; break;
            case Tools::strtolower('Keto / Low-Carb'): case Tools::strtolower('Keto & Low-Carb'): case Tools::strtolower('Low-Carb'):
                $candidates = ['Dieta: Keto / Low-Carb']; break;
            case Tools::strtolower('Niski Indeks Glikemiczny'):
                $candidates = ['Dieta: Niski Indeks Glikemiczny']; break;
            default:
                $candidates = ['Dieta: ' . $dietLabel, $dietLabel]; break;
        }
        $db = Db::getInstance();
        foreach ($candidates as $cand) {
            $idLang = (int)$this->context->language->id;
            $q = new DbQuery();
            $q->select('f.id_feature')->from('feature', 'f')
              ->innerJoin('feature_lang', 'fl', 'fl.id_feature=f.id_feature AND fl.id_lang='.(int)$idLang)
              ->where('fl.name = "'.pSQL($cand).'"');
            $id = (int)Db::getInstance()->getValue($q);
            if ($id > 0) { return $id; }
        }
        return 0;
    }

    protected function dmtoEnsureDietRootCategories(): array
    {
        $idShop = (int)$this->context->shop->id;
        $idLang = (int)$this->context->language->id;
        $idParentToCreateUnder = $this->dmtoGetForcedDietParentId();

        $rootNames = ['Bio / Organic', 'Bez cukru', 'Bez laktozy', 'Bez glutenu', 'Wegańskie', 'Wegetariańskie', 'Keto / Low-Carb', 'Niski Indeks Glikemiczny'];
        $ids = [];
        foreach ($rootNames as $rootName) {
            $id = (int)Db::getInstance()->getValue('SELECT c.id_category FROM `'._DB_PREFIX_.'category_lang` cl INNER JOIN `'._DB_PREFIX_.'category` c ON (c.id_category = cl.id_category AND c.id_parent = '.(int)$idParentToCreateUnder.') INNER JOIN `'._DB_PREFIX_.'category_shop` cs ON (cs.id_category = c.id_category) WHERE cs.id_shop='.(int)$idShop.' AND cl.id_lang='.(int)$idLang.' AND cl.name = "'.pSQL($rootName).'"');
            if ($id > 0) { $ids[] = $id; continue; }
            $cat = new Category(); $cat->id_parent = $idParentToCreateUnder; $cat->active = 1; $cat->is_root_category = 0; $cat->link_rewrite = []; $cat->name = [];
            foreach (Language::getLanguages(false) as $lng) { $cat->name[$lng['id_lang']] = $rootName; $cat->link_rewrite[$lng['id_lang']] = Tools::link_rewrite($rootName); }
            if ($cat->add()) { $cat->associateTo([$idShop]); $ids[] = (int)$cat->id; }
        }
        return array_values(array_unique(array_filter($ids)));
    }

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        $this->meta_title = 'DIETA? MAMY TO – Zarządzanie';
    }

    protected function ajaxJson($arr){ header('Content-Type: application/json'); echo json_encode($arr); }

    public function postProcess()
    {
        if (Tools::getIsset('ajax') && Tools::getIsset('action')) {
            $action = Tools::getValue('action');
            if ($action === 'Step2Start') { $this->ajaxProcessStep2Start(); exit; }
            if ($action === 'Step2Chunk') { $this->ajaxProcessStep2Chunk(); exit; }
            if ($action === 'CleanupUnusedValues') { $this->ajaxProcessCleanupUnusedValues(); exit; }
            if ($action === 'Step3Start') { $this->ajaxProcessStep3Start(); exit; }
            if ($action === 'Step3Chunk') { $this->ajaxProcessStep3Chunk(); exit; }
        }

        $saved = false;
        if (Tools::isSubmit('submitGlobalSettings')) { $this->saveGlobalSettings(); DietConfigService::resetInstance(); $this->confirmations[] = $this->l('Zapisano ustawienia globalne.'); $saved = true; }
        if (Tools::isSubmit('saveTypeSettings')) { $this->saveStep2Settings(); DietConfigService::resetInstance(); $this->confirmations[] = $this->l('Zapisano ustawienia Kroku 2.'); $saved = true; }
        if (Tools::isSubmit('saveTreeSettings')) { $this->saveStep3Settings(); DietConfigService::resetInstance(); $this->confirmations[] = $this->l('Zapisano ustawienia Kroku 3.'); $saved = true; }

        if (Tools::isSubmit('submitAnalyzeAddFeatures') || Tools::isSubmit('submitAnalyzeAndAddFeatures')) {
            try { $force = (bool)Tools::getValue('dmto_step1_force', false); $svc = new DietStep1Service(); $res = $svc->run($force); $this->confirmations[] = sprintf($this->l('Krok 1 Zakończony: Przeanalizowano %d produktów. Zaktualizowano %d (usunięto %d cech, dodano %d cech).'), (int)($res['processed'] ?? 0), (int)($res['updated'] ?? 0), (int)($res['features_removed'] ?? 0), (int)($res['features_added'] ?? 0)); } catch (Exception $e) { $this->errors[] = $this->l('Błąd w Kroku 1: ').$e->getMessage(); }
        }
        if (Tools::isSubmit('submitAssignProductTypes')) {
            if (!$saved) { $this->saveStep2Settings(); DietConfigService::resetInstance(); $this->confirmations[] = $this->l('Zapisano ustawienia Kroku 2.'); } $config = DietConfigService::getInstance();
            try { $stats = DietStep2Service::run($config->typeDepth, $config->forceRebuild); $this->confirmations[] = sprintf($this->l('Krok 2: zaktualizowano %d produktów, dodano %d wartości cechy.'), (int)($stats['products_updated'] ?? 0), (int)($stats['feature_values_created'] ?? 0)); } catch (Exception $e) { $this->errors[] = $this->l('Błąd w Kroku 2: ').$e->getMessage(); }
            if ($config->cleanupUnused) { $cleanupSummary = $this->cleanupUnusedProductTypeValues(); $this->confirmations[] = sprintf($this->l('Krok 2 Czyszczenie: Usunięto %d nieużywanych wartości cechy.'), $cleanupSummary['count']); }
            if ($config->createTreeAfterStep2) { $this->runDietTreeSync($config->forceRebuild); } if ($config->autoReindex) { $this->triggerFacetReindex(); }
        }

        if (Tools::isSubmit('submitDietTreeSync') || Tools::isSubmit('submitBulkAssignCategories')) {
            if (!$saved) {
                $this->saveStep3Settings();
                Configuration::loadConfiguration(); // Wymuś odświeżenie
                DietConfigService::resetInstance();
                $this->confirmations[] = $this->l('Zapisano ustawienia Kroku 3.');
            } else {
                Configuration::loadConfiguration(); // Wymuś odświeżenie
                DietConfigService::resetInstance();
            }
            $config = DietConfigService::getInstance();
            $force = (bool)Tools::getValue('dmto_tree_force', $config->treeForce);
            $this->runDietTreeSync($force);
            if ($config->treeCleanupUnused) {
                $deletedCount = $this->cleanupUnusedDietCategories();
                $this->confirmations[] = sprintf($this->l('Krok 3 Czyszczenie: Usunięto %d pustych kategorii dietetycznych.'), $deletedCount);
            }
        }

        return parent::postProcess();
    }

    public function ajaxProcessStep2Start()
    {
        // Ustawienia Kroku 2 są zapisywane osobno (przycisk "Zapisz ustawienia" lub pełny bieg).
        // Ten endpoint AJAX tylko korzysta z już zapisanej konfiguracji – nie nadpisuje jej pustymi wartościami.
        Configuration::loadConfiguration();
        DietConfigService::resetInstance();
        $config = DietConfigService::getInstance();
        $idShop = (int)$this->context->shop->id;
        $idLang = (int)$this->context->language->id;
        $featureName = 'Rodzaj produktu';
        $sqlFeat = sprintf("SELECT id_feature FROM `%sfeature_lang` WHERE name='%s' AND id_lang=%d", _DB_PREFIX_, pSQL($featureName), (int)$idLang);
        $idFeature = (int)Db::getInstance()->getValue($sqlFeat);
        $where = $config->processOnlyActive ? " WHERE ps.active=1 " : " WHERE 1 ";
        $join  = "";
        if (!$config->forceRebuild && $idFeature > 0) {
            $join  .= sprintf(' LEFT JOIN `%sfeature_product` fp ON (fp.id_product=p.id_product AND fp.id_feature=%d) ', _DB_PREFIX_, (int)$idFeature);
            $where .= ' AND fp.id_feature IS NULL ';
        }
        $sql = "FROM `%sproduct` p INNER JOIN `%sproduct_shop` ps ON (ps.id_product=p.id_product AND ps.id_shop=%d) %s %s";
        $sql = sprintf($sql, _DB_PREFIX_, _DB_PREFIX_, (int)$idShop, $join, $where);
        $total = (int)Db::getInstance()->getValue("SELECT COUNT(p.id_product) " . $sql);
        if ($config->testModeEnabled && $config->testModeLimit > 0) {
            $total = min($total, $config->testModeLimit);
        }
        $minId = (int)Db::getInstance()->getValue("SELECT MIN(p.id_product) " . $sql);
        $job = str_replace('.', '', uniqid('dmto_s2_', true));
        $state = ['total' => $total, 'done'  => 0, 'last_id' => max(0, $minId - 1)];
        Configuration::updateValue('DMTO_S2_JOB_'.$job, json_encode($state));
        return $this->ajaxJson(['ok'=>true, 'job'=>$job, 'total'=>$total]);
    }

    public function ajaxProcessStep2Chunk()
    {
        $job = Tools::getValue('job');
        $raw = Configuration::get('DMTO_S2_JOB_'.$job);
        if (!$raw) { return $this->ajaxJson(['ok'=>false, 'error'=>'JOB_NOT_FOUND']); }
        $state = json_decode($raw, true);
        if (!is_array($state) || $state['done'] >= $state['total']) {
            Configuration::deleteByName('DMTO_S2_JOB_'.$job);
            return $this->ajaxJson(['ok'=>true, 'finished'=>true, 'done'=>($state['done'] ?? 0), 'total'=>($state['total'] ?? 0), 'percent'=>100]);
        }
        $config = DietConfigService::getInstance();
        $idShop = (int)$this->context->shop->id;
        $featureName = 'Rodzaj produktu';
        $sqlFeat = sprintf("SELECT id_feature FROM `%sfeature_lang` WHERE name='%s' AND id_lang=%d", _DB_PREFIX_, pSQL($featureName), (int)$this->context->language->id);
        $idFeature = (int)Db::getInstance()->getValue($sqlFeat);
        $where = ' p.id_product > '.(int)$state['last_id'].' ';
        if ($config->processOnlyActive) { $where .= ' AND ps.active=1 '; }
        $join  = '';
        if (!$config->forceRebuild && $idFeature > 0) {
            $join  .= sprintf(' LEFT JOIN `%sfeature_product` fp ON (fp.id_product=p.id_product AND fp.id_feature=%d) ', _DB_PREFIX_, (int)$idFeature);
            $where .= ' AND fp.id_feature IS NULL ';
        }
        $limit = ($config->testModeEnabled && $config->testModeLimit > 0) ? min(200, $state['total'] - $state['done']) : 200;
        $sql = sprintf("SELECT p.id_product FROM `%sproduct` p INNER JOIN `%sproduct_shop` ps ON (ps.id_product=p.id_product AND ps.id_shop=%d) %s WHERE %s ORDER BY p.id_product ASC LIMIT %d", _DB_PREFIX_, _DB_PREFIX_, (int)$idShop, $join, $where, $limit);
        $ids = array_column(Db::getInstance()->executeS($sql), 'id_product');
        if (!$ids) {
            $state['done'] = $state['total'];
            Configuration::updateValue('DMTO_S2_JOB_'.$job, json_encode($state));
            return $this->ajaxJson(['ok'=>true, 'finished'=>true, 'done'=>$state['done'], 'total'=>$state['total'], 'percent'=>100]);
        }
        $stats = ProductCategorizer::assignProductTypesForIds($ids, $config->typeDepth, $config->forceRebuild);
        $state['done'] += count($ids);
        $state['last_id'] = max($state['last_id'], max($ids));
        Configuration::updateValue('DMTO_S2_JOB_'.$job, json_encode($state));
        $percent = $state['total'] > 0 ? min(100, (int)floor($state['done'] * 100 / $state['total'])) : 100;
        return $this->ajaxJson(['ok'=>true, 'finished'=>($state['done'] >= $state['total']), 'done'=>$state['done'], 'total'=>$state['total'], 'percent'=>$percent, 'batch_stats'=>$stats]);
    }

    public function ajaxProcessCleanupUnusedValues()
    {
        try {
            $cleanupSummary = $this->cleanupUnusedProductTypeValues();
            $this->ajaxJson(['ok' => true, 'deleted_summary' => $cleanupSummary]);
        } catch (Exception $e) { $this->ajaxJson(['ok' => false, 'error' => $e->getMessage()]); }
    }

    public function ajaxProcessStep3Start()
    {
        // Ustawienia Kroku 3 są zapisywane z formularza (przycisk "Zapisz ustawienia" / synchronizacja).
        // Tutaj jedynie odświeżamy konfigurację, nie nadpisujemy jej pustymi wartościami z żądania AJAX.
        Configuration::loadConfiguration();
        DietConfigService::resetInstance();
        $config = DietConfigService::getInstance();

        $idShop = (int)$this->context->shop->id;
        $onlyActive = (int)$config->processOnlyActive;
        $ignoreSkuEnabled = (int)Configuration::get('DIETAMAMYTO_SKU_IGNORE_ENABLED', 1);
        $ignoreSkuPrefix = (string)Configuration::get('DIETAMAMYTO_SKU_IGNORE_PREFIX', '');
        $allowedCsv = (string)Configuration::get('DIETAMAMYTO_ALLOWED_CATEGORIES', '');
        $allowed = array_values(array_filter(array_map('intval', preg_split('/[\s,;]+/',$allowedCsv,-1,PREG_SPLIT_NO_EMPTY))));
        $restrictCategories = !empty($allowed);
        $allowedFull = [];
        if ($restrictCategories) {
            $in = implode(',', $allowed);
            $q = new DbQuery();
            $q->select('DISTINCT c2.id_category')->from('category','c1')->innerJoin('category','c2','c2.nleft BETWEEN c1.nleft AND c1.nright')->innerJoin('category_shop','cs','cs.id_category=c2.id_category AND cs.id_shop='.$idShop)->where('c1.id_category IN ('.$in.')');
            $rows = Db::getInstance()->executeS($q) ?: [];
            foreach ($rows as $r) { $allowedFull[] = (int)$r['id_category']; }
            if (empty($allowedFull)) { $restrictCategories = false; }
        }
        $dietRootIds = $this->getDietRoots();
        if (empty($dietRootIds)) { $dietRootIds = $this->dmtoEnsureDietRootCategories(); }
        $svc = new DietStep3Service($this->context);
        $jobPlan = [];
        $limit = ($config->testModeEnabled && $config->testModeLimit > 0) ? (int)$config->testModeLimit : 0;
        $allUnique = [];
        foreach ($dietRootIds as $rootId) {
            if ($rootId == $this->dmtoGetForcedDietParentId()) {
                $parentCatTest = new Category($rootId, $this->context->language->id);
                if (Validate::isLoadedObject($parentCatTest) && Tools::strtolower($parentCatTest->name) == 'produkty dopasowane do diety') { continue; }
            }
            if ($limit > 0 && count($allUnique) >= $limit) { break; }
            $cat = new Category($rootId, $this->context->language->id);
            if (!Validate::isLoadedObject($cat)) { continue; }
            $simpleLabel = $cat->name;
            $idFeatureDiet = (int)$this->dmtoFindFeatureIdByDietLabel($simpleLabel);
            if ($idFeatureDiet <= 0) { continue; }
            $idsQ = new DbQuery();
            $idsQ->select('DISTINCT p.id_product')->from('product', 'p')->innerJoin('product_shop','ps','ps.id_product=p.id_product AND ps.id_shop='.$idShop)->innerJoin('feature_product','fp','fp.id_product=p.id_product')->innerJoin('feature_value_lang','fvl','fvl.id_feature_value=fp.id_feature_value AND fvl.id_lang='.(int)$this->context->language->id)->where('fp.id_feature='.(int)$idFeatureDiet)->where('LOWER(fvl.value) IN ("tak","yes","true","1")');
            if ($onlyActive) { $idsQ->where('ps.active=1'); }
            if ($ignoreSkuEnabled && $ignoreSkuPrefix !== '') { $idsQ->where("p.reference NOT LIKE '".pSQL($ignoreSkuPrefix)."%'"); }
            if (!empty($restrictCategories)) { $idsQ->innerJoin('category_product','cp','cp.id_product=p.id_product')->where('cp.id_category IN ('.implode(',', $allowedFull).')'); }
            $count = 0;
            $uniqRows = Db::getInstance()->executeS($idsQ) ?: [];
            if ($uniqRows) {
                $productsForThisRoot = 0;
                foreach ($uniqRows as $ur) {
                    $pid = (int)$ur['id_product'];
                    if ($limit > 0 && count($allUnique) >= $limit && !isset($allUnique[$pid])) { continue; }
                    $allUnique[$pid] = 1;
                    $productsForThisRoot++;
                }
                $count = $productsForThisRoot;
            }
            if ($count > 0) { $jobPlan[] = ['root_id' => (int)$rootId, 'feature_id' => (int)$idFeatureDiet, 'count' => (int)$count]; }
        }
        $jobId = 'dmto_s3_' . str_replace('.', '', uniqid('', true));
        $jobState = ['plan' => $jobPlan, 'total' => (int)count($allUnique), 'seen_products' => [], 'done' => 0, 'current_plan_index' => 0, 'current_offset' => 0, 'restrict' => $restrictCategories ? $allowedFull : []];
        Configuration::updateValue($jobId, json_encode($jobState));
        return $this->ajaxJson(['ok' => true, 'job' => $jobId, 'total' => (int)count($allUnique), 'seen' => [], 'roots' => count($jobPlan)]);
    }

    public function ajaxProcessStep3Chunk()
    {
        // ⭐ DODANO: Wymuś odświeżenie konfiguracji w KAŻDEJ paczce AJAX
        Configuration::loadConfiguration();
        DietConfigService::resetInstance();

        $jobId = Tools::getValue('job');
        $rawState = Configuration::get($jobId);
        if (!$rawState) { return $this->ajaxJson(['ok' => false, 'error' => 'JOB_NOT_FOUND']); }
        $state = json_decode($rawState, true);
        $chunkSize = 100;
        $config = DietConfigService::getInstance(); // Teraz powinno być świeże
        $idShop = (int)$this->context->shop->id;
        $onlyActive = (int)$config->processOnlyActive;
        $ignoreSkuEnabled = (int)Configuration::get('DIETAMAMYTO_SKU_IGNORE_ENABLED', 1);
        $ignoreSkuPrefix = (string)Configuration::get('DIETAMAMYTO_SKU_IGNORE_PREFIX', '');
        $restrictCats = isset($state['restrict']) && is_array($state['restrict']) && !empty($state['restrict']) ? array_map('intval',$state['restrict']) : [];

        if (!isset($state['plan'][$state['current_plan_index']])) {
            Configuration::deleteByName($jobId);
            return $this->ajaxJson(['ok' => true, 'finished' => true, 'done' => (int)$state['done'], 'total' => (int)$state['total'], 'percent' => 100]);
        }
        $currentPlan = $state['plan'][$state['current_plan_index']];
        $rootId = (int)$currentPlan['root_id'];
        $idFeature = (int)$currentPlan['feature_id'];
        $offset = (int)$state['current_offset'];
        $limit = (int)$currentPlan['count'];

        $q = new DbQuery();
        $q->select('DISTINCT p.id_product')->from('product','p')->innerJoin('product_shop','ps','ps.id_product=p.id_product AND ps.id_shop='.$idShop)->innerJoin('feature_product','fp','fp.id_product=p.id_product')->innerJoin('feature_value_lang','fvl','fvl.id_feature_value=fp.id_feature_value AND fvl.id_lang='.(int)$this->context->language->id)->where('fp.id_feature='.$idFeature)->where('LOWER(fvl.value) IN ("tak","yes","true","1")');
        if ($onlyActive) { $q->where('ps.active=1'); }
        if ($ignoreSkuEnabled && $ignoreSkuPrefix !== '') { $q->where("p.reference NOT LIKE '".pSQL($ignoreSkuPrefix)."%'"); }
        if (!empty($restrictCats)) { $q->innerJoin('category_product','cp','cp.id_product=p.id_product')->where('cp.id_category IN ('.implode(',', $restrictCats).')'); }

        $globalLimit = ($config->testModeEnabled && $config->testModeLimit > 0) ? (int)$config->testModeLimit : 0;
        if ($globalLimit > 0) { if ($state['done'] + $chunkSize > $globalLimit) { $chunkSize = max(0, $globalLimit - $state['done']); } }
        if ($chunkSize <= 0 && $globalLimit > 0) { $state['done'] = $state['total']; }

        $q->orderBy('p.id_product ASC')->limit($chunkSize, $offset);
        $rows = ($chunkSize > 0) ? (Db::getInstance()->executeS($q) ?: []) : [];
        $productIds = array_map('intval', array_column($rows, 'id_product'));

        $svc = new DietStep3Service($this->context);
        $force = (bool)$config->treeForce;
        $batchStats = ['processed'=>0, 'created_categories'=>0, 'linked_products'=>0, 'touched_category_ids'=>[]];
        $processedInThisBatch = 0;

        if (!empty($productIds)) {
            $newProductsInBatch = [];
            if (!isset($state['seen_products'])) { $state['seen_products'] = []; }
            foreach ($productIds as $pid) {
                if ($globalLimit > 0 && count($state['seen_products']) >= $globalLimit && !isset($state['seen_products'][$pid])) { continue; }
                if (!isset($state['seen_products'][$pid])) { $newProductsInBatch[] = $pid; $state['seen_products'][$pid] = 1; }
            }
            $processedInThisBatch = count($newProductsInBatch);
            $stats = $svc->sync($rootId, $productIds, $force); // sync użyje świeżego configa
            if (is_array($stats)) {
                foreach (['processed','created_categories','linked_products'] as $k) { if (isset($stats[$k])) { $batchStats[$k] += (int)$stats[$k]; } }
                if (!empty($stats['touched_category_ids'])) { foreach ($stats['touched_category_ids'] as $cid) { $batchStats['touched_category_ids'][$cid] = true; } }
            }
            $state['current_offset'] += count($productIds);
            $state['done'] += $processedInThisBatch;
        }

        if (empty($productIds) || $state['current_offset'] >= $limit || ($globalLimit > 0 && $state['done'] >= $globalLimit)) {
            $state['current_plan_index']++;
            $state['current_offset'] = 0;
        }

        $finished = !isset($state['plan'][$state['current_plan_index']]);
        if ($finished) { $state['done'] = $state['total']; }

        Configuration::updateValue($jobId, json_encode($state));
        $percent = ($state['total'] > 0) ? min(100, (int)floor($state['done'] * 100 / $state['total'])) : 100;
        return $this->ajaxJson(['ok' => true, 'finished' => $finished, 'done' => (int)$state['done'], 'total' => (int)$state['total'], 'percent' => $percent, 'batch_stats' => $batchStats]);
    }

    protected function saveGlobalSettings()
    {
        Configuration::updateValue('DIETAMAMYTO_PROCESS_ONLY_ACTIVE', (int)Tools::getValue('dmto_process_only_active'));
        Configuration::updateValue('DIETAMAMYTO_SKU_IGNORE_ENABLED', (int)Tools::getValue('dmto_sku_ignore_enabled'));
        Configuration::updateValue('DIETAMAMYTO_SKU_IGNORE_PREFIX', (string)Tools::getValue('dmto_sku_ignore_prefix'));
        Configuration::updateValue('DIETAMAMYTO_TEST_MODE_ENABLED', (int)Tools::getValue('dmto_test_mode_enabled'));
        Configuration::updateValue('DIETAMAMYTO_TEST_MODE_LIMIT', (int)Tools::getValue('dmto_test_mode_limit'));
        $allowed = Tools::getValue('dmto_allowed_categories');
        if (!is_array($allowed)) { $allowed = (array)$allowed; }
        $allowed = array_values(array_filter(array_map('intval', $allowed)));
        Configuration::updateValue('DIETAMAMYTO_ALLOWED_CATEGORIES', implode(',', $allowed));
    }

    protected function saveStep2Settings()
    {
        Configuration::updateValue('DIETAMAMYTO_TYPE_DEPTH', max(1, min(6, (int)Tools::getValue('dmto_type_depth'))));
        $depthMode = (string)Tools::getValue('dmto_depth_mode');
        if (!in_array($depthMode, ['leaf', 'root'])) { $depthMode = 'leaf'; }
        Configuration::updateValue('DIETAMAMYTO_DEPTH_MODE', $depthMode);
        Configuration::updateValue('DIETAMAMYTO_AUTO_REINDEX', (int)Tools::getValue('dmto_auto_reindex'));
        Configuration::updateValue('DIETAMAMYTO_FORCE_REBUILD', (int)Tools::getValue('dmto_force_rebuild'));
        Configuration::updateValue('DIETAMAMYTO_CREATE_TREE', (int)Tools::getValue('dmto_create_tree'));
        Configuration::updateValue('DIETAMAMYTO_CLEANUP_UNUSED', (int)Tools::getValue('dmto_cleanup_unused'));
    }

    protected function saveStep3Settings()
    {
        Configuration::updateValue('DIETAMAMYTO_TREE_USE_TYPE_CAP', (int)Tools::getValue('dmto_tree_use_type_cap'));
        Configuration::updateValue('DIETAMAMYTO_TREE_DEPTH', max(1, min(6, (int)Tools::getValue('dmto_tree_depth'))));
        $treeDepthMode = (string)Tools::getValue('dmto_tree_depth_mode');
        if (!in_array($treeDepthMode, ['leaf', 'root'])) { $treeDepthMode = 'root'; }
        Configuration::updateValue('DIETAMAMYTO_TREE_DEPTH_MODE', $treeDepthMode);
        $ids = (string)Tools::getValue('dmto_diet_root_ids', '');
        $ids = implode(',', array_filter(array_map('intval', preg_split('/[\s,;]+/', $ids, -1, PREG_SPLIT_NO_EMPTY))));
        Configuration::updateValue('DIETAMAMYTO_DIET_CATEGORY_IDS', $ids);
        Configuration::updateValue('DIETAMAMYTO_TREE_FORCE', (int)Tools::getValue('dmto_tree_force'));
        Configuration::updateValue('DIETAMAMYTO_TREE_CLEANUP_UNUSED', (int)Tools::getValue('dmto_tree_cleanup_unused'));
    }

    public function renderList()
    {
        if (Tools::isSubmit('dmto_super_cleanup')) {
            $doFeatures = (bool)Tools::getValue('dmto_cleanup_features'); $doCategories = (bool)Tools::getValue('dmto_cleanup_categories');
            if (!$doFeatures && !$doCategories) { $this->errors[] = $this->l('Zaznacz przynajmniej jedną opcję czyszczenia.'); } else {
                $reportHtml = '<div style="text-align:left">';
                if ($doFeatures) { $repA = $this->dmtoCleanupModuleFeatures(); $reportHtml .= '<h4>✔ CECHY modułu</h4><ul><li>Rozpoznane ID cech: '.(int)$repA['features_found'].'</li><li>Powiązania feature_product usunięte: '.(int)$repA['pf_removed'].' (produkty dotknięte: '.(int)$repA['pf_products'].')</li><li>Powiązania feature_product usunięte: '.(int)$repA['pfs_removed'].' (produkty dotknięte: '.(int)$repA['pfs_products'].')</li></ul>'; }
                if ($doCategories) { $repB = $this->dmtoCleanupDietCategories(); $rootsList = implode(',', array_map('intval', (array)$repB['roots'])); $reportHtml .= '<h4>✔ KATEGORIE z Kroku 3</h4><ul><li>Rooty: ['.$rootsList.']</li><li>Potomne kategorie do usunięcia: '.(int)$repB['categories_total'].'</li><li>Powiązania category_product do usunięcia: '.(int)$repB['links_to_remove'].' (produkty dotknięte: '.(int)$repB['products_linked'].')</li><li>Usunięte powiązania category_product: '.(int)$repB['links_removed'].'</li><li>Usunięte kategorie: '.(int)$repB['categories_deleted'].'</li></ul>'; }
                $reportHtml .= '</div>'; $this->confirmations[] = $reportHtml;
            }
        }
        if (Tools::isSubmit('submitGlobalSettings')) { $allowed = Tools::getValue('dmto_allowed_categories'); if (!is_array($allowed)) { $allowed = (array)$allowed; } $allowed = array_values(array_filter(array_map('intval', $allowed))); Configuration::updateValue('DIETAMAMYTO_ALLOWED_CATEGORIES', implode(',', $allowed)); }
        $statsSvc = new DietStatsService(); $stats = $statsSvc->getStats(); $link = $this->context->link; if (!empty($stats['undieted_list'])) { foreach ($stats['undieted_list'] as &$row) { $row['edit_url'] = $link->getAdminLink('AdminProducts', true, [], ['id_product' => (int)$row['id_product'], 'updateproduct' => 1]); } unset($row); }
        $config = DietConfigService::getInstance();
        $this->context->smarty->assign([
            'token' => Tools::getAdminTokenLite('AdminDietamamyto'), 'stats' => $stats, 'module_uri' => $this->module->getPathUri(), 'module_path' => _PS_MODULE_DIR_.'dietamamyto/', 'form_action_link' => $this->context->link->getAdminLink('AdminDietamamyto'), 'ajax_link' => $this->context->link->getAdminLink('AdminDietamamyto'), 'product_type_summary' => $this->getProductTypeFeatureSummary(),
            'dmto_process_only_active' => $config->processOnlyActive, 'dmto_sku_ignore_enabled' => $config->skuIgnoreEnabled, 'dmto_sku_ignore_prefix' => $config->skuIgnorePrefix, 'dmto_test_mode_enabled' => $config->testModeEnabled, 'dmto_test_mode_limit' => $config->testModeLimit,
            'dmto_type_depth' => $config->typeDepth, 'dmto_depth_mode' => $config->depthMode, 'dmto_auto_reindex' => $config->autoReindex, 'dmto_force_rebuild' => $config->forceRebuild, 'dmto_create_tree' => $config->createTreeAfterStep2, 'dmto_cleanup_unused' => $config->cleanupUnused,
            'dmto_tree_use_type_cap' => $config->treeUseTypeCap, 'tree_depth' => $config->treeDepth, 'dmto_tree_depth_mode' => $config->treeDepthMode, 'diet_root_ids' => $config->dietRootIds, 'dmto_tree_force' => $config->treeForce, 'dmto_tree_cleanup_unused' => $config->treeCleanupUnused,
            'dmto_category_tree_html' => $this->renderCategoryTreeHtml(),
        ]);
        return $this->context->smarty->fetch(_PS_MODULE_DIR_.'dietamamyto/views/templates/admin/configure.tpl');
    }

    private function cleanupUnusedProductTypeValues(): array
    {
        $featureName = 'Rodzaj produktu'; $idLang = (int)$this->context->language->id; $idFeature = (int)Db::getInstance()->getValue("SELECT id_feature FROM `"._DB_PREFIX_."feature_lang` WHERE name = '".pSQL($featureName)."' AND id_lang = ".$idLang); if (!$idFeature) { return ['count' => 0, 'names' => []]; } $query = new DbQuery(); $query->select('fv.id_feature_value')->from('feature_value', 'fv')->leftJoin('feature_product', 'fp', 'fv.id_feature_value = fp.id_feature_value')->where('fv.id_feature = ' . (int)$idFeature)->where('fp.id_product IS NULL')->groupBy('fv.id_feature_value'); $idsToDelete = array_column(Db::getInstance()->executeS($query), 'id_feature_value'); if (empty($idsToDelete)) { return ['count' => 0, 'names' => []]; } $deletedNamesQuery = new DbQuery(); $deletedNamesQuery->select('value')->from('feature_value_lang')->where('id_feature_value IN ('.implode(',', array_map('intval', $idsToDelete)).')')->where('id_lang = '.$idLang); $deletedNames = array_column(Db::getInstance()->executeS($deletedNamesQuery), 'value'); $deletedCount = 0; foreach ($idsToDelete as $id) { $featureValue = new FeatureValue((int)$id); if (Validate::isLoadedObject($featureValue) && $featureValue->delete()) { $deletedCount++; } } return ['count' => $deletedCount, 'names' => $deletedNames];
    }

    protected function getProductTypeFeatureSummary(): array
    {
        $idLang = (int)$this->context->language->id; $idFeature = (int)Db::getInstance()->getValue("SELECT id_feature FROM `"._DB_PREFIX_."feature_lang` WHERE name = 'Rodzaj produktu' AND id_lang = ".$idLang); if (!$idFeature) { return []; } $query = new DbQuery(); $query->select('fvl.value, COUNT(fp.id_product) as product_count')->from('feature_value_lang', 'fvl')->join('JOIN `'._DB_PREFIX_.'feature_value` fv ON fvl.id_feature_value = fv.id_feature_value')->join('LEFT JOIN `'._DB_PREFIX_.'feature_product` fp ON fv.id_feature_value = fp.id_feature_value')->where('fv.id_feature = ' . (int)$idFeature)->where('fvl.id_lang = ' . (int)$idLang)->groupBy('fvl.id_feature_value')->orderBy('fvl.value ASC'); return Db::getInstance()->executeS($query) ?: [];
    }

    protected function getDietRoots(): array
    {
        $config = DietConfigService::getInstance(); $ids = array_filter(array_map('intval', preg_split('/[\s,;]+/', (string)$config->dietRootIds, -1, PREG_SPLIT_NO_EMPTY))); if (!empty($ids)) { if (count($ids) == 1 && $ids[0] == $this->dmtoGetForcedDietParentId()) {} else { return array_values(array_unique($ids)); } } $idShop = (int)$this->context->shop->id; $idParent = $this->dmtoGetForcedDietParentId(); $parentCategory = new Category($idParent); if (!Validate::isLoadedObject($parentCategory)) { return []; } $patternsExact = ['Bez glutenu', 'Wegetariańskie', 'Wegańskie', 'Bez laktozy', 'Bez cukru', 'Niski Indeks Glikemiczny']; $patternsLike = ['Bio%', 'BIO%', 'Bio / Organic%', 'Organic%', 'Keto%', 'Keto / Low-Carb%', 'Keto & Low-Carb%', 'Low-Carb%']; $whereParts = []; if (!empty($patternsExact)) { $quoted = array_map('pSQL', $patternsExact); $whereParts[] = "cl.name IN ('" . implode("','", $quoted) . "')"; } if (!empty($patternsLike)) { $likes = array_map(function($p){ return "cl.name LIKE '" . pSQL($p) . "'"; }, $patternsLike); $whereParts[] = '(' . implode(' OR ', $likes) . ')'; } if (empty($whereParts)) { return []; } $sql = 'SELECT DISTINCT c.id_category FROM `' . _DB_PREFIX_ . 'category` c INNER JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON cl.id_category = c.id_category INNER JOIN `' . _DB_PREFIX_ . 'category_shop` cs ON cs.id_category = c.id_category AND cs.id_shop = ' . $idShop . ' WHERE (' . implode(' OR ', $whereParts) . ') AND c.nleft > '.(int)$parentCategory->nleft.' AND c.nright < '.(int)$parentCategory->nright.' AND c.level_depth = '.(int)($parentCategory->level_depth + 1); $rows = Db::getInstance()->executeS($sql) ?: []; $out = []; foreach ($rows as $r) { $out[] = (int)$r['id_category']; } return array_values(array_unique(array_filter($out)));
    }

    protected function runDietTreeSync(bool $force)
    {
        $config = DietConfigService::getInstance(); $dietRoots = $this->getDietRoots(); $svc = new DietStep3Service($this->context); $totalStats = ['processed'=>0, 'created_categories'=>0, 'linked_products'=>0, 'failed_products' => 0]; $totalProductIdsByRoot = []; $allUniqueIds = []; $idShop = (int)$this->context->shop->id; $idLang = (int)$this->context->language->id; foreach($dietRoots as $rootId) { $rootCat = new Category((int)$rootId, $idLang, $idShop); if (!Validate::isLoadedObject($rootCat)) continue; $rootName = $rootCat->name; $idFeatureDiet = (int)$this->dmtoFindFeatureIdByDietLabel($rootName); if ($idFeatureDiet <= 0) { continue; } $q = new DbQuery(); $q->select('DISTINCT p.id_product')->from('product', 'p')->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . $idShop)->innerJoin('feature_product', 'fp', 'fp.id_product = p.id_product')->innerJoin('feature_value_lang', 'fvl', 'fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = ' . $idLang)->where('fp.id_feature = ' . (int)$idFeatureDiet)->where('LOWER(fvl.value) IN ("tak","yes","true","1")'); if ($config->processOnlyActive) { $q->where('ps.active = 1'); } $productIds = array_column(Db::getInstance()->executeS($q) ?: [], 'id_product'); if(!empty($productIds)) { $totalProductIdsByRoot[$rootId] = $productIds; $allUniqueIds = array_merge($allUniqueIds, $productIds); } } $allUniqueIds = array_unique($allUniqueIds); if ($config->testModeEnabled && $config->testModeLimit > 0) { $allUniqueIds = array_slice($allUniqueIds, 0, $config->testModeLimit); } foreach($totalProductIdsByRoot as $rootId => $productIds) { $productIdsToProcessForThisRoot = array_intersect($productIds, $allUniqueIds); if(!empty($productIdsToProcessForThisRoot)) { $stats = $svc->sync($rootId, $productIdsToProcessForThisRoot, $force); foreach($totalStats as $key => &$val) { if (isset($stats[$key])) { $val += (is_array($stats[$key])) ? count($stats[$key]) : (int)$stats[$key]; } } unset($val); } } $totalStats['processed'] = count($allUniqueIds); $this->confirmations[] = sprintf($this->l('Drzewo diet (Synchronicznie): Przetworzono %d unikalnych produktów, utworzono kategorii %d, powiązano produktów %d (Błędy: %d).'), (int)($totalStats['processed']), (int)($totalStats['created_categories']), (int)($totalStats['linked_products']), (int)($totalStats['failed_products']));
    }

    protected function triggerFacetReindex(): void
    {
        try { $fs = Module::getInstanceByName('ps_facetedsearch'); if (!$fs || !Validate::isLoadedObject($fs)) { return; } if (class_exists('\PrestaShop\Module\FacetedSearch\Indexation\Indexer')) { $idx = new \PrestaShop\Module\FacetedSearch\Indexation\Indexer(Context::getContext()); if (method_exists($idx, 'indexAll')) { $idx->indexAll(); return; } } if (class_exists('\PrestaShop\Module\FacetedSearch\Indexer')) { $idx = new \PrestaShop\Module\FacetedSearch\Indexer(Context::getContext()); if (method_exists($idx, 'indexAll')) { $idx->indexAll(); return; } } if (method_exists($fs, 'rebuildIndex')) { $fs->rebuildIndex(); } } catch (Exception $e) {}
    }

    protected function cleanupUnusedDietCategories(): int
    {
        $dietRootIds = $this->getDietRoots(); if (empty($dietRootIds)) { return 0; } $allDescendants = []; foreach ($dietRootIds as $rootId) { $rootCategory = new Category($rootId); if (!Validate::isLoadedObject($rootCategory)) { continue; } $descendants = $rootCategory->getDescendants($this->context->language->id); if ($descendants) { foreach ($descendants as $k => $desc) { if ($desc['id_category'] == $rootId) { unset($descendants[$k]); } } $allDescendants = array_merge($allDescendants, $descendants); } } if (empty($allDescendants)) { return 0; } usort($allDescendants, function ($a, $b) { return $b['level_depth'] <=> $a['level_depth']; }); $deletedCount = 0; foreach ($allDescendants as $catData) { $category = new Category($catData['id_category'], $this->context->language->id); if (!Validate::isLoadedObject($category)) continue; $children = Category::getChildren($category->id, $this->context->language->id, false); $numProducts = $category->getProducts($this->context->language->id, 1, 1, null, null, true); if (empty($children) && $numProducts == 0) { try { if ($category->delete()) { $deletedCount++; } } catch (Exception $e) {} } } return $deletedCount;
    }

    protected function renderCategoryTreeHtml()
    {
        if (!class_exists('HelperTreeCategories')) { require_once _PS_CORE_DIR_.'/classes/helper/HelperTreeCategories.php'; } $selectedCsv = (string)Configuration::get('DIETAMAMYTO_ALLOWED_CATEGORIES'); $selected = array_filter(array_map('intval', preg_split('/[\s,;]+/', $selectedCsv, -1, PREG_SPLIT_NO_EMPTY))); $tree = new HelperTreeCategories('dmto_allowed_categories_tree'); $tree->setUseCheckBox(true)->setRootCategory((int)Configuration::get('PS_ROOT_CATEGORY'))->setSelectedCategories($selected)->setInputName('dmto_allowed_categories'); return $tree->render();
    }

    protected function dmtoCleanupModuleFeatures(): array
    {
        $db = Db::getInstance(); $idShop = (int)$this->context->shop->id; $q1 = new DbQuery(); $q1->select('DISTINCT f.id_feature')->from('feature', 'f')->innerJoin('feature_lang', 'fl', 'fl.id_feature = f.id_feature')->where("fl.name = 'Rodzaj produktu' OR fl.name LIKE 'Dieta:%' OR fl.name LIKE 'Certyfikat:%' OR fl.name LIKE '%cukr%' OR fl.name LIKE '%laktoz%' OR fl.name LIKE '%gluten%' OR fl.name LIKE 'Bez:%'"); $idsName = array_map('intval', array_column($db->executeS($q1) ?: [], 'id_feature')); $q2 = new DbQuery(); $q2->select('DISTINCT fv.id_feature')->from('feature_value', 'fv')->innerJoin('feature_value_lang', 'fvl', 'fvl.id_feature_value = fv.id_feature_value')->where("(fvl.value LIKE '%bez%' AND (fvl.value LIKE '%cukr%' OR fvl.value LIKE '%laktoz%' OR fvl.value LIKE '%gluten%'))"); $idsByValue = array_map('intval', array_column($db->executeS($q2) ?: [], 'id_feature')); $ids = array_values(array_unique(array_merge($idsName, $idsByValue))); if (empty($ids)) { return ['features_found'=>0, 'fp_to_remove'=>0,'fp_removed'=>0,'fp_left'=>0,'fp_products'=>0, 'pf_removed'=>0,'pf_products'=>0,'pfs_removed'=>0,'pfs_products'=>0]; } $in = implode(',', array_map('intval', $ids)); $sqlProducts = "SELECT COUNT(DISTINCT fp.id_product) FROM `"._DB_PREFIX_."feature_product` fp INNER JOIN `"._DB_PREFIX_."product_shop` ps ON ps.id_product = fp.id_product AND ps.id_shop = ".$idShop." WHERE fp.id_feature IN ($in)"; $fpProducts = (int)$db->getValue($sqlProducts); $sqlToRemove = "SELECT COUNT(*) FROM `"._DB_PREFIX_."feature_product` fp INNER JOIN `"._DB_PREFIX_."product_shop` ps ON ps.id_product = fp.id_product AND ps.id_shop = ".$idShop." WHERE fp.id_feature IN ($in)"; $fpToRemove = (int)$db->getValue($sqlToRemove); $sqlDel = "DELETE fp FROM `"._DB_PREFIX_."feature_product` fp INNER JOIN `"._DB_PREFIX_."product_shop` ps ON ps.id_product = fp.id_product AND ps.id_shop = ".$idShop." WHERE fp.id_feature IN ($in)"; $db->execute($sqlDel); $sqlLeft = "SELECT COUNT(*) FROM `"._DB_PREFIX_."feature_product` fp INNER JOIN `"._DB_PREFIX_."product_shop` ps ON ps.id_product = fp.id_product AND ps.id_shop = ".$idShop." WHERE fp.id_feature IN ($in)"; $fpLeft = (int)$db->getValue($sqlLeft); $fpRemoved = max(0, $fpToRemove - $fpLeft); return ['features_found' => count($ids), 'fp_to_remove' => $fpToRemove, 'fp_removed' => $fpRemoved, 'fp_left' => $fpLeft, 'fp_products' => $fpProducts, 'pf_removed' => $fpRemoved, 'pf_products' => $fpProducts, 'pfs_removed' => 0, 'pfs_products' => 0];
    }

    protected function dmtoCleanupDietCategories(): array
    {
        $db = Db::getInstance(); $idShop = (int)$this->context->shop->id; $config = class_exists('DietConfigService') ? DietConfigService::getInstance() : null; $roots = []; if ($config && !empty($config->dietRootIds)) { $roots = array_values(array_filter(array_map('intval', preg_split('/[\s,;]+/', (string)$config->dietRootIds, -1, PREG_SPLIT_NO_EMPTY)))); } else { $roots = $this->getDietRoots(); } $ids = []; if (!empty($roots)) { $inRoots = implode(',', $roots); $q = new DbQuery(); $q->select('DISTINCT c2.id_category')->from('category', 'c1')->innerJoin('category', 'c2', 'c2.nleft > c1.nleft AND c2.nright < c1.nright')->innerJoin('category_shop', 'cs', 'cs.id_category = c2.id_category AND cs.id_shop = '.$idShop)->where('c1.id_category IN ('.$inRoots.')'); $rows = $db->executeS($q) ?: []; foreach ($rows as $r) { $ids[] = (int)$r['id_category']; } } $ids = array_values(array_unique(array_filter($ids))); if (empty($ids)) { return ['roots'=>$roots, 'categories_total'=>0, 'categories_deleted'=>0, 'links_to_remove'=>0, 'links_removed'=>0, 'products_linked'=>0]; } $in = implode(',', $ids); $linksToRemove = (int)$db->getValue("SELECT COUNT(*) FROM `"._DB_PREFIX_."category_product` WHERE id_category IN ($in)"); $productsLinked = (int)$db->getValue("SELECT COUNT(DISTINCT id_product) FROM `"._DB_PREFIX_."category_product` WHERE id_category IN ($in)"); $db->delete('category_product', 'id_category IN ('.$in.')'); $linksLeft = (int)$db->getValue("SELECT COUNT(*) FROM `"._DB_PREFIX_."category_product` WHERE id_category IN ($in)"); $linksRemoved = max(0, $linksToRemove - $linksLeft); $deleted = 0; $categoriesToDelete = Db::getInstance()->executeS('SELECT id_category, level_depth FROM `'._DB_PREFIX_.'category` WHERE id_category IN ('.$in.') ORDER BY level_depth DESC') ?: []; foreach ($categoriesToDelete as $c) { $idc = (int)$c['id_category']; try { $stillLinked = (int)$db->getValue("SELECT COUNT(*) FROM `"._DB_PREFIX_."category_product` WHERE id_category=".(int)$idc); $hasChildren = (int)$db->getValue("SELECT COUNT(*) FROM `"._DB_PREFIX_.'category` WHERE id_parent='.(int)$idc); if ($stillLinked === 0 && $hasChildren === 0) { $cat = new Category((int)$idc, null, $idShop); if (Validate::isLoadedObject($cat) && $cat->delete()) { $deleted++; } } } catch (Exception $e) {} } return ['roots' => $roots, 'categories_total' => count($ids), 'categories_deleted' => $deleted, 'links_to_remove' => $linksToRemove, 'links_removed' => $linksRemoved, 'products_linked' => $productsLinked];
    }

    public function ajaxProcessGetDietCategorySummary()
    {
        $raw = Tools::getValue('category_ids'); $ids = []; if ($raw) { $arr = json_decode($raw, true); if (is_array($arr)) { $ids = array_values(array_filter(array_map('intval', $arr))); } } if (empty($ids)) { return $this->ajaxJson(['ok'=>true,'summary'=>[]]); } $idShop = (int)$this->context->shop->id; $idLang = (int)$this->context->language->id; $in = implode(',', $ids); $rows = Db::getInstance()->executeS("SELECT c.id_category, cl.name, COUNT(DISTINCT cp.id_product) AS products FROM `"._DB_PREFIX_."category` c INNER JOIN `"._DB_PREFIX_."category_lang` cl ON cl.id_category=c.id_category AND cl.id_lang=".(int)$idLang." INNER JOIN `"._DB_PREFIX_."category_shop` cs ON cs.id_category=c.id_category AND cs.id_shop=".$idShop." LEFT JOIN `"._DB_PREFIX_."category_product` cp ON cp.id_category=c.id_category WHERE c.id_category IN (".$in.") GROUP BY c.id_category, cl.name") ?: []; $summary = []; foreach ($rows as $r) { $summary[] = ['id' => (int)$r['id_category'], 'name' => $r['name'], 'products' => (int)$r['products']]; } return $this->ajaxJson(['ok'=>true,'summary'=>$summary]);
    }

    protected function dmtoFindCategoryIdByName(array $names): int
    {
        $idShop = (int)$this->context->shop->id; $names = array_filter(array_map('trim', $names)); foreach ($names as $name) { $q = new DbQuery(); $q->select('c.id_category')->from('category_lang', 'cl')->innerJoin('category', 'c', 'c.id_category = cl.id_category')->innerJoin('category_shop', 'cs', 'cs.id_category = c.id_category')->where('cs.id_shop='.(int)$idShop)->where('cl.name="'.pSQL($name).'"')->limit(1); $id = (int)Db::getInstance()->getValue($q); if ($id > 0) { return $id; } } return 0;
    }
}