<?php
/**
 * /modules/dietamamyto/services/ProductCategorizer.php
 */
if (!defined('_PS_VERSION_')) { exit; }

class ProductCategorizer
{
    public static function assignProductTypes(int $depth, bool $force = true): array
    {
        $ids = self::collectCandidateIds($force);
        return self::assignProductTypesForIds($ids, $depth, $force);
    }

    public static function assignProductTypesForIds(array $productIds, int $depth, bool $force = true): array
    {
        if (empty($productIds)) {
            return ['products_updated' => 0, 'feature_values_created' => 0, 'created_value_names' => [], 'failed_products' => []];
        }
        $context = Context::getContext();
        $idLang  = (int)$context->language->id;
        $idShop  = (int)$context->shop->id;
        
        $config = DietConfigService::getInstance();

        $labelMode  = (string)Configuration::get('DIETAMAMYTO_TYPE_LABEL_MODE', 'path');
        $labelDepth = (int)Configuration::get('DIETAMAMYTO_TYPE_LABEL_DEPTH', 2);
        if ($labelDepth <= 0) { $labelDepth = 2; }
        $mode = $config->depthMode;

        $featureName = 'Rodzaj produktu';
        $sqlFeature = sprintf("SELECT id_feature FROM `%sfeature_lang` WHERE name='%s' AND id_lang=%d", _DB_PREFIX_, pSQL($featureName), (int)$idLang);
        $idFeature = (int)Db::getInstance()->getValue($sqlFeature);
        
        if ($idFeature <= 0) {
            $feature = new Feature();
            foreach (Language::getLanguages(false) as $lang) { $feature->name[$lang['id_lang']] = $featureName; }
            $feature->add();
            $idFeature = (int)$feature->id;
            if ($idFeature > 0 && Shop::isFeatureActive()) {
                Db::getInstance()->insert('feature_shop', ['id_feature' => $idFeature, 'id_shop' => $idShop]);
            }
        }

        $updated = 0;
        $createdVals = 0;
        $createdValueNames = [];
        $failedProducts = [];

        $inIds = implode(',', array_map('intval', $productIds));
        $activeCondition = $config->processOnlyActive ? 'ps.active=1 AND' : '';
        $sqlRows = sprintf("SELECT p.id_product, p.id_category_default FROM `%sproduct` p INNER JOIN `%sproduct_shop` ps ON (ps.id_product=p.id_product AND ps.id_shop=%d) WHERE %s p.id_product IN (%s)", _DB_PREFIX_, _DB_PREFIX_, (int)$idShop, $activeCondition, $inIds);
        $rows = Db::getInstance()->executeS($sqlRows);
        
        if (!$rows) {
            return ['products_updated' => 0, 'feature_values_created' => 0, 'created_value_names' => [], 'failed_products' => []];
        }

        foreach ($rows as $r) {
            $idProduct = (int)$r['id_product'];
            try {
                $idDefault = (int)$r['id_category_default'];
                if ($idDefault <= 0) {
                    $failedProducts[$idProduct] = 'Brak kategorii domyślnej';
                    continue;
                }
                if (!$force) {
                    if (Db::getInstance()->getValue("SELECT 1 FROM `"._DB_PREFIX_."feature_product` WHERE id_product=$idProduct AND id_feature=$idFeature")) { continue; }
                }
                $path = self::getCleanPath($idDefault, $idLang, $idShop);
                if (!$path) {
                    $failedProducts[$idProduct] = 'Nie udało się ustalić ścieżki kategorii';
                    continue;
                }
                $idx = ($mode === 'root') ? min(max(0, $depth - 1), count($path) - 1) : max(0, count($path) - $depth);
                $label = self::buildTypeLabelFromPath($path, $idx, $labelMode, $labelDepth);
                if ($label === '') {
                    $failedProducts[$idProduct] = 'Nie udało się zbudować etykiety z ścieżki kategorii';
                    continue;
                }
                $sqlFindVal = sprintf("SELECT fv.id_feature_value FROM `%sfeature_value` fv INNER JOIN `%sfeature_value_lang` fvl ON (fvl.id_feature_value=fv.id_feature_value AND fvl.id_lang=%d) WHERE fv.id_feature=%d AND fvl.value='%s'", _DB_PREFIX_, _DB_PREFIX_, (int)$idLang, (int)$idFeature, pSQL($label));
                $idVal = (int)Db::getInstance()->getValue($sqlFindVal);
                if ($idVal <= 0) {
                    $fv = new FeatureValue();
                    $fv->id_feature = $idFeature;
                    foreach (Language::getLanguages(false) as $lang) { $fv->value[$lang['id_lang']] = $label; }
                    $fv->add();
                    $idVal = (int)$fv->id;
                    if ($idVal > 0 && Shop::isFeatureActive()) {
                        Db::getInstance()->insert('feature_value_shop', ['id_feature_value' => $idVal, 'id_shop' => $idShop]);
                    }
                    $createdValueNames[] = $label;
                    $createdVals++;
                }
                Db::getInstance()->delete('feature_product', "id_product=$idProduct AND id_feature=$idFeature");
                if (Db::getInstance()->insert('feature_product', ['id_feature' => $idFeature, 'id_product' => $idProduct, 'id_feature_value' => $idVal])) {
                    $updated++;
                }
            } catch (Exception $e) {
                $failedProducts[$idProduct] = 'Wyjątek: ' . $e->getMessage();
                continue;
            }
        }
        return ['products_updated' => $updated, 'feature_values_created' => $createdVals, 'created_value_names' => $createdValueNames, 'failed_products' => $failedProducts];
    }

