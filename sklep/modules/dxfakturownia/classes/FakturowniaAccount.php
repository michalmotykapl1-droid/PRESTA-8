<?php

class FakturowniaAccount extends ObjectModel
{
    public $id_dxfakturownia_account;
    public $name;
    public $api_token;
    public $domain;
    public $connection_status; 
    public $last_error;        
    public $is_default;
    public $active;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'dxfakturownia_accounts',
        'primary' => 'id_dxfakturownia_account',
        'fields' => [
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
            'api_token' => ['type' => self::TYPE_STRING, 'required' => true],
            'domain' => ['type' => self::TYPE_STRING, 'required' => true],
            'connection_status' => ['type' => self::TYPE_BOOL],
            'last_error' => ['type' => self::TYPE_STRING],
            'is_default' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool'],
            'date_add' => ['type' => self::TYPE_DATE],
            'date_upd' => ['type' => self::TYPE_DATE],
        ],
    ];

    public static function getDefaultAccount()
    {
        $sql = new DbQuery();
        $sql->select('id_dxfakturownia_account');
        $sql->from('dxfakturownia_accounts');
        $sql->where('active = 1 AND is_default = 1');
        
        $id = Db::getInstance()->getValue($sql);
        
        return $id ? new FakturowniaAccount($id) : false;
    }
}