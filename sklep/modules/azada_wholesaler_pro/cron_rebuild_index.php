<?php
require_once(dirname(__FILE__) . '/cron_init.php');

$sourceTable = Tools::getValue('source_table', '');
$sourceTable = $sourceTable !== '' ? $sourceTable : null;

AzadaCronLog::run('rebuild_index', ['AzadaCronRunner', 'runRebuildSearchIndex'], $sourceTable);