    protected static function collectCandidateIds(bool $force): array
    {
        $idShop = (int)Context::getContext()->shop->id;
        $idLang = (int)Context::getContext()->language->id;
        
        $config = DietConfigService::getInstance();

        $featureName = 'Rodzaj produktu';
        $sqlFeature = sprintf("SELECT id_feature FROM `%sfeature_lang` WHERE name='%s' AND id_lang=%d", _DB_PREFIX_, pSQL($featureName), (int)$idLang);
        $idFeature = (int)Db::getInstance()->getValue($sqlFeature);

        $where = $config->processOnlyActive ? ' WHERE ps.active=1 ' : ' WHERE 1 ';
        $join  = '';
        if (!$force && $idFeature > 0) {
            $join  .= sprintf(' LEFT JOIN `%sfeature_product` fp ON (fp.id_product=p.id_product AND fp.id_feature=%d) ', _DB_PREFIX_, (int)$idFeature);
            $where .= ' AND fp.id_feature IS NULL ';
        }

        $limit = '';
        if ($config->testModeEnabled && $config->testModeLimit > 0) {
            $limit = 'LIMIT ' . (int)$config->testModeLimit;
        }

        $sql = sprintf(
            "SELECT p.id_product
             FROM `%sproduct` p
             INNER JOIN `%sproduct_shop` ps ON (ps.id_product=p.id_product AND ps.id_shop=%d)
             %s %s %s",
             _DB_PREFIX_, _DB_PREFIX_, (int)$idShop, $join, $where, $limit
        );
        
        return array_column(Db::getInstance()->executeS($sql), 'id_product');
    }
    
    protected static function getCleanPath(int $idCategory, int $idLang, int $idShop): array
    {
        $cat = new Category($idCategory, $idLang, $idShop);
        if (!Validate::isLoadedObject($cat)) { return []; }
        $parents = $cat->getParentsCategories($idLang);
        if (empty($parents)) { return []; }
        $path = array_reverse($parents);
        $out  = [];
        foreach ($path as $p) {
            $name = trim((string)($p['name'] ?? ''));
            $lower = Tools::strtolower($name);
            if ($name === '' || $lower === 'home' || $lower === 'strona główna' || $lower === 'homepage') { continue; }
            $out[] = ['id' => (int)$p['id_category'], 'name' => $name];
        }
        return $out;
    }

    protected static function buildTypeLabelFromPath(array $path, int $idx, string $mode, int $labelDepth): string
    {
        if (!isset($path[$idx])) { return ''; }
        if ($mode === 'name') { return trim((string)($path[$idx]['name'] ?? '')); }
        $start = max(0, $idx - $labelDepth + 1);
        $slice = array_slice($path, $start, $idx - $start + 1);
        $parts = [];
        foreach ($slice as $node) {
            $n = trim((string)$node['name']);
            if ($n !== '') { $parts[] = $n; }
        }
        return implode(' › ', $parts);
    }
}