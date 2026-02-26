<?php
/**
 * Simple gated logger for tvcmssearch.
 */
if (!defined('_PS_VERSION_')) { exit; }

class TvcmsSearchLogger
{
    const LOG_FILE = 'debug.log';
    const LOG_PATH = _PS_MODULE_DIR_ . 'tvcmssearch/';

    protected static function enabled()
    {
        return (int)\Configuration::get('TVCMSSEARCH_DEBUG_LOG') === 1;
    }

    protected static function path()
    {
        return self::LOG_PATH . self::LOG_FILE;
    }

    public static function log($message, $level = 'DEBUG', array $context = [])
    {
        if (!self::enabled()) {
            return;
        }
        $line = '['.date('Y-m-d H:i:s').'] ['.$level.'] '.$message;
        if (!empty($context)) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        }
        $line .= PHP_EOL;
        @file_put_contents(self::path(), $line, FILE_APPEND);
    }

    public static function debug($message, array $context = []) { self::log($message, 'DEBUG', $context); }
    public static function info($message, array $context = [])  { self::log($message, 'INFO', $context); }
    public static function warning($message, array $context = []) { self::log($message, 'WARNING', $context); }
    public static function error($message, array $context = []) { self::log($message, 'ERROR', $context); }

    public static function clearLog()
    {
        @file_put_contents(self::path(), '');
    }
}
