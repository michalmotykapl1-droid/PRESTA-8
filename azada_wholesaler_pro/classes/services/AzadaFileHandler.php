<?php

class AzadaFileHandler
{
    /**
     * Upewnia się, że katalog docelowy istnieje
     */
    public static function ensureDirectory($path)
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Pobiera plik z URL i zapisuje na dysku.
     * Zwraca tablicę ['status' => 'success/error', 'msg' => '...']
     */
    public static function downloadFile($remoteUrl, $localPath, $cookieFile = null, $userAgent = null)
    {
        // 1. Tworzymy katalog jeśli nie istnieje (np. FV)
        self::ensureDirectory($localPath);

        $fp = fopen($localPath, 'w+');
        if ($fp === false) {
            return ['status' => 'error', 'msg' => 'Brak uprawnień zapisu (fopen) - sprawdz uprawnienia folderu downloads'];
        }

        $ch = curl_init($remoteUrl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($userAgent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        } else {
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        }

        if ($cookieFile && file_exists($cookieFile)) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        }

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        // --- WALIDACJA ROZMIARU (POPRAWIONA) ---
        $fileSize = 0;
        if (file_exists($localPath)) {
            $fileSize = filesize($localPath);
        }

        // ZMIANA: Zamiast > 50 bajtów, ustawiamy > 0.
        // Małe korekty (KFS) mogą być bardzo lekkie, więc musimy je przepuścić.
        if ($httpCode == 200 && $fileSize > 0) {
            return ['status' => 'success', 'msg' => 'OK'];
        } elseif ($httpCode == 200) {
            // Kod 200, ale plik ma 0 bajtów (pusty)
            @unlink($localPath);
            return ['status' => 'error', 'msg' => 'Pusty plik (0 bajtów)'];
        } else {
            @unlink($localPath);
            return ['status' => 'error', 'msg' => 'Błąd HTTP: ' . $httpCode];
        }
    }


    /**
     * Dla wybranych dostawców (np. NaturaMed) normalizuje plik CSV do UTF-8,
     * ale tylko gdy wejście nie jest poprawnym UTF-8.
     */
    public static function normalizeCsvFileToUtf8($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $content = @file_get_contents($filePath);
        if (!is_string($content) || $content === '') {
            return false;
        }

        $bom = "\xEF\xBB\xBF";
        if (strncmp($content, $bom, 3) === 0) {
            $content = substr($content, 3);
        }

        if (preg_match('//u', $content)) {
            return true;
        }

        $converted = false;
        if (function_exists('iconv')) {
            $converted = @iconv('Windows-1250', 'UTF-8//IGNORE', $content);
            if ($converted === false || $converted === '') {
                $converted = @iconv('ISO-8859-2', 'UTF-8//IGNORE', $content);
            }
        }

        if ($converted === false || $converted === '') {
            return false;
        }

        return (@file_put_contents($filePath, $converted) !== false);
    }

    public static function deleteFile($filePath)
    {
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    public static function getSafeFileName($docNumber, $extension = 'csv')
    {
        // Zamiana ukośników w numerze faktury na podkreślniki (FS 1/2026 -> FS_1_2026)
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $docNumber) . '.' . $extension;
    }
}
