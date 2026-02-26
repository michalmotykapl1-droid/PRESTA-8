<?php
/**
 * Kontroler: AdminModulExtraController
 * Wersja: 1.5 (Obsługa rozszerzonej struktury: direct + smaller)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/ExtraSearchRepository.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/ExtraStockManager.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/ExtraHtmlGenerator.php';
require_once _PS_MODULE_DIR_ . 'modulzamowien/classes/AlternativeProductFinder.php';

class AdminModulExtraController extends ModuleAdminController
{
    private $searchRepository;
    private $stockManager;
    private $alternativeFinder;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        
        $this->searchRepository = new ExtraSearchRepository();
        $this->stockManager = new ExtraStockManager();
        $this->alternativeFinder = new AlternativeProductFinder();
    }

    public function postProcess()
    {
        if (Tools::getValue('action') == 'addExtraItem') $this->ajaxProcessAddExtraItem();
        if (Tools::getValue('action') == 'removeExtraItem') $this->ajaxProcessRemoveExtraItem();
        if (Tools::getValue('action') == 'clearExtraItems') $this->ajaxProcessClearExtraItems();
        if (Tools::getValue('action') == 'getExtraTable') $this->ajaxProcessGetExtraTable();
        if (Tools::getValue('action') == 'searchProduct') $this->ajaxProcessSearchProduct();
    }

    public function ajaxProcessSearchProduct()
    {
        $query = trim(Tools::getValue('q'));
        if (strlen($query) < 3) die(json_encode([]));

        // 1. Dokładne
        $results = $this->searchRepository->searchAndFormat($query, $this->context);
        
        // 2. Alternatywne (jeśli brak dokładnych)
        if (empty($results)) {
            $altResult = $this->alternativeFinder->findAlternatives($query, $this->context);
            
            // Sprawdzamy czy cokolwiek znaleziono
            if (!empty($altResult['direct']) || !empty($altResult['smaller'])) {
                die(json_encode([
                    'alternatives_found' => true,
                    'original_query' => $query,
                    'source_info' => $altResult['source_info'],
                    'direct_data' => $altResult['direct'],
                    'smaller_data' => $altResult['smaller']
                ]));
            }
        }
        
        die(json_encode($results));
    }

    public function ajaxProcessAddExtraItem()
    {
        $ean = trim(Tools::getValue('ean'));
        $name = trim(Tools::getValue('name'));
        $qty = (int)Tools::getValue('qty');
        $sku = trim(Tools::getValue('sku'));
        $force = (int)Tools::getValue('force');
        $id_shop = (int)$this->context->shop->id ?: 1;

        if ($qty <= 0) die(json_encode(['success' => false]));

        $result = $this->stockManager->addItem($ean, $name, $qty, $sku, $id_shop, $force);
        die(json_encode($result));
    }

    public function ajaxProcessRemoveExtraItem()
    {
        $id_extra = (int)Tools::getValue('id_extra');
        $id_shop = (int)$this->context->shop->id ?: 1;
        $result = $this->stockManager->removeItem($id_extra, $id_shop);
        die(json_encode($result));
    }

    public function ajaxProcessClearExtraItems()
    {
        $this->stockManager->clearItems($this->context);
        die(json_encode(['success' => true]));
    }

    public function ajaxProcessGetExtraTable()
    {
        $html = ExtraHtmlGenerator::generateRows();
        die(json_encode(['html' => $html]));
    }
}