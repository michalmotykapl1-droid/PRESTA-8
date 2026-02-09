<?php

class AzadaBioPlanetB2B
{
    private $loginUrl = 'https://bioplanet.pl/logowanie';
    private $checkUrl = 'https://bioplanet.pl/zamowienia'; 
    private $cookieFile;
    private $debugFile;

    public function __construct()
    {
        if (!defined('_PS_MODULE_DIR_')) {
            define('_PS_MODULE_DIR_', _PS_ROOT_DIR_ . '/modules/');
        }
        $this->cookieFile = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/cookies_bioplanet.txt';
        $this->debugFile  = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/debug.html';
    }

    public function checkLogin($login, $password)
    {
        if (empty($login) || empty($password)) {
            return false;
        }
        if (file_exists($this->cookieFile)) @unlink($this->cookieFile);
        $this->performLogin($login, $password);
        $html = $this->request($this->checkUrl);
        if (strpos($html, $login) !== false || strpos($html, '/logout') !== false || strpos($html, 'Wyloguj') !== false) {
            return true;
        }
        return false;
    }

    // --- POBIERANIE ZAMÓWIEŃ ---
    public function scrapeOrders($login, $password)
    {
        if (empty($login) || empty($password)) {
            return ['status' => 'error', 'msg' => 'Brak danych logowania B2B.'];
        }
        $check = $this->request($this->checkUrl);
        if (strpos($check, 'name="Uzytkownik"') !== false || strpos($check, 'login-form') !== false) {
            $this->performLogin($login, $password);
        }

        $daysBack = (int)Configuration::get('AZADA_B2B_DAYS_RANGE', 7);
        if ($daysBack < 1) $daysBack = 1;
        $dateFrom = date('Y-m-d', strtotime("-$daysBack days"));
        $dateTo = date('Y-m-d');
        
        $ajaxUrl = "https://bioplanet.pl/dokumenty/PobierzListe/Zamowienie/$dateFrom/$dateTo/True/False?mozliwaPlatnosc=False&czysadane=False&szukanieFraza=";
        $html = $this->request($ajaxUrl);
        return $this->parseHtmlTable($html);
    }

    // --- POBIERANIE FAKTUR (POPRAWIONE) ---
    public function scrapeInvoices($login, $password)
    {
        if (empty($login) || empty($password)) {
            return ['status' => 'error', 'msg' => 'Brak danych logowania B2B.'];
        }
        // 1. Logowanie
        $check = $this->request('https://bioplanet.pl/faktury');
        if (strpos($check, 'name="Uzytkownik"') !== false) {
            $this->performLogin($login, $password);
        }

        // 2. Budowanie URL
        $daysBack = (int)Configuration::get('AZADA_FV_DAYS_RANGE', 30);
        if ($daysBack < 1) $daysBack = 30;
        
        $dateFrom = date('Y-m-d', strtotime("-$daysBack days"));
        $dateTo = date('Y-m-d'); 

        // ZMIANA TUTAJ: Zmieniłem "True" na "False" w trzecim parametrze.
        // Było: .../Faktura/$dateFrom/$dateTo/True/False... (Tylko niezapłacone)
        // Jest: .../Faktura/$dateFrom/$dateTo/False/False... (Wszystkie)
        $ajaxUrl = "https://bioplanet.pl/dokumenty/PobierzListe/Faktura/$dateFrom/$dateTo/False/False?mozliwaPlatnosc=False&czysadane=False&szukanieFraza=";

        $html = $this->request($ajaxUrl);
        return $this->parseInvoicesTable($html);
    }

