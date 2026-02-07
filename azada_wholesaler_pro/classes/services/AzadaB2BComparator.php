<?php

class AzadaB2BComparator
{
    const ACTION_NONE = 0;
    const ACTION_DOWNLOAD_NEW = 1;
    const ACTION_UPDATE_PRICE = 2;
    const ACTION_UPDATE_STATUS = 3;

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
        if (trim($remoteStatus) !== trim($localData['status'])) {
            return self::ACTION_UPDATE_STATUS;
        }

        // 4. Jeśli wszystko to samo -> NIC NIE RÓB
        return self::ACTION_NONE;
    }
}