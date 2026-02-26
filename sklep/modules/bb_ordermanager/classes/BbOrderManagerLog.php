<?php
/**
 * ObjectModel dla tabeli bb_ordermanager_logs
 *
 * Używany głównie przez kontroler BO (LOGI) do podglądu wpisów.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class BbOrderManagerLog extends ObjectModel
{
    /** @var int */
    public $id_log;

    /** @var int */
    public $id_order;

    /** @var int */
    public $id_employee;

    /** @var string */
    public $employee_name;

    /** @var string */
    public $action;

    /** @var string */
    public $details;

    /** @var string */
    public $message;

    /** @var string */
    public $date_add;

    public static $definition = [
        'table' => 'bb_ordermanager_logs',
        'primary' => 'id_log',
        'multilang' => false,
        'fields' => [
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'employee_name' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255],
            'action' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
            'details' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml'],
            'message' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'required' => true],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}
