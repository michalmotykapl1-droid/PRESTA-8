<?php
namespace AllegroPro\Model;

use ObjectModel;

class Order extends ObjectModel
{
    public $id_allegropro_order;
    public $id_allegropro_account;
    public $checkout_form_id;
    public $status;
    public $buyer_login;
    public $buyer_email;
    public $total_amount;
    public $currency;
    public $updated_at_allegro;
    public $date_add;
    public $date_upd;

    // Pola wirtualne (do wyświetlania w liście)
    public $account_label;
    public $shipping_method;

    public static $definition = [
        'table' => 'allegropro_order',
        'primary' => 'id_allegropro_order',
        'fields' => [
            'id_allegropro_account' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'checkout_form_id'      => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true],
            'status'                => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'buyer_login'           => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'],
            'buyer_email'           => ['type' => self::TYPE_STRING, 'validate' => 'isEmail'],
            'total_amount'          => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'currency'              => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 3],
            'updated_at_allegro'    => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_add'              => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd'              => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}