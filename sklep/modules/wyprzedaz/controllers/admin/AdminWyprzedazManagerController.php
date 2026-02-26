<?php
/**
 * Ścieżka do pliku: /modules/wyprzedaz/controllers/admin/AdminWyprzedazManagerController.php
 */
class AdminWyprzedazManagerController extends ModuleAdminController
{
    /* ====== VAR DIR + SESSION HELPERS (PS 8.2.1) ====== */

    protected function sanitizeSid($sid)
    {
        return preg_replace('/[^A-Za-z0-9_.-]/', '', (string)$sid);
    }
    protected function varDir()
    {
        $dir = _PS_MODULE_DIR_.'wyprzedaz/var';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        return $dir;
    }
    protected function getSessionPath($sid)
    {
        return $this->varDir().'/import_'.$sid.'.json';
    }
    protected function writeSession($sidOrData, $data = null)
    {
        if (is_array($sidOrData) && $data === null) {
            $data = $sidOrData;
            $sid = isset($data['session_id']) ? $data['session_id'] : null;
        } else {
            $sid = $sidOrData;
        }
        if (!$sid || !is_array($data)) return false;
        return (bool)@file_put_contents($this->getSessionPath($sid), json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    protected function readSession($sid)
    {
        $f = $this->getSessionPath($sid);
        if (!file_exists($f)) return null;
        $j = @file_get_contents($f);
        return $j ? json_decode($j, true) : null;
    }
    protected function removeSession($sid)
    {
        $f = $this->getSessionPath($sid);
        if (file_exists($f)) @unlink($f);
    }
    protected function getStagingTable(){ return _DB_PREFIX_.'wyprzedaz_csv_staging'; }
    protected function getTasksTable(){   return _DB_PREFIX_.'wyprzedaz_finalize_tasks'; }

    const TABLE_HISTORY = 'wyprzedaz_import_history';
    const TABLE_DUPES   = 'wyprzedaz_csv_duplikaty';
    const TABLE_DETAILS = 'wyprzedaz_product_details';
    const TABLE_NOTF    = 'wyprzedaz_not_found_products';

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initContent()
    {
        if (Tools::isSubmit('submitWyprzedazSettings')) {
            Configuration::updateValue('WYPRZEDAZ_DISCOUNT_SHORT', (float)Tools::getValue('WYPRZEDAZ_DISCOUNT_SHORT'));
            Configuration::updateValue('WYPRZEDAZ_DISCOUNT_30', (float)Tools::getValue('WYPRZEDAZ_DISCOUNT_30'));
            Configuration::updateValue('WYPRZEDAZ_DISCOUNT_90', (float)Tools::getValue('WYPRZEDAZ_DISCOUNT_90'));
            Configuration::updateValue('WYPRZEDAZ_DISCOUNT_OVER', (float)Tools::getValue('WYPRZEDAZ_DISCOUNT_OVER'));
            Configuration::updateValue('WYPRZEDAZ_SHORT_DATE_DAYS', (int)Tools::getValue('WYPRZEDAZ_SHORT_DATE_DAYS'));
            Configuration::updateValue('WYPRZEDAZ_DISCOUNT_VERY_SHORT', (float)Tools::getValue('WYPRZEDAZ_DISCOUNT_VERY_SHORT'));
            Configuration::updateValue('WYPRZEDAZ_DISCOUNT_BIN', (float)Tools::getValue('WYPRZEDAZ_DISCOUNT_BIN'));
            Configuration::updateValue('WYPRZEDAZ_IGNORE_BIN_EXPIRY', (int)Tools::getValue('WYPRZEDAZ_IGNORE_BIN_EXPIRY'));
            
            Configuration::updateValue('WYPRZEDAZ_ENABLE_OVER90_LONGEXP', (int)Tools::getValue('WYPRZEDAZ_ENABLE_OVER90_LONGEXP'));
            Configuration::updateValue('WYPRZEDAZ_DISCOUNT_OVER90_LONGEXP', (float)Tools::getValue('WYPRZEDAZ_DISCOUNT_OVER90_LONGEXP'));
            $this->confirmations[] = $this->l('Ustawienia rabatów zostały zapisane.');
        }

        if (Tools::getValue('export_not_found_csv') == '1' || Tools::getValue('export_not_found') == '1') {
            $this->exportNotFoundEansCsv();
            exit;
        }

        $sql_expired = new DbQuery();
        $sql_expired->select('COUNT(DISTINCT p.id_product)');
        $sql_expired->from('product', 'p');
        $sql_expired->leftJoin('wyprzedaz_product_details', 'wpd', 'p.id_product = wpd.id_product');
        $sql_expired->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = '.(int)$this->context->shop->id);
        $sql_expired->where("p.reference LIKE 'A_MAG_%'");
        if (!Configuration::get('WYPRZEDAZ_IGNORE_BIN_EXPIRY')) {
            $sql_expired->where('wpd.expiry_date < CURDATE()');
        } else {
             $sql_expired->where('(wpd.expiry_date < CURDATE() AND wpd.regal != "KOSZ")');
        }
        $sql_expired->where('sa.quantity > 0');
        $expired_products_count = (int)Db::getInstance()->getValue($sql_expired);

        $shortDateThreshold = (int)Configuration::get('WYPRZEDAZ_SHORT_DATE_DAYS', 14);
        $sql_short = new DbQuery();
        $sql_short->select('COUNT(p.id_product)');
        $sql_short->from('product', 'p');
        $sql_short->leftJoin('wyprzedaz_product_details', 'wpd', 'p.id_product = wpd.id_product');
        $sql_short->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = '.(int)$this->context->shop->id);
        $sql_short->where("p.reference LIKE 'A_MAG_%'");
        $sql_short->where('wpd.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ' . $shortDateThreshold . ' DAY)');
        $sql_short->where('sa.quantity > 0');
        $short_date_products_count = (int)Db::getInstance()->getValue($sql_short);
        
        $sql_30_days = new DbQuery();
        $sql_30_days->select('COUNT(p.id_product)');
        $sql_30_days->from('product', 'p');
        $sql_30_days->leftJoin('wyprzedaz_product_details', 'wpd', 'p.id_product = wpd.id_product');
        $sql_30_days->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = '.(int)$this->context->shop->id);
        $sql_30_days->where("p.reference LIKE 'A_MAG_%'");
        $sql_30_days->where('DATEDIFF(CURDATE(), wpd.receipt_date) <= 30');
        $sql_30_days->where('sa.quantity > 0');
        $products_30_days_count = (int)Db::getInstance()->getValue($sql_30_days);

        $sql_31_90_days = new DbQuery();
        $sql_31_90_days->select('COUNT(p.id_product)');
        $sql_31_90_days->from('product', 'p');
        $sql_31_90_days->leftJoin('wyprzedaz_product_details', 'wpd', 'p.id_product = wpd.id_product');
        $sql_31_90_days->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = '.(int)$this->context->shop->id);
        $sql_31_90_days->where("p.reference LIKE 'A_MAG_%'");
        $sql_31_90_days->where('DATEDIFF(CURDATE(), wpd.receipt_date) BETWEEN 31 AND 90');
        $sql_31_90_days->where('sa.quantity > 0');
        $products_31_90_days_count = (int)Db::getInstance()->getValue($sql_31_90_days);

        $sql_over_90_days = new DbQuery();
        $sql_over_90_days->select('COUNT(p.id_product)');
        $sql_over_90_days->from('product', 'p');
        $sql_over_90_days->leftJoin('wyprzedaz_product_details', 'wpd', 'p.id_product = wpd.id_product');
        $sql_over_90_days->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = '.(int)$this->context->shop->id);
        $sql_over_90_days->where("p.reference LIKE 'A_MAG_%'");
        $sql_over_90_days->where('DATEDIFF(CURDATE(), wpd.receipt_date) > 90');
        $sql_over_90_days->where('sa.quantity > 0');
        $products_over_90_days_count = (int)Db::getInstance()->getValue($sql_over_90_days);

        $date_filter = Tools::getValue('date_filter', 'all');
        $sale_products = $this->getSaleProducts($date_filter);
        $import_history = $this->getImportHistory(); // Pobranie historii do widoku
        
        if (empty($sale_products)) {
    if (method_exists($this, 'getPreviewFromCsv')) {
        $sale_products = $this->getPreviewFromCsv();
    } else {
        $sale_products = [];
    }
}
        $duplicated_products = $this->getDuplicatesFromTempTable();
        $not_found_products = $this->getNotFoundProducts();

        $this->context->smarty->assign([
            'WYPRZEDAZ_DISCOUNT_SHORT' => Configuration::get('WYPRZEDAZ_DISCOUNT_SHORT'),
            'WYPRZEDAZ_DISCOUNT_30'    => Configuration::get('WYPRZEDAZ_DISCOUNT_30'),
            'WYPRZEDAZ_DISCOUNT_90'    => Configuration::get('WYPRZEDAZ_DISCOUNT_90'),
            'WYPRZEDAZ_DISCOUNT_OVER'  => Configuration::get('WYPRZEDAZ_DISCOUNT_OVER'),
            'WYPRZEDAZ_SHORT_DATE_DAYS' => $shortDateThreshold,
            'WYPRZEDAZ_DISCOUNT_VERY_SHORT' => Configuration::get('WYPRZEDAZ_DISCOUNT_VERY_SHORT'),
            'WYPRZEDAZ_DISCOUNT_BIN' => Configuration::get('WYPRZEDAZ_DISCOUNT_BIN'),
            'WYPRZEDAZ_IGNORE_BIN_EXPIRY' => Configuration::get('WYPRZEDAZ_IGNORE_BIN_EXPIRY'),
            'WYPRZEDAZ_ENABLE_OVER90_LONGEXP' => Configuration::get('WYPRZEDAZ_ENABLE_OVER90_LONGEXP'),
            'WYPRZEDAZ_DISCOUNT_OVER90_LONGEXP' => Configuration::get('WYPRZEDAZ_DISCOUNT_OVER90_LONGEXP'),
            'sale_products' => $sale_products,
            'duplicated_products' => $duplicated_products,
            'not_found_products' => $not_found_products,
            'import_history' => $import_history,
            'sort' => Tools::getValue('sort'),
            'way' => Tools::getValue('way'),
            'sort_not_found' => Tools::getValue('sort_not_found'),
            'way_not_found' => Tools::getValue('way_not_found'),
            'sort_duplicates' => Tools::getValue('sort_duplicates'), 
            'way_duplicates' => Tools::getValue('way_duplicates'),   
            'link' => $this->context->link,
            'date_filter' => $date_filter,
            'expired_products_count' => $expired_products_count,
            'short_date_products_count' => $short_date_products_count,
            'products_30_days_count' => $products_30_days_count,
            'products_31_90_days_count' => $products_31_90_days_count,
            'products_over_90_days_count' => $products_over_90_days_count,
            'base_dir' => __PS_BASE_URI__,
            'sale_products_count' => count($sale_products),
            'duplicate_products_count' => count(array_unique(array_column($duplicated_products, 'ean'))),
            'not_found_products_count' => count($not_found_products),
        ]);

        parent::initContent();
        $this->setTemplate('configure.tpl');
    }

    private function getSaleProducts($date_filter = 'all')
    {
        $sql = new DbQuery();
        $sql->select('p.id_product, p.reference, sa.quantity, pl.name, p.ean13, p.wholesale_price, sp.reduction, sp.reduction_type');
        $sql->select('wpd.expiry_date, wpd.receipt_date, wpd.regal, wpd.polka');
        
        $sql->from('product', 'p');
        $sql->leftJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = '.(int)$this->context->language->id);
        $sql->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = '.(int)$this->context->shop->id);
        $sql->leftJoin('category_product', 'cp', 'cp.id_product = p.id_product');
        $sql->leftJoin('specific_price', 'sp', 'sp.id_product = p.id_product');
        $sql->leftJoin(self::TABLE_DETAILS, 'wpd', 'p.id_product = wpd.id_product');

        $sql->where("p.reference LIKE 'A_MAG_%'");
        $sql->where("cp.id_category IN (45, 180)");
        $sql->where('sa.quantity > 0');

        $shortDateThreshold = (int)Configuration::get('WYPRZEDAZ_SHORT_DATE_DAYS', 14);
        $ignoreBinExpiry = (bool)Configuration::get('WYPRZEDAZ_IGNORE_BIN_EXPIRY');
        
        switch ($date_filter) {
            case 'expired':
                if (!$ignoreBinExpiry) {
                    $sql->where('wpd.expiry_date < CURDATE()');
                } else {
                    $sql->where('(wpd.expiry_date < CURDATE() AND wpd.regal != "KOSZ")');
                }
                break;
            case 'short':
                $sql->where('wpd.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ' . $shortDateThreshold . ' DAY)');
                break;
            case '30':
                $sql->where('DATEDIFF(CURDATE(), wpd.receipt_date) <= 30');
                break;
            case '90':
                $sql->where('DATEDIFF(CURDATE(), wpd.receipt_date) BETWEEN 31 AND 90');
                break;
            case 'over_90':
                $sql->where('DATEDIFF(CURDATE(), wpd.receipt_date) > 90');
                break;
        }

        $allowedSortFields = [
            'ean' => 'p.ean13', 'reference' => 'p.reference', 'quantity' => 'sa.quantity',
            'discount' => 'sp.reduction', 'expiry' => 'wpd.expiry_date', 'regal' => 'wpd.regal', 'polka' => 'wpd.polka',
        ];

        $sort = Tools::getValue('sort');
        $way = Tools::getValue('way') === 'desc' ? 'DESC' : 'ASC';

        if (isset($allowedSortFields[$sort])) {
            $sql->orderBy($allowedSortFields[$sort] . ' ' . $way);
        } else {
            $sql->orderBy('p.reference ASC');
        }

        $rows = Db::getInstance()->executeS($sql);
        
        $finalRows = [];
        $today = new DateTime();
        $today->setTime(0, 0, 0);

        foreach ($rows as $row) {
            $row['product_url'] = Context::getContext()->link->getProductLink((int)$row['id_product']);
            
            if (isset($row['regal']) && trim(mb_strtoupper($row['regal'], 'UTF-8')) === 'KOSZ') {
                if ($ignoreBinExpiry) {
                    $row['status'] = 'ok';
                } else {
                    $expiryDate = DateTime::createFromFormat('Y-m-d', $row['expiry_date']);
                    if ($expiryDate && $expiryDate < $today) {
                        $row['status'] = 'expired';
                    } elseif ($expiryDate && $today->diff($expiryDate)->days < $shortDateThreshold) {
                        $row['status'] = 'short_date';
                    } else {
                        $row['status'] = 'ok';
                    }
                }
            } else {
                $expiryDate = DateTime::createFromFormat('Y-m-d', $row['expiry_date']);
                if ($expiryDate && $expiryDate < $today) {
                    $row['status'] = 'expired';
                } elseif ($expiryDate && $today->diff($expiryDate)->days < $shortDateThreshold) {
                    $row['status'] = 'short_date';
                } else {
                    $row['status'] = 'ok';
                }
            }
            
            $finalRows[] = $row;
        }

        return $finalRows;
    }
    
    private function getDuplicatesFromTempTable()
    {
        $sql = new DbQuery();
        $sql->select('t1.ean, t1.quantity, t1.receipt_date, t1.expiry_date, t1.regal, t1.polka, pl.name, p.reference');
        $sql->from(self::TABLE_DUPES, 't1');
        $sql->leftJoin('product', 'p', 'p.ean13 = t1.ean');
        $sql->leftJoin('product_lang', 'pl', 'pl.id_product = p.id_product AND pl.id_lang = '.(int)$this->context->language->id);
        
        $sql->groupBy('t1.ean, t1.receipt_date, t1.expiry_date, t1.regal, t1.polka');

        $sort = Tools::getValue('sort_duplicates', 'ean');
        $way = Tools::getValue('way_duplicates', 'asc') === 'desc' ? 'DESC' : 'ASC';

        $allowedSortFields = [
            'ean' => 't1.ean', 'quantity' => 't1.quantity', 'expiry_date' => 't1.expiry_date',
            'receipt_date' => 't1.receipt_date', 'regal' => 't1.regal', 'polka' => 't1.polka', 'reference' => 'p.reference',
        ];

        if (isset($allowedSortFields[$sort])) {
            $sql->orderBy($allowedSortFields[$sort] . ' ' . pSQL($way) . ', t1.ean ASC, t1.expiry_date ASC');
        } else {
            $sql->orderBy('t1.ean ASC, t1.expiry_date ASC');
        }

        $duplicates = Db::getInstance()->executeS($sql);

        $final_duplicates = [];
        $class_to_use = 'row-even';
        $current_ean_date_key = null;

        foreach ($duplicates as $row) {
            $ean_date_key = $row['ean'] . '_' . $row['expiry_date'];
            if ($ean_date_key !== $current_ean_date_key) {
                $current_ean_date_key = $ean_date_key;
                $class_to_use = ($class_to_use == 'row-even') ? 'row-odd' : 'row-even';
            }
            $row['row_class'] = $class_to_use;
            $row['reduction'] = $this->getDiscountByDates($row['expiry_date'], $row['receipt_date'], $row['regal']);
            $row['product_url'] = '';
            $id_product = Product::getIdByEan13($row['ean']);
            if ($id_product) {
                $row['product_url'] = Context::getContext()->link->getAdminLink('AdminProducts') . '&updateproduct&id_product=' . (int)$id_product;
            }
            $final_duplicates[] = $row;
        }

        return $final_duplicates;
    }
    
    protected function exportNotFoundEansCsv()
    {
        $rows = Db::getInstance()->executeS('SELECT ean FROM `'._DB_PREFIX_.self::TABLE_NOTF.'` WHERE ean IS NOT NULL AND ean <> "" ORDER BY ean ASC');
        $filename = 'brakujace_eany_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['EAN'], ';');
        if ($rows) { foreach ($rows as $r) { fputcsv($out, [(string)$r['ean']], ';'); } }
        fclose($out);
    }
    
    private function getNotFoundProducts()
    {
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from(self::TABLE_NOTF);
        $allowedSortFields = ['ean' => 'ean', 'quantity' => 'quantity', 'expiry_date' => 'expiry_date', 'receipt_date' => 'receipt_date'];
        $sort = Tools::getValue('sort_not_found', 'expiry_date');
        $way = Tools::getValue('way_not_found', 'ASC');
        if (isset($allowedSortFields[$sort])) {
            $sql->orderBy($allowedSortFields[$sort] . ' ' . pSQL($way));
        } else {
            $sql->orderBy('expiry_date ASC');
        }
        return Db::getInstance()->executeS($sql);
    }
    
    private function saveSaleProductDetails($id_product, $ean, $expiry_date_str, $receipt_date_str, $regal, $polka)
    {
        $expiryDate = DateTime::createFromFormat('d.m.Y', $expiry_date_str);
        $receiptDate = DateTime::createFromFormat('d.m.Y', $receipt_date_str);
        $data = [
            'id_product' => (int)$id_product, 'ean' => pSQL($ean),
            'expiry_date' => $expiryDate ? $expiryDate->format('Y-m-d') : null,
            'receipt_date' => $receiptDate ? $receiptDate->format('Y-m-d') : null,
            'regal' => pSQL($regal), 'polka' => pSQL($polka),
        ];
        return Db::getInstance()->insert(self::TABLE_DETAILS, $data, false, true, Db::REPLACE);
    }

    private function updateSpecificPrice($id_product, $discount, $id_shop)
    {
        SpecificPrice::deleteByProductId((int)$id_product);
        if ($discount > 0) {
            $specificPrice = new SpecificPrice();
            $specificPrice->id_product = (int)$id_product;
            $specificPrice->id_shop = $id_shop;
            $specificPrice->id_currency = 0; $specificPrice->id_country = 0;
            $specificPrice->id_group = 0; $specificPrice->id_customer = 0;
            $specificPrice->price = -1; $specificPrice->from_quantity = 1;
            $specificPrice->reduction_type = 'percentage';
            $specificPrice->reduction = $discount / 100;
            $specificPrice->from = '0000-00-00 00:00:00';
            $specificPrice->to = '0000-00-00 00:00:00';
            $specificPrice->add();
        }
    }

    /**
     * @param array $importedData A chunk of tasks to process.
     * @return array List of valid SKUs processed in this chunk.
     */
    private function processSmartGroupedImport($importedData)
    {
        $mainSaleCategoryId = 45;
        $shortDateCategoryId = 180;
        $shortDateThreshold = (int)Configuration::get('WYPRZEDAZ_SHORT_DATE_DAYS', 14);
        $ignoreBinExpiry = (bool)Configuration::get('WYPRZEDAZ_IGNORE_BIN_EXPIRY');
        
        $context = Context::getContext();
        $id_shop = (int)$context->shop->id;
        $id_lang = (int)$context->language->id;
        $today = new DateTime();
        $today->setTime(0,0,0);
        $grouped = [];
        $validSkus = [];
        
        foreach ($importedData as $row) {
            $ean = trim($row['ean']);
            if (empty($ean)) continue;
            
            $date = DateTime::createFromFormat('d.m.Y', $row['expiry_date']);
            if (!$date) continue;

            $days = ($date >= $today) ? $today->diff($date)->days : 0;
            $waznosc_key = $date->format('d.m.Y');
            
            $dataToStore = [
                'stan' => 0, 'data_przyjecia' => $row['receipt_date'],
                'regal' => mb_strtoupper($row['regal'], 'UTF-8'), 'polka' => mb_strtoupper($row['polka'], 'UTF-8')
            ];
            
            if (trim($dataToStore['regal']) === 'KOSZ') {
                if (!isset($grouped[$ean]['bin'][$waznosc_key])) $grouped[$ean]['bin'][$waznosc_key] = $dataToStore;
                $grouped[$ean]['bin'][$waznosc_key]['stan'] += (int)$row['quantity'];
            }
            elseif ($days < $shortDateThreshold) {
                if (!isset($grouped[$ean]['short'][$waznosc_key])) $grouped[$ean]['short'][$waznosc_key] = $dataToStore;
                $grouped[$ean]['short'][$waznosc_key]['stan'] += (int)$row['quantity'];
            } else {
                if (!isset($grouped[$ean]['long'][$waznosc_key])) $grouped[$ean]['long'][$waznosc_key] = $dataToStore;
                $grouped[$ean]['long'][$waznosc_key]['stan'] += (int)$row['quantity'];
            }
        }
        
        foreach ($grouped as $ean => $set) {
            $id_product = Product::getIdByEan13($ean);
            if (!$id_product) continue;
            
            $originalProduct = new Product($id_product, true, $id_lang, $id_shop);
            
            $process_sets = ['bin', 'short', 'long'];
            foreach ($process_sets as $type) {
                if (!empty($set[$type])) {
                    foreach ($set[$type] as $data_waznosci => $data) {
                        $productIdToSave = 0;
                        $suma = $data['stan'];
                        $data_przyjecia = $data['data_przyjecia'];
                        $regal = $data['regal'];
                        $polka = $data['polka'];
                        
                        $saleProductSku = 'A_MAG_' . $ean . '_' . str_replace('.', '', $data_waznosci) . '_(' . $regal . '_' . $polka . ')';
                        $validSkus[] = $saleProductSku;
                        $idSaleProduct = Product::getIdByReference($saleProductSku);
                        
                        if ($type === 'bin') {
                            $targetCategoryId = $ignoreBinExpiry ? $mainSaleCategoryId : $shortDateCategoryId;
                        } elseif ($type === 'short') {
                            $targetCategoryId = $shortDateCategoryId;
                        } else {
                            $targetCategoryId = $mainSaleCategoryId;
                        }

                        $discount = $this->getDiscountByDates($data_waznosci, $data_przyjecia, $regal);
                        
                        $description = is_array($originalProduct->description) ? ($originalProduct->description[$id_lang] ?? '') : $originalProduct->description;
                        $description = preg_replace('/<p>DATA WAŻNOŚCI:.*<\/p>/i', '', $description);
                        $description = preg_replace('/<p><strong>UWAGA:<\/strong>.*<\/p>/i', '', $description);
                        $expiry_text = '<p>DATA WAŻNOŚCI: ' . $data_waznosci . '</p>';
                        $note = '';
                        if ($type === 'bin') $note = '<p><strong>UWAGA:</strong> Produkt nie podlega zwrotowi ze względu na uszkodzenie/koniec terminu ważności.</p>';
                        elseif ($type === 'short') $note = '<p><strong>UWAGA:</strong> Produkt nie podlega zwrotowi ze względu na krótką datę ważności.</p>';

                        if ($idSaleProduct) {
                            $productIdToSave = (int)$idSaleProduct;
                            $updProduct = new Product($productIdToSave, true, $id_lang, $id_shop);
                            $updProduct->minimal_quantity = 1; $updProduct->price = $originalProduct->price;
                            $updProduct->wholesale_price = $originalProduct->wholesale_price;
                            $updProduct->available_for_order = 1; $updProduct->show_price = 1; $updProduct->active = 1;
                            $updProduct->id_category_default = $targetCategoryId;
                            $updProduct->description = [];
                            $updProduct->description[$id_lang] = $description . $expiry_text . $note;
                            $updProduct->save();
                            // --- BEGIN: copy features from original (UPDATE) ---
                            $origFeatures = Product::getFeaturesStatic((int)$originalProduct->id);
                            if (!empty($origFeatures)) {
                                Db::getInstance()->delete('feature_product', 'id_product='.(int)$updProduct->id);
                                foreach ($origFeatures as $feat) {
                                    if (!empty($feat['id_feature']) && !empty($feat['id_feature_value'])) {
                                        Product::addFeatureProductImport(
                                            (int)$updProduct->id,
                                            (int)$feat['id_feature'],
                                            (int)$feat['id_feature_value']
                                        );
                                    }
                                }
                            }
                            // --- END: copy features (UPDATE) ---

                            $updProduct->setWsCategories([['id' => $targetCategoryId]]);
                            StockAvailable::setQuantity($productIdToSave, 0, (int)$suma, $id_shop);
                        } else {
                            $newProduct = $originalProduct->duplicateObject();
                            if (!Validate::isLoadedObject($newProduct)) continue;
                            
                            $productIdToSave = (int)$newProduct->id;
                            $newProduct->minimal_quantity = 1; $newProduct->price = $originalProduct->price;
                            $newProduct->wholesale_price = $originalProduct->wholesale_price;
                            $newProduct->id_shop_list = [$id_shop]; $newProduct->active = true; $newProduct->state = 1;
                            $newProduct->indexed = 0; $newProduct->id_tax_rules_group = $originalProduct->id_tax_rules_group;
                            $newProduct->available_for_order = 1; $newProduct->show_price = 1;
                            $newProduct->id_category_default = $targetCategoryId;
                            $newProduct->reference = $saleProductSku; $newProduct->date_add = $originalProduct->date_add;
                            $newProduct->description = [];
                            $newProduct->description[$id_lang] = $description . $expiry_text . $note;
                            $newProduct->save();
                            // --- BEGIN: copy features from original (NEW) ---
                            $origFeatures = Product::getFeaturesStatic((int)$originalProduct->id);
                            if (!empty($origFeatures)) {
                                Db::getInstance()->delete('feature_product', 'id_product='.(int)$newProduct->id);
                                foreach ($origFeatures as $feat) {
                                    if (!empty($feat['id_feature']) && !empty($feat['id_feature_value'])) {
                                        Product::addFeatureProductImport(
                                            (int)$newProduct->id,
                                            (int)$feat['id_feature'],
                                            (int)$feat['id_feature_value']
                                        );
                                    }
                                }
                            }
                            // --- END: copy features (NEW) ---

                            $newProduct->setWsCategories([['id' => $targetCategoryId]]);
                            StockAvailable::setQuantity($productIdToSave, 0, (int)$suma, $id_shop);
                            $this->copyProductImages($originalProduct, $newProduct);
                        }
                        
                        if ($productIdToSave > 0) {
                            $this->updateSpecificPrice($productIdToSave, $discount, $id_shop);
                            $this->saveSaleProductDetails($productIdToSave, $ean, $data_waznosci, $data_przyjecia, $regal, $polka);
                        }
                    }
                }
            }
        }
        
        return $validSkus;
    }
    
    private function copyProductImages(Product $originalProduct, Product $newProduct)
    {
        $images = $originalProduct->getImages((int)$this->context->language->id);
        if (empty($images)) { return true; }
        foreach ($images as $image_data) {
            $oldImage = new Image($image_data['id_image']);
            $formats = ['jpg', 'png', 'webp', 'jpeg'];
            $oldPath = null; $format = null;
            foreach ($formats as $f) {
                $tryPath = _PS_PROD_IMG_DIR_ . Image::getImgFolderStatic($oldImage->id) . $oldImage->id . '.' . $f;
                if (file_exists($tryPath)) { $oldPath = $tryPath; $format = $f; break; }
            }
            if (!$oldPath) { continue; }
            $newImage = new Image();
            $newImage->id_product = (int)$newProduct->id;
            $newImage->position = Image::getHighestPosition((int)$newProduct->id) + 1;
            $newImage->cover = (bool)$image_data['cover'];
            $newImage->image_format = $format;
            if (!$newImage->add()) { continue; }
            $imgFolder = _PS_PROD_IMG_DIR_ . Image::getImgFolderStatic($newImage->id);
            if (!is_dir($imgFolder)) { mkdir($imgFolder, 0777, true); }
            $newPath = $imgFolder . $newImage->id . '.' . $format;
            if (!ImageManager::resize($oldPath, $newPath, null, null, $format, true)) { continue; }
            $types = ImageType::getImagesTypes('products');
            foreach ($types as $type) {
                $thumbPath = $imgFolder . $newImage->id . '-' . $type['name'] . '.' . $format;
                ImageManager::resize($newPath, $thumbPath, (int)$type['width'], (int)$type['height'], $format);
            }
            $newImage->update();
        }
        return true;
    }
    
    private function getDiscountByDates($data_waznosci, $data_przyjecia, $regal) 
    {
        if (trim(mb_strtoupper($regal, 'UTF-8')) === 'KOSZ') {
            return (float)Configuration::get('WYPRZEDAZ_DISCOUNT_BIN');
        }

        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $dateWaznosci = DateTime::createFromFormat('d.m.Y', $data_waznosci);
        if (!$dateWaznosci) { return 0; }
        $daysToExpiry = ($dateWaznosci >= $today) ? $today->diff($dateWaznosci)->days : 0;
        
        $shortDateThreshold = (int)Configuration::get('WYPRZEDAZ_SHORT_DATE_DAYS', 14);
        
        $veryShortDiscount = (float)Configuration::get('WYPRZEDAZ_DISCOUNT_VERY_SHORT');

        if ($daysToExpiry < 7) {
            return $veryShortDiscount;
        } elseif ($daysToExpiry < $shortDateThreshold) { 
            return (float)Configuration::get('WYPRZEDAZ_DISCOUNT_SHORT'); 
        }

        $datePrzyjecia = DateTime::createFromFormat('d.m.Y', $data_przyjecia);
        if (!$datePrzyjecia) { return 0; }
        $daysSinceReceipt = ($datePrzyjecia <= $today) ? $datePrzyjecia->diff($today)->days : 0;
        if (Configuration::get('WYPRZEDAZ_ENABLE_OVER90_LONGEXP') && $daysSinceReceipt > 90 && $daysToExpiry >= 180) {
            return (float)Configuration::get('WYPRZEDAZ_DISCOUNT_OVER90_LONGEXP');
        } elseif ($daysSinceReceipt <= 30) {
            return (float)Configuration::get('WYPRZEDAZ_DISCOUNT_30');
        } elseif ($daysSinceReceipt <= 90) { 
            return (float)Configuration::get('WYPRZEDAZ_DISCOUNT_90');
        } else {
            return (float)Configuration::get('WYPRZEDAZ_DISCOUNT_OVER');
        }
    }
    
    protected function logImportHistory($filename, $rowsTotal, $rowsInDb, $rowsNotFound)
    {
        $data = [
            'date_add' => date('Y-m-d H:i:s'), 'filename' => pSQL((string)$filename),
            'rows_total' => (int)$rowsTotal, 'rows_in_db' => (int)$rowsInDb, 'rows_not_found' => (int)$rowsNotFound,
            'id_shop' => (int)$this->context->shop->id, 'id_employee' => (int)($this->context->employee ? $this->context->employee->id : 0),
        ];
        Db::getInstance()->insert(self::TABLE_HISTORY, $data);
    }

    protected function getImportHistory($limit = 100)
    {
        $limit = (int)$limit;
        return Db::getInstance()->executeS('SELECT * FROM `'._DB_PREFIX_.self::TABLE_HISTORY.'` ORDER BY date_add DESC LIMIT '.$limit);
    }
    
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getLocalPath() . 'views/css/back.css');
        $this->addJS($this->module->getLocalPath() . 'views/js/back.js');
    }

