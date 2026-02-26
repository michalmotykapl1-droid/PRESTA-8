<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class ExtraSearchRepository
{
    public function searchAndFormat($query, $context)
    {
        // 1. ZAPYTANIE SQL (Z priorytetem nazwy i A_MAG)
        $words = explode(' ', $query);
        $whereClauses = [];
        foreach ($words as $word) {
            if (!empty($word)) {
                $w = pSQL($word);
                $whereClauses[] = "(p.ean13 LIKE '%$w%' OR pl.name LIKE '%$w%' OR p.reference LIKE '%$w%')";
            }
        }
        $whereSql = implode(' AND ', $whereClauses);
        $fullQuery = pSQL($query);

        // Pobieramy ID, Nazwę, EAN, SKU, Cenę, Podatek i Stan
        $sql = 'SELECT p.id_product, pl.name, pl.link_rewrite, p.ean13, p.reference, 
                       p.wholesale_price, p.id_tax_rules_group, sa.quantity, i.id_image
                FROM ' . _DB_PREFIX_ . 'product p
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . (int)$context->language->id . ')
                LEFT JOIN ' . _DB_PREFIX_ . 'stock_available sa ON (p.id_product = sa.id_product AND sa.id_product_attribute = 0)
                LEFT JOIN ' . _DB_PREFIX_ . 'image i ON (i.id_product = p.id_product AND i.cover = 1)
                WHERE ' . $whereSql . '
                AND p.active = 1
                ORDER BY 
                    CASE WHEN p.reference LIKE "A_MAG%" THEN 0 ELSE 1 END ASC,
                    CASE WHEN pl.name LIKE "' . $fullQuery . '%" THEN 0 ELSE 1 END ASC,
                    CASE WHEN pl.name LIKE "%' . $fullQuery . '%" THEN 0 ELSE 1 END ASC,
                    pl.name ASC
                LIMIT 100';
        
        $prestaResults = Db::getInstance()->executeS($sql);
        
        $wmsGlobalList = [];
        $stdGlobalList = [];
        $groupedByEan = [];
        
        // 2. Grupowanie wyników po EAN
        if ($prestaResults) {
            foreach ($prestaResults as $row) {
                $ean = trim($row['ean13']);
                if (empty($ean)) {
                    $groupedByEan['NO_EAN_' . $row['id_product']][] = $row;
                } else {
                    $groupedByEan[$ean][] = $row;
                }
            }
        }

        // 3. Przetwarzanie Grup
        foreach ($groupedByEan as $eanKey => $rows) {
            
            $baseProductInfo = $rows[0]; 
            
            // OBLICZANIE CENY BRUTTO
            $priceGross = $this->calculateGrossPrice($baseProductInfo['wholesale_price'], $baseProductInfo['id_tax_rules_group']);

            // --- A. WMS (Lokalizacje Magazynowe) ---
            // Tutaj sprawdzamy stan w tabelach WMS - jeśli tam jest > 0, to pokazujemy,
            // niezależnie od tego, co jest w stock_available (Presta).
            $eanSafe = ($baseProductInfo['ean13']) ? pSQL($baseProductInfo['ean13']) : '';
            
            if (!empty($eanSafe)) {
                // 1. Sprawdź Duplikaty (Priorytet)
                $duplicates = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` WHERE ean = '$eanSafe' AND quantity > 0");

                if ($duplicates && count($duplicates) > 0) {
                    foreach ($duplicates as $dup) {
                        $wmsRow = $baseProductInfo;
                        $expiry = (!empty($dup['expiry_date']) && $dup['expiry_date'] != '0000-00-00') ? ' | Ważn: ' . date('d.m.Y', strtotime($dup['expiry_date'])) : '';
                        $wmsRow['image_url'] = $this->getImageUrl($baseProductInfo, $context);
                        $wmsRow['name'] = '<span style="color:#e08e00; font-weight:bold;">★ [MAG] ' . $baseProductInfo['name'] . '</span><br><span style="color:#0099cc; font-size:0.9em;"><i class="icon-map-marker"></i> Lok: <b>' . $dup['regal'] . ' / ' . $dup['polka'] . '</b>' . $expiry . '</span>';
                        $wmsRow['quantity'] = (int)$dup['quantity'];
                        $wmsRow['reference'] = 'DUPL_' . $dup['id']; 
                        $wmsRow['unique_js_id'] = 'dup_' . $dup['id'];
                        $wmsRow['price_gross'] = number_format($priceGross, 2, '.', '');
                        $wmsGlobalList[] = $wmsRow; 
                    }
                } 
                // 2. Sprawdź Detale (Fallback)
                else {
                    $details = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE ean = '$eanSafe' AND quantity_wms > 0");
                    if ($details && count($details) > 0) {
                        foreach ($details as $det) {
                            $wmsRow = $baseProductInfo;
                            $expiry = (!empty($det['expiry_date']) && $det['expiry_date'] != '0000-00-00') ? ' | Ważn: ' . date('d.m.Y', strtotime($det['expiry_date'])) : '';
                            $wmsRow['image_url'] = $this->getImageUrl($baseProductInfo, $context);
                            $wmsRow['name'] = '<span style="color:#e08e00; font-weight:bold;">★ [MAG] ' . $baseProductInfo['name'] . '</span><br><span style="color:#0099cc; font-size:0.9em;"><i class="icon-map-marker"></i> Lok: <b>' . $det['regal'] . ' / ' . $det['polka'] . '</b>' . $expiry . '</span>';
                            $wmsRow['quantity'] = (int)$det['quantity_wms'];
                            $wmsRow['reference'] = $det['sku']; 
                            $wmsRow['unique_js_id'] = md5($det['sku']);
                            $wmsRow['price_gross'] = number_format($priceGross, 2, '.', '');
                            $wmsGlobalList[] = $wmsRow;
                        }
                    }
                }
            }

            // --- B. HURTOWNIE (Standard PrestaShop) ---
            foreach ($rows as $r) {
                $refUpper = strtoupper($r['reference']);
                
                // Pomijamy produkty, które są stricte magazynowe (A_MAG)
                if (strpos($refUpper, 'A_MAG') === 0 || strpos($refUpper, '0_MAG') === 0) {
                    continue;
                }

                // NOWY WARUNEK: Ukrywamy produkty z zerowym stanem (Standardowe)
                if ((int)$r['quantity'] <= 0) {
                    continue;
                }

                $supplierTag = '[SKLEP / HURTOWNIA]';
                $color = '#999';

                if (strpos($refUpper, 'BP_') === 0) { $supplierTag = '[BIO PLANET]'; $color = '#4caf50'; } 
                elseif (strpos($refUpper, 'NAT_') === 0) { $supplierTag = '[NATURA]'; $color = '#2196f3'; } 
                elseif (strpos($refUpper, 'EKOWIT') === 0) { $supplierTag = '[EKOWITAL]'; $color = '#ff9800'; } 
                elseif (strpos($refUpper, 'STEW') === 0) { $supplierTag = '[STEWIARNIA]'; $color = '#9c27b0'; } 

                $stdRow = $r;
                $stdRow['image_url'] = $this->getImageUrl($r, $context);
                
                $stdRow['name'] = $r['name'] . ' <span style="color:'.$color.'; font-weight:bold; font-size:0.85em; float:right;">' . $supplierTag . '</span>';
                $stdRow['unique_js_id'] = 'std_' . $r['id_product'] . '_' . md5($r['reference']);
                
                $pg = $this->calculateGrossPrice($r['wholesale_price'], $r['id_tax_rules_group']);
                $stdRow['price_gross'] = number_format($pg, 2, '.', '');
                
                $stdGlobalList[] = $stdRow;
            }
        }

        return array_merge($wmsGlobalList, $stdGlobalList);
    }

    private function getImageUrl($row, $context)
    {
        if ($row['id_image']) {
            $link = $context->link->getImageLink($row['link_rewrite'], $row['id_image'], 'small_default');
            if (Configuration::get('PS_SSL_ENABLED') && strpos($link, 'https') === false) {
                return str_replace('http://', 'https://', $link);
            }
            return $link;
        }
        return '';
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
        } catch (Exception $e) {
            return (float)$priceNet;
        }
    }
}