<?php
/**
 * 2007-2023 PrestaShop
 *
 * ProductPro Price Calculation Service
 * Contains core logic for price per unit display.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductProPriceCalcService
{
    private $context;
    private $moduleInstance;

    public function __construct(Module $moduleInstance)
    {
        $this->context = Context::getContext();
        $this->moduleInstance = $moduleInstance;
    }

    private function l($string)
    {
        return $this->moduleInstance->l($string, 'productpropricecalcservice');
    }

    /**
     * Główna metoda renderująca widok ceny za jednostkę oraz dane strukturalne.
     */
    public function renderPricePerUnitDisplay(array $params)
    {
        // 1. Sprawdzamy, czy mamy dane produktu
        if (!isset($params['product'])) {
            return '';
        }
        $productData = $params['product'];
        $id_product = (int)($productData['id_product'] ?? $productData->id);
        
        $product = new Product($id_product, false, $this->context->language->id);

        // 2. Sprawdzamy, czy produkt jest załadowany i czy ma wagę
        if (!Validate::isLoadedObject($product) || $product->weight <= 0) {
            return '';
        }

        // 3. Pobieramy cenę produktu
        $price = Product::getPriceStatic($id_product, true);
        
        // 4. Pobieramy wybraną jednostkę z konfiguracji
        $unit = Configuration::get('PRODUCTPRO_PRICE_UNIT');

        $price_per_unit = 0;
        $unit_label = '';
        $schema_data = [];

        // 5. Obliczamy cenę i przygotowujemy dane dla schema.org
        if ($unit === '100g') {
            $price_per_unit = $price / ($product->weight * 10);
            $unit_label = '/ 100g';
            
            $schema_data = [
                'price'             => round($price_per_unit, 2), // Zaokrąglamy do 2 miejsc po przecinku
                'currency'          => $this->context->currency->iso_code,
                'billing_increment' => 100,
                'unit_code'         => 'GRM', // UN/CEFACT code for Gram
            ];

        } else { // Domyślnie liczymy dla 'kg'
            $price_per_unit = $price / $product->weight;
            $unit_label = '/ kg';

            $schema_data = [
                'price'             => round($price_per_unit, 2),
                'currency'          => $this->context->currency->iso_code,
                'billing_increment' => 1,
                'unit_code'         => 'KGM', // UN/CEFACT code for Kilogram
            ];
        }

        // 6. Formatujemy cenę i tworzymy finalny tekst
        $formatted_price = Tools::displayPrice($price_per_unit, $this->context->currency);
        $display_text = $formatted_price . ' ' . $unit_label;
        
        // 7. Przypisujemy wszystko do szablonu Smarty
        $this->context->smarty->assign([
            'price_per_unit_text' => $display_text,
            'schema_data'         => $schema_data, // Przekazujemy dane dla SEO
        ]);

        // 8. Wyświetlamy szablon hooka
        return $this->moduleInstance->display(
            $this->moduleInstance->getLocalPath(), 
            'views/templates/hook/display_price_per_unit.tpl'
        );
    }
}
