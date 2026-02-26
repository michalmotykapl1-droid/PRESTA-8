<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class ExtraStockManager
{
    public function addItem($ean, $name, $qty, $sku, $id_shop, $force = 0)
    {
        $isMag = false;
        $idProduct = 0;

        // 1. Rozpoznanie (WMS czy Zwykły)
        if (!empty($sku) && (strpos($sku, 'A_MAG') === 0 || strpos($sku, 'DUPL_') === 0)) {
            $isMag = true;
            $idProduct = (int)Db::getInstance()->getValue("SELECT id_product FROM `"._DB_PREFIX_."product` WHERE ean13 = '".pSQL($ean)."'");
        } else {
            $idProduct = (int)Db::getInstance()->getValue("SELECT id_product FROM `"._DB_PREFIX_."product` WHERE ean13 = '".pSQL($ean)."'");
        }

        // --- STRAŻNIK MAGAZYNU (Weryfikacja przed dodaniem) ---
        if ($isMag == false && $force == 0 && !empty($ean)) {
            $eanSafe = pSQL($ean);
            $wmsLocations = []; // Zmieniamy na tablicę

            // Sprawdź duplikaty
            $dupes = Db::getInstance()->executeS("SELECT regal, polka, quantity FROM `" . _DB_PREFIX_ . "wyprzedazpro_csv_duplikaty` WHERE ean = '$eanSafe' AND quantity > 0");
            if ($dupes) {
                foreach ($dupes as $d) {
                    $wmsLocations[] = [
                        'loc' => $d['regal'] . ' / ' . $d['polka'],
                        'qty' => (int)$d['quantity']
                    ];
                }
            } 
            // Sprawdź detale (fallback)
            else {
                $details = Db::getInstance()->executeS("SELECT regal, polka, quantity_wms FROM `" . _DB_PREFIX_ . "wyprzedazpro_product_details` WHERE ean = '$eanSafe' AND quantity_wms > 0");
                if ($details) {
                    foreach ($details as $d) {
                        $wmsLocations[] = [
                            'loc' => $d['regal'] . ' / ' . $d['polka'],
                            'qty' => (int)$d['quantity_wms']
                        ];
                    }
                }
            }

            if (count($wmsLocations) > 0) {
                // Zwracamy tablicę lokalizacji zamiast tekstu msg
                return [
                    'success' => true, 
                    'confirmation_needed' => true, 
                    'locations' => $wmsLocations, // Tu są dane dla ładnego okienka
                    'product_ean' => $ean
                ];
            }
        }

        // --- ZDEJMOWANIE STANU ---
        if ($isMag) {
            if ($idProduct > 0) StockAvailable::updateQuantity($idProduct, 0, -1 * $qty, $id_shop);
            
            if (strpos($sku, 'DUPL_') === 0) {
                $dupId = (int)str_replace('DUPL_', '', $sku);
                Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."wyprzedazpro_csv_duplikaty` SET quantity = quantity - ".(int)$qty." WHERE id = ".$dupId);
                $dupEan = Db::getInstance()->getValue("SELECT ean FROM `"._DB_PREFIX_."wyprzedazpro_csv_duplikaty` WHERE id = ".$dupId);
                if ($dupEan) {
                    Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."wyprzedazpro_product_details` SET quantity_wms = quantity_wms - ".(int)$qty." WHERE ean = '".pSQL($dupEan)."'");
                }
            } elseif (strpos($sku, 'A_MAG') === 0) {
                Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."wyprzedazpro_product_details` SET quantity_wms = quantity_wms - ".(int)$qty." WHERE sku = '".pSQL($sku)."'");
            }
        }

        // --- ZAPIS DO LISTY ---
        $existing = false;
        if ($isMag && !empty($sku)) {
             $existing = Db::getInstance()->getRow("SELECT id_extra, qty FROM `"._DB_PREFIX_."modulzamowien_extra_items` WHERE sku = '".pSQL($sku)."'");
        } elseif (!empty($ean)) {
            $existing = Db::getInstance()->getRow("SELECT id_extra, qty FROM `"._DB_PREFIX_."modulzamowien_extra_items` WHERE ean = '".pSQL($ean)."' AND is_mag = 0");
        }

        if ($existing) {
            Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."modulzamowien_extra_items` SET qty = qty + ".(int)$qty." WHERE id_extra = ".(int)$existing['id_extra']);
        } else {
            $cleanName = strip_tags($name);
            if ($isMag && preg_match('/Lok:\s*<b>(.*?)<\/b>/', $name, $matches)) {
                $cleanName .= ' [' . $matches[1] . ']';
            }
            
            Db::getInstance()->insert('modulzamowien_extra_items', [
                'ean' => pSQL($ean), 'name' => pSQL($cleanName), 'qty' => (int)$qty,
                'sku' => pSQL($sku), 'is_mag' => (int)$isMag, 'id_product' => (int)$idProduct,
                'mag_sku' => pSQL($sku), 'date_add' => date('Y-m-d H:i:s')
            ]);
        }

        return ['success' => true, 'is_mag' => $isMag, 'deducted_qty' => $qty];
    }

    public function removeItem($id_extra, $id_shop)
    {
        $item = Db::getInstance()->getRow("SELECT * FROM `"._DB_PREFIX_."modulzamowien_extra_items` WHERE id_extra = ".(int)$id_extra);
        if ($item) {
            $qty = (int)$item['qty'];
            if (isset($item['is_mag']) && $item['is_mag']) {
                if (isset($item['id_product']) && $item['id_product'] > 0) StockAvailable::updateQuantity($item['id_product'], 0, $qty, $id_shop);
                $sku = $item['mag_sku'];
                if (!empty($sku)) {
                    if (strpos($sku, 'DUPL_') === 0) {
                        $dupId = (int)str_replace('DUPL_', '', $sku);
                        Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."wyprzedazpro_csv_duplikaty` SET quantity = quantity + ".$qty." WHERE id = ".$dupId);
                        $dupEan = Db::getInstance()->getValue("SELECT ean FROM `"._DB_PREFIX_."wyprzedazpro_csv_duplikaty` WHERE id = ".$dupId);
                        if ($dupEan) Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."wyprzedazpro_product_details` SET quantity_wms = quantity_wms + ".$qty." WHERE ean = '".pSQL($dupEan)."'");
                    } elseif (strpos($sku, 'A_MAG') === 0) {
                        Db::getInstance()->execute("UPDATE `"._DB_PREFIX_."wyprzedazpro_product_details` SET quantity_wms = quantity_wms + ".$qty." WHERE sku = '".pSQL($sku)."'");
                    }
                }
            }
            Db::getInstance()->delete('modulzamowien_extra_items', 'id_extra = '.(int)$id_extra);
            return ['success' => true, 'restored_qty' => $qty, 'is_mag' => (int)$item['is_mag'], 'sku' => $item['mag_sku'], 'ean' => $item['ean']];
        }
        return ['success' => false];
    }

    public function clearItems($context)
    {
        $items = Db::getInstance()->executeS("SELECT * FROM `"._DB_PREFIX_."modulzamowien_extra_items`");
        if ($items && count($items) > 0) {
            $count = count($items); 
            $employee = $context->employee;
            $supplierName = '[EXTRA] RĘCZNE BRAKI - ' . date('d.m.Y H:i');
            Db::getInstance()->insert('modulzamowien_history', [
                'id_employee' => (int)$employee->id, 'employee_name' => pSQL($employee->firstname . ' ' . $employee->lastname),
                'supplier_name' => pSQL($supplierName), 'total_cost' => 0.00, 'items_count' => $count,
                'order_data' => pSQL(json_encode($items)), 'date_add' => date('Y-m-d H:i:s')
            ]);
            Db::getInstance()->execute("TRUNCATE TABLE `"._DB_PREFIX_."modulzamowien_extra_items`");
            return true;
        }
        return true;
    }
}