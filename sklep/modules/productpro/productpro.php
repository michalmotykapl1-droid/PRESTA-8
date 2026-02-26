<?php
/**
 * 2007-2023 PrestaShop
 *
 * Module: Zarządzanie Wariantami Produktów PRO
 * PrestaShop 1.7 ↔ 8.x
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

// Dołączamy wszystkie pliki serwisowe, które są używane przez ten moduł.
require_once __DIR__ . '/services/ProductProWeightService.php';
require_once __DIR__ . '/services/ProductProPriceCalcService.php';
require_once __DIR__ . '/services/ProductProCategoryAssignService.php';
require_once __DIR__ . '/services/ProductProWeightCorrectionService.php'; // Nowa usługa

class ProductPro extends Module
{
    private $productProWeightService;
    private $productProPriceCalcService;
    private $productProCategoryAssignService;
    private $productProWeightCorrectionService; // Nowa właściwość

    public function __construct()
    {
        $this->name            = 'productpro';
        $this->tab             = 'front_office_features';
        $this->version         = '1.5.0'; // Increased module version
        $this->author          = 'Twoja Nazwa';
        $this->need_instance   = 1;
        $this->bootstrap       = true;

        parent::__construct();

        $this->displayName     = $this->l('Zarządzanie Wariantami Produktów PRO');
        $this->description     = $this->l('Moduł do zarządzania i prezentacji powiązanych produktów wagowych, uzupełniania brakujących wag, korygowania niezgodnych wag oraz wyświetlania ceny za jednostkę i przypisywania do kategorii wagowych z możliwością konfiguracji przedziałów.');
        $this->confirmUninstall= $this->l('Czy na pewno chcesz odinstalować ten moduł?');

        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];

        $this->productProWeightService = new ProductProWeightService($this);
        $this->productProPriceCalcService = new ProductProPriceCalcService($this);
        $this->productProCategoryAssignService = new ProductProCategoryAssignService($this);
        $this->productProWeightCorrectionService = new ProductProWeightCorrectionService($this); // Inicjalizacja nowej usługi
    }

    /**
     * Module installation method.
     */
    public function install()
    {
        if (!parent::install()
            || !$this->registerHook('actionAdminControllerSetMedia')
            || !$this->registerHook('actionFrontControllerSetMedia')
            || !$this->registerHook('displayProductProWeightSelector')
            || !$this->registerHook('displayProductFlavorSelector')
            || !$this->registerHook('displayProductButtons')
            || !$this->registerHook('displayProductAdditionalInfo')
            || !$this->registerHook('displayFooterProduct')
            || !$this->registerHook('displayPricePerUnit')
        ) {
            return false;
        }

        if (!$this->installTab()) {
            return false;
        }
        
        // Setting default values for existing option
        Configuration::updateValue('PRODUCTPRO_PRICE_UNIT', 'kg');

        // NEW: Setting default values for weight range configuration and category IDs/names
        // Using 0 as default ID, meaning "no category selected"
        Configuration::updateValue('PRODUCTPRO_CATEGORY_5_10KG_ID', 0); 
        Configuration::updateValue('PRODUCTPRO_CATEGORY_5_10KG_NAME', ''); 
        Configuration::updateValue('PRODUCTPRO_WEIGHT_5_10KG_MIN', 5.0);
        Configuration::updateValue('PRODUCTPRO_WEIGHT_5_10KG_MAX', 10.0);

        Configuration::updateValue('PRODUCTPRO_CATEGORY_20_25KG_ID', 0); 
        Configuration::updateValue('PRODUCTPRO_CATEGORY_20_25KG_NAME', ''); 
        Configuration::updateValue('PRODUCTPRO_WEIGHT_20_25KG_MIN', 20.0);
        Configuration::updateValue('PRODUCTPRO_WEIGHT_20_25KG_MAX', 25.0);

        return true;
    }

    /**
     * Module uninstallation method.
     */
    public function uninstall()
    {
        if (!$this->uninstallTab()) {
            return false;
        }
        
        // Deleting existing configuration variable
        Configuration::deleteByName('PRODUCTPRO_PRICE_UNIT');

        // NEW: Deleting configuration variables for weight ranges and category IDs/names
        Configuration::deleteByName('PRODUCTPRO_CATEGORY_5_10KG_ID');
        Configuration::deleteByName('PRODUCTPRO_CATEGORY_5_10KG_NAME');
        Configuration::deleteByName('PRODUCTPRO_WEIGHT_5_10KG_MIN');
        Configuration::deleteByName('PRODUCTPRO_WEIGHT_5_10KG_MAX');

        Configuration::deleteByName('PRODUCTPRO_CATEGORY_20_25KG_ID');
        Configuration::deleteByName('PRODUCTPRO_CATEGORY_20_25KG_NAME');
        Configuration::deleteByName('PRODUCTPRO_WEIGHT_20_25KG_MIN');
        Configuration::deleteByName('PRODUCTPRO_WEIGHT_20_25KG_MAX');

        return parent::uninstall();
    }

    /**
     * Installs tabs in the admin menu.
     */
    protected function installTab()
    {
        $languages = Language::getLanguages(true);
        
        // 1. Parent tab
        $parentTab = new Tab();
        $parentTab->active = 1;
        $parentTab->class_name = 'AdminProductProParent';
        $parentTab->name = [];
        foreach ($languages as $lang) {
            $parentTab->name[$lang['id_lang']] = $this->l('Zarządzanie Wariantami PRO');
        }
        $parentTab->id_parent = (int) Tab::getIdFromClassName('IMPROVE');
        $parentTab->module = $this->name;
        if (!$parentTab->add()) {
            return false;
        }

        // 2. First child tab: Products without weights
        $tab1 = new Tab();
        $tab1->active = 1;
        $tab1->class_name = 'AdminProductProConfig';
        $tab1->name = [];
        foreach ($languages as $lang) {
            $tab1->name[$lang['id_lang']] = $this->l('Produkty bez wag');
        }
        $tab1->id_parent = (int)$parentTab->id;
        $tab1->module = $this->name;
        if (!$tab1->add()) {
            return false;
        }
        
        // 3. Second child tab: PRO Weight Correction
        $tab2 = new Tab();
        $tab2->active = 1;
        $tab2->class_name = 'AdminProductProCorrection';
        $tab2->name = [];
        foreach ($languages as $lang) {
            $tab2->name[$lang['id_lang']] = $this->l('Korekta Wag PRO');
        }
        $tab2->id_parent = (int)$parentTab->id;
        $tab2->module = $this->name;
        if (!$tab2->add()) {
            return false;
        }

        // 4. Third child tab: Price per unit
        $tab3 = new Tab();
        $tab3->active = 1;
        $tab3->class_name = 'AdminProductProPriceCalc';
        $tab3->name = [];
        foreach ($languages as $lang) {
            $tab3->name[$lang['id_lang']] = $this->l('Cena za jednostkę');
        }
        $tab3->id_parent = (int)$parentTab->id;
        $tab3->module = $this->name;
        if (!$tab3->add()) {
            return false;
        }

        // 5. Fourth child tab: Category Assignment
        $tab4 = new Tab();
        $tab4->active = 1;
        $tab4->class_name = 'AdminProductProCategoryAssign';
        $tab4->name = [];
        foreach ($languages as $lang) {
            $tab4->name[$lang['id_lang']] = $this->l('Przypisywanie do kategorii');
        }
        $tab4->id_parent = (int)$parentTab->id;
        $tab4->module = $this->name;
        if (!$tab4->add()) {
            return false;
        }

        // 6. Fifth child tab: Weight Category Configuration
        $tab5 = new Tab();
        $tab5->active = 1;
        $tab5->class_name = 'AdminProductProCategoryAssignConfig';
        $tab5->name = [];
        foreach ($languages as $lang) {
            $tab5->name[$lang['id_lang']] = $this->l('Konfiguracja kategorii wagowych');
        }
        $tab5->id_parent = (int)$parentTab->id;
        $tab5->module = $this->name;
        if (!$tab5->add()) {
            return false;
        }

        return true;
    }

    /**
     * Uninstalls tabs from the admin menu.
     */
    protected function uninstallTab()
    {
        $tabs = [
            'AdminProductProParent',
            'AdminProductProConfig',
            'AdminProductProCorrection',
            'AdminProductProPriceCalc',
            'AdminProductProCategoryAssign',
            'AdminProductProCategoryAssignConfig'
        ];
        foreach ($tabs as $className) {
            $id_tab = (int) Tab::getIdFromClassName($className);
            if ($id_tab) {
                $tab = new Tab($id_tab);
                if (!$tab->delete()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Hook for including CSS/JS in the admin panel.
     */
    public function hookActionAdminControllerSetMedia()
    {
        $currentController = Tools::getValue('controller');
        if (in_array($currentController, [
            'AdminProductProConfig',
            'AdminProductProCorrection',
            'AdminProductProPriceCalc',
            'AdminProductProCategoryAssign',
            'AdminProductProCategoryAssignConfig'
        ])) {
            $this->context->controller->addCSS($this->_path . 'views/css/admin_productpro.css');
        }
    }

    /**
     * Hook for loading resources on the shop front.
     */
    public function hookActionFrontControllerSetMedia()
    {
        if ($this->context->controller->php_self === 'product') {
            $this->context->controller->addCSS($this->_path . 'views/css/front_productpro.css');
            
            // --- TUTAJ DODANO REJESTRACJĘ PLIKU JS (JEDYNA ZMIANA) ---
            $this->context->controller->addJS($this->_path . 'views/js/front_productpro.js');
        }
    }

    /**
     * Implementation of custom hook displayPricePerUnit.
     */
    public function hookDisplayPricePerUnit(array $params)
    {
        return $this->productProPriceCalcService->renderPricePerUnitDisplay($params);
    }

    /**
     * Implementations of existing hooks on the product page.
     * Changed logic to prioritize displaying the flavor/type selector.
     */
    public function hookDisplayProductProWeightSelector(array $params)
    {
        // This hook should remain unchanged if it is only used to display the weight selector
        return $this->productProWeightService->renderWeightSelector($params);
    }

    public function hookDisplayProductButtons(array $params)
    {
        $output = '';
        // First, try to render the flavor/type selector
        $flavorOutput = $this->productProWeightService->renderFlavorSelector($params);
        if (!empty($flavorOutput)) {
            $output .= $flavorOutput;
        } else {
            // If the flavor/type selector returned nothing, try to render the weight selector
            $output .= $this->productProWeightService->renderWeightSelector($params);
        }
        return $output;
    }

    public function hookDisplayProductAdditionalInfo(array $params)
    {
        $output = '';
        // First, try to render the flavor/type selector
        $flavorOutput = $this->productProWeightService->renderFlavorSelector($params);
        if (!empty($flavorOutput)) {
            $output .= $flavorOutput;
        } else {
            // If the flavor/type selector returned nothing, try to render the weight selector
            $output .= $this->productProWeightService->renderWeightSelector($params);
        }
        return $output;
    }

    public function hookDisplayFooterProduct(array $params)
    {
        $output = '';
        // First, try to render the flavor/type selector
        $flavorOutput = $this->productProWeightService->renderFlavorSelector($params);
        if (!empty($flavorOutput)) {
            $output .= $flavorOutput;
        } else {
            // If the flavor/type selector returned nothing, try to render the weight selector
            $output .= $this->productProWeightService->renderWeightSelector($params);
        }
        return $output;
    }

    public function hookDisplayProductFlavorSelector(array $params)
    {
        // This hook should remain unchanged if it is only used to display the type selector
        return $this->productProWeightService->renderFlavorSelector($params);
    }

    /**
     * Admin controller for "Produkty bez wag" tab.
     */
    public function getContent()
    {
        // Handle form submissions for weight corrections
        if (Tools::isSubmit('submitSaveSuggestedWeights')) {
            $result = $this->productProWeightCorrectionService->saveSuggestedWeights();
            if ($result['success']) {
                $this->context->controller->confirmations[] = $result['message'];
            } else {
                $this->context->controller->errors[] = $result['message'];
            }
        } elseif (Tools::isSubmit('submitSaveSingleWeight')) {
            $idProduct = (int)Tools::getValue('id_product');
            $newWeight = (float)Tools::getValue('new_weight');
            $result = $this->productProWeightCorrectionService->saveSingleWeight($idProduct, $newWeight);
            if ($result['success']) {
                $this->context->controller->confirmations[] = $result['message'];
            } else {
                $this->context->controller->errors[] = $result['message'];
            }
        } elseif (Tools::isSubmit('submitSaveAllWeightCorrections')) { // Handle new "Save All" button
            $result = $this->productProWeightCorrectionService->saveAllWeightCorrections();
            if ($result['success']) {
                $this->context->controller->confirmations[] = $result['message'];
            } else {
                $this->context->controller->errors[] = $result['message'];
            }
        }

        $output = '';
        // Render the configuration form for "Produkty bez wag"
        $output .= $this->renderProductsWithoutWeightForm();
        // Render the configuration form for "Korekta Wag PRO"
        $output .= $this->renderWeightCorrectionForm();
        // Render the configuration form for "Cena za jednostkę"
        $output .= $this->renderPricePerUnitConfigForm();
        // Render the configuration form for "Przypisywanie do kategorii"
        $output .= $this->renderCategoryAssignForm();
        // Render the configuration form for "Konfiguracja kategorii wagowych"
        $output .= $this->renderCategoryAssignConfigForm();

        return $output;
    }

    // --- Admin Forms Rendering (simplified for brevity, actual content would be in Admin Controllers) ---
    // These methods would typically be in separate Admin Controllers for each tab.
    // For this refactoring, we'll assume they exist and call the appropriate service methods.

    protected function renderProductsWithoutWeightForm()
    {
        $productsWithoutWeight = $this->productProWeightCorrectionService->getProductsWithoutWeight();
        $this->context->smarty->assign([
            'productsWithoutWeight' => $productsWithoutWeight,
            'module_dir' => $this->_path,
        ]);
        return $this->display(__FILE__, 'views/templates/admin/products_without_weight.tpl');
    }

    protected function renderWeightCorrectionForm()
    {
        $productsWithDiscrepancy = $this->productProWeightCorrectionService->getProductsWithWeightDiscrepancy();
        $this->context->smarty->assign([
            'productsWithDiscrepancy' => $productsWithDiscrepancy,
            'module_dir' => $this->_path,
        ]);
        return $this->display(__FILE__, 'views/templates/admin/weight_correction.tpl');
    }

    protected function renderPricePerUnitConfigForm()
    {
        // This would be handled by ProductProPriceCalcService
        return ''; // Placeholder
    }

    protected function renderCategoryAssignForm()
    {
        // This would be handled by ProductProCategoryAssignService
        return ''; // Placeholder
    }

    protected function renderCategoryAssignConfigForm()
    {
        // This would be handled by ProductProCategoryAssignService
        return ''; // Placeholder
    }
}