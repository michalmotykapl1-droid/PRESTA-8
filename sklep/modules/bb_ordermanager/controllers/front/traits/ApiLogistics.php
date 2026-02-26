<?php
trait ApiLogistics
{
    // ZMIANA: private -> protected (dla bezpieczeństwa przy używaniu w traitach)
    protected function searchLockers() {
        $postcode = trim(Tools::getValue('postcode')); 
        $inputString = trim(Tools::getValue('city')); 
        $street = trim(Tools::getValue('street'));
        
        $token = (string) Configuration::get('INPOST_SHIPPING_GEOWIDGET_TOKEN');
        if (!$token) $token = (string) Configuration::get('INPOST_SHIPPING_GEOWIDGET_SANDBOX_TOKEN');
        if (!$token) $token = (string) Configuration::get('INPOST_GEOWIDGET_TOKEN');
        if (!$token) { 
            $sqlTok = 'SELECT `value` FROM `' . _DB_PREFIX_ . 'configuration` WHERE `name` LIKE "%GEOWIDGET%" AND `name` LIKE "%TOKEN%" ORDER BY `id_configuration` DESC'; 
            $token = (string) Db::getInstance()->getValue($sqlTok); 
        }
        $token = trim((string)$token);

        $lat = 52.2297; $lon = 21.0122; $foundGeo = false;
        
        // Zabezpieczenie przed błędami API Geocoding
        try {
            if (!empty($inputString)) { $query = urlencode($inputString . " Polska"); if ($this->getCoordinates($query, $lat, $lon)) $foundGeo = true; }
            if (!$foundGeo && !empty($inputString)) { $parts = explode(' ', $inputString); if (count($parts) > 1 && strlen($parts[0]) > 2) { $cityOnly = $parts[0]; $query = urlencode($cityOnly . " Polska"); if ($this->getCoordinates($query, $lat, $lon)) $foundGeo = true; } }
            if (!$foundGeo && !empty($postcode)) { $query = urlencode($postcode . " Polska"); if ($this->getCoordinates($query, $lat, $lon)) $foundGeo = true; }
        } catch (Exception $e) {
            // Ignorujemy błędy geokodowania, zostajemy przy domyślnym lat/lon
        }

        $relativePoint = $lat . ',' . $lon;
        $pointsUrl = 'https://api-pl-points.easypack24.net/v1/points?relative_point=' . $relativePoint . '&sort_by=distance_to_relative_point&sort_order=asc&max_distance=20000&limit=20&type=parcel_locker,pop&functions=parcel_collect';
        
        $ch = curl_init(); curl_setopt($ch, CURLOPT_URL, $pointsUrl); curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3); curl_setopt($ch, CURLOPT_TIMEOUT, 5); $pointsJson = curl_exec($ch); curl_close($ch);
        
        $pointsData = json_decode($pointsJson, true); $points = [];
        
        if (isset($pointsData['items']) && is_array($pointsData['items'])) { 
            foreach ($pointsData['items'] as $item) { 
                $distKm = null; 
                if (isset($item['distance']) && is_numeric($item['distance'])) { 
                    $distKm = ((float)$item['distance']) / 1000; 
                } else { 
                    $distKm = $this->calculateDistance($lat, $lon, $item['location']['latitude'], $item['location']['longitude']); 
                } 
                $distKm = round($distKm, 2); 
                $img = 'https://geowidget.inpost.pl/images/logos/paczkomat.png'; 
                if (!empty($item['image_url'])) $img = $item['image_url']; 
                
                $points[] = [ 
                    'name' => $item['name'], 
                    'desc' => isset($item['location_description']) ? $item['location_description'] : '', 
                    'address' => $item['address']['line1'] . ', ' . $item['address']['line2'], 
                    'distance' => $distKm, 
                    'image' => $img, 
                    'lat' => $item['location']['latitude'], 
                    'lon' => $item['location']['longitude'] 
                ]; 
            } 
        }
        
