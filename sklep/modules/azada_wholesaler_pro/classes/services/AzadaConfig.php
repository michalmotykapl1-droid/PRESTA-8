<?php

/**
 * AzadaConfig
 *
 * Ujednolicone pobieranie ustawień z Configuration.
 *
 * WAŻNE: Configuration::get($key, $id_lang, ...) – drugi parametr to język,
 * a NIE domyślna wartość. Ten helper zapewnia prawdziwy $default.
 */
class AzadaConfig
{
    /** @var array|null */
    private static $defaultsCache = null;

    /**
     * Domyślne wartości konfiguracji modułu.
     *
     * Ważne: tylko klucze modułu (AZADA_*). Nie mieszamy tu ustawień PS.
     *
     * @return array<string, mixed>
     */
    public static function getDefaults()
    {
        if (self::$defaultsCache !== null) {
            return self::$defaultsCache;
        }

        // Uwaga: dla AZADA_CRON_KEY używamy generatora – zapisujemy tylko gdy brak klucza.
        self::$defaultsCache = [
            // --- CRON / bezpieczeństwo ---
            'AZADA_USE_SECURE_TOKEN' => 1,
            'AZADA_CRON_KEY' => function () {
                return Tools::passwdGen(32);
            },

            // --- B2B / Dokumenty ---
            'AZADA_B2B_DAYS_RANGE' => 7,
            'AZADA_B2B_AUTO_FMT_ACTIVE' => 0,
            'AZADA_B2B_PREF_FORMAT' => 'utf8',
            'AZADA_B2B_AUTO_DOWNLOAD' => 0,
            'AZADA_B2B_DELETE_DAYS' => 7,
            'AZADA_B2B_FETCH_STRATEGY' => 'strict',

            // --- FV / Faktury ---
            'AZADA_FV_DAYS_RANGE' => 30,
            'AZADA_FV_AUTO_DOWNLOAD' => 0,
            'AZADA_FV_PREF_FORMAT' => 'csv',
            'AZADA_FV_DELETE_DAYS' => 365,

            // --- Logi ---
            'AZADA_LOGS_RETENTION' => 30,

            // --- Produkty / import ---
            'AZADA_LINK_EXISTING_PRODUCTS' => 0,
            'AZADA_NEW_PROD_ACTIVE' => 0,
            'AZADA_NEW_PROD_VISIBILITY' => 'both',

            // --- Aktualizacja pól (update) ---
            'AZADA_UPD_NAME' => 0,
            'AZADA_UPD_DESC_LONG' => 0,
            'AZADA_UPD_DESC_SHORT' => 0,
            'AZADA_UPD_META' => 1,
            'AZADA_UPD_REFERENCE' => 0,
            'AZADA_UPD_EAN' => 1,
            'AZADA_UPD_FEATURES' => 1,
            'AZADA_UPD_MANUFACTURER' => 1,
            'AZADA_UPD_CATEGORY' => 0,

            // --- Stany / ilości ---
            'AZADA_UPD_ACTIVE' => 0,
            'AZADA_UPD_QTY' => 1,
            'AZADA_UPD_MIN_QTY' => 0,
            'AZADA_STOCK_ZERO_ACTION' => 0,
            'AZADA_STOCK_MISSING_ZERO' => 1,
            'AZADA_SKIP_NO_QTY' => 0,

            // --- Ceny ---
            'AZADA_UPD_PRICE' => 1,
            'AZADA_UPD_WHOLESALE_PRICE' => 0,
            'AZADA_UPD_UNIT_PRICE' => 0,
            'AZADA_UPD_TAX' => 0,
            'AZADA_PRICE_ROUNDING' => 0,
            'AZADA_SKIP_NO_PRICE' => 1,

            // --- Zdjęcia / opisy ---
            'AZADA_UPD_IMAGES' => 1,
            'AZADA_DELETE_OLD_IMAGES' => 0,
            'AZADA_SKIP_NO_IMAGE' => 0,
            'AZADA_SKIP_NO_DESC' => 0,

            // --- Zaawansowane ---
            'AZADA_CLEAR_CACHE' => 0,
            'AZADA_DISABLE_MISSING_PROD' => 1,
        ];

        return self::$defaultsCache;
    }

    /**
     * Zwraca domyślną wartość (obsługuje też callable).
     *
     * @param mixed $default
     * @return mixed
     */
    private static function resolveDefault($default)
    {
        return is_callable($default) ? $default() : $default;
    }

    /**
     * Inicjuje brakujące wartości w tabeli configuration.
     *
     * Dzięki temu:
     * - świeża instalacja ma komplet ustawień,
     * - crony nie "dziedziczą" przypadkowo false (brak klucza),
     * - token CRON jest wygenerowany od razu.
     *
     * Uwaga: nie nadpisuje istniejących wartości (nawet jeśli są "0").
     *
     * @return void
     */
    public static function ensureDefaults()
    {
        $defaults = self::getDefaults();

        foreach ($defaults as $key => $defaultSpec) {
            $current = Configuration::get($key);

            $shouldInit = ($current === false || $current === null);

            // Token musi być niepusty.
            if ($key === 'AZADA_CRON_KEY') {
                if ($current === false || $current === null || trim((string)$current) === '') {
                    $shouldInit = true;
                }
            }

            if ($shouldInit) {
                Configuration::updateValue($key, self::resolveDefault($defaultSpec));
            }
        }
    }

    /**
     * Pobierz wartość konfiguracji lub zwróć $default, jeśli klucz nie istnieje.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $value = Configuration::get($key);

        // PrestaShop zwraca false gdy klucz nie istnieje.
        if ($value === false || $value === null) {
            // Jeśli nie podano defaultu, spróbuj pobrać z mapy domyślnych wartości.
            if ($default === null) {
                $defaults = self::getDefaults();
                if (array_key_exists($key, $defaults)) {
                    return self::resolveDefault($defaults[$key]);
                }
            }

            return self::resolveDefault($default);
        }

        return $value;
    }

    /**
     * @param string $key
     * @param int $default
     * @return int
     */
    public static function getInt($key, $default = 0)
    {
        return (int)self::get($key, $default);
    }

    /**
     * @param string $key
     * @param bool|int $default
     * @return bool
     */
    public static function getBool($key, $default = false)
    {
        $defaultInt = $default ? 1 : 0;
        $value = self::get($key, $defaultInt);

        if (is_bool($value)) {
            return (bool)$value;
        }

        return (int)$value === 1;
    }

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    public static function getString($key, $default = '')
    {
        return (string)self::get($key, $default);
    }

    /**
     * @param string $key
     * @param float $default
     * @return float
     */
    public static function getFloat($key, $default = 0.0)
    {
        return (float)self::get($key, $default);
    }
}
