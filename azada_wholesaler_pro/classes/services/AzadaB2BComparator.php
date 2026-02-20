<?php

class AzadaB2BComparator
{
    const ACTION_NONE = 0;
    const ACTION_DOWNLOAD_NEW = 1;
    const ACTION_UPDATE_PRICE = 2;
    const ACTION_UPDATE_STATUS = 3;

    private static function normalizeStatusForCompare($status)
    {
        $status = trim((string)$status);
        if ($status === '') {
            return '';
        }

        $status = str_replace(["\xc2\xa0", "\xa0"], ' ', $status);
        $status = preg_replace('/\s+/u', ' ', $status);
        $statusLower = mb_strtolower($status, 'UTF-8');

        if (strpos($statusLower, 'różnic') !== false || strpos($statusLower, 'roznic') !== false) {
            return 'ZAMÓWIENIE RÓŻNICOWE';
        }

        if (strpos($statusLower, 'nowe') !== false) {
            return 'NOWE ZAMÓWIENIE';
        }

        if (strpos($statusLower, 'anul') !== false || strpos($statusLower, 'brak towar') !== false) {
            return 'ANULOWANE - BRAK TOWARU';
        }

        if (strpos($statusLower, 'zrealiz') !== false) {
            return 'ZREALIZOWANE';
        }

        if (
            strpos($statusLower, 'przekazan') !== false ||
            strpos($statusLower, 'niezrealiz') !== false ||
            strpos($statusLower, 'w realiz') !== false ||
            strpos($statusLower, 'w trakcie') !== false ||
            strpos($statusLower, 'realizacji') !== false ||
            strpos($statusLower, 'oczek') !== false
        ) {
            return 'PRZEKAZANE DO MAGAZYNU';
        }

        return mb_strtoupper($status, 'UTF-8');
    }

    /**
     * Porównuje dane z hurtowni (Remote) z danymi w bazie (Local)
     * Zwraca kod akcji (co należy zrobić).
     */
    public static function compare($remoteDocNumber, $remoteNettoRaw, $remoteStatus, $localData)
    {
        // 1. Jeśli nie ma w bazie -> POBIERZ
        if (!$localData || empty($localData)) {
            return self::ACTION_DOWNLOAD_NEW;
        }

        // 2. Jeśli jest w bazie, sprawdzamy CENĘ
        // Musimy wyczyścić cenę z hurtowni do float
        $remotePrice = (float)AzadaCsvParser::sanitizePrice($remoteNettoRaw);
        $localPrice = (float)$localData['amount_netto'];

        // Tolerancja 1 grosz
        if (abs($remotePrice - $localPrice) > 0.01) {
            return self::ACTION_UPDATE_PRICE;
        }

        // 3. Sprawdzamy STATUS
        // Normalizujemy stringi (trim)
        $remoteStatusNorm = self::normalizeStatusForCompare($remoteStatus);
        $localStatusNorm = self::normalizeStatusForCompare(isset($localData['status']) ? $localData['status'] : '');

        if ($remoteStatusNorm !== $localStatusNorm) {
            return self::ACTION_UPDATE_STATUS;
        }

        // 4. Jeśli wszystko to samo -> NIC NIE RÓB
        return self::ACTION_NONE;
    }
}
