<?php

class AzadaNaturaMedB2B
{
    private $baseUrl = 'https://naturamed.com.pl';
    private $loginUrl = 'https://naturamed.com.pl/logowanie';
    private $ordersUrl = 'https://naturamed.com.pl/zamowienia';
    private $invoicesUrl = 'https://naturamed.com.pl/faktury';
    private $cookieFile;

    public function __construct()
    {
        if (!defined('_PS_MODULE_DIR_')) {
            define('_PS_MODULE_DIR_', _PS_ROOT_DIR_ . '/modules/');
        }
        $this->cookieFile = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/cookies_naturamed.txt';
    }

    public function checkLogin($login, $password)
    {
        if (empty($login) || empty($password)) return false;
        if (file_exists($this->cookieFile)) @unlink($this->cookieFile);
        $this->performLogin($login, $password);
        $html = $this->request($this->ordersUrl);
        return $this->isLoggedIn($html);
    }

    public function scrapeInvoices($login, $password)
    {
        if (!$this->isLoggedIn($this->request($this->invoicesUrl))) {
            $this->performLogin($login, $password);
        }

        $daysBack = (int)Configuration::get('AZADA_FV_DAYS_RANGE', 30);
        $dateFrom = date('Y-m-d', strtotime("-".($daysBack < 1 ? 30 : $daysBack)." days"));
        $dateTo = date('Y-m-d');

        $rodzaj = 'Faktura';
        $ajaxUrl = rtrim($this->baseUrl, '/') . "/dokumenty/PobierzListe/$rodzaj/$dateFrom/$dateTo/False/False";
        $headers = ['X-Requested-With: XMLHttpRequest'];
        
        $response = $this->request($ajaxUrl, [], $headers);
        $json = json_decode($response, true);
        $html = isset($json['Html']) ? $json['Html'] : $response;

        return ['status' => 'success', 'data' => $this->parseHtmlTable($html)];
    }

    private function parseHtmlTable($html)
    {
        if (empty($html)) return [];
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>');
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//tr[td]');
        $documents = [];

        foreach ($rows as $row) {
            $cols = $row->getElementsByTagName('td');
            if ($cols->length < 4) continue;

            $dateRaw = trim($cols->item(0)->nodeValue);
            if (stripos($dateRaw, 'Razem') !== false) continue;

            $number = trim($cols->item(1)->nodeValue);
            $number = preg_replace('/\s+/', ' ', $number);

            $docId = '';
            $actionColumn = $cols->item($cols->length - 1);
            $links = $xpath->query('.//a[@href]|.//button[@data-ajax-url]', $actionColumn);
            foreach ($links as $link) {
                $url = $link->hasAttribute('href') ? $link->getAttribute('href') : $link->getAttribute('data-ajax-url');
                if (preg_match('/\/Pobierz\/(\d+)\//', $url, $m)) {
                    $docId = $m[1];
                    break;
                }
            }

            $netto = preg_replace('/[^\d,\.\-]/', '', trim($cols->item(2)->nodeValue));
            $brutto = preg_replace('/[^\d,\.\-]/', '', trim($cols->item(3)->nodeValue));

            $isPaid = false;
            $deadline = '';
            if ($cols->length >= 8) {
                $deadline = trim($cols->item(6)->nodeValue);
                $paidStatus = $cols->item(7);
                $isPaid = (stripos($paidStatus->nodeValue, 'TAK') !== false || stripos($paidStatus->getAttribute('class'), 'zaplacony-tak') !== false);
            }

            $options = [];
            if (!empty($docId)) {
                $options[] = [
                    'name' => 'CSV',
                    'url' => rtrim($this->baseUrl, '/') . '/dokumenty/Pobierz/' . $docId . '/SolEx.Hurt.Core.Importy.Eksporty.CsvSymbol/'
                ];
                $options[] = [
                    'name' => 'PDF',
                    'url' => rtrim($this->baseUrl, '/') . '/dokumenty/Pobierz/' . $docId . '/SolEx.Hurt.Core.Importy.Eksporty.Pdf/'
                ];
            }

            $documents[] = [
                'date' => $dateRaw,
                'number' => mb_strtoupper($number, 'UTF-8'),
                'doc_id' => $docId,
                'netto' => $netto,
                'brutto' => $brutto,
                'deadline' => $deadline,
                'is_paid' => $isPaid,
                'options' => $options
            ];
        }
        return $documents;
    }

    public function downloadFile($remoteUrl, $localPath, $login, $password)
    {
        if (!$this->isLoggedIn($this->request($this->ordersUrl))) {
            $this->performLogin($login, $password);
        }

        require_once(dirname(__FILE__) . '/../services/AzadaFileHandler.php');
        AzadaFileHandler::ensureDirectory($localPath);

        $ch = curl_init($remoteUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/115.0.0.0 Safari/537.36');
        
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && strlen($content) > 500) {
            // Sprawdzamy czy to plik CSV lub tekstowy
            if (stripos($remoteUrl, 'csv') !== false || stripos($remoteUrl, 'CsvSymbol') !== false) {
                
                // 1. Próba konwersji mb_convert (często stabilniejsza niż iconv)
                // Ustawiamy Windows-1250 jako źródło, ponieważ tak koduje Natura-Med
                $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1250');
                
                // 2. Opcjonalne: Usuwamy BOM (Byte Order Mark), jeśli serwer go dodał, co psuje import w Presta
                $bom = pack('H*','EFBBBF');
                $content = preg_replace("/^$bom/", '', $content);
            }
            
            if (file_put_contents($localPath, $content)) {
                return ['status' => 'success'];
            }
        }
        
        if (file_exists($localPath)) @unlink($localPath);
        return ['status' => 'error'];
    }

    private function performLogin($login, $password)
    {
        $payload = ['Uzytkownik' => $login, 'Haslo' => $password];
        $this->request($this->loginUrl, $payload);
    }

    private function isLoggedIn($html)
    {
        return (stripos($html, 'wyloguj') !== false || stripos($html, 'zamowienia@bigbio.pl') !== false);
    }

    private function request($url, $postData = [], $headers = [])
    {
        $ch = curl_init($url);
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
        if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}