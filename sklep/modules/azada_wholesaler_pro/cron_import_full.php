<?php

/**
 * CRON: IMPORT FULL – pobranie danych z hurtowni do tabel RAW
 *
 * Skrypt do wywołania zewnętrznego (CRON).
 * Token (opcjonalny) jest weryfikowany wg ustawień modułu.
 */
require_once(dirname(__FILE__) . '/cron_init.php');

AzadaCronLog::run('import_full', ['AzadaCronRunner', 'runImportFull']);
