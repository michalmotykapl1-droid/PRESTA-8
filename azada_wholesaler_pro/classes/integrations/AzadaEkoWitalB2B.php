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
        if (empty($login) || empty($password)) {
            return false;
        }
        if (file_exists($this->cookieFile)) {
            @unlink($this->cookieFile);
        }
        $this->performLogin($login, $password);
        $html = $this->request($this->ordersUrl);
        return $this->isLoggedIn($html);
    }

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

        $rows = $this->parseTableRows($html, ['DATA UTWORZENIA', 'NUMER', 'WARTOŚĆ']);
        $documents = [];

        foreach ($rows as $row) {
            $date = $this->getCellText($row, 0);
            $number = $this->getCellText($row, 1);
            $netto = $this->extractNetto($this->getCellText($row, 2));
            $status = $this->getCellText($row, 3);

            if (empty($number)) {
                continue;
            }

            $options = $this->extractDownloadOptions($row);
            if (empty($options)) {
                $previewLink = $this->extractPreviewLink($row);
                if ($previewLink) {
                    $options = $this->extractOptionsFromPreview($previewLink);
                }
            }

            $documents[] = [
                'date' => $date,
                'number' => $number,
                'netto' => $netto,
                'status' => $status,
                'options' => $options
            ];
        }

        return ['status' => 'success', 'data' => $documents];
    }

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

        $rows = $this->parseTableRows($html, ['DATA WYSTAWIENIA', 'NUMER', 'WARTOŚĆ']);
        $documents = [];

        foreach ($rows as $row) {
            $date = $this->getCellText($row, 0);
            $number = $this->getCellText($row, 1);
            $netto = $this->extractNetto($this->getCellText($row, 2));
            $deadline = $this->getCellText($row, 3);
            $isPaid = $this->rowIndicatesPaid($row);

            if (empty($number)) {
                continue;
            }

            $options = $this->extractDownloadOptions($row);
            if (empty($options)) {
                $previewLink = $this->extractPreviewLink($row);
                if ($previewLink) {
                    $options = $this->extractOptionsFromPreview($previewLink);
                }
            }

            $documents[] = [
                'date' => $date,
                'number' => $number,
                'netto' => $netto,
                'deadline' => $deadline,
                'is_paid' => $isPaid,
                'options' => $options
            ];
        }

        return ['status' => 'success', 'data' => $documents];
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
        if ($fp === false) {
            return ['status' => 'error', 'msg' => 'Brak uprawnień do zapisu.'];
        }

        $ch = curl_init($remoteUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36');

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        $fileSize = 0;
        if (file_exists($localPath)) {
            $fileSize = filesize($localPath);
        }

        if ($httpCode == 200 && $fileSize > 50) {
            return ['status' => 'success', 'msg' => 'OK'];
        }
        @unlink($localPath);
        return ['status' => 'error', 'msg' => 'Błąd pobierania (kod: '.$httpCode.', size: '.$fileSize.')'];
    }

    private function performLogin($login, $password)
    {
        $loginPage = $this->request($this->loginUrl);
        $postData = $this->buildLoginPayload($loginPage, $login, $password);
        $actionUrl = $postData['action'];
        unset($postData['action']);

        $this->request($actionUrl, $postData);
    }

    private function buildLoginPayload($html, $login, $password)
    {
        $payload = [];
        $actionUrl = $this->loginUrl;

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $form = $xpath->query('//form')->item(0);
        if ($form) {
            $action = $form->getAttribute('action');
            if (!empty($action)) {
                $actionUrl = $this->normalizeUrl($action);
            }

            $inputs = $xpath->query('.//input', $form);
            $loginField = null;
            $passwordField = null;
            foreach ($inputs as $input) {
                $name = $input->getAttribute('name');
                $type = strtolower($input->getAttribute('type'));
                if (empty($name)) {
                    continue;
                }
                if ($type === 'hidden') {
                    $payload[$name] = $input->getAttribute('value');
                } elseif ($type === 'password') {
                    $passwordField = $name;
                } elseif ($type === 'email' || $type === 'text') {
                    if ($loginField === null || preg_match('/login|email|user|username/i', $name)) {
                        $loginField = $name;
                    }
                }
            }

            if ($loginField) {
                $payload[$loginField] = $login;
            }
            if ($passwordField) {
                $payload[$passwordField] = $password;
            }
        }

        if (!array_filter($payload)) {
            $payload = [
                'login' => $login,
                'email' => $login,
                'password' => $password,
                'haslo' => $password,
                'Uzytkownik' => $login,
                'Haslo' => $password,
            ];
        }

        $payload['action'] = $actionUrl;

        return $payload;
    }

    private function isLoggedIn($html)
    {
        if (empty($html)) {
            return false;
        }
        $markers = ['Wyloguj', 'logout', 'Zamówienia', 'Faktury'];
        foreach ($markers as $marker) {
            if (stripos($html, $marker) !== false) {
                return true;
            }
        }
        if (stripos($html, 'logowanie') !== false && stripos($html, 'hasło') !== false) {
            return false;
        }
        return true;
    }

    private function parseTableRows($html, array $headerHints)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $tables = $xpath->query('//table');

        foreach ($tables as $table) {
            $headerText = '';
            $headers = $xpath->query('.//th', $table);
            foreach ($headers as $th) {
                $headerText .= ' ' . trim($th->nodeValue);
            }
            $matches = true;
            foreach ($headerHints as $hint) {
                if (stripos($headerText, $hint) === false) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                return $xpath->query('.//tbody/tr', $table);
            }
        }

        return $xpath->query('//tr');
    }

    private function getCellText($row, $index)
    {
        $cells = $row->getElementsByTagName('td');
        if ($cells->length <= $index) {
            return '';
        }
        return trim(preg_replace('/\s+/', ' ', $cells->item($index)->nodeValue));
    }

    private function extractNetto($text)
    {
        if (preg_match('/netto\s*([\d\s,\.]+)/iu', $text, $matches)) {
            return trim($matches[1]);
        }
        return trim($text);
    }

    private function rowIndicatesPaid($row)
    {
        $text = trim($row->nodeValue);
        if (stripos($text, 'zapłac') !== false || stripos($text, 'zaplac') !== false) {
            return true;
        }
        return false;
    }

    private function extractDownloadOptions(DOMElement $row)
    {
        $options = [];
        $links = $row->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (empty($href)) {
                continue;
            }
            $href = $this->normalizeUrl($href);
            if (stripos($href, 'download') !== false || stripos($href, 'pobierz') !== false ||
                stripos($href, 'csv') !== false || stripos($href, 'xml') !== false ||
                stripos($href, 'pdf') !== false || stripos($href, 'epp') !== false) {
                $name = trim($link->nodeValue);
                if (empty($name)) {
                    $name = $this->guessNameFromUrl($href);
                }
                $options[] = ['name' => $name, 'url' => $href];
            }
        }

        return $options;
    }

    private function extractPreviewLink(DOMElement $row)
    {
        $links = $row->getElementsByTagName('a');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (empty($href)) {
                continue;
            }
            $href = $this->normalizeUrl($href);
            if (stripos($href, 'documentpreview') !== false || stripos($href, 'zamowienia') !== false || stripos($href, 'faktury') !== false) {
                return $href;
            }
        }
        return null;
    }

    private function extractOptionsFromPreview($url)
    {
        $html = $this->request($url);
        if (empty($html)) {
            return [];
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $options = [];
        $links = $xpath->query('//a');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (empty($href)) {
                continue;
            }
            $href = $this->normalizeUrl($href);
            if (stripos($href, 'download') !== false || stripos($href, 'pobierz') !== false ||
                stripos($href, 'csv') !== false || stripos($href, 'xml') !== false ||
                stripos($href, 'pdf') !== false || stripos($href, 'epp') !== false) {
                $name = trim($link->nodeValue);
                if (empty($name)) {
                    $name = $this->guessNameFromUrl($href);
                }
                $options[] = ['name' => $name, 'url' => $href];
            }
        }

        return $options;
    }

    private function guessNameFromUrl($url)
    {
        $lower = strtolower($url);
        if (strpos($lower, 'pdf') !== false) {
            return 'PDF';
        }
        if (strpos($lower, 'xml') !== false) {
            return 'XML';
        }
        if (strpos($lower, 'epp') !== false) {
            return 'EPP';
        }
        if (strpos($lower, 'csv') !== false) {
            return 'CSV';
        }
        return 'Pobierz';
    }

    private function normalizeUrl($url)
    {
        if (strpos($url, 'http') === 0) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        return rtrim($this->baseUrl, '/') . '/' . ltrim($url, '/');
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
