<?php

class AzadaAnalysis extends ObjectModel
{
    public $id_analysis;
    public $id_wholesaler;
    public $id_invoice_file;
    public $id_order_file;
    public $doc_number_invoice;
    public $doc_number_order;
    public $status;          
    public $total_diff_net;  
    public $items_match_count;
    public $items_error_count;
    public $date_analyzed;

    public static $definition = [
        'table' => 'azada_wholesaler_pro_analysis',
        'primary' => 'id_analysis',
        'fields' => [
            'id_wholesaler'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'id_invoice_file'    => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'id_order_file'      => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'allow_null' => true],
            'doc_number_invoice' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 100],
            // LIMIT 65000 DLA TEXT
            'doc_number_order'   => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 65000, 'allow_null' => true],
            'status'             => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 20],
            // WALIDACJA isFloat (akceptuje ujemne)
            'total_diff_net'     => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'],
            'items_match_count'  => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'items_error_count'  => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'date_analyzed'      => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}