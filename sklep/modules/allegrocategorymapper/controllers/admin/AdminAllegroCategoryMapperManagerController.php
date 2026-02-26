<?php
// /modules/allegrocategorymapper/controllers/admin/AdminAllegroCategoryMapperManagerController.php

use ACM\Api\AllegroClient;
use ACM\Domain\CategoryPathBuilder;
use ACM\Domain\AssignmentService;
use ACM\Domain\Logger;

class AdminAllegroCategoryMapperManagerController extends ModuleAdminController
{
    public function __construct(){ $this->bootstrap = true; parent::__construct(); }

    public function initContent()
    {
        parent::initContent();
        $operation_summary = $this->context->cookie->acm_operation_summary;
        if ($operation_summary) {
            $this->context->smarty->assign('operation_summary', json_decode($operation_summary, true));
            unset($this->context->cookie->acm_operation_summary);
        }
        if (Tools::isSubmit('submitApplyMapping')) {
            $this->processApplyMapping();
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminAllegroCategoryMapperManager'));
        }
        $id_shop = (int)$this->context->shop->id;
        $countDone = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'allegro_ean_done`');
        $countTotalActive = (int)Db::getInstance()->getValue('SELECT COUNT(p.id_product) FROM `' . _DB_PREFIX_ . 'product` p INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON (p.id_product = ps.id_product AND ps.id_shop = ' . $id_shop . ') WHERE ps.active = 1');
        $countToDo = $countTotalActive - $countDone;
        $latestBatchId = $this->getLatestBatchId();
        $results_by_product = $this->getScanResultsByProduct($latestBatchId);
        $categoriesTree = $this->buildCategoryTreeForShop((int)$this->context->language->id, (int)$this->context->shop->id, true);
        
        $categoriesFile = _PS_MODULE_DIR_.'allegrocategorymapper/cache/allegro_categories.json';
        $categoriesFileInfo = null;
        if (file_exists($categoriesFile)) {
            $categoriesFileInfo = [
                'exists' => true,
                'date' => date('Y-m-d H:i:s', filemtime($categoriesFile))
            ];
        }

