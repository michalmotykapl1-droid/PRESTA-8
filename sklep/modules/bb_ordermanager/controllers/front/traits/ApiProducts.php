<?php
trait ApiProducts
{
    protected function searchProducts() {
        $query = trim(Tools::getValue('query'));
        if (strlen($query) < 3) { echo json_encode(['success'=>true, 'products'=>[]]); die(); }

        $db = Db::getInstance();
        $id_lang = (int)$this->context->language->id;
        $context = Context::getContext();
        
        // Rozbijamy zapytanie na słowa, żeby znaleźć "Czekolada Torras" 
        // nawet jeśli w bazie jest "Torras Czekolada Gorzka"
        $words = explode(' ', $query);
        $whereClauses = [];

        foreach ($words as $word) {
            $word = pSQL(trim($word));
            if (!empty($word)) {
                // Każde wpisane słowo musi pasować do Nazwy LUB Indeksu LUB EAN LUB Producenta
                $whereClauses[] = "(
                    pl.name LIKE '%$word%' OR 
                    p.reference LIKE '%$word%' OR 
                    p.ean13 LIKE '%$word%' OR 
                    m.name LIKE '%$word%'
                )";
            }
        }
        $whereSql = implode(' AND ', $whereClauses);

        $sql = 'SELECT p.id_product, pl.name, p.reference, p.ean13, p.price, t.rate as tax_rate, 
                       pl.link_rewrite, i.id_image, m.name as manufacturer_name
                FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . $id_lang . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (p.id_manufacturer = m.id_manufacturer)
                LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (p.id_product = i.id_product AND i.cover = 1)
                LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON (p.id_tax_rules_group = tr.id_tax_rules_group AND tr.id_country = ' . (int)Configuration::get('PS_COUNTRY_DEFAULT') . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'tax` t ON (tr.id_tax = t.id_tax)
                WHERE ' . $whereSql . '
                GROUP BY p.id_product
                LIMIT 20';
        
        $results = $db->executeS($sql);
        $products = [];
        
        foreach ($results as $row) {
            $tax_rate = $row['tax_rate'] ? (float)$row['tax_rate'] : 0;
            $price_net = (float)$row['price'];
            $price_gross = $price_net * (1 + ($tax_rate / 100));
            
            // Generowanie linku do zdjęcia
            $imageUrl = null;
            if ($row['id_image']) {
                $imageUrl = $context->link->getImageLink($row['link_rewrite'] ?? 'p', $row['id_product'] . '-' . $row['id_image'], 'small_default');
            }

            $products[] = [
                'id' => $row['id_product'],
                'name' => $row['name'],
                'ref' => $row['reference'],
                'ean' => $row['ean13'],
                'manufacturer' => $row['manufacturer_name'],
                'image_url' => $imageUrl,
                'price_gross' => round($price_gross, 2),
                'tax_rate' => $tax_rate
            ];
        }

        echo json_encode(['success' => true, 'products' => $products]);
        die();
    }

    protected function addProductToOrder() {
        $id_order = (int)Tools::getValue('id_order');
        $id_product = (int)Tools::getValue('id_product');
        $qty = (int)Tools::getValue('qty');
        
        if (!$id_order || !$id_product || $qty < 1) throw new Exception("Błędne dane");

        $db = Db::getInstance();
        $productObj = new Product($id_product, false, $this->context->language->id);
        
        $tax_rate = $productObj->getTaxesRate();
        $price_net = $productObj->price;
        $price_gross = $price_net * (1 + ($tax_rate / 100));
        
        $detail = new OrderDetail();
        $detail->id_order = $id_order;
        $detail->product_id = $id_product;
        $detail->product_attribute_id = 0; 
        $detail->product_name = $productObj->name;
        $detail->product_quantity = $qty;
        $detail->product_quantity_in_stock = $qty;
        $detail->product_price = $price_net;
        $detail->unit_price_tax_incl = $price_gross;
        $detail->unit_price_tax_excl = $price_net;
        $detail->total_price_tax_incl = $price_gross * $qty;
        $detail->total_price_tax_excl = $price_net * $qty;
        $detail->tax_rate = $tax_rate;
        $detail->product_reference = $productObj->reference;
        $detail->product_ean13 = $productObj->ean13;
        $detail->product_weight = $productObj->weight;
        $detail->id_shop = 1;
        $detail->id_warehouse = 0;
        
        if ($detail->add()) {
            $db->execute('UPDATE `' . _DB_PREFIX_ . 'order_detail` SET `date_upd` = NOW() WHERE `id_order_detail` = ' . (int)$detail->id);
            
            $this->recalculateOrderTotal($id_order);
            $this->addSystemLog(
                $id_order,
                'DODANO PRODUKT: ' . $productObj->name . ' (Ilość: ' . $qty . ', Brutto: ' . number_format($price_gross, 2) . ')',
                'PRODUCT_ADD',
                [
                    'id_product' => (int) $id_product,
                    'product_name' => (string) $productObj->name,
                    'qty' => (int) $qty,
                    'price_net' => (float) $price_net,
                    'price_gross' => (float) $price_gross,
                    'tax_rate' => (float) $tax_rate,
                ]
            );
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Błąd dodawania do bazy");
        }
        die();
    }

    protected function updateOrderProduct() {
        $id_detail = (int)Tools::getValue('id_order_detail');
        $qty = (int)Tools::getValue('qty');
        $price_net = (float)Tools::getValue('price_net');
        $tax_rate = (float)Tools::getValue('tax_rate');
        
        if (!$id_detail || $qty < 1) throw new Exception("Błędne dane");
        
        $db = Db::getInstance();
        $current = $db->getRow('SELECT id_order, product_name, product_quantity, unit_price_tax_excl, unit_price_tax_incl, tax_rate FROM `' . _DB_PREFIX_ . 'order_detail` WHERE id_order_detail = ' . $id_detail);
        if (!$current) throw new Exception("Nie znaleziono pozycji");
        
        $price_gross = $price_net * (1 + ($tax_rate / 100));
        
        // --- SZCZEGÓŁOWE LOGI ---
        $changes = [];
        $oldQty = (int)$current['product_quantity'];
        $oldNet = (float)$current['unit_price_tax_excl'];
        $oldTax = (float)$current['tax_rate'];
        $oldGross = (float)$current['unit_price_tax_incl'];

        if ($oldQty != $qty) {
            $changes[] = "ILOŚĆ: $qty (było $oldQty)";
        }
        if (abs($oldNet - $price_net) > 0.001) {
            $changes[] = "Netto: " . number_format($price_net, 2) . " (było " . number_format($oldNet, 2) . ")";
        }
        if (abs($oldTax - $tax_rate) > 0.001) {
            $changes[] = "VAT: " . (int)$tax_rate . "% (było " . (int)$oldTax . "%)";
        }
        if (abs($oldGross - $price_gross) > 0.001) {
            $changes[] = "Brutto: " . number_format($price_gross, 2) . " (było " . number_format($oldGross, 2) . ")";
        }

        $logMessage = 'EDYCJA PRODUKTU: ' . $current['product_name'];
        if (!empty($changes)) {
            $logMessage .= ' [' . implode(', ', $changes) . ']';
        } else {
            $logMessage .= ' [Brak zmian]';
        }
        // ----------------------------------------------

        $data = [
            'product_quantity' => $qty,
            'product_quantity_in_stock' => $qty,
            'tax_rate' => $tax_rate,
            'product_price' => $price_net,
            'unit_price_tax_excl' => $price_net,
            'unit_price_tax_incl' => $price_gross,
            'total_price_tax_excl' => $price_net * $qty,
            'total_price_tax_incl' => $price_gross * $qty,
            'date_upd' => date('Y-m-d H:i:s')
        ];
        
        $db->update('order_detail', $data, 'id_order_detail = ' . $id_detail);
        $this->recalculateOrderTotal((int)$current['id_order']);
        
        $this->addSystemLog(
            (int)$current['id_order'],
            $logMessage,
            'PRODUCT_EDIT',
            [
                'id_order_detail' => (int) $id_detail,
                'product_name' => (string) $current['product_name'],
                'old' => [
                    'qty' => (int) $oldQty,
                    'price_net' => (float) $oldNet,
                    'tax_rate' => (float) $oldTax,
                    'price_gross' => (float) $oldGross,
                ],
                'new' => [
                    'qty' => (int) $qty,
                    'price_net' => (float) $price_net,
                    'tax_rate' => (float) $tax_rate,
                    'price_gross' => (float) $price_gross,
                ],
            ]
        );
        
        echo json_encode(['success' => true]);
        die();
    }

    protected function deleteOrderProduct() {
        $id_detail = (int)Tools::getValue('id_order_detail');
        if (!$id_detail) throw new Exception("Brak ID");
        
        $db = Db::getInstance();
        $row = $db->getRow('SELECT id_order, product_name, product_quantity FROM `' . _DB_PREFIX_ . 'order_detail` WHERE id_order_detail = ' . $id_detail);
        
        if ($row) {
            $id_order = (int)$row['id_order'];
            $prodName = $row['product_name'];
            $qty = (int)$row['product_quantity'];

            $db->delete('order_detail', 'id_order_detail = ' . $id_detail);
            $db->delete('bb_ordermanager_packing', 'id_order_detail = ' . $id_detail);
            
            $this->recalculateOrderTotal($id_order);
            $this->addSystemLog(
                $id_order,
                'USUNIĘTO PRODUKT: ' . $prodName . ' (Ilość: ' . $qty . ')',
                'PRODUCT_DELETE',
                [
                    'id_order_detail' => (int) $id_detail,
                    'product_name' => (string) $prodName,
                    'qty' => (int) $qty,
                ]
            );
            
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Błąd usuwania");
        }
        die();
    }

    private function recalculateOrderTotal($id_order) {
        $db = Db::getInstance();
        $productTotal = (float)$db->getValue('SELECT SUM(total_price_tax_incl) FROM `' . _DB_PREFIX_ . 'order_detail` WHERE id_order = ' . (int)$id_order);
        $shipping = (float)$db->getValue('SELECT total_shipping_tax_incl FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order = ' . (int)$id_order);
        $newTotal = $productTotal + $shipping;
        
        $db->update('orders', [
            'total_products_wt' => $productTotal,
            'total_paid' => $newTotal,
            'total_paid_tax_incl' => $newTotal,
            'date_upd' => date('Y-m-d H:i:s')
        ], 'id_order = ' . (int)$id_order);
    }
}