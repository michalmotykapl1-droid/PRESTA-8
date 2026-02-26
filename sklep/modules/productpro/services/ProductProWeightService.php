<?php
/**
 * 2007-2023 PrestaShop
 *
 * ProductPro Weight Service
 * Contains core logic for product weight management.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductProWeightService
{
    private $context;
    private $moduleInstance;

    public function __construct(Module $moduleInstance)
    {
        $this->context = Context::getContext();
        $this->moduleInstance = $moduleInstance;
    }

    /**
     * Pusta metoda debugowania - nie zapisuje już plików.
     * Pozostawiona, aby nie powodować błędów w miejscach wywołania.
     */
    protected function logDebug($msg, $method = '')
    {
        // Debug disabled
        return;
    }

    private function l($string, $specific = 'productproweightservice')
    {
        return $this->moduleInstance->l($string, $specific);
    }

    /**
     * Renderuje selektor wag (dropdown).
     * Logika: 
     * 1. Nie ukrywa produktów z wagą 0.
     * 2. Separuje produkty 'normalne' od 'outlet' (SKU zaczynające się od A_MAG).
     */
    public function renderWeightSelector(array $params)
    {
        // $this->logDebug(...) calls are now safe and ignored
        if (!isset($params['product'])) {
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        
        $idProduct = (int) ($params['product']['id_product'] ?? $params['product']->id ?? 0);

        if (!$idProduct) {
            return '';
        }

        $product = new Product($idProduct, false, $idLang, $idShop);

        if (!Validate::isLoadedObject($product) || empty($product->name)) {
            return '';
        }

        $id_manufacturer = (int)$product->id_manufacturer;
        if ($id_manufacturer === 0) {
            return '';
        }

        // 1. Sprawdzamy typ AKTUALNEGO produktu (czy to "Łap Okazje" z A_MAG)
        $currentSku = isset($product->reference) ? (string)$product->reference : '';
        $isCurrentOutlet = (strpos($currentSku, 'A_MAG') === 0);

        // Wyciągamy nazwę bazową
        $baseName = trim(preg_replace('/\s+\d+[\.,]?\d*\s*(kg|g|l|ml).*/i', '', $product->name));
        
        // 2. Budujemy zapytanie
        $query = (new DbQuery())
            ->select('p.id_product, p.weight, pl.name, p.reference')
            ->from('product', 'p')
            ->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop)
            ->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $idShop)
            ->where('p.id_manufacturer = ' . $id_manufacturer)
            ->where('pl.name LIKE "' . pSQL($baseName) . '%"')
            ->where('ps.active = 1');

        $rows = Db::getInstance()->executeS($query);

        if (empty($rows) || count($rows) < 2) {
            return '';
        }

        $variants = [];
        $unique_products_check = []; 

        foreach ($rows as $row) {
            // 3. Sprawdzamy typ ZNALEZIONEGO produktu
            $rowSku = isset($row['reference']) ? (string)$row['reference'] : '';
            $rowIsOutlet = (strpos($rowSku, 'A_MAG') === 0);

            // 4. Filtracja: Normalny widzi tylko Normalne. A_MAG widzi tylko A_MAG.
            if ($isCurrentOutlet !== $rowIsOutlet) {
                continue;
            }

            $weightInGrams = (float)$row['weight'] * 1000;
            
            $roundedGrams = round($weightInGrams / 100) * 100;
            if ($roundedGrams == 0) {
                $roundedGrams = (int)$weightInGrams;
            }

            $variants[] = [
                'id'            => (int)$row['id_product'],
                'display_grams' => $roundedGrams,
                'link'          => $this->context->link->getProductLink((int)$row['id_product']),
                'name'          => $row['name'],
                'sku'           => $rowSku
            ];
            
            $unique_products_check[$row['id_product']] = true; 
        }

        if (count($variants) <= 1) {
            return '';
        }

        usort($variants, function($a, $b) {
            return $a['display_grams'] <=> $b['display_grams'];
        });

        $this->context->smarty->assign([
            'variants'  => $variants,
            'currentId' => $idProduct,
            'product_data' => [
                'id_product' => $product->id,
                'url' => $this->context->link->getProductLink($product),
                'name' => $product->name,
            ],
        ]);

        return $this->moduleInstance->display($this->moduleInstance->getLocalPath(), 'views/templates/hook/product_weights_selector.tpl');
    }
    
    /**
     * Renderuje selektor smaków/typów (Inne rodzaje).
     */
    public function renderFlavorSelector(array $params)
    {
        if (!isset($params['product'])) {
            return '';
        }

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;
        $idProduct = (int) ($params['product']['id_product'] ?? ($params['product']->id ?? 0));

        if (!$idProduct) {
            return '';
        }

        $product = new Product($idProduct, false, $idLang, $idShop);
        if (!Validate::isLoadedObject($product)) {
            return '';
        }

        $cleanName = function ($name) use ($product) {
            $name = trim($name);
            $name = preg_replace('/\s+\d+[\.,]?\d*\s*(kg|g|l|ml).*/iu', '', $name);

            if ($product->id_manufacturer) {
                $manufacturer = new Manufacturer((int) $product->id_manufacturer, $this->context->language->id);
                if (Validate::isLoadedObject($manufacturer) && !empty($manufacturer->name)) {
                    $pattern = '/^' . preg_quote($manufacturer->name, '/') . '\s+/iu';
                    $name = preg_replace($pattern, '', $name);
                }
            }
            return trim($name);
        };

        $currentProductCleanName = $cleanName($product->name);
        if ($currentProductCleanName === '') {
            return '';
        }

        $extractBaseToken = function ($name) {
            $parts = preg_split('/\s+/', trim($name));
            return isset($parts[0]) ? Tools::strtolower($parts[0]) : '';
        };

        $currentBaseToken = $extractBaseToken($currentProductCleanName);
        if ($currentBaseToken === '') {
            return '';
        }

        $productWeightInGrams = (float) $product->weight * 1000;
        $roundedCurrentProductWeight = ($productWeightInGrams > 0)
            ? (int) (round($productWeightInGrams / 100) * 100)
            : 0;
        if ($roundedCurrentProductWeight === 0 && $productWeightInGrams > 0) {
            $roundedCurrentProductWeight = (int) $productWeightInGrams;
        }

        $idCategory = (int) $product->id_category_default;
        if ($idCategory <= 0) {
            return '';
        }

        $sql = (new DbQuery())
            ->select('p.id_product, pl.name, p.weight')
            ->from('product', 'p')
            ->innerJoin(
                'product_lang',
                'pl',
                'p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop
            )
            ->innerJoin(
                'product_shop',
                'ps',
                'p.id_product = ps.id_product AND ps.id_shop = ' . $idShop
            )
            ->innerJoin(
                'category_product',
                'cp',
                'cp.id_product = p.id_product'
            )
            ->where('cp.id_category = ' . (int) $idCategory)
            ->where('p.id_product != ' . (int) $idProduct)
            ->where('ps.active = 1');

        $rows = Db::getInstance()->executeS($sql);

        if (empty($rows)) {
            return '';
        }

        $variants = [];
        $seenDisplayNames = [];

        foreach ($rows as $row) {
            $rowId = (int) $row['id_product'];

            $rowWeightInGrams = (float) $row['weight'] * 1000;
            $roundedRowWeight = ($rowWeightInGrams > 0)
                ? (int) (round($rowWeightInGrams / 100) * 100)
                : 0;
            if ($roundedRowWeight === 0 && $rowWeightInGrams > 0) {
                $roundedRowWeight = (int) $rowWeightInGrams;
            }

            if ($roundedCurrentProductWeight > 0 && $roundedRowWeight !== $roundedCurrentProductWeight) {
                continue;
            }

            $fullName = trim($row['name']);
            if ($fullName === '') {
                continue;
            }

            $cleanedRowName = $cleanName($fullName);
            if ($cleanedRowName === '') {
                continue;
            }

            $rowBaseToken = $extractBaseToken($cleanedRowName);
            if ($rowBaseToken === '' || $rowBaseToken !== $currentBaseToken) {
                continue;
            }

            if (Tools::strtolower($cleanedRowName) === Tools::strtolower($currentProductCleanName)) {
                continue;
            }

            $displayNameLower = Tools::strtolower($fullName);
            if (isset($seenDisplayNames[$displayNameLower])) {
                continue;
            }

            $rowProduct = new Product($rowId, false, $idLang, $idShop);
            if (!Validate::isLoadedObject($rowProduct)) {
                continue;
            }

            $priceWithReduction = (float) $rowProduct->getPrice(true);
            $priceWithoutReduction = (float) $rowProduct->getPriceWithoutReduct(true);
            $hasDiscount = $priceWithoutReduction > 0 && $priceWithoutReduction > $priceWithReduction + 0.0001;

            $priceFormatted = Tools::displayPrice($priceWithReduction);
            $priceWithoutReductionFormatted = $hasDiscount ? Tools::displayPrice($priceWithoutReduction) : '';

            $rowCategories = Product::getProductCategories($rowId);
            $isSale = in_array(45, $rowCategories) || in_array(180, $rowCategories);

            $cover = Product::getCover($rowId);
            $imageUrl = '';
            if ($cover && isset($cover['id_image'])) {
                $imageUrl = $this->context->link->getImageLink(
                    $rowProduct->link_rewrite,
                    (int) $cover['id_image'],
                    'side_product_default'
                );
            } else {
                $noPic = $this->context->link->getNoPictureImage($idLang);
                if (is_array($noPic) && isset($noPic['link'])) {
                    $imageUrl = $noPic['link'];
                }
            }

            $variants[] = [
                'id'                      => $rowId,
                'display_name'            => $fullName,
                'link'                    => $this->context->link->getProductLink($rowProduct),
                'image_url'               => $imageUrl,
                'price'                   => $priceFormatted,
                'price_without_reduction' => $priceWithoutReductionFormatted,
                'has_discount'            => $hasDiscount,
                'is_sale'                 => $isSale,
            ];

            $seenDisplayNames[$displayNameLower] = true;
        }

        if (empty($variants)) {
            return '';
        }

        usort($variants, function ($a, $b) {
            return strcoll($a['display_name'], $b['display_name']);
        });

        $this->context->smarty->assign([
            'type_variants' => $variants,
            'currentId'     => $idProduct,
            'currentName'   => $product->name,
            'product_data'  => [
                'id_product' => $product->id,
                'url'        => $this->context->link->getProductLink($product),
                'name'       => $product->name,
            ],
        ]);

        return $this->moduleInstance->display(
            $this->moduleInstance->getLocalPath(),
            'views/templates/hook/product_types_selector.tpl'
        );
    }
}