        // Czyszczenie bufora przed outputem
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => true, 'token' => $token, 'points' => $points]); die();
    }

    // ZMIANA: private -> protected
    protected function getCoordinates($query, &$lat, &$lon) { 
        $geoUrl = "https://nominatim.openstreetmap.org/search?q={$query}&format=json&limit=1"; 
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $geoUrl); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_USERAGENT, 'BigBioManager/1.0 (contact@bigbio.pl)'); 
        $geoJson = curl_exec($ch); 
        curl_close($ch); 
        $geoData = json_decode($geoJson, true); 
        if (!empty($geoData) && isset($geoData[0]['lat'])) { 
            $lat = $geoData[0]['lat']; 
            $lon = $geoData[0]['lon']; 
            return true; 
        } 
        return false; 
    }

    // ZMIANA: private -> protected
    protected function calculateDistance($lat1, $lon1, $lat2, $lon2) { 
        if (($lat1 == $lat2) && ($lon1 == $lon2)) return 0; 
        $theta = $lon1 - $lon2; 
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
        $dist = acos($dist); 
        $dist = rad2deg($dist); 
        $miles = $dist * 60 * 1.1515; 
        return ($miles * 1.609344); 
    }

    protected function updateAddressData() {
        $id_address = (int)Tools::getValue('id_address'); 
        $id_order = (int)Tools::getValue('id_order'); 
        $type = Tools::getValue('address_type');
        
        if (!$id_address) throw new Exception("Brak ID adresu");
        
        $db = Db::getInstance();

        // Pobierz stan "przed" do audytu
        $before = null;
        try {
            $before = $db->getRow('SELECT firstname, lastname, company, address1, postcode, city, vat_number, other FROM `' . _DB_PREFIX_ . 'address` WHERE id_address = ' . (int) $id_address);
        } catch (Exception $e) {
            $before = null;
        }

        $data = [ 
            'firstname' => pSQL(Tools::getValue('firstname')), 
            'lastname' => pSQL(Tools::getValue('lastname')), 
            'company' => pSQL(Tools::getValue('company')), 
            'address1' => pSQL(Tools::getValue('address1')), 
            'postcode' => pSQL(Tools::getValue('postcode')), 
            'city' => pSQL(Tools::getValue('city')), 
            'vat_number'=> pSQL(Tools::getValue('vat_number')), 
            'other' => pSQL(Tools::getValue('other')), 
            'date_upd' => date('Y-m-d H:i:s') 
        ];

        // Wylicz różnice
        $changes = [];
        $fieldsToAudit = ['firstname','lastname','company','address1','postcode','city','vat_number','other'];
        if (is_array($before)) {
            foreach ($fieldsToAudit as $f) {
                $oldV = isset($before[$f]) ? (string)$before[$f] : '';
                $newV = isset($data[$f]) ? (string)$data[$f] : '';
                if ($oldV !== $newV) {
                    $changes[$f] = ['old' => $oldV, 'new' => $newV];
                }
            }
        }
        
        $needSplit = false;
        if ($id_order && $type) { 
            $orderAddrs = $db->getRow('SELECT id_address_delivery, id_address_invoice FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order = ' . $id_order); 
            if ($orderAddrs && $orderAddrs['id_address_delivery'] == $orderAddrs['id_address_invoice']) { $needSplit = true; } 
        }
        
        if ($needSplit) { 
            $original = $db->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'address` WHERE id_address = ' . $id_address); 
            if (!$original) throw new Exception("Nie znaleziono adresu źródłowego"); 
            unset($original['id_address'], $original['date_add'], $original['date_upd']); 
            $newData = array_merge($original, $data); 
            $newData['date_add'] = date('Y-m-d H:i:s'); 
            if ($db->insert('address', $newData)) { 
                $newId = $db->Insert_ID(); 
                $colToUpdate = ($type === 'address_invoice') ? 'id_address_invoice' : 'id_address_delivery'; 
                $db->update('orders', [$colToUpdate => $newId], 'id_order = ' . $id_order); 

                // Log audytowy (split)
                if ($id_order) {
                    $typHuman = ($type === 'address_invoice') ? 'Faktura' : 'Dostawa';
                    $msg = 'ADRES: Rozdzielono adresy i zapisano zmiany (' . $typHuman . ')';
                    if (!empty($changes)) {
                        $msg .= ' [' . implode(', ', array_keys($changes)) . ']';
                    }
                    $this->addSystemLog(
                        (int) $id_order,
                        $msg,
                        'ADDRESS_UPDATE',
                        [
                            'mode' => 'split',
                            'address_type' => (string) $type,
                            'old_id_address' => (int) $id_address,
                            'new_id_address' => (int) $newId,
                            'changes' => $changes,
                        ]
                    );
                }

                if (ob_get_length()) ob_clean(); 
                echo json_encode(['success' => true, 'new_id' => $newId]); die(); 
            } else { throw new Exception("Błąd przy tworzeniu adresu"); } 
        } else { 
            if ($db->update('address', $data, 'id_address = ' . $id_address)) { 

                // Log audytowy (update)
                if ($id_order) {
                    $typHuman = ($type === 'address_invoice') ? 'Faktura' : 'Dostawa';
                    $msg = 'ADRES: Zaktualizowano dane (' . $typHuman . ')';
                    if (!empty($changes)) {
                        $msg .= ' [' . implode(', ', array_keys($changes)) . ']';
                    }
                    $this->addSystemLog(
                        (int) $id_order,
                        $msg,
                        'ADDRESS_UPDATE',
                        [
                            'mode' => 'update',
                            'address_type' => (string) $type,
                            'id_address' => (int) $id_address,
                            'changes' => $changes,
                        ]
                    );
                }

                if (ob_get_length()) ob_clean(); 
                echo json_encode(['success' => true]); die(); 
            } else { throw new Exception("Błąd zapisu DB"); } 
        }
    }

    protected function addTrackingNumber() { 
        $id_order = (int)Tools::getValue('id_order'); 
        $number = trim(Tools::getValue('tracking_number')); 
        $id_carrier = (int)Tools::getValue('id_carrier'); 
        if (!$id_order || !$number) throw new Exception("Brak danych"); 
        
        $db = Db::getInstance(); 
        $order = new Order($id_order); 
        if (!Validate::isLoadedObject($order)) throw new Exception("Brak zamówienia"); 
        
        if (!$id_carrier) $id_carrier = $order->id_carrier; 
        
        $exists = $db->getValue("SELECT id_order_carrier FROM `" . _DB_PREFIX_ . "order_carrier` WHERE id_order = $id_order AND tracking_number = '" . pSQL($number) . "'"); 
        if (!$exists) { 
            $new_oc = new OrderCarrier(); 
            $new_oc->id_order = $id_order; 
            $new_oc->id_carrier = $id_carrier; 
            $new_oc->tracking_number = $number; 
            $new_oc->date_add = date('Y-m-d H:i:s'); 
            $new_oc->add(); 
            
            if (empty($order->shipping_number)) { 
                $order->shipping_number = $number; 
                $order->update(); 
            } 
        } 

        // Log audytowy
        $this->addSystemLog(
            (int) $id_order,
            'DOSTAWA: Dodano numer śledzenia ' . $number,
            'TRACKING_ADD',
            [
                'tracking_number' => (string) $number,
                'id_carrier' => (int) $id_carrier,
            ]
        );

        if (ob_get_length()) ob_clean(); 
        echo json_encode(['success' => true]); die(); 
    }

    // --- ZMODYFIKOWANA METODA: WYKRYWANIE ŹRÓDŁA PO NAZWIE MODUŁU I POBIERANIE SMART ---
    protected function getShippingDetails($id_order, $carrierNameStandard, $db) { 
        // Ładowanie klas integracji (jeśli pliki istnieją)
        $pathX13 = _PS_MODULE_DIR_ . 'bb_ordermanager/integrations/AllegroX13.php';
        $pathPro = _PS_MODULE_DIR_ . 'bb_ordermanager/integrations/BbAllegroProShipping.php'; // Ładujemy nową klasę z getSmartInfo
        $pathProOld = _PS_MODULE_DIR_ . 'bb_ordermanager/integrations/AllegroPro.php'; // Ładujemy też starą dla getDeliveryInfo jeśli używana
        
        if (file_exists($pathX13)) require_once $pathX13;
        if (file_exists($pathPro)) require_once $pathPro;
        if (file_exists($pathProOld)) require_once $pathProOld;

        $shipping = [
            'name' => $carrierNameStandard, 
            'point_id' => null, 
            'point_name' => null, 
            'point_addr' => null,
            'is_allegro_smart' => false,
            'smart_left' => 0,
            'smart_limit' => 0
        ]; 
        
        // 1. SPRAWDZAMY ŹRÓDŁO ZAMÓWIENIA (kolumna `module`)
        $moduleName = $db->getValue('SELECT module FROM `' . _DB_PREFIX_ . 'orders` WHERE id_order = ' . (int)$id_order);
        
        // 2. LOGIKA WYBORU INTEGRACJI
        
        // PRZYPADEK A: Moduł AllegroPro
        if ($moduleName === 'allegropro' && class_exists('BbAllegroPro')) {
            $proData = BbAllegroPro::getDeliveryInfo($id_order);
            if ($proData) {
                if (!empty($proData['method_name'])) $shipping['name'] = $proData['method_name'];
                $shipping['point_id']   = $proData['point_id'];
                $shipping['point_name'] = $proData['point_name'];
                $shipping['point_addr'] = $proData['point_addr'];
                
                // --- NOWE: POBIERANIE INFO O SMARCIE ---
                if (class_exists('BbAllegroProShipping')) {
                    $smartInfo = BbAllegroProShipping::getSmartInfo($id_order);
                    $shipping['is_allegro_smart'] = $smartInfo['is_smart'];
                    $shipping['smart_left'] = $smartInfo['left'];
                    $shipping['smart_limit'] = $smartInfo['limit'];
                }
                
                return $shipping;
            }
        }
        
        // PRZYPADEK B: Moduł X13 Allegro
        elseif ($moduleName === 'x13allegro' && class_exists('BbAllegroX13')) {
            $x13Data = BbAllegroX13::getDeliveryInfo($id_order);
            if ($x13Data) {
                if (!empty($x13Data['method_name'])) $shipping['name'] = $x13Data['method_name'];
                $shipping['point_id']   = $x13Data['point_id'];
                $shipping['point_name'] = $x13Data['point_name'];
                $shipping['point_addr'] = $x13Data['point_addr'];
                return $shipping;
            }
        }

        // 3. FALLBACK: Standardowe szukanie w adresie PrestaShop (dla innych modułów lub ręcznych zamówień)
        $pointInAddr = $db->getValue('SELECT other FROM `' . _DB_PREFIX_ . 'address` a LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON a.id_address = o.id_address_delivery WHERE o.id_order = ' . (int)$id_order); 
        if ($pointInAddr && preg_match('/[A-Z0-9]{5,7}/', $pointInAddr, $matches)) {
            $shipping['point_id'] = $matches[0]; 
        }
        
        return $shipping; 
    }

    // --- NOWE METODY DLA ALLEGRO PRO SHIPPING ---

    protected function createAllegroShipment() {
        $id_order = (int)Tools::getValue('id_order');
        $size = Tools::getValue('size_code'); // A, B, C lub null
        $weight = Tools::getValue('weight'); // float lub null
        $isSmart = (int)Tools::getValue('is_smart');

        if (!$id_order) throw new Exception("Brak ID zamówienia");

        $path = _PS_MODULE_DIR_ . 'bb_ordermanager/integrations/BbAllegroProShipping.php';
        if (!file_exists($path)) throw new Exception("Brak pliku integracji BbAllegroProShipping.php");
        
        require_once $path;

        try {
            $res = BbAllegroProShipping::createShipment($id_order, $size, $weight, $isSmart);
            
            // Dodaj log systemowy
            $msg = 'ALLEGRO: Utworzono przesyłkę' . ($size ? " (Gabaryt $size)" : "");
            $this->addSystemLog(
                $id_order,
                $msg,
                'SHIPMENT_CREATE',
                [
                    'provider' => 'ALLEGRO',
                    'size' => $size,
                    'weight' => $weight,
                    'is_smart' => (int) $isSmart,
                ]
            );

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        die();
    }

    protected function getAllegroLabel() {
        $id_order = (int)Tools::getValue('id_order');
        $shipmentId = Tools::getValue('shipment_id');

        if (!$id_order || !$shipmentId) die('Brak danych');

        $path = _PS_MODULE_DIR_ . 'bb_ordermanager/integrations/BbAllegroProShipping.php';
        if (!file_exists($path)) die("Brak integracji");
        
        require_once $path;

        try {
            $pdfContent = BbAllegroProShipping::getLabel($id_order, $shipmentId);

            // Log audytowy
            $this->addSystemLog(
                (int) $id_order,
                'ALLEGRO: Pobrano etykietę (shipment_id: ' . (string) $shipmentId . ')',
                'LABEL_DOWNLOAD',
                [
                    'provider' => 'ALLEGRO',
                    'shipment_id' => (string) $shipmentId,
                ]
            );
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="label_' . $shipmentId . '.pdf"');
            echo $pdfContent;
        } catch (Exception $e) {
            die('Błąd: ' . $e->getMessage());
        }
        die();
    }
}