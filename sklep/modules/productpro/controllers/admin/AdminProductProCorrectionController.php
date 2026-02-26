<?php
/**
 * 2007-2023 PrestaShop
 *
 * Kontroler strony do korekty wag produktów.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Dołączamy pliki z klasami serwisowymi
// Zachowujemy ProductProWeightService.php, jeśli jest używany gdzieś indziej w tym kontrolerze (choć dla tej funkcjonalności nie jest)
require_once _PS_MODULE_DIR_ . 'productpro/services/ProductProWeightService.php';
// Dołączamy nowy plik z klasą serwisową dla korekty wag
require_once _PS_MODULE_DIR_ . 'productpro/services/ProductProWeightCorrectionService.php';

class AdminProductProCorrectionController extends ModuleAdminController
{
    // Zmieniono nazwę właściwości, aby odzwierciedlała nową usługę
    private $productProWeightCorrectionService;

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
        // Prawidłowo inicjalizujemy nową usługę ProductProWeightCorrectionService.
        $this->productProWeightCorrectionService = new ProductProWeightCorrectionService($this->module);
    }

    /**
     * Przetwarzanie akcji POST.
     */
    public function postProcess()
    {
        // Akcja zapisu wagi dla pojedynczego produktu
        if (Tools::isSubmit('save_single_correction')) {
            $id_product = (int)Tools::getValue('id_product_to_correct');
            $weight = (float)str_replace(',', '.', Tools::getValue('corrected_weight'));

            // Używamy metody z nowej usługi do zapisu pojedynczej wagi
            $result = $this->productProWeightCorrectionService->saveSingleWeight($id_product, $weight);
            
            if ($result['success']) {
                $this->confirmations[] = $result['message'];
            } else {
                $this->errors[] = $result['message'];
            }
            // Przekierowanie, aby uniknąć ponownego wysłania formularza
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminProductProCorrection'));
        }

        // Akcja zapisu wszystkich sugerowanych korekt
        if (Tools::isSubmit('save_all_corrections')) {
            // Wywołujemy metodę z nowej usługi
            $result = $this->productProWeightCorrectionService->saveAllWeightCorrections();

            if ($result['success']) {
                $this->confirmations[] = $result['message'];
            } else {
                // Używamy ostrzeżenia, gdy nie ma nic do zrobienia
                $this->warnings[] = $result['message'];
            }
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminProductProCorrection'));
        }

        parent::postProcess();
    }
    
    /**
     * Inicjalizacja i renderowanie widoku.
     */
    public function initContent()
    {
        parent::initContent();

        // Pobieramy realne dane z nowej usługi za pomocą naszej metody
        $productsForCorrection = $this->productProWeightCorrectionService->getProductsWithWeightDiscrepancy();

        $this->context->smarty->assign([
            'module_name'           => $this->module->displayName,
            'products_for_correction' => $productsForCorrection,
            'products_count'        => count($productsForCorrection),
            'scan_correction_url'   => $this->context->link->getAdminLink('AdminProductProCorrection'),
        ]);

        // Ustawiamy szablon dla tej strony - używamy istniejącego 'correction.tpl'
        $this->setTemplate('correction.tpl');
    }
}
