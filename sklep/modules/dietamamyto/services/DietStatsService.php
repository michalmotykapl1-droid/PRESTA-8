<?php
/**
 * /modules/dietamamyto/services/DietStatsService.php
 */
if (!defined('_PS_VERSION_')) { exit; }

// Wymagane do pobrania dynamicznej listy cech
require_once _PS_MODULE_DIR_.'dietamamyto/services/DietStep1Service.php';

class DietStatsService
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

    public function getStats(): array
    {
        $db = Db::getInstance();
        $idShop = $this->idShop;
        $idLang = $this->idLang;

        // Warunki SQL dla różnych zakresów produktów
        $joinActive = 'JOIN `'._DB_PREFIX_.'product_shop` ps ON (ps.id_product = p.id_product AND ps.id_shop = ' . $idShop . ')';
        $joinStock = 'JOIN `'._DB_PREFIX_.'stock_available` sa ON (sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = ' . $idShop . ')';
        
        $whereAll = '';
        $whereActiveInStock = 'WHERE ps.active = 1 AND sa.quantity > 0';

        // 1. Liczymy produkty "Wszystkie" i "Aktywne w magazynie"
        $totalAll = (int)$db->getValue('SELECT COUNT(p.id_product) FROM `'._DB_PREFIX_.'product` p ' . $joinActive);
        $totalActiveInStock = (int)$db->getValue('SELECT COUNT(p.id_product) FROM `'._DB_PREFIX_.'product` p ' . $joinActive . ' ' . $joinStock . ' ' . $whereActiveInStock);

        // 2. Dynamicznie pobieramy listę cech dietetycznych z modułu
        $dietFeaturesRaw = DietStep1Service::getFeatureRules();
        $featureNames = array_keys($dietFeaturesRaw);
        
        $featureMap = [];
        if (!empty($featureNames)) {
            $sql = 'SELECT `name`, `id_feature` FROM `'._DB_PREFIX_.'feature_lang` WHERE `id_lang` = ' . $idLang . ' AND `name` IN ("' . implode('","', array_map('pSQL', $featureNames)) . '")';
            $results = $db->executeS($sql);
            foreach ($results as $row) {
                // Usuwamy prefiksy dla ładniejszych etykiet
                $label = str_replace(['Dieta: ', 'Certyfikat: ', 'Bez: '], '', $row['name']);
                $featureMap[$row['id_feature']] = $label;
            }
        }

        $featureIds = array_keys($featureMap);
        $perDietAll = [];
        $perDietActiveInStock = [];

        if (!empty($featureIds)) {
            // 3. Liczymy cechy dla WSZYSTKICH produktów
            $sqlAll = '
                SELECT fp.id_feature, COUNT(DISTINCT p.id_product) as count
                FROM `'._DB_PREFIX_.'feature_product` fp
                JOIN `'._DB_PREFIX_.'product` p ON p.id_product = fp.id_product
                ' . $joinActive . '
                JOIN `'._DB_PREFIX_.'feature_value_lang` fvl ON fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = ' . $idLang . '
                WHERE fp.id_feature IN (' . implode(',', $featureIds) . ') AND fvl.value = "tak"
                GROUP BY fp.id_feature';
            $resultsAll = $db->executeS($sqlAll);

            // 4. Liczymy cechy dla AKTYWNYCH produktów w magazynie
            $sqlActive = '
                SELECT fp.id_feature, COUNT(DISTINCT p.id_product) as count
                FROM `'._DB_PREFIX_.'feature_product` fp
                JOIN `'._DB_PREFIX_.'product` p ON p.id_product = fp.id_product
                ' . $joinActive . '
                ' . $joinStock . '
                JOIN `'._DB_PREFIX_.'feature_value_lang` fvl ON fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = ' . $idLang . '
                WHERE fp.id_feature IN (' . implode(',', $featureIds) . ') AND fvl.value = "tak" ' . str_replace('WHERE', 'AND', $whereActiveInStock) . '
                GROUP BY fp.id_feature';
            $resultsActive = $db->executeS($sqlActive);
            
            // Mapowanie wyników do etykiet
            $countsAll = array_column($resultsAll, 'count', 'id_feature');
            $countsActive = array_column($resultsActive, 'count', 'id_feature');

            foreach ($featureMap as $id => $label) {
                $perDietAll[] = ['label' => $label, 'count' => $countsAll[$id] ?? 0];
                $perDietActiveInStock[] = ['label' => $label, 'count' => $countsActive[$id] ?? 0];
            }
        }
        
        // 5. Produkty "Bez diety" (tylko dla aktywnych, bo lista jest długa)
        $shopConditionPL = $this->columnExists('product_lang', 'id_shop') ? ' AND pl.id_shop = ' . (int)$idShop : '';
        $undietSubquery = 'AND NOT EXISTS (SELECT 1 FROM `'._DB_PREFIX_.'feature_product` fp JOIN `'._DB_PREFIX_.'feature_lang` fl ON fl.id_feature = fp.id_feature AND fl.id_lang = '.$idLang.' JOIN `'._DB_PREFIX_.'feature_value_lang` fvl ON fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = '.$idLang.' WHERE fp.id_product = p.id_product AND fl.name LIKE \'Dieta:%\' AND LOWER(fvl.value) = \'tak\')';

        $undietCountAll = (int)$db->getValue('SELECT COUNT(DISTINCT p.id_product) FROM `'._DB_PREFIX_.'product` p JOIN `'._DB_PREFIX_.'product_shop` ps ON ps.id_product = p.id_product AND ps.id_shop = '.$idShop.' WHERE 1 ' . $undietSubquery);
        $undietCountActive = (int)$db->getValue('SELECT COUNT(DISTINCT p.id_product) FROM `'._DB_PREFIX_.'product` p ' . $joinActive . ' ' . $joinStock . ' ' . $whereActiveInStock . ' ' . $undietSubquery);

        $undietList = $db->executeS('SELECT p.id_product, pl.name, p.reference FROM `'._DB_PREFIX_.'product` p JOIN `'._DB_PREFIX_.'product_shop` ps ON ps.id_product = p.id_product AND ps.id_shop = '.$idShop.' JOIN `'._DB_PREFIX_.'product_lang` pl ON pl.id_product = p.id_product AND pl.id_lang = '.$idLang . $shopConditionPL . ' WHERE ps.active = 1 ' . $undietSubquery . ' ORDER BY p.id_product DESC LIMIT 100');

        return [
            'all_products' => [
                'total' => $totalAll,
                'per_diet' => $perDietAll,
                'undieted_count' => $undietCountAll
            ],
            'active_in_stock' => [
                'total' => $totalActiveInStock,
                'per_diet' => $perDietActiveInStock,
                'undieted_count' => $undietCountActive
            ],
            'undieted_list'   => $undietList ?: [],
        ];
    }
    
    private function columnExists(string $tableName, string $columnName): bool
    {
        static $cache = [];
        $key = "$tableName.$columnName";
        if (isset($cache[$key])) return $cache[$key];
        return $cache[$key] = (bool)Db::getInstance()->getValue("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '".pSQL(_DB_NAME_)."' AND TABLE_NAME = '".pSQL(_DB_PREFIX_.$tableName)."' AND COLUMN_NAME = '".pSQL($columnName)."'");
    }
}