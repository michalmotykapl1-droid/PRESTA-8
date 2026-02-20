<?php

class DxFakturowniaInvoice extends ObjectModel
{
    public $id_dxfakturownia_invoice;
    public $id_dxfakturownia_account;
    public $id_order;
    public $remote_id;
    public $parent_remote_id;
    public $kind;          
    public $number;
    public $buyer_name; // NOWE POLE
    public $sell_date;
    public $price_gross;
    public $status;
    public $view_url;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'dxfakturownia_invoices',
        'primary' => 'id_dxfakturownia_invoice',
        'fields' => [
            'id_dxfakturownia_account' => ['type' => self::TYPE_INT, 'required' => true],
            'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'remote_id' => ['type' => self::TYPE_INT, 'required' => true],
            'parent_remote_id' => ['type' => self::TYPE_INT],
            'kind' => ['type' => self::TYPE_STRING, 'required' => true],
            'number' => ['type' => self::TYPE_STRING, 'required' => true],
            'buyer_name' => ['type' => self::TYPE_STRING], // Definicja
            'sell_date' => ['type' => self::TYPE_DATE],
            'price_gross' => ['type' => self::TYPE_FLOAT],
            'status' => ['type' => self::TYPE_STRING],
            'view_url' => ['type' => self::TYPE_STRING],
            'date_add' => ['type' => self::TYPE_DATE],
            'date_upd' => ['type' => self::TYPE_DATE],
        ],
    ];
    
    public static function getByRemoteId($remote_id)
    {
        $query = new DbQuery();
        $query->select('id_dxfakturownia_invoice');
        $query->from('dxfakturownia_invoices');
        $query->where('`remote_id` = ' . (int)$remote_id);
        
        $id = Db::getInstance()->getValue($query);
        return $id ? new DxFakturowniaInvoice($id) : false;
    }

    public static function getCorrectionsForInvoice($parent_remote_id)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'dxfakturownia_invoices` 
                WHERE `parent_remote_id` = ' . (int)$parent_remote_id . ' 
                AND `kind` = \'correction\'';
        
        return Db::getInstance()->executeS($sql);
    }
}