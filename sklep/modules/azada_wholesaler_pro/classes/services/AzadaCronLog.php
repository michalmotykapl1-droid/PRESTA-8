<?php

/**
 * AzadaCronLog
 *
 * Wrapper do logowania wykonań CRON do wspólnej tabeli modułu:
 *   ps_azada_wholesaler_pro_logs
 *
 * Dzięki temu wszystkie logi (CRON + B2B + inne) są w jednym miejscu.
 */
class AzadaCronLog
{
    /**
     * Źródło logów CRON w tabeli azada_wholesaler_pro_logs.
     */
    const SOURCE = 'CRON';

    /**
     * Maksymalna liczba ostatnich logów CRON trzymana w tabeli (tylko source=CRON).
     */
    const KEEP_LAST = 500;

    /** @var string */
    private static $currentTask = '';

    /** @var string|null */
    private static $currentSourceTable = null;

    /** @var float */
    private static $startTs = 0.0;

    /** @var int */
    private static $obLevel = 0;

    /** @var bool */
    private static $finalized = false;

    /** @var array */
    private static $params = [];

    /**
     * Zapewnia istnienie tabeli logów modułu.
     */
    public static function ensureTable()
    {
        // Nie polegamy na autoloaderze w CRON
        if (!class_exists('AzadaLogger')) {
            $file = dirname(__FILE__) . '/AzadaLogger.php';
            if (file_exists($file)) {
                require_once($file);
            }
        }

        if (class_exists('AzadaLogger') && method_exists('AzadaLogger', 'ensureTable')) {
            AzadaLogger::ensureTable();
        }

        return true;
    }

    /**
     * Uruchamia task z logowaniem (bez logowania błędnych tokenów).
     *
     * @param string        $task
     * @param callable      $fn
     * @param string|null   $sourceTable
     */
    public static function run($task, $fn, $sourceTable = null)
    {
        // Najpierw token (jeśli zły, AzadaCronRunner::assertToken() zrobi exit() i nie zaśmiecamy logów).
        if (class_exists('AzadaCronRunner') && method_exists('AzadaCronRunner', 'assertToken')) {
            AzadaCronRunner::assertToken();
        }

        self::begin($task, $sourceTable);

        // Finalizacja niezależnie od exit()/fatal.
        register_shutdown_function([__CLASS__, 'shutdownHandler']);

        try {
            call_user_func($fn);
        } catch (Throwable $e) {
            // Złapane wyjątki (CronRunner zwykle nie rzuca, ale lepiej mieć).
            echo "EXCEPTION: " . $e->getMessage() . "\n";
        }

        // Jeśli nie było exit() – finalizujemy już teraz.
        self::shutdownHandler();
    }

    /**
     * Start logowania.
     */
    private static function begin($task, $sourceTable = null)
    {
        self::ensureTable();

        self::$currentTask = (string)$task;
        self::$currentSourceTable = $sourceTable ? (string)$sourceTable : null;
        self::$startTs = microtime(true);
        self::$finalized = false;

        // Parametry (bez tokena)
        $params = $_GET;
        if (isset($params['token'])) {
            unset($params['token']);
        }
        self::$params = is_array($params) ? $params : [];

        // Zaczynamy buforowanie outputu (żeby wrzucić do loga).
        self::$obLevel = (int)ob_get_level();
        ob_start();
    }

