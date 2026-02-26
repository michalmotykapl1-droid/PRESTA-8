<?php
/**
 * /modules/dietamamyto/services/DietConfigService.php
 *
 * Poprawka: Wymusza treeDepth >= 3, aby obejść problem z cachowaniem wartości 1.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class DietConfigService
{
    private static $instance = null;

    public $processOnlyActive;
    public $testModeEnabled;
    public $testModeLimit;
    public $skuIgnoreEnabled;
    public $skuIgnorePrefix;
    public $typeDepth;
    public $depthMode;
    public $autoReindex;
    public $forceRebuild;
    public $createTreeAfterStep2;
    public $cleanupUnused;
    public $treeUseTypeCap;
    public $treeDepth;
    public $treeDepthMode;
    public $dietRootIds;
    public $treeForce;
    public $treeCleanupUnused;

    private function __construct()
    {
        // Globalne
        $this->processOnlyActive = (int)Configuration::get('DIETAMAMYTO_PROCESS_ONLY_ACTIVE', 1);
        $this->testModeEnabled = (int)Configuration::get('DIETAMAMYTO_TEST_MODE_ENABLED', 0);
        $this->testModeLimit = max(0, (int)Configuration::get('DIETAMAMYTO_TEST_MODE_LIMIT', 100));
        $this->skuIgnoreEnabled = (int)Configuration::get('DIETAMAMYTO_SKU_IGNORE_ENABLED', 1);
        $this->skuIgnorePrefix = (string)Configuration::get('DIETAMAMYTO_SKU_IGNORE_PREFIX', 'bp_');

        // Krok 2
        $this->typeDepth = max(1, min(6, (int)Configuration::get('DIETAMAMYTO_TYPE_DEPTH', 2)));
        $this->depthMode = (string)Configuration::get('DIETAMAMYTO_DEPTH_MODE', 'leaf');
        $this->autoReindex = (int)Configuration::get('DIETAMAMYTO_AUTO_REINDEX', 1);
        $this->forceRebuild = (int)Configuration::get('DIETAMAMYTO_FORCE_REBUILD', 0);
        $this->createTreeAfterStep2 = (int)Configuration::get('DIETAMAMYTO_CREATE_TREE', 0);
        $this->cleanupUnused = (int)Configuration::get('DIETAMAMYTO_CLEANUP_UNUSED', 0);

        // Krok 3
        $this->treeUseTypeCap = (int)Configuration::get('DIETAMAMYTO_TREE_USE_TYPE_CAP', 1);
        
        $rawDepth = (int)Configuration::get('DIETAMAMYTO_TREE_DEPTH', 1);
        $effectiveDepth = $rawDepth;
        
        // ⭐ Wymuszenie odczytu z POST, jeśli jest to żądanie z formularza, 
        // W PRZECIWNYM RAZIE: jeśli wartość z DB jest 1, ustaw na 3.
        if (Tools::isSubmit('saveTreeSettings') || Tools::getValue('dmto_tree_depth')) {
             $postDepth = (int)Tools::getValue('dmto_tree_depth');
             if ($postDepth > 0) {
                 $effectiveDepth = $postDepth; 
             }
        } else if ($rawDepth <= 1) {
             $effectiveDepth = 3; // Obejście agresywnego cache'u
        }

        $this->treeDepth = max(1, min(6, (int)$effectiveDepth)); 
        
        $this->treeDepthMode = (string)Configuration::get('DIETAMAMYTO_TREE_DEPTH_MODE', 'root');

        $this->dietRootIds = (string)Configuration::get('DIETAMAMYTO_DIET_CATEGORY_IDS', '');
        $this->treeForce = (int)Configuration::get('DIETAMAMYTO_TREE_FORCE', 0);
        $this->treeCleanupUnused = (int)Configuration::get('DIETAMAMYTO_TREE_CLEANUP_UNUSED', 0);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function resetInstance()
    {
        self::$instance = null;
    }
}