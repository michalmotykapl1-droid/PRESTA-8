<?php
/**
 * Ścieżka: /modules/wyprzedazpro/classes/Api/ReceptionService.php
 * Poprawka: Przepisywanie VAT (id_tax_rules_group) z produktu oryginalnego.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class ReceptionService
{
    const CAT_SALE = 45;
    const CAT_SHORT_DATE = 180;

    /**
     * KROK 1: Wrzut do tabeli staging
     */
    public function insertToStaging($ean, $qty, $regal, $polka, $expiryDateStr)
    {
        if ((int)$qty <= 0) return ['success' => false, 'msg' => 'Ilość musi być > 0'];
        if (empty($ean)) return ['success' => false, 'msg' => 'Brak EAN'];

        $session_id = 'SCANNER_WAITING'; 
        $receipt_date = date('Y-m-d');   
        $expiry_date = empty($expiryDateStr) ? '0000-00-00' : $expiryDateStr;

        $data = [
            'session_id' => pSQL($session_id),
            'ean'        => pSQL($ean),
            'quantity'   => (int)$qty,
            'receipt_date' => pSQL($receipt_date),
            'expiry_date'  => pSQL($expiry_date),
            'regal'      => pSQL(mb_strtoupper($regal)),
            'polka'      => pSQL(mb_strtoupper($polka))
        ];

        $res = Db::getInstance()->insert('wyprzedazpro_csv_staging', $data);

        if ($res) {
            return ['success' => true, 'msg' => 'Zapisano w buforze.'];
        } else {
            return ['success' => false, 'msg' => 'Błąd zapisu do tabeli staging.'];
        }
    }

    /**
     * KROK 2: Przetwarza dane z tabeli staging (AUTOMAT)
     */
    public function processScannerQueue($id_shop)
    {
        $session_id = 'SCANNER_WAITING';
        
        $sql = new DbQuery();
        $sql->select('*');
        $sql->from('wyprzedazpro_csv_staging');
        $sql->where('session_id = "' . pSQL($session_id) . '"');
        
        $rows = Db::getInstance()->executeS($sql);

        if (empty($rows)) {
            return ['success' => true, 'msg' => 'Brak danych do przetworzenia.'];
        }

        $processed = 0;
        
        foreach ($rows as $row) {
            try {
                $this->processSingleRow($row, $id_shop);
                Db::getInstance()->delete('wyprzedazpro_csv_staging', 'id = '.(int)$row['id']);
                $processed++;
            } catch (Exception $e) {
                Db::getInstance()->delete('wyprzedazpro_csv_staging', 'id = '.(int)$row['id']);
            }
        }

        return ['success' => true, 'msg' => 'Przetworzono produktów: ' . $processed];
    }

    // --- LOGIKA BIZNESOWA (POPRAWIONA O VAT) ---
    private function processSingleRow($row, $id_shop)
    {
        $ean = $row['ean'];
        $qty = (int)$row['quantity'];
        
        // 1. Walidacja bazy - pobieramy ORYGINALNY PRODUKT
        $id_base_product = (int)\Product::getIdByEan13($ean);
        if (!$id_base_product) return; 

        $id_lang = (int)\Context::getContext()->language->id;
        
        // Ładujemy oryginał (z niego weźmiemy VAT)
        $originalProduct = new \Product($id_base_product, true, null, $id_shop);

        // 2. Dane
        $regal = mb_strtoupper(trim($row['regal']), 'UTF-8');
        $polka = mb_strtoupper(trim($row['polka']), 'UTF-8');
        $dateWaznosci = new \DateTime($row['expiry_date']);
        $datePrzyjecia = new \DateTime($row['receipt_date']);

        // 3. SKU
        $skuSuffix = $dateWaznosci->format('dmY') . '_(' . $regal . '_' . $polka . ')';
        $saleProductSku = 'A_MAG_' . $ean . '_' . $skuSuffix;

        // 4. Kategoria
        $isBin = ($regal === 'KOSZ');
        $ignoreBinExpiry = (bool)\Configuration::get('WYPRZEDAZPRO_IGNORE_BIN_EXPIRY');
        $today = new \DateTime(); $today->setTime(0,0,0);
        $daysToExpiry = ($dateWaznosci >= $today) ? $today->diff($dateWaznosci)->days : 0;
        $shortThreshold = (int)\Configuration::get('WYPRZEDAZPRO_SHORT_DATE_DAYS', 14);

        if ($isBin) $targetCategoryId = $ignoreBinExpiry ? self::CAT_SALE : self::CAT_SHORT_DATE;
        elseif ($daysToExpiry < $shortThreshold) $targetCategoryId = self::CAT_SHORT_DATE;
        else $targetCategoryId = self::CAT_SALE;

        // 5. Opis
        $rawDesc = is_array($originalProduct->description) ? ($originalProduct->description[$id_lang] ?? '') : $originalProduct->description;
        $rawDesc = preg_replace('/<p>DATA WAŻNOŚCI:.*<\/p>/i', '', $rawDesc);
        $rawDesc = preg_replace('/<p><strong>UWAGA:<\/strong>.*<\/p>/i', '', $rawDesc);
        $expiry_text = '<p>DATA WAŻNOŚCI: ' . $dateWaznosci->format('d.m.Y') . '</p>';
        $note = $isBin ? '<p><strong>UWAGA:</strong> Produkt nie podlega zwrotowi ze względu na uszkodzenie/koniec terminu ważności.</p>' : ($daysToExpiry < $shortThreshold ? '<p><strong>UWAGA:</strong> Produkt nie podlega zwrotowi ze względu na krótką datę ważności.</p>' : '');
        $finalDescription = $rawDesc . $expiry_text . $note;

        // 6. Zapis / Aktualizacja
        $idSaleProduct = (int)\Product::getIdByReference($saleProductSku);
        
        if ($idSaleProduct) {
            $product = new \Product($idSaleProduct, true, null, $id_shop);
        } else {
            $product = $originalProduct->duplicateObject();
            if (!\Validate::isLoadedObject($product)) return;
            $product->reference = $saleProductSku;
            $this->copyImages($originalProduct, $product);
            $this->copyFeatures($originalProduct->id, $product->id);
        }

        // --- FIX VAT: PRZEPISUJEMY REGUŁĘ PODATKOWĄ Z ORYGINAŁU ---
        $product->id_tax_rules_group = (int)$originalProduct->id_tax_rules_group;
        // -----------------------------------------------------------

        // Cena
        $basePrice = (float)$originalProduct->price;
        if ($basePrice <= 0.000001) $basePrice = 0.01;
        $product->price = $basePrice;
        $product->wholesale_price = (float)$originalProduct->wholesale_price;
        
        $product->active = 1;
        $product->id_category_default = $targetCategoryId;
        $product->description = [$id_lang => $finalDescription]; 
        
        $product->save();

        $product->setWsCategories([['id' => $targetCategoryId]]);
        \StockAvailable::setQuantity($product->id, 0, $qty, $id_shop);

        // 7. Rabat i Szczegóły WMS
        $discount = $this->calculateDiscount($dateWaznosci, $datePrzyjecia, $isBin, $daysToExpiry);
        $this->updateSpecificPrice($product->id, $discount, $id_shop);
        
        // Zapis do tabeli detali (is_manual = 1)
        $this->saveDetails($product->id, $ean, $dateWaznosci, $datePrzyjecia, $regal, $polka, $saleProductSku, $qty, 1);
    }

    // --- HELPERY ---
    private function calculateDiscount($dateWaznosci, $datePrzyjecia, $isBin, $daysToExpiry) {
        if ($isBin) return (float)\Configuration::get('WYPRZEDAZPRO_DISCOUNT_BIN');
        $veryShort = (float)\Configuration::get('WYPRZEDAZPRO_DISCOUNT_VERY_SHORT');
        $shortThreshold = (int)\Configuration::get('WYPRZEDAZPRO_SHORT_DATE_DAYS', 14);
        if ($daysToExpiry < 7) return $veryShort;
        elseif ($daysToExpiry < $shortThreshold) return (float)\Configuration::get('WYPRZEDAZPRO_DISCOUNT_SHORT');
        $today = new \DateTime(); $today->setTime(0,0,0);
        $daysSinceReceipt = ($datePrzyjecia <= $today) ? $datePrzyjecia->diff($today)->days : 0;
        if (\Configuration::get('WYPRZEDAZPRO_ENABLE_OVER90_LONGEXP') && $daysSinceReceipt > 90 && $daysToExpiry >= 180) return (float)\Configuration::get('WYPRZEDAZPRO_DISCOUNT_OVER90_LONGEXP');
        elseif ($daysSinceReceipt <= 30) return (float)\Configuration::get('WYPRZEDAZPRO_DISCOUNT_30');
        elseif ($daysSinceReceipt <= 90) return (float)\Configuration::get('WYPRZEDAZPRO_DISCOUNT_90');
        else return (float)\Configuration::get('WYPRZEDAZPRO_DISCOUNT_OVER');
    }

    private function updateSpecificPrice($id_product, $discount, $id_shop) {
        \SpecificPrice::deleteByProductId((int)$id_product);
        if ($discount > 0) {
            $sp = new \SpecificPrice();
            $sp->id_product = (int)$id_product; $sp->id_shop = $id_shop;
            $sp->id_currency = 0; $sp->id_country = 0; $sp->id_group = 0; $sp->id_customer = 0;
            $sp->price = -1; $sp->from_quantity = 1; $sp->reduction_type = 'percentage';
            $sp->reduction = $discount / 100;
            $sp->from = '0000-00-00 00:00:00'; $sp->to = '0000-00-00 00:00:00';
            $sp->add();
        }
    }

    private function copyFeatures($id_source, $id_dest) {
        $features = \Product::getFeaturesStatic((int)$id_source);
        if (!empty($features)) {
            \Db::getInstance()->delete('feature_product', 'id_product='.(int)$id_dest);
            foreach ($features as $feat) {
                if (!empty($feat['id_feature']) && !empty($feat['id_feature_value'])) \Product::addFeatureProductImport((int)$id_dest, (int)$feat['id_feature'], (int)$feat['id_feature_value']);
            }
        }
    }

    private function copyImages($origProd, $newProd) {
        $id_lang = (int)\Context::getContext()->language->id;
        $images = $origProd->getImages($id_lang);
        if (empty($images)) return;
        foreach ($images as $imgData) {
            $oldImage = new \Image($imgData['id_image']);
            $newImage = new \Image();
            $newImage->id_product = (int)$newProd->id;
            $newImage->position = \Image::getHighestPosition((int)$newProd->id) + 1;
            $newImage->cover = (bool)$imgData['cover'];
            if (!$newImage->add()) continue;
            $pathOld = _PS_PROD_IMG_DIR_ . \Image::getImgFolderStatic($oldImage->id) . $oldImage->id . '.jpg';
            $folderNew = _PS_PROD_IMG_DIR_ . \Image::getImgFolderStatic($newImage->id);
            if (!file_exists($folderNew)) @mkdir($folderNew, 0777, true);
            $pathNew = $folderNew . $newImage->id . '.jpg';
            if (file_exists($pathOld)) {
                @copy($pathOld, $pathNew);
                $imagesTypes = \ImageType::getImagesTypes('products');
                foreach ($imagesTypes as $type) \ImageManager::resize($pathNew, $folderNew . $newImage->id . '-' . $type['name'] . '.jpg', $type['width'], $type['height']);
            }
        }
    }

    private function saveDetails($id_product, $ean, $dateExp, $dateRec, $regal, $polka, $sku, $qty, $is_manual = 0) {
        $data = [
            'id_product' => (int)$id_product, 'ean' => pSQL($ean),
            'expiry_date' => $dateExp->format('Y-m-d'), 'receipt_date' => $dateRec->format('Y-m-d'),
            'regal' => pSQL($regal), 'polka' => pSQL($polka), 'sku' => pSQL($sku),
            'quantity_wms' => (int)$qty, 'is_manual' => (int)$is_manual
        ];
        \Db::getInstance()->insert('wyprzedazpro_product_details', $data, false, true, \Db::REPLACE);
    }
}