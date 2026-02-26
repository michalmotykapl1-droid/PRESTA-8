<?php

// Comparator może być używany w CRON i panelu BO, dlatego włączamy repozytorium,
// aby móc wymusić kanoniczny katalog FV (migracja z legacy downloads/fv).
require_once(dirname(__FILE__) . '/AzadaDbRepository.php');

class AzadaInvoiceComparator
{
    const ACTION_NONE = 0;          // Nic nie rób
    const ACTION_DOWNLOAD_NEW = 1;  // Nowa faktura -> Pobierz
    const ACTION_UPDATE_STATUS = 2; // Zmieniono status płatności -> Aktualizuj
    const ACTION_UPDATE_FILE = 3;   // Zmieniono kwotę/plik -> Pobierz ponownie

    public static function compare($remoteNumber, $remoteNetto, $remoteIsPaid, $dbInvoice)
    {
        // 1. Jeśli nie ma w bazie -> NOWY
        if (!$dbInvoice) {
            return self::ACTION_DOWNLOAD_NEW;
        }

        // 2. Normalizacja danych do porównania
        // Usuwamy " PLN" i spacje, zamieniamy przecinek na kropkę
        $cleanRemoteNetto = (float)str_replace([',', ' ', 'PLN'], ['.', '', ''], $remoteNetto);
        $cleanDbNetto = (float)str_replace([',', ' ', 'PLN'], ['.', '', ''], $dbInvoice['amount_netto']);

        $remotePaidInt = $remoteIsPaid ? 1 : 0;
        $dbPaidInt = (int)$dbInvoice['is_paid'];

        // 3. Sprawdzamy różnice
        // Czy zmieniła się kwota? (np. korekta w locie)
        if (abs($cleanRemoteNetto - $cleanDbNetto) > 0.02) {
            return self::ACTION_UPDATE_FILE;
        }

        // Czy zmienił się status płatności? (np. z "Do zapłaty" na "Zapłacono")
        if ($remotePaidInt !== $dbPaidInt) {
            return self::ACTION_UPDATE_STATUS;
        }

        // 4. Czy plik fizycznie istnieje?
        // KANON: zawsze downloads/FV (duże litery). Jeśli plik jest w legacy downloads/fv -> migrujemy.
        if (!empty($dbInvoice['file_name'])) {
            AzadaDbRepository::ensureInvoiceFileInFV($dbInvoice['file_name']);
        }

        $filePath = _PS_MODULE_DIR_ . 'azada_wholesaler_pro/downloads/FV/' . $dbInvoice['file_name'];
        if (!file_exists($filePath)) {
            return self::ACTION_DOWNLOAD_NEW; // Baza mówi że jest, ale pliku nie ma -> Pobierz
        }

        // Jeśli wszystko pasuje
        return self::ACTION_NONE;
    }
}