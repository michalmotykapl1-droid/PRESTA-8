<?php
/**
 * Ścieżka: /modules/wyprzedazpro/controllers/admin/AdminWyprzedazProController.php
 * Wersja: REFACTORED + HARD DELETE (KOSZ)
 */

require_once _PS_MODULE_DIR_ . 'wyprzedazpro/classes/WmsConfiguration.php';
require_once _PS_MODULE_DIR_ . 'wyprzedazpro/classes/WmsProductRepository.php';
require_once _PS_MODULE_DIR_ . 'wyprzedazpro/classes/WmsImportManager.php';
require_once _PS_MODULE_DIR_ . 'wyprzedazpro/classes/WmsExportManager.php';
require_once _PS_MODULE_DIR_ . 'wyprzedazpro/classes/WmsSynchronizer.php';

class AdminWyprzedazProController extends ModuleAdminController
{
    private $config;
    private $repository;
    private $importManager;
    private $exportManager;
    private $synchronizer;

    public function __construct()
    {
        $this->bootstrap = true;
        $this->display = 'view';
        parent::__construct();
        $this->meta_title = $this->l('Zarządzanie Wyprzedażą PRO (WMS)');
        
        $this->config = new WmsConfiguration();
        $this->repository = new WmsProductRepository();
        $this->importManager = new WmsImportManager();
        $this->exportManager = new WmsExportManager();
        $this->synchronizer = new WmsSynchronizer();
    }

    public function initContent()
    {
        // --- 0. OPERACJE NA KOSZU (Delete) ---

        // A. MASOWE USUWANIE (HARD): WMS + PRESTA
        if (Tools::isSubmit('delete_all_bin_full')) {
            // 1. Pobierz wszystkie produkty z kosza
            $items = $this->repository->getBinProducts((int)$this->context->language->id, (int)$this->context->shop->id);
            $deleted_count = 0;
            
            foreach ($items as $item) {
                // Usuwamy fizycznie produkt z Presty po jego ID
                if (!empty($item['id_product'])) {
                    $product = new Product((int)$item['id_product']);
                    if (Validate::isLoadedObject($product)) {
                        $product->delete(); // To usuwa produkt z tabel ps_product, ps_product_lang itd.
                        $deleted_count++;
                    }
                }
            }
            
            // 2. Czyścimy tabelę WMS
            $this->repository->deleteAllBinProducts();
            
            $this->confirmations[] = "Usunięto fizycznie $deleted_count produktów ze sklepu oraz wyczyszczono wpisy WMS w lokalizacji KOSZ.";
            Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=1');
        }

        // B. MASOWE USUWANIE (SOFT): Tylko WMS
        if (Tools::isSubmit('delete_all_bin')) {
            $this->repository->deleteAllBinProducts();
            $this->confirmations[] = "Wyczyszczono wpisy WMS z lokalizacji KOSZ (Produkty w sklepie pozostały).";
            Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=1');
        }

        // C. POJEDYNCZE USUWANIE (HARD): WMS + PRESTA
        if (Tools::isSubmit('delete_bin_item_full') && Tools::getValue('id_product')) {
            $id_del = (int)Tools::getValue('id_product');
            
            // Usuń z Presty (po ID)
            $product = new Product($id_del);
            if (Validate::isLoadedObject($product)) {
                $product->delete();
            }
            
            // Usuń z WMS (po ID)
            $this->repository->deleteBinProduct($id_del);
            
            $this->confirmations[] = "Produkt (ID: $id_del) został usunięty ze Sklepu i z WMS.";
            Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=1');
        }

        // D. POJEDYNCZE USUWANIE (SOFT): Tylko WMS
        if (Tools::isSubmit('delete_bin_item') && Tools::getValue('id_product')) {
            $id_del = (int)Tools::getValue('id_product');
            $this->repository->deleteBinProduct($id_del);
            $this->confirmations[] = "Rekord z lokalizacji KOSZ został usunięty (Produkt w sklepie pozostał).";
            Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token . '&conf=1');
        }

        // --- KONIEC OPERACJI NA KOSZU ---

        // 1. Synchronizacja WMS -> Presta
        if (Tools::isSubmit('submitSyncWmsToPresta')) {
            $res = $this->synchronizer->synchronizeAll((int)$this->context->shop->id);
            if ($res['success']) {
                $this->confirmations[] = "Sukces! Zsynchronizowano stany dla " . $res['count'] . " produktów.";
            }
        }

        // 2. Eksporty CSV
        if (Tools::getValue('export_current_wms') == '1') {
            $this->exportManager->exportCurrentWmsState();
        }
        if (Tools::getValue('export_not_found_csv') == '1' || Tools::getValue('export_not_found') == '1') {
            $this->exportManager->exportNotFoundEansCsv();
        }

        // 3. Konfiguracja
        if (Tools::isSubmit('submitWyprzedazSettings')) {
            $this->config->processSettings();
            $this->confirmations[] = $this->l('Ustawienia rabatów zostały zapisane.');
        }

