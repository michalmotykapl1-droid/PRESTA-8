<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'wyprzedazpro/classes/WmsCalculator.php';

class WmsImportManager
{
    const TABLE_STAGING = 'wyprzedazpro_csv_staging';
    const TABLE_TASKS   = 'wyprzedazpro_finalize_tasks';
    const TABLE_DUPES   = 'wyprzedazpro_csv_duplikaty';
    const TABLE_DETAILS = 'wyprzedazpro_product_details';
    const TABLE_NOTF    = 'wyprzedazpro_not_found_products';
    const TABLE_HISTORY = 'wyprzedazpro_import_history';

    protected function varDir() {
        $dir = _PS_MODULE_DIR_.'wyprzedazpro/var';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        return $dir;
    }
    
    protected function getSessionPath($sid) {
        return $this->varDir().'/import_'.$sid.'.json';
    }
    
    public function writeSession($sid, $data) {
        return (bool)@file_put_contents($this->getSessionPath($sid), json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    public function readSession($sid) {
        $f = $this->getSessionPath($sid);
        return file_exists($f) ? json_decode(@file_get_contents($f), true) : null;
    }
    
    public function removeSession($sid) {
        $f = $this->getSessionPath($sid);
        if (file_exists($f)) @unlink($f);
    }

    public function startImport($fileData)
    {
        try {
            if (empty($fileData['tmp_name'])) throw new Exception('Brak pliku CSV.');
            
            $sid = uniqid('wy_', true);
            $dir = $this->varDir();
            $dest = $dir.'/upload_'.$sid.'.csv';
            
            if (!@move_uploaded_file($fileData['tmp_name'], $dest)) {
                if (!@copy($fileData['tmp_name'], $dest)) {
                    throw new Exception('Nie można zapisać pliku na serwerze.');
                }
            }
            
            $fh = fopen($dest,'r'); if(!$fh) throw new Exception('Nie można otworzyć pliku.');
            $header = null; $total = 0;
            while(($line=fgets($fh))!==false){ if(trim($line)==='') continue; $total++; if($header===null) $header=$line; }
            fclose($fh);
            $total_rows = ($total > 1) ? $total - 1 : 0;

            $delimiter = (strpos($header,';')!==false) ? ';' : ',';
            $fh = fopen($dest,'r'); $off=0; while(($line=fgets($fh))!==false){ if(trim($line)===''){ $off+=strlen($line); continue; } $off+=strlen($line); break; } fclose($fh);
            $batch = (int)Configuration::get('WYPRZEDAZPRO_IMPORT_BATCH', 500);
            $chunks = $total_rows > 0 ? (int)ceil($total_rows / max(1, $batch)) : 0;
            
            $state = [
                'file'=>$dest,'session_id'=>$sid,'delimiter'=>$delimiter,'offset'=>$off,'line'=>0,
                'total_rows'=>$total_rows,'processed'=>0,'in_db'=>0,'not_found'=>0,
                'chunks_total'=>$chunks,'chunks_done'=>0,'batch'=>$batch,'finished'=>false,
                'all_valid_skus' => [],
                'original_filename' => $fileData['name'],
            ];
            
            Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.self::TABLE_STAGING.'`');
            Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.self::TABLE_TASKS.'`');
            Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.self::TABLE_DUPES.'`');
            Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.self::TABLE_NOTF.'`');
            
            $this->writeSession($sid, $state);
            @file_put_contents($this->varDir().'/last_session.txt', $sid);
            
