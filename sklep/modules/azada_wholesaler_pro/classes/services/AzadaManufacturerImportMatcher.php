<?php

/**
 * AzadaManufacturerImportMatcher
 *
 * Mapuje / tworzy producenta (Manufacturer) w PrestaShop na podstawie wartości "marka" z hurtowni.
 *
 * Zasada działania:
 * 1) Szukamy mapowania w tabeli azada_wholesaler_pro_manufacturer_map (per source_table + key)
 * 2) Jeśli brak – próbujemy dopasować producenta po nazwie w ps_manufacturer
 * 3) Jeśli brak – tworzymy producenta w PrestaShop
 * 4) Zapisujemy/aktualizujemy mapowanie dla przyszłych importów
 */
class AzadaManufacturerImportMatcher
{
    const TABLE = 'azada_wholesaler_pro_manufacturer_map';

    /**
     * Upewnia się, że tabela mapowania istnieje.
     *
     * @return bool
     */
    public static function ensureTable()
    {
        // Preferowana ścieżka: AzadaInstaller ma ensureManufacturerMapTables().
        if (!class_exists('AzadaInstaller')) {
            $installerPath = dirname(__FILE__) . '/AzadaInstaller.php';
            if (file_exists($installerPath)) {
                require_once $installerPath;
            }
        }

        if (class_exists('AzadaInstaller') && method_exists('AzadaInstaller', 'ensureManufacturerMapTables')) {
            try {
                return (bool) AzadaInstaller::ensureManufacturerMapTables();
            } catch (Exception $e) {
                // fallback poniżej
            }
        }

        // Fallback (gdyby ktoś użył klasy bez Installera)
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` (
            `id_manufacturer_map` int(11) NOT NULL AUTO_INCREMENT,
            `source_table` varchar(64) NOT NULL,
            `source_manufacturer` varchar(255) NOT NULL,
            `source_manufacturer_key` varchar(255) NOT NULL,
            `id_manufacturer` int(11) NOT NULL DEFAULT 0,
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_manufacturer_map`),
            UNIQUE KEY `uniq_source_manufacturer` (`source_table`,`source_manufacturer_key`),
            KEY `idx_id_manufacturer` (`id_manufacturer`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Normalizacja klucza (porównywanie marek niezależnie od wielkości liter i znaków specjalnych).
     *
     * @param string $brand
     * @return string
     */
    public static function normalizeKey($brand)
    {
        $s = trim((string) $brand);
        if ($s === '') {
            return '';
        }

        // Na wszelki wypadek ograniczamy.
        if (class_exists('Tools')) {
            $s = Tools::substr($s, 0, 255);
            $s = Tools::strtolower($s);

            // Tools::replaceAccentedChars istnieje w PS, ale na wszelki wypadek sprawdzamy.
            if (method_exists('Tools', 'replaceAccentedChars')) {
                $s = Tools::replaceAccentedChars($s);
            }
        } else {
            $s = mb_strtolower($s, 'UTF-8');
            $s = mb_substr($s, 0, 255, 'UTF-8');
        }

        // Dodatkowa normalizacja polskich znaków (fallback).
        $s = strtr($s, [
            'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ż' => 'z', 'ź' => 'z',
            'Ą' => 'a', 'Ć' => 'c', 'Ę' => 'e', 'Ł' => 'l', 'Ń' => 'n', 'Ó' => 'o', 'Ś' => 's', 'Ż' => 'z', 'Ź' => 'z',
        ]);

        // Zostawiamy tylko litery/cyfry.
        $s = preg_replace('/[^a-z0-9]+/u', '', (string) $s);
        $s = trim((string) $s);

        if ($s === '') {
            return '';
        }

        // Bezpieczny limit dla indexu UNIQUE.
        if (class_exists('Tools')) {
            $s = Tools::substr($s, 0, 255);
        } else {
            $s = mb_substr($s, 0, 255, 'UTF-8');
        }

        return (string) $s;
    }

    /**
     * Rozwiązuje id_manufacturer dla danej marki i hurtowni.
     *
     * @param string $sourceTable np. azada_raw_abro
     * @param string $brand       marka z hurtowni
     * @param int    $idShop      sklep (do associateTo w MultiShop)
     *
     * @return int id_manufacturer lub 0
     */
    public static function resolveManufacturerId($sourceTable, $brand, $idShop = 0)
    {
        $brand = trim((string) $brand);
        if ($brand === '') {
            return 0;
        }

        self::ensureTable();

        $sourceTable = trim((string) $sourceTable);
        if ($sourceTable === '') {
            $sourceTable = 'unknown';
        }

        $key = self::normalizeKey($brand);
        if ($key === '') {
            return 0;
        }

        $db = Db::getInstance();
        $mapTable = _DB_PREFIX_ . self::TABLE;

        $row = null;
        try {
            // UWAGA: bez LIMIT (PS czasem dokleja LIMIT w getRow/getValue)
            $row = $db->getRow(
                'SELECT id_manufacturer, id_manufacturer_map, source_manufacturer '
                . 'FROM `' . bqSQL($mapTable) . '` '
                . 'WHERE source_table=\'' . pSQL($sourceTable) . '\' AND source_manufacturer_key=\'' . pSQL($key) . '\''
            );
        } catch (Exception $e) {
            $row = null;
        }

        $idMapped = 0;
        $idMap = 0;
        if (is_array($row) && !empty($row)) {
            $idMapped = isset($row['id_manufacturer']) ? (int) $row['id_manufacturer'] : 0;
            $idMap = isset($row['id_manufacturer_map']) ? (int) $row['id_manufacturer_map'] : 0;
        }

        if ($idMapped > 0) {
            // Sprawdź czy producent nadal istnieje.
            $exists = 0;
            try {
                $exists = (int) $db->getValue(
                    'SELECT id_manufacturer FROM `' . bqSQL(_DB_PREFIX_ . 'manufacturer') . '` WHERE id_manufacturer=' . (int) $idMapped
                );
            } catch (Exception $e) {
                $exists = 0;
            }

            if ($exists > 0) {
                return (int) $idMapped;
            }

            // Mapowanie wskazuje na nieistniejącego producenta – reset.
            if ($idMap > 0) {
                try {
                    $db->update(self::TABLE, [
                        'id_manufacturer' => 0,
                        'date_upd' => date('Y-m-d H:i:s'),
                    ], 'id_manufacturer_map=' . (int) $idMap);
                } catch (Exception $e) {
                    // ignore
                }
            }
        }

        // 1) Spróbuj dopasować producenta po nazwie (case-insensitive)
        $brandName = self::truncateName($brand, 64);
        $id = 0;
        try {
            $rows = $db->executeS(
                'SELECT id_manufacturer FROM `' . bqSQL(_DB_PREFIX_ . 'manufacturer') . '` '
                . 'WHERE LOWER(name)=LOWER(\'' . pSQL($brandName) . '\') '
                . 'ORDER BY id_manufacturer ASC LIMIT 1'
            );
            if (is_array($rows) && !empty($rows) && isset($rows[0]['id_manufacturer'])) {
                $id = (int) $rows[0]['id_manufacturer'];
            }
        } catch (Exception $e) {
            $id = 0;
        }

        // 2) Jeśli brak – twórz producenta
        if ($id <= 0) {
            $id = self::createManufacturer($brandName, (int) $idShop);
        }

        if ($id <= 0) {
            return 0;
        }

        // Zapisz / zaktualizuj mapowanie
        self::upsertMapping($sourceTable, $brand, $key, (int) $id);

        return (int) $id;
    }

    private static function truncateName($name, $maxLen)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }
        $maxLen = (int) $maxLen;
        if ($maxLen <= 0) {
            $maxLen = 64;
        }

