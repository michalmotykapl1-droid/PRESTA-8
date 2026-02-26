<?php

class DataStandardizer
{
    /**
     * Czyści numer telefonu (usuwa spacje, myślniki, dodaje kierunkowy jeśli brak)
     */
    public static function standardizePhone($phone)
    {
        // Usuń wszystko co nie jest cyfrą lub plusem
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);

        // Opcjonalnie: Domyślny kierunkowy dla Polski (jeśli numer ma 9 cyfr)
        if (strlen($cleanPhone) == 9 && substr($cleanPhone, 0, 1) != '0') {
            $cleanPhone = '+48' . $cleanPhone;
        }

        return $cleanPhone;
    }

    /**
     * Czyści kod pocztowy (format XX-XXX)
     */
    public static function standardizePostcode($postcode)
    {
        // Usuń wszystko co nie jest cyfrą
        $digits = preg_replace('/[^0-9]/', '', $postcode);

        // Jeśli mamy 5 cyfr, sformatuj jako XX-XXX
        if (strlen($digits) == 5) {
            return substr($digits, 0, 2) . '-' . substr($digits, 2, 3);
        }

        return $postcode; // Zwróć oryginał jeśli format jest inny
    }

    /**
     * Usuwa białe znaki z początku/końca i podwójne spacje
     */
    public static function cleanString($text)
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }
}