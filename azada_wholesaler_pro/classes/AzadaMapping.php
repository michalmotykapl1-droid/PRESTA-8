<?php

class AzadaMapping extends ObjectModel
{
    public $id_mapping;
    public $id_wholesaler;
    public $csv_column;
    public $ps_target;
    public $logic_type;
    public $logic_value;
    public $is_identifier;

    public static $definition = [
        // Zaktualizowana nazwa tabeli
        'table' => 'azada_wholesaler_pro_mapping',
        'primary' => 'id_mapping',
        'fields' => [
            'id_wholesaler' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'csv_column' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'ps_target' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true],
            'logic_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'logic_value' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
            'is_identifier' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
        ],
    ];
}