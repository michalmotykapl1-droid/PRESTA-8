<?php
/**
 * 2007-2023 PrestaShop
 *
 * ProductPro Weight Correction Service
 * Contains core logic for product weight correction and discrepancy detection.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductProWeightCorrectionService
{
    private $context;
    private $moduleInstance;

    public function __construct(Module $moduleInstance)
    {
        $this->context = Context::getContext();
        $this->moduleInstance = $moduleInstance;
    }

    /**
     * Pusta metoda debugowania - wyłączone zapisywanie do pliku.
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

    public function getProductsWithoutWeight()
    {
        $idLang = (int)$this->context->language->id;
        $idShop = (int)$this->context->shop->id;
        $sql = (new DbQuery())->select('p.id_product, pl.name, p.ean13 AS ean, p.reference AS sku')->from('product', 'p')->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $idShop)->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop)->where('p.weight = 0');
        $rows = Db::getInstance()->executeS($sql);
        
        if ($rows) {
            foreach ($rows as &$row) {
                $row['suggested'] = $this->parseWeightFromText($row['name']);
            }
        }
        return $rows ?: [];
    }

    private function parseWeightFromText($text, $referenceWeight = null)
    {
        if (preg_match_all('/([\d.,]+)\s*(kg|g|l|ml)/i', $text, $matches, PREG_SET_ORDER)) {
            $foundWeights = [];
            foreach ($matches as $match) {
                $num = (float)str_replace(',', '.', $match[1]);
                $unit = strtolower($match[2]);
                $weightInKg = 0;
                switch ($unit) {
                    case 'g':  $weightInKg = round($num / 1000, 3); break;
                    case 'kg': $weightInKg = round($num, 3); break;
                    case 'ml': $weightInKg = round($num / 1000, 3); break;
                    case 'l':  $weightInKg = round($num, 3); break;
                }
                if ($weightInKg > 0) {
                    $foundWeights[] = $weightInKg;
                }
            }

            $foundWeights = array_unique($foundWeights);

            if (empty($foundWeights)) {
                return null;
            }

            if (count($foundWeights) === 1) {
                return array_shift($foundWeights);
            }

            if ($referenceWeight !== null) {
                $closestWeight = null;
                $minDifference = null;

                foreach ($foundWeights as $weight) {
                    $difference = abs($referenceWeight - $weight);
                    if ($minDifference === null || $difference < $minDifference) {
                        $minDifference = $difference;
                        $closestWeight = $weight;
                    }
                }
                return $closestWeight;
            } else {
                return max($foundWeights);
            }
        }

        return null;
    }

    public function saveSuggestedWeights()
    {
        $products = $this->getProductsWithoutWeight();
        $updated_count = 0;
        foreach ($products as $product) {
            if ($product['suggested'] !== null) {
                $idProduct = (int)$product['id_product'];
                Db::getInstance()->update('product', ['weight' => (float)$product['suggested']], 'id_product = '.$idProduct);
                $updated_count++;
            }
        }
        if ($updated_count > 0) {
            return ['success' => true, 'message' => $this->l('Zapisano pomyślnie wagę dla ') . $updated_count . $this->l(' produktów.')];
        } else {
            return ['success' => false, 'message' => $this->l('Nie znaleziono wag do zapisania.')];
        }
    }

    public function saveSingleWeight($id_product, $weight)
    {
        $idProduct = (int)$id_product;
        if ($idProduct > 0 && $weight >= 0) {
            $result = Db::getInstance()->update('product', ['weight' => (float)$weight], 'id_product = '.$idProduct);
            if ($result) {
                return ['success' => true, 'message' => $this->l('Waga dla produktu ID ') . $idProduct . $this->l(' została zaktualizowana.')];
            } else {
                return ['success' => false, 'message' => $this->l('Wystąpił błąd podczas aktualizacji wagi dla produktu ID ') . $idProduct . '.'];
            }
        }
        return ['success' => false, 'message' => $this->l('Nieprawidłowe dane produktu lub wagi.')];
    }

    public function getProductsWithWeightDiscrepancy()
    {
        $idLang = (int)$this->context->language->id;
        $idShop = (int)$this->context->shop->id;

        $sql = (new DbQuery())
            ->select('p.id_product, pl.name, p.weight AS current_weight')
            ->from('product', 'p')
            ->innerJoin('product_shop', 'ps', 'p.id_product = ps.id_product AND ps.id_shop = ' . $idShop)
            ->innerJoin('product_lang', 'pl', 'p.id_product = pl.id_product AND pl.id_lang = ' . $idLang . ' AND pl.id_shop = ' . $idShop)
            ->where('p.weight > 0');

        $products = Db::getInstance()->executeS($sql);

        $discrepancy_products = [];

        if ($products) {
            foreach ($products as $product) {
                $current_weight = (float)$product['current_weight'];
                $suggested_weight = $this->parseWeightFromText($product['name'], $current_weight);

                if ($suggested_weight !== null && abs($suggested_weight - $current_weight) > 0.0001) {
                    $discrepancy_products[] = [
                        'id_product' => (int)$product['id_product'],
                        'name' => $product['name'],
                        'current_weight' => $current_weight,
                        'suggested_weight' => $suggested_weight,
                        'difference' => $suggested_weight - $current_weight,
                    ];
                }
            }
        }
        return $discrepancy_products;
    }

    public function saveAllWeightCorrections()
    {
        $products = $this->getProductsWithWeightDiscrepancy();
        $updated_count = 0;

        foreach ($products as $product) {
            if (isset($product['suggested_weight']) && $product['suggested_weight'] !== null) {
                $idProduct = (int)$product['id_product'];
                $newWeight = (float)$product['suggested_weight'];
                
                $this->saveSingleWeight($idProduct, $newWeight);
                $updated_count++;
            }
        }

        if ($updated_count > 0) {
            return ['success' => true, 'message' => $this->l('Zapisano pomyślnie wagę dla ') . $updated_count . $this->l(' produktów.')];
        } else {
            return ['success' => false, 'message' => $this->l('Nie znaleziono produktów do skorygowania lub wszystkie sugestie zostały już zastosowane.')];
        }
    }
}