        if (class_exists('Tools')) {
            return Tools::substr($name, 0, $maxLen);
        }

        return mb_substr($name, 0, $maxLen, 'UTF-8');
    }

    private static function createManufacturer($name, $idShop = 0)
    {
        if (!class_exists('Manufacturer')) {
            return 0;
        }

        $name = trim((string) $name);
        if ($name === '') {
            return 0;
        }

        $manufacturer = new Manufacturer();
        $manufacturer->name = $name;
        $manufacturer->active = 1;

        if (!$manufacturer->add()) {
            return 0;
        }

        // MultiShop: powiąż z bieżącym sklepem
        $idShop = (int) $idShop;
        if ($idShop <= 0 && class_exists('Context') && isset(Context::getContext()->shop) && isset(Context::getContext()->shop->id)) {
            $idShop = (int) Context::getContext()->shop->id;
        }

        if ($idShop > 0 && method_exists($manufacturer, 'associateTo')) {
            try {
                $manufacturer->associateTo($idShop);
            } catch (Exception $e) {
                // ignore
            }
        }

        return (int) $manufacturer->id;
    }

    private static function upsertMapping($sourceTable, $brand, $key, $idManufacturer)
    {
        $sourceTable = trim((string) $sourceTable);
        if ($sourceTable === '') {
            $sourceTable = 'unknown';
        }

        $brand = trim((string) $brand);
        if ($brand === '') {
            return;
        }

        if (class_exists('Tools')) {
            $brand = Tools::substr($brand, 0, 255);
        } else {
            $brand = mb_substr($brand, 0, 255, 'UTF-8');
        }

        $now = date('Y-m-d H:i:s');

        $sql = 'INSERT INTO `' . bqSQL(_DB_PREFIX_ . self::TABLE) . '` '
            . '(`source_table`, `source_manufacturer`, `source_manufacturer_key`, `id_manufacturer`, `date_add`, `date_upd`) '
            . 'VALUES (\'' . pSQL($sourceTable) . '\', \'' . pSQL($brand) . '\', \'' . pSQL($key) . '\', ' . (int) $idManufacturer . ', \'' . pSQL($now) . '\', \'' . pSQL($now) . '\') '
            . 'ON DUPLICATE KEY UPDATE '
            . '`source_manufacturer`=VALUES(`source_manufacturer`), '
            . '`id_manufacturer`=VALUES(`id_manufacturer`), '
            . '`date_upd`=VALUES(`date_upd`)';

        try {
            Db::getInstance()->execute($sql);
        } catch (Exception $e) {
            // ignore
        }
    }
}
