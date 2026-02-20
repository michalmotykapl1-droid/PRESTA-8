<?php

class AzadaEkoWitalB2B
{
    private $baseUrl = 'https://eko-wital.pl';
    private $loginUrl = 'https://eko-wital.pl/logowanie';
    private $ordersUrl = 'https://eko-wital.pl/zamowienia';
    private $invoicesUrl = 'https://eko-wital.pl/faktury';
    private $cookieFile;
    private $debugFile;

    public function __construct()
    {
        if (!defined('_PS_MODULE_DIR_')) {
            define('_PS_MODULE_DIR_', _PS_ROOT_DIR_ . '/modules/');
        }
        $this->cookieFile = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/cookies_ekowital.txt';
        $this->debugFile  = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/debug_ekowital.html';
    }

    public function checkLogin($login, $password)
    {
        if (empty($login) || empty($password)) return false;
        
        if (file_exists($this->cookieFile)) @unlink($this->cookieFile);
        
        $this->performLogin($login, $password);
        $html = $this->request($this->ordersUrl);
        return $this->isLoggedIn($html);
    }

    // --- POBIERANIE ZAMÓWIEŃ ---
    public function scrapeOrders($login, $password)
    {
        if (empty($login) || empty($password)) {
            return ['status' => 'error', 'msg' => 'Brak danych logowania B2B.'];
        }

        $html = $this->request($this->ordersUrl);
        if (!$this->isLoggedIn($html)) {
            $this->performLogin($login, $password);
            $html = $this->request($this->ordersUrl);
        }

        if (!$this->isLoggedIn($html)) {
            return ['status' => 'error', 'msg' => 'Brak dostępu do listy zamówień (logowanie nieudane).'];
        }

        $listEndpoint = $this->extractListUrl($html, $this->ordersUrl);

        $daysBack = (int)Configuration::get('AZADA_B2B_DAYS_RANGE', 7);
        if ($daysBack < 1) $daysBack = 7;
        
        $dateFrom = date('Y-m-d\T00:00:00', strtotime("-$daysBack days"));
        $dateTo = date('Y-m-d\T23:59:59');

        $docsAll = $this->fetchAndParseTable($listEndpoint, $dateFrom, $dateTo, 10, 'order');
        
        return ['status' => 'success', 'data' => $docsAll];
    }

    // --- POBIERANIE FAKTUR ---
    public function scrapeInvoices($login, $password)
    {
        if (empty($login) || empty($password)) {
            return ['status' => 'error', 'msg' => 'Brak danych logowania B2B.'];
        }

        $html = $this->request($this->invoicesUrl);
        if (!$this->isLoggedIn($html)) {
            $this->performLogin($login, $password);
            $html = $this->request($this->invoicesUrl);
        }

        if (!$this->isLoggedIn($html)) {
            return ['status' => 'error', 'msg' => 'Brak dostępu do listy faktur (logowanie nieudane).'];
        }

        $listEndpoint = $this->extractListUrl($html, $this->invoicesUrl);

        $daysBack = (int)Configuration::get('AZADA_FV_DAYS_RANGE', 30);
        if ($daysBack < 1) $daysBack = 30;
        
        $dateFrom = date('Y-m-d\T00:00:00', strtotime("-$daysBack days"));
        $dateTo = date('Y-m-d\T23:59:59');

        $docsAll = $this->fetchAndParseTable($listEndpoint, $dateFrom, $dateTo, 10, 'invoice');
        $docsUnpaid = $this->fetchAndParseTable($listEndpoint, $dateFrom, $dateTo, 0, 'invoice');

        $unpaidMap = [];
        foreach ($docsUnpaid as $u) {
            $unpaidMap[$u['number']] = $u['deadline'];
        }

        foreach ($docsAll as &$doc) {
            if (isset($unpaidMap[$doc['number']])) {
                $doc['is_paid'] = false;
                $doc['deadline'] = $unpaidMap[$doc['number']];
            } else {
                $doc['is_paid'] = true;
                $doc['deadline'] = ''; 
            }
        }

        return ['status' => 'success', 'data' => $docsAll];
    }

