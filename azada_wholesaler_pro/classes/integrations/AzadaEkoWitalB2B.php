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

        $debug = [];
        $response = $this->requestWithInfo($this->invoicesUrl);
        $html = $response['body'];
        $debug[] = 'GET ' . $this->invoicesUrl . ' => HTTP ' . $response['http_code'] . ', bytes: ' . $response['bytes'];
        if (!empty($response['error'])) {
            $debug[] = 'Błąd CURL: ' . $response['error'];
        }

        if (empty($html)) {
            return ['status' => 'error', 'msg' => 'Pusta odpowiedź z listy faktur.', 'debug' => $debug];
        }

        if (!$this->isLoggedIn($html)) {
            $debug[] = 'Sesja niezalogowana, próba logowania.';
            $this->performLogin($login, $password);
            $response = $this->requestWithInfo($this->invoicesUrl);
            $html = $response['body'];
            $debug[] = 'GET (po logowaniu) ' . $this->invoicesUrl . ' => HTTP ' . $response['http_code'] . ', bytes: ' . $response['bytes'];
            if (!empty($response['error'])) {
                $debug[] = 'Błąd CURL po logowaniu: ' . $response['error'];
            }
            if (!$this->isLoggedIn($html)) {
                return [
                    'status' => 'error',
                    'msg' => 'Brak dostępu do listy faktur (logowanie nieudane lub sesja wygasła).',
                    'debug' => $debug
                ];
            }
        }

        $rows = $this->parseTableRows($html, ['DATA WYSTAWIENIA', 'NUMER']);
        $debug[] = 'Znalezione wiersze tabeli: ' . $rows->length;
        $documents = [];

        foreach ($rows as $row) {
            $date = $this->getCellText($row, 0);
            $number = $this->getCellText($row, 1);
            $netto = $this->extractNetto($this->getCellText($row, 2));
            $deadline = $this->getCellText($row, 3);
            $isPaid = false;

            if (empty($number)) {
                continue;
            }

            $options = $this->extractDownloadOptions($row);
            $debug[] = 'Dokument: ' . $number . ' (linki w tabeli: ' . count($options) . ')';
            if (empty($options)) {
                $previewLink = $this->extractPreviewLink($row);
                if ($previewLink) {
                    $debug[] = 'Podgląd: ' . $previewLink;
                    $options = $this->extractOptionsFromPreview($previewLink);
                    $debug[] = 'Opcje z podglądu: ' . count($options);
                } else {
                    $debug[] = 'Brak linku podglądu dla dokumentu: ' . $number;
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

        if (empty($documents)) {
            $debug[] = 'Brak dokumentów z HTML, próba pobrania listy przez AJAX.';
            if (method_exists($this, 'fetchInvoicesFromAjax')) {
                $ajaxResult = $this->fetchInvoicesFromAjax($html, $debug);
                if (!empty($ajaxResult['documents'])) {
                    $documents = $ajaxResult['documents'];
                }
                if (!empty($ajaxResult['debug'])) {
                    $debug = array_merge($debug, $ajaxResult['debug']);
                }
            } else {
                $debug[] = 'Brak metody fetchInvoicesFromAjax w klasie integracji.';
            }
        }

        if (empty($documents)) {
            $debug[] = 'Brak dokumentów do zwrócenia po parsowaniu.';
        }

        return ['status' => 'success', 'data' => $documents, 'debug' => $debug];
    }

    public function debugFetchInvoicesPage($login, $password)
    {
        if (empty($login) || empty($password)) {
            return ['status' => 'error', 'msg' => 'Brak danych logowania B2B.'];
        }

        $debug = [];
        $response = $this->requestWithInfo($this->invoicesUrl);
        $html = $response['body'];
        $debug[] = 'GET ' . $this->invoicesUrl . ' => HTTP ' . $response['http_code'] . ', bytes: ' . $response['bytes'];
        if (!empty($response['error'])) {
            $debug[] = 'Błąd CURL: ' . $response['error'];
        }

        $isLoggedIn = $this->isLoggedIn($html);
        if (!$isLoggedIn) {
            $debug[] = 'Sesja niezalogowana, próba logowania.';
            $this->performLogin($login, $password);
            $response = $this->requestWithInfo($this->invoicesUrl);
            $html = $response['body'];
            $debug[] = 'GET (po logowaniu) ' . $this->invoicesUrl . ' => HTTP ' . $response['http_code'] . ', bytes: ' . $response['bytes'];
            if (!empty($response['error'])) {
                $debug[] = 'Błąd CURL po logowaniu: ' . $response['error'];
            }
            $isLoggedIn = $this->isLoggedIn($html);
        }

        return [
            'status' => $isLoggedIn ? 'success' : 'error',
            'msg' => $isLoggedIn ? 'OK' : 'Brak dostępu do listy faktur (logowanie nieudane lub sesja wygasła).',
            'debug' => $debug,
            'html' => $html,
        ];
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
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if (preg_match('/netto\s*([\d\s,\.]+)/iu', $text, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/([\d\s,\.]+)\s*PLN/i', $text, $matches)) {
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

        $buttons = $xpath->query('//button[@data-s-document-download-url]|//button[@data-s-document-download]');
        foreach ($buttons as $button) {
            $href = $button->getAttribute('data-s-document-download-url');
            if (empty($href)) {
                $href = $button->getAttribute('data-s-document-download');
            }
            if (empty($href)) {
                continue;
            }
            $href = $this->normalizeUrl($href);
            $name = trim($button->nodeValue);
            if (empty($name)) {
                $name = $this->guessNameFromUrl($href);
            }
            $options[] = ['name' => $name, 'url' => $href];
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

    private function requestWithInfo($url, $postData = [], $headers = [])
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
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);

        return [
            'body' => $body,
            'http_code' => isset($info['http_code']) ? $info['http_code'] : 0,
            'bytes' => is_string($body) ? strlen($body) : 0,
            'error' => $error,
        ];
    }

    private function fetchInvoicesFromAjax($html, $debug)
    {
        $config = $this->extractInvoiceListConfig($html);
        if (empty($config['listUrl'])) {
            return ['documents' => [], 'debug' => array_merge($debug, ['Brak listUrl w HTML (kontrolka InvoiceListControl).'])];
        }

        $absoluteUrl = $this->normalizeUrl($config['listUrl']);
        $headers = [
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . $this->invoicesUrl,
        ];

        $range = $this->buildInvoiceDateRange();
        $debug[] = 'Zakres dni z konfiguracji AZADA_FV_DAYS_RANGE: ' . $range['daysBack'] . ' (od ' . $range['dateFrom'] . ' do ' . $range['dateTo'] . ').';

        $documentsAll = $this->fetchInvoiceListByMode(10, $range, $absoluteUrl, $headers, $debug);
        $documentsUnpaid = $this->fetchInvoiceListByMode(0, $range, $absoluteUrl, $headers, $debug);

        if (!empty($documentsAll)) {
            $documents = $this->applyPaidStatusFromUnpaid($documentsAll, $documentsUnpaid);
            return ['documents' => $documents, 'debug' => $debug];
        }

        return ['documents' => $documentsAll, 'debug' => $debug];
    }

    private function extractInvoiceListConfig($html)
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        $scriptNodes = $xpath->query('//div[contains(@class,"kontrolka-InvoiceListControl")]/script[@type="application/json"]');
        foreach ($scriptNodes as $node) {
            $jsonText = trim($node->nodeValue);
            if (empty($jsonText)) {
                continue;
            }
            $data = json_decode($jsonText, true);
            if (!is_array($data) || empty($data['listUrl'])) {
                continue;
            }
            $range = $this->extractDateRange($data);
            return [
                'listUrl' => $data['listUrl'],
                'dateFrom' => $range['from'],
                'dateTo' => $range['to'],
                'mode' => isset($data['mode']) ? (int)$data['mode'] : 10,
            ];
        }
        return [
            'listUrl' => null,
            'dateFrom' => null,
            'dateTo' => null,
            'mode' => 10,
        ];
    }

    private function extractDateRange($data)
    {
        if (empty($data['dateRanges'])) {
            return ['from' => null, 'to' => null];
        }
        $raw = $data['dateRanges'];
        if (!is_string($raw)) {
            return ['from' => null, 'to' => null];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = json_decode(html_entity_decode($raw), true);
        }
        if (!is_array($decoded)) {
            return ['from' => null, 'to' => null];
        }

        foreach ($decoded as $rangeJson) {
            $range = json_decode($rangeJson, true);
            if (!is_array($range) || count($range) < 2) {
                continue;
            }
            $from = $this->formatDateOnly($range[0]);
            $to = $this->formatDateOnly($range[1]);
            if ($from && $to) {
                return ['from' => $from, 'to' => $to];
            }
        }
        return ['from' => null, 'to' => null];
    }

    private function formatDateOnly($value)
    {
        if (empty($value)) {
            return null;
        }
        $parts = explode('T', $value);
        return $parts[0] ?? null;
    }

    private function buildInvoiceDateRange()
    {
        $daysBack = (int)Configuration::get('AZADA_FV_DAYS_RANGE', 30);
        if ($daysBack < 1) {
            $daysBack = 30;
        }

        $dateTo = date('Y-m-d');
        $dateFrom = date('Y-m-d', strtotime("-{$daysBack} days"));
        $offset = date('P');

        return [
            'daysBack' => $daysBack,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'dateFromIso' => $dateFrom . 'T00:00:00',
            'dateToIso' => $dateTo . 'T23:59:59',
            'dateFromOffset' => $dateFrom . 'T00:00:00' . $offset,
            'dateToOffset' => $dateTo . 'T23:59:59' . $offset,
        ];
    }

    private function buildInvoicePayloadVariants($modeType, $range)
    {
        $base = [
            'dateFrom' => $range['dateFrom'],
            'dateTo' => $range['dateTo'],
            'dateFromIso' => $range['dateFromIso'],
            'dateToIso' => $range['dateToIso'],
            'dateFromOffset' => $range['dateFromOffset'],
            'dateToOffset' => $range['dateToOffset'],
            'search' => '',
        ];

        return [
            array_merge($base, [
                'mode' => $modeType,
                'documentListMode' => $modeType,
                'documentMode' => $modeType,
                'listMode' => $modeType,
                'modeType' => $modeType,
                'orderColumn' => 'CreationDate',
                'isDescendingOrder' => true,
                'page' => 1,
                'onlyUncompleted' => null,
                'paymentMode' => $modeType,
                'onlyUnpaid' => $modeType === 0 ? 1 : 0,
                'includePaid' => $modeType === 0 ? 0 : 1,
            ]),
            array_merge($base, [
                'mode' => $modeType,
                'documentListMode' => $modeType,
                'documentListModeId' => $modeType,
                'documentMode' => $modeType,
                'listMode' => $modeType,
                'modeType' => $modeType,
                'orderColumn' => 'CreationDate',
                'isDescendingOrder' => true,
                'page' => 1,
                'onlyUncompleted' => null,
                'paymentMode' => $modeType,
                'onlyUnpaid' => $modeType === 0 ? 'true' : 'false',
                'includePaid' => $modeType === 0 ? 'false' : 'true',
            ]),
            array_merge($base, [
                'documentListMode' => $modeType,
                'documentListModeId' => $modeType,
                'modeType' => $modeType,
                'orderColumn' => 'CreationDate',
                'isDescendingOrder' => true,
                'page' => 1,
                'onlyUncompleted' => null,
                'onlyUnpaid' => $modeType === 0 ? 'true' : 'false',
                'includePaid' => $modeType === 0 ? 'false' : 'true',
            ]),
        ];
    }

    private function fetchInvoiceListByMode($modeType, $range, $absoluteUrl, $headers, &$debug)
    {
        $label = $modeType === 0 ? 'tryb płatności' : 'tryb przeglądania';
        $debug[] = 'Pobieranie listy faktur: ' . $label . ' (modeType=' . $modeType . ').';

        $documents = [];
        foreach ($this->buildInvoicePayloadVariants($modeType, $range) as $index => $payload) {
            $response = $this->requestWithInfo($absoluteUrl, $payload, $headers);
            $debug[] = 'AJAX POST #' . ($index + 1) . ' ' . $absoluteUrl . ' (' . $payload['dateFrom'] . ' -> ' . $payload['dateTo'] . ', modeType=' . $modeType . ') => HTTP ' . $response['http_code'] . ', bytes: ' . $response['bytes'];
            if (!empty($response['error'])) {
                $debug[] = 'Błąd CURL (AJAX POST): ' . $response['error'];
            }
            $documents = $this->parseInvoicesFromAjaxResponse($response['body'], $debug);
            if (!empty($documents)) {
                break;
            }
        }

        if (!empty($documents)) {
            return $documents;
        }

        $queryUrl = $absoluteUrl . (strpos($absoluteUrl, '?') === false ? '?' : '&') . http_build_query([
            'mode' => $modeType,
            'documentListMode' => $modeType,
            'documentListModeId' => $modeType,
            'documentMode' => $modeType,
            'listMode' => $modeType,
            'modeType' => $modeType,
            'includePaid' => $modeType === 0 ? 'false' : 'true',
            'onlyUnpaid' => $modeType === 0 ? 'true' : 'false',
            'dateFrom' => $range['dateFrom'],
            'dateTo' => $range['dateTo'],
            'dateFromOffset' => $range['dateFromOffset'],
            'dateToOffset' => $range['dateToOffset'],
            'orderColumn' => 'CreationDate',
            'isDescendingOrder' => 'true',
            'page' => 1,
            'onlyUncompleted' => null,
        ]);
        $response = $this->requestWithInfo($queryUrl, [], $headers);
        $debug[] = 'AJAX GET (fallback) ' . $queryUrl . ' => HTTP ' . $response['http_code'] . ', bytes: ' . $response['bytes'];
        if (!empty($response['error'])) {
            $debug[] = 'Błąd CURL (AJAX GET): ' . $response['error'];
        }
        return $this->parseInvoicesFromAjaxResponse($response['body'], $debug);
    }

    private function applyPaidStatusFromUnpaid($documentsAll, $documentsUnpaid)
    {
        $unpaidIndex = $this->buildInvoiceIndex($documentsUnpaid);
        foreach ($documentsAll as &$document) {
            $key = $this->buildInvoiceKey($document);
            $document['is_paid'] = isset($unpaidIndex[$key]) ? 0 : 1;
        }
        return $documentsAll;
    }

    private function buildInvoiceIndex($documents)
    {
        $index = [];
        foreach ($documents as $document) {
            $key = $this->buildInvoiceKey($document);
            if ($key !== '') {
                $index[$key] = true;
            }
        }
        return $index;
    }

    private function buildInvoiceKey($document)
    {
        if (!empty($document['doc_id'])) {
            return 'id:' . $document['doc_id'];
        }
        if (!empty($document['number'])) {
            return 'nr:' . $document['number'];
        }
        return '';
    }

    private function parseInvoicesFromAjaxResponse($body, $debug)
    {
        $documents = [];
        $trimmed = ltrim($body);
        $isJson = (strpos($trimmed, '{') === 0 || strpos($trimmed, '[') === 0);

        if ($isJson) {
            $data = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $debug[] = 'AJAX odpowiedź JSON: OK';
                $items = [];
                if (isset($data['documents']) && is_array($data['documents'])) {
                    $items = $data['documents'];
                } elseif (isset($data['items']) && is_array($data['items'])) {
                    $items = $data['items'];
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $items = $data['data'];
                } elseif (is_array($data) && isset($data[0])) {
                    $items = $data;
                }

                foreach ($items as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $number = $this->pickFirstValue($item, ['number', 'documentNumber', 'docNumber', 'numer']);
                    if (empty($number)) {
                        continue;
                    }
                    $docId = $this->pickFirstValue($item, ['id', 'documentId', 'docId', 'document_id']);
                    $options = $this->extractOptionsFromItem($item);
                    if (empty($options) && !empty($docId)) {
                        $options = $this->extractOptionsFromPreview($this->buildPreviewUrl($docId));
                    }
                    $documents[] = [
                        'date' => $this->pickFirstValue($item, ['date', 'issueDate', 'data', 'dataWystawienia']),
                        'number' => $number,
                        'doc_id' => $docId,
                        'netto' => $this->pickFirstValue($item, ['netto', 'amountNet', 'valueNet', 'kwotaNetto', 'value', 'gross']),
                        'deadline' => $this->pickFirstValue($item, ['deadline', 'dueDate', 'termin', 'dataPlatnosci']),
                        'is_paid' => (bool)$this->pickFirstValue($item, ['isPaid', 'paid', 'zaplacona']),
                        'options' => $options,
                    ];
                }

                return $documents;
            }
            $debug[] = 'AJAX odpowiedź JSON: błąd parsowania (' . json_last_error_msg() . ')';
        }

        $debug[] = 'AJAX odpowiedź traktowana jako HTML.';
        return $this->parseInvoicesFromAjaxHtml($body);
    }

    private function parseInvoicesFromAjaxHtml($html)
    {
        $documents = [];
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//tr[td]');

        foreach ($rows as $row) {
            $number = $this->getCellText($row, 1);
            if (empty($number)) {
                continue;
            }
            $docId = $xpath->evaluate('string(.//*[@data-s-document-list-document-id][1]/@data-s-document-list-document-id)', $row);
            if (empty($docId)) {
                $docId = $xpath->evaluate('string(./@data-s-document-list-document-id)', $row);
            }

            $options = $this->extractDownloadOptions($row);
            if (empty($options) && !empty($docId)) {
                $options = $this->extractOptionsFromPreview($this->buildPreviewUrl($docId));
            }

            $documents[] = [
                'date' => $this->getCellText($row, 0),
                'number' => $number,
                'doc_id' => $docId,
                'netto' => $this->extractNetto($this->getCellText($row, 2)),
                'deadline' => $this->getCellText($row, 3),
                'is_paid' => false,
                'options' => $options,
            ];
        }

        return $documents;
    }

    private function buildPreviewUrl($docId)
    {
        return $this->normalizeUrl('/documentpreview/' . $docId);
    }

    private function extractOptionsFromItem($item)
    {
        $options = [];
        $possibleKeys = ['downloadLinks', 'links', 'files', 'documentFiles', 'options'];
        foreach ($possibleKeys as $key) {
            if (!empty($item[$key]) && is_array($item[$key])) {
                foreach ($item[$key] as $entry) {
                    if (is_array($entry)) {
                        $url = $this->pickFirstValue($entry, ['url', 'href', 'link']);
                        if ($url) {
                            $options[] = [
                                'name' => $this->pickFirstValue($entry, ['name', 'label', 'type']) ?: $this->guessNameFromUrl($url),
                                'url' => $this->normalizeUrl($url),
                            ];
                        }
                    }
                }
            }
        }
        return $options;
    }

    private function pickFirstValue($item, $keys)
    {
        foreach ($keys as $key) {
            if (isset($item[$key]) && $item[$key] !== '') {
                return $item[$key];
            }
        }
        return '';
    }
}