    /* ===================== AJAX IMPORT ===================== */

    protected function ajaxJson($payload, $code = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($payload);
        exit;
    }
    
    public function ajaxProcessCsvImportStart()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if (empty($_FILES['csv_file']['tmp_name'])) throw new \Exception('Brak pliku CSV.');
            
            $sid = uniqid('wy_', true);
            $dir = $this->varDir();
            $dest = $dir.'/upload_'.$sid.'.csv';
            if (!@move_uploaded_file($_FILES['csv_file']['tmp_name'], $dest)) {
                if (!@copy($_FILES['csv_file']['tmp_name'], $dest)) {
                    throw new \Exception('Nie można zapisać pliku na serwerze.');
                }
            }
            $fh = fopen($dest,'r'); if(!$fh) throw new \Exception('Nie można otworzyć pliku.');
            $header = null; $total = 0;
            while(($line=fgets($fh))!==false){ if(trim($line)==='') continue; $total++; if($header===null) $header=$line; }
            fclose($fh);
            $total_rows = ($total > 1) ? $total - 1 : 0;

            $delimiter = (strpos($header,';')!==false) ? ';' : ',';
            $fh = fopen($dest,'r'); $off=0; while(($line=fgets($fh))!==false){ if(trim($line)===''){ $off+=strlen($line); continue; } $off+=strlen($line); break; } fclose($fh);
            $batch = (int)Configuration::get('WYPRZEDAZ_IMPORT_BATCH', 500);
            $chunks = $total_rows > 0 ? (int)ceil($total_rows / max(1, $batch)) : 0;
            
