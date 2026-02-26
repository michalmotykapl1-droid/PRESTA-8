<?php

/**
 * CRON: ABRO – stany co kilka minut (PULL + PUSH)
 *
 * Ten endpoint jest skrótem dla najczęstszego scenariusza:
 * - pobierz świeże stany z ABRO do RAW w trybie LIGHT (bez pełnego importu),
 * - następnie zaktualizuj stany produktów w PrestaShop.
 *
 * Parametry (możesz nadpisać w URL):
 * - source_table=azada_raw_abro
 * - pull_light=1
 * - pull_min_interval=120 (sekundy) – zabezpieczenie przed zbyt częstym pobieraniem feedu
 * - qty_only=1 – tylko ilości (bez min. ilości i bez aktywności)
 * - fast=1 – tryb szybki (hurtowa aktualizacja stock_available)
 */

require_once(dirname(__FILE__) . '/cron_init.php');

// Ustawiamy domyślne parametry jeśli nie zostały podane w URL
if (!isset($_GET['source_table']) || $_GET['source_table'] === '') {
    $_GET['source_table'] = 'azada_raw_abro';
}

if (!isset($_GET['pull_light'])) {
    $_GET['pull_light'] = '1';
}

if (!isset($_GET['pull_min_interval'])) {
    $_GET['pull_min_interval'] = '120';
}

if (!isset($_GET['qty_only'])) {
    $_GET['qty_only'] = '1';
}

if (!isset($_GET['fast'])) {
    $_GET['fast'] = '1';
}

$sourceTable = Tools::getValue('source_table', '');
$sourceTable = $sourceTable !== '' ? $sourceTable : 'azada_raw_abro';

AzadaCronLog::run('update_qty_abro', ['AzadaCronRunner', 'runUpdateQty'], $sourceTable);

