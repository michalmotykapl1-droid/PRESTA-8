<?php

/**
 * CRON: CREATE PRODUCTS – placeholder pod automatyczny import produktów z kategorii Import ON
 *
 * Skrypt do wywołania zewnętrznego (CRON).
 * Token (opcjonalny) jest weryfikowany wg ustawień modułu.
 */
require_once(dirname(__FILE__) . '/cron_init.php');

$sourceTable = Tools::getValue('source_table', '');
$sourceTable = $sourceTable !== '' ? $sourceTable : null;

AzadaCronLog::run('create_products', ['AzadaCronRunner', 'runCreateProducts'], $sourceTable);
