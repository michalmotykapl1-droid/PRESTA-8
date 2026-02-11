<?php

class AzadaFileHelper
{
    /**
     * Kompatybilna metoda bool (używana w wielu miejscach).
     */
    public static function checkUrl($url)
    {
        $result = self::checkUrlDetailed($url);
        return !empty($result['status']);
    }

    /**
     * Szczegółowa diagnostyka połączenia URL.
     * Zwraca: status(bool), http_code(int), msg(string)
     */
    public static function checkUrlDetailed($url)
    {
        if (empty($url)) {
            return ['status' => false, 'http_code' => 0, 'msg' => 'Brak URL API.'];
        }

        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36';

        // 1) HEAD
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_exec($ch);
        $headCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headErrNo = (int)curl_errno($ch);
        $headErr = (string)curl_error($ch);
        curl_close($ch);

        if ($headCode >= 200 && $headCode < 400) {
            return ['status' => true, 'http_code' => $headCode, 'msg' => 'Połączenie OK (HEAD).'];
        }

        if (in_array($headCode, [401, 403, 405], true) && $headErrNo === 0) {
            return ['status' => true, 'http_code' => $headCode, 'msg' => 'Endpoint odpowiada, ale ogranicza metodę HEAD.'];
        }

        // 2) GET (bez pobierania całego pliku)
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_RANGE, '0-256');

        $body = curl_exec($ch);
        $getCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $getErrNo = (int)curl_errno($ch);
        $getErr = (string)curl_error($ch);
        curl_close($ch);

        if ($getCode >= 200 && $getCode < 400) {
            return ['status' => true, 'http_code' => $getCode, 'msg' => 'Połączenie OK (GET fallback).'];
        }

        if (in_array($getCode, [401, 403, 405], true) && $getErrNo === 0) {
            return ['status' => true, 'http_code' => $getCode, 'msg' => 'Endpoint API odpowiada (dostęp ograniczony przez serwer).'];
        }

        $err = $getErrNo ? $getErr : ($headErrNo ? $headErr : 'Nieznany błąd połączenia.');
        $code = $getCode ?: $headCode;
        return [
            'status' => false,
            'http_code' => (int)$code,
            'msg' => 'Brak połączenia z API. HTTP: ' . (int)$code . '. ' . $err,
        ];
    }

    /**
     * Stara metoda do pobierania nagłówków (używana przy mapowaniu)
     */
    public static function getCsvHeaders($url, $delimiter = ';')
    {
        $content = self::getChunk($url, 2048);
        if (!$content) return false;

        if ($delimiter === 'auto' || empty($delimiter)) {
            $delimiter = self::detectDelimiter($content);
        }

        $lines = explode("\n", $content);
        $headerLine = reset($lines);

        if (empty($headerLine)) return false;

        $headers = str_getcsv($headerLine, $delimiter);
        return array_map(function($h) {
            return trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)); 
        }, $headers);
    }

    public static function getChunk($url, $size = 2048)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RANGE, "0-" . ($size - 1));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; PrestaShopModule/1.0)');
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && $data) return $data;
        return false;
    }

    public static function detectDelimiter($contentLine)
    {
        $delimiters = [';' => 0, ',' => 0, "\t" => 0, '|' => 0];
        foreach ($delimiters as $delimiter => &$count) {
            $count = substr_count($contentLine, $delimiter);
        }
        return array_search(max($delimiters), $delimiters);
    }
}
