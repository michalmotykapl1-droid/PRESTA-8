<?php
if (!defined('_PS_VERSION_')) { exit; }

class DietStep2Service
{
    
    public static function run(int $depth, bool $force): array
    {
        $allowed = self::dmtoGetExpandedAllowedCategoryIds();
        if (!empty($allowed)) {
            $idShop = (int)Context::getContext()->shop->id;
            $idsQuery = new DbQuery();
            $idsQuery->select('DISTINCT cp.id_product');
            $idsQuery->from('category_product','cp');
            $idsQuery->innerJoin('product','p','p.id_product=cp.id_product');
            $idsQuery->innerJoin('product_shop','ps','ps.id_product=p.id_product AND ps.id_shop='.$idShop);

            $onlyActive = (int)Configuration::get('DIETAMAMYTO_PROCESS_ONLY_ACTIVE', 1);
            if ($onlyActive) { $idsQuery->where('ps.active=1'); }

            $ignoreSkuEnabled = (int)Configuration::get('DIETAMAMYTO_SKU_IGNORE_ENABLED', 1);
            $ignoreSkuPrefix  = (string)Configuration::get('DIETAMAMYTO_SKU_IGNORE_PREFIX', '');
            if ($ignoreSkuEnabled && $ignoreSkuPrefix !== '') {
                $idsQuery->where("p.reference NOT LIKE '".pSQL($ignoreSkuPrefix)."%'");
            }

            $idsQuery->where('cp.id_category IN ('.implode(',', array_map('intval', $allowed)).')');

            $testModeEnabled = (int)Configuration::get('DIETAMAMYTO_TEST_MODE_ENABLED', 0);
            $testModeLimit   = (int)Configuration::get('DIETAMAMYTO_TEST_MODE_LIMIT', 100);
            if ($testModeEnabled && $testModeLimit > 0) { $idsQuery->limit($testModeLimit); }

            $rows = Db::getInstance()->executeS($idsQuery) ?: [];
            $ids  = [];
            foreach ($rows as $r) { $ids[] = (int)$r['id_product']; }

            if (!empty($ids)) {
                $pcPath = _PS_MODULE_DIR_.'dietamamyto/services/ProductCategorizer.php';
                if (!file_exists($pcPath)) { throw new Exception('Brak pliku ProductCategorizer.php'); }
                require_once $pcPath;
                if (!class_exists('ProductCategorizer')) { throw new Exception('Brak klasy ProductCategorizer'); }
                $stats = ProductCategorizer::assignProductTypesForIds($ids, $depth, $force);
                if (!is_array($stats)) { $stats = ['products_updated'=>0, 'feature_values_created'=>0]; }
                return $stats;
            }
        }

        // Fallback – bez ograniczeń kategorii
        $pcPath = _PS_MODULE_DIR_.'dietamamyto/services/ProductCategorizer.php';
        if (!file_exists($pcPath)) { throw new Exception('Brak pliku ProductCategorizer.php'); }
        require_once $pcPath;
        if (!class_exists('ProductCategorizer')) { throw new Exception('Brak klasy ProductCategorizer'); }
        $stats = ProductCategorizer::assignProductTypes($depth, $force);
        if (!is_array($stats)) { $stats = ['products_updated'=>0, 'feature_values_created'=>0]; }
        return $stats;
    }

}