        $this->context->smarty->assign(['latestBatchId' => $latestBatchId, 'results_by_product' => $results_by_product, 'categoriesTree' => $categoriesTree, 'root_category_id' => (int)Configuration::get('ACM_ROOT_CATEGORY_ID'), 'build_full_path' => (int)Configuration::get('ACM_BUILD_FULL_PATH'), 'debug_enabled' => (bool)Configuration::get('ACM_DEBUG'), 'countDone' => $countDone, 'countToDo' => $countToDo, 'scan_chunk' => (int)Configuration::get('ACM_SCAN_CHUNK_SIZE'), 'categoriesFileInfo' => $categoriesFileInfo]);
        $this->setTemplate('manager.tpl');
    }

    protected function buildCategoryTreeForShop($id_lang, $id_shop, $fetchMappingStatus = false)
    {
        $productCounts = [];
        $sqlCounts = 'SELECT cp.`id_category`, COUNT(cp.`id_product`) as count FROM `' . _DB_PREFIX_ . 'category_product` cp INNER JOIN `' . _DB_PREFIX_ . 'product_shop` ps ON (ps.`id_product` = cp.`id_product` AND ps.`id_shop` = ' . (int)$id_shop . ') WHERE ps.`active` = 1 GROUP BY cp.`id_category`';
        foreach (Db::getInstance()->executeS($sqlCounts) as $row) {
            $productCounts[(int)$row['id_category']] = (int)$row['count'];
        }
        $sql = 'SELECT c.id_category, cs.id_shop, c.id_parent, c.level_depth, c.nleft, cl.name' . ($fetchMappingStatus ? ', IF(acm.id IS NOT NULL, 1, 0) as is_mapped' : '') . ' FROM ' . _DB_PREFIX_ . 'category c INNER JOIN ' . _DB_PREFIX_ . 'category_shop cs ON (cs.id_category=c.id_category AND cs.id_shop=' . (int)$id_shop . ') INNER JOIN ' . _DB_PREFIX_ . 'category_lang cl ON (cl.id_category=c.id_category AND cl.id_lang=' . (int)$id_lang . ' AND cl.id_shop=' . (int)$id_shop . ')' . ($fetchMappingStatus ? ' LEFT JOIN `' . _DB_PREFIX_ . 'allegro_category_map` acm ON (acm.ps_id_category = c.id_category)' : '') . ' WHERE c.nleft IS NOT NULL AND c.active = 1 ORDER BY c.nleft ASC';
        $rows = Db::getInstance()->executeS($sql);
        $nodes = [];
        foreach ($rows as $r) {
            $id = (int)$r['id_category'];
            $nodes[$id] = ['id' => $id, 'parent' => (int)$r['id_parent'], 'name' => $r['name'], 'children' => [], 'is_mapped' => (isset($r['is_mapped']) ? (bool)$r['is_mapped'] : false), 'product_count' => $productCounts[$id] ?? 0];
        }
        $homeId = (int)Configuration::get('PS_HOME_CATEGORY');
        $rootList = [];
        foreach ($nodes as $id => &$node) {
            $pid = (int)$node['parent'];
            if (isset($nodes[$pid])) {
                $nodes[$pid]['children'][] = &$node;
            }
        }
        if (isset($nodes[$homeId])) {
            $rootList = $nodes[$homeId]['children'];
        } else {
            foreach ($nodes as $nid => $n) {
                if (!isset($nodes[(int)$n['parent']])) {
                    $rootList[] = $nodes[$nid];
                }
            }
        }
        return $rootList;
    }

    protected function processApplyMapping()
    {
        $mappings = Tools::getValue('mappings', []);
        $manualMappings = Tools::getValue('manual_mapping', []);
        $manualSelections = Tools::getValue('manual_selection', []);
        $selectedProducts = Tools::getValue('product_selection', []);
        if (empty($selectedProducts)) {
            $this->errors[] = $this->l('Nie zaznaczono żadnych produktów do mapowania.');
            return;
        }
        $rootId = (int)Tools::getValue('root_category_id', (int)Configuration::get('ACM_ROOT_CATEGORY_ID'));
        $buildFullPath = (bool)Tools::getValue('build_full_path');
        $markDone = (bool)Tools::getValue('mark_done');
        $changeDefault = (bool)Tools::getValue('change_default_category');
        $batchId = (int)Tools::getValue('batch_id');
        if (!$rootId) { $this->errors[] = $this->l('Brak ID kategorii głównej.'); return; }
        $assignedProductsCount = 0;
        $processedProducts = [];
        $builder = new CategoryPathBuilder((int)$this->context->language->id);
        $assigner = new AssignmentService();
        $logger = new Logger((bool)Configuration::get('ACM_DEBUG'));
        $client = new AllegroClient(Configuration::get('ACM_API_URL'), Configuration::get('ACM_ACCESS_TOKEN'), $logger);
        $summary = ['created' => [], 'reused' => [], 'assigned_count' => 0];
        foreach ($selectedProducts as $productId => $on) {
            $allegroDataToProcess = [];
            if (!empty($manualMappings[$productId]) && isset($manualSelections[$productId])) {
                try {
                    $catId = trim($manualMappings[$productId]);
                    $pathData = $this->findCategoryInLocalFile($catId);
                    if ($pathData) {
                         $allegroDataToProcess[] = [
                            'allegro_category_id' => $catId,
                            'allegro_category_name' => $pathData['name'],
                            'allegro_category_path' => json_encode($pathData['path_array']),
                        ];
                    } else {
                       $this->errors[] = sprintf($this->l('Nie znaleziono kategorii o ID %s w lokalnej bazie. Pobierz kategorie ponownie.'), $catId);
                    }
                } catch (Exception $e) {
                    $this->errors[] = sprintf($this->l('Błąd przy przetwarzaniu ręcznej kategorii %s: %s'), $catId, $e->getMessage());
                }
            }
            if (!empty($mappings[$productId])) {
                $allegroDataArray = $mappings[$productId];
                if (!is_array($allegroDataArray)) { $allegroDataArray = [$allegroDataArray]; }
                foreach ($allegroDataArray as $allegroDataJson) {
                    $allegroDataToProcess[] = json_decode(urldecode($allegroDataJson), true);
                }
            }
            if (empty($allegroDataToProcess)) { continue; }
            $newCategoryIdsForProduct = [];
            foreach ($allegroDataToProcess as $allegroData) {
                if (empty($allegroData['allegro_category_id'])) { continue; }
                $psCatId = 0;
                if ($buildFullPath) {
                    $path = is_array($allegroData['allegro_category_path']) ? $allegroData['allegro_category_path'] : json_decode($allegroData['allegro_category_path'], true);
                    $builderResult = $builder->ensureFullPath($rootId, $path, (string)$allegroData['allegro_category_id'], (string)$allegroData['allegro_category_name']);
                } else {
                    $builderResult = $builder->ensureLeaf($rootId, (string)$allegroData['allegro_category_id'], (string)$allegroData['allegro_category_name']);
                }
                $psCatId = $builderResult['id'];
                $summary['created'] = array_merge($summary['created'], $builderResult['created']);
                $summary['reused'] = array_merge($summary['reused'], $builderResult['reused']);
                if ($psCatId > 0) { $newCategoryIdsForProduct[] = $psCatId; }
            }
            if (!empty($newCategoryIdsForProduct)) {
                if ($assigner->moveProductToCategories((int)$productId, $newCategoryIdsForProduct, $changeDefault)) {
                    $assignedProductsCount++;
                    $processedProducts[(int)$productId] = true;
                }
            }
        }
        if ($markDone && !empty($processedProducts)) {
            foreach (array_keys($processedProducts) as $id_product) {
                Db::getInstance()->insert('allegro_ean_done', ['id_product' => (int)$id_product, 'done_at' => date('Y-m-d H:i:s'), 'last_batch_id' => (int)$batchId], false, true, Db::ON_DUPLICATE_KEY);
            }
        }
        $summary['assigned_count'] = $assignedProductsCount;
        $summary['created'] = array_unique($summary['created']);
        $summary['reused'] = array_unique($summary['reused']);
        $this->context->cookie->acm_operation_summary = json_encode($summary);
    }

    public function ajaxProcessPrepareScan()
    {
        $selected = Tools::getValue('category_ids', []);
        if (!is_array($selected) || empty($selected)) {
            die(json_encode(['ok' => false, 'error' => 'No categories']));
        }
        
        // Odczytujemy limit z konfiguracji
        $limit = (int)Configuration::get('ACM_SCAN_CHUNK_SIZE', null, null, null, 200);

        $skipDone = (bool)Configuration::get('ACM_SKIP_DONE');
        $id_shop = (int)$this->context->shop->id;
        $ids = array_map('intval', $selected);
        $ids_str = implode(',', $ids);

        $sql = 'SELECT DISTINCT p.id_product FROM ' . _DB_PREFIX_ . 'category_product cp INNER JOIN ' . _DB_PREFIX_ . 'product p ON (p.id_product = cp.id_product) INNER JOIN ' . _DB_PREFIX_ . 'product_shop ps ON (ps.id_product=p.id_product AND ps.id_shop=' . (int)$id_shop . ') WHERE cp.id_category IN (' . $ids_str . ') AND ps.active = 1';
        if ($skipDone) {
            $sql .= ' AND p.id_product NOT IN (SELECT id_product FROM ' . _DB_PREFIX_ . 'allegro_ean_done)';
        }
        
        // Dodajemy LIMIT do zapytania, aby pobrać maksymalnie tyle produktów, ile ustawiono
        $sql .= ' ORDER BY p.id_product LIMIT ' . $limit;

        $rows = Db::getInstance()->executeS($sql);
        $product_ids = array_map(function ($r) {
            return (int)$r['id_product'];
        }, $rows);

        // Zwracamy listę ID, która ma już nałożony limit
        die(json_encode(['ok' => true, 'ids' => $product_ids, 'total' => count($product_ids), 'batch_id' => time()]));
    }

    public function ajaxProcessScanChunk()
    {
        $ids = Tools::getValue('ids', []);
        $batchId = (int)Tools::getValue('batch_id');
        $selectMode = (string)Configuration::get('ACM_SELECT_MODE');
        $maxRes = (int)Configuration::get('ACM_MAX_RESULTS_PER_PRODUCT');
        $minWords = (int)Configuration::get('ACM_MIN_SEARCH_WORDS', null, null, null, 3);
        $useNameSearch = (bool)Configuration::get('ACM_USE_NAME_SEARCH', null, null, null, true);
        $logger = new Logger((bool)Configuration::get('ACM_DEBUG'));
        $client = new AllegroClient(Configuration::get('ACM_API_URL'), Configuration::get('ACM_ACCESS_TOKEN'), $logger);
        $id_lang = (int)$this->context->language->id;
        $saved = 0; $noean = 0; $errors = 0;

        foreach ($ids as $pid) {
            $product = new \Product((int)$pid, false, $id_lang);
            if (!\Validate::isLoadedObject($product)) {
                $errors++; continue;
            }
            $ean = $product->ean13 ?: '';
            if (!$ean && $product->hasCombinations()) {
                $attr = Db::getInstance()->getRow('SELECT ean13 FROM ' . _DB_PREFIX_ . 'product_attribute WHERE id_product=' . (int)$product->id . ' AND ean13<>"" ORDER BY id_product_attribute ASC');
                if (!empty($attr['ean13'])) $ean = $attr['ean13'];
            }
            
            $cats = [];
            try {
                $prods = [];
                if ($ean) {
                    $res = $client->searchByEan($ean);
                    $prods = isset($res['products']) ? $res['products'] : [];
                } else {
                    $noean++;
                }
                if ($useNameSearch && empty($prods) && !empty($product->name)) {
                    $cleanName = preg_replace('/(\s+\d+(\.\d+)?\s*(g|kg|ml|l|szt\.?))$/i', '', $product->name);
                    $words = explode(' ', $cleanName);
                    for ($i = count($words); $i >= $minWords; $i--) {
                        $phrase = implode(' ', array_slice($words, 0, $i));
                        if (Tools::strlen($phrase) < 4) continue;
                        $res = $client->searchByName($phrase);
                        $prods = isset($res['products']) ? $res['products'] : [];
                        if (!empty($prods)) {
                            break;
                        }
                    }
                }
                if (!empty($prods)) {
                    foreach ($prods as $pr) {
                        $cid = $pr['category']['id'] ?? null;
                        $path = $pr['category']['path'] ?? [];
                        $name = end($path)['name'] ?? ($pr['category']['name'] ?? '');
                        if (!$cid) continue;
                        $score = isset($pr['matching']['score']) ? (float)$pr['matching']['score'] : 0.0;
                        $offers = isset($pr['offersCount']) ? (int)$pr['offersCount'] : 0;
                        $cats[$cid] = ['id' => $cid, 'name' => $name, 'path' => $path, 'score' => $score, 'offers' => $offers];
                        if (count($cats) >= $maxRes) break;
                    }
                }
            } catch (\Exception $e) {
                $errors++;
                if ($logger->isEnabled()) $logger->add('Chunk error', ['pid' => $pid, 'msg' => $e->getMessage()]);
            }

            if (empty($cats)) {
                Db::getInstance()->insert('allegro_ean_results', ['batch_id' => $batchId, 'id_product' => $product->id, 'ean' => pSQL($ean), 'allegro_category_id' => 'NO_MATCH', 'allegro_category_name' => 'Nie znaleziono dopasowania', 'allegro_category_path' => '', 'score' => 0, 'found_at' => date('Y-m-d H:i:s')]);
                $saved++;
            } else {
                $selected = ($selectMode === 'all') ? array_values($cats) : [max($cats)];
                foreach ($selected as $c) {
                    Db::getInstance()->insert('allegro_ean_results', ['batch_id' => $batchId, 'id_product' => $product->id, 'ean' => pSQL($ean), 'allegro_category_id' => pSQL((string)$c['id']), 'allegro_category_name' => pSQL($c['name']), 'allegro_category_path' => pSQL(json_encode($c['path'])), 'offers_count' => (int)$c['offers'], 'score' => (float)$c['score'], 'found_at' => date('Y-m-d H:i:s')]);
                    $saved++;
                }
            }
        }
        die(json_encode(['ok' => true, 'saved' => $saved, 'noean' => $noean, 'errors' => $errors, 'processed_count' => count($ids)]));
    }
    
    protected function getLatestBatchId()
    {
        $row = Db::getInstance()->getRow('SELECT MAX(batch_id) as bid FROM ' . _DB_PREFIX_ . 'allegro_ean_results');
        return (int)($row['bid'] ?? 0);
    }

    protected function getScanResultsByProduct($batchId)
    {
        if (!$batchId) {
            return [];
        }
        $sql = 'SELECT r.id_product, r.ean, r.allegro_category_id, r.allegro_category_name, r.allegro_category_path, pl.name as product_name FROM `' . _DB_PREFIX_ . 'allegro_ean_results` r LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.id_product = r.id_product AND pl.id_lang = ' . (int)$this->context->language->id . ') WHERE r.batch_id = ' . (int)$batchId . ' ORDER BY pl.name ASC, r.score DESC';
        $results = Db::getInstance()->executeS($sql);
        $groupedResults = [];
        if (is_array($results)) {
            foreach ($results as $row) {
                $productId = (int)$row['id_product'];
                if (!isset($groupedResults[$productId])) {
                    $groupedResults[$productId] = ['id_product' => $productId, 'product_name' => $row['product_name'], 'ean' => $row['ean'], 'options' => []];
                }
                $pathArray = json_decode($row['allegro_category_path'], true);
                $pathFormatted = $row['allegro_category_name'];
                if (is_array($pathArray)) {
                    if (!empty($pathArray) && isset($pathArray[0]['name']) && strtolower($pathArray[0]['name']) === 'allegro') {
                        array_shift($pathArray);
                    }
                    $pathNames = array_column($pathArray, 'name');
                    $pathFormatted = implode(' > ', $pathNames);
                }
                $groupedResults[$productId]['options'][] = ['allegro_category_id' => $row['allegro_category_id'], 'allegro_category_name' => $row['allegro_category_name'], 'allegro_category_path' => $row['allegro_category_path'], 'allegro_category_path_formatted' => $pathFormatted];
            }
        }
        return $groupedResults;
    }

    public function ajaxProcessFetchCategoryPath()
    {
        $categoryId = Tools::getValue('categoryId');
        if (!$categoryId) {
            die(json_encode(['ok' => false, 'error' => 'Missing categoryId']));
        }
        
        $result = $this->findCategoryInLocalFile($categoryId);

        if ($result) {
            die(json_encode(['ok' => true, 'path' => $result['path_string']]));
        } else {
            die(json_encode(['ok' => false, 'error' => 'Nie znaleziono w pliku. Pobierz kategorie.']));
        }
    }

    public function ajaxProcessDownloadCategories()
    {
        try {
            $logger = new Logger((bool)Configuration::get('ACM_DEBUG'));
            $client = new AllegroClient(Configuration::get('ACM_API_URL'), Configuration::get('ACM_ACCESS_TOKEN'), $logger);
            
            $categories = $client->fetchAllCategoriesWithChildren();

            $cacheDir = _PS_MODULE_DIR_.'allegrocategorymapper/cache/';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            $file = $cacheDir . 'allegro_categories.json';
            
            if (file_put_contents($file, json_encode($categories))) {
                 die(json_encode(['ok' => true, 'message' => 'Pobrano ' . count($categories) . ' głównych kategorii.', 'date' => date('Y-m-d H:i:s')]));
            } else {
                 die(json_encode(['ok' => false, 'error' => 'Nie udało się zapisać pliku. Sprawdź uprawnienia do zapisu w folderze modułu.']));
            }
        } catch (Exception $e) {
            die(json_encode(['ok' => false, 'error' => $e->getMessage()]));
        }
    }

    private function findCategoryInLocalFile($categoryId) {
        $file = _PS_MODULE_DIR_.'allegrocategorymapper/cache/allegro_categories.json';
        if (!file_exists($file)) {
            return null;
        }

        $categories = json_decode(file_get_contents($file), true);
        if (!$categories) {
            return null;
        }

        return $this->findCategoryInTree($categories, $categoryId);
    }
    
    private function findCategoryInTree(array $categories, $categoryId, $parentPath = [])
    {
        foreach ($categories as $category) {
            $currentPath = array_merge($parentPath, [['id' => $category['id'], 'name' => $category['name']]]);
            
            if ($category['id'] == $categoryId) {
                $pathNames = array_column($currentPath, 'name');
                return [
                    'path_string' => implode(' > ', $pathNames),
                    'path_array' => $currentPath,
                    'name' => $category['name'],
                ];
            }

            if (!empty($category['children'])) {
                $found = $this->findCategoryInTree($category['children'], $categoryId, $currentPath);
                if ($found) {
                    return $found;
                }
            }
        }
        return null;
    }

    public function ajaxProcessSearchAllegroCategory()
    {
        $q = Tools::getValue('q', '');
        $q = trim($q);
        $limit = (int)Tools::getValue('limit', 20);
        if ($limit <= 0) { $limit = 20; }

        $file = _PS_MODULE_DIR_.'allegrocategorymapper/cache/allegro_categories.json';
        if (!file_exists($file)) {
            die(json_encode(['ok' => false, 'error' => 'Categories file not found']));
        }
        $json = @file_get_contents($file);
        if ($json === false) {
            die(json_encode(['ok' => false, 'error' => 'Cannot read categories file']));
        }
        $tree = json_decode($json, true);
        if (!is_array($tree)) {
            die(json_encode(['ok' => false, 'error' => 'Bad JSON']));
        }

        $results = [];

        // If query is numeric, try exact id match first
        if ($q !== '' && ctype_digit($q)) {
            $found = $this->findCategoryInTree($tree, $q, []);
            if ($found) {
                $results[] = ['id' => (string)$q, 'label' => $found['path_string']];
                die(json_encode(['ok' => true, 'data' => $results]));
            }
        }

        $needle = Tools::strtolower(Tools::replaceAccentedChars($q));

        $collect = function($node, $path) use (&$collect, &$results, $needle, $limit) {
            if (isset($node['name'])) {
                $currentPath = $path;
                $currentPath[] = $node['name'];
                $label = implode(' > ', $currentPath);

                $hay = Tools::strtolower(Tools::replaceAccentedChars($label));
                if ($needle !== '' && strpos($hay, $needle) !== false) {
                    $id = isset($node['id']) ? (string)$node['id'] : '';
                    $results[] = ['id' => $id, 'label' => $label];
                    if (count($results) >= $limit) {
                        return true; // stop
                    }
                }

                if (!empty($node['children']) && is_array($node['children'])) {
                    foreach ($node['children'] as $child) {
                        if ($collect($child, $currentPath)) {
                            return true;
                        }
                    }
                }
            } elseif (is_array($node)) {
                foreach ($node as $child) {
                    if ($collect($child, $path)) {
                        return true;
                    }
                }
            }
            return false;
        };
        $collect($tree, []);

        die(json_encode(['ok' => true, 'data' => $results]));
    }


    /**
     * Delete selected categories with BO-like options.
     * mode: move_hide | move | delete_products
     * ids: JSON array or CSV of category IDs
     */
    public function ajaxProcessDeleteCategories()
    {
        $idsParam = Tools::getValue('ids', '');
        if (is_string($idsParam) && Tools::substr($idsParam, 0, 1) === '[') {
            $ids = json_decode($idsParam, true);
        } else {
            $ids = is_array($idsParam) ? $idsParam : preg_split('/[,\s]+/', (string)$idsParam);
        }
        $ids = array_values(array_unique(array_map('intval', (array)$ids)));
        $mode = Tools::getValue('mode', 'move_hide');
        if (!$ids) {
            die(json_encode(['ok'=>false,'error'=>'No categories selected']));
        }
        $idShop = (int)$this->context->shop->id;
        $idLang = (int)$this->context->language->id;
        $protected = [(int)$this->context->shop->id_category]; // shop root

        foreach ($ids as $idCategory) {
            if ($idCategory <= 0 || in_array($idCategory, $protected, true)) {
                continue;
            }
            $this->acmDeleteCategoryStrategy((int)$idCategory, $mode, $idShop, $idLang);
        }
        die(json_encode(['ok'=>true]));
    }

    /**
     * Recursive category deletion according to strategy.
     */
    protected function acmDeleteCategoryStrategy($idCategory, $mode, $idShop, $idLang)
    {
        $cat = new Category((int)$idCategory, $idLang, $idShop);
        if (!Validate::isLoadedObject($cat)) {
            return;
        }

        // Handle products that are ONLY in this category
        $productRows = Db::getInstance()->executeS('
            SELECT DISTINCT cp.id_product
            FROM `'._DB_PREFIX_.'category_product` cp
            WHERE cp.id_category='.(int)$idCategory.'
        ');
        $parentId = (int)$cat->id_parent;

        foreach ($productRows as $row) {
            $idProduct = (int)$row['id_product'];
            $otherCount = (int)Db::getInstance()->getValue('
                SELECT COUNT(*)
                FROM `'._DB_PREFIX_.'category_product`
                WHERE id_product='.(int)$idProduct.' AND id_category!='.(int)$idCategory.'
            ');
            if ($otherCount > 0) {
                continue; // has other categories
            }

            if ($mode === 'delete_products') {
                $p = new Product($idProduct, false, $idLang, $idShop);
                if (Validate::isLoadedObject($p)) {
                    $p->delete();
                }
            } else {
                // move to parent
                if ($parentId > 0) {
                    Db::getInstance()->insert('category_product', [
                        'id_category' => (int)$parentId,
                        'id_product' => (int)$idProduct,
                        'position' => 0,
                    ], false, true, Db::REPLACE);
                    Db::getInstance()->update('product', [
                        'id_category_default' => (int)$parentId,
                    ], 'id_product='.(int)$idProduct.' AND id_category_default='.(int)$idCategory);

                    if ($mode === 'move_hide') {
                        Db::getInstance()->update('product_shop', [
                            'visibility' => pSQL('none'),
                        ], 'id_product='.(int)$idProduct.' AND id_shop='.(int)$idShop);
                    }
                }
            }
        }

        // Recurse on children
        $children = Db::getInstance()->executeS('
            SELECT id_category FROM `'._DB_PREFIX_.'category`
            WHERE id_parent='.(int)$idCategory.'
        ');
        foreach ($children as $child) {
            $this->acmDeleteCategoryStrategy((int)$child['id_category'], $mode, $idShop, $idLang);
        }

        // Delete category itself
        $cat->delete();
    }
    public function ajaxProcessCountSelectedImpact()
{
    header('Content-Type: application/json; charset=utf-8');

    $idsRaw = Tools::getValue('category_ids');
    $ids = is_string($idsRaw) ? @json_decode($idsRaw, true) : (array)$idsRaw;
    $ids = array_values(array_filter(array_map('intval', (array)$ids)));
    if (empty($ids)) {
        die(json_encode(array('ok' => false, 'error' => 'Brak ID kategorii.')));
    }

    $idShop = (int)$this->context->shop->id;
    $idLang = (int)$this->context->language->id;
    $idsSql = implode(',', array_map('intval', $ids));

    // nazwy
    $names = array();
    $sqlN = 'SELECT c.id_category, COALESCE(cl.name, CONCAT("ID ", c.id_category)) AS name
             FROM `'._DB_PREFIX_.'category` c
             LEFT JOIN `'._DB_PREFIX_.'category_lang` cl
               ON (cl.id_category=c.id_category AND cl.id_shop='.(int)$idShop.' AND cl.id_lang='.(int)$idLang.')
             WHERE c.id_category IN ('.$idsSql.')';
    foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlN) as $row) {
        $names[(int)$row['id_category']] = $row['name'];
    }

    // liczniki
    $counts = array();
    $sqlC = 'SELECT cp.id_category, COUNT(DISTINCT cp.id_product) AS cnt
             FROM `'._DB_PREFIX_.'category_product` cp
             WHERE cp.id_category IN ('.$idsSql.')
             GROUP BY cp.id_category';
    $total = 0;
    foreach (Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlC) as $r) {
        $cid = (int)$r['id_category'];
        $cnt = (int)$r['cnt'];
        $counts[$cid] = $cnt;
        $total += $cnt;
    }

    $items = array();
    foreach ($ids as $cid) {
        $items[] = array(
            'id'   => (int)$cid,
            'name' => isset($names[$cid]) ? $names[$cid] : ('ID '.$cid),
            'cnt'  => isset($counts[$cid]) ? (int)$counts[$cid] : 0,
        );
    }

    die(json_encode(array('ok'=>true, 'items'=>$items, 'total'=>$total)));
}