        // 4. Pobieranie Danych
        $id_shop = (int)$this->context->shop->id;
        $id_lang = (int)$this->context->language->id;
        
        $counters = $this->repository->getCounters($id_shop);
        $date_filter = Tools::getValue('date_filter', 'all');
        $sale_products = $this->repository->getSaleProducts($date_filter, $id_lang, $id_shop);
        $import_history = $this->repository->getImportHistory();
        $duplicates = $this->repository->getDuplicates($id_lang);
        $not_found = $this->repository->getNotFoundProducts();
        
        // Produkty w KOSZU
        $bin_products = $this->repository->getBinProducts($id_lang, $id_shop);

        $this->context->smarty->assign([
            'ajax_url' => $this->context->link->getAdminLink('AdminWyprzedazPro'),
            
            'bin_products' => $bin_products,
            'bin_products_count' => count($bin_products),

            'sale_products' => $sale_products,
            'sale_products_count' => count($sale_products),
            'duplicated_products' => $duplicates,
            'duplicate_products_count' => count(array_unique(array_column($duplicates, 'ean'))),
            'not_found_products' => $not_found,
            'not_found_products_count' => count($not_found),
            'import_history' => $import_history,
            'expired_products_count' => $counters['expired'],
            'short_date_products_count' => $counters['short'],
            'products_30_days_count' => $counters['30_days'],
            'products_31_90_days_count' => $counters['31_90_days'],
            'products_over_90_days_count' => $counters['over_90_days'],
            
            // Konfiguracja do widoku
            'WYPRZEDAZPRO_DISCOUNT_SHORT' => Configuration::get('WYPRZEDAZPRO_DISCOUNT_SHORT'),
            'WYPRZEDAZPRO_DISCOUNT_30'    => Configuration::get('WYPRZEDAZPRO_DISCOUNT_30'),
            'WYPRZEDAZPRO_DISCOUNT_90'    => Configuration::get('WYPRZEDAZPRO_DISCOUNT_90'),
            'WYPRZEDAZPRO_DISCOUNT_OVER'  => Configuration::get('WYPRZEDAZPRO_DISCOUNT_OVER'),
            'WYPRZEDAZPRO_SHORT_DATE_DAYS' => Configuration::get('WYPRZEDAZPRO_SHORT_DATE_DAYS'),
            'WYPRZEDAZPRO_DISCOUNT_VERY_SHORT' => Configuration::get('WYPRZEDAZPRO_DISCOUNT_VERY_SHORT'),
            'WYPRZEDAZPRO_DISCOUNT_BIN' => Configuration::get('WYPRZEDAZPRO_DISCOUNT_BIN'),
            'WYPRZEDAZPRO_IGNORE_BIN_EXPIRY' => Configuration::get('WYPRZEDAZPRO_IGNORE_BIN_EXPIRY'),
            'WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP' => Configuration::get('WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP'),
            'WYPRZEDAZPRO_DISCOUNT_OVER90_LONGEXP' => Configuration::get('WYPRZEDAZPRO_DISCOUNT_OVER90_LONGEXP'),
            
            // Parametry URL
            'sort' => Tools::getValue('sort'),
            'way' => Tools::getValue('way'),
            'sort_not_found' => Tools::getValue('sort_not_found'),
            'way_not_found' => Tools::getValue('way_not_found'),
            'sort_duplicates' => Tools::getValue('sort_duplicates'), 
            'way_duplicates' => Tools::getValue('way_duplicates'),   
            'link' => $this->context->link,
            'date_filter' => $date_filter,
            'current' => self::$currentIndex,
            'token' => $this->token,
        ]);

        parent::initContent();
        $this->setTemplate('configure.tpl');
    }

    // --- AJAX PROXY METHODS ---
    
    public function ajaxProcessCsvImportStart() {
        $this->ajaxJson($this->importManager->startImport($_FILES['csv_file']));
    }

    public function ajaxProcessCsvImportChunk() {
        $this->ajaxJson($this->importManager->processChunk(Tools::getValue('session_id')));
    }

    public function ajaxProcessCsvImportFinalizeStart() {
        $this->ajaxJson($this->importManager->finalizeStart(Tools::getValue('session_id')));
    }

    public function ajaxProcessCsvImportFinalizeChunk() {
        $this->ajaxJson($this->importManager->finalizeChunk(Tools::getValue('session_id'), (int)$this->context->shop->id));
    }

    public function ajaxProcessCsvImportFinalizeFinish() {
        $this->ajaxJson($this->importManager->finalizeFinish(Tools::getValue('session_id'), (int)$this->context->shop->id));
    }

    protected function ajaxJson($payload, $code = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($payload);
        exit;
    }
    
    public function setMedia($isNewTheme = false) {
        parent::setMedia($isNewTheme);
        $this->addCSS($this->module->getPathUri() . 'views/css/back.css?v='.time());
        $this->addJS($this->module->getPathUri() . 'views/js/back.js?v='.time());
    }
}