<?php
/**
 * 2007-2023 PrestaShop
 *
 * Kontroler strony konfiguracyjnej modułu ProductPro.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Dołączamy pliki z klasami serwisowymi
// Zachowujemy ProductProWeightService.php, jeśli jest używany gdzieś indziej w tym kontrolerze (choć dla tej funkcjonalności nie jest)
require_once _PS_MODULE_DIR_ . 'productpro/services/ProductProWeightService.php';
// Dołączamy nowy plik z klasą serwisową dla korekty wag
require_once _PS_MODULE_DIR_ . 'productpro/services/ProductProWeightCorrectionService.php';

class AdminProductProConfigController extends ModuleAdminController
{
    // Zmieniono nazwę właściwości, aby odzwierciedlała nową usługę
    private $productProWeightCorrectionService; 
    // Usunięto starą właściwość private $productProWeightService; ponieważ nie jest już używana w tym kontrolerze dla tej funkcjonalności.
    // Jeśli ten kontroler używa innych metod z ProductProWeightService, należy dodać $productProWeightService i zainicjalizować go.
    // Na podstawie oryginalnego kodu, nie używa.

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        // Prawidłowo inicjalizujemy nową usługę ProductProWeightCorrectionService.
        $this->productProWeightCorrectionService = new ProductProWeightCorrectionService($this->module);

        // Ustawienia dla renderowania listy produktów (jeśli kontroler miałby renderować listę)
        // Na podstawie oryginalnego kodu, ten kontroler nie używa standardowego renderList w ten sposób.
        // Jeśli w przyszłości będzie używał, te właściwości będą potrzebne:
        // $this->context = Context::getContext();
        // $this->className = 'Product';
        // $this->table = 'product';
        // $this->identifier = 'id_product';
        // $this->lang = false;
        // $this->addRowAction('edit');
        // $this->list_no_link = true;
        // $this->fields_list = [...];
        // $this->bulk_actions = [...];
        // $this->fields_form = [...]; // To już masz dla przycisku "Zapisz wszystkie"
    }

    /**
     * Przetwarzanie akcji POST.
     */
    public function postProcess()
    {
        // Zmieniono wywołania na nową usługę ProductProWeightCorrectionService
        if (Tools::isSubmit('save_weights')) { // To jest przycisk "Zapisz wszystkie sugerowane wagi"
            $result = $this->productProWeightCorrectionService->saveSuggestedWeights();
            if ($result['success']) {
                $this->confirmations[] = $result['message'];
            } else {
                $this->warnings[] = $result['message'];
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminProductProConfig'));
        }

        if (Tools::isSubmit('save_single_weight')) { // To jest przycisk "Zapisz" dla pojedynczego produktu
            $id_product = (int)Tools::getValue('id_product_to_save');
            $weight = (float)str_replace(',', '.', Tools::getValue('single_weight'));

            $result = $this->productProWeightCorrectionService->saveSingleWeight($id_product, $weight);
            if ($result['success']) {
                $this->confirmations[] = $result['message'];
            } else {
                $this->errors[] = $result['message'];
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminProductProConfig'));
        }
        
        // Jeśli masz akcję "save_all_corrections" zdefiniowaną gdzieś indziej w tym kontrolerze,
        // również musiałaby zostać zmieniona na $this->productProWeightCorrectionService->saveAllWeightCorrections();
        // Na podstawie przesłanego kodu, nie ma jej tutaj.

        parent::postProcess();
    }
    
    /**
     * Inicjalizacja i renderowanie widoku.
     */
    public function initContent()
    {
        parent::initContent();

        // Pobieramy produkty bez wag z nowej usługi
        $products = $this->productProWeightCorrectionService->getProductsWithoutWeight();

        $this->context->smarty->assign([
            'module_name'    => $this->module->displayName,
            'products'       => $products,
            'products_count' => count($products),
            'scan_url'       => $this->context->link->getAdminLink('AdminProductProConfig'),
        ]);

        // Ustawiamy szablon dla tej strony - używamy istniejącego 'configure.tpl'
        $this->setTemplate('configure.tpl');
    }
}
