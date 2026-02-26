<?php

/**
 * CRON: UPDATE PRICE – aktualizacja cen/kosztu/VAT w PrestaShop na podstawie RAW i narzutów
 *
 * Skrypt do wywołania zewnętrznego (CRON).
 * Token (opcjonalny) jest weryfikowany wg ustawień modułu.
 */
require_once(dirname(__FILE__) . '/cron_init.php');

$sourceTable = Tools::getValue('source_table', '');
$sourceTable = $sourceTable !== '' ? $sourceTable : null;

AzadaCronLog::run('update_price', ['AzadaCronRunner', 'runUpdatePrice'], $sourceTable);
