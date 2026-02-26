<?php

require_once(dirname(__FILE__) . '/AzadaInstaller.php');
require_once(dirname(__FILE__) . '/AzadaLogger.php');
require_once(dirname(__FILE__) . '/AzadaCategoryImportMatcher.php');
require_once(dirname(__FILE__) . '/AzadaManufacturerImportMatcher.php');

/**
 * AzadaCronRunner
 *
 * Jeden "silnik" dla różnych endpointów CRON. Każdy plik cron_*.php woła tylko odpowiednią metodę.
 *
 * Zasady:
 * - Token bezpieczeństwa (AZADA_USE_SECURE_TOKEN + AZADA_CRON_KEY).
 * - Lock (flock) żeby nie odpalać dwóch tych samych zadań naraz.
 * - Import = pobieranie danych z hurtowni do tabel RAW.
 * - Update = aktualizacja produktów w PrestaShop na podstawie RAW (bez pobierania plików z hurtowni).
 */
class AzadaCronRunner
{
    /**
     * Wspólne ustawienia wykonania.
     */
    public static function init($taskName = 'cron')
    {
        @ini_set('max_execution_time', 0);
        @ini_set('memory_limit', '1024M');

        // Wyjście "czyste" dla crona (żeby hosting dobrze logował).
        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        }

