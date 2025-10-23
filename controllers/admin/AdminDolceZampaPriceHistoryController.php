<?php
/**
 * Admin Controller for Mlab Price History
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminMlabPriceHistoryController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'price_history';
        $this->identifier = 'id_price_history';
        $this->className = 'MlabPriceHistory';
        $this->lang = false;
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();

        $this->_select = '
            pl.name as product_name,
            IF(a.id_product_attribute > 0, 
                CONCAT(pl.name, " - ", GROUP_CONCAT(DISTINCT agl.name, ": ", al.name SEPARATOR ", ")),
                pl.name
            ) as full_product_name
        ';

        $this->_join = '
            LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl 
                ON (a.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . ' AND pl.id_shop = a.id_shop)
            LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac 
                ON (a.id_product_attribute = pac.id_product_attribute)
            LEFT JOIN ' . _DB_PREFIX_ . 'attribute a_attr 
                ON (pac.id_attribute = a_attr.id_attribute)
            LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al 
                ON (a_attr.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id . ')
            LEFT JOIN ' . _DB_PREFIX_ . 'attribute_group_lang agl 
                ON (a_attr.id_attribute_group = agl.id_attribute_group AND agl.id_lang = ' . (int)$this->context->language->id . ')
        ';

        $this->_group = 'GROUP BY a.id_price_history';

        $this->fields_list = [
            'id_price_history' => [
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'full_product_name' => [
                'title' => 'Prodotto',
                'havingFilter' => true,
            ],
            'old_price' => [
                'title' => 'Prezzo Precedente',
                'align' => 'right',
                'type' => 'price',
                'currency' => true,
            ],
            'new_price' => [
                'title' => 'Nuovo Prezzo',
                'align' => 'right',
                'type' => 'price',
                'currency' => true,
            ],
            'price_type' => [
                'title' => 'Tipo',
                'align' => 'center',
            ],
            'reduction_type' => [
                'title' => 'Tipo Riduzione',
                'align' => 'center',
            ],
            'reduction_value' => [
                'title' => 'Valore Riduzione',
                'align' => 'right',
            ],
            'date_add' => [
                'title' => 'Data',
                'align' => 'right',
                'type' => 'datetime',
            ],
        ];

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        
        $this->page_header_toolbar_btn['view_lowest_prices'] = [
            'href' => self::$currentIndex . '&viewLowestPrices&token=' . $this->token,
            'desc' => 'Vedi Prezzi Minimi 30 Giorni',
            'icon' => 'process-icon-stats',
        ];
    }

    public function renderView()
    {
        if (Tools::getValue('viewLowestPrices')) {
            return $this->renderLowestPricesList();
        }
        
        return parent::renderView();
    }

    protected function renderLowestPricesList()
    {
        $this->table = 'lowest_price_30d';
        $this->identifier = 'id_lowest_price';
        $this->className = 'MlabLowestPrice30d';
        
        $this->_select = '
            pl.name as product_name,
            IF(a.id_product_attribute > 0, 
                CONCAT(pl.name, " - ", GROUP_CONCAT(DISTINCT agl.name, ": ", al.name SEPARATOR ", ")),
                pl.name
            ) as full_product_name
        ';

        $this->_join = '
            LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl 
                ON (a.id_product = pl.id_product AND pl.id_lang = ' . (int)$this->context->language->id . ' AND pl.id_shop = a.id_shop)
            LEFT JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac 
                ON (a.id_product_attribute = pac.id_product_attribute)
            LEFT JOIN ' . _DB_PREFIX_ . 'attribute a_attr 
                ON (pac.id_attribute = a_attr.id_attribute)
            LEFT JOIN ' . _DB_PREFIX_ . 'attribute_lang al 
                ON (a_attr.id_attribute = al.id_attribute AND al.id_lang = ' . (int)$this->context->language->id . ')
            LEFT JOIN ' . _DB_PREFIX_ . 'attribute_group_lang agl 
                ON (a_attr.id_attribute_group = agl.id_attribute_group AND agl.id_lang = ' . (int)$this->context->language->id . ')
        ';

        $this->_group = 'GROUP BY a.id_lowest_price';

        $this->fields_list = [
            'id_lowest_price' => [
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'full_product_name' => [
                'title' => 'Prodotto',
                'havingFilter' => true,
            ],
            'lowest_price' => [
                'title' => 'Prezzo Minimo',
                'align' => 'right',
                'type' => 'price',
                'currency' => true,
            ],
            'lowest_price_date' => [
                'title' => 'Data Prezzo Minimo',
                'align' => 'right',
                'type' => 'datetime',
            ],
            'current_price' => [
                'title' => 'Prezzo Corrente',
                'align' => 'right',
                'type' => 'price',
                'currency' => true,
            ],
            'date_upd' => [
                'title' => 'Ultimo Aggiornamento',
                'align' => 'right',
                'type' => 'datetime',
            ],
        ];

        return parent::renderList();
    }
}
