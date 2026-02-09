<?php

class AzadaRawSchema
{
    public static function getColumns()
    {
        return [
            'id_raw' => 'int(11) NOT NULL AUTO_INCREMENT',
            'kod_kreskowy' => 'varchar(64) DEFAULT NULL',
            'produkt_id' => 'varchar(64) DEFAULT NULL',
            'kod' => 'varchar(64) DEFAULT NULL',
            'nazwa' => 'text DEFAULT NULL',
            'marka' => 'text DEFAULT NULL',
            'opis' => 'text DEFAULT NULL',
            'LinkDoProduktu' => 'text DEFAULT NULL',
            'NaStanie' => 'varchar(64) DEFAULT NULL',
            'zdjecieglownelinkurl' => 'text DEFAULT NULL',
            'kategoria' => 'text DEFAULT NULL',
            'jednostkapodstawowa' => 'text DEFAULT NULL',
            'waga' => 'decimal(20,6) DEFAULT 0.000000',
            'ilosc_w_opakowaniu' => 'text DEFAULT NULL',
            'wymagane_oz' => 'text DEFAULT NULL',
            'ilosc' => 'text DEFAULT NULL',
            'cenaprzedrabatemnetto' => 'decimal(20,6) DEFAULT 0.000000',
            'cenaporabacienetto' => 'decimal(20,6) DEFAULT 0.000000',
            'vat' => 'decimal(20,6) DEFAULT 0.000000',
            'cenadetalicznabrutto' => 'decimal(20,6) DEFAULT 0.000000',
            'dostepnyod' => 'text DEFAULT NULL',
            'gwaranterminprzydatdni' => 'text DEFAULT NULL',
            'producentnazwaiadres' => 'text DEFAULT NULL',
            'nazamowienie' => 'text DEFAULT NULL',
            'krajpochodzeniaskladnikow' => 'text DEFAULT NULL',
            'masabrutto' => 'decimal(20,6) DEFAULT 0.000000',
            'cenastandardnetto' => 'decimal(20,6) DEFAULT 0.000000',
            'orientacyjny_termin_przydatnosci' => 'text DEFAULT NULL',
            'minimum_logistyczne' => 'text DEFAULT NULL',
            'stan_magazynowy_live' => 'text DEFAULT NULL',
            'cena_netto_live' => 'decimal(20,6) DEFAULT 0.000000',
            'glebokosc' => 'decimal(20,6) DEFAULT 0.000000',
            'szerokosc' => 'decimal(20,6) DEFAULT 0.000000',
            'wysokosc' => 'decimal(20,6) DEFAULT 0.000000',
            'data_aktualizacji' => 'text DEFAULT NULL',
        ];
    }

    public static function getColumnNames($includePrimary = false)
    {
        $columns = array_keys(self::getColumns());
        if ($includePrimary) {
            return $columns;
        }
        return array_values(array_filter($columns, function ($column) {
            return $column !== 'id_raw';
        }));
    }

    public static function createTable($tableName, $dropExisting = false)
    {
        $db = Db::getInstance();
        $fullTableName = _DB_PREFIX_ . pSQL($tableName);

        if ($dropExisting) {
            if (!$db->execute("DROP TABLE IF EXISTS `$fullTableName`")) {
                return false;
            }
        }

        $columns = self::getColumns();
        $sqlParts = [];
        foreach ($columns as $name => $definition) {
            $sqlParts[] = "`$name` $definition";
        }

        $sqlParts[] = "PRIMARY KEY (`id_raw`)";
        $sqlParts[] = "KEY `kod_kreskowy` (`kod_kreskowy`)";

        $sql = "CREATE TABLE IF NOT EXISTS `$fullTableName` (" . implode(', ', $sqlParts) . ") ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        return (bool)$db->execute($sql);
    }
}
