<?php

/**
 * CRON: UPDATE QTY – aktualizacja stanów (i min. ilości) w PrestaShop na podstawie RAW
 *
 * Skrypt do wywołania zewnętrznego (CRON).
 * Token (opcjonalny) jest weryfikowany wg ustawień modułu.
 */
require_once(dirname(__FILE__) . '/cron_init.php');

$sourceTable = Tools::getValue('source_table', '');
$sourceTable = $sourceTable !== '' ? $sourceTable : null;

AzadaCronLog::run('update_qty', ['AzadaCronRunner', 'runUpdateQty'], $sourceTable);
