<?php

/**
 * AzadaCategoryImportMatcher
 *
 * "Konsument" mapowania kategorii hurtowni -> PrestaShop.
 *
 * Cel: w jednym miejscu trzymać logikę:
 * - jak parsujemy pole kategoria z RAW (segmenty po ';', usuwanie '*', trim, czyszczenie),
 * - jak sprawdzamy czy produkt kwalifikuje się do importu (is_active=1 + id_category_default>0),
 * - jakie ID kategorii PS przypisać oraz jaka jest domyślna kategoria.
 *
 * Dzięki temu tę samą klasę można użyć:
 * - w Poczekalni (podgląd, status importu),
 * - w cron/automacie importu produktów,
 * - w przyszłości w tworzeniu/aktualizacji produktów.
 */
class AzadaCategoryImportMatcher
{
    /**
     * Cache aktywnych mapowań: [source_table => [normalized_key => mappingInfo]]
     * mappingInfo = [source_category, id_category_default, ps_category_ids, category_markup_percent]
     */
    private static $activeMappingsCache = [];

    /** @var array<string,bool> */
    private static $activeMappingsLoaded = [];

    /**
     * Dopasuj produkt RAW do mapowań kategorii.
     *
     * @param string $sourceTable np. 'azada_raw_bioplanet'
     * @param string $rawCategory wartość z kolumny `kategoria`
     *
     * @return array{
     *   is_importable: bool,
     *   reason: string,
     *   matched_source_categories: string[],
     *   ps_category_ids: int[],
     *   id_category_default: int,
     *   category_markup_percent: float
     * }
     */
    public static function match($sourceTable, $rawCategory)
    {
        $sourceTable = trim((string)$sourceTable);
        $rawCategory = (string)$rawCategory;

        if ($sourceTable === '') {
            return [
                'is_importable' => false,
                'reason' => 'missing_source_table',
                'matched_source_categories' => [],
                'ps_category_ids' => [],
                'id_category_default' => 0,
                'category_markup_percent' => 0.0,
            ];
        }

        $segments = self::extractSegments($rawCategory);
        if (empty($segments)) {
            return [
                'is_importable' => false,
                'reason' => 'missing_category',
                'matched_source_categories' => [],
                'ps_category_ids' => [],
                'id_category_default' => 0,
                'category_markup_percent' => 0.0,
            ];
        }

        $mappings = self::getActiveMappingsBySourceTable($sourceTable);
        if (empty($mappings)) {
            return [
                'is_importable' => false,
                'reason' => 'no_active_mappings',
                'matched_source_categories' => [],
                'ps_category_ids' => [],
                'id_category_default' => 0,
                'category_markup_percent' => 0.0,
            ];
        }

        $matchedSource = [];
        $mergedPsCategoryIds = [];
        $defaultMapping = null;

        // Reguła domyślna: bierzemy OSTATNIĄ dopasowaną kategorię z listy segmentów (najbardziej szczegółową).
        foreach ($segments as $seg) {
            $key = self::buildKey($seg);
            if ($key === '' || !isset($mappings[$key])) {
                continue;
            }

            $info = $mappings[$key];
            $defaultMapping = $info;
            $matchedSource[] = (string)$info['source_category'];

            if (!empty($info['ps_category_ids']) && is_array($info['ps_category_ids'])) {
                foreach ($info['ps_category_ids'] as $idCat) {
                    $idCat = (int)$idCat;
                    if ($idCat > 0) {
                        $mergedPsCategoryIds[$idCat] = $idCat;
                    }
                }
            }
        }

        if ($defaultMapping === null) {
            return [
                'is_importable' => false,
                'reason' => 'category_not_mapped_or_inactive',
                'matched_source_categories' => [],
                'ps_category_ids' => [],
                'id_category_default' => 0,
                'category_markup_percent' => 0.0,
            ];
        }

        $idDefault = isset($defaultMapping['id_category_default']) ? (int)$defaultMapping['id_category_default'] : 0;
        if ($idDefault <= 0 && !empty($defaultMapping['ps_category_ids']) && is_array($defaultMapping['ps_category_ids'])) {
            $first = reset($defaultMapping['ps_category_ids']);
            $idDefault = (int)$first;
        }

        return [
            'is_importable' => ($idDefault > 0),
            'reason' => ($idDefault > 0 ? 'ok' : 'missing_default_category'),
            'matched_source_categories' => array_values(array_unique($matchedSource)),
            'ps_category_ids' => array_values($mergedPsCategoryIds),
            'id_category_default' => $idDefault,
            'category_markup_percent' => isset($defaultMapping['category_markup_percent']) ? (float)$defaultMapping['category_markup_percent'] : 0.0,
        ];
    }

