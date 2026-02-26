<?php
/**
 * Niezależna integracja wysyłkowa dla MANAGER PRO
 * Wersja 3.5 - FIX: sender.phone wymagany + sender.name <= 30 + Agresywne czyszczenie ID Punktu + Logika Kurier/Box
 */

class BbAllegroProShipping
{
    /**
     * BEZPIECZEŃSTWO:
     * Nie zapisujemy etykiet ani logów debug w publicznym katalogu modułu.
     * Pliki tymczasowe trzymamy w katalogu tymczasowym serwera (poza webroot).
     *
     * Jeśli musisz debugować integrację, ustaw w konfiguracji:
     *   Configuration::updateValue('BB_OM_DEBUG', 1);
     */
    private static $migratedLegacyFiles = false;

    private static function getBaseStorageDir()
    {
        $base = rtrim((string)sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'bb_ordermanager' . DIRECTORY_SEPARATOR;

        if (!self::ensureDir($base)) {
            // Fallback: cache dir (ostatnia deska ratunku)
            $fallback = defined('_PS_CACHE_DIR_')
                ? _PS_CACHE_DIR_
                : (_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR);

            $base = rtrim((string)$fallback, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'bb_ordermanager' . DIRECTORY_SEPARATOR;
            self::ensureDir($base);
        }

        if (!self::$migratedLegacyFiles) {
            self::$migratedLegacyFiles = true;
            self::migrateLegacyPublicFiles($base);
        }

        return $base;
    }

    private static function getLabelDir()
    {
        $dir = self::getBaseStorageDir() . 'labels' . DIRECTORY_SEPARATOR;
        self::ensureDir($dir);
        return $dir;
    }

    private static function getDebugFilePath()
    {
        return self::getBaseStorageDir() . 'last_request_debug.txt';
    }

    private static function ensureDir($dir)
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        return is_dir($dir) && is_writable($dir);
    }

    private static function isDebugEnabled()
    {
        return (bool)Configuration::get('BB_OM_DEBUG');
    }

    private static function writeDebug($content)
    {
        if (!self::isDebugEnabled()) {
            return;
        }
        @file_put_contents(self::getDebugFilePath(), (string)$content);
    }

    private static function migrateLegacyPublicFiles($baseDir)
    {
        // 1) public labels dir -> przenieś do nowego katalogu
        $oldLabelsDir = _PS_MODULE_DIR_ . 'bb_ordermanager/labels/';
        $newLabelsDir = rtrim((string)$baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'labels' . DIRECTORY_SEPARATOR;
        self::ensureDir($newLabelsDir);

        if (is_dir($oldLabelsDir) && is_readable($oldLabelsDir)) {
            $files = glob($oldLabelsDir . 'label_*.*');
            if (is_array($files)) {
                foreach ($files as $file) {
                    if (!is_file($file)) {
                        continue;
                    }
                    $dest = $newLabelsDir . basename($file);
                    if (@rename($file, $dest)) {
                        continue;
                    }
                    if (@copy($file, $dest)) {
                        @unlink($file);
                    }
                }
            }
        }

        // 2) public debug file -> przenieś (opcjonalnie) i wyczyść publiczny
        $oldDebug = _PS_MODULE_DIR_ . 'bb_ordermanager/last_request_debug.txt';
        if (is_file($oldDebug)) {
            $dest = rtrim((string)$baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'last_request_debug.txt';

            if (self::isDebugEnabled()) {
                @copy($oldDebug, $dest);
            }

            // Nie trzymaj w publicznym pliku payloadów z danymi adresowymi
            @file_put_contents($oldDebug, "DEBUG przeniesiony do katalogu tymczasowego serwera. Ustaw BB_OM_DEBUG=1 aby logować.
");
        }
    }


    /**
     * Uruchamia jednorazową migrację/oczyszczenie plików legacy z publicznego katalogu modułu.
     * Można to wołać np. przy wejściu do Managera.
     */
    public static function secureLegacyFiles()
    {
        // Wywołanie getBaseStorageDir() odpali migrację legacy (tylko raz na request)
        self::getBaseStorageDir();
        self::getLabelDir();
        return true;
    }

    /**
     * Allegro Shipment Management ma twarde limity długości pól (np. sender.name <= 30).
     * Ujednolicamy białe znaki i bezpiecznie przycinamy UTF-8.
     */
    private static function limitText($text, $maxLen)
    {
        $text = (string)$text;
        // Normalizacja białych znaków
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        if ($maxLen > 0 && function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') > $maxLen) {
                $text = mb_substr($text, 0, $maxLen, 'UTF-8');
            }
        } else {
            if ($maxLen > 0 && strlen($text) > $maxLen) {
                $text = substr($text, 0, $maxLen);
            }
        }
        return $text;
    }

    public static function getSmartInfo($id_order)
    {
        $db = Db::getInstance();
        $order = $db->getRow("SELECT checkout_form_id FROM `" . _DB_PREFIX_ . "allegropro_order` WHERE id_order_prestashop = " . (int)$id_order);
        if (!$order) return ['is_smart' => false, 'left' => 0, 'limit' => 0];
        
        $cfId = $order['checkout_form_id'];
        $shipping = $db->getRow("SELECT is_smart, package_count FROM `" . _DB_PREFIX_ . "allegropro_order_shipping` WHERE checkout_form_id = '" . pSQL($cfId) . "'");
        
        if (!$shipping || !$shipping['is_smart']) {
            return ['is_smart' => false, 'left' => 0, 'limit' => 0];
        }
        
        $limit = (int)$shipping['package_count'];
        $used = (int)$db->getValue("SELECT COUNT(*) FROM `" . _DB_PREFIX_ . "allegropro_shipment` WHERE checkout_form_id = '" . pSQL($cfId) . "' AND is_smart = 1 AND status != 'CANCELLED'");
        $left = max(0, $limit - $used);
        
        return ['is_smart' => true, 'left' => $left, 'limit' => $limit];
    }

    public static function createShipment($id_order, $sizeCode = null, $weight = null, $isSmart = false)
    {
        $db = Db::getInstance();

        $order = $db->getRow("SELECT * FROM `" . _DB_PREFIX_ . "allegropro_order` WHERE id_order_prestashop = " . (int)$id_order);
        if (!$order) throw new Exception("To nie jest zamówienie Allegro Pro.");

        $cfId = $order['checkout_form_id'];
        $accountId = (int)$order['id_allegropro_account'];

        $account = $db->getRow("SELECT * FROM `" . _DB_PREFIX_ . "allegropro_account` WHERE id_allegropro_account = " . $accountId);
        if (!$account || empty($account['access_token'])) throw new Exception("Brak autoryzacji konta Allegro.");

        $accessToken = self::ensureAccessToken($account);

        $shipping = $db->getRow("SELECT * FROM `" . _DB_PREFIX_ . "allegropro_order_shipping` WHERE checkout_form_id = '" . pSQL($cfId) . "'");
        $buyer = $db->getRow("SELECT * FROM `" . _DB_PREFIX_ . "allegropro_order_buyer` WHERE checkout_form_id = '" . pSQL($cfId) . "'");

        if (!$shipping) throw new Exception("Brak danych wysyłkowych.");

        $methodId = $shipping['method_id']; 
        
        // Budowanie Payloadu
        $payload = self::buildPayload($shipping, $buyer, $methodId, $sizeCode, $weight);

        // DEBUG: Zapisz co wysyłamy, żebyś widział w razie błędu
        $debugContent = "TIME: " . date('Y-m-d H:i:s') . "\n";
        $debugContent .= "ORDER ID: $id_order\n";
        $debugContent .= "PAYLOAD:\n" . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        self::writeDebug($debugContent);

        $response = self::request('POST', '/shipment-management/shipments/create-commands', $accessToken, $account['sandbox'], ['input' => $payload]);

        if (!$response['ok']) {
            $err = $response['json']['errors'][0]['message'] ?? 'Nieznany błąd API';
            if (isset($response['json']['errors'][0]['details'])) {
                $err .= ' (' . $response['json']['errors'][0]['details'] . ')';
            }
            if (isset($response['json']['errors'][0]['path'])) {
                $err .= ' [Pole: ' . $response['json']['errors'][0]['path'] . ']';
            }
            throw new Exception("Allegro API: " . $err . " | WYSŁANO: " . json_encode($payload));
        }

        $commandId = $response['json']['commandId'] ?? ($response['json']['id'] ?? null);
        if (empty($commandId)) {
            throw new Exception("Allegro API: brak commandId w odpowiedzi create-commands (sprawdź response w debug).");
        }

        // --- Polling statusu tworzenia paczki ---
        $shipmentId = null;
        $status = 'IN_PROGRESS';
        $errors = [];

        // max ~15s, ale z poszanowaniem Retry-After
        for ($i = 0; $i < 10; $i++) {
            $statusResp = self::request('GET', '/shipment-management/shipments/create-commands/' . $commandId, $accessToken, $account['sandbox']);

            if (!$statusResp['ok']) {
                // jeśli jeszcze nie gotowe lub chwilowy błąd - spróbuj ponownie
                $retryAfter = (int)($statusResp['headers']['retry-after'] ?? 1);
                if ($retryAfter <= 0) $retryAfter = 1;
                usleep($retryAfter * 1000000);
                continue;
            }

            $status = $statusResp['json']['status'] ?? 'IN_PROGRESS';
            $shipmentId = $statusResp['json']['shipmentId'] ?? null;
            $errors = $statusResp['json']['errors'] ?? [];

            if ($status === 'SUCCESS' && !empty($shipmentId)) {
                break;
            }

            if ($status === 'ERROR') {
                $errMsg = $errors[0]['message'] ?? 'Nieznany błąd tworzenia przesyłki';
                $errPath = $errors[0]['path'] ?? '';
                throw new Exception('Allegro API: ' . $errMsg . ($errPath ? ' [Pole: ' . $errPath . ']' : ''));
            }

            // IN_PROGRESS
            $retryAfter = (int)($statusResp['headers']['retry-after'] ?? 1);
            if ($retryAfter <= 0) $retryAfter = 1;
            usleep($retryAfter * 1000000);
        }

        if (empty($shipmentId)) {
            // Nie udało się uzyskać shipmentId w czasie polling-u
            $msg = 'Allegro API: nie zwrócono shipmentId (status: ' . $status . ').';
            if (self::isDebugEnabled()) {
                $msg .= ' Sprawdź log debug (BB_OM_DEBUG).';
            }
            throw new Exception($msg);
        }

        // status końcowy
        $status = 'CREATED';

        $db->insert('allegropro_shipment', [
            'id_allegropro_account' => $accountId,
            'checkout_form_id' => pSQL($cfId),
            'shipment_id' => pSQL($shipmentId ?? $commandId),
            'tracking_number' => '', 
            'carrier_mode' => $sizeCode ? 'BOX' : 'COURIER',
            'size_details' => pSQL($sizeCode ?? 'CUSTOM'),
            'is_smart' => (int)$isSmart,
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        if ($shipmentId) {
            try { self::getLabel($id_order, $shipmentId); } catch (Exception $e) {}
        }

        return ['success' => true, 'shipment_id' => $shipmentId];
    }

    public static function getLabel($id_order, $shipmentId)
    {
        $labelDir = self::getLabelDir();
        $format = Configuration::get('BB_MANAGER_LABEL_FORMAT') ?: 'PDF';
        $ext = ($format === 'ZPL' || $format === 'EPL') ? strtolower($format) : 'pdf';
        
        $fileName = 'label_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $shipmentId) . '.' . $ext;
        $filePath = $labelDir . $fileName;

        if (file_exists($filePath) && filesize($filePath) > 0) {
            return file_get_contents($filePath);
        }

        $db = Db::getInstance();
        $order = $db->getRow("SELECT id_allegropro_account FROM `" . _DB_PREFIX_ . "allegropro_order` WHERE id_order_prestashop = " . (int)$id_order);
        if (!$order) throw new Exception("Brak zamówienia.");
        
        $account = $db->getRow("SELECT * FROM `" . _DB_PREFIX_ . "allegropro_account` WHERE id_allegropro_account = " . (int)$order['id_allegropro_account']);
        $accessToken = self::ensureAccessToken($account);

        $pageSize = Configuration::get('BB_MANAGER_LABEL_SIZE') ?: 'A4';

        $payload = [
            'shipmentIds' => [$shipmentId],
            'pageSize' => $pageSize,
            'labelFormat' => $format,
            'cutLine' => ($format === 'PDF' && $pageSize === 'A4')
        ];

        $response = self::request('POST', '/shipment-management/label', $accessToken, $account['sandbox'], $payload, true);

        if (!$response['ok']) {
            throw new Exception("Błąd pobierania etykiety z API Allegro.");
        }

        if (!empty($response['body'])) {
            file_put_contents($filePath, $response['body']);
        }

        return $response['body']; 
    }

    private static function buildPayload($shipping, $buyer, $methodId, $sizeCode, $weight)
    {
        $configWeight = (float)Configuration::get('BB_MANAGER_DEF_WEIGHT');
        if ($configWeight <= 0) $configWeight = 1.0;
        
        $configContent = Configuration::get('BB_MANAGER_CONTENT') ?: 'Towary handlowe';
        $configType = Configuration::get('BB_MANAGER_PKG_TYPE') ?: 'PACKAGE';

        // Nadawca: telefon jest wymagany przez Allegro Shipment Management
        $senderPhoneRaw = Configuration::get('BB_MANAGER_SENDER_PHONE');
        if (empty($senderPhoneRaw)) $senderPhoneRaw = Configuration::get('BB_ALLEGROPRO_SENDER_PHONE');
        if (empty($senderPhoneRaw)) $senderPhoneRaw = Configuration::get('PS_SHOP_PHONE');
        if (empty($senderPhoneRaw)) $senderPhoneRaw = Configuration::get('PS_SHOP_PHONE2');
        $senderPhone = preg_replace('/[^0-9+]/', '', (string)$senderPhoneRaw);
        if (preg_match('/^[0-9]{9}$/' , $senderPhone)) $senderPhone = '+48' . $senderPhone;
        if (!empty($senderPhone) && $senderPhone[0] !== '+' && preg_match('/^48[0-9]{9}$/', $senderPhone)) $senderPhone = '+' . $senderPhone;
        if (empty($senderPhone)) {
            throw new Exception('Brak numeru telefonu nadawcy (sender.phone). Ustaw PS_SHOP_PHONE (Parametry sklepu > Kontakt) lub ustaw BB_MANAGER_SENDER_PHONE.');
        }



        $sender = [
            // Allegro limit: max 30 znaków
            'name' => self::limitText(Configuration::get('PS_SHOP_NAME'), 30),
            'street' => Configuration::get('PS_SHOP_ADDR1'),
            'city' => Configuration::get('PS_SHOP_CITY'),
            'postalCode' => Configuration::get('PS_SHOP_CODE'),
            'countryCode' => 'PL',
            'email' => Configuration::get('PS_SHOP_EMAIL'),
            'phone' => $senderPhone,
        ];

        // Czyszczenie Telefonu
        $phone = preg_replace('/[^0-9+]/', '', (string)$shipping['addr_phone']);
        if (preg_match("/^[0-9]{9}$/", $phone)) { $phone = "+48" . $phone; }
        if (!empty($phone) && $phone[0] !== "+" && preg_match("/^48[0-9]{9}$/", $phone)) { $phone = "+" . $phone; }
        if (strpos($phone, '00') === 0) { $phone = '+' . substr($phone, 2); }

        $receiver = [
            'name' => trim($shipping['addr_name']),
            'street' => $shipping['addr_street'],
            'city' => $shipping['addr_city'],
            'postalCode' => $shipping['addr_zip'],
            'countryCode' => $shipping['addr_country'] ?? 'PL',
            'email' => $buyer['email'],
            'phone' => $phone
        ];
        
        // --- LOGIKA GABARYTÓW I PUNKTU ---
        $dims = ['length' => 10, 'width' => 10, 'height' => 10, 'unit' => 'CENTIMETER'];
        $wgtVal = $configWeight;
        $type = $configType;

        $isBoxMode = false;

        if ($sizeCode) {
            // Jeśli podano sizeCode (A, B, C) -> To jest Paczkomat (BOX)
            $isBoxMode = true;
            $type = 'PACKAGE';
            $wgtVal = 25.0;
            if ($sizeCode == 'A') { $dims = ['height'=>8, 'width'=>38, 'length'=>64, 'unit'=>'CENTIMETER']; }
            if ($sizeCode == 'B') { $dims = ['height'=>19, 'width'=>38, 'length'=>64, 'unit'=>'CENTIMETER']; }
            if ($sizeCode == 'C') { $dims = ['height'=>41, 'width'=>38, 'length'=>64, 'unit'=>'CENTIMETER']; }
        } else {
            // Jeśli NIE podano sizeCode -> To jest Kurier
            $isBoxMode = false;
            if ($weight) {
                $wgtVal = (float)str_replace(',', '.', (string)$weight);
            }
            $dims['length'] = (int)Configuration::get('BB_MANAGER_PKG_LEN') ?: 10;
            $dims['width'] = (int)Configuration::get('BB_MANAGER_PKG_WID') ?: 10;
            $dims['height'] = (int)Configuration::get('BB_MANAGER_PKG_HEI') ?: 10;
        }

        // --- ID PUNKTU (TYLKO JEŚLI TRYB BOX) ---
        // Jeśli użytkownik wybrał "KURIER" (sizeCode jest null), NIE wysyłamy punktu, nawet jak jest w bazie!
        if ($isBoxMode && !empty($shipping['pickup_point_id'])) {
            $rawPoint = trim($shipping['pickup_point_id']);
            
            // 1. Rozbij po spacjach i weź ostatni element (np. z "Paczkomat WAW186M" weźmie "WAW186M")
            // Działa też na "PaczkoPunkt POP-GRW1" -> "POP-GRW1"
            $parts = preg_split('/\s+/', $rawPoint);
            $lastPart = end($parts);

            // 2. Agresywne czyszczenie: zostaw tylko Litery, Cyfry i Myślnik (InPost ID to np. WAW186M lub POP-123)
            $cleanPoint = preg_replace('/[^A-Z0-9-]/', '', strtoupper($lastPart));
            
            if (!empty($cleanPoint)) {
                // Allegro API wymaga, aby receiver.point było STRINGIEM (np. "GRW01A"), a nie obiektem {id: ...}
                // Wysyłanie obiektu powoduje błąd parsowania: "The object sent cannot be properly parsed [Pole: input.receiver.point]"
                $receiver['point'] = $cleanPoint;
            }
        }

        $wgtString = number_format($wgtVal, 3, '.', '');

        // Allegro Shipment Management (create-commands):
        // packages[].length/width/height MUSZĄ być obiektami {value, unit}, np. unit: CENTIMETER.
        // Zob. poradnik: /shipment-management/shipments/create-commands (sekcja packages). 
        $pkgLen = (int)($dims['length'] ?? 10);
        $pkgWid = (int)($dims['width'] ?? 10);
        $pkgHei = (int)($dims['height'] ?? 10);
        $dimUnit = (string)($dims['unit'] ?? 'CENTIMETER');

        $data = [
            'deliveryMethodId' => $methodId,
            'sender' => $sender,
            'receiver' => $receiver,
            'labelFormat' => Configuration::get('BB_MANAGER_LABEL_FORMAT') ?: 'PDF',
            'packages' => [[
                'type' => $type,
                'length' => [
                    'value' => (string)$pkgLen,
                    'unit' => $dimUnit
                ],
                'width' => [
                    'value' => (string)$pkgWid,
                    'unit' => $dimUnit
                ],
                'height' => [
                    'value' => (string)$pkgHei,
                    'unit' => $dimUnit
                ],
                'weight' => [
                    'value' => (string)$wgtString,
                    'unit' => 'KILOGRAMS'
                ],
                // opis na etykiecie paczki (poradnik używa pola textOnLabel)
                'textOnLabel' => $configContent
            ]]
        ];

        return $data;
    }

    private static function ensureAccessToken($account)
    {
        $expires = strtotime($account['token_expires_at']);
        if (time() < $expires - 60) {
            return $account['access_token']; 
        }

        $env = ((int)$account['sandbox'] == 1) ? 'sandbox' : 'prod';
        $url = ($env == 'sandbox' ? 'https://allegro.pl.allegrosandbox.pl' : 'https://allegro.pl') . '/auth/oauth/token';
        
        $clientId = Configuration::get('ALLEGROPRO_CLIENT_ID');
        $clientSecret = Configuration::get('ALLEGROPRO_CLIENT_SECRET');
        $auth = base64_encode($clientId . ':' . $clientSecret);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => $account['refresh_token']
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic $auth"]);
        
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($res, true);
        if ($code == 200 && isset($json['access_token'])) {
            $newAccess = $json['access_token'];
            $newRefresh = $json['refresh_token'];
            $newExp = date('Y-m-d H:i:s', time() + $json['expires_in']);
            
            Db::getInstance()->update('allegropro_account', [
                'access_token' => pSQL($newAccess),
                'refresh_token' => pSQL($newRefresh),
                'token_expires_at' => pSQL($newExp)
            ], 'id_allegropro_account = ' . (int)$account['id_allegropro_account']);

            return $newAccess;
        }

        throw new Exception("Nie udało się odświeżyć tokena Allegro.");
    }

    private static function request($method, $path, $token, $sandbox, $data = null, $binary = false)
    {
        $env = ((int)$sandbox == 1) ? 'sandbox' : 'prod';
        $baseUrl = ($env == 'sandbox' ? 'https://api.allegro.pl.allegrosandbox.pl' : 'https://api.allegro.pl');

        $ch = curl_init($baseUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $headers = [
            "Authorization: Bearer $token",
            "Accept: " . ($binary ? "application/pdf" : "application/vnd.allegro.public.v1+json"),
            "Content-Type: application/vnd.allegro.public.v1+json"
        ];

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Zbieraj nagłówki (Retry-After bywa kluczowe dla create-commands)
        curl_setopt($ch, CURLOPT_HEADER, true);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($res, 0, $headerSize);
        $body = substr($res, $headerSize);

        $parsed = [];
        foreach (preg_split('/\r\n|\r|\n/', (string)$rawHeaders) as $line) {
            $pos = strpos($line, ':');
            if ($pos !== false) {
                $k = strtolower(trim(substr($line, 0, $pos)));
                $v = trim(substr($line, $pos + 1));
                if ($k !== '') {
                    $parsed[$k] = $v;
                }
            }
        }

        return [
            'ok' => ($code >= 200 && $code < 300),
            'json' => (!$binary ? json_decode($body, true) : null),
            'body' => $body,
            'code' => $code,
            'headers' => $parsed,
            'raw_headers' => $rawHeaders,
        ];
    }
}