        self::assertToken();
        self::acquireLockOrExit($taskName);
    }

    /**
     * Sprawdzenie tokena bezpieczeństwa.
     */
    public static function assertToken()
    {
        $useToken = (int)Configuration::get('AZADA_USE_SECURE_TOKEN', 1);
        if ($useToken !== 1) {
            return true;
        }

        $expected = (string)Configuration::get('AZADA_CRON_KEY');
        $provided = (string)Tools::getValue('token', '');

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            if (function_exists('http_response_code')) {
                http_response_code(403);
            }
            echo "ERROR: Invalid token.\n";
            echo "Hint: włącz token w module i skopiuj URL z konfiguracji.\n";
            exit;
        }

        return true;
    }

    /**
     * Lock per task (import_full, update_qty, ...).
     * Używamy flock – lock zwalnia się automatycznie po zakończeniu procesu.
     */
    private static function acquireLockOrExit($taskName)
    {
        $safe = preg_replace('/[^a-z0-9_\\-]/i', '_', (string)$taskName);
        $lockFile = _PS_CACHE_DIR_ . 'azada_wholesaler_pro_' . $safe . '.lock';

        $fp = @fopen($lockFile, 'c+');
        if (!$fp) {
            // Brak locka nie jest krytyczny, ale lepiej nie robić równoległych importów.
            echo "WARN: Nie można utworzyć pliku lock: {$lockFile}\n";
            return;
        }

        if (!@flock($fp, LOCK_EX | LOCK_NB)) {
            echo "SKIP: Zadanie '{$taskName}' już działa (lock aktywny).\n";
            exit;
        }

        // Trzymamy uchwyt do końca skryptu
        self::$lockHandle = $fp;
        @ftruncate($fp, 0);
        @fwrite($fp, (string)time());
    }

    /** @var resource|null */
    private static $lockHandle = null;

    /**
     * Sprawdza czy inny task ma aktywny lock.
     * Nie blokuje – jeśli lock wolny, chwilowo go łapie i natychmiast zwalnia.
     */
    private static function isTaskRunning($taskName)
    {
        $safe = preg_replace('/[^a-z0-9_\-]/i', '_', (string)$taskName);
        $lockFile = _PS_CACHE_DIR_ . 'azada_wholesaler_pro_' . $safe . '.lock';

        $fp = @fopen($lockFile, 'c+');
        if (!$fp) {
            return false;
        }

        $locked = !@flock($fp, LOCK_EX | LOCK_NB);
        if (!$locked) {
            // Zwolnij natychmiast
            @flock($fp, LOCK_UN);
        }
        @fclose($fp);
        return $locked;
    }

    private static function printHeader($title)
    {
        echo "==========================================================\n";
        echo "  {$title} - " . date('Y-m-d H:i:s') . "\n";
        echo "==========================================================\n\n";
    }

    private static function printFooter($title, $okCount, $errCount, $skipCount = 0)
    {
        echo "\n==========================================================\n";
        echo "  KONIEC: {$title}\n";
        echo "  OK: " . (int)$okCount . " | ERR: " . (int)$errCount . " | SKIP: " . (int)$skipCount . "\n";
        echo "==========================================================\n";
    }

    /**
     * CRON: Import FULL (pobranie danych z hurtowni do RAW).
     * To jest odpowiednik przycisku "POBIERZ DANE" w HUB.
     */
    public static function runImportFull()
    {
        self::init('import_full');
        self::printHeader('CRON IMPORT FULL');

        $wholesalers = self::getActiveWholesalersForImport();
        if (empty($wholesalers)) {
            echo "Brak aktywnych hurtowni do importu.\n";
            self::printFooter('CRON IMPORT FULL', 0, 0, 0);
            return;
        }

        $engine = new AzadaImportEngine();
        $ok = 0;
        $err = 0;

        // Po imporcie warto odświeżyć tabelę indeksu (Poczekalnia), aby CRON CREATE PRODUCTS
        // działał w pełni automatycznie (bez konieczności wchodzenia w Poczekalnię w panelu).
        // Można wyłączyć: refresh_index=0
        $refreshIndex = ((int)Tools::getValue('refresh_index', 1) === 1);

        foreach ($wholesalers as $w) {
            $id = (int)$w['id_wholesaler'];
            $name = isset($w['name']) ? (string)$w['name'] : ('ID ' . $id);
            $raw = isset($w['raw_table_name']) ? (string)$w['raw_table_name'] : '';

            echo ">> {$name} (id={$id}, raw_table={$raw})\n";

            try {
                $res = $engine->runFullImport($id);
            } catch (Exception $e) {
                $res = ['status' => 'error', 'msg' => $e->getMessage()];
            }

            $status = isset($res['status']) ? (string)$res['status'] : 'error';
            $msg = isset($res['msg']) ? (string)$res['msg'] : '';

            if ($status === 'success') {
                echo "OK: {$msg}

";
                $ok++;

                if ($refreshIndex && $raw !== '') {
                    // Odświeżamy tylko ten fragment indeksu, który odpowiada tej hurtowni.
                    $ref = self::refreshSearchIndexForTable($raw);
                    if (is_array($ref)) {
                        $ins = isset($ref['inserted']) ? (int)$ref['inserted'] : 0;
                        $del = isset($ref['deleted']) ? (int)$ref['deleted'] : 0;
                        $st = isset($ref['status']) ? (string)$ref['status'] : 'ok';
                        echo "INDEX: {$raw} – {$st}, deleted={$del}, inserted={$ins}

";
                    }
                }
            } else {
                echo "ERR: {$msg}\n\n";
                $err++;
            }
        }

        self::printFooter('CRON IMPORT FULL', $ok, $err, 0);
    }

    /**
     * CRON: Import LIGHT – tryb lekki, jeśli integracja wspiera.
     *
     * Jeśli integracja nie ma metody importProductsLight(), zwraca SKIP.
     */
    public static function runImportLight()
    {
        self::init('import_light');
        self::printHeader('CRON IMPORT LIGHT');

        // Bezpiecznik: nie odpalamy LIGHT w trakcie FULL (ABRO i podobne integracje przebudowują tabele).
        if (self::isTaskRunning('import_full')) {
            echo "SKIP: Trwa CRON IMPORT FULL (lock aktywny). Import LIGHT zostaje pominięty.\n";
            self::printFooter('CRON IMPORT LIGHT', 0, 0, 1);
            return;
        }

        $wholesalers = self::getActiveWholesalersForImport();
        if (empty($wholesalers)) {
            echo "Brak aktywnych hurtowni do importu.\n";
            self::printFooter('CRON IMPORT LIGHT', 0, 0, 0);
            return;
        }

        $engine = new AzadaImportEngine();
        $ok = 0;
        $err = 0;
        $skip = 0;

        foreach ($wholesalers as $w) {
            $id = (int)$w['id_wholesaler'];
            $name = isset($w['name']) ? (string)$w['name'] : ('ID ' . $id);
            $raw = isset($w['raw_table_name']) ? (string)$w['raw_table_name'] : '';

            echo ">> {$name} (id={$id}, raw_table={$raw})\n";

            try {
                if (method_exists($engine, 'runLightImport')) {
                    $res = $engine->runLightImport($id);
                } else {
                    $res = ['status' => 'skipped', 'msg' => 'Brak metody runLightImport() w AzadaImportEngine.'];
                }
            } catch (Exception $e) {
                $res = ['status' => 'error', 'msg' => $e->getMessage()];
            }

            $status = isset($res['status']) ? (string)$res['status'] : 'error';
            $msg = isset($res['msg']) ? (string)$res['msg'] : '';

            if ($status === 'success') {
                echo "OK: {$msg}\n\n";
                $ok++;
            } elseif ($status === 'skipped') {
                echo "SKIP: {$msg}\n\n";
                $skip++;
            } else {
                echo "ERR: {$msg}\n\n";
                $err++;
            }
        }

        self::printFooter('CRON IMPORT LIGHT', $ok, $err, $skip);
    }

    /**
     * CRON: Rebuild Index (Poczekalnia)
     *
     * Odświeża tabelę _DB_PREFIX_azada_raw_search_index na podstawie tabel RAW.
     *
     * Parametry:
     * - source_table=azada_raw_abro (opcjonalnie) → odśwież tylko jedną hurtownię (jedną tabelę RAW)
     *
     * Uwaga: ten CRON nie pobiera danych z hurtowni – tylko buduje indeks na podstawie tego,
     * co już jest w tabelach RAW.
     */
    public static function runRebuildSearchIndex()
    {
        self::init('rebuild_index');
        self::printHeader('CRON REBUILD INDEX');

        self::ensureSearchIndexTableExists();

        $only = trim((string)Tools::getValue('source_table', ''));
        $tables = [];

        if ($only !== '') {
            $tables[] = $only;
        } else {
            $tables = self::getAvailableRawTablesForIndex();
        }

        if (empty($tables)) {
            echo "Brak tabel RAW do zbudowania indeksu.
";
            self::printFooter('CRON REBUILD INDEX', 0, 0, 0);
            return;
        }

        $ok = 0;
        $err = 0;
        $skip = 0;

        foreach ($tables as $t) {
            $t = trim((string)$t);
            if ($t === '') {
                $skip++;
                continue;
            }

            $res = self::refreshSearchIndexForTable($t);
            if (!is_array($res)) {
                echo "ERR: {$t} – nieznany błąd.
";
                $err++;
                continue;
            }

            $status = isset($res['status']) ? (string)$res['status'] : 'ok';
            $deleted = isset($res['deleted']) ? (int)$res['deleted'] : 0;
            $inserted = isset($res['inserted']) ? (int)$res['inserted'] : 0;
            $msg = isset($res['msg']) ? (string)$res['msg'] : '';

            if ($status === 'skip') {
                echo "SKIP: {$t} – {$msg}
";
                $skip++;
                continue;
            }

            if ($status === 'error') {
                echo "ERR: {$t} – {$msg}
";
                $err++;
                continue;
            }

            echo "OK: {$t} – deleted={$deleted}, inserted={$inserted}
";
            $ok++;
        }

        self::printFooter('CRON REBUILD INDEX', $ok, $err, $skip);
    }



    /**
     * CRON: Update QTY – aktualizuje stany w PrestaShop na podstawie RAW.
     *
     * Domyślnie dotyczy produktów, które mają wpis w tabeli product_origin (produkty tworzone modułem).
     */
    public static function runUpdateQty()
    {
        self::init('update_qty');
        self::printHeader('CRON UPDATE QTY');

        // Bezpiecznik: nie aktualizujemy stanów w trakcie pełnego importu, bo niektóre integracje
        // (np. ABRO) przebudowują tabele RAW i w trakcie mogłyby wyglądać jak "puste".
        if (self::isTaskRunning('import_full')) {
            echo "SKIP: Trwa CRON IMPORT FULL (lock aktywny). Update QTY zostaje pominięty, żeby uniknąć chwilowego wyzerowania stanów.\n";
            self::printFooter('CRON UPDATE QTY', 0, 0, 1);
            return;
        }

        AzadaInstaller::ensureProductOriginTable();

        $idShop = (int)Context::getContext()->shop->id;
        if ($idShop <= 0) {
            $idShop = (int)Configuration::get('PS_SHOP_DEFAULT');
        }

        $updQty = (int)Configuration::get('AZADA_UPD_QTY', 1) === 1;
        $updMinQty = (int)Configuration::get('AZADA_UPD_MIN_QTY', 0) === 1;
        $updActive = (int)Configuration::get('AZADA_UPD_ACTIVE', 1) === 1;

        // Override dla częstych cronów stanów (np. ABRO co kilka minut):
        // qty_only=1 → tylko ilość, bez min. ilości i bez aktywności.
        $qtyOnly = (int)Tools::getValue('qty_only', 0) === 1;
        if ($qtyOnly) {
            $updMinQty = false;
            $updActive = false;
        }
        $zeroAction = (int)Configuration::get('AZADA_STOCK_ZERO_ACTION', 0);
        $missingToZero = (int)Configuration::get('AZADA_STOCK_MISSING_ZERO', 1) === 1;

        if (!$updQty && !$updMinQty && !$updActive) {
            echo "INFO: W ustawieniach modułu wyłączono aktualizację stanów/min.ilości/aktywności (AZADA_UPD_QTY / AZADA_UPD_MIN_QTY / AZADA_UPD_ACTIVE).\n";
            self::printFooter('CRON UPDATE QTY', 0, 0, 0);
            return;
        }

        $onlySourceTable = trim((string)Tools::getValue('source_table', ''));
        $fast = (int)Tools::getValue('fast', 0) === 1;

        // Tryb FAST (optymalny do częstych aktualizacji stanów):
        // - działa tylko gdy mamy filtr source_table
        // - i gdy aktualizujemy tylko QTY (bez minQty i bez aktywności)
        if ($fast && $onlySourceTable !== '' && $updQty && !$updMinQty && !$updActive) {
            // Opcjonalny PULL (LIGHT) przed PUSH
            $pullLight = (int)Tools::getValue('pull_light', 0) === 1;
            if (!$pullLight && $onlySourceTable === 'azada_raw_abro') {
                // ABRO: domyślnie pull_light, jeśli cron jest uruchamiany per-source_table
                $pullLight = true;
            }

            if ($pullLight) {
                self::refreshRawUsingLightImport([
                    ['source_table' => $onlySourceTable],
                ]);
                echo "\n";
            }

            $idShopGroup = 0;
            if (isset(Context::getContext()->shop) && isset(Context::getContext()->shop->id_shop_group)) {
                $idShopGroup = (int)Context::getContext()->shop->id_shop_group;
            }

            $onlyModule = (int)Tools::getValue('only_module', 1) === 1;
            $missingToZero = (int)Configuration::get('AZADA_STOCK_MISSING_ZERO', 1) === 1;

            $res = self::fastUpdateQtyForSourceTable($onlySourceTable, $idShop, $idShopGroup, $missingToZero, $onlyModule);
            $ok = (int)$res['ok'];
            $err = (int)$res['err'];
            $skip = (int)$res['skip'];
            self::printFooter('CRON UPDATE QTY (FAST)', $ok, $err, $skip);
            return;
        }

        $origins = self::getOriginRows();
        if (empty($origins)) {
            echo "Brak produktów w tabeli pochodzenia (azada_wholesaler_pro_product_origin).\n";
            self::printFooter('CRON UPDATE QTY', 0, 0, 0);
            return;
        }

        // Opcjonalnie: zanim przeniesiemy stany do Presta, możemy odświeżyć RAW dla hurtowni,
        // które wspierają tryb LIGHT (np. ABRO – szybka synchronizacja stanów bez pełnego importu).
        //
        // Domyślnie włączone tylko, gdy wywołujesz cron z parametrem pull_light=1
        // lub gdy filtrujesz po source_table=azada_raw_abro (najczęstszy przypadek dla stanów co kilka minut).
        $sourceFilter = trim((string)Tools::getValue('source_table', ''));
        $pullLight = (int)Tools::getValue('pull_light', 0) === 1;
        if (!$pullLight && $sourceFilter === 'azada_raw_abro') {
            $pullLight = true;
        }

        if ($pullLight) {
            self::refreshRawUsingLightImport($origins);
            echo "\n";
        }

        $ok = 0;
        $err = 0;
        $skip = 0;

        foreach ($origins as $o) {
            $idProduct = (int)$o['id_product'];
            $sourceTable = isset($o['source_table']) ? (string)$o['source_table'] : '';
            $ean = isset($o['ean13']) ? (string)$o['ean13'] : '';
            $ref = isset($o['reference']) ? (string)$o['reference'] : '';

            if ($idProduct <= 0 || $sourceTable === '') {
                $skip++;
                continue;
            }

            $raw = self::fetchRawRow($sourceTable, $ean, $ref, ['ilosc','wymagane_oz','ilosc_w_opakowaniu','jednostkapodstawowa','NaStanie']);
            if (!is_array($raw) || empty($raw)) {
                if ($missingToZero && $updQty) {
                    self::applyQtyToProduct($idProduct, 0, $idShop);
                    if ($updActive) {
                        self::applyStockZeroAction($idProduct, 0, $zeroAction);
                    }
                    $ok++;
                } else {
                    $skip++;
                }
                continue;
            }

            $qty = self::parseInt(isset($raw['ilosc']) ? $raw['ilosc'] : 0);
            if ($updQty) {
                self::applyQtyToProduct($idProduct, $qty, $idShop);
            }

            if ($updMinQty) {
                $minQty = self::computeMinimalQuantity($raw);
                self::applyMinimalQtyToProduct($idProduct, $minQty);
            }

            if ($updActive) {
                self::applyStockZeroAction($idProduct, $qty, $zeroAction);
            }

            $ok++;
        }

        self::printFooter('CRON UPDATE QTY', $ok, $err, $skip);
    }

    /**
     * Odświeża tabele RAW dla hurtowni, które wspierają importProductsLight().
     *
     * Mechanizm jest future-proof:
     * - jeśli dodasz nową hurtownię i dopiszesz jej importProductsLight(),
     *   to Update QTY automatycznie zacznie ją "pullować" (gdy pull_light=1).
     */
    private static function refreshRawUsingLightImport(array $origins)
    {
        // Kolejny bezpiecznik – jeśli import light już działa w innym procesie, nie dublujemy.
        if (self::isTaskRunning('import_light')) {
            echo "SKIP: Trwa CRON IMPORT LIGHT (lock aktywny) – pomijam dodatkowe odświeżenie RAW.\n";
            return;
        }

        // Zbierz unikalne source_table
        $sourceTables = [];
        foreach ($origins as $o) {
            $st = isset($o['source_table']) ? trim((string)$o['source_table']) : '';
            if ($st !== '') {
                $sourceTables[$st] = true;
            }
        }

        if (empty($sourceTables)) {
            return;
        }

        $engine = new AzadaImportEngine();
        $doneAny = false;

        echo "INFO: pull_light=1 → odświeżam RAW przez LIGHT import (tylko integracje, które wspierają ten tryb).\n";

        foreach (array_keys($sourceTables) as $sourceTable) {
            $idWh = self::resolveWholesalerIdBySourceTable($sourceTable);
            if ($idWh <= 0) {
                continue;
            }

            // Minimalny interwał (żeby nie hammerować endpointu w razie częstych wywołań)
            $minIntervalSec = (int)Tools::getValue('pull_min_interval', 120);
            if ($minIntervalSec < 0) {
                $minIntervalSec = 0;
            }
            $lastKey = 'AZADA_LIGHT_LAST_' . (int)$idWh;
            $lastTs = (int)Configuration::get($lastKey);
            if ($minIntervalSec > 0 && $lastTs > 0 && (time() - $lastTs) < $minIntervalSec) {
                echo "SKIP: id_wholesaler={$idWh} (" . pSQL($sourceTable) . ") – LIGHT był niedawno (" . (time() - $lastTs) . "s temu).\n";
                continue;
            }

            $doneAny = true;
            echo "\n>> LIGHT import: source_table=" . pSQL($sourceTable) . " (id_wholesaler={$idWh})\n";

            try {
                $res = $engine->runLightImport((int)$idWh);
            } catch (Exception $e) {
                $res = ['status' => 'error', 'msg' => $e->getMessage()];
            }

            $status = isset($res['status']) ? (string)$res['status'] : 'error';
            $msg = isset($res['msg']) ? (string)$res['msg'] : '';

            if ($status === 'success') {
                Configuration::updateValue($lastKey, (int)time());
                echo "OK: {$msg}\n";
            } elseif ($status === 'skipped') {
                echo "SKIP: {$msg}\n";
            } else {
                echo "ERR: {$msg}\n";
            }
        }

        if ($doneAny) {
            echo "\nINFO: zakończono odświeżanie RAW (LIGHT).\n";
        }
    }

    /**
     * FAST QTY update – hurtowa aktualizacja stock_available JOIN origin + RAW.
     *
     * Ten tryb jest idealny do uruchamiania co kilka minut (np. ABRO), bo:
     * - nie robi N zapytań per produkt,
     * - aktualizuje tylko te rekordy, gdzie qty faktycznie się zmieniło.
     *
     * Uwaga: w trybie FAST aktualizujemy wyłącznie ilości (qty). Min. ilość i aktywność
     * powinny być aktualizowane rzadziej (lub w trybie standardowym).
     */
    private static function fastUpdateQtyForSourceTable($sourceTable, $idShop, $idShopGroup, $missingToZero, $onlyModule)
    {
        $sourceTable = trim((string)$sourceTable);
        if ($sourceTable === '') {
            return ['ok' => 0, 'err' => 0, 'skip' => 0];
        }

        // PrestaShop stock_available ma dwa tryby:
        // - stock per sklep: id_shop > 0, id_shop_group = 0
        // - stock współdzielony w grupie: id_shop = 0, id_shop_group > 0
        // NIE powinno być wierszy z id_shop > 0 i id_shop_group > 0 (to powoduje duplikaty w listach).
        $shareStock = false;
        try {
            if (class_exists('ShopGroup') && (int)$idShopGroup > 0) {
                $sg = new ShopGroup((int)$idShopGroup);
                if (Validate::isLoadedObject($sg) && (int)$sg->share_stock === 1) {
                    $shareStock = true;
                }
            }
        } catch (Exception $e) {
            $shareStock = false;
        }

        // Ustal, który rekord stock_available jest „właściwy” dla tej instalacji.
        // Jeśli share_stock=1 → operujemy na id_shop=0 + id_shop_group.
        // W przeciwnym wypadku → operujemy na id_shop + id_shop_group=0.
        $saIdShop = $shareStock ? 0 : (int)$idShop;
        $saIdShopGroup = $shareStock ? (int)$idShopGroup : 0;

        $originTable = _DB_PREFIX_ . 'azada_wholesaler_pro_product_origin';
        $stockTable = _DB_PREFIX_ . 'stock_available';
        $rawTable = _DB_PREFIX_ . $sourceTable;
        $productTable = _DB_PREFIX_ . 'product';

        $db = Db::getInstance();

        // Szybki sanity-check: czy RAW table istnieje.
        $rawExists = (bool)$db->getValue(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='" . pSQL($rawTable) . "'"
        );
        if (!$rawExists) {
            echo "ERR: Brak tabeli RAW: {$rawTable}\n";
            return ['ok' => 0, 'err' => 1, 'skip' => 0];
        }

        $whereOrigin = "o.source_table='" . pSQL($sourceTable) . "'";
        if ((bool)$onlyModule) {
            $whereOrigin .= " AND o.created_by_module=1";
        }

        // Sprzątanie "złych" rekordów stock_available, które mogą powodować duplikaty w BO.
        // Usuwamy tylko dla produktów obsługiwanych przez moduł (JOIN po origin).
        $cleanupParts = [];
        // Zawsze: id_shop > 0 i id_shop_group > 0 to błąd (nasz wcześniejszy FAST insert mógł to utworzyć).
        $cleanupParts[] = '(sa.id_shop > 0 AND sa.id_shop_group > 0)';
        if ($shareStock) {
            // Jeśli stock jest współdzielony w grupie, to rekord shop-specific (id_shop=X,id_shop_group=0) jest zbędny.
            $cleanupParts[] = '(sa.id_shop = ' . (int)$idShop . ' AND sa.id_shop_group = 0)';
        } else {
            // Jeśli stock jest per sklep, to rekord group-specific (id_shop=0,id_shop_group=G) jest zbędny.
            $cleanupParts[] = '(sa.id_shop = 0 AND sa.id_shop_group = ' . (int)$idShopGroup . ')';
        }

        $deleteSql = 'DELETE sa FROM `' . bqSQL($stockTable) . '` sa
            INNER JOIN `' . bqSQL($originTable) . '` o
                ON o.id_product = sa.id_product AND ' . $whereOrigin . '
            INNER JOIN `' . bqSQL($productTable) . '` p
                ON p.id_product = o.id_product
            WHERE sa.id_product_attribute = 0
              AND (' . implode(' OR ', $cleanupParts) . ')';

        try {
            $db->execute($deleteSql);
        } catch (Exception $e) {
            // Nie blokuj całego crona – jeśli nie da się sprzątnąć, przejdź dalej.
            echo 'WARN: Nie udało się posprzątać stock_available (duplikaty) – ' . $e->getMessage() . "\n";
        }

        // Upewniamy się, że stock_available ma rekordy dla tych produktów (w prawidłowym trybie: shop albo group).
        // (bez tego UPDATE mógłby nic nie zmienić dla świeżych produktów)
        $insertSql = "INSERT IGNORE INTO `" . bqSQL($stockTable) . "`
            (`id_product`,`id_product_attribute`,`id_shop`,`id_shop_group`,`quantity`,`depends_on_stock`,`out_of_stock`)
            SELECT o.id_product, 0, " . (int)$saIdShop . ", " . (int)$saIdShopGroup . ", 0, 0, 2
            FROM `" . bqSQL($originTable) . "` o
            INNER JOIN `" . bqSQL($productTable) . "` p ON p.id_product = o.id_product
            WHERE {$whereOrigin}";
        $db->execute($insertSql);

        // Priorytet matchu jak w fetchRawRow(): najpierw SKU (produkt_id), potem EAN (kod_kreskowy)
        $targetExpr = $missingToZero
            ? "GREATEST(0, CAST(COALESCE(r_sku.`ilosc`, r_ean.`ilosc`, 0) AS SIGNED))"
            : "GREATEST(0, CAST(COALESCE(r_sku.`ilosc`, r_ean.`ilosc`, sa.`quantity`) AS SIGNED))";

        $whereMatch = $missingToZero
            ? ''
            : ' AND (r_sku.id_raw IS NOT NULL OR r_ean.id_raw IS NOT NULL)';

        $updateSql = "UPDATE `" . bqSQL($stockTable) . "` sa
            INNER JOIN `" . bqSQL($originTable) . "` o
                ON o.id_product = sa.id_product AND {$whereOrigin}
            INNER JOIN `" . bqSQL($productTable) . "` p
                ON p.id_product = o.id_product
            LEFT JOIN `" . bqSQL($rawTable) . "` r_sku
                ON (o.reference IS NOT NULL AND o.reference <> '' AND r_sku.`produkt_id` = o.reference)
            LEFT JOIN `" . bqSQL($rawTable) . "` r_ean
                ON (o.ean13 IS NOT NULL AND o.ean13 <> '' AND r_ean.`kod_kreskowy` = o.ean13)
            SET sa.`quantity` = {$targetExpr}
            WHERE sa.id_product_attribute = 0
              AND sa.id_shop = " . (int)$saIdShop . "
              AND sa.id_shop_group = " . (int)$saIdShopGroup . "
              AND sa.`quantity` <> {$targetExpr}
              {$whereMatch}";

        $ok = 0;
        $err = 0;
        $skip = 0;

        try {
            $db->execute($updateSql);
            $affected = method_exists($db, 'Affected_Rows') ? (int)$db->Affected_Rows() : 0;
            $ok = $affected;
            echo "OK: FAST QTY updated rows: {$affected} (source_table={$sourceTable}, shop={$idShop}).\n";
        } catch (Exception $e) {
            $err++;
            echo "ERR: FAST QTY update failed: " . $e->getMessage() . "\n";
        }

        // Skip count – informacyjnie: ile produktów jest w origin dla tej hurtowni.
        try {
            $total = (int)$db->getValue("SELECT COUNT(*) FROM `" . bqSQL($originTable) . "` o WHERE {$whereOrigin}");
            $skip = max(0, $total - $ok);
            echo "INFO: origin rows for {$sourceTable}: {$total}.\n";
        } catch (Exception $e) {
            // ignore
        }

        return ['ok' => $ok, 'err' => $err, 'skip' => $skip];
    }

    /**
     * Mapuje source_table (np. azada_raw_abro) → id_wholesaler z tabeli integracji.
     */

    /**
     * Mapuje source_table (np. azada_raw_abro) → id_wholesaler z tabeli integracji.
     */
    private static function resolveWholesalerIdBySourceTable($sourceTable)
    {
        $sourceTable = trim((string)$sourceTable);
        if ($sourceTable === '') {
            return 0;
        }

        // Uwaga: Db::getValue() w PrestaShop może dopinać LIMIT 1 automatycznie.
        // Dlatego w zapytaniu NIE umieszczamy "LIMIT 1", żeby uniknąć "LIMIT 1 LIMIT 1".
        $sql = "SELECT MIN(id_wholesaler) FROM `" . bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_integration') . "`
                WHERE raw_table_name='" . pSQL($sourceTable) . "'";

        try {
            return (int)Db::getInstance()->getValue($sql);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * CRON: Update PRICE – aktualizuje ceny (sprzedaż/koszt/VAT) na podstawie RAW i narzutów.
     */
    public static function runUpdatePrice()
    {
        self::init('update_price');
        self::printHeader('CRON UPDATE PRICE');

        AzadaInstaller::ensureProductOriginTable();

        $updPrice = (int)Configuration::get('AZADA_UPD_PRICE', 1) === 1;
        $updWholesale = (int)Configuration::get('AZADA_UPD_WHOLESALE_PRICE', 0) === 1;
        $updTax = (int)Configuration::get('AZADA_UPD_TAX', 0) === 1;
        $rounding = (int)Configuration::get('AZADA_PRICE_ROUNDING', 0);

        if (!$updPrice && !$updWholesale && !$updTax) {
            echo "INFO: W ustawieniach modułu wyłączono aktualizację cen/kosztu/VAT (AZADA_UPD_PRICE / AZADA_UPD_WHOLESALE_PRICE / AZADA_UPD_TAX).\n";
            self::printFooter('CRON UPDATE PRICE', 0, 0, 0);
            return;
        }

        $origins = self::getOriginRows();
        if (empty($origins)) {
            echo "Brak produktów w tabeli pochodzenia (azada_wholesaler_pro_product_origin).\n";
            self::printFooter('CRON UPDATE PRICE', 0, 0, 0);
            return;
        }

        $ok = 0;
        $err = 0;
        $skip = 0;

        foreach ($origins as $o) {
            $idProduct = (int)$o['id_product'];
            $sourceTable = isset($o['source_table']) ? (string)$o['source_table'] : '';
            $ean = isset($o['ean13']) ? (string)$o['ean13'] : '';
            $ref = isset($o['reference']) ? (string)$o['reference'] : '';

            if ($idProduct <= 0 || $sourceTable === '') {
                $skip++;
                continue;
            }

            $raw = self::fetchRawRow($sourceTable, $ean, $ref, ['cenaporabacienetto','vat','kategoria']);
            if (!is_array($raw) || empty($raw)) {
                $skip++;
                continue;
            }

            $purchaseNet = self::parseFloat(isset($raw['cenaporabacienetto']) ? $raw['cenaporabacienetto'] : 0);
            $vatRate = self::parseFloat(isset($raw['vat']) ? $raw['vat'] : 0);
            $rawCategory = isset($raw['kategoria']) ? (string)$raw['kategoria'] : '';

            if ($purchaseNet <= 0.0 && (int)Configuration::get('AZADA_SKIP_NO_PRICE', 1) === 1) {
                $skip++;
                continue;
            }

            $saleNet = self::computeSalePriceNet($purchaseNet, $sourceTable, $rawCategory);
            $saleNet = self::applyPriceRounding($saleNet, $rounding);

            try {
                $product = new Product($idProduct);
                if (!Validate::isLoadedObject($product)) {
                    $skip++;
                    continue;
                }

                if ($updWholesale) {
                    $product->wholesale_price = (float)$purchaseNet;
                }

                if ($updPrice) {
                    $product->price = (float)$saleNet;
                }

                if ($updTax) {
                    $idTaxRules = self::resolveTaxRulesGroupIdByRate($vatRate);
                    if ($idTaxRules > 0) {
                        $product->id_tax_rules_group = (int)$idTaxRules;
                    }
                }

                $product->update();
                $ok++;
            } catch (Exception $e) {
                $err++;
                echo "ERR: id_product={$idProduct} – " . $e->getMessage() . "\n";
            }
        }

        self::printFooter('CRON UPDATE PRICE', $ok, $err, $skip);
    }

    /**
     * CRON: Create Products (Import ON) – placeholder.
     *
     * Ten cron jest celowo osobny, ale jego logika (automatyczne tworzenie produktów z wybranych kategorii)
     * jest wdrażana w kolejnym kroku, po pełnym domknięciu mapowania kategorii.
     */

    public static function runCreateProducts()
    {
        self::init('create_products');
        self::printHeader('CRON CREATE PRODUCTS');

        // Bezpiecznik: nie tworzymy produktów w trakcie pełnego importu (tabele RAW mogą być przebudowywane).
        if (self::isTaskRunning('import_full')) {
            echo "SKIP: Trwa CRON IMPORT FULL (lock aktywny). Tworzenie produktów zostaje pominięte.\n";
            self::printFooter('CRON CREATE PRODUCTS', 0, 0, 1);
            return;
        }

        AzadaInstaller::ensureProductOriginTable();
        AzadaInstaller::ensureCategoryMapTables();

        $limit = (int)Tools::getValue('limit', 50);
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 1000) {
            $limit = 1000;
        }

        // Ile wierszy z indeksu skanujemy, żeby znaleźć $limit importowalnych.
        $scanLimit = (int)Tools::getValue('scan_limit', 0);
        if ($scanLimit < 1) {
            $scanLimit = max(200, $limit * 10);
        }
        if ($scanLimit > 5000) {
            $scanLimit = 5000;
        }

        $onlyInStock = ((int)Tools::getValue('only_in_stock', 1) === 1);
        $skipIfExistingPsProduct = ((int)Tools::getValue('skip_existing_ps', 1) === 1);

        // Można przyspieszyć cron: images=0 / desc=0
        // Domyślnie zachowujemy się jak przy "Dodaj ręcznie" w Poczekalni:
        // pobieramy zdjęcia i opis (chyba że jawnie ustawisz images=0 / desc=0).
        $withImages = ((int)Tools::getValue('images', 1) === 1);
        $withDesc = ((int)Tools::getValue('desc', 1) === 1);

        $dryRun = ((int)Tools::getValue('dry', 0) === 1);

        $sourceTableFilter = trim((string)Tools::getValue('source_table', ''));

        echo "PARAMS: limit={$limit}, scan_limit={$scanLimit}, only_in_stock=" . ($onlyInStock ? '1' : '0')
            . ", skip_existing_ps=" . ($skipIfExistingPsProduct ? '1' : '0')
            . ", images=" . ($withImages ? '1' : '0')
            . ", desc=" . ($withDesc ? '1' : '0')
            . ", dry=" . ($dryRun ? '1' : '0')
            . ($sourceTableFilter !== '' ? ", source_table={$sourceTableFilter}" : '')
            . "\n\n";

        // Indeks globalny jest podstawą Poczekalni i najwygodniejszym miejscem do pobrania kandydatów.
        // Jeśli go nie ma – prosimy o wejście do Poczekalni (tabela buduje się automatycznie).
        $indexTable = _DB_PREFIX_ . 'azada_raw_search_index';
        if (!self::tableExists($indexTable)) {
            echo "ERR: Brak tabeli indeksu: {$indexTable}.\n";
            echo "Wejdź w: Integracja Hurtowni PRO → Lista Produktów (Poczekalnia), żeby indeks został zbudowany.\n";
            self::printFooter('CRON CREATE PRODUCTS', 0, 1, 0);
            return;
        }

        $rows = self::fetchIndexRowsForCreate($scanLimit, $sourceTableFilter, false);
        if (empty($rows)) {
            echo "Brak kandydatów do utworzenia. Sprawdź czy masz aktywne mapowania kategorii (is_active=1) i czy w Poczekalni są produkty w tych kategoriach.\n";
            self::printFooter('CRON CREATE PRODUCTS', 0, 0, 0);
            return;
        }

        $ok = 0;
        $err = 0;
        $skip = 0;

        foreach ($rows as $row) {
            if ($ok >= $limit) {
                break;
            }

            $sourceTable = isset($row['source_table']) ? trim((string)$row['source_table']) : '';
            if ($sourceTable === '') {
                $skip++;
                continue;
            }

            $rawCategory = isset($row['kategoria']) ? (string)$row['kategoria'] : '';
            $match = AzadaCategoryImportMatcher::match($sourceTable, $rawCategory);
            if (!is_array($match) || empty($match['is_importable'])) {
                $skip++;
                continue;
            }
            $ean = isset($row['kod_kreskowy']) ? trim((string)$row['kod_kreskowy']) : '';
            $sku = isset($row['produkt_id']) ? trim((string)$row['produkt_id']) : '';

            if ($skipIfExistingPsProduct) {
                $existing = self::findExistingProductId($ean, $sku);
                if ($existing > 0) {
                    echo "SKIP: Produkt już istnieje w PrestaShop (id={$existing}) dla {$sourceTable} (EAN={$ean}, SKU={$sku}).\n";
                    $skip++;
                    continue;
                }
            }

            $raw = self::fetchRawRow($sourceTable, $ean, $sku, []);
            if (!is_array($raw) || empty($raw)) {
                echo "SKIP: Nie znaleziono wiersza RAW dla {$sourceTable} (EAN={$ean}, SKU={$sku}).\n";
                $skip++;
                continue;
            }

            // Filtr: tylko produkty na stanie – sprawdzamy na świeżych danych z RAW (a nie z indeksu),
            // żeby CRON działał poprawnie także przy LIGHT importach (np. ABRO stany co kilka minut).
            if ($onlyInStock) {
                $qty = isset($raw['ilosc']) ? self::parseInt($raw['ilosc']) : 0;
                $naStanie = isset($raw['NaStanie']) ? self::isTrueLike($raw['NaStanie']) : false;
                if (!$naStanie && $qty <= 0) {
                    $skip++;
                    continue;
                }
            }

            $name = isset($raw['nazwa']) ? trim((string)$raw['nazwa']) : '';
            if ($name === '') {
                echo "SKIP: Brak nazwy produktu w RAW ({$sourceTable}, EAN={$ean}, SKU={$sku}).\n";
                $skip++;
                continue;
            }

            if ($dryRun) {
                echo "DRY: Utworzyłbym produkt: {$sourceTable} | {$name} | EAN={$ean} | SKU={$sku}\n";
                $ok++;
                continue;
            }

            $idProduct = self::createProductFromRaw($sourceTable, $raw, $match, [
                'with_images' => $withImages,
                'with_desc' => $withDesc,
            ]);

            if ($idProduct > 0) {
                echo "OK: Utworzono produkt id={$idProduct} ({$sourceTable}) | {$name}\n";
                $ok++;
            } else {
                echo "ERR: Nie udało się utworzyć produktu ({$sourceTable}) | {$name} | EAN={$ean} | SKU={$sku}\n";
                $err++;
            }
        }

        self::printFooter('CRON CREATE PRODUCTS', $ok, $err, $skip);
    }

    

    /**
     * Pobiera kandydatów do tworzenia produktów z tabeli indeksu (Poczekalnia):
     * - tylko takie, które NIE mają powiązania w product_origin (tzn. nie są jeszcze utworzone/połączone),
     * - opcjonalnie tylko dla jednej hurtowni (source_table),
     * - UWAGA: filtrujemy od razu po AKTYWNYCH mapowaniach kategorii (is_active=1 + id_category_default>0),
     *   żeby cron nie tracił czasu na kategorie nieimportowane.
     *
     * Dzięki temu przy starcie (gdy aktywnych kategorii jest mało) cron działa szybko.
     */
    private static function fetchIndexRowsForCreate($limit, $sourceTableFilter = '', $onlyInStock = false)
    {
        $limit = (int)$limit;
        if ($limit < 1) {
            $limit = 1;
        }

        $indexTable = bqSQL(_DB_PREFIX_ . 'azada_raw_search_index');
        $originTable = bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin');
        $productTable = bqSQL(_DB_PREFIX_ . 'product');
        $catMapTable = bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_category_map');

        // Subquery: match po SKU (reference)
        $originSku = "(
"
            . "  SELECT o.source_table, o.reference, MIN(o.id_product) AS id_product
"
            . "  FROM `{$originTable}` o
"
            . "  INNER JOIN `{$productTable}` p ON p.id_product = o.id_product
"
            . "  WHERE TRIM(IFNULL(o.reference,'')) <> ''
"
            . "  GROUP BY o.source_table, o.reference
"
            . ") oref";

        // Subquery: match po EAN
        $originEan = "(
"
            . "  SELECT o.source_table, o.ean13, MIN(o.id_product) AS id_product
"
            . "  FROM `{$originTable}` o
"
            . "  INNER JOIN `{$productTable}` p ON p.id_product = o.id_product
"
            . "  WHERE TRIM(IFNULL(o.ean13,'')) <> ''
"
            . "  GROUP BY o.source_table, o.ean13
"
            . ") oean";

        // Normalizacja pola kategoria na listę segmentów (zgodnie z AzadaCategoryImportMatcher::extractSegments):
        // - usuwa '*'
        // - usuwa spacje wokół średników
        // - zamienia ';' na ',' aby można było użyć FIND_IN_SET
        $catListExpr = "REPLACE(REPLACE(REPLACE(REPLACE(TRIM(IFNULL(s.kategoria,'')), '*', ''), '; ', ';'), ' ;', ';'), ';', ',')";

        $sql = "SELECT s.id_raw, s.source_table, s.kod_kreskowy, s.produkt_id, s.kategoria, s.ilosc, s.NaStanie
"
            . "FROM `{$indexTable}` s
"
            . "LEFT JOIN {$originSku} ON oref.source_table = s.source_table AND oref.reference = s.produkt_id
"
            . "LEFT JOIN {$originEan} ON oean.source_table = s.source_table AND oean.ean13 = s.kod_kreskowy
"
            . "WHERE oref.id_product IS NULL AND oean.id_product IS NULL
"
            . "  AND EXISTS (
"
            . "      SELECT 1 FROM `{$catMapTable}` cm
"
            . "      WHERE cm.source_table = s.source_table
"
            . "        AND cm.source_type='category'
"
            . "        AND cm.is_active=1
"
            . "        AND cm.id_category_default > 0
"
            . "        AND FIND_IN_SET(cm.source_category, {$catListExpr}) > 0
"
            . "  )
";

        $sourceTableFilter = trim((string)$sourceTableFilter);
        if ($sourceTableFilter !== '') {
            $sql .= "  AND s.source_table='" . pSQL($sourceTableFilter) . "'
";
        }

        if ($onlyInStock) {
            $sql .= "  AND (
"
                . "    LOWER(TRIM(IFNULL(s.NaStanie,''))) IN ('1','true','tak','yes')
"
                . "    OR CAST(IFNULL(NULLIF(s.ilosc,''),'0') AS SIGNED) > 0
"
                . "  )
";
        }

        $sql .= "ORDER BY s.id_raw ASC
";
        $sql .= "LIMIT " . (int)$limit;

        try {
            $rows = Db::getInstance()->executeS($sql);
        } catch (Exception $e) {
            $rows = [];
        }

        return is_array($rows) ? $rows : [];
    }

    

    /**
     * Upewnia się, że tabela indeksu (Poczekalnia) istnieje.
     * Nie czyści danych – tylko tworzy strukturę, jeśli brak.
     */
    private static function ensureSearchIndexTableExists()
    {
        $db = Db::getInstance();
        $target = _DB_PREFIX_ . 'azada_raw_search_index';

        // Struktura zgodna z AdminAzadaProductListController::buildGlobalSearchIndexTable()
        $db->execute("CREATE TABLE IF NOT EXISTS `$target` (
            `id_raw` INT(11) NOT NULL AUTO_INCREMENT,
            `source_table` VARCHAR(64) NULL,
            `zdjecieglownelinkurl` TEXT NULL,
            `nazwa` TEXT NULL,
            `kod_kreskowy` TEXT NULL,
            `produkt_id` TEXT NULL,
            `marka` TEXT NULL,
            `kategoria` TEXT NULL,
            `jednostkapodstawowa` TEXT NULL,
            `ilosc` TEXT NULL,
            `wymagane_oz` TEXT NULL,
            `ilosc_w_opakowaniu` TEXT NULL,
            `NaStanie` TEXT NULL,
            `cenaporabacienetto` TEXT NULL,
            `vat` TEXT NULL,
            `LinkDoProduktu` TEXT NULL,
            `data_aktualizacji` DATETIME NULL,
            PRIMARY KEY (`id_raw`)
        ) ENGINE=" . _MYSQL_ENGINE_ . " DEFAULT CHARSET=utf8");
    }

    /**
     * Zwraca listę tabel RAW dostępnych w bazie (bez search_index, _source, _conversion).
     */
    private static function getAvailableRawTablesForIndex()
    {
        $tables = [];
        $db = Db::getInstance();
        $prefix = pSQL(_DB_PREFIX_);
        $rows = $db->executeS("SHOW TABLES LIKE '" . $prefix . "azada_raw_%'");

        if (empty($rows)) {
            return [];
        }

        foreach ($rows as $row) {
            $fullName = (string)reset($row);
            $table = preg_replace('/^' . preg_quote(_DB_PREFIX_, '/') . '/', '', $fullName);

            if ($table === 'azada_raw_search_index') {
                continue;
            }

            if (preg_match('/_(source|conversion)$/', $table)) {
                continue;
            }

            $tables[$table] = $table;
        }

        ksort($tables);
        return array_values($tables);
    }

    /**
     * Odświeża indeks (Poczekalnia) dla jednej tabeli RAW.
     * Robi DELETE po source_table i ponowny INSERT na podstawie aktualnego RAW.
     *
     * @param string $rawTableName np. "azada_raw_abro"
     * @return array status, deleted, inserted, msg
     */
    private static function refreshSearchIndexForTable($rawTableName)
    {
        $rawTableName = trim((string)$rawTableName);

        if ($rawTableName === '' || $rawTableName === 'azada_raw_search_index') {
            return ['status' => 'skip', 'deleted' => 0, 'inserted' => 0, 'msg' => 'Pusta/niewłaściwa tabela.'];
        }

        if (preg_match('/_(source|conversion)$/', $rawTableName)) {
            return ['status' => 'skip', 'deleted' => 0, 'inserted' => 0, 'msg' => 'Tabela techniczna (_source/_conversion) – pomijam.'];
        }

        if (!preg_match('/^azada_raw_[a-z0-9_]+$/i', $rawTableName)) {
            return ['status' => 'skip', 'deleted' => 0, 'inserted' => 0, 'msg' => 'Niepoprawna nazwa tabeli.'];
        }

        self::ensureSearchIndexTableExists();

        $indexTable = _DB_PREFIX_ . 'azada_raw_search_index';
        $rawFull = _DB_PREFIX_ . $rawTableName;

        if (!self::tableExists($rawFull)) {
            return ['status' => 'skip', 'deleted' => 0, 'inserted' => 0, 'msg' => 'Brak tabeli RAW: ' . $rawFull];
        }

        $db = Db::getInstance();

        // Sprawdzamy jakie kolumny istnieją w RAW (różne hurtownie mogą mieć różne zestawy kolumn).
        $cols = $db->executeS("SHOW COLUMNS FROM `" . bqSQL($rawFull) . "`");
        if (empty($cols)) {
            return ['status' => 'skip', 'deleted' => 0, 'inserted' => 0, 'msg' => 'Nie mogę odczytać kolumn RAW.'];
        }

        $has = [];
        foreach ($cols as $c) {
            if (isset($c['Field'])) {
                $has[(string)$c['Field']] = true;
            }
        }

        $columnsToCopy = [
            'zdjecieglownelinkurl', 'nazwa', 'kod_kreskowy', 'produkt_id', 'marka', 'kategoria',
            'jednostkapodstawowa', 'ilosc', 'wymagane_oz', 'ilosc_w_opakowaniu', 'NaStanie',
            'cenaporabacienetto', 'vat', 'LinkDoProduktu', 'data_aktualizacji',
        ];

        $insertCols = ['source_table'];
        $selectExpr = ["'" . pSQL($rawTableName) . "'"];

        foreach ($columnsToCopy as $col) {
            $insertCols[] = $col;
            if (isset($has[$col])) {
                $selectExpr[] = "t.`" . bqSQL($col) . "`";
            } else {
                $selectExpr[] = "NULL";
            }
        }

        // Usuń stary fragment indeksu dla tej hurtowni
        try {
            $db->execute("DELETE FROM `" . bqSQL($indexTable) . "` WHERE `source_table`='" . pSQL($rawTableName) . "'");
            $deleted = method_exists($db, 'Affected_Rows') ? (int)$db->Affected_Rows() : 0;
        } catch (Exception $e) {
            return ['status' => 'error', 'deleted' => 0, 'inserted' => 0, 'msg' => 'DELETE index failed: ' . $e->getMessage()];
        }

        // Wstaw aktualne dane z RAW
        try {
            $sql = "INSERT INTO `" . bqSQL($indexTable) . "` (`" . implode('`,`', array_map('bqSQL', $insertCols)) . "`)
                SELECT " . implode(',', $selectExpr) . "
                FROM `" . bqSQL($rawFull) . "` t";
            $db->execute($sql);
            $inserted = method_exists($db, 'Affected_Rows') ? (int)$db->Affected_Rows() : 0;
        } catch (Exception $e) {
            return ['status' => 'error', 'deleted' => (int)$deleted, 'inserted' => 0, 'msg' => 'INSERT index failed: ' . $e->getMessage()];
        }

        return ['status' => 'ok', 'deleted' => (int)$deleted, 'inserted' => (int)$inserted, 'msg' => ''];
    }

/**
     * Tworzy nowy produkt w PrestaShop na podstawie wiersza RAW.
     *
     * @param string $sourceTable np. azada_raw_abro
     * @param array $raw pełny wiersz z tabeli RAW
     * @param array $catMatch wynik AzadaCategoryImportMatcher::match
     * @param array $options ['with_images'=>bool,'with_desc'=>bool]
     *
     * @return int id_product lub 0
     */
    private static function createProductFromRaw($sourceTable, array $raw, array $catMatch, array $options = [])
    {
        $context = Context::getContext();
        $idShop = (int)$context->shop->id;

        $withImages = isset($options['with_images']) ? (bool)$options['with_images'] : true;
        $withDesc = isset($options['with_desc']) ? (bool)$options['with_desc'] : true;

        $name = isset($raw['nazwa']) ? trim((string)$raw['nazwa']) : '';
        if ($name === '') {
            return 0;
        }

        $ean = isset($raw['kod_kreskowy']) ? (string)$raw['kod_kreskowy'] : '';
        $sku = isset($raw['produkt_id']) ? (string)$raw['produkt_id'] : '';

        $eanNorm = self::normalizeEan($ean);
        $skuRaw = trim((string)$sku);

        // Kategorie z mapowania
        $idDefaultCategory = isset($catMatch['id_category_default']) ? (int)$catMatch['id_category_default'] : 0;
        $categoryIds = [];
        if (isset($catMatch['ps_category_ids']) && is_array($catMatch['ps_category_ids'])) {
            foreach ($catMatch['ps_category_ids'] as $idCat) {
                $idCat = (int)$idCat;
                if ($idCat > 0) {
                    $categoryIds[$idCat] = $idCat;
                }
            }
        }
        if ($idDefaultCategory > 0) {
            $categoryIds[$idDefaultCategory] = $idDefaultCategory;
        }
        $categoryIds = array_values($categoryIds);

        if ($idDefaultCategory <= 0) {
            // Awaryjnie: Home
            $idDefaultCategory = (int)Configuration::get('PS_HOME_CATEGORY');
            if ($idDefaultCategory <= 0) {
                $idDefaultCategory = 2;
            }
            if (!in_array($idDefaultCategory, $categoryIds, true)) {
                $categoryIds[] = $idDefaultCategory;
            }
        }

        // Cena zakupu i sprzedaży
        $purchaseNet = isset($raw['cenaporabacienetto']) ? self::parseFloat($raw['cenaporabacienetto']) : 0.0;
        $rawCategory = isset($raw['kategoria']) ? (string)$raw['kategoria'] : '';

        $saleNet = self::computeSalePriceNet($purchaseNet, $sourceTable, $rawCategory);
        $rounding = (int)Configuration::get('AZADA_PRICE_ROUNDING', 0);
        $saleNet = self::applyPriceRounding($saleNet, $rounding);
        $saleNet = round((float)$saleNet, 6);

        $vatRate = isset($raw['vat']) ? self::parseFloat($raw['vat']) : 0.0;
        $idTaxRulesGroup = self::resolveTaxRulesGroupIdByRate($vatRate);

        $qty = isset($raw['ilosc']) ? self::parseInt($raw['ilosc']) : 0;
        $minQty = self::computeMinimalQuantity($raw);

        // Dostawca = hurtownia
        $wholesalerIntegration = self::ensureWholesalerIntegrationRow($sourceTable);
        $wholesalerName = isset($wholesalerIntegration['name']) ? (string)$wholesalerIntegration['name'] : self::getWholesalerDisplayName($sourceTable);
        $idSupplier = self::ensureSupplierIdForWholesalerName($wholesalerName, $idShop);

        // Producent
        $brand = isset($raw['marka']) ? (string)$raw['marka'] : '';
        $idManufacturer = self::ensureManufacturerId($sourceTable, $brand, $idShop);

        // Reference (SKU) w PrestaShop musi być unikalne i max 32 znaki.
        // Jeśli inny moduł ma już ten sam reference, wygenerujemy _2/_3.
        $baseReference = '';
        if ($skuRaw !== '') {
            $baseReference = self::normalizeReference($skuRaw);
            if ($baseReference === '') {
                $baseReference = $skuRaw;
            }
        } elseif ($eanNorm !== '') {
            $baseReference = 'AZADA_' . $eanNorm;
        } else {
            $baseReference = 'AZADA_' . (string)time();
        }
        $productReference = self::generateUniqueProductReference($baseReference);

        $product = new Product();
        $product->id_shop_default = $idShop;

        // Tak samo jak "Dodaj ręcznie" w Poczekalni: produkt ma być od razu widoczny.
        $product->active = 1;
        $product->visibility = 'both';
        $product->available_for_order = 1;
        $product->show_price = 1;
        $product->indexed = 1;

        $product->reference = $productReference;
        if ($eanNorm !== '') {
            $product->ean13 = $eanNorm;
        }

        $product->id_category_default = (int)$idDefaultCategory;
        $product->price = (float)$saleNet;
        $product->wholesale_price = (float)$purchaseNet;

        if ($idTaxRulesGroup > 0) {
            $product->id_tax_rules_group = (int)$idTaxRulesGroup;
        }

        if ($idSupplier > 0) {
            $product->id_supplier = (int)$idSupplier;
        }

        if ($idManufacturer > 0) {
            $product->id_manufacturer = (int)$idManufacturer;
        }

        $product->minimal_quantity = (int)$minQty;

        // Wyłączamy unit price (nie ustawiamy unity / unit_price_ratio)
        $product->unity = '';
        $product->unit_price_ratio = 0;

        // Multilang: nazwa, slug, opis
        $languages = Language::getLanguages(false);
        foreach ((array)$languages as $lang) {
            $idLang = isset($lang['id_lang']) ? (int)$lang['id_lang'] : 0;
            if ($idLang <= 0) {
                continue;
            }

            $product->name[$idLang] = self::truncateString($name, 128);
            $product->link_rewrite[$idLang] = Tools::link_rewrite($name);

            if ($withDesc) {
                $rawDescription = isset($raw['opis']) ? (string)$raw['opis'] : '';
                $descHtml = self::normalizeDescriptionHtml($rawDescription);
                if ($descHtml !== '') {
                    $product->description[$idLang] = $descHtml;
                    $product->description_short[$idLang] = self::buildShortDescription($descHtml, 400);
                }
            }
        }

        if (!$product->add()) {
            return 0;
        }

        // Dostawca widoczny w zakładce "Dostawcy" (product_supplier)
        if ($idSupplier > 0) {
            $supplierRef = ($skuRaw !== '' ? self::normalizeReference($skuRaw) : $productReference);
            self::ensureProductSupplierLink((int)$product->id, (int)$idSupplier, $supplierRef, (float)$purchaseNet, $idShop);
        }

        // Kategorie
        if (!empty($categoryIds)) {
            if (method_exists($product, 'updateCategories')) {
                $product->updateCategories($categoryIds);
            } elseif (method_exists($product, 'addToCategories')) {
                $product->addToCategories($categoryIds);
            }
        }

        // Stany
        if (class_exists('StockAvailable')) {
            StockAvailable::setQuantity((int)$product->id, 0, (int)$qty, $idShop);
        }

        // Zdjęcia
        if ($withImages) {
            self::addProductImagesFromRaw((int)$product->id, $raw, $name, $idShop);
        }

        // Zapis pochodzenia (ważne: origin.reference = RAW produkt_id)
        Db::getInstance()->execute(
            'DELETE FROM `' . bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_product_origin') . '` WHERE `id_product`=' . (int)$product->id
        );
        Db::getInstance()->insert('azada_wholesaler_pro_product_origin', [
            'id_product' => (int)$product->id,
            'source_table' => pSQL($sourceTable),
            'ean13' => pSQL($eanNorm !== '' ? $eanNorm : $ean),
            'reference' => pSQL($skuRaw),
            'created_by_module' => 1,
            'date_add' => date('Y-m-d H:i:s'),
        ], true);

        return (int)$product->id;
    }

    private static function tableExists($fullTableName)
    {
        $fullTableName = trim((string)$fullTableName);
        if ($fullTableName === '') {
            return false;
        }

        // Najbardziej kompatybilne na hostingach: SHOW TABLES (bez potrzeby dostępu do INFORMATION_SCHEMA).
        try {
            $rows = Db::getInstance()->executeS("SHOW TABLES LIKE '" . pSQL($fullTableName) . "'");
        } catch (Exception $e) {
            $rows = [];
        }

        return is_array($rows) && !empty($rows);
    }

    /**
     * Jeśli w integracjach nie istnieje rekord dla danej hurtowni (raw_table_name), tworzymy go automatycznie.
     */
    private static function ensureWholesalerIntegrationRow($sourceTable)
    {
        $sourceTable = trim((string)$sourceTable);
        if ($sourceTable === '') {
            return null;
        }

        $tableIntegration = _DB_PREFIX_ . 'azada_wholesaler_pro_integration';

        try {
            $rows = Db::getInstance()->executeS(
                'SELECT id_wholesaler, name FROM `' . bqSQL($tableIntegration) . '` '
                . 'WHERE raw_table_name=\'' . pSQL($sourceTable) . '\' '
                . 'ORDER BY id_wholesaler ASC '
                . 'LIMIT 1'
            );
        } catch (Exception $e) {
            $rows = [];
        }

        if (is_array($rows) && !empty($rows) && isset($rows[0]) && is_array($rows[0])) {
            return [
                'id_wholesaler' => (int)$rows[0]['id_wholesaler'],
                'name' => isset($rows[0]['name']) ? (string)$rows[0]['name'] : self::getWholesalerDisplayName($sourceTable),
            ];
        }

        $name = self::getWholesalerDisplayName($sourceTable);
        $now = date('Y-m-d H:i:s');

        $payload = [
            'name' => $name,
            'active' => 1,
            'raw_table_name' => $sourceTable,
            'file_url' => '',
            'file_format' => 'csv',
            'delimiter' => ';',
            'encoding' => 'UTF-8',
            'skip_header' => 1,
            'api_key' => null,
            'b2b_login' => null,
            'b2b_password' => null,
            'connection_status' => 0,
            'diagnostic_result' => null,
            'last_import' => null,
            'date_add' => $now,
            'date_upd' => $now,
        ];

        try {
            Db::getInstance()->insert('azada_wholesaler_pro_integration', $payload, true);
            $id = (int)Db::getInstance()->Insert_ID();
        } catch (Exception $e) {
            $id = 0;
        }

        return [
            'id_wholesaler' => (int)$id,
            'name' => $name,
        ];
    }

    private static function getWholesalerDisplayName($table)
    {
        $name = str_replace('azada_raw_', '', trim((string)$table));
        if ($name === '') {
            return '-';
        }
        return ucfirst($name);
    }

    /**
     * Dostawca (Supplier) dla hurtowni – tworzymy automatycznie jeśli nie istnieje.
     */
    private static function ensureSupplierIdForWholesalerName($wholesalerName, $idShop = 0)
    {
        $name = trim((string)$wholesalerName);
        if ($name === '') {
            return 0;
        }

        $supplierTable = _DB_PREFIX_ . 'supplier';
        try {
            $rows = Db::getInstance()->executeS(
                'SELECT id_supplier FROM `' . bqSQL($supplierTable) . '` '
                . 'WHERE LOWER(name)=LOWER(\'' . pSQL($name) . '\') '
                . 'ORDER BY id_supplier ASC '
                . 'LIMIT 1'
            );
        } catch (Exception $e) {
            $rows = [];
        }

        if (is_array($rows) && !empty($rows) && isset($rows[0]['id_supplier'])) {
            return (int)$rows[0]['id_supplier'];
        }

        if (!class_exists('Supplier')) {
            return 0;
        }

        $supplier = new Supplier();
        $supplier->name = $name;
        $supplier->active = 1;

        if (!$supplier->add()) {
            return 0;
        }

        // MultiShop: powiąż z bieżącym sklepem
        $idShop = (int)$idShop;
        if ($idShop <= 0 && isset(Context::getContext()->shop) && isset(Context::getContext()->shop->id)) {
            $idShop = (int)Context::getContext()->shop->id;
        }

        if ($idShop > 0 && method_exists($supplier, 'associateTo')) {
            try {
                $supplier->associateTo($idShop);
            } catch (Exception $e) {
                // ignore
            }
        }

        return (int)$supplier->id;
    }

    private static function ensureManufacturerId($sourceTable, $brand, $idShop = 0)
    {
        $brand = trim((string)$brand);
        if ($brand === '') {
            return 0;
        }

        $sourceTable = trim((string)$sourceTable);
        if ($sourceTable === '') {
            $sourceTable = 'unknown';
        }

        // Preferujemy mapowanie producentów (per hurtownia), żeby unikać duplikatów w PrestaShop.
        if (class_exists('AzadaManufacturerImportMatcher')) {
            $mappedId = (int) AzadaManufacturerImportMatcher::resolveManufacturerId($sourceTable, $brand, (int) $idShop);
            if ($mappedId > 0) {
                return $mappedId;
            }
        }

        $brand = self::truncateString($brand, 64);

        try {
            $id = (int)Db::getInstance()->getValue(
                "SELECT id_manufacturer FROM `" . bqSQL(_DB_PREFIX_ . 'manufacturer') . "` WHERE name='" . pSQL($brand) . "'"
            );
        } catch (Exception $e) {
            $id = 0;
        }

        if ($id > 0) {
            return $id;
        }

        if (!class_exists('Manufacturer')) {
            return 0;
        }

        $manufacturer = new Manufacturer();
        $manufacturer->name = $brand;
        $manufacturer->active = 1;

        if (!$manufacturer->add()) {
            return 0;
        }

        // MultiShop: powiąż z bieżącym sklepem
        $idShop = (int)$idShop;
        if ($idShop <= 0 && isset(Context::getContext()->shop) && isset(Context::getContext()->shop->id)) {
            $idShop = (int)Context::getContext()->shop->id;
        }

        if ($idShop > 0 && method_exists($manufacturer, 'associateTo')) {
            try {
                $manufacturer->associateTo($idShop);
            } catch (Exception $e) {
                // ignore
            }
        }

        return (int)$manufacturer->id;
    }

    /**
     * Ustawia dostawcę w product/product_shop + wpis do product_supplier.
     */
    private static function ensureProductSupplierLink($idProduct, $idSupplier, $supplierReference = '', $supplierPriceNet = 0.0, $idShop = 0)
    {
        $idProduct = (int)$idProduct;
        $idSupplier = (int)$idSupplier;

        if ($idProduct <= 0 || $idSupplier <= 0) {
            return;
        }

        $idShop = (int)$idShop;
        if ($idShop <= 0 && isset(Context::getContext()->shop) && isset(Context::getContext()->shop->id)) {
            $idShop = (int)Context::getContext()->shop->id;
        }

        try {
            Db::getInstance()->update('product', ['id_supplier' => (int)$idSupplier], 'id_product=' . (int)$idProduct);
        } catch (Exception $e) {
            // ignore
        }

        try {
            Db::getInstance()->update('product_shop', ['id_supplier' => (int)$idSupplier], 'id_product=' . (int)$idProduct . ' AND id_shop=' . (int)$idShop);
        } catch (Exception $e) {
            // ignore
        }

        $idCurrency = (int)Configuration::get('PS_CURRENCY_DEFAULT');
        if ($idCurrency <= 0 && isset(Context::getContext()->currency) && isset(Context::getContext()->currency->id)) {
            $idCurrency = (int)Context::getContext()->currency->id;
        }
        if ($idCurrency <= 0) {
            $idCurrency = 1;
        }

        $ref = trim((string)$supplierReference);
        if ($ref !== '') {
            $ref = self::truncateString($ref, 64);
        }

        $supplierPriceNet = (float)$supplierPriceNet;
        if ($supplierPriceNet < 0) {
            $supplierPriceNet = 0.0;
        }

        $tableProductSupplier = _DB_PREFIX_ . 'product_supplier';
        $existingId = 0;

        try {
            $rows = Db::getInstance()->executeS(
                'SELECT id_product_supplier FROM `' . bqSQL($tableProductSupplier) . '` '
                . 'WHERE id_product=' . (int)$idProduct . ' AND id_product_attribute=0 AND id_supplier=' . (int)$idSupplier . ' '
                . 'ORDER BY id_product_supplier ASC '
                . 'LIMIT 1'
            );
            if (is_array($rows) && !empty($rows) && isset($rows[0]['id_product_supplier'])) {
                $existingId = (int)$rows[0]['id_product_supplier'];
            }
        } catch (Exception $e) {
            $existingId = 0;
        }

        $payload = [
            'id_product' => (int)$idProduct,
            'id_product_attribute' => 0,
            'id_supplier' => (int)$idSupplier,
            'product_supplier_reference' => $ref,
            'product_supplier_price_te' => (float)$supplierPriceNet,
            'id_currency' => (int)$idCurrency,
        ];

        try {
            if ($existingId > 0) {
                Db::getInstance()->update('product_supplier', $payload, 'id_product_supplier=' . (int)$existingId);
            } else {
                Db::getInstance()->insert('product_supplier', $payload, true);
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    private static function normalizeEan($ean)
    {
        $ean = trim((string)$ean);
        if ($ean === '') {
            return '';
        }

        $ean = preg_replace('/\D+/', '', $ean);
        if ($ean === null) {
            return '';
        }

        $ean = trim($ean);
        if ($ean === '') {
            return '';
        }

        if (strlen($ean) !== 13) {
            return '';
        }

        if (class_exists('Validate') && !Validate::isEan13($ean)) {
            return '';
        }

        return $ean;
    }

    private static function normalizeReference($ref)
    {
        $ref = trim((string)$ref);
        if ($ref === '') {
            return '';
        }

        $ref = self::truncateString($ref, 32);

        if (class_exists('Validate') && !Validate::isReference($ref)) {
            $ref = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', $ref);
            if ($ref === null) {
                return '';
            }
            $ref = self::truncateString($ref, 32);
        }

        return trim((string)$ref);
    }

    private static function truncateString($value, $max)
    {
        $value = (string)$value;
        $max = (int)$max;
        if ($max <= 0) {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($value, 'UTF-8') > $max) {
                return mb_substr($value, 0, $max, 'UTF-8');
            }
            return $value;
        }

        if (strlen($value) > $max) {
            return substr($value, 0, $max);
        }

        return $value;
    }

    private static function normalizeDescriptionHtml($raw)
    {
        $raw = (string)$raw;
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $decoded = html_entity_decode($raw, ENT_QUOTES, 'UTF-8');
        $decoded = trim((string)$decoded);

        $html = $decoded;
        if (strpos($decoded, '<') === false) {
            $safe = htmlspecialchars($decoded, ENT_QUOTES, 'UTF-8');
            $safe = nl2br($safe);
            $html = '<p>' . $safe . '</p>';
        }

        if (class_exists('Tools') && method_exists('Tools', 'purifyHTML')) {
            $html = Tools::purifyHTML($html);
        }

        return trim((string)$html);
    }

    private static function buildShortDescription($html, $maxChars = 400)
    {
        $maxChars = (int)$maxChars;
        if ($maxChars <= 0) {
            $maxChars = 400;
        }

        $text = strip_tags((string)$html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        if ($text === null) {
            $text = '';
        }
        $text = trim((string)$text);

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') > $maxChars) {
                $text = rtrim(mb_substr($text, 0, $maxChars, 'UTF-8')) . '...';
            }
        } else {
            if (strlen($text) > $maxChars) {
                $text = rtrim(substr($text, 0, $maxChars)) . '...';
            }
        }

        return '<p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    /**
     * Szuka istniejącego produktu w PrestaShop po SKU (reference) lub EAN.
     * Używane jako bezpiecznik, żeby cron CREATE nie robił dubli, gdy inny moduł już utworzył produkt.
     */
    private static function findExistingProductId($ean, $sku)
    {
        $ean = trim((string)$ean);
        $sku = trim((string)$sku);

        $productTable = _DB_PREFIX_ . 'product';
        $db = Db::getInstance();

        if ($sku !== '') {
            $skuNorm = self::normalizeReference($sku);
            $check = ($skuNorm !== '' ? $skuNorm : $sku);
            try {
                $id = (int)$db->getValue(
                    "SELECT MIN(id_product) FROM `" . bqSQL($productTable) . "` WHERE TRIM(IFNULL(reference,'')) <> '' AND reference='" . pSQL($check) . "'"
                );
            } catch (Exception $e) {
                $id = 0;
            }
            if ($id > 0) {
                return $id;
            }
        }

        if ($ean !== '') {
            $eanNorm = self::normalizeEan($ean);
            $check = ($eanNorm !== '' ? $eanNorm : $ean);
            try {
                $id = (int)$db->getValue(
                    "SELECT MIN(id_product) FROM `" . bqSQL($productTable) . "` WHERE TRIM(IFNULL(ean13,'')) <> '' AND ean13='" . pSQL($check) . "'"
                );
            } catch (Exception $e) {
                $id = 0;
            }
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    /**
     * Generuje unikalny reference produktu (max 32 znaki) – jeśli base istnieje, dopina _2/_3...
     */
    private static function generateUniqueProductReference($baseReference)
    {
        $baseReference = trim((string)$baseReference);
        if ($baseReference === '') {
            return '';
        }

        $baseReference = self::normalizeReference($baseReference);
        if ($baseReference === '') {
            $baseReference = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', (string)$baseReference);
            if ($baseReference === null) {
                return '';
            }
            $baseReference = self::truncateString($baseReference, 32);
        }

        if ($baseReference === '') {
            return '';
        }

        $productTable = bqSQL(_DB_PREFIX_ . 'product');
        $db = Db::getInstance();

        try {
            $exists = (int)$db->getValue(
                "SELECT 1 FROM `{$productTable}` WHERE TRIM(IFNULL(reference,'')) <> '' AND reference='" . pSQL($baseReference) . "'"
            );
        } catch (Exception $e) {
            $exists = 0;
        }

        if ($exists <= 0) {
            return $baseReference;
        }

        for ($i = 2; $i <= 99; $i++) {
            $suffix = '_' . (string)$i;
            $maxBase = 32 - strlen($suffix);
            if ($maxBase < 1) {
                $maxBase = 1;
            }

            $candidateBase = self::truncateString($baseReference, $maxBase);
            $candidate = $candidateBase . $suffix;

            if (class_exists('Validate') && !Validate::isReference($candidate)) {
                $candidate = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', (string)$candidate);
                if ($candidate === null) {
                    continue;
                }
                $candidate = self::truncateString($candidate, 32);
            }

            try {
                $exists = (int)$db->getValue(
                    "SELECT 1 FROM `{$productTable}` WHERE TRIM(IFNULL(reference,'')) <> '' AND reference='" . pSQL($candidate) . "'"
                );
            } catch (Exception $e) {
                $exists = 0;
            }

            if ($exists <= 0) {
                return $candidate;
            }
        }

        $tail = '_' . substr((string)time(), -4);
        $maxBase = 32 - strlen($tail);
        if ($maxBase < 1) {
            $maxBase = 1;
        }
        return self::truncateString($baseReference, $maxBase) . $tail;
    }

    /**
     * Dodaje zdjęcia produktu na podstawie pól RAW.
     * Wykorzystuje klasy Image/ImageManager z PrestaShop.
     */
    private static function addProductImagesFromRaw($idProduct, array $raw, $productName = '', $idShop = 0)
    {
        $idProduct = (int)$idProduct;
        if ($idProduct <= 0) {
            return;
        }

        $idShop = (int)$idShop;
        if ($idShop <= 0 && isset(Context::getContext()->shop) && isset(Context::getContext()->shop->id)) {
            $idShop = (int)Context::getContext()->shop->id;
        }

        if (!class_exists('Image') || !class_exists('ImageManager')) {
            return;
        }

        // Tak samo jak w "Dodaj ręcznie": zbieramy zdjęcia z kilku pól RAW
        $keys = ['zdjecieglownelinkurl', 'zdjecie1linkurl', 'zdjecie2linkurl', 'zdjecie3linkurl'];
        $urls = [];

        foreach ($keys as $k) {
            if (!isset($raw[$k])) {
                continue;
            }
            $val = trim((string)$raw[$k]);
            if ($val === '') {
                continue;
            }
            $url = self::normalizeImageUrl($val);
            if ($url === '') {
                continue;
            }
            $urls[] = $url;
        }

        // Dodatkowe zdjęcia (czasem w 1 polu, oddzielone ; lub ,)
        $extra = isset($raw['zdjeciadodatkowezalaczniki']) ? trim((string)$raw['zdjeciadodatkowezalaczniki']) : '';
        if ($extra !== '') {
            $parts = preg_split('/[;,\s]+/', $extra);
            if (is_array($parts)) {
                foreach ($parts as $p) {
                    $p = trim((string)$p);
                    if ($p === '') {
                        continue;
                    }
                    $url = self::normalizeImageUrl($p);
                    if ($url === '') {
                        continue;
                    }
                    $urls[] = $url;
                }
            }
        }

        // Usuń duplikaty, zachowując kolejność
        $unique = [];
        $seen = [];
        foreach ($urls as $u) {
            if (isset($seen[$u])) {
                continue;
            }
            $seen[$u] = true;
            $unique[] = $u;
        }
        $urls = $unique;

        if (empty($urls)) {
            return;
        }

        $productName = trim((string)$productName);
        if ($productName === '') {
            $productName = 'Product image';
        }

        $languages = Language::getLanguages(false);

        foreach ($urls as $i => $url) {
            $image = new Image();
            $image->id_product = (int)$idProduct;
            $image->position = Image::getHighestPosition((int)$idProduct) + 1;
            $image->cover = ($i === 0) ? 1 : 0;

            // Legenda (multi-lang)
            foreach ((array)$languages as $lang) {
                $idLang = isset($lang['id_lang']) ? (int)$lang['id_lang'] : 0;
                if ($idLang <= 0) {
                    continue;
                }
                $legend = $productName;
                if ($i > 0) {
                    $legend .= ' #' . ($i + 1);
                }
                $image->legend[$idLang] = self::truncateString($legend, 128);
            }

            if (!$image->add()) {
                continue;
            }

            // MultiShop: skojarz obraz z aktualnym sklepem (żeby był widoczny)
            if ($idShop > 0) {
                try {
                    Db::getInstance()->insert('image_shop', [
                        'id_image' => (int)$image->id,
                        'id_shop' => (int)$idShop,
                        'cover' => (int)$image->cover,
                    ], true);
                } catch (Exception $e) {
                    // ignore
                }
            }

            $tmp = self::downloadRemoteFileToTmp($url);
            if ($tmp === '') {
                $image->delete();
                continue;
            }

            $ok = true;

            // Szybka walidacja: czy to faktycznie obraz
            $imgInfo = @getimagesize($tmp);
            if (!$imgInfo) {
                $ok = false;
            }

            if ($ok) {
                $path = $image->getPathForCreation();

                // Oryginał
                if (!ImageManager::resize($tmp, $path . '.jpg')) {
                    $ok = false;
                } else {
                    // Miniatury wg ImageType
                    if (class_exists('ImageType') && method_exists('ImageType', 'getImagesTypes')) {
                        $types = ImageType::getImagesTypes('products');
                        if (is_array($types)) {
                            foreach ($types as $type) {
                                if (!isset($type['name'], $type['width'], $type['height'])) {
                                    continue;
                                }
                                ImageManager::resize(
                                    $tmp,
                                    $path . '-' . $type['name'] . '.jpg',
                                    (int)$type['width'],
                                    (int)$type['height']
                                );
                            }
                        }
                    }

                    // Watermark (jeśli włączony)
                    if (class_exists('Hook') && method_exists('Hook', 'exec')) {
                        Hook::exec('actionWatermark', [
                            'id_image' => (int)$image->id,
                            'id_product' => (int)$idProduct,
                        ]);
                    }
                }
            }

            @unlink($tmp);

            if (!$ok) {
                $image->delete();
                continue;
            }
        }
    }

    private static function normalizeImageUrl($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }

        // Czasem w RAW jest "//domain/path"
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        // Podstawowa walidacja protokołu
        if (!preg_match('#^https?://#i', $url)) {
            return '';
        }

        // Prosty fix na spacje w URL
        $url = str_replace(' ', '%20', $url);

        return $url;
    }

    private static function downloadRemoteFileToTmp($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return '';
        }

        $tmp = tempnam(_PS_TMP_IMG_DIR_, 'azada_');
        if ($tmp === false) {
            return '';
        }

        // Upewnij się, że jest jpg
        $tmpJpg = $tmp . '.jpg';
        @unlink($tmp);

        $data = false;
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 20,
                    'follow_location' => 1,
                    'user_agent' => 'AzadaWholesalerPro/1.0',
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);

            $data = Tools::file_get_contents($url, false, $context);
        } catch (Exception $e) {
            $data = false;
        }

        if ($data === false || $data === '') {
            return '';
        }

        if (@file_put_contents($tmpJpg, $data) === false) {
            return '';
        }

        return $tmpJpg;
    }
/**
     * Pobierz aktywne hurtownie do importu.
     */
    private static function getActiveWholesalersForImport()
    {
        $sql = 'SELECT id_wholesaler, name, raw_table_name FROM `' . bqSQL(_DB_PREFIX_ . 'azada_wholesaler_pro_integration') . '` WHERE active=1';
        $onlyId = (int)Tools::getValue('id_wholesaler', 0);
        if ($onlyId > 0) {
            $sql .= ' AND id_wholesaler=' . (int)$onlyId;
        }
        $sql .= ' ORDER BY id_wholesaler ASC';

        try {
            $rows = Db::getInstance()->executeS($sql);
        } catch (Exception $e) {
            $rows = [];
        }

        // Tylko takie, które mają raw_table_name – integracje bazują na tym polu.
        $out = [];
        foreach ((array)$rows as $r) {
            $raw = isset($r['raw_table_name']) ? trim((string)$r['raw_table_name']) : '';
            if ($raw === '') {
                continue;
            }
            $out[] = $r;
        }
        return $out;
    }

    /**
     * Pobiera wpisy z tabeli pochodzenia produktów (produkty stworzone/obsługiwane przez moduł).
     */
    private static function getOriginRows()
    {
        $table = _DB_PREFIX_ . 'azada_wholesaler_pro_product_origin';
        $productTable = _DB_PREFIX_ . 'product';
        $onlyModule = (int)Tools::getValue('only_module', 1) === 1;
        $onlySourceTable = trim((string)Tools::getValue('source_table', ''));

        // Origin może zawierać wpisy po produktach skasowanych w PrestaShop.
        // Żeby cron nie próbował aktualizować nieistniejących ID (i nie tworzył "ghost" rekordów stock_available),
        // od razu filtrujemy je przez join do ps_product.
        $sql = 'SELECT o.id_product, o.source_table, o.ean13, o.reference, o.created_by_module
                FROM `' . bqSQL($table) . '` o
                INNER JOIN `' . bqSQL($productTable) . '` p ON p.id_product = o.id_product';

        $where = [];
        if ($onlyModule) {
            $where[] = 'o.created_by_module=1';
        }

        if ($onlySourceTable !== '') {
            $where[] = "o.source_table='" . pSQL($onlySourceTable) . "'";
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY id_product ASC';

        try {
            $rows = Db::getInstance()->executeS($sql);
        } catch (Exception $e) {
            $rows = [];
        }

        return is_array($rows) ? $rows : [];
    }

    /**
     * Pobierz rekord z RAW po EAN/SKU.
     *
     * @param string $sourceTable np. 'azada_raw_abro'
     * @param string $ean
     * @param string $sku
     * @param array $columns lista kolumn do pobrania; jeśli pusta pobiera '*'
     */
    private static function fetchRawRow($sourceTable, $ean, $sku, array $columns = [])
    {
        $sourceTable = trim((string)$sourceTable);
        $ean = trim((string)$ean);
        $sku = trim((string)$sku);

        if ($sourceTable === '') {
            return null;
        }

        $full = bqSQL(_DB_PREFIX_ . $sourceTable);

        $select = '*';
        if (!empty($columns)) {
            $safeCols = [];
            foreach ($columns as $c) {
                $c = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$c);
                if ($c !== '') {
                    $safeCols[] = '`' . bqSQL($c) . '`';
                }
            }
            if (!empty($safeCols)) {
                $select = implode(',', $safeCols);
            }
        }

        $db = Db::getInstance();

        // 1) Match po SKU
        if ($sku !== '') {
            try {
                $res = $db->executeS(
                    "SELECT {$select} FROM `{$full}` WHERE `produkt_id`='" . pSQL($sku) . "' LIMIT 1"
                );
                if (is_array($res) && !empty($res) && isset($res[0])) {
                    return $res[0];
                }
            } catch (Exception $e) {}
        }

        // 2) Match po EAN
        if ($ean !== '') {
            try {
                $res = $db->executeS(
                    "SELECT {$select} FROM `{$full}` WHERE `kod_kreskowy`='" . pSQL($ean) . "' LIMIT 1"
                );
                if (is_array($res) && !empty($res) && isset($res[0])) {
                    return $res[0];
                }
            } catch (Exception $e) {}
        }

        return null;
    }

    private static function applyQtyToProduct($idProduct, $qty, $idShop)
    {
        $qty = (int)$qty;
        if ($qty < 0) {
            $qty = 0;
        }

        if (class_exists('StockAvailable')) {
            StockAvailable::setQuantity((int)$idProduct, 0, (int)$qty, (int)$idShop);
        }
    }

    private static function applyMinimalQtyToProduct($idProduct, $minQty)
    {
        $minQty = (int)$minQty;
        if ($minQty < 1) {
            $minQty = 1;
        }

        try {
            $product = new Product((int)$idProduct);
            if (!Validate::isLoadedObject($product)) {
                return;
            }
            $product->minimal_quantity = (int)$minQty;
            $product->update();
        } catch (Exception $e) {}
    }

    private static function applyStockZeroAction($idProduct, $qty, $zeroAction)
    {
        $qty = (int)$qty;
        $zeroAction = (int)$zeroAction;

        try {
            $product = new Product((int)$idProduct);
            if (!Validate::isLoadedObject($product)) {
                return;
            }

            if ($qty <= 0) {
                if ($zeroAction === 0) {
                    $product->active = 0;
                } elseif ($zeroAction === 2) {
                    // "Na zamówienie" – ustawiamy dostępność później.
                    $product->active = 1;
                    $langs = Language::getLanguages(false);
                    foreach ($langs as $lang) {
                        $product->available_later[(int)$lang['id_lang']] = 'Na zamówienie';
                    }
                } else {
                    // zeroAction=1: zostaw aktywny
                    $product->active = 1;
                }
            } else {
                // Wraca na stan – włączamy ponownie (typowe oczekiwanie).
                $product->active = 1;

                if ($zeroAction === 2) {
                    // Czyścimy "Na zamówienie", jeśli było ustawione.
                    $langs = Language::getLanguages(false);
                    foreach ($langs as $lang) {
                        $product->available_later[(int)$lang['id_lang']] = '';
                    }
                }
            }

            $product->update();
        } catch (Exception $e) {}
    }

    private static function computeMinimalQuantity(array $raw)
    {
        $rawRequiredOz = isset($raw['wymagane_oz']) ? (string)$raw['wymagane_oz'] : '';
        $rawPackQty = isset($raw['ilosc_w_opakowaniu']) ? (string)$raw['ilosc_w_opakowaniu'] : '';
        $rawUnit = isset($raw['jednostkapodstawowa']) ? (string)$raw['jednostkapodstawowa'] : '';

        $pack = self::parseFloat($rawPackQty);
        $isPackUnit = self::isPackUnit($rawUnit);

        $min = 1;
        if (self::isTrueLike($rawRequiredOz) && $pack > 0) {
            $min = $isPackUnit ? 1 : (int)ceil($pack);
        }
        if ($min < 1) {
            $min = 1;
        }
        return (int)$min;
    }

    private static function isTrueLike($value)
    {
        $v = trim((string)$value);
        if ($v === '') {
            return false;
        }

        $v = Tools::strtolower($v);
        $v = str_replace(' ', '', $v);

        return in_array($v, ['1','true','tak','yes','min','min(1)','min(100)'], true) || (strpos($v, 'min') === 0);
    }

    private static function isPackUnit($unit)
    {
        $u = Tools::strtolower(trim((string)$unit));
        if ($u === '') {
            return false;
        }

        // Najczęściej spotykane: opak / opak. / op / pak / paczka / zestaw
        return (strpos($u, 'opak') !== false)
            || (strpos($u, 'pak') !== false)
            || (strpos($u, 'pacz') !== false)
            || (strpos($u, 'zest') !== false);
    }

    /**
     * Oblicz cenę sprzedaży netto:
     * - jeśli w mapowaniu kategorii ustawiono narzut (category_markup_percent != 0) => użyj tylko jego (ignoruj mnożnik i narzut hurtowni),
     * - w przeciwnym razie => użyj mnożnika + narzutu hurtowni.
     */
    private static function computeSalePriceNet($purchaseNet, $sourceTable, $rawCategory)
    {
        $purchaseNet = (float)$purchaseNet;
        if ($purchaseNet < 0) {
            $purchaseNet = 0.0;
        }

        $pricing = self::getWholesalerPricingSettings($sourceTable);
        $priceMultiplier = isset($pricing['price_multiplier']) ? (float)$pricing['price_multiplier'] : 1.0;
        if ($priceMultiplier <= 0) {
            $priceMultiplier = 1.0;
        }
        $globalMarkup = isset($pricing['price_markup_percent']) ? (float)$pricing['price_markup_percent'] : 0.0;

        $categoryMarkup = 0.0;
        if (class_exists('AzadaCategoryImportMatcher')) {
            $match = AzadaCategoryImportMatcher::match($sourceTable, (string)$rawCategory);
            if (is_array($match) && isset($match['category_markup_percent'])) {
                $categoryMarkup = (float)$match['category_markup_percent'];
            }
        }

        if ($categoryMarkup != 0.0) {
            // Override: tylko narzut kategorii, bez mnożnika hurtowni i bez globalnego narzutu.
            $priceMultiplier = 1.0;
            $markup = $categoryMarkup;
        } else {
            $markup = $globalMarkup;
        }

        $base = $purchaseNet * $priceMultiplier;
        return self::applyMarkupToPrice($base, $markup);
    }

    private static function applyMarkupToPrice($price, $markupPercent)
    {
        $price = (float)$price;
        $markupPercent = (float)$markupPercent;

        if ($markupPercent == 0.0) {
            return $price;
        }

        return $price * (1.0 + ($markupPercent / 100.0));
    }

    private static function applyPriceRounding($priceNet, $roundingMode)
    {
        $priceNet = (float)$priceNet;
        $roundingMode = (int)$roundingMode;

        if ($priceNet <= 0) {
            return $priceNet;
        }

        if ($roundingMode === 1) {
            // Do .99 (np. 12.99)
            $zl = floor($priceNet);
            $res = $zl + 0.99;
            if ($res < $priceNet) {
                $res = $zl + 1.99;
            }
            return round($res, 2);
        }

        if ($roundingMode === 2) {
            // Do pełnych złotych
            return round(ceil($priceNet), 2);
        }

        // Brak
        return $priceNet;
    }

    private static function getWholesalerPricingSettings($sourceTable)
    {
        $sourceTable = trim((string)$sourceTable);
        static $cache = [];

        if (isset($cache[$sourceTable])) {
            return $cache[$sourceTable];
        }

        $cache[$sourceTable] = [
            'price_multiplier' => 1.0,
            'price_markup_percent' => 0.0,
        ];

        if ($sourceTable === '') {
            return $cache[$sourceTable];
        }

        $tableIntegration = _DB_PREFIX_ . 'azada_wholesaler_pro_integration';
        $tableHub = _DB_PREFIX_ . 'azada_wholesaler_pro_hub_settings';

        try {
            $rows = Db::getInstance()->executeS(
                'SELECT hs.price_multiplier, hs.price_markup_percent '
                . 'FROM `' . bqSQL($tableIntegration) . '` w '
                . 'LEFT JOIN `' . bqSQL($tableHub) . '` hs ON (hs.id_wholesaler = w.id_wholesaler) '
                . 'WHERE w.raw_table_name=\'' . pSQL($sourceTable) . '\' '
                . 'LIMIT 1'
            );
        } catch (Exception $e) {
            $rows = [];
        }

        if (is_array($rows) && !empty($rows) && isset($rows[0]) && is_array($rows[0])) {
            if (isset($rows[0]['price_multiplier'])) {
                $cache[$sourceTable]['price_multiplier'] = (float)$rows[0]['price_multiplier'];
            }
            if (isset($rows[0]['price_markup_percent'])) {
                $cache[$sourceTable]['price_markup_percent'] = (float)$rows[0]['price_markup_percent'];
            }
        }

        return $cache[$sourceTable];
    }

    /**
     * Znajdź id_tax_rules_group po stawce VAT.
     * Uwaga: to jest best-effort – jeśli nie znajdziemy, zostawiamy obecne.
     */

    private static function resolveTaxRulesGroupIdByRate($vatRate)
    {
        $vatRate = (float)$vatRate;
        if ($vatRate < 0) {
            $vatRate = 0.0;
        }

        // Presta trzyma stawki w ps_tax.rate (np. 23.000).
        // Uwaga: Db::getValue() może dopinać LIMIT 1 automatycznie – nie dodajemy LIMIT w SQL.
        $sql = 'SELECT MIN(trg.id_tax_rules_group)
                FROM `' . bqSQL(_DB_PREFIX_ . 'tax') . '` t
                INNER JOIN `' . bqSQL(_DB_PREFIX_ . 'tax_rule') . '` tr ON (tr.id_tax = t.id_tax)
                INNER JOIN `' . bqSQL(_DB_PREFIX_ . 'tax_rules_group') . '` trg ON (trg.id_tax_rules_group = tr.id_tax_rules_group)
                WHERE ABS(t.rate - ' . (float)$vatRate . ') < 0.0001';

        try {
            $id = (int)Db::getInstance()->getValue($sql);
            return $id > 0 ? $id : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    private static function parseInt($value)
    {
        if (is_numeric($value)) {
            return (int)$value;
        }

        $v = str_replace([' ', ','], ['', '.'], (string)$value);
        if ($v === '') {
            return 0;
        }

        return (int)floor((float)$v);
    }

    private static function parseFloat($value)
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        $v = str_replace([' ', ','], ['', '.'], (string)$value);
        if ($v === '') {
            return 0.0;
        }

        return (float)$v;
    }
}
