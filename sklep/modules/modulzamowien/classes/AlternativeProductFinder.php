<?php
/**
 * Klasa AlternativeProductFinder
 * Wersja 18.0: Wizualizacja Baterii Cenowej (Poziome klocki)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AlternativeProductFinder
{
    public function findAlternatives($query, $context)
    {
        $query = trim($query);
        if (strlen($query) < 3) return ['direct' => [], 'smaller' => [], 'source_info' => []];

        $targetWeight = null;
        $searchKeywords = [];
        $excludeIds = [];
        
        $sourceName = $query;
        $sourceManufacturerId = 0;
        $sourceManufacturerName = '';
        $sourcePriceGross = 0;
        $sourceEan = '';

        if (preg_match('/^\d{12,14}$/', $query)) {
            $sourceProduct = Db::getInstance()->getRow('
                SELECT p.id_product, p.weight, pl.name, p.id_manufacturer, m.name as man_name, p.wholesale_price, p.id_tax_rules_group, p.ean13
                FROM ' . _DB_PREFIX_ . 'product p
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . (int)$context->language->id . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
                WHERE p.ean13 = "'.pSQL($query).'" OR p.reference = "'.pSQL($query).'"
            ');

            if ($sourceProduct) {
                $targetWeight = (float)$sourceProduct['weight'];
                $sourceName = $sourceProduct['name'];
                $sourceEan = $sourceProduct['ean13'];
                $sourceManufacturerId = (int)$sourceProduct['id_manufacturer'];
                $sourceManufacturerName = $sourceProduct['man_name'];
                
                $sourcePriceGross = $this->calculateGrossPrice($sourceProduct['wholesale_price'], $sourceProduct['id_tax_rules_group']);

                $searchKeywords = $this->extractKeywordsFromName($sourceProduct['name']);
                $excludeIds[] = (int)$sourceProduct['id_product'];
            } else {
                return ['direct' => [], 'smaller' => [], 'source_info' => ['name' => 'Nieznany EAN: '.$query, 'weight' => 0, 'manufacturer' => '', 'price_gross' => 0, 'ean' => $query]];
            }
        } 
        else {
            $parsed = $this->parseStringQuery($query);
            $searchKeywords = $parsed['name_parts'];
            $targetWeight = $parsed['weight'];
            $sourceName = $query; 
        }

        if (empty($searchKeywords)) return ['direct' => [], 'smaller' => [], 'source_info' => []];

        $sourceInfo = [
            'name' => $sourceName,
            'ean' => $sourceEan,
            'weight' => $targetWeight,
            'manufacturer' => $sourceManufacturerName,
            'price_gross' => round($sourcePriceGross, 2)
        ];

        $directAlternatives = $this->queryProducts($searchKeywords, $excludeIds, $targetWeight, 'direct', $sourceManufacturerId, $context, $sourcePriceGross);

        $smallerAlternatives = [];
        if ($targetWeight !== null && $targetWeight > 0) {
            $smallerAlternatives = $this->queryProducts($searchKeywords, $excludeIds, $targetWeight, 'smaller', $sourceManufacturerId, $context, $sourcePriceGross);
        }

        return [
            'direct' => $directAlternatives,
            'smaller' => $smallerAlternatives,
            'source_info' => $sourceInfo
        ];
    }

    private function queryProducts($keywords, $excludeIds, $targetWeight, $mode, $sourceManId, $context, $sourcePriceGross)
    {
        $sql = 'SELECT p.id_product, pl.name, pl.link_rewrite, p.ean13, p.reference, 
                       p.wholesale_price, p.id_tax_rules_group, sa.quantity, i.id_image, p.weight, p.id_manufacturer, m.name as man_name
                FROM ' . _DB_PREFIX_ . 'product p
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . (int)$context->language->id . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (p.id_product = sa.id_product AND sa.id_product_attribute = 0)
                LEFT JOIN ' . _DB_PREFIX_ . 'image i ON (i.id_product = p.id_product AND i.cover = 1)
                LEFT JOIN ' . _DB_PREFIX_ . 'manufacturer m ON (p.id_manufacturer = m.id_manufacturer)
                WHERE p.active = 1 
                AND sa.quantity > 0 ';

        if (!empty($excludeIds)) {
            $sql .= ' AND p.id_product NOT IN ('.implode(',', $excludeIds).') ';
        }

        foreach ($keywords as $word) {
            if (strlen($word) > 2) {
                $w = pSQL($word);
                $sql .= " AND pl.name LIKE '%$w%' ";
            }
        }

        if ($targetWeight !== null && $targetWeight > 0) {
            if ($mode == 'direct') {
                $min = $targetWeight * 0.90; 
                $max = $targetWeight * 1.10;
                $sql .= " AND p.weight BETWEEN $min AND $max ";
            } elseif ($mode == 'smaller') {
                $maxSmall = $targetWeight * 0.80; 
                $sql .= " AND p.weight > 0 AND p.weight <= $maxSmall ";
            }
        }

        $sql .= ' ORDER BY 
                  CASE WHEN p.reference LIKE "A_MAG%" THEN 0 ELSE 1 END ASC,
                  CASE WHEN p.id_manufacturer = '.(int)$sourceManId.' THEN 0 ELSE 1 END ASC,
                  sa.quantity DESC, 
                  pl.name ASC 
                  LIMIT 15';

        $results = Db::getInstance()->executeS($sql);
        if (!$results) return [];

        $formatted = [];
        foreach ($results as $row) {
            
            $multiplier = 1;
            $packInfo = '';
            $isShortage = false;
            
            $priceGross = $this->calculateGrossPrice($row['wholesale_price'], $row['id_tax_rules_group']);

            if ($mode == 'smaller' && $row['weight'] > 0) {
                $needed = ceil($targetWeight / $row['weight']);
                $multiplier = $needed;
                
                $weightDisplay = ($row['weight'] < 1) ? ($row['weight']*1000).'g' : $row['weight'].'kg';
                $targetDisplay = ($targetWeight < 1) ? ($targetWeight*1000).'g' : $targetWeight.'kg';
                
                if (strpos($row['reference'], 'A_MAG') === 0 && (int)$row['quantity'] < $needed) {
                    $available = (int)$row['quantity'];
                    $packInfo = "<span style='color:#d9534f; font-weight:bold;'><i class='icon-warning-sign'></i> Potrzeba $needed szt. (Masz tylko $available!)</span>";
                    $isShortage = true;
                } else {
                    $packInfo = "Kup <b>$needed szt.</b> po <b>$weightDisplay</b> aby uzyskać ok. $targetDisplay";
                }
            }

            // --- WIZUALIZACJA BATERII CENOWEJ ---
            $totalAltPrice = $priceGross * $multiplier;
            $priceDiffText = '';
            
            if ($sourcePriceGross > 0) {
                $diffVal = $totalAltPrice - $sourcePriceGross;
                
                if ($diffVal < -0.05) {
                    // TANIEJ - Zielony tekst
                    $priceDiffText = '<div style="color:#2e7d32; font-weight:bold; font-size:0.85em; margin-top:2px;">-' . number_format(abs($diffVal), 2) . ' zł</div>';
                } 
                elseif ($diffVal > 0.05) {
                    // DROŻEJ - Budujemy "Baterię"
                    $barsCount = floor($diffVal); // Ilość pełnych złotówek różnicy
                    if ($barsCount > 5) $barsCount = 5; // Max 5 kresek
                    
                    // Kontener baterii
                    $batteryHtml = '<div style="display:flex; gap:1px; margin-left:6px; border:1px solid #ccc; padding:1px; border-radius:2px; background:#fff;">';
                    
                    for ($i = 1; $i <= 5; $i++) {
                        // Jeśli aktualny index <= obliczona ilość kresek -> CZERWONY, w przeciwnym razie SZARY
                        $bg = ($i <= $barsCount) ? '#d9534f' : '#eeeeee';
                        $batteryHtml .= '<div style="width:4px; height:8px; background-color:'.$bg.';"></div>';
                    }
                    $batteryHtml .= '</div>';

                    // Całość w jednej linii (Flex)
                    $priceDiffText = '<div style="display:flex; align-items:center; justify-content:center; margin-top:2px;">';
                    $priceDiffText .= '<span style="color:#d9534f; font-size:0.85em; font-weight:bold;">+' . number_format($diffVal, 2) . ' zł</span>';
                    $priceDiffText .= $batteryHtml;
                    $priceDiffText .= '</div>';
                } 
                else {
                    $priceDiffText = '<div style="color:#999; font-size:0.85em; margin-top:2px;">= 0.00 zł</div>';
                }
            }

            // Uzupełnianie danych WMS
            $finalName = $row['name'];
            if (strpos($row['reference'], 'A_MAG') === 0 || strpos($row['reference'], 'DUPL') === 0) {
                $finalName = $this->appendWmsInfo($row['name'], $row['reference'], $row['ean13']);
            }

            $formatted[] = [
                'id_product' => $row['id_product'],
                'name' => $finalName,
                'ean13' => $row['ean13'],
                'reference' => $row['reference'],
                'wholesale_price' => $row['wholesale_price'],
                'price_gross' => number_format($priceGross, 2, '.', ''),
                'quantity' => (int)$row['quantity'],
                'image_url' => $this->getImageUrl($row, $context),
                'unique_js_id' => 'alt_' . $mode . '_' . $row['id_product'],
                'is_alternative' => true,
                'weight' => (float)$row['weight'],
                'manufacturer_name' => $row['man_name'],
                'is_same_manufacturer' => ($sourceManId > 0 && $row['id_manufacturer'] == $sourceManId),
                'multiplier' => $multiplier,
                'pack_info' => $packInfo,
                'is_shortage' => $isShortage,
                'price_diff_html' => $priceDiffText
            ];
        }

        return $formatted;
    }

    private function appendWmsInfo($originalName, $sku, $ean)
    {
        $wmsData = Db::getInstance()->getRow("SELECT regal, polka, expiry_date FROM `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` WHERE ean = '".pSQL($ean)."' AND quantity > 0");
        if (!$wmsData) {
            $wmsData = Db::getInstance()->getRow("SELECT regal, polka, expiry_date FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE ean = '".pSQL($ean)."' AND quantity_wms > 0");
        }

        if ($wmsData) {
            $expiry = (!empty($wmsData['expiry_date']) && $wmsData['expiry_date'] != '0000-00-00') ? ' | Ważn: ' . date('d.m.Y', strtotime($wmsData['expiry_date'])) : '';
            $location = $wmsData['regal'] . ' / ' . $wmsData['polka'];
            return '<span style="color:#e08e00; font-weight:bold;">★ [MAG] ' . $originalName . '</span><br><span style="color:#0099cc; font-size:0.9em;"><i class="icon-map-marker"></i> Lok: <b>' . $location . '</b>' . $expiry . '</span>';
        }
        return '★ [MAG] ' . $originalName;
    }

    private function calculateGrossPrice($priceNet, $idTaxRulesGroup)
    {
        try {
            $taxRate = 0;
            if ($idTaxRulesGroup) {
                $id_country = (int)Configuration::get('PS_COUNTRY_DEFAULT');
                $address = new Address();
                $address->id_country = $id_country;
                $address->id_state = 0;
                $address->postcode = 0; 
                if (class_exists('TaxManagerFactory')) {
                    $taxManager = TaxManagerFactory::getManager($address, (int)$idTaxRulesGroup);
                    $taxCalculator = $taxManager->getTaxCalculator();
                    $taxRate = $taxCalculator->getTotalRate();
                }
            }
            return (float)$priceNet * (1 + ($taxRate / 100));
        } catch (Exception $e) { return (float)$priceNet; }
    }

    private function extractKeywordsFromName($name) {
        $cleanName = preg_replace('/\d+(?:[.,]\d+)?\s*(kg|g|ml|l)\b/iu', '', $name);
        return $this->tokenize($cleanName);
    }

    private function parseStringQuery($query) {
        $weight = null;
        $cleanQuery = $query;
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(kg|g|ml|l)\b/iu', $query, $matches)) {
            $val = str_replace(',', '.', $matches[1]);
            $unit = mb_strtolower($matches[2]);
            if ($unit == 'kg' || $unit == 'l') $weight = (float)$val;
            elseif ($unit == 'g' || $unit == 'ml') $weight = (float)$val / 1000;
            $cleanQuery = str_replace($matches[0], '', $query);
        }
        return ['name_parts' => $this->tokenize($cleanQuery), 'weight' => $weight];
    }

    private function tokenize($string) {
        $string = preg_replace('/[^a-zA-Z0-9ąęćłńóśźżĄĘĆŁŃÓŚŹŻ\s]/u', ' ', $string);
        $parts = explode(' ', $string);
        $validParts = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if (strlen($p) >= 2) $validParts[] = $p;
        }
        return $validParts;
    }

    private function getImageUrl($row, $context) {
        if ($row['id_image']) {
            $link = $context->link->getImageLink($row['link_rewrite'], $row['id_image'], 'small_default');
            if (Configuration::get('PS_SSL_ENABLED') && strpos($link, 'https') === false) {
                return str_replace('http://', 'https://', $link);
            }
            return $link;
        }
        return '';
    }
}