<?php

/**
 * CRON: IMPORT LIGHT – tryb lekki (jeśli integracja wspiera) dla częstszej synchronizacji RAW
 *
 * Skrypt do wywołania zewnętrznego (CRON).
 * Token (opcjonalny) jest weryfikowany wg ustawień modułu.
 */
require_once(dirname(__FILE__) . '/cron_init.php');

AzadaCronLog::run('import_light', ['AzadaCronRunner', 'runImportLight']);