    public function downloadFile($remoteUrl, $localPath, $login, $password)
    {
        if (empty($login) || empty($password)) {
            return ['status' => 'error', 'msg' => 'Brak danych logowania B2B.'];
        }

        $test = $this->request($this->ordersUrl);
        if (!$this->isLoggedIn($test)) {
            $this->performLogin($login, $password);
        }

        require_once(dirname(__FILE__) . '/../services/AzadaFileHandler.php');
        AzadaFileHandler::ensureDirectory($localPath);

        $fp = fopen($localPath, 'w+');
        if ($fp === false) return ['status' => 'error', 'msg' => 'Brak uprawnień do zapisu.'];

        $ch = curl_init($remoteUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/115.0.0.0 Safari/537.36');
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($httpCode == 200 && filesize($localPath) > 50) {
            return ['status' => 'success', 'msg' => 'OK'];
        }
        
        @unlink($localPath);
        return ['status' => 'error', 'msg' => 'Błąd pobierania (kod: '.$httpCode.')'];
    }

    // --- FUNKCJE POMOCNICZE ---

    private function extractListUrl($html, $fallbackUrl)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $scripts = $xpath->query('//script[@type="application/json"]');
        foreach ($scripts as $script) {
            $jsonText = trim($script->nodeValue);
            $data = json_decode($jsonText, true);
            if (isset($data['listUrl'])) {
                $url = $data['listUrl'];
                return (strpos($url, 'http') === 0) ? $url : rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');
            }
        }
        return $fallbackUrl;
    }

    private function fetchAndParseTable($url, $dateFrom, $dateTo, $modeType, $docType)
    {
        $payload = [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'modeType' => $modeType,
            'documentListMode' => $modeType,
            'orderColumn' => 'CreationDate',
            'isDescendingOrder' => 'true',
            'page' => 1
        ];

        $headers = [
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . ($docType === 'invoice' ? $this->invoicesUrl : $this->ordersUrl)
        ];
        
        $response = $this->request($url, $payload, $headers);

        $trimmed = ltrim($response);
        if (strpos($trimmed, '{') === 0) {
            $data = json_decode($response, true);
            if (isset($data['Html'])) {
                $response = $data['Html']; 
            } elseif (isset($data['html'])) {
                $response = $data['html'];
            }
        }

        return $this->parseHtmlTable($response, $modeType, $docType);
    }

    private function parseHtmlTable($html, $modeType, $docType)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        
        $rows = $xpath->query('//tbody/tr');
        if ($rows->length === 0) $rows = $xpath->query('//tr[td]');

