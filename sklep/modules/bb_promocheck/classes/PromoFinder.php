<?php

class PromoFinder
{
    public static function findData($product, $context)
    {
        // 1. Pobieramy EAN
        $ean = null;
        if (is_array($product) && isset($product['ean13'])) {
            $ean = $product['ean13'];
        } elseif (is_object($product) && isset($product->ean13)) {
            $ean = $product->ean13;
        }

        if (empty($ean)) {
            return false;
        }

        // 2. Szukamy produktu A_MAG_
        $searchPattern = 'A_MAG_' . $ean . '%';
        
        // DODANO: p.reference do zapytania, żeby wyciągnąć SKU
        $sql = 'SELECT p.id_product, p.reference, pl.link_rewrite, pl.name 
                FROM ' . _DB_PREFIX_ . 'product p
                LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl ON (p.id_product = pl.id_product AND pl.id_lang = ' . (int)$context->language->id . ')
                WHERE p.reference LIKE "' . pSQL($searchPattern) . '" 
                AND p.active = 1';
        
        $result = Db::getInstance()->getRow($sql);

        if (!$result) {
            return false;
        }

        $id_promo_product = (int)$result['id_product'];
        $reference = $result['reference'];
        
        // --- LOGIKA DATY ---
        // SKU: A_MAG_{EAN}_{DATE}_{OTHER}
        // Przykład: A_MAG_4005967005392_01012026_(H_5)
        // Rozbijamy po "_"
        $parts = explode('_', $reference);
        $formatted_date = '';
        
        // Zazwyczaj data to 4. element (index 3), bo: 0:A, 1:MAG, 2:EAN, 3:DATA
        if (isset($parts[3]) && strlen($parts[3]) === 8 && is_numeric($parts[3])) {
            $raw_date = $parts[3]; // np 01012026
            // Formatujemy na DD.MM.YYYY
            $formatted_date = substr($raw_date, 0, 2) . '.' . substr($raw_date, 2, 2) . '.' . substr($raw_date, 4, 4);
        }

        // Zabezpieczenie pętli
        $currentId = (is_array($product)) ? $product['id_product'] : $product->id;
        if ($id_promo_product == (int)$currentId) {
            return false;
        }

        // 3. Stan magazynowy
        $quantity_total_stock = (int)StockAvailable::getQuantityAvailableByProduct($id_promo_product, 0);
        if ($quantity_total_stock <= 0) {
            return false;
        }

        // 4. Koszyk
        $quantity_in_cart = 0;
        if (isset($context->cart) && $context->cart->getProducts()) {
            foreach ($context->cart->getProducts() as $cartProduct) {
                if ($cartProduct['id_product'] == $id_promo_product) {
                    $quantity_in_cart += (int)$cartProduct['cart_quantity'];
                }
            }
        }

        $qty_left_to_buy = $quantity_total_stock - $quantity_in_cart;
        if ($qty_left_to_buy < 0) $qty_left_to_buy = 0;

        // 5. Dane do widoku
        $link = new Link();
        $promo_url = $link->getProductLink($id_promo_product, $result['link_rewrite']);
        $price = Product::getPriceStatic($id_promo_product, true); 
        $formatted_price = Tools::displayPrice($price, $context->currency);

        return [
            'id_product'   => $id_promo_product,
            'name'         => $result['name'],
            'url'          => $promo_url,
            'price'        => $formatted_price,
            'qty_total'    => $quantity_total_stock,
            'qty_in_cart'  => $quantity_in_cart,
            'qty_left'     => $qty_left_to_buy,
            'expiry_date'  => $formatted_date // Przekazujemy datę
        ];
    }
}