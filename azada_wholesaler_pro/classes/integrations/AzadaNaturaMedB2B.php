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

    public function scrapeInvoices($login, $password)
    {
        if (empty($login) || empty($password)) {
            return ['status' => 'error', 'msg' => 'Brak danych logowania B2B.'];
        }

        if (!$this->isLoggedIn($this->request($this->invoicesUrl))) {
            $this->performLogin($login, $password);
        }

        $daysBack = (int)Configuration::get('AZADA_FV_DAYS_RANGE', 30);
        $dateFrom = date('Y-m-d', strtotime('-' . ($daysBack < 1 ? 30 : $daysBack) . ' days'));
        $dateTo = date('Y-m-d');

        $html = $this->fetchDocumentListHtml('Faktura', $dateFrom, $dateTo);

        return ['status' => 'success', 'data' => $this->parseHtmlTable($html, 'invoice')];
    }

    public function scrapeOrders($login, $password)
    {
        if (empty($login) || empty($password)) {
            return ['status' => 'error', 'msg' => 'Brak danych logowania B2B.'];
        }

        if (!$this->isLoggedIn($this->request($this->ordersUrl))) {
            $this->performLogin($login, $password);
        }

        $daysBack = (int)Configuration::get('AZADA_B2B_DAYS_RANGE', 7);
        $dateFrom = date('Y-m-d', strtotime('-' . ($daysBack < 1 ? 7 : $daysBack) . ' days'));
        $dateTo = date('Y-m-d');

        // Dla zamówień NaturaMed endpoint zwykle działa z wariantem True/False.
        $html = $this->fetchDocumentListHtml('Zamowienie', $dateFrom, $dateTo, 'True', 'False');

        return ['status' => 'success', 'data' => $this->parseHtmlTable($html, 'order')];
    }

    private function fetchDocumentListHtml($type, $dateFrom, $dateTo, $flag1 = 'False', $flag2 = 'False')
    {
        $ajaxUrl = rtrim($this->baseUrl, '/') . "/dokumenty/PobierzListe/$type/$dateFrom/$dateTo/$flag1/$flag2";
        $headers = ['X-Requested-With: XMLHttpRequest'];
        $response = $this->request($ajaxUrl, [], $headers);
        $json = json_decode($response, true);

        return isset($json['Html']) ? $json['Html'] : $response;
    }

    private function parseHtmlTable($html, $documentType = 'invoice')
    {
        if (empty($html)) {
            return [];
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>');
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query('//tr[td]');
        $documents = [];

        foreach ($rows as $row) {
            $cols = $row->getElementsByTagName('td');
            if ($cols->length < 4) {
                continue;
            }

            $dateRaw = trim($cols->item(0)->nodeValue);
            if ($dateRaw === '' || stripos($dateRaw, 'Razem') !== false) {
                continue;
            }

            $number = trim($cols->item(1)->nodeValue);
            $number = preg_replace('/\s+/', ' ', $number);
            if ($number === '') {
                continue;
            }

            $actionColumnIndex = $cols->length - 1;
            $actionColumn = $cols->item($actionColumnIndex);

            $docId = '';
            $links = $xpath->query('.//a[@href]|.//button[@data-ajax-url]', $actionColumn);
            foreach ($links as $link) {
                $url = $link->hasAttribute('href') ? $link->getAttribute('href') : $link->getAttribute('data-ajax-url');
                if (preg_match('/\/Pobierz\/(\d+)\//', $url, $m)) {
                    $docId = $m[1];
                    break;
                }
            }

            $netto = preg_replace('/[^\d,\.\-]/', '', trim($cols->item(2)->nodeValue));
            $brutto = '';
            $status = '';
            $isPaid = false;
            $deadline = '';

            if ($documentType === 'invoice') {
                $brutto = preg_replace('/[^\d,\.\-]/', '', trim($cols->item(3)->nodeValue));
                if ($cols->length >= 8) {
                    $deadline = trim($cols->item(6)->nodeValue);
                    $paidStatus = $cols->item(7);
                    $isPaid = (
                        stripos($paidStatus->nodeValue, 'TAK') !== false ||
                        stripos($paidStatus->getAttribute('class'), 'zaplacony-tak') !== false
                    );
                }
            } else {
                // Dla zamówień NaturaMed status może być zwrócony jako osobna kolumna
                // (zależnie od wariantu endpointu) lub jako tekst/title wewnątrz wiersza.
                $status = $this->extractOrderStatus($xpath, $row, $cols);

                // Ujednolicenie z innymi integracjami (jak EkoWital/BioPlanet)
                if (mb_stripos($status, 'niezrealizowane', 0, 'UTF-8') !== false) {
                    $status = 'Przekazano do realizacji';
                }

                // Brutto: bierzemy pierwszą sensowną wartość liczbową pomiędzy netto a kolumną akcji.
                for ($i = 3; $i < $actionColumnIndex; $i++) {
                    $candidate = preg_replace('/[^\d,\.\-]/', '', trim($cols->item($i)->nodeValue));
                    if ($candidate !== '' && is_numeric(str_replace(',', '.', $candidate))) {
                        $brutto = $candidate;
                        break;
                    }
                }
                if ($brutto === '') {
                    $brutto = $netto;
                }
            }

            $options = $this->extractDownloadOptions($xpath, $row, $docId);

            $documents[] = [
                'date' => $dateRaw,
                'number' => mb_strtoupper($number, 'UTF-8'),
                'doc_id' => $docId,
                'netto' => $netto,
                'brutto' => $brutto,
                'deadline' => $deadline,
                'status' => $status,
                'is_paid' => $isPaid,
                'options' => $options
            ];
        }

        return $documents;
    }

    private function extractOrderStatus($xpath, $row, $cols)
    {
        $status = '';
        $actionIndex = $cols->length - 1;

        // 1) Najpierw próbujemy znaleźć status w kolumnach pomiędzy netto a kolumną akcji.
        for ($i = 3; $i < $actionIndex; $i++) {
            $txt = trim(preg_replace('/\s+/', ' ', $cols->item($i)->textContent));
            if ($txt !== '' && !$this->looksLikePrice($txt)) {
                $status = $txt;
                break;
            }
        }

        // 2) Potem title ze span (ale pomijamy tooltips z akcji typu "pobierz dokument").
        if ($status === '') {
            for ($i = 0; $i < $actionIndex; $i++) {
                $titleNodes = $xpath->query('.//span[@title]|.//*[@title]', $cols->item($i));
                foreach ($titleNodes as $node) {
                    $title = trim($node->getAttribute('title'));
                    if ($title === '') {
                        continue;
                    }
                    $titleLower = mb_strtolower($title, 'UTF-8');
                    if (strpos($titleLower, 'pobierz') !== false || strpos($titleLower, 'pokaż') !== false || strpos($titleLower, 'zamów') !== false) {
                        continue;
                    }
                    $status = $title;
                    break 2;
                }
            }
        }

        return trim($status);
    }

    private function looksLikePrice($text)
    {
        $clean = trim((string)$text);
        if ($clean === '') {
            return false;
        }

        $clean = str_replace(['PLN', 'zł', ' '], '', $clean);
        $clean = str_replace(',', '.', $clean);

        return (bool)preg_match('/^-?\d+(\.\d+)?$/', $clean);
    }

    private function extractDownloadOptions($xpath, $row, $docId = '')
    {
        $options = [];
        $links = $xpath->query('.//a[@href]', $row);

        foreach ($links as $link) {
            $href = trim($link->getAttribute('href'));
            if ($href === '' || stripos($href, '/dokumenty/Pobierz/') === false) {
                continue;
            }

            $name = trim(preg_replace('/\s+/', ' ', $link->textContent));
            if ($name === '') {
                $name = 'CSV';
            }

            $fullUrl = (strpos($href, 'http') === 0) ? $href : rtrim($this->baseUrl, '/') . $href;
            $options[] = ['name' => $name, 'url' => $fullUrl];
        }

        if (empty($options) && !empty($docId)) {
            $options[] = [
                'name' => 'Pobierz Csv - Symbol',
                'url' => rtrim($this->baseUrl, '/') . '/dokumenty/Pobierz/' . $docId . '/SolEx.Hurt.Core.Importy.Eksporty.CsvSymbol/'
            ];
            $options[] = [
                'name' => 'Pobierz Csv - EAN',
                'url' => rtrim($this->baseUrl, '/') . '/dokumenty/Pobierz/' . $docId . '/SolEx.Hurt.Core.Importy.Eksporty.Csv/'
            ];
        }

        return $options;
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
            if (stripos($remoteUrl, 'csv') !== false || stripos($remoteUrl, 'CsvSymbol') !== false) {
                $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1250');

                $bom = pack('H*', 'EFBBBF');
                $content = preg_replace("/^$bom/", '', $content);
            }

            if (file_put_contents($localPath, $content)) {
                return ['status' => 'success'];
            }
        }

        if (file_exists($localPath)) {
            @unlink($localPath);
        }

        return ['status' => 'error'];
    }

    private function performLogin($login, $password)
    {
        $payload = ['Uzytkownik' => $login, 'Haslo' => $password];
        $this->request($this->loginUrl, $payload);
    }

    private function isLoggedIn($html)
    {
        if (empty($html)) {
            return false;
        }

        return (
            stripos($html, 'wyloguj') !== false ||
            stripos($html, '/wylogowanie') !== false ||
            stripos($html, '/zamowienia') !== false
        );
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

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }
}
