<?php
/**
 * 2007-2023 PrestaShop
 *
 * Serwis do zarządzania logiką modułu Historia Ceny Omnibus.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class OmnibusPriceHistoryService
{
    private $module;
    private $context;
    private $db;
    private $_logFile; // Dodajemy zmienną do pliku logu

    public function __construct($module)
    {
        $this->module = $module;
        $this->context = Context::getContext();
        $this->db = Db::getInstance();
        $this->_logFile = dirname(__DIR__) . '/omnibus_debug_log.txt'; // Ścieżka do pliku logu
    }

    /**
     * Tworzy tabelę w bazie danych do przechowywania historii cen.
     * Wywoływana podczas instalacji modułu.
     *
     * @return bool
     */
    public function createPriceHistoryTable()
    {
        $log_message_prefix = '[' . date('Y-m-d H:i:s').' (SERVICE TIME)] ';
        @file_put_contents($this->_logFile, $log_message_prefix . 'CREATE_TABLE: Rozpoczynam tworzenie tabeli historii cen.' . "\n", FILE_APPEND);

        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'omnibus_price_history` (
            `id_omnibus_price_history` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT(10) UNSIGNED NOT NULL,
            `id_product_attribute` INT(10) UNSIGNED NOT NULL DEFAULT 0,
            `id_shop` INT(10) UNSIGNED NOT NULL,
            `id_currency` INT(10) UNSIGNED NOT NULL,
            `id_country` INT(10) UNSIGNED NOT NULL DEFAULT 0,
            `id_group` INT(10) UNSIGNED NOT NULL DEFAULT 0,
            `price` DECIMAL(20,6) NOT NULL,
            `date_add` DATETIME NOT NULL,
            `change_type` VARCHAR(32) NOT NULL DEFAULT \'manual\',
            PRIMARY KEY (`id_omnibus_price_history`),
            KEY `id_product` (`id_product`),
            KEY `id_product_attribute` (`id_product_attribute`),
            KEY `id_shop` (`id_shop`),
            KEY `date_add` (`date_add`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        $result = $this->db->execute($sql);
        if ($result) {
            @file_put_contents($this->_logFile, $log_message_prefix . 'CREATE_TABLE_SUCCESS: Tabela historii cen utworzona pomyślnie.' . "\n", FILE_APPEND);
        } else {
            @file_put_contents($this->_logFile, $log_message_prefix . 'CREATE_TABLE_ERROR: Błąd podczas tworzenia tabeli historii cen. SQL: ' . $sql . "\n", FILE_APPEND);
        }
        return $result;
    }

    /**
     * Usuwa tabelę historii cen z bazy danych.
     * Wywoływana podczas deinstalacji modułu.
     *
     * @return bool
     */
    public function dropPriceHistoryTable()
    {
        $log_message_prefix = '[' . date('Y-m-d H:i:s').' (SERVICE TIME)] ';
        @file_put_contents($this->_logFile, $log_message_prefix . 'DROP_TABLE: Rozpoczynam usuwanie tabeli historii cen.' . "\n", FILE_APPEND);

        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'omnibus_price_history`;';
        $result = $this->db->execute($sql);
        if ($result) {
            @file_put_contents($this->_logFile, $log_message_prefix . 'DROP_TABLE_SUCCESS: Tabela historii cen usunięta pomyślnie.' . "\n", FILE_APPEND);
        } else {
            @file_put_contents($this->_logFile, $log_message_prefix . 'DROP_TABLE_ERROR: Błąd podczas usuwania tabeli historii cen. SQL: ' . $sql . "\n", FILE_APPEND);
        }
        return $result;
    }

    /**
     * Zapisuje aktualną cenę produktu do historii.
     * ZMIENIONA KOLEJNOŚĆ PARAMETRÓW: Wymagane przed opcjonalnymi (dla PHP 8.x).
     *
     * @param int $id_product
     * @param float $price                     <-- Wymagany parametr
     * @param int $id_product_attribute (0 for simple products) <-- Opcjonalny
     * @param string $change_type
     * @param int|null $id_shop
     * @param int|null $id_currency
     * @param int|null $id_country
     * @param int|null $id_group
     * @return bool
     */
    public function saveProductPriceToHistory(
        $id_product,
        $price, // <-- Ten parametr był wcześniej po $id_product_attribute, teraz jest wymagany zaraz po $id_product.
        $id_product_attribute = 0, // <-- Opcjonalny parametr musi być po wszystkich wymaganych.
        $change_type = 'manual',
        $id_shop = null,
        $id_currency = null,
        $id_country = 0,
        $id_group = 0
    ) {
        $log_message_prefix = '[' . date('Y-m-d H:i:s').' (SERVICE TIME)] ';
        @file_put_contents($this->_logFile, $log_message_prefix . 'SAVE_PRICE: Wywołano saveProductPriceToHistory. Produkt: ' . $id_product . ', Atrybut: ' . $id_product_attribute . ', Cena: ' . $price . ', Typ: ' . $change_type . "\n", FILE_APPEND);

        if ($id_shop === null) {
            $id_shop = (int)$this->context->shop->id;
        }
        if ($id_currency === null) {
            $id_currency = (int)$this->context->currency->id;
        }

        $overwrite_same_day_price_up = (bool)Configuration::get('OMNIBUS_OVERWRITE_SAME_DAY_PRICE_UP');
        $today = date('Y-m-d');

        $existing_entry = $this->db->getRow('
            SELECT `id_omnibus_price_history`, `price`, `change_type`
            FROM `' . _DB_PREFIX_ . 'omnibus_price_history`
            WHERE `id_product` = ' . (int)$id_product . '
            AND `id_product_attribute` = ' . (int)$id_product_attribute . '
            AND `id_shop` = ' . (int)$id_shop . '
            AND `id_currency` = ' . (int)$id_currency . '
            AND `id_country` = ' . (int)$id_country . '
            AND `id_group` = ' . (int)$id_group . '
            AND DATE(`date_add`) = \'' . pSQL($today) . '\'
            ORDER BY `date_add` DESC
        ');

        if ($existing_entry) {
            @file_put_contents($this->_logFile, $log_message_prefix . 'SAVE_PRICE: Znaleziono istniejący wpis z dzisiaj. Cena: ' . $existing_entry['price'] . ', Typ: ' . $existing_entry['change_type'] . "\n", FILE_APPEND);

            if ($overwrite_same_day_price_up && (float)$price >= (float)$existing_entry['price']) {
                @file_put_contents($this->_logFile, $log_message_prefix . 'SAVE_PRICE: Nadpisywanie istniejącego wpisu (cena wyższa/równa, overwrite włączony).'."\n", FILE_APPEND);
                return $this->db->update(
                    'omnibus_price_history',
                    [
                        'price' => (float)$price,
                        'change_type' => pSQL($change_type),
                        'date_add' => date('Y-m-d H:i:s'),
                    ],
                    'id_omnibus_price_history = ' . (int)$existing_entry['id_omnibus_price_history']
                );
            } elseif (!$overwrite_same_day_price_up && (float)$price == (float)$existing_entry['price']) {
                @file_put_contents($this->_logFile, $log_message_prefix . 'SAVE_PRICE: Cena taka sama, bez nadpisywania. Pomijam zapis.'."\n", FILE_APPEND);
                return true;
            } elseif (!$overwrite_same_day_price_up && (float)$price > (float)$existing_entry['price']) {
                @file_put_contents($this->_logFile, $log_message_prefix . 'SAVE_PRICE: Cena wzrosła, ale bez nadpisywania. Dodaję nowy wpis.'."\n", FILE_APPEND);
            } elseif ((float)$price < (float)$existing_entry['price']) {
                @file_put_contents($this->_logFile, $log_message_prefix . 'SAVE_PRICE: Cena spadła. Dodaję nowy wpis (wymóg Omnibus).'."\n", FILE_APPEND);
            } else {
                @file_put_contents($this->_logFile, $log_message_prefix . 'SAVE_PRICE: Inny przypadek, pomijam zapis.'."\n", FILE_APPEND);
                return true;
            }
        } else {
            @file_put_contents($this->_logFile, $log_message_prefix . 'SAVE_PRICE: Brak istniejącego wpisu z dzisiaj. Dodaję nowy.'."\n", FILE_APPEND);
        }

        $data = [
            'id_product'         => (int)$id_product,
            'id_product_attribute' => (int)$id_product_attribute,
            'id_shop'            => (int)$id_shop,
            'id_currency'        => (int)$id_currency,
            'id_country'         => (int)$id_country,
            'id_group'           => (int)$id_group,
            'price'              => (float)$price,
            'date_add'           => date('Y-m-d H:i:s'),
            'change_type'        => pSQL($change_type),
        ];

        $insert_result = $this->db->insert('omnibus_price_history', $data);
        @file_put_contents($this->_logFile, $log_message_prefix . 'SAVE_PRICE: Wynik insertu: ' . ($insert_result ? 'SUCCESS' : 'FAILURE') . "\n", FILE_APPEND);
        return $insert_result;
    }

    /**
     * Pobiera najniższą cenę produktu z ostatnich X dni przed daną datą.
     * ZMIENIONA KOLEJNOŚĆ PARAMETRÓW: Wymagane przed opcjonalnymi (dla PHP 8.x).
     *
     * @param int $id_product
     * @param string $date_to_check Data, do której sprawdzamy (np. data rozpoczęcia promocji) <-- Wymagany
     * @param int $id_product_attribute (0 for simple products) <-- Opcjonalny
     * @param int|null $id_shop
     * @param int|null $id_currency
     * @param int|null $id_country
     * @param int|null $id_group
     * @param int|null $days Liczba dni wstecz (domyślnie 30)
     * @return float|null Najniższa cena lub null, jeśli brak danych
     */
    public function getLowestPriceBeforeDate(
        $id_product,
        $date_to_check,
        $id_product_attribute = 0, // Zmieniona kolejność
        $id_shop = null,
        $id_currency = null,
        $id_country = 0,
        $id_group = 0,
        $days = null
    ) {
        $log_message_prefix = '[' . date('Y-m-d H:i:s').' (SERVICE TIME)] ';
        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_LOWEST_PRICE: Wywołano getLowestPriceBeforeDate. Produkt: ' . $id_product . ', Atrybut: ' . $id_product_attribute . ', Data sprawdzania: ' . $date_to_check . "\n", FILE_APPEND);

        if ($id_shop === null) {
            $id_shop = (int)$this->context->shop->id;
        }
        if ($id_currency === null) {
            $id_currency = (int)$this->context->currency->id;
        }
        if ($days === null) {
            $days = (int)Configuration::get('OMNIBUS_HISTORY_DAYS');
        }

        $date_from = date('Y-m-d H:i:s', strtotime($date_to_check . ' -' . (int)$days . ' days'));

        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_LOWEST_PRICE: Zakres dat: od ' . $date_from . ' do ' . $date_to_check . "\n", FILE_APPEND);
        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_LOWEST_PRICE: Kontekst: Sklep: ' . $id_shop . ', Waluta: ' . $id_currency . ', Kraj: ' . $id_country . ', Grupa: ' . $id_group . "\n", FILE_APPEND);

        $currencyCondition = ($id_currency === 0) ? '1' : 'oph.`id_currency` = ' . (int)$id_currency;
        $countryCondition = ($id_country === 0) ? '1' : 'oph.`id_country` = ' . (int)$id_country;
        $groupCondition = ($id_group === 0) ? '1' : 'oph.`id_group` = ' . (int)$id_group;

        $sql = '
            SELECT MIN(oph.price) AS lowest_price
            FROM `' . _DB_PREFIX_ . 'omnibus_price_history` oph
            WHERE oph.`id_product` = ' . (int)$id_product . '
              AND oph.`id_product_attribute` = ' . (int)$id_product_attribute . '
              AND oph.`id_shop` = ' . (int)$id_shop . '
              AND (' . $currencyCondition . ')
              AND (' . $countryCondition . ')
              AND (' . $groupCondition . ')
              AND oph.`date_add` >= \'' . pSQL($date_from) . '\'
              AND oph.`date_add` < \'' . pSQL($date_to_check) . '\'
              AND oph.`change_type` NOT IN (\'promotion\')
        ';

        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_LOWEST_PRICE: Zapytanie SQL: ' . $sql . "\n", FILE_APPEND);
        $result = $this->db->getValue($sql);
        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_LOWEST_PRICE: Wynik zapytania: ' . ($result !== false ? (float)$result : 'NULL') . "\n", FILE_APPEND);

        return $result !== false ? (float)$result : null;
    }

    /**
     * Pobiera pełną historię cen dla danego produktu i kombinacji.
     * Używane do podglądu w panelu administracyjnym.
     *
     * @param int $id_product
     * @param int $id_product_attribute (optional, 0 for all attributes or simple product)
     * @param int $id_shop (optional)
     * @param int $id_currency (optional)
     * @param int $id_country (optional)
     * @param int $id_group (optional)
     * @param int $days_limit (optional, default to OMNIBUS_HISTORY_DAYS)
     * @return array
     */
    public function getProductPriceHistory(
        $id_product,
        $id_product_attribute = null,
        $id_shop = null,
        $id_currency = null,
        $id_country = null,
        $id_group = null,
        $days_limit = null
    ) {
        $log_message_prefix = '[' . date('Y-m-d H:i:s').' (SERVICE TIME)] ';
        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_PRODUCT_HISTORY: Wywołano getProductPriceHistory. Produkt: ' . $id_product . ', Atrybut: ' . ($id_product_attribute ?? 'null') . "\n", FILE_APPEND);

        if ($id_shop === null) {
            $id_shop = (int)$this->context->shop->id;
        }
        if ($id_currency === null) {
            $id_currency = (int)$this->context->currency->id;
        }
        if ($id_country === null) {
            $id_country = (bool)Configuration::get('OMNIBUS_INDEX_COUNTRIES') ? (int)$this->context->country->id : 0;
        }
        if ($id_group === null) {
            $id_group = (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS') ? (int)Group::getCurrent()->id : 0;
            if ($id_group === 0 && (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS')) {
                $id_group = (int)Configuration::get('PS_CUSTOMER_GROUP');
            }
        }
        if ($days_limit === null) {
            $days_limit = (int)Configuration::get('OMNIBUS_HISTORY_DAYS');
        }

        $date_from = date('Y-m-d H:i:s', strtotime('-' . (int)$days_limit . ' days'));

        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_PRODUCT_HISTORY: Zakres dat: od ' . $date_from . ' do teraz.' . "\n", FILE_APPEND);
        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_PRODUCT_HISTORY: Kontekst: Sklep: ' . $id_shop . ', Waluta: ' . $id_currency . ', Kraj: ' . $id_country . ', Grupa: ' . $id_group . "\n", FILE_APPEND);

        $sql = 'SELECT oph.*, c.iso_code AS currency_iso, cl.name AS country_name, gl.name AS group_name
                FROM `' . _DB_PREFIX_ . 'omnibus_price_history` oph
                LEFT JOIN `' . _DB_PREFIX_ . 'currency` c ON (c.id_currency = oph.id_currency)
                LEFT JOIN `' . _DB_PREFIX_ . 'country_lang` cl ON (cl.id_country = oph.id_country AND cl.id_lang = ' . (int)$this->context->language->id . ')
                LEFT JOIN `' . _DB_PREFIX_ . 'group_lang` gl ON (gl.id_group = oph.id_group AND gl.id_lang = ' . (int)$this->context->language->id . ')
                WHERE oph.`id_product` = ' . (int)$id_product . '
                AND oph.`id_shop` = ' . (int)$id_shop . '
                AND oph.`date_add` >= \'' . pSQL($date_from) . '\'';

        if ($id_product_attribute !== null) {
            $sql .= ' AND oph.`id_product_attribute` = ' . (int)$id_product_attribute;
        }

        $currencyCondition = ($id_currency === 0) ? '1' : 'oph.`id_currency` = ' . (int)$id_currency;
        $countryCondition = ($id_country === 0) ? '1' : 'oph.`id_country` = ' . (int)$id_country;
        $groupCondition = ($id_group === 0) ? '1' : 'oph.`id_group` = ' . (int)$id_group;

        $sql .= ' AND (' . $currencyCondition . ')';
        $sql .= ' AND (' . $countryCondition . ')';
        $sql .= ' AND (' . $groupCondition . ')';
        $sql .= ' ORDER BY oph.`date_add` ASC';

        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_PRODUCT_HISTORY: Zapytanie SQL: ' . $sql . "\n", FILE_APPEND);
        $result = $this->db->executeS($sql);
        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_PRODUCT_HISTORY: Liczba wyników: ' . count($result) . "\n", FILE_APPEND);

        return $result;
    }

    /**
     * Usuwa stare wpisy z historii cen, które wykraczają poza skonfigurowany okres.
     *
     * @return bool
     */
    public function cleanOldPriceHistory()
    {
        $days_to_keep = (int)Configuration::get('OMNIBUS_HISTORY_DAYS');
        if ($days_to_keep <= 0) {
            return true; // Nie usuwaj, jeśli ustawiono 0 lub mniej dni
        }

        $date_limit = date('Y-m-d H:i:s', strtotime('-' . $days_to_keep . ' days'));

        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'omnibus_price_history`
                WHERE `date_add` < \'' . pSQL($date_limit) . '\'';

        return $this->db->execute($sql);
    }

    /**
     * Wspólna logika do pobierania i przypisywania danych dla hooków wyświetlających cenę Omnibus.
     * PRZENIESIONA Z GŁÓWNEJ KLASY MODUŁU.
     * @param object|array|null $product_data Obiekt produktu (Product, ProductListingLazyArray) lub tablica danych produktu z hooka.
     * @param int $id_product_attribute ID kombinacji produktu (0 dla produktu prostego).
     * @param string|null $hook_type Typ hooka (np. 'price', 'after_price').
     * @return array|null
     */
    public function getOmnibusPriceDisplayDataForHook($product_data, $id_product_attribute = 0, $hook_type = null)
    {
        $log_message_prefix = '[' . date('Y-m-d H:i:s').' (SERVICE TIME)] ';
        @file_put_contents($this->_logFile, $log_message_prefix . 'GET_DISPLAY_DATA: Wywołano getOmnibusPriceDisplayDataForHook. Typ hooka: ' . ($hook_type ?? 'N/A') . "\n", FILE_APPEND);

        if (!(bool)Configuration::get('OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE')) {
            @file_put_contents($this->_logFile, $log_message_prefix . 'GET_DISPLAY_DATA: Opcja wyświetlania wyłączona w konfiguracji.' . "\n", FILE_APPEND);
            return null;
        }

        $product = null;
        $id_product = 0;

        if ($product_data instanceof Product && Validate::isLoadedObject($product_data)) {
            $product = $product_data;
            $id_product = (int)$product->id;
            @file_put_contents($this->_logFile, $log_message_prefix . "GET_DISPLAY_DATA: Obiekt Product załadowany z obiektu Product przekazanego bezpośrednio. ID: " . $id_product . "\n", FILE_APPEND);
        } elseif (is_array($product_data) && isset($product_data['id_product'])) {
            $id_product = (int)$product_data['id_product'];
            $product = new Product($id_product);
            if (!Validate::isLoadedObject($product)) $product = null;
        } elseif ($product_data instanceof \PrestaShop\PrestaShop\Core\Product\ProductListing\Hook\ProductListingLazyArray) {
            $id_product = (int)$product_data->getId();
            $product = new Product($id_product);
            if (!Validate::isLoadedObject($product)) $product = null;
        }

        if (!$product && Tools::getValue('controller') == 'product' && Tools::getIsset('id_product')) {
            $id_product = (int)Tools::getValue('id_product');
            if ($id_product > 0) {
                $product = new Product($id_product);
                if (!Validate::isLoadedObject($product)) $product = null;
            }
        }

        if (!$product) {
            @file_put_contents($this->_logFile, $log_message_prefix . 'GET_DISPLAY_DATA: Brak obiektu produktu po wszystkich próbach. Zwracam null.' . "\n", FILE_APPEND);
            return null;
        }

        if ($id_product_attribute === 0 && $product->hasAttributes()) {
             $default_id_product_attribute = Product::getDefaultAttribute($product->id);
             if ($default_id_product_attribute > 0) {
                 $id_product_attribute = $default_id_product_attribute;
             }
        }
        if ($id_product_attribute === 0 && Tools::getIsset('id_product_attribute')) {
            $id_product_attribute = (int)Tools::getValue('id_product_attribute');
        }
        
        $id_shop = (int)$this->context->shop->id;
        $id_currency = (int)$this->context->currency->id;
        $id_country = (bool)Configuration::get('OMNIBUS_INDEX_COUNTRIES') ? (int)$this->context->country->id : 0;
        $id_group = (bool)Configuration::get('OMNIBUS_INDEX_CUSTOMER_GROUPS') ? (int)Group::getCurrent()->id : 0;

        $has_active_specific_price = Product::isDiscounted($id_product, $id_product_attribute);
        $lowest_price_info = null;

        if ($has_active_specific_price) {
            $date_to_check = date('Y-m-d H:i:s');
            $lowest_price = $this->getLowestPriceBeforeDate(
                $id_product,
                $date_to_check,
                $id_product_attribute,
                $id_shop,
                $id_currency,
                $id_country,
                $id_group
            );

            if ($lowest_price !== null && $lowest_price > 0) {
                $formatted_price = Tools::displayPrice($lowest_price, $this->context->currency);
                $text_template = Configuration::get('OMNIBUS_PROMO_PRICE_TEXT', $this->context->language->id);
                if(empty($text_template)) {
                    $text_template = 'Najniższa cena z 30 dni przed obniżką: <span class="price">{price}</span>';
                }
                $lowest_price_info = [
                    'price' => $formatted_price,
                    'text' => str_replace('{price}', $formatted_price, $text_template),
                ];
            }
        }

        $full_history = [];
        if ((bool)Configuration::get('OMNIBUS_ENABLE_FULL_HISTORY')) {
            $full_history = $this->getProductPriceHistory(
                $id_product,
                $id_product_attribute,
                $id_shop,
                $id_currency,
                $id_country,
                $id_group
            );
        }

        return [
            'omnibus_lowest_price_info' => $lowest_price_info,
            'omnibus_display_promo_price_product_page' => (bool)Configuration::get('OMNIBUS_DISPLAY_PROMO_PRICE_PRODUCT_PAGE'),
            'omnibus_promo_price_font_size' => Configuration::get('OMNIBUS_PROMO_PRICE_FONT_SIZE'),
            'omnibus_promo_price_font_color' => Configuration::get('OMNIBUS_PROMO_PRICE_FONT_COLOR'),
            'omnibus_promo_price_price_color' => Configuration::get('OMNIBUS_PROMO_PRICE_PRICE_COLOR'),
            'history' => $full_history,
        ];
    }
    
    /**
     * Pobiera ostatnią (najświeższą) cenę dla każdego produktu+atrybutu.
     * Zwraca mapę: ['{id_product}_{id_attr}' => price].
     * @return array
     */
    protected function getLastRecordedPricesMap()
    {
        $sql = '
            SELECT oph.id_product, oph.id_product_attribute, oph.price
            FROM `' . _DB_PREFIX_ . 'omnibus_price_history` oph
            INNER JOIN (
                SELECT id_product, id_product_attribute, MAX(date_add) AS max_date
                FROM `' . _DB_PREFIX_ . 'omnibus_price_history`
                GROUP BY id_product, id_product_attribute
            ) last
            ON oph.id_product = last.id_product
            AND oph.id_product_attribute = last.id_product_attribute
            AND oph.date_add = last.max_date
        ';
        $rows = $this->db->executeS($sql);
        $map = [];
        foreach ($rows as $r) {
            $key = $r['id_product'] . '_' . $r['id_product_attribute'];
            $map[$key] = (float)$r['price'];
        }
        return $map;
    }

    /**
     * Aktualizuje ceny wszystkich aktywnych produktów, zapisując tylko zmiany.
     * Idealne dla zadania CRON.
     * @param int|null $batchSize Limit produktów do przetworzenia w jednym uruchomieniu.
     */
    public function updateAllPrices($batchSize = null)
    {
        $lastPrices = $this->getLastRecordedPricesMap();

        $sql = 'SELECT p.id_product, pa.id_product_attribute
                FROM `'._DB_PREFIX_.'product` p
                LEFT JOIN `'._DB_PREFIX_.'product_attribute` pa
                    ON (pa.id_product = p.id_product
                        AND p.cache_default_attribute = pa.id_product_attribute)
                WHERE p.active = 1';
        $rows = $this->db->executeS($sql);

        if ($batchSize !== null) {
            $rows = array_slice($rows, 0, (int)$batchSize);
        }

        foreach ($rows as $row) {
            $idProduct = (int)$row['id_product'];
            $idAttr    = (int)($row['id_product_attribute'] ?? 0);
            $key       = $idProduct . '_' . $idAttr;

            $price = (float)Product::getPriceStatic($idProduct, true, $idAttr, 6);

            if (isset($lastPrices[$key]) && (float)$lastPrices[$key] === $price) {
                continue;
            }

            $this->saveProductPriceToHistory(
                $idProduct,
                $price,
                $idAttr,
                'auto'
            );
        }

        $this->cleanOldPriceHistory();
    }

    /**
     * Aktualizuje ceny bazowe dla produktów w promocji, zapisując tylko zmiany.
     * Idealne dla zadania CRON.
     * @param int|null $batchSize Limit produktów do przetworzenia w jednym uruchomieniu.
     */
    public function updatePromoPrices($batchSize = null)
    {
        $lastPrices = $this->getLastRecordedPricesMap();
        $now   = date('Y-m-d H:i:s');
        $sql   = 'SELECT DISTINCT id_product, id_product_attribute
                    FROM `'._DB_PREFIX_.'specific_price`
                    WHERE reduction > 0
                        AND `from` <= "'.pSQL($now).'"
                        AND (`to` = "0000-00-00 00:00:00" OR `to` >= "'.pSQL($now).'")';
        $rows  = $this->db->executeS($sql);

        if ($batchSize !== null) {
            $rows = array_slice($rows, 0, (int)$batchSize);
        }

        foreach ($rows as $row) {
            $idProduct = (int)$row['id_product'];
            $idAttr    = (int)$row['id_product_attribute'];
            $key       = $idProduct . '_' . $idAttr;

            $basePrice = (float)Product::getPriceStatic(
                $idProduct,
                true,
                $idAttr,
                6,       // Poprawka: Wymuszenie 6 miejsc po przecinku
                null,
                false,
                false
            );

            if (isset($lastPrices[$key]) && (float)$lastPrices[$key] === $basePrice) {
                continue;
            }

            $this->saveProductPriceToHistory(
                $idProduct,
                $basePrice,
                $idAttr,
                'promo_auto'
            );
        }

        $this->cleanOldPriceHistory();
    }
}