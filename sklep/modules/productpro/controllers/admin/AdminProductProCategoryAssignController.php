<?php
/**
 * 2007-2023 PrestaShop
 *
 * Kontroler strony do przypisywania produktów do kategorii na podstawie wagi.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Dołączamy plik z nową klasą serwisową
require_once _PS_MODULE_DIR_ . 'productpro/services/ProductProCategoryAssignService.php';

class AdminProductProCategoryAssignController extends ModuleAdminController
{
    private $productProCategoryAssignService;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        $this->productProCategoryAssignService = new ProductProCategoryAssignService($this->module);
        
        $this->list_id = 'product_category_assign';
        $this->toolbar_title[0] = $this->l('Przypisywanie produktów do kategorii wagowych');
    }

    /**
     * Przetwarzanie akcji POST (np. przypisania produktów do kategorii).
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAssignProductsToCategory')) {
            $categoryType = Tools::getValue('category_type');
            $selectedProductIds = Tools::getValue('product_box');
            
            if (empty($selectedProductIds)) {
                $this->warnings[] = $this->l('Nie wybrano żadnych produktów do przypisania.');
                return;
            }

            $result = $this->productProCategoryAssignService->assignProductsToCategory($categoryType, $selectedProductIds);

            if ($result['success']) {
                $this->confirmations[] = $result['message'];
            } else {
                $this->errors[] = $result['message'];
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminProductProCategoryAssign'));
        }
    }
    
    /**
     * Inicjalizacja i renderowanie widoku.
     */
    public function initContent()
    {
        parent::initContent();

        // Pobieramy produkty dla każdej z kategorii wagowych, używając nowej metody serwisu
        $products5to10kg = $this->productProCategoryAssignService->getProductsForConfiguredRange('5_10kg');
        $products20to25kg = $this->productProCategoryAssignService->getProductsForConfiguredRange('20_25kg');

        $this->context->smarty->assign([
            'module_name'                => $this->module->displayName,
            'products_5_10kg'            => $products5to10kg,
            'products_20_25kg'           => $products20to25kg,
            'products_5_10kg_count'      => count($products5to10kg),
            'products_20_25kg_count'     => count($products20to25kg),
            
            // Pobieramy nazwy kategorii z konfiguracji do wyświetlenia na przyciskach
            'category_name_5_10kg'       => Configuration::get('PRODUCTPRO_CATEGORY_5_10KG_NAME'),
            'category_name_20_25kg'      => Configuration::get('PRODUCTPRO_CATEGORY_20_25KG_NAME'),
            
            'current_url'                => $this->context->link->getAdminLink('AdminProductProCategoryAssign'),
        ]);

        $this->setTemplate('category_assign.tpl');
    }
}