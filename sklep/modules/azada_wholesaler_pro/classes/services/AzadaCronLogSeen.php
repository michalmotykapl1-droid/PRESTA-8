<?php

/**
 * AzadaCronLogSeen
 *
 * Stan "ostatnio widzianego" loga per pracownik.
 *
 * Uwaga: mimo nazwy historycznej (CronLogSeen), klasa dotyczy teraz
 * wspólnej tabeli logów modułu: azada_wholesaler_pro_logs.
 *
 * Dzięki temu badge "nowe logi" obejmuje wszystkie logi modułu.
 */
class AzadaCronLogSeen
{
    const TABLE = 'azada_wholesaler_pro_logs_seen';

    public static function ensureTable()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . self::TABLE . '` (
            `id_employee` int(11) NOT NULL,
            `last_seen_id` int(11) NOT NULL DEFAULT 0,
            `date_upd` datetime NOT NULL,
            PRIMARY KEY (`id_employee`),
            KEY `idx_last_seen_id` (`last_seen_id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        return Db::getInstance()->execute($sql);
    }

    /**
     * Zwraca last_seen_id. Jeśli brak wpisu (np. po aktualizacji),
     * inicjuje go na aktualny MAX(id_log), żeby badge nie pokazał tysięcy logów.
     */
    public static function getLastSeenId($idEmployee)
    {
        self::ensureTable();

        $idEmployee = (int)$idEmployee;
        if ($idEmployee <= 0) {
            return 0;
        }

        $row = Db::getInstance()->getRow(
            'SELECT `last_seen_id` FROM `' . _DB_PREFIX_ . self::TABLE . '` WHERE `id_employee`=' . (int)$idEmployee
        );

        if ($row && isset($row['last_seen_id'])) {
            return (int)$row['last_seen_id'];
        }

        // Brak wpisu → ustaw na aktualny max id_log, aby nie spamować badge po update.
        self::markAllSeen($idEmployee);
        return (int)self::getLastSeenId($idEmployee);
    }

    /**
     * Oznacza wszystkie logi jako "widziane".
     */
    public static function markAllSeen($idEmployee)
    {
        self::ensureTable();

        $idEmployee = (int)$idEmployee;
        if ($idEmployee <= 0) {
            return false;
        }

        $maxId = (int)Db::getInstance()->getValue(
            'SELECT MAX(`id_log`) FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_logs`'
        );

        $exists = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . self::TABLE . '` WHERE `id_employee`=' . (int)$idEmployee
        );

        $data = [
            'id_employee' => (int)$idEmployee,
            'last_seen_id' => (int)$maxId,
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        if ($exists > 0) {
            return Db::getInstance()->update(self::TABLE, $data, 'id_employee=' . (int)$idEmployee);
        }

        return Db::getInstance()->insert(self::TABLE, $data);
    }

    /**
     * Liczba nowych logów od ostatniego wejścia do zakładki.
     */
    public static function getNewCount($idEmployee)
    {
        self::ensureTable();

        $idEmployee = (int)$idEmployee;
        if ($idEmployee <= 0) {
            return 0;
        }

        $last = (int)self::getLastSeenId($idEmployee);

        return (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_logs` WHERE `id_log` > ' . (int)$last
        );
    }
}