    /**
     * Finalizacja loga (wywoływana na shutdown).
     */
    public static function shutdownHandler()
    {
        if (self::$finalized) {
            return;
        }
        self::$finalized = true;

        // Jeśli begin() nie było wywołane – nic nie logujemy.
        if (self::$currentTask === '') {
            return;
        }

        $output = '';
        if (ob_get_level() > self::$obLevel) {
            $output = (string)ob_get_contents();
            @ob_end_flush();
        }

        // Fatal error (jeśli wystąpił)
        $err = error_get_last();
        if ($err && isset($err['type']) && in_array((int)$err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $output .= "\nFATAL: " . (isset($err['message']) ? $err['message'] : '') . " @ " . (isset($err['file']) ? $err['file'] : '') . ":" . (isset($err['line']) ? $err['line'] : '') . "\n";
        }

        // Analiza wyniku
        $parsed = self::parseSummaryFromOutput($output);
        $ok = (int)$parsed['ok'];
        $errCount = (int)$parsed['err'];
        $skip = (int)$parsed['skip'];
        $status = (string)$parsed['status'];
        $message = (string)$parsed['message'];

        $durationMs = (int)round((microtime(true) - (float)self::$startTs) * 1000);
        $memoryPeak = (int)memory_get_peak_usage(true);

        // Trunc output (żeby nie pompować bazy na giga)
        $output = self::truncate($output, 200000);

        $durationSec = $durationMs > 0 ? ($durationMs / 1000) : 0;
        $durationStr = $durationSec > 0 ? number_format($durationSec, 2, '.', '') . 's' : '-';

        $taskLabel = self::humanizeTask(self::$currentTask);

        $title = 'CRON: ' . $taskLabel . ' | OK: ' . (int)$ok . ' ERR: ' . (int)$errCount . ' SKIP: ' . (int)$skip . ' | ' . $durationStr;
        if (self::$currentSourceTable) {
            $title .= ' | ' . self::$currentSourceTable;
        }
        $title = self::truncate($title, 255);

        $detailsLines = [];
        $detailsLines[] = 'TASK: ' . (string)self::$currentTask;
        $detailsLines[] = 'STATUS: ' . $status;
        $detailsLines[] = 'OK: ' . (int)$ok . ' | ERR: ' . (int)$errCount . ' | SKIP: ' . (int)$skip;
        if (self::$currentSourceTable) {
            $detailsLines[] = 'SOURCE_TABLE: ' . self::$currentSourceTable;
        }
        if (!empty(self::$params)) {
            $json = json_encode(self::$params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json !== false && $json !== '[]') {
                $detailsLines[] = 'PARAMS: ' . $json;
            }
        }
        if ($message !== '') {
            $detailsLines[] = 'MESSAGE: ' . self::truncate($message, 500);
        }
        $detailsLines[] = 'DURATION_MS: ' . (int)$durationMs;
        $detailsLines[] = 'MEMORY_PEAK: ' . (int)$memoryPeak;
        $detailsLines[] = '';
        $detailsLines[] = '---------------- OUTPUT ----------------';
        $detailsLines[] = (string)$output;

        $details = implode("\n", $detailsLines);

        // severity
        $severity = 1; // info
        if ($errCount > 0) {
            $severity = defined('AzadaLogger::SEVERITY_ERROR') ? AzadaLogger::SEVERITY_ERROR : 3;
        } elseif ($ok > 0) {
            $severity = defined('AzadaLogger::SEVERITY_SUCCESS') ? AzadaLogger::SEVERITY_SUCCESS : 2;
        }

        if (class_exists('AzadaLogger') && method_exists('AzadaLogger', 'addLog')) {
            AzadaLogger::addLog(self::SOURCE, $title, $details, $severity);
        }

        // Utrzymujemy logi CRON w ryzach (nie ruszamy innych logów)
        self::purgeOld(self::KEEP_LAST);

        // Reset
        self::$currentTask = '';
        self::$currentSourceTable = null;
        self::$startTs = 0.0;
        self::$obLevel = 0;
        self::$params = [];
    }

    /**
     * Parsuje OK/ERR/SKIP z outputu.
     *
     * Oczekiwany format (np. footer CronRunner):
     *   OK: 10 | ERR: 0 | SKIP: 2
     */
    private static function parseSummaryFromOutput($output)
    {
        $ok = 0;
        $err = 0;
        $skip = 0;
        $status = 'OK';
        $message = '';

        if (preg_match('/OK\s*:\s*(\d+)\s*\|\s*ERR\s*:\s*(\d+)\s*\|\s*SKIP\s*:\s*(\d+)/i', $output, $m)) {
            $ok = (int)$m[1];
            $err = (int)$m[2];
            $skip = (int)$m[3];
        } else {
            // Fallback: zlicz wystąpienia OK:/ERR:/SKIP:
            if (preg_match_all('/^OK\s*:/mi', $output, $mm)) {
                $ok = max($ok, count($mm[0]));
            }
            if (preg_match_all('/^(ERR|ERROR|EXCEPTION|FATAL)\s*:/mi', $output, $mm2)) {
                $err = max($err, count($mm2[0]));
            }
            if (preg_match_all('/^SKIP\s*:/mi', $output, $mm3)) {
                $skip = max($skip, count($mm3[0]));
            }

            if (preg_match('/^(ERR|ERROR|EXCEPTION|FATAL):([^\n]*)/mi', $output, $m2)) {
                $err = max($err, 1);
                if ($message === '') {
                    $message = trim($m2[2]);
                }
            }
        }

        if ($message === '') {
            // Spróbuj złapać pierwszą sensowną linię informacyjną
            if (preg_match('/^(INFO|WARN|SKIP|ERR|ERROR):\s*([^\n]+)/mi', $output, $m3)) {
                $message = trim($m3[2]);
            }
        }

        if ($err > 0) {
            $status = 'ERR';
        } elseif ($ok > 0) {
            $status = 'OK';
        } elseif ($skip > 0) {
            $status = 'SKIP';
        } else {
            // Nic nie wyszło: jeśli output zawiera "SKIP" – traktuj jako SKIP, w przeciwnym razie OK.
            if (stripos($output, 'SKIP') !== false) {
                $status = 'SKIP';
                $skip = max($skip, 1);
            } elseif (stripos($output, 'ERR') !== false || stripos($output, 'ERROR') !== false || stripos($output, 'FATAL') !== false) {
                $status = 'ERR';
                $err = max($err, 1);
            } else {
                $status = 'OK';
            }
        }

        return [
            'ok' => $ok,
            'err' => $err,
            'skip' => $skip,
            'status' => $status,
            'message' => $message,
        ];
    }

    /**
     * Pobiera ostatnie logi CRON (source=CRON).
     */
    public static function getLast($limit = 50)
    {
        self::ensureTable();

        $limit = (int)$limit;
        if ($limit < 1) {
            $limit = 50;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_logs` WHERE `source` = \'CRON\' ORDER BY `id_log` DESC LIMIT ' . (int)$limit
        );
    }

    /**
     * Czyści wszystkie logi CRON.
     */
    public static function clearAll()
    {
        self::ensureTable();
        return Db::getInstance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_logs` WHERE `source` = \'CRON\'');
    }

    /**
     * Usuwa stare logi CRON, zostawiając ostatnie $keep.
     */
    public static function purgeOld($keep = 500)
    {
        self::ensureTable();

        $keep = (int)$keep;
        if ($keep < 50) {
            $keep = 50;
        }

        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_logs`
                WHERE `source` = \'CRON\'
                  AND `id_log` NOT IN (
                    SELECT `id_log` FROM (
                        SELECT `id_log`
                        FROM `' . _DB_PREFIX_ . 'azada_wholesaler_pro_logs`
                        WHERE `source` = \'CRON\'
                        ORDER BY `id_log` DESC
                        LIMIT ' . (int)$keep . '
                    ) t
                )';

        return Db::getInstance()->execute($sql);
    }

    private static function truncate($str, $max = 200000)
    {
        $str = (string)$str;
        $max = (int)$max;
        if ($max <= 0) {
            return '';
        }
        if (Tools::strlen($str) <= $max) {
            return $str;
        }

        // Trzymamy końcówkę (najczęściej tam jest stopka z podsumowaniem)
        return Tools::substr($str, Tools::strlen($str) - $max, $max);
    }

    private static function humanizeTask($task)
    {
        $t = (string)$task;
        $t = str_replace(['_', '-'], ' ', $t);
        $t = trim($t);
        if ($t === '') {
            return 'CRON';
        }
        return $t;
    }
}