public function ajaxProcessResetLinksToNew()
{
    header('Content-Type: application/json; charset=utf-8');

    $idsRaw = Tools::getValue('category_ids');
    $ids = is_string($idsRaw) ? @json_decode($idsRaw, true) : (array)$idsRaw;
    $ids = array_values(array_filter(array_map('intval', (array)$ids)));
    if (empty($ids)) {
        die(json_encode(array('ok'=>false,'error'=>'Brak ID kategorii.')));
    }

    $idShop = (int)$this->context->shop->id;
    $newId  = 507;
    $idsSql = implode(',', array_map('intval', $ids));

    // zbierz produkty
    $products = Db::getInstance()->executeS('
        SELECT DISTINCT cp.id_product
        FROM `'._DB_PREFIX_.'category_product` cp
        WHERE cp.id_category IN ('.$idsSql.')
    ');
    $pids = array_map(function($r){ return (int)$r['id_product']; }, $products);
    if (empty($pids)) {
        die(json_encode(array('ok'=>true, 'message'=>'Brak produktów do zmiany.')));
    }
    $pidsSql = implode(',', array_map('intval', $pids));

    // dołącz NEW
    Db::getInstance()->execute('
        INSERT IGNORE INTO `'._DB_PREFIX_.'category_product` (id_category, id_product)
        SELECT '.(int)$newId.', cp.id_product
        FROM `'._DB_PREFIX_.'category_product` cp
        WHERE cp.id_category IN ('.$idsSql.')
    ');

    // ustaw domyślną
    Db::getInstance()->execute('
        UPDATE `'._DB_PREFIX_.'product`
        SET id_category_default='.(int)$newId.'
        WHERE id_product IN ('.$pidsSql.')
    ');
    Db::getInstance()->execute('
        UPDATE `'._DB_PREFIX_.'product_shop`
        SET id_category_default='.(int)$newId.'
        WHERE id_product IN ('.$pidsSql.') AND id_shop='.(int)$idShop.'
    ');

    // resetuj „zrobione”
    if (Db::getInstance()->executeS('SHOW TABLES LIKE "'._DB_PREFIX_.'allegro_ean_done"')) {
        Db::getInstance()->execute('
            DELETE d FROM `'._DB_PREFIX_.'allegro_ean_done` d
            JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.id_product=d.id_product)
            WHERE cp.id_category IN ('.$idsSql.')
        ');
    }

    die(json_encode(array('ok'=>true,'message'=>'Zmieniono produkty: '.count($pids).'.')));
}

public function ajaxProcessRescanSelected()
{
    header('Content-Type: application/json; charset=utf-8');

    $idsRaw = Tools::getValue('category_ids');
    $ids = is_string($idsRaw) ? @json_decode($idsRaw, true) : (array)$idsRaw;
    $ids = array_values(array_filter(array_map('intval', (array)$ids)));
    if (empty($ids)) {
        die(json_encode(array('ok'=>false,'error'=>'Brak ID kategorii.')));
    }
    $idsSql = implode(',', array_map('intval', $ids));

    $products = Db::getInstance()->executeS('
        SELECT DISTINCT cp.id_product
        FROM `'._DB_PREFIX_.'category_product` cp
        WHERE cp.id_category IN ('.$idsSql.')
    ');
    if (!empty($products) && Db::getInstance()->executeS('SHOW TABLES LIKE "'._DB_PREFIX_.'allegro_ean_done"')) {
        Db::getInstance()->execute('
            DELETE d FROM `'._DB_PREFIX_.'allegro_ean_done` d
            JOIN `'._DB_PREFIX_.'category_product` cp ON (cp.id_product=d.id_product)
            WHERE cp.id_category IN ('.$idsSql.')
        ');
    }

    die(json_encode(array('ok'=>true,'message'=>'Zlecono ponowne skanowanie. Produkty: '.count($products).'.')));
}


}
