<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class MlabPriceHistory extends ObjectModel
{
    public $id_price_history;
    public $id_product;
    public $id_product_attribute;
    public $id_shop;
    public $old_price;
    public $new_price;
    public $price_type;
    public $reduction_type;
    public $reduction_value;
    public $date_add;

    public static $definition = [
        'table' => 'price_history',
        'primary' => 'id_price_history',
        'fields' => [
            'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
            'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
            'old_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'new_price' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPrice'],
            'price_type' => ['type' => self::TYPE_STRING, 'size' => 20],
            'reduction_type' => ['type' => self::TYPE_STRING, 'size' => 10],
            'reduction_value' => ['type' => self::TYPE_FLOAT],
            'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'],
        ],
    ];
}