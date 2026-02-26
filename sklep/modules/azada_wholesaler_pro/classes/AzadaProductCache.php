<?php

class AzadaProductCache extends ObjectModel
{
    public $id_product_cache;
    public $id_wholesaler;
    public $reference; // Kod produktu (Indeks)
    public $ean13;
    public $name;
    public $price_tax_excl; // Cena zakupu netto
    public $quantity;
    public $date_upd;

    public static $definition = [
        'table' => 'azada_wholesaler_pro_cache',
        'primary' => 'id_product_cache',
        'fields' => [
            'id_wholesaler' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
            'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 64],
            'ean13' => ['type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13],
            'name' => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'size' => 255],
            'price_tax_excl' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'quantity' => ['type' => self::TYPE_INT, 'validate' => 'isInt'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}