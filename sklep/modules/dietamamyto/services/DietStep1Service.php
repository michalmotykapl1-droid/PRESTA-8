<?php
/**
 * /modules/dietamamyto/services/DietStep1Service.php
 *
 * Poprawki:
 * 1. [Priorytet Nazwy] Dodano logikę, która sprawdza NAZWĘ produktu w pierwszej kolejności.
 * Jeśli nazwa zawiera słowo 'include' (np. "bezglutenowy"), cecha jest dodawana
 * i reguły 'exclude' (np. "owies" w alergenach) są ignorowane dla tej cechy.
 * 2. [Alergeny] Rozbudowano funkcję `isInAllergenDisclaimer`, aby rozpoznawała
 * również wzorzec "Wyprodukowano w zakładzie, w którym przetwarza się...".
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class DietStep1Service
{

    private static function dmtoGetExpandedAllowedCategoryIds(): array
    {
        $csv = (string)Configuration::get('DIETAMAMYTO_ALLOWED_CATEGORIES');
        $ids = array_filter(array_map('intval', preg_split('/[\s,;]+/', $csv, -1, PREG_SPLIT_NO_EMPTY)));
        $ids = array_values(array_unique($ids));
        if (empty($ids)) { return []; }
        $idShop = (int)Context::getContext()->shop->id;
        $in = implode(',', $ids);
        $q = new DbQuery();
        $q->select('DISTINCT c2.id_category');
        $q->from('category', 'c1');
        $q->innerJoin('category', 'c2', 'c2.nleft BETWEEN c1.nleft AND c1.nright');
        $q->innerJoin('category_shop', 'cs', 'cs.id_category = c2.id_category AND cs.id_shop = '.$idShop);
        $q->where('c1.id_category IN ('.$in.')');
        $rows = Db::getInstance()->executeS($q) ?: [];
        $out = [];
        foreach ($rows as $r) { $out[] = (int)$r['id_category']; }
        return array_values(array_unique($out));
    }


    private const STRICT_VEG_MODE = false;

    // Definicje reguł pozostają bez zmian
    private static $featureRules = [
        'Certyfikat: BIO' => [ 'include' => ['bio', 'eko', 'ekologiczny', 'ekologiczna', 'ekologiczne', 'organiczny', 'organiczna', 'organiczne', 'z upraw ekologicznych'], 'exclude' => ['biodegradowalny', 'biotyna', 'probiotyk'] ],
        'Dieta: Bez glutenu' => [ 
            'include' => ['bez glutenu', 'bezglutenowy', 'bezglutenowa', 'bezglutenowe', 'gluten-free', 'glutenu', 'bezgl'], 
            'exclude' => [
                'może zawierać gluten', 'śladowe ilości glutenu', 'pszenica', 'pszenny', 'pszenne', 'pszenna', 
                'żyto', 'żytni', 'jęczmień', 'jęczmienny', 'orkisz', 'orkiszowy', 'pszenżyto', 'kamut',
                'mąka pszenna', 'gluten pszenny', 'białko pszenne', 'słód jęczmienny', 'kasza manna', 'kuskus', 'seitan',
                'owies', 'owsiane', 'owsiana', 'owsiany', 'płatki owsiane', 'mąka owsiana'
            ] 
        ],
        'Bez: Laktozy' => [ 'include' => ['bez laktozy', 'lactose-free', 'nie zawiera laktozy', 'laktozy'], 'exclude' => ['może zawierać mleko', 'może zawierać laktozę', 'mleko', 'serwatka', 'maślanka', 'jogurt', 'śmietana', 'ser', 'masło'] ],
        'Bez: Cukru' => [ 'include' => [], 'exclude' => [] ],
        'Dieta: Wegańska' => [
            'include' => ['wegański', 'wegańska', 'wegańskie', 'vegan', '100% roślinny', 'produktów pochodzenia zwierzęcego'],
            'exclude' => [
                'mięso', 'drób', 'ryby', 'owoce morza', 'skorupiaki', 'żelatyna', 'kolagen', 'smalec', 'podpuszczka zwierzęca',
                'mięczaki', 'mleko', 'mleczny', 'serwatka', 'kazeina', 'laktoza', 'masło', 'jogurt', 'śmietana', 'maślanka', 'ser',
                'jaja', 'jajka', 'jajeczny', 'białko jaja', 'żółtko jaja', 'miód', 'wosk pszczeli', 'propolis', 'pyłek pszczeli', 'pierzga',
                'koszenila', 'karmin', 'szelak', 'lanolina', 'może zawierać mleko', 'może zawierać jaja', 'może zawierać laktozę', 'śladowe ilości mleka', 'śladowe ilości jaj'
            ]
        ],
        'Dieta: Wegetariańska' => [
            'include' => ['wegetariański', 'wegetariańska', 'wegetariańskie', 'vegetarian', 'dla wegetarian'],
            'exclude' => [
                'mięso', 'drób', 'ryby', 'owoce morza', 'skorupiaki', 'żelatyna', 'kolagen', 'smalec', 'podpuszczka', 'koszenila'
            ]
        ],
        'Dieta: Keto / Low-Carb' => [
            'include' => [ 'keto', 'ketogeniczny', 'ketogeniczna', 'ketogeniczne', 'low-carb', 'low carb', 'niskowęglowodanow', 'lchf' ],
            'exclude' => []
        ],
        'Dieta: Niski Indeks Glikemiczny' => [
            'include' => [ 'niski indeks glikemiczny', 'niski ig', 'low gi', 'low-glycemic', 'low glycemic', 'o niskim indeksie glikemicznym' ],
            'exclude' => [ 'syrop glukozowo-fruktoz', 'syrop glukozowy', 'syrop fruktozowy', 'maltodekstryn', 'karmel', 'biała mąka', 'mąka pszenna' ]
        ],
    ];
    
    public static function getFeatureRules(): array
    {
        return self::$featureRules;
    }

    private static $suggestionPatterns = [ '/sugestia podania.*?(?:\.|$)/ius', '/propozycja podania.*?(?:\.|$)/ius', '/wystarczy dodać.*?(?:\.|$)/ius', '/idealn(?:y|a|e) do.*?(?:\.|$)/ius', '/doskonał(?:y|a|e) do.*?(?:\.|$)/ius', '/podawaj z.*?(?:\.|$)/ius', '/świetnie smakuje z.*?(?:\.|$)/ius', '/dodać do dań z.*?(?:\.|$)/ius', ];
    private static $sugar_RedCardKeywords = [ 'cukier trzcinowy', 'cukier kokosowy', 'cukier', 'cukier brązowy', 'cukier demerara', 'sacharoza', 'glukoza', 'fruktoza', 'dekstroza', 'maltoza', 'maltodekstryna', 'syrop glukozowy', 'syrop fruktozowy', 'syrop glukozowo-fruktozowy', 'syrop kukurydziany', 'słód', 'melasa' ];
    private static $sugar_GoldenKeywords = [ 'bez dodatku cukru', 'bez dodatku cukrów', 'niesłodzone', 'niesłodzona', 'w tym cukry: 0 g', 'w tym cukry: 0,0 g', 'w tym cukry 0 g', 'w tym cukry 0,0 g' ];

    public function run(bool $force = false): array
    {
        $stats = ['processed' => 0, 'updated' => 0, 'features_added' => 0, 'features_removed' => 0];
        $id_lang = (int)Context::getContext()->language->id;
        
        $sql = new DbQuery();
        $sql->select('p.id_product');
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . (int)Context::getContext()->shop->id);

        $whereConditions = [];
        
        $onlyActive = (int)Configuration::get('DIETAMAMYTO_PROCESS_ONLY_ACTIVE', 1);
        if ($onlyActive) {
            $whereConditions[] = 'ps.active = 1';
        }
        
        $ignoreSkuEnabled = (int)Configuration::get('DIETAMAMYTO_SKU_IGNORE_ENABLED', 1);
        $ignoreSkuPrefix = (string)Configuration::get('DIETAMAMYTO_SKU_IGNORE_PREFIX', 'bp_');
        if ($ignoreSkuEnabled && !empty($ignoreSkuPrefix)) {
            $whereConditions[] = 'p.reference NOT LIKE \''.pSQL($ignoreSkuPrefix).'%\'';
        }
        
        $allowedCats = self::dmtoGetExpandedAllowedCategoryIds();
        if (!empty($allowedCats)) {
            $sql->innerJoin('category_product', 'cp', 'cp.id_product = p.id_product');
            $whereConditions[] = 'cp.id_category IN ('.implode(',', array_map('intval', $allowedCats)).')';
            $sql->groupBy('p.id_product'); 
        }

        if (!empty($whereConditions)) {
            $sql->where(implode(' AND ', $whereConditions));
        }

        $testModeEnabled = (int)Configuration::get('DIETAMAMYTO_TEST_MODE_ENABLED', 0);
        $testModeLimit = (int)Configuration::get('DIETAMAMYTO_TEST_MODE_LIMIT', 100);
        if ($testModeEnabled && $testModeLimit > 0) {
            $sql->limit($testModeLimit);
        }
        
        $productsToProcess = Db::getInstance()->executeS($sql);

        if (empty($productsToProcess)) { return $stats; }

        $featureNames = array_keys(self::$featureRules);
        $featureNameToIdMap = [];
        $sql_features = new DbQuery();
        $sql_features->select('fl.name, f.id_feature');
        $sql_features->from('feature', 'f');
        $sql_features->leftJoin('feature_lang', 'fl', 'f.id_feature = fl.id_feature AND fl.id_lang = ' . $id_lang);
        $sql_features->where('fl.name IN (\'' . implode("','", array_map('pSQL', $featureNames)) . '\')');
        $featuresFromDb = Db::getInstance()->executeS($sql_features);
        foreach ($featuresFromDb as $feature) { $featureNameToIdMap[$feature['name']] = (int)$feature['id_feature']; }
        
        $allManagedFeatureIds = array_values($featureNameToIdMap);
        if (empty($allManagedFeatureIds)) { return $stats; }

        foreach ($productsToProcess as $row) {
            $productId = (int)$row['id_product'];
            $stats['processed']++;
            $product = new Product($productId, true, $id_lang);
            if (!Validate::isLoadedObject($product)) { continue; }
            
            $initialFeaturesQuery = new DbQuery();
            $initialFeaturesQuery->select('id_feature')->from('feature_product')->where('id_product = ' . $productId . ' AND id_feature IN (' . implode(',', $allManagedFeatureIds) . ')');
            $initialFeatureIds = array_column(Db::getInstance()->executeS($initialFeaturesQuery), 'id_feature');
            sort($initialFeatureIds);
            
            $fullText = $product->name . ' ' . $product->description_short . ' ' . $product->description;
            $fullText = strip_tags($fullText);
            $fullText = preg_replace('/\s+/', ' ', $fullText);
            $cleanText = preg_replace(self::$suggestionPatterns, '', $fullText);
            
            $idealFeatureIds = [];
            
            $flexibleCheckFeatures = ['Dieta: Wegańska', 'Dieta: Wegetariańska', 'Dieta: Bez glutenu', 'Bez: Laktozy'];

            foreach (self::$featureRules as $featureName => $rules) {
                if (!isset($featureNameToIdMap[$featureName])) { continue; }
                $featureId = $featureNameToIdMap[$featureName];

                // ⭐ START POPRAWKI: PRIORYTET DLA NAZWY PRODUKTU ⭐
                $productNameLower = mb_strtolower($product->name, 'UTF-8');
                $forceInclude = false;

                // Priorytet 1: Sprawdź 'include' w NAZWIE produktu (na razie tylko dla glutenu, zgodnie z żądaniem)
                if ($featureName === 'Dieta: Bez glutenu' && !empty($rules['include'])) {
                    foreach ($rules['include'] as $includeWord) {
                        if (strpos($productNameLower, mb_strtolower($includeWord, 'UTF-8')) !== false) {
                            $idealFeatureIds[] = $featureId; // Dodaj cechę
                            $forceInclude = true; // Oznacz, że nazwa wymusiła dodanie
                            break; // Przerwij wewnętrzną pętlę (słów 'include')
                        }
                    }
                }

                if ($forceInclude) {
                    continue; // Pomiń resztę logiki (exclude/include) dla TEJ cechy i idź do następnej (np. Bez Laktozy)
                }
                // ⭐ KONIEC POPRAWKI PRIORYTETU ⭐
                
                // Logika dla "Bez Cukru" (bez zmian)
                if ($featureName === 'Bez: Cukru') {
                    $decision = null;
                    $lowerFullText = mb_strtolower($fullText, 'UTF-8');
                    foreach (self::$sugar_GoldenKeywords as $safeWord) {
                        if (strpos($lowerFullText, $safeWord) !== false) {
                            $decision = true; break;
                        }
                    }
                    if ($decision === null) {
                        foreach (self::$sugar_RedCardKeywords as $unsafeWord) {
                            if (strpos($lowerFullText, $unsafeWord) !== false) {
                                $decision = false; break;
                            }
                        }
                    }
                    if ($decision === null) { $decision = true; }
                    if ($decision === true) { $idealFeatureIds[] = $featureId; }
                    continue;
                }
                
                // Logika 'exclude' (teraz uruchamiana tylko jeśli nazwa nie wymusiła cechy)
                $isExcluded = false;
                if (!empty($rules['exclude'])) {
                    $lowerCleanText = mb_strtolower($cleanText, 'UTF-8');
                    foreach ($rules['exclude'] as $excludeWord) {
                        
                        $match = in_array($featureName, $flexibleCheckFeatures, true) 
                            ? (strpos($lowerCleanText, mb_strtolower($excludeWord, 'UTF-8')) !== false) 
                            : (bool)preg_match('/\b' . preg_quote($excludeWord, '/') . '\b/iu', $cleanText);
                            
                        if ($match) {
                            // Używamy $flexibleCheckFeatures (zamiast sztywnej listy), aby obsłużyć też gluten/laktozę
                            if (!self::STRICT_VEG_MODE && in_array($featureName, $flexibleCheckFeatures, true) && $this->isInAllergenDisclaimer($fullText, $excludeWord)) {
                                continue; // Ignoruj to wykluczenie, to tylko ostrzeżenie o alergenach
                            }
                            $isExcluded = true; break;
                        }
                    }
                }
                if ($isExcluded) { continue; } // Jeśli wykluczone, przejdź do następnej cechy
                
                // Logika 'include' (dla reszty tekstu)
                $isIncluded = false;
                if (!empty($rules['include'])) {
                    $lowerFullText = mb_strtolower($fullText, 'UTF-8');
                    foreach ($rules['include'] as $includeWord) {
                        if (strpos($lowerFullText, mb_strtolower($includeWord, 'UTF-8')) !== false) {
                            $isIncluded = true; break;
                        }
                    }
                }
                
                if ($isIncluded) {
                    $idealFeatureIds[] = $featureId;
                } else {
                    $inferableFeatures = ['Dieta: Wegetariańska', 'Bez: Laktozy'];
                    if (in_array($featureName, $inferableFeatures)) {
                        $idealFeatureIds[] = $featureId;
                    }
                }
            }
            
            // Logika dla Keto (bez zmian)
            $ketoFeatureId = $featureNameToIdMap['Dieta: Keto / Low-Carb'] ?? null;
            if ($ketoFeatureId && !in_array($ketoFeatureId, $idealFeatureIds)) {
                if ($this->isKetoByNutritionalInfo($fullText, 5.0)) {
                    $idealFeatureIds[] = $ketoFeatureId;
                }
            }
            
            // Logika dla Niski IG (bez zmian)
            $lowGiFeatureId = $featureNameToIdMap['Dieta: Niski Indeks Glikemiczny'] ?? null;
            if ($lowGiFeatureId && !in_array($lowGiFeatureId, $idealFeatureIds)) {
                $hasRedSugar = false;
                foreach (self::$sugar_RedCardKeywords as $red) {
                    if (preg_match('/\b' . preg_quote($red, '/') . '\b/iu', $fullText)) { $hasRedSugar = true; break; }
                }
                $hasLowGiWords = (bool)preg_match('/niski\s+indeks\s+glikemiczny|niski\s+ig|low\s*gi|low[-\s]?glycemic/iu', $fullText);
                $inferByNumbers = $this->isLowGIByNutritionalInfo($fullText);
                $decideLowGi = null;
                if ($hasLowGiWords) {
                    $decideLowGi = $hasRedSugar ? false : true;
                } elseif ($inferByNumbers !== null) {
                    $decideLowGi = $inferByNumbers;
                } else {
                    $hasWholegrain = (bool)preg_match('/pełnoziarn|c[aą]ł(e|a|y)\s*ziarno/iu', $fullText);
                    $hasFiberWord  = (bool)preg_match('/wysoki\s+błonnik|źr[oó]dł[a|o]\s+błonnika|rich\s+in\s+fiber/iu', $fullText);
                    if (($hasWholegrain || $hasFiberWord) && !$hasRedSugar) {
                        $decideLowGi = true;
                    }
                }
                if ($decideLowGi === true) { $idealFeatureIds[] = $lowGiFeatureId; }
            }
            
            // Logika Wegańska -> Wegetariańska/BezLaktozy (bez zmian)
            $veganFeatureId = $featureNameToIdMap['Dieta: Wegańska'] ?? null;
            if ($veganFeatureId && in_array($veganFeatureId, $idealFeatureIds)) {
                $vegetarianFeatureId = $featureNameToIdMap['Dieta: Wegetariańska'] ?? null;
                if ($vegetarianFeatureId) { $idealFeatureIds[] = $vegetarianFeatureId; }
                $lactoseFreeFeatureId = $featureNameToIdMap['Bez: Laktozy'] ?? null;
                if ($lactoseFreeFeatureId) { $idealFeatureIds[] = $lactoseFreeFeatureId; }
            }
            
            $idealFeatureIds = array_unique($idealFeatureIds);
            sort($idealFeatureIds);
            
            // Logika aktualizacji (bez zmian)
            if ($initialFeatureIds !== $idealFeatureIds) {
                $stats['updated']++;
                $removed = array_diff($initialFeatureIds, $idealFeatureIds);
                $added = array_diff($idealFeatureIds, $idealFeatureIds);
                $stats['features_removed'] += count($removed);
                $stats['features_added'] += count($added);
                
                Db::getInstance()->delete('feature_product', 'id_product = ' . $productId . ' AND id_feature IN (' . implode(',', $allManagedFeatureIds) . ')');
                
                if (!empty($idealFeatureIds)) {
                    foreach ($idealFeatureIds as $featureIdToAdd) {
                        $valueName = 'tak';
                        $sql_fv = new DbQuery();
                        $sql_fv->select('fv.id_feature_value')->from('feature_value', 'fv')->leftJoin('feature_value_lang', 'fvl', 'fv.id_feature_value = fvl.id_feature_value AND fvl.id_lang = ' . $id_lang)->where('fv.id_feature = ' . (int)$featureIdToAdd . ' AND fvl.value = \'' . pSQL($valueName) . '\'');
                        $featureValueId = (int)Db::getInstance()->getValue($sql_fv);
                        if (!$featureValueId) {
                            $featureValue = new FeatureValue();
                            $featureValue->id_feature = (int)$featureIdToAdd;
                            $featureValue->custom = 0;
                            foreach (Language::getLanguages(false) as $lang) {
                                $featureValue->value[$lang['id_lang']] = $valueName;
                            }
                            $featureValue->add();
                            $featureValueId = $featureValue->id;
                        }
                        Product::addFeatureProductImport($product->id, $featureIdToAdd, $featureValueId);
                    }
                }
                Cache::clean('Product::getFeaturesStatic_' . $productId);
            }
        }
        return $stats;
    }

    // Logika pomocnicza (bez zmian)
    private function isKetoByNutritionalInfo(string $text, float $netCarbThreshold): bool
    {
        $extractValue = function(string $pattern, string $subject) { if (preg_match($pattern, $subject, $matches)) { return (float)str_replace(',', '.', $matches[1]); } return null; };
        if (!preg_match('/(wartość odżywcza w 100\s*(?:g|ml)|wartości odżywcze w 100\s*(?:g|ml))/iu', $text, $tableHeader, PREG_OFFSET_CAPTURE)) { return false; }
        $nutritionText = mb_substr($text, $tableHeader[0][1]);
        $patterns = [ 'carbs' => '/Węglowodany.*?([\d,.]+)\s*g/iu', 'fiber' => '/Błonnik.*?([\d,.]+)\s*g/iu', 'polyols' => '/(?:alkohole wielowodorotlenowe \(poliole\)|poliole).*?([\d,.]+)\s*g/iu', ];
        $carbs = $extractValue($patterns['carbs'], $nutritionText);
        if ($carbs === null) { return false; }
        $fiber = $extractValue($patterns['fiber'], $nutritionText) ?? 0.0;
        $polyols = $extractValue($patterns['polyols'], $nutritionText) ?? 0.0;
        $netCarbs = $carbs - $fiber - $polyols;
        return $netCarbs <= $netCarbThreshold;
    }

    // Logika pomocnicza (bez zmian)
    private function isLowGIByNutritionalInfo(string $text): ?bool
    {
        $extractValue = function(string $pattern, string $subject) { if (preg_match($pattern, $subject, $m)) { return (float)str_replace(',', '.', $m[1]); } return null; };
        if (!preg_match('/(wartość[i]? odżywcza[e]? w 100\s*(?:g|ml)|wartości odżywcze w 100\s*(?:g|ml))/iu', $text, $hdr, PREG_OFFSET_CAPTURE)) { return null; }
        $nutrition = mb_substr($text, $hdr[0][1]);
        $carbs  = $extractValue('/Węglowodany.*?([\d,.]+)\s*g/iu', $nutrition);
        $sugars = $extractValue('/(?:w tym\s+)?Cukry.*?([\d,.]+)\s*g/iu', $nutrition);
        $fiber  = $extractValue('/Błonnik.*?([\d,.]+)\s*g/iu', $nutrition);
        $polyols = $extractValue('/(?:poliole|alkohole.*?wielowodorotlenowe).*?([\d,.]+)\s*g/iu', $nutrition) ?? 0.0;
        if ($carbs !== null && $carbs <= 0.5 && ($fiber === null || $fiber <= 0.5)) { return null; }
        if ($carbs === null && $sugars === null && $fiber === null) { return null; }
        $isDrink = (bool)preg_match('/\b(100\s*ml|napój|napoje)\b/i', $hdr[0][0] . ' ' . $nutrition);
        $sugarsOk = ($sugars !== null) ? ($sugars <= ($isDrink ? 2.5 : 5.0)) : null;
        $fiberOk  = ($fiber  !== null) ? ($fiber  >= 6.0) : null;
        $netCarbs = null;
        if ($carbs !== null) { $netCarbs = $carbs - ($fiber ?? 0.0) - $polyols; }
        $netOk = ($netCarbs !== null) ? ($netCarbs <= 15.0) : null;
        if ($sugarsOk === true || $fiberOk === true) { if ($netOk === false) return false; return true; }
        if ($sugarsOk === false) return false;
        return null;
    }

    /**
     * ⭐ POPRAWKA: Rozbudowano wzorce do sprawdzania alergenów.
     */
    private function isInAllergenDisclaimer(string $text, string $word): bool
    {
        $patterns = [
            // Wzorzec 1: "może zawierać", "śladowe ilości", "may contain"
            '/(?:może\s+zawierać|moze\s+zawierac|śladowe\s+ilości|sladowe\s+ilosci|may\s+contain)[^.]{0,140}\b' . preg_quote($word, '/') . '\b/iu',
            // Wzorzec 2: "Wyprodukowano w zakładzie...", "Na terenie zakładu...", "...przetwarza się..."
            '/(?:wyprodukowano\s+w\s+zakładzie|na\s+terenie\s+zakładu|w\s+zakładzie\s+produkcyjnym|zakładzie\s+konfekcjonuj[aą]cym)[^.]{0,160}(?:przetwarza\s+się|używa\s+się|występuj[eą]|konfekcjonuje\s+się)\b[^.]{0,160}\b' . preg_quote($word, '/') . '\b/iu'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }
        return false;
    }
}