            $state = [
                'file'=>$dest,'session_id'=>$sid,'delimiter'=>$delimiter,'offset'=>$off,'line'=>0,
                'total_rows'=>$total_rows,'processed'=>0,'in_db'=>0,'not_found'=>0,
                'chunks_total'=>$chunks,'chunks_done'=>0,'batch'=>$batch,'finished'=>false,
                'all_valid_skus' => [],
                'original_filename' => $_FILES['csv_file']['name'],
            ];
            
            \Db::getInstance()->execute('TRUNCATE TABLE `'. _DB_PREFIX_ .'wyprzedaz_csv_staging`');
            \Db::getInstance()->execute('TRUNCATE TABLE `'. _DB_PREFIX_ .'wyprzedaz_finalize_tasks`');
            \Db::getInstance()->execute('TRUNCATE TABLE `'. _DB_PREFIX_ . self::TABLE_DUPES .'`');
            \Db::getInstance()->execute('TRUNCATE TABLE `'. _DB_PREFIX_ . self::TABLE_NOTF .'`');
            
            $this->writeSession($sid, $state);
            @file_put_contents($this->varDir().'/last_session.txt', $sid);
            echo json_encode(['ok'=>true,'session_id'=>$sid,'total_rows'=>$state['total_rows'],'chunks_total'=>$chunks,'batch'=>$batch]);
        } catch (\Throwable $e) {
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
        }
        exit;
    }

    public function ajaxProcessCsvImportChunk()
{
    header('Content-Type: application/json; charset=utf-8');
    try {
        $sid = $this->sanitizeSid(Tools::getValue('session_id'));
        if (!$sid) {
            $sid = @file_get_contents($this->varDir().'/last_session.txt');
            $sid = $sid ? $this->sanitizeSid($sid) : '';
        }
        $st = $this->readSession($sid);
        if (!$st) {
            throw new \Exception('Sesja nie istnieje.');
        }

        $fh = fopen($st['file'], 'r');
        if (!$fh) {
            throw new \Exception('Nie można odczytać pliku.');
        }
        if ($st['offset'] > 0) {
            fseek($fh, $st['offset']);
        }

        // Mapa kolumn po indeksach – jak dotychczas
        $map = [
            'EAN' => 0, 'KOD' => 1, 'Regał' => 2, 'Półka' => 3,
            'DATA PRZYJĘCIA' => 4, 'DATA WAŻNOŚCI' => 5, 'STAN' => 6
        ];

        $vals = [];
        $rows_read = 0;   // <- LICZYMY KAŻDĄ linię (nawet bez EAN)
        $inDb = 0;
        $nf = 0;
        $insert_batch_size = 100;

        for ($i = 0; $i < (int)$st['batch']; $i++) {
            $line = fgets($fh);
            if ($line === false) {
                break;
            }
            if (trim($line) === '') {
                $st['offset'] = ftell($fh);
                continue;
            }

            // linia jest realna – zwiększamy liczniki postępu
            $st['line']++;
            $st['offset'] = ftell($fh);
            $rows_read++;

            $data = str_getcsv($line, $st['delimiter']);

            // zabezpieczenia na zbyt krótkie wiersze
            $ean = isset($data[$map['EAN']]) ? trim($data[$map['EAN']]) : '';
            if ($ean === '') {
                // brak EAN – przetworzone jako linia, ale bez insertu
                continue;
            }

            $qty = (int)($data[$map['STAN']] ?? 0);
            $rd  = $data[$map['DATA PRZYJĘCIA']] ?? '';
            $ed  = $data[$map['DATA WAŻNOŚCI']] ?? '';
            $reg = isset($data[$map['Regał']]) ? Tools::strtoupper(trim($data[$map['Regał']])) : '';
            $pol = isset($data[$map['Półka']]) ? Tools::strtoupper(trim($data[$map['Półka']])) : '';

            $ED = DateTime::createFromFormat('d.m.Y', $ed);
            if (!$ED) $ED = DateTime::createFromFormat('Y-m-d', $ed);
            $RD = DateTime::createFromFormat('d.m.Y', $rd);
            if (!$RD) $RD = DateTime::createFromFormat('Y-m-d', $rd);

            $vals[] = '("'.pSQL($sid).'", "'.pSQL($ean).'", '.(int)$qty.', "'
                . ($RD ? $RD->format('Y-m-d') : '0000-00-00') . '", "'
                . ($ED ? $ED->format('Y-m-d') : '0000-00-00') . '", "'
                . pSQL($reg) . '", "'. pSQL($pol) .'")';

            static $cache = [];
            if (!array_key_exists($ean, $cache)) {
                $cache[$ean] = (bool)Product::getIdByEan13($ean);
            }
            if ($cache[$ean]) {
                $inDb++;
            } else {
                $nf++;
            }

            if (count($vals) >= $insert_batch_size) {
                \Db::getInstance()->execute(
                    'INSERT INTO `'.$this->getStagingTable().'` (`session_id`,`ean`,`quantity`,`receipt_date`,`expiry_date`,`regal`,`polka`) VALUES '
                    . implode(',', $vals)
                );
                $vals = [];
            }
        }

        if (!empty($vals)) {
            \Db::getInstance()->execute(
                'INSERT INTO `'.$this->getStagingTable().'` (`session_id`,`ean`,`quantity`,`receipt_date`,`expiry_date`,`regal`,`polka`) VALUES '
                . implode(',', $vals)
            );
        }
        fclose($fh);

        // aktualizacja stanu sesji
        $st['processed']  += $rows_read;         // <- różnica względem poprzedniej wersji
        $st['in_db']      += $inDb;
        $st['not_found']  += $nf;
        if ($rows_read > 0) {
            $st['chunks_done'] += 1;
        }

        // Fallback: jeśli doszliśmy do końca pliku, wymuś zakończenie stagingu
        clearstatcache(true, $st['file']);
        $fileSize = @filesize($st['file']);
        $atEnd    = ($fileSize && $st['offset'] >= $fileSize);

        $done = (
            ($st['total_rows'] > 0 && $st['processed'] >= $st['total_rows'])
            || $atEnd
        );
        $st['finished'] = $done;

        $this->writeSession($sid, $st);

        $payload = [
            'ok'          => true,
            'processed'   => $st['processed'],
            'in_db'       => $st['in_db'],
            'not_found'   => $st['not_found'],
            'chunks_done' => $st['chunks_done'],
            'chunks_total'=> $st['chunks_total']
        ];

        if ($done) {
            $payload['done']  = true;
            $payload['stage'] = 'staging_complete';
        } else {
            $payload['done']  = false;
        }

        echo json_encode($payload);
    } catch (\Throwable $e) {
        echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    }
    exit;
}


    public function ajaxProcessCsvImportFinalizeStart()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $sid = $this->sanitizeSid(Tools::getValue('session_id'));
            $st  = $this->readSession($sid);
            if(!$st) throw new \Exception('Brak sesji.');
            
            $sql = new DbQuery();
            $sql->select('ean, quantity, receipt_date, expiry_date, regal, polka');
            $sql->from('wyprzedaz_csv_staging');
            $sql->where('session_id="'.pSQL($sid).'"');
            $rows = \Db::getInstance()->executeS($sql) ?: [];

            $byKey=[]; $notFound=0;
            $notFoundProductsData = []; 
            
            foreach($rows as $r){
                if(!Product::getIdByEan13($r['ean'])){ 
                    $notFound++; 
                    $notFoundProductsData[] = $r;
                    continue; 
                }
                $key=$r['ean'].'||'.$r['expiry_date'];
                $loc=Tools::strtoupper(trim($r['regal'])).'|'.Tools::strtoupper(trim($r['polka']));
                if(!isset($byKey[$key])) $byKey[$key]=[];
                if(!isset($byKey[$key][$loc])) $byKey[$key][$loc]=$r;
                else $byKey[$key][$loc]['quantity'] += (int)$r['quantity'];
            }

            \Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.self::TABLE_DUPES.'`');
            \Db::getInstance()->execute('DELETE FROM `'.$this->getTasksTable().'` WHERE session_id="'.pSQL($sid).'"');
            
            $valsDup=[]; $valsTask=[];

            foreach($byKey as $k => $locs) {
                $entries = array_values($locs);
                if (empty($entries)) {
                    continue;
                }

                if (count($entries) > 1) { 
                    foreach ($entries as $entry) {
                        $valsDup[] = '("'.pSQL($entry['ean']).'", '.(int)$entry['quantity'].', "'.pSQL($entry['receipt_date']).'", "'.pSQL($entry['expiry_date']).'", "'.pSQL($entry['regal']).'", "'.pSQL($entry['polka']).'")';
                    }
                    $total_quantity = 0;
                    foreach ($entries as $entry) {
                        $total_quantity += (int)$entry['quantity'];
                    }
                    $representative_entry = $entries[0];
                    $rec = $representative_entry;
                    $valsTask[] = '("'.pSQL($sid).'","'.pSQL($rec['ean']).'",'.(int)$total_quantity.',"'.pSQL($rec['receipt_date']).'","'.pSQL($rec['expiry_date']).'","'.pSQL($rec['regal']).'","'.pSQL($rec['polka']).'",0)';

                } else {
                    $single_entry = $entries[0];
                    $rec = $single_entry;
                    $valsTask[] = '("'.pSQL($sid).'","'.pSQL($rec['ean']).'",'.(int)$rec['quantity'].',"'.pSQL($rec['receipt_date']).'","'.pSQL($rec['expiry_date']).'","'.pSQL($rec['regal']).'","'.pSQL($rec['polka']).'",0)';
                }
            }
            
            if(!empty($valsDup)){
                \Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.self::TABLE_DUPES.'` (`ean`,`quantity`,`receipt_date`,`expiry_date`,`regal`,`polka`) VALUES '.implode(',', $valsDup));
            }
            if(!empty($valsTask)){
                \Db::getInstance()->execute('INSERT INTO `'.$this->getTasksTable().'` (`session_id`,`ean`,`quantity`,`receipt_date`,`expiry_date`,`regal`,`polka`,`status`) VALUES '.implode(',', $valsTask));
            }
            
            \Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.self::TABLE_NOTF.'`');
            if(!empty($notFoundProductsData)){
                $now = date('Y-m-d H:i:s');
                foreach ($notFoundProductsData as $p) {
                    \Db::getInstance()->insert(self::TABLE_NOTF, [
                        'ean' => pSQL($p['ean']), 'quantity' => (int)$p['quantity'],
                        'receipt_date' => pSQL($p['receipt_date']), 'expiry_date' => pSQL($p['expiry_date']),
                        'regal' => pSQL($p['regal']), 'polka' => pSQL($p['polka']), 'date_add' => $now,
                    ], false, true, Db::INSERT, true);
                }
            }

            $totalTasks = (int)\Db::getInstance()->getValue('SELECT COUNT(*) FROM `'.$this->getTasksTable().'` WHERE session_id="'.pSQL($sid).'"');
            $st['finalize_total']=$totalTasks; $st['finalize_done']=0; $st['created_or_updated']=0; $st['not_found']=$notFound; $this->writeSession($sid,$st);
            echo json_encode(['ok'=>true,'finalize_total'=>$totalTasks,'not_found'=>$notFound]);
        } catch (\Throwable $e) { 
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); 
        }
        exit;
    }

    public function ajaxProcessCsvImportFinalizeChunk()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $sid = $this->sanitizeSid(Tools::getValue('session_id'));
            $batch = (int)Configuration::get('WYPRZEDAZ_FINALIZE_BATCH', 100);
            $rows = \Db::getInstance()->executeS('SELECT id, ean, quantity, receipt_date, expiry_date, regal, polka FROM `'.$this->getTasksTable().'` WHERE session_id="'.pSQL($sid).'" AND status=0 LIMIT '.(int)$batch) ?: [];
            if (empty($rows)) {
                $done = (int)\Db::getInstance()->getValue('SELECT COUNT(*) FROM `'.$this->getTasksTable().'` WHERE session_id="'.pSQL($sid).'" AND status=1');
                $total= (int)\Db::getInstance()->getValue('SELECT COUNT(*) FROM `'.$this->getTasksTable().'` WHERE session_id="'.pSQL($sid).'"');
                echo json_encode(['ok'=>true,'done'=>true,'finalize_done'=>$done,'finalize_total'=>$total]); exit;
            }
            $pack=[];
            foreach($rows as $r){
                $pack[]=[
                    'ean'=>$r['ean'], 'quantity'=>(int)$r['quantity'],
                    'receipt_date'=>($r['receipt_date'] && $r['receipt_date']!='0000-00-00')?date('d.m.Y',strtotime($r['receipt_date'])):'',
                    'expiry_date'=>($r['expiry_date'] && $r['expiry_date']!='0000-00-00')?date('d.m.Y',strtotime($r['expiry_date'])):'',
                    'regal'=>$r['regal'], 'polka'=>$r['polka'],
                ];
            }
            
            $chunkValidSkus = $this->processSmartGroupedImport($pack);
            $st = $this->readSession($sid);
            if (isset($st['all_valid_skus']) && is_array($st['all_valid_skus'])) {
                $st['all_valid_skus'] = array_merge($st['all_valid_skus'], $chunkValidSkus);
            } else {
                $st['all_valid_skus'] = $chunkValidSkus;
            }
            $this->writeSession($sid, $st);

            $ids = implode(',', array_map('intval', array_column($rows,'id')));
            \Db::getInstance()->execute('UPDATE `'.$this->getTasksTable().'` SET status=1 WHERE id IN ('.$ids.')');
            $done = (int)\Db::getInstance()->getValue('SELECT COUNT(*) FROM `'.$this->getTasksTable().'` WHERE session_id="'.pSQL($sid).'" AND status=1');
            $total= (int)\Db::getInstance()->getValue('SELECT COUNT(*) FROM `'.$this->getTasksTable().'` WHERE session_id="'.pSQL($sid).'"');
            
            $notFoundCount = $st && isset($st['not_found']) ? (int)$st['not_found'] : 0;
            echo json_encode(['ok'=>true,'done'=>($done>=$total),'finalize_done'=>$done,'finalize_total'=>$total,'created_or_updated'=>$done,'not_found'=>$notFoundCount]);
        } catch (\Throwable $e) { echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
        exit;
    }

    public function ajaxProcessCsvImportFinalizeFinish()
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $sid = $this->sanitizeSid(Tools::getValue('session_id'));
            $st = $this->readSession($sid);
            
            if (isset($st['all_valid_skus']) && is_array($st['all_valid_skus'])) {
                $allMagProducts = \Db::getInstance()->executeS("SELECT p.id_product, p.reference FROM `"._DB_PREFIX_."product` p WHERE p.reference LIKE 'A_MAG_%'");
                $id_shop = (int)$this->context->shop->id;
                foreach ($allMagProducts as $row) {
                    if (!in_array($row['reference'], $st['all_valid_skus'])) {
                        $p = new Product($row['id_product'], false, null, $id_shop);
                        if (Validate::isLoadedObject($p)) {
                            StockAvailable::setQuantity((int)$row['id_product'], 0, 0, $id_shop);
                            $p->active = 0;
                            $p->save();
                        }
                    }
                }
            }

            $created = (int)\Db::getInstance()->getValue('SELECT COUNT(*) FROM `'.$this->getTasksTable().'` WHERE session_id="'.pSQL($sid).'" AND status=1');
            $not_found = $st && isset($st['not_found']) ? (int)$st['not_found'] : 0;
            $duplicates_count = (int)\Db::getInstance()->getValue('SELECT COUNT(DISTINCT ean) FROM `'._DB_PREFIX_.self::TABLE_DUPES.'`');
            
            $this->logImportHistory(
                $st['original_filename'] ?? basename($st['file']),
                $st['total_rows'] ?? 0,
                $st['in_db'] ?? 0,
                $st['not_found'] ?? 0
            );

            $shortDateThreshold = (int)Configuration::get('WYPRZEDAZ_SHORT_DATE_DAYS', 14);
            $sql_short = new DbQuery();
            $sql_short->select('COUNT(p.id_product)')->from('product', 'p');
            $sql_short->leftJoin(self::TABLE_DETAILS, 'wpd', 'p.id_product = wpd.id_product');
            $sql_short->leftJoin('stock_available', 'sa', 'sa.id_product = p.id_product AND sa.id_product_attribute = 0 AND sa.id_shop = '.(int)$this->context->shop->id);
            $sql_short->where("p.reference LIKE 'A_MAG_%' AND wpd.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL " . $shortDateThreshold . " DAY) AND sa.quantity > 0");
            $short_date_total = (int)Db::getInstance()->getValue($sql_short);

            \Db::getInstance()->execute('DELETE FROM `'.$this->getTasksTable().'` WHERE session_id="'.pSQL($sid).'"');
            $this->removeSession($sid);

            echo json_encode([
                'ok' => true, 'created_or_updated' => $created, 'not_found' => $not_found,
                'duplicates_count' => $duplicates_count, 'short_date_total' => $short_date_total
            ]);

        } catch (\Throwable $e) { 
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); 
        }
        exit;
    }

    /**
     * Podgląd importu z CSV / tabeli tymczasowej.
     * Minimalny stub – zwraca pustą tablicę, by nie powodować 500.
     */
    protected function getPreviewFromCsv(): array
    {
        return [];
    }
}