        $documents = [];
        foreach ($rows as $row) {
            $cols = $row->getElementsByTagName('td');
            if ($cols->length < 4) continue;

            $date = trim($cols->item(0)->nodeValue);
            
            $numberNode = $xpath->query('.//div[contains(@class, "font-weight-bolder")]', $cols->item(1))->item(0);
            if ($numberNode) {
                $number = trim($numberNode->nodeValue);
            } else {
                $numberRaw = trim($cols->item(1)->nodeValue);
                $number = trim(preg_replace('/\s+/', '', $numberRaw));
            }

            if (empty($number)) continue;

            $netto = '';
            $brutto = '';
            $deadline = '';
            $status = '';

            if ($modeType === 10) { 
                $valRaw = trim($cols->item(2)->nodeValue);
                
                if (preg_match('/netto\s*(-?[\d\s,\.]+)/iu', $valRaw, $matches)) {
                    $netto = trim($matches[1]);
                } else {
                    $netto = trim(preg_replace('/[^\d,\.\-]/', '', $valRaw));
                }
                
                if (preg_match('/brutto\s*(-?[\d\s,\.]+)/iu', $valRaw, $matches)) {
                    $brutto = trim($matches[1]);
                }
            }

            if ($docType === 'invoice') {
                if ($modeType === 0) { 
                    $deadline = trim($cols->item(3)->nodeValue);
                    $deadline = preg_replace('/Dni po terminie.*/is', '', $deadline);
                    $deadline = trim($deadline);
                }
            } elseif ($docType === 'order') {
                $statusNode = $xpath->query('.//span[@title]', $cols->item(3))->item(0);
                if ($statusNode) {
                    $status = trim($statusNode->getAttribute('title'));
                } else {
                    $status = trim($cols->item(3)->nodeValue);
                }

                // --- NOWOŚĆ: Tłumaczenie "Niezrealizowane" na żółty status z BioPlanet ---
                if (mb_stripos($status, 'niezrealizowane', 0, 'UTF-8') !== false) {
                    if (stripos($number, 'B2B') !== false) {
                        $status = 'Przekazano do realizacji';
                    } else {
                        // Zabezpieczenie dla innych ewentualnych prefiksów
                        $status = 'Przekazano do realizacji';
                    }
                }
            }

            $docId = '';
            $idNode = $xpath->query('.//*[@data-s-document-list-document-id]', $row)->item(0);
            if ($idNode) {
                $docId = $idNode->getAttribute('data-s-document-list-document-id');
            } else {
                $links = $row->getElementsByTagName('a');
                foreach ($links as $link) {
                    $href = $link->getAttribute('href');
                    if (preg_match('/\/(\d{5,})$/', $href, $m)) {
                        $docId = $m[1];
                        break;
                    }
                }
            }

            $options = [];
            if (!empty($docId)) {
                $options[] = [
                    'name' => 'CSV',
                    'url' => rtrim($this->baseUrl, '/') . '/dokumenty/download/' . $docId . '/solex_csv_po_symbolu-utf8'
                ];
                if ($docType === 'order') {
                    $options[] = [
                        'name' => 'PDF',
                        'url' => rtrim($this->baseUrl, '/') . '/documentexport/' . $docId
                    ];
                }
            }

            $documents[] = [
                'date' => $date,
                'number' => mb_strtoupper($number, 'UTF-8'),
                'doc_id' => $docId,
                'netto' => $netto,
                'brutto' => $brutto,
                'deadline' => $deadline,
                'status' => $status,
                'is_paid' => ($docType === 'invoice') ? ($modeType === 10) : false,
                'options' => $options
            ];
        }

        return $documents;
    }

    private function performLogin($login, $password)
    {
        $html = $this->request($this->loginUrl);
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        
        $form = $xpath->query('//form')->item(0);
        if ($form) {
            $action = $form->getAttribute('action');
            $actionUrl = $action ? (strpos($action, 'http') === 0 ? $action : rtrim($this->baseUrl, '/') . '/' . ltrim($action, '/')) : $this->loginUrl;
            
            $payload = [];
            $inputs = $xpath->query('.//input', $form);
            foreach ($inputs as $input) {
                $name = $input->getAttribute('name');
                $type = strtolower($input->getAttribute('type'));
                
                if (empty($name)) continue;
                
                if ($type === 'hidden') {
                    $payload[$name] = $input->getAttribute('value');
                } elseif ($type === 'password') {
                    $payload[$name] = $password;
                } elseif ($type === 'email' || $type === 'text') {
                    if (preg_match('/login|email|user|username|uzytkownik/i', $name)) {
                        $payload[$name] = $login;
                    }
                }
            }
            
            if (!isset($payload['login']) && !isset($payload['email']) && !isset($payload['Uzytkownik'])) {
                $payload['Uzytkownik'] = $login; 
                $payload['Haslo'] = $password;
            }

            $this->request($actionUrl, $payload);
        }
    }

    private function isLoggedIn($html)
    {
        if (empty($html)) return false;
        if (stripos($html, 'wyloguj') !== false || stripos($html, 'logout') !== false || stripos($html, 'Zamówienia') !== false || stripos($html, 'Faktury') !== false) {
            return true;
        }
        return false;
    }

    private function request($url, $postData = [], $headers = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/115.0.0.0 Safari/537.36');

        if (!empty($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}