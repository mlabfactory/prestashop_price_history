<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class MlabLowestPrice30d extends ObjectModel
{
    public $id_lowest_price;
    public $id_product;
    public $id_product_attribute;
    public $id_shop;
    public $lowest_price;
    public $lowest_price_date;
    public $current_price;
    public $date_add;
    public $date_upd;

    public static $definition = [
        'table' => 'lowest_price_30d',
        'primary' => 'id_lowest_price',
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'lowest_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'lowest_price_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'current_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
            'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}