            return ['ok'=>true,'session_id'=>$sid,'total_rows'=>$state['total_rows'],'chunks_total'=>$chunks,'batch'=>$batch];
        } catch (Exception $e) {
            return ['ok'=>false,'error'=>$e->getMessage()];
        }
    }

    public function processChunk($sid)
    {
        try {
            $st = $this->readSession($sid);
            if (!$st) throw new Exception('Sesja nie istnieje.');

            $fh = fopen($st['file'], 'r');
            if (!$fh) throw new Exception('Nie można odczytać pliku.');
            if ($st['offset'] > 0) fseek($fh, $st['offset']);

            $map = ['EAN' => 0, 'KOD' => 1, 'Regał' => 2, 'Półka' => 3, 'DATA PRZYJĘCIA' => 4, 'DATA WAŻNOŚCI' => 5, 'STAN' => 6];
            $vals = []; $rows_read = 0; $inDb = 0; $nf = 0;

            for ($i = 0; $i < (int)$st['batch']; $i++) {
                $line = fgets($fh);
                if ($line === false) break;
                if (trim($line) === '') { $st['offset'] = ftell($fh); continue; }

                $st['line']++; $st['offset'] = ftell($fh); $rows_read++;
                $data = str_getcsv($line, $st['delimiter']);
                $ean = isset($data[$map['EAN']]) ? trim($data[$map['EAN']]) : '';
                if ($ean === '') continue;

                $qty = (int)($data[$map['STAN']] ?? 0);
                $rd = $data[$map['DATA PRZYJĘCIA']] ?? '';
                $ed = $data[$map['DATA WAŻNOŚCI']] ?? '';
                $reg = isset($data[$map['Regał']]) ? Tools::strtoupper(trim($data[$map['Regał']])) : '';
                $pol = isset($data[$map['Półka']]) ? Tools::strtoupper(trim($data[$map['Półka']])) : '';

                $ED = DateTime::createFromFormat('d.m.Y', $ed); if (!$ED) $ED = DateTime::createFromFormat('Y-m-d', $ed);
                $RD = DateTime::createFromFormat('d.m.Y', $rd); if (!$RD) $RD = DateTime::createFromFormat('Y-m-d', $rd);

                $vals[] = '("'.pSQL($sid).'", "'.pSQL($ean).'", '.(int)$qty.', "' . ($RD ? $RD->format('Y-m-d') : '0000-00-00') . '", "' . ($ED ? $ED->format('Y-m-d') : '0000-00-00') . '", "' . pSQL($reg) . '", "'. pSQL($pol) .'")';

                if (Product::getIdByEan13($ean)) $inDb++; else $nf++;

                if (count($vals) >= 100) {
                    Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.self::TABLE_STAGING.'` (`session_id`,`ean`,`quantity`,`receipt_date`,`expiry_date`,`regal`,`polka`) VALUES ' . implode(',', $vals));
                    $vals = [];
                }
            }

            if (!empty($vals)) {
                Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.self::TABLE_STAGING.'` (`session_id`,`ean`,`quantity`,`receipt_date`,`expiry_date`,`regal`,`polka`) VALUES ' . implode(',', $vals));
            }
            fclose($fh);

            $st['processed'] += $rows_read; $st['in_db'] += $inDb; $st['not_found'] += $nf;
            if ($rows_read > 0) $st['chunks_done'] += 1;

            clearstatcache(true, $st['file']);
            $done = (($st['total_rows'] > 0 && $st['processed'] >= $st['total_rows']) || (@filesize($st['file']) && $st['offset'] >= @filesize($st['file'])));
            $st['finished'] = $done;
            $this->writeSession($sid, $st);

            $payload = ['ok'=>true, 'processed'=>$st['processed'], 'in_db'=>$st['in_db'], 'not_found'=>$st['not_found'], 'chunks_done'=>$st['chunks_done'], 'chunks_total'=>$st['chunks_total']];
            if ($done) { $payload['done'] = true; $payload['stage'] = 'staging_complete'; } else { $payload['done'] = false; }
            return $payload;

        } catch (Exception $e) { return ['ok'=>false, 'error'=>$e->getMessage()]; }
    }

    public function finalizeStart($sid)
    {
        try {
            $st = $this->readSession($sid); if(!$st) throw new Exception('Brak sesji.');
            $rows = Db::getInstance()->executeS('SELECT ean, quantity, receipt_date, expiry_date, regal, polka FROM `'._DB_PREFIX_.self::TABLE_STAGING.'` WHERE session_id="'.pSQL($sid).'"') ?: [];

            $byKey=[]; $notFound=0; $notFoundData=[];
            foreach($rows as $r){
                if(!Product::getIdByEan13($r['ean'])){ $notFound++; $notFoundData[]=$r; continue; }
                $key=$r['ean'].'||'.$r['expiry_date'];
                $loc=Tools::strtoupper(trim($r['regal'])).'|'.Tools::strtoupper(trim($r['polka']));
                if(!isset($byKey[$key])) $byKey[$key]=[];
                if(!isset($byKey[$key][$loc])) $byKey[$key][$loc]=$r;
                else $byKey[$key][$loc]['quantity'] += (int)$r['quantity'];
            }

            Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.self::TABLE_DUPES.'`');
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.self::TABLE_TASKS.'` WHERE session_id="'.pSQL($sid).'"');
            
            $valsDup=[]; $valsTask=[];
            foreach($byKey as $k=>$locs) {
                $entries=array_values($locs); if(empty($entries)) continue;
                if(count($entries)>1) {
                    foreach($entries as $e) $valsDup[]='("'.pSQL($e['ean']).'", '.(int)$e['quantity'].', "'.pSQL($e['receipt_date']).'", "'.pSQL($e['expiry_date']).'", "'.pSQL($e['regal']).'", "'.pSQL($e['polka']).'")';
                    $total_q=0; foreach($entries as $e) $total_q+=(int)$e['quantity'];
                    $rec=$entries[0];
                    $valsTask[]='("'.pSQL($sid).'","'.pSQL($rec['ean']).'",'.(int)$total_q.',"'.pSQL($rec['receipt_date']).'","'.pSQL($rec['expiry_date']).'","'.pSQL($rec['regal']).'","'.pSQL($rec['polka']).'",0)';
                } else {
                    $rec=$entries[0];
                    $valsTask[]='("'.pSQL($sid).'","'.pSQL($rec['ean']).'",'.(int)$rec['quantity'].',"'.pSQL($rec['receipt_date']).'","'.pSQL($rec['expiry_date']).'","'.pSQL($rec['regal']).'","'.pSQL($rec['polka']).'",0)';
                }
            }
            if(!empty($valsDup)) Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.self::TABLE_DUPES.'` (`ean`,`quantity`,`receipt_date`,`expiry_date`,`regal`,`polka`) VALUES '.implode(',',$valsDup));
            if(!empty($valsTask)) Db::getInstance()->execute('INSERT INTO `'._DB_PREFIX_.self::TABLE_TASKS.'` (`session_id`,`ean`,`quantity`,`receipt_date`,`expiry_date`,`regal`,`polka`,`status`) VALUES '.implode(',',$valsTask));
            
            Db::getInstance()->execute('TRUNCATE TABLE `'._DB_PREFIX_.self::TABLE_NOTF.'`');
            if(!empty($notFoundData)){
                $now=date('Y-m-d H:i:s');
                foreach($notFoundData as $p) Db::getInstance()->insert(self::TABLE_NOTF, ['ean'=>pSQL($p['ean']),'quantity'=>(int)$p['quantity'],'receipt_date'=>pSQL($p['receipt_date']),'expiry_date'=>pSQL($p['expiry_date']),'regal'=>pSQL($p['regal']),'polka'=>pSQL($p['polka']),'date_add'=>$now], false, true, Db::INSERT, true);
            }

            $totalTasks = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.self::TABLE_TASKS.'` WHERE session_id="'.pSQL($sid).'"');
            $st['finalize_total']=$totalTasks; $st['not_found']=$notFound; $this->writeSession($sid,$st);
            return ['ok'=>true,'finalize_total'=>$totalTasks,'not_found'=>$notFound];
        } catch (Exception $e) { return ['ok'=>false,'error'=>$e->getMessage()]; }
    }

    public function finalizeChunk($sid, $id_shop)
    {
        try {
            $batch = (int)Configuration::get('WYPRZEDAZPRO_FINALIZE_BATCH', 100);
            $rows = Db::getInstance()->executeS('SELECT id, ean, quantity, receipt_date, expiry_date, regal, polka FROM `'._DB_PREFIX_.self::TABLE_TASKS.'` WHERE session_id="'.pSQL($sid).'" AND status=0 LIMIT '.(int)$batch) ?: [];
            if(empty($rows)) {
                $done=(int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.self::TABLE_TASKS.'` WHERE session_id="'.pSQL($sid).'" AND status=1');
                $total=(int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.self::TABLE_TASKS.'` WHERE session_id="'.pSQL($sid).'"');
                return ['ok'=>true,'done'=>true,'finalize_done'=>$done,'finalize_total'=>$total];
            }
            $pack=[];
            foreach($rows as $r) {
                $pack[]=[
                    'ean'=>$r['ean'], 'quantity'=>(int)$r['quantity'],
                    'receipt_date'=>($r['receipt_date'] && $r['receipt_date']!='0000-00-00')?date('d.m.Y',strtotime($r['receipt_date'])):'',
                    'expiry_date'=>($r['expiry_date'] && $r['expiry_date']!='0000-00-00')?date('d.m.Y',strtotime($r['expiry_date'])):'',
                    'regal'=>$r['regal'], 'polka'=>$r['polka']
                ];
            }
            
            $validSkus = $this->processSmartGroupedImport($pack, $id_shop);
            
            $st = $this->readSession($sid);
            if(isset($st['all_valid_skus'])) $st['all_valid_skus'] = array_merge($st['all_valid_skus'], $validSkus); else $st['all_valid_skus'] = $validSkus;
            $this->writeSession($sid, $st);

            $ids = implode(',', array_map('intval', array_column($rows,'id')));
            Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.self::TABLE_TASKS.'` SET status=1 WHERE id IN ('.$ids.')');
            
            $done=(int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.self::TABLE_TASKS.'` WHERE session_id="'.pSQL($sid).'" AND status=1');
            $total=(int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.self::TABLE_TASKS.'` WHERE session_id="'.pSQL($sid).'"');
            
            return ['ok'=>true,'done'=>($done>=$total),'finalize_done'=>$done,'finalize_total'=>$total,'created_or_updated'=>$done,'not_found'=>$st['not_found']];
        } catch (Exception $e) { return ['ok'=>false,'error'=>$e->getMessage()]; }
    }

    public function finalizeFinish($sid, $id_shop)
    {
        try {
            $st = $this->readSession($sid);
            if (isset($st['all_valid_skus']) && is_array($st['all_valid_skus'])) {
                $dbItems = Db::getInstance()->executeS("SELECT id_product, sku FROM `" . _DB_PREFIX_ . self::TABLE_DETAILS . "`");
                foreach ($dbItems as $item) {
                    if (!in_array($item['sku'], $st['all_valid_skus'])) {
                        $isManual = (int)Db::getInstance()->getValue("SELECT is_manual FROM `" . _DB_PREFIX_ . self::TABLE_DETAILS . "` WHERE id_product = " . (int)$item['id_product']);
                        if ($isManual == 1) continue;
                        Db::getInstance()->delete(self::TABLE_DETAILS, 'id_product='.(int)$item['id_product']);
                        $p = new Product((int)$item['id_product'], false, null, $id_shop);
                        if (Validate::isLoadedObject($p)) { StockAvailable::setQuantity((int)$item['id_product'], 0, 0, $id_shop); $p->active = 0; $p->save(); }
                    }
                }
            }
            $created = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `'._DB_PREFIX_.self::TABLE_TASKS.'` WHERE session_id="'.pSQL($sid).'" AND status=1');
            $this->logImportHistory($st['original_filename'], $st['total_rows'], $st['in_db'], $st['not_found']);
            Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.self::TABLE_TASKS.'` WHERE session_id="'.pSQL($sid).'"');
            $this->removeSession($sid);
            return ['ok'=>true, 'created_or_updated'=>$created, 'not_found'=>$st['not_found']];
        } catch (Exception $e) { return ['ok'=>false,'error'=>$e->getMessage()]; }
    }

    private function processSmartGroupedImport($importedData, $id_shop)
    {
        $mainSaleCategoryId = 45;
        $shortDateCategoryId = 180;
        $shortDateThreshold = (int)Configuration::get('WYPRZEDAZPRO_SHORT_DATE_DAYS', 14);
        $ignoreBinExpiry = (bool)Configuration::get('WYPRZEDAZPRO_IGNORE_BIN_EXPIRY');
        $id_lang = (int)Context::getContext()->language->id;
        $today = new DateTime(); $today->setTime(0,0,0);
        $grouped = []; $validSkus = [];

        foreach ($importedData as $row) {
            $ean = trim($row['ean']); if(empty($ean)) continue;
            $date = DateTime::createFromFormat('d.m.Y', $row['expiry_date']); if(!$date) $date = DateTime::createFromFormat('Y-m-d', $row['expiry_date']); if(!$date) continue;
            $days = ($date >= $today) ? $today->diff($date)->days : 0;
            $waznosc_key = $date->format('d.m.Y');
            $dataToStore = ['stan' => 0, 'data_przyjecia' => $row['receipt_date'], 'regal' => mb_strtoupper($row['regal'], 'UTF-8'), 'polka' => mb_strtoupper($row['polka'], 'UTF-8')];
            
            if (trim($dataToStore['regal']) === 'KOSZ') {
                if (!isset($grouped[$ean]['bin'][$waznosc_key])) $grouped[$ean]['bin'][$waznosc_key] = $dataToStore;
                $grouped[$ean]['bin'][$waznosc_key]['stan'] += (int)$row['quantity'];
            } elseif ($days < $shortDateThreshold) {
                if (!isset($grouped[$ean]['short'][$waznosc_key])) $grouped[$ean]['short'][$waznosc_key] = $dataToStore;
                $grouped[$ean]['short'][$waznosc_key]['stan'] += (int)$row['quantity'];
            } else {
                if (!isset($grouped[$ean]['long'][$waznosc_key])) $grouped[$ean]['long'][$waznosc_key] = $dataToStore;
                $grouped[$ean]['long'][$waznosc_key]['stan'] += (int)$row['quantity'];
            }
        }

        foreach ($grouped as $ean => $set) {
            $id_product = Product::getIdByEan13($ean); if (!$id_product) continue;
            $originalProduct = new Product($id_product, true, $id_lang, $id_shop);
            $process_sets = ['bin', 'short', 'long'];
            foreach ($process_sets as $type) {
                if (!empty($set[$type])) {
                    foreach ($set[$type] as $data_waznosci => $data) {
                        $productIdToSave = 0; $suma = $data['stan']; $data_przyjecia = $data['data_przyjecia']; $regal = $data['regal']; $polka = $data['polka'];
                        $skuSuffix = str_replace('.', '', $data_waznosci) . '_(' . $regal . '_' . $polka . ')';
                        $saleProductSku = 'A_MAG_' . $ean . '_' . $skuSuffix;
                        $validSkus[] = $saleProductSku;
                        $idSaleProduct = Product::getIdByReference($saleProductSku);
                        
                        if ($type === 'bin') $targetCategoryId = $ignoreBinExpiry ? $mainSaleCategoryId : $shortDateCategoryId;
                        elseif ($type === 'short') $targetCategoryId = $shortDateCategoryId;
                        else $targetCategoryId = $mainSaleCategoryId;

                        $discount = WmsCalculator::getDiscountByDates($data_waznosci, $data_przyjecia, $regal);
                        $description = is_array($originalProduct->description) ? ($originalProduct->description[$id_lang] ?? '') : $originalProduct->description;
                        $description = preg_replace('/<p>DATA WAŻNOŚCI:.*<\/p>/i', '', $description);
                        $description = preg_replace('/<p><strong>UWAGA:<\/strong>.*<\/p>/i', '', $description);
                        $expiry_text = '<p>DATA WAŻNOŚCI: ' . $data_waznosci . '</p>';
                        $note = ($type === 'bin') ? '<p><strong>UWAGA:</strong> Produkt nie podlega zwrotowi ze względu na uszkodzenie/koniec terminu ważności.</p>' : (($type === 'short') ? '<p><strong>UWAGA:</strong> Produkt nie podlega zwrotowi ze względu na krótką datę ważności.</p>' : '');

                        if ($idSaleProduct) {
                            $productIdToSave = (int)$idSaleProduct;
                            $updProduct = new Product($productIdToSave, true, $id_lang, $id_shop);
                            $updProduct->price = $originalProduct->price; $updProduct->wholesale_price = $originalProduct->wholesale_price; $updProduct->active = 1;
                            $updProduct->id_category_default = $targetCategoryId;
                            $updProduct->description = []; $updProduct->description[$id_lang] = $description . $expiry_text . $note;
                            $updProduct->save();
                            $updProduct->setWsCategories([['id' => $targetCategoryId]]);
                            StockAvailable::setQuantity($productIdToSave, 0, (int)$suma, $id_shop);
                        } else {
                            $newProduct = $originalProduct->duplicateObject();
                            if (!Validate::isLoadedObject($newProduct)) continue;
                            $productIdToSave = (int)$newProduct->id;
                            $newProduct->price = $originalProduct->price; $newProduct->active = true; $newProduct->indexed = 0; $newProduct->id_tax_rules_group = $originalProduct->id_tax_rules_group;
                            $newProduct->id_category_default = $targetCategoryId;
                            $newProduct->reference = $saleProductSku; 
                            $newProduct->description = []; $newProduct->description[$id_lang] = $description . $expiry_text . $note;
                            $newProduct->save();
                            $newProduct->setWsCategories([['id' => $targetCategoryId]]);
                            StockAvailable::setQuantity($productIdToSave, 0, (int)$suma, $id_shop);
                            $this->copyProductImages($originalProduct, $newProduct);
                        }
                        if ($productIdToSave > 0) {
                            $this->updateSpecificPrice($productIdToSave, $discount, $id_shop);
                            $this->saveSaleProductDetails($productIdToSave, $ean, $data_waznosci, $data_przyjecia, $regal, $polka, $saleProductSku, (int)$suma);
                        }
                    }
                }
            }
        }
        return $validSkus;
    }

    private function updateSpecificPrice($id_product, $discount, $id_shop) {
        SpecificPrice::deleteByProductId((int)$id_product);
        if ($discount > 0) {
            $sp = new SpecificPrice(); $sp->id_product = (int)$id_product; $sp->id_shop = $id_shop;
            $sp->id_currency = 0; $sp->id_country = 0; $sp->id_group = 0; $sp->id_customer = 0;
            $sp->price = -1; $sp->from_quantity = 1; $sp->reduction_type = 'percentage';
            $sp->reduction = $discount / 100;
            $sp->from = '0000-00-00 00:00:00'; $sp->to = '0000-00-00 00:00:00';
            $sp->add();
        }
    }

    private function saveSaleProductDetails($id_product, $ean, $expiry_date_str, $receipt_date_str, $regal, $polka, $sku, $quantity_wms) {
        $expiryDate = DateTime::createFromFormat('d.m.Y', $expiry_date_str); if(!$expiryDate) $expiryDate = DateTime::createFromFormat('Y-m-d', $expiry_date_str);
        $receiptDate = DateTime::createFromFormat('d.m.Y', $receipt_date_str); if(!$receiptDate) $receiptDate = DateTime::createFromFormat('Y-m-d', $receipt_date_str);
        $data = ['id_product' => (int)$id_product, 'ean' => pSQL($ean), 'expiry_date' => $expiryDate ? $expiryDate->format('Y-m-d') : null, 'receipt_date' => $receiptDate ? $receiptDate->format('Y-m-d') : null, 'regal' => pSQL($regal), 'polka' => pSQL($polka), 'sku' => pSQL($sku), 'quantity_wms' => (int)$quantity_wms];
        return Db::getInstance()->insert(self::TABLE_DETAILS, $data, false, true, Db::REPLACE);
    }

    private function logImportHistory($filename, $rowsTotal, $rowsInDb, $rowsNotFound) {
        $data = ['date_add' => date('Y-m-d H:i:s'), 'filename' => pSQL((string)$filename), 'rows_total' => (int)$rowsTotal, 'rows_in_db' => (int)$rowsInDb, 'rows_not_found' => (int)$rowsNotFound, 'id_shop' => (int)Context::getContext()->shop->id, 'id_employee' => (int)Context::getContext()->employee->id];
        Db::getInstance()->insert(self::TABLE_HISTORY, $data);
    }

    private function copyProductImages(Product $originalProduct, Product $newProduct) {
        $images = $originalProduct->getImages((int)Context::getContext()->language->id); if (empty($images)) return true;
        foreach ($images as $image_data) {
            $oldImage = new Image($image_data['id_image']); $formats = ['jpg', 'png', 'webp', 'jpeg']; $oldPath = null; $format = null;
            foreach ($formats as $f) { $tryPath = _PS_PROD_IMG_DIR_ . Image::getImgFolderStatic($oldImage->id) . $oldImage->id . '.' . $f; if (file_exists($tryPath)) { $oldPath = $tryPath; $format = $f; break; } }
            if (!$oldPath) continue;
            $newImage = new Image(); $newImage->id_product = (int)$newProduct->id; $newImage->position = Image::getHighestPosition((int)$newProduct->id) + 1; $newImage->cover = (bool)$image_data['cover']; $newImage->image_format = $format;
            if (!$newImage->add()) continue;
            $imgFolder = _PS_PROD_IMG_DIR_ . Image::getImgFolderStatic($newImage->id); if (!is_dir($imgFolder)) mkdir($imgFolder, 0777, true);
            $newPath = $imgFolder . $newImage->id . '.' . $format;
            if (!ImageManager::resize($oldPath, $newPath, null, null, $format, true)) continue;
            $types = ImageType::getImagesTypes('products');
            foreach ($types as $type) { $thumbPath = $imgFolder . $newImage->id . '-' . $type['name'] . '.' . $format; ImageManager::resize($newPath, $thumbPath, (int)$type['width'], (int)$type['height'], $format); }
            $newImage->update();
        }
        return true;
    }
}