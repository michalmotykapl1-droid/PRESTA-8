<?php

class AzadaLogger
{
    const SEVERITY_INFO = 1;
    const SEVERITY_SUCCESS = 2;
    const SEVERITY_ERROR = 3;

    /**
     * Dodaje nowy wpis do logów
     */
    public static function addLog($source, $title, $details = '', $severity = self::SEVERITY_INFO)
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'azada_wholesaler_pro_logs';

        $db->insert('azada_wholesaler_pro_logs', [
            'severity' => (int)$severity,
            'source'   => pSQL($source),
            'title'    => pSQL($title),
            'details'  => pSQL($details, true), // true = allow HTML
            'date_add' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Czyści logi starsze niż X dni (pobierane z konfiga)
     */
    public static function cleanOldLogs()
    {
        $days = (int)Configuration::get('AZADA_LOGS_RETENTION');
        if ($days <= 0) $days = 30; // Domyślnie 30 dni jeśli nie ustawiono

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        Db::getInstance()->execute("DELETE FROM " . _DB_PREFIX_ . "azada_wholesaler_pro_logs WHERE date_add < '$cutoffDate'");
    }

    /**
     * Tworzy tabelę w bazie danych (wywoływane przez Managera)
     */
    public static function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "azada_wholesaler_pro_logs` (
            `id_log` int(11) NOT NULL AUTO_INCREMENT,
            `severity` tinyint(1) NOT NULL DEFAULT 1,
            `source` varchar(50) DEFAULT NULL,
            `title` varchar(255) DEFAULT NULL,
            `details` longtext,
            `date_add` datetime NOT NULL,
            PRIMARY KEY (`id_log`),
            KEY `date_add` (`date_add`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8;";

        Db::getInstance()->execute($sql);
    }
}