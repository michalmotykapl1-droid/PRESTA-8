<?php

require_once(dirname(__FILE__) . '/AzadaConfig.php');

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
    public static function cleanOldLogs($force = false)
    {
        // Retencja logów w dniach (wpisywana w konfiguracji)
        $days = (int) AzadaConfig::getInt('AZADA_LOGS_RETENTION', 30);
        if ($days <= 0) {
            // Bezpieczny fallback: 30 dni jeśli nie ustawiono / wpisano 0
            $days = 30;
        }

        // Throttle: nie czyścimy przy każdym CRON-ie (np. co 3–5 min).
        // Czyścimy maksymalnie raz na 12 godzin.
        $intervalHours = 12;
        $lastClean = (string) AzadaConfig::get('AZADA_LOGS_LAST_CLEAN', '');
        if (!$force && $lastClean !== '') {
            $lastTs = @strtotime($lastClean);
            if ($lastTs !== false && (time() - $lastTs) < ($intervalHours * 3600)) {
                return;
            }
        }

        // Ustawiamy znacznik od razu (anty-race) – żeby równoległe procesy nie odpaliły czyszczenia naraz.
        Configuration::updateValue('AZADA_LOGS_LAST_CLEAN', date('Y-m-d H:i:s'));

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $cutoffDateSql = pSQL($cutoffDate);

        Db::getInstance()->execute(
            'DELETE FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_logs` WHERE `date_add` < \'' . $cutoffDateSql . '\''
        );
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