    /**
     * Pobierz aktywne mapowania dla danej hurtowni (source_table).
     *
     * Aktywne = is_active=1 oraz zmapowane (id_category_default>0).
     */
    public static function getActiveMappingsBySourceTable($sourceTable)
    {
        $sourceTable = trim((string)$sourceTable);
        if ($sourceTable === '') {
            return [];
        }

        if (isset(self::$activeMappingsLoaded[$sourceTable])) {
            return isset(self::$activeMappingsCache[$sourceTable]) ? self::$activeMappingsCache[$sourceTable] : [];
        }

        self::$activeMappingsLoaded[$sourceTable] = true;
        self::$activeMappingsCache[$sourceTable] = [];

        $table = _DB_PREFIX_ . 'azada_wholesaler_pro_category_map';

        try {
            $rows = Db::getInstance()->executeS(
                "SELECT source_category, id_category_default, ps_category_ids, category_markup_percent\n"
                . "FROM `" . bqSQL($table) . "`\n"
                . "WHERE source_table='" . pSQL($sourceTable) . "'\n"
                . "  AND source_type='category'\n"
                . "  AND is_active=1\n"
                . "  AND id_category_default > 0"
            );
        } catch (Exception $e) {
            $rows = [];
        }

        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        foreach ($rows as $row) {
            $sourceCategory = isset($row['source_category']) ? trim((string)$row['source_category']) : '';
            if ($sourceCategory === '') {
                continue;
            }

            $key = self::buildKey($sourceCategory);
            if ($key === '') {
                continue;
            }

            $psIds = [];
            $rawJson = isset($row['ps_category_ids']) ? trim((string)$row['ps_category_ids']) : '';
            if ($rawJson !== '') {
                $decoded = json_decode($rawJson, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $idCat) {
                        $idCat = (int)$idCat;
                        if ($idCat > 0) {
                            $psIds[$idCat] = $idCat;
                        }
                    }
                }
            }

            $idDefault = isset($row['id_category_default']) ? (int)$row['id_category_default'] : 0;
            if ($idDefault > 0) {
                $psIds[$idDefault] = $idDefault;
            }

            self::$activeMappingsCache[$sourceTable][$key] = [
                'source_category' => $sourceCategory,
                'id_category_default' => $idDefault,
                'ps_category_ids' => array_values($psIds),
                'category_markup_percent' => isset($row['category_markup_percent']) ? (float)$row['category_markup_percent'] : 0.0,
            ];
        }

        return self::$activeMappingsCache[$sourceTable];
    }

    /**
     * Wyciąga segmenty z pola `kategoria` (analogicznie do syncSourceCategories).
     *
     * Przykład: "*Żywność; Konfitury; Dżemy" => ["Żywność", "Konfitury", "Dżemy"]
     */
    public static function extractSegments($rawCategory)
    {
        $rawCategory = trim((string)$rawCategory);
        if ($rawCategory === '') {
            return [];
        }

        $rawCategory = preg_replace('/\x{00A0}/u', ' ', $rawCategory);
        if ($rawCategory === null) {
            $rawCategory = '';
        }

        $parts = array_map('trim', explode(';', $rawCategory));
        $parts = array_values(array_filter($parts, function ($item) {
            return trim((string)$item) !== '';
        }));

        if (!empty($parts) && strpos($parts[0], '*') === 0) {
            array_shift($parts);
        }

        $result = [];
        $seen = [];

        foreach ($parts as $part) {
            $part = ltrim((string)$part, "* \t\n\r\0\x0B");
            $normalized = self::normalizeLabel($part);
            if ($normalized === '') {
                continue;
            }

            if (self::toUpper($normalized) === 'WSZYSTKIE') {
                continue;
            }

            $key = self::buildKey($normalized);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $result[] = $normalized;
            $seen[$key] = true;
        }

        return $result;
    }

    private static function normalizeLabel($raw)
    {
        $value = trim((string)$raw);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value);
        if ($value === null) {
            return '';
        }

        // Odrzucamy oczywiste śmieci: same liczby / znaki oraz wartości wyglądające na pola adresowe.
        $onlyNumericLike = preg_match('/^[0-9\s,\.-]+$/u', $value);
        if ($onlyNumericLike) {
            return '';
        }

        if (preg_match('/\b(ul\.|street|hamburg|gmbh)\b/i', $value)) {
            return '';
        }

        if (function_exists('mb_strlen')) {
            if (mb_strlen($value, 'UTF-8') < 3) {
                return '';
            }
        } else {
            if (strlen($value) < 3) {
                return '';
            }
        }

        return $value;
    }

    private static function buildKey($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        // Normalizacja spacji, potem lower-case.
        $value = preg_replace('/\s+/u', ' ', $value);
        if ($value === null) {
            return '';
        }

        $value = self::toLower($value);
        return trim((string)$value);
    }

    private static function toLower($value)
    {
        $value = (string)$value;
        if (class_exists('Tools')) {
            return Tools::strtolower($value);
        }

        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value, 'UTF-8');
        }

        return strtolower($value);
    }

    private static function toUpper($value)
    {
        $value = (string)$value;
        if (class_exists('Tools')) {
            return Tools::strtoupper($value);
        }

        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($value, 'UTF-8');
        }

        return strtoupper($value);
    }
}