    public function downloadFile($remoteUrl, $localPath, $login, $password)
    {
        if (empty($login) || empty($password)) {
            return ['status' => 'error', 'msg' => 'Brak danych logowania B2B.'];
        }
        $test = $this->request($this->checkUrl);
        if (strpos($test, 'name="Uzytkownik"') !== false) {
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        $fileSize = 0;
        if (file_exists($localPath)) $fileSize = filesize($localPath);

        if ($httpCode == 200 && $fileSize > 50) {
            return ['status' => 'success', 'msg' => 'OK'];
        } else {
            @unlink($localPath);
            return ['status' => 'error', 'msg' => 'Błąd pobierania (kod: '.$httpCode.', size: '.$fileSize.')'];
        }
    }

    private function performLogin($login, $password)
    {
        $postData = [
            'Uzytkownik' => $login,
            'Haslo' => $password,
            'logowanie' => 'ZALOGUJ'
        ];
        $this->request('https://bioplanet.pl/logowanie/m?ReturnURL=/zamowienia', $postData);
    }

    private function parseHtmlTable($html)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//tr');
        $documents = [];

        $strategy = Configuration::get('AZADA_B2B_FETCH_STRATEGY', 'strict');

        foreach ($rows as $row) {
            $cols = $row->getElementsByTagName('td');
            if ($cols->length >= 4) {
                $rawDocNumber = trim($cols->item(1)->nodeValue);
                if (stripos($rawDocNumber, 'Planowany') !== false) {
                    $parts = preg_split('/Planowany/i', $rawDocNumber);
                    $docNumber = trim($parts[0]);
                } else {
                    $docNumber = $rawDocNumber;
                }
                $docNumber = preg_replace('/\s+/', ' ', $docNumber);
                $docStatus = trim($cols->item(4)->nodeValue); 
                
                if (empty($docNumber)) continue;
                
                if ($strategy === 'strict') {
                    $isValidPrefix = (stripos($docNumber, 'ZK') !== false || stripos($docNumber, 'FV') !== false || stripos($docNumber, 'WZ') !== false);
                    if (!$isValidPrefix) continue; 
                }

                $options = $this->extractDownloadOptions($xpath, $row);

                $documents[] = [
                    'date' => trim($cols->item(0)->nodeValue),
                    'number' => $docNumber,
                    'netto' => trim($cols->item(2)->nodeValue),
                    'status' => $docStatus,
                    'options' => $options
                ];
            }
        }
        return ['status' => 'success', 'data' => $documents];
    }

    private function parseInvoicesTable($html)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//tr');
        $invoices = [];

        foreach ($rows as $row) {
            $cols = $row->getElementsByTagName('td');
            if ($cols->length >= 8) {
                $date = trim($cols->item(0)->nodeValue);
                $docNumber = trim($cols->item(1)->nodeValue);
                $netto = trim($cols->item(3)->nodeValue);
                $deadline = trim($cols->item(7)->nodeValue);
                $isPaidTxt = trim($cols->item(8)->nodeValue);
                $isPaid = (stripos($isPaidTxt, 'TAK') !== false);

                if (stripos($docNumber, 'FS') === false && stripos($docNumber, 'KFS') === false) {
                    continue;
                }

                $options = $this->extractDownloadOptions($xpath, $row);

                $invoices[] = [
                    'date' => $date,
                    'number' => $docNumber,
                    'netto' => $netto,
                    'deadline' => $deadline,
                    'is_paid' => $isPaid,
                    'options' => $options
                ];
            }
        }
        return ['status' => 'success', 'data' => $invoices];
    }

    private function extractDownloadOptions($xpath, $row)
    {
        $options = [];
        $dropdowns = $xpath->query('.//ul[contains(@class, "dropdown-menu")]//a', $row);
        
        if ($dropdowns->length > 0) {
            foreach ($dropdowns as $link) {
                $href = $link->getAttribute('href');
                $text = trim($link->nodeValue);
                $text = str_replace('Pobierz ', '', $text);
                if (strpos($href, 'http') === false) $href = 'https://bioplanet.pl' . $href;
                
                if (stripos($href, 'Pobierz') !== false || stripos($href, 'download') !== false) {
                    $options[] = ['name' => $text, 'url' => $href];
                }
            }
        } else {
             $links = $row->getElementsByTagName('a');
             foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if (stripos($href, 'csv') !== false || stripos($href, 'download') !== false) {
                    if (strpos($href, 'http') === false) $href = 'https://bioplanet.pl' . $href;
                    $options[] = ['name' => 'Pobierz domyślny', 'url' => $href];
                    break;
                }
             }
        }
        return $options;
    }

    private function request($url, $postData = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');

        if (!empty($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
