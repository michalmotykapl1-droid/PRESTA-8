<?php

class AzadaFileHelper
{
    /**
     * Nowa metoda: Sprawdza czy URL istnieje i odpowiada (Ping)
     * Zwraca TRUE jeśli serwer odpowie kodem 200 (OK).
     */
    public static function checkUrl($url)
    {
        if (empty($url)) return false;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true); // Nie pobieraj ciała, tylko nagłówki
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Czekaj max 10 sekund
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Ignoruj problemy z certyfikatem SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // Udajemy przeglądarkę, żeby hurtownia nas nie zablokowała
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'); 
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Akceptujemy kody 200 (OK) oraz 301/302 (Przekierowania)
        if ($httpCode >= 200 && $httpCode < 400) {
            return true;
        }
        
        return false;
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