<?php
/**
 * BB Order Manager - Packing table schema guard
 *
 * Cel:
 * - naprawić krytyczny problem z unikalnym indeksem na (id_order, product_id, product_attribute_id)
 *   który może psuć pakowanie przy kilku liniach order_detail dla tego samego produktu/atrybutu
 *   (np. personalizacje / customizations).
 * - zapewnić, że jedynym kluczem unikalnym jest id_order_detail.
 *
 * Działa jako migracja "w locie" (bez reinstalacji modułu).
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BbOrderManagerPackingSchema
{
    const TABLE = 'bb_ordermanager_packing';
    const CONF_SCHEMA_OK = 'BB_OM_PACKING_SCHEMA_OK';

    /**
     * Upewnij się, że struktura tabeli pakowania jest poprawna.
     *
     * - usuwa unikalny indeks na (id_order, product_id, product_attribute_id) jeśli istnieje
     * - zapewnia unikalność id_order_detail
     * - dodaje indeks pomocniczy na id_order
     */
    public static function ensureSchema()
    {
        try {
            if ((int) Configuration::get(self::CONF_SCHEMA_OK) === 1) {
                return;
            }
        } catch (Exception $e) {
            // ignore
        }

        $db = Db::getInstance();
        $table = _DB_PREFIX_ . self::TABLE;

        try {
            // SHOW TABLES LIKE zwraca nazwę tabeli jako string; nie rzutujemy na int.
            $existsName = $db->getValue("SHOW TABLES LIKE '" . pSQL($table) . "'");
            if (empty($existsName)) {
                return;
            }

            $idx = $db->executeS('SHOW INDEX FROM `' . bqSQL($table) . '`');
            if (!is_array($idx)) {
                return;
            }

            // Zbuduj mapę indeksów -> (unikalny?, lista kolumn w kolejności)
            $byName = [];
            foreach ($idx as $row) {
                if (empty($row['Key_name']) || empty($row['Column_name'])) {
                    continue;
                }
                $name = (string) $row['Key_name'];
                if (!isset($byName[$name])) {
                    $byName[$name] = [
                        'non_unique' => (int) ($row['Non_unique'] ?? 1),
                        'cols' => [],
                    ];
                }
                $seq = (int) ($row['Seq_in_index'] ?? 0);
                $byName[$name]['cols'][$seq] = (string) $row['Column_name'];
            }

            // Normalizuj kolejność kolumn
            foreach ($byName as $n => $d) {
                if (!empty($d['cols'])) {
                    ksort($byName[$n]['cols']);
                    $byName[$n]['cols'] = array_values($byName[$n]['cols']);
                }
            }

            // 1) Usuń krytyczny unikalny indeks na (id_order, product_id, product_attribute_id)
            foreach ($byName as $name => $d) {
                $isUnique = ((int) $d['non_unique'] === 0);
                if (!$isUnique) {
                    continue;
                }
                $cols = $d['cols'] ?? [];
                if ($cols === ['id_order', 'product_id', 'product_attribute_id']) {
                    $db->execute('ALTER TABLE `' . bqSQL($table) . '` DROP INDEX `' . bqSQL($name) . '`');
                }
            }

            // Odśwież indeksy po ewentualnym DROP
            $idx2 = $db->executeS('SHOW INDEX FROM `' . bqSQL($table) . '`');
            $byName2 = [];
            if (is_array($idx2)) {
                foreach ($idx2 as $row) {
                    if (empty($row['Key_name']) || empty($row['Column_name'])) {
                        continue;
                    }
                    $name = (string) $row['Key_name'];
                    if (!isset($byName2[$name])) {
                        $byName2[$name] = [
                            'non_unique' => (int) ($row['Non_unique'] ?? 1),
                            'cols' => [],
                        ];
                    }
                    $seq = (int) ($row['Seq_in_index'] ?? 0);
                    $byName2[$name]['cols'][$seq] = (string) $row['Column_name'];
                }
                foreach ($byName2 as $n => $d) {
                    if (!empty($d['cols'])) {
                        ksort($byName2[$n]['cols']);
                        $byName2[$n]['cols'] = array_values($byName2[$n]['cols']);
                    }
                }
            }

            // 2) Upewnij się, że id_order_detail jest unikalne
            $hasUniqueDetail = false;
            foreach ($byName2 as $name => $d) {
                $isUnique = ((int) $d['non_unique'] === 0);
                if ($isUnique && ($d['cols'] ?? []) === ['id_order_detail']) {
                    $hasUniqueDetail = true;
                    break;
                }
            }
            if (!$hasUniqueDetail) {
                $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD UNIQUE KEY `order_detail_unique` (`id_order_detail`)');
            }

            // 3) Indeks pomocniczy na id_order (dla SUM/SELECT)
            $hasIdxOrder = false;
            foreach ($byName2 as $name => $d) {
                $cols = $d['cols'] ?? [];
                if ($cols === ['id_order']) {
                    $hasIdxOrder = true;
                    break;
                }
            }
            if (!$hasIdxOrder) {
                $db->execute('ALTER TABLE `' . bqSQL($table) . '` ADD INDEX `idx_order` (`id_order`)');
            }

            try {
                Configuration::updateValue(self::CONF_SCHEMA_OK, 1);
            } catch (Exception $e) {
                // ignore
            }
        } catch (Exception $e) {
            // Bezpieczny fallback: nie wysypujemy aplikacji.
        }
    }
}
