<?php

/**
 * Mlab Price History Module
 *
 * @author   Mlabfactory
 * @license  Proprietary
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mlab_Price_History extends Module
{
    public function __construct()
    {
        $this->name = 'mlab_price_history';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'mlabfactory';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Mlab Price History');
        $this->description = $this->l('Tracks price changes when promotions are activated and maintains lowest price in last 30 days.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->installDb()) {
            return false;
        }

        if (!$this->registerHook('actionProductUpdate') ||
            !$this->registerHook('actionObjectSpecificPriceAddAfter') ||
            !$this->registerHook('actionObjectSpecificPriceUpdateAfter') ||
            !$this->registerHook('actionObjectSpecificPriceDeleteAfter') ||
            !$this->registerHook('displayProductLowestPrice')) { // Nome personalizzato
            return false;
        }

        // Installa il tab solo se non esiste già
        if (!$this->tabExists('AdminMlabPriceHistory')) {
            if (!$this->installTab()) {
                return false;
            }
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        if (!$this->uninstallTab()) {
            return false;
        }

        if (!$this->uninstallDb()) {
            return false;
        }

        return true;
    }

    /**
     * Check if tab exists
     */
    private function tabExists($className)
    {
        $sql = 'SELECT id_tab FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "' . pSQL($className) . '"';
        return (bool)Db::getInstance()->getValue($sql);
    }

    /**
     * Install admin tab
     */
    private function installTab()
    {
        try {
            $tab = new Tab();
            $tab->active = 1;
            $tab->class_name = 'AdminMlabPriceHistory';
            $tab->name = [];
            
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = 'Storico Prezzi';
            }

            // Trova l'ID del parent tab in modo sicuro
            $parentId = $this->getParentTabId();
            if (!$parentId) {
                return false;
            }
            
            $tab->id_parent = $parentId;
            $tab->module = $this->name;
            
            return $tab->add();
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error installing tab: ' . $e->getMessage(), 3, null, 'Module', $this->name);
            return false;
        }
    }

    /**
     * Get parent tab ID safely
     */
    private function getParentTabId()
    {
        // Prova prima con query diretta
        $sql = 'SELECT id_tab FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "AdminCatalog"';
        $parentId = Db::getInstance()->getValue($sql);
        
        if ($parentId) {
            return (int)$parentId;
        }

        // Fallback con il metodo statico se la query fallisce
        if (method_exists('Tab', 'getIdFromClassName')) {
            $parentId = Tab::getIdFromClassName('AdminCatalog');
            if ($parentId) {
                return (int)$parentId;
            }
        }

        // Se tutto fallisce, usa l'ID di AdminParentCatalog
        $sql = 'SELECT id_tab FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "AdminParentCatalog"';
        $parentId = Db::getInstance()->getValue($sql);
        
        return $parentId ? (int)$parentId : 0;
    }

    /**
     * Uninstall admin tab
     */
    private function uninstallTab()
    {
        try {
            $sql = 'SELECT id_tab FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = "AdminMlabPriceHistory"';
            $idTab = Db::getInstance()->getValue($sql);

            if ($idTab) {
                $tab = new Tab($idTab);
                return $tab->delete();
            }
            
            return true;
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error uninstalling tab: ' . $e->getMessage(), 3, null, 'Module', $this->name);
            return true; // Non bloccare la disinstallazione per errori di tab
        }
    }

    /**
     * Create database tables
     */
    private function installDb()
    {
        $sql = [];

        // Table for price history
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'price_history` (
            `id_price_history` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT(11) UNSIGNED NOT NULL,
            `id_product_attribute` INT(11) UNSIGNED DEFAULT 0,
            `id_shop` INT(11) UNSIGNED DEFAULT 1,
            `old_price` DECIMAL(20,6) NOT NULL,
            `new_price` DECIMAL(20,6) NOT NULL,
            `price_type` VARCHAR(50) NOT NULL COMMENT "regular, sale, specific_price",
            `reduction_type` VARCHAR(50) DEFAULT NULL COMMENT "amount, percentage",
            `reduction_value` DECIMAL(20,6) DEFAULT NULL,
            `date_add` DATETIME NOT NULL,
            PRIMARY KEY (`id_price_history`),
            KEY `id_product` (`id_product`),
            KEY `id_product_attribute` (`id_product_attribute`),
            KEY `date_add` (`date_add`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        // Table for lowest price in last 30 days
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'lowest_price_30d` (
            `id_lowest_price` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT(11) UNSIGNED NOT NULL,
            `id_product_attribute` INT(11) UNSIGNED DEFAULT 0,
            `id_shop` INT(11) UNSIGNED DEFAULT 1,
            `lowest_price` DECIMAL(20,6) NOT NULL,
            `lowest_price_date` DATETIME NOT NULL,
            `current_price` DECIMAL(20,6) NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_lowest_price`),
            UNIQUE KEY `product_shop` (`id_product`, `id_product_attribute`, `id_shop`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Drop database tables
     */
    private function uninstallDb()
    {
        $sql = [];
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'price_history`';
        $sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'lowest_price_30d`';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Hook when product is updated
     */
    public function hookActionProductUpdate($params)
    {
        if (!isset($params['product'])) {
            return;
        }

        $product = $params['product'];
        if (!Validate::isLoadedObject($product)) {
            return;
        }

        $this->trackPriceChange($product);
    }

    /**
     * Hook when specific price is added
     */
    public function hookActionObjectSpecificPriceAddAfter($params)
    {
        if (!isset($params['object'])) {
            return;
        }

        $specificPrice = $params['object'];
        $this->trackSpecificPriceChange($specificPrice, 'add');
    }

    /**
     * Hook when specific price is updated
     */
    public function hookActionObjectSpecificPriceUpdateAfter($params)
    {
        if (!isset($params['object'])) {
            return;
        }

        $specificPrice = $params['object'];
        $this->trackSpecificPriceChange($specificPrice, 'update');
    }

    /**
     * Hook when specific price is deleted
     */
    public function hookActionObjectSpecificPriceDeleteAfter($params)
    {
        if (!isset($params['object'])) {
            return;
        }

        $specificPrice = $params['object'];
        $this->trackSpecificPriceChange($specificPrice, 'delete');
    }

    /**
     * Track price changes for a product
     */
    private function trackPriceChange($product)
    {
        $idProduct = (int)$product->id;
        $idShop = (int)Context::getContext()->shop->id;
        
        // Get current price
        $currentPrice = Product::getPriceStatic($idProduct, true);
        
        // Get last recorded price from history
        $lastPrice = $this->getLastRecordedPrice($idProduct, 0, $idShop);
        
        // If price has changed, record it
        if ($lastPrice === false || abs($lastPrice - $currentPrice) > 0.001) {
            $this->addPriceHistory(
                $idProduct,
                0,
                $idShop,
                $lastPrice !== false ? $lastPrice : $currentPrice,
                $currentPrice,
                'regular'
            );
            
            $this->updateLowestPrice30d($idProduct, 0, $idShop, $currentPrice);
        }
    }

    /**
     * Track specific price changes (promotions)
     */
    private function trackSpecificPriceChange($specificPrice, $action = 'add')
    {
        $idProduct = (int)$specificPrice->id_product;
        $idProductAttribute = (int)$specificPrice->id_product_attribute;
        $idShop = (int)$specificPrice->id_shop;
        
        if ($idShop == 0) {
            $idShop = (int)Context::getContext()->shop->id;
        }

        // Get current price with specific price applied
        $currentPrice = Product::getPriceStatic(
            $idProduct,
            true,
            $idProductAttribute ? $idProductAttribute : null
        );

        // Get last recorded price
        $lastPrice = $this->getLastRecordedPrice($idProduct, $idProductAttribute, $idShop);
        
        if ($lastPrice === false) {
            // If no previous price, get the base price
            $product = new Product($idProduct, false, Context::getContext()->language->id);
            $lastPrice = (float)$product->price;
        }

        // Determine reduction type and value
        $reductionType = null;
        $reductionValue = null;
        
        if ($action != 'delete') {
            if ($specificPrice->reduction_type == 'amount') {
                $reductionType = 'amount';
                $reductionValue = (float)$specificPrice->reduction;
            } elseif ($specificPrice->reduction_type == 'percentage') {
                $reductionType = 'percentage';
                $reductionValue = (float)$specificPrice->reduction * 100;
            }
        }

        // Add to history
        $this->addPriceHistory(
            $idProduct,
            $idProductAttribute,
            $idShop,
            $lastPrice,
            $currentPrice,
            'specific_price',
            $reductionType,
            $reductionValue
        );

        // Update lowest price in last 30 days
        $this->updateLowestPrice30d($idProduct, $idProductAttribute, $idShop, $currentPrice);
    }

    /**
     * Add entry to price history
     */
    private function addPriceHistory(
        $idProduct,
        $idProductAttribute,
        $idShop,
        $oldPrice,
        $newPrice,
        $priceType = 'regular',
        $reductionType = null,
        $reductionValue = null
    ) {
        $data = [
            'id_product' => (int)$idProduct,
            'id_product_attribute' => (int)$idProductAttribute,
            'id_shop' => (int)$idShop,
            'old_price' => (float)$oldPrice,
            'new_price' => (float)$newPrice,
            'price_type' => pSQL($priceType),
            'reduction_type' => $reductionType ? pSQL($reductionType) : null,
            'reduction_value' => $reductionValue ? (float)$reductionValue : null,
            'date_add' => date('Y-m-d H:i:s'),
        ];

        return Db::getInstance()->insert('price_history', $data);
    }

    /**
     * Update lowest price in last 30 days
     */
    private function updateLowestPrice30d($idProduct, $idProductAttribute, $idShop, $currentPrice)
    {
        // Get existing record
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'lowest_price_30d`
                WHERE `id_product` = ' . (int)$idProduct . '
                AND `id_product_attribute` = ' . (int)$idProductAttribute . '
                AND `id_shop` = ' . (int)$idShop;
        
        $existingRecord = Db::getInstance()->getRow($sql);
        
        // Get lowest price from history in last 30 days
        $lowestPriceData = $this->getLowestPriceFromHistory($idProduct, $idProductAttribute, $idShop);
        
        if ($existingRecord) {
            // Update existing record
            $updateData = [
                'lowest_price' => (float)$lowestPriceData['price'],
                'lowest_price_date' => pSQL($lowestPriceData['date']),
                'current_price' => (float)$currentPrice,
                'date_upd' => date('Y-m-d H:i:s'),
            ];
            
            return Db::getInstance()->update(
                'lowest_price_30d',
                $updateData,
                '`id_product` = ' . (int)$idProduct . '
                AND `id_product_attribute` = ' . (int)$idProductAttribute . '
                AND `id_shop` = ' . (int)$idShop
            );
        } else {
            // Insert new record
            $insertData = [
                'id_product' => (int)$idProduct,
                'id_product_attribute' => (int)$idProductAttribute,
                'id_shop' => (int)$idShop,
                'lowest_price' => (float)$lowestPriceData['price'],
                'lowest_price_date' => pSQL($lowestPriceData['date']),
                'current_price' => (float)$currentPrice,
                'date_upd' => date('Y-m-d H:i:s'),
            ];
            
            return Db::getInstance()->insert('lowest_price_30d', $insertData);
        }
    }

    /**
     * Get lowest price from history in last 30 days
     */
    private function getLowestPriceFromHistory($idProduct, $idProductAttribute, $idShop)
    {
        $sql = 'SELECT `new_price` as price, `date_add` as date
                FROM `' . _DB_PREFIX_ . 'price_history`
                WHERE `id_product` = ' . (int)$idProduct . '
                AND `id_product_attribute` = ' . (int)$idProductAttribute . '
                AND `id_shop` = ' . (int)$idShop . '
                AND `date_add` >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY `new_price` ASC, `date_add` ASC';
        
        $result = Db::getInstance()->getRow($sql);
        
        if ($result) {
            return $result;
        }
        
        // If no history in last 30 days, use current price
        $currentPrice = Product::getPriceStatic(
            $idProduct,
            true,
            $idProductAttribute ? $idProductAttribute : null
        );
        
        return [
            'price' => $currentPrice,
            'date' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get last recorded price from history
     */
    private function getLastRecordedPrice($idProduct, $idProductAttribute, $idShop)
    {
        $sql = 'SELECT `new_price`
                FROM `' . _DB_PREFIX_ . 'price_history`
                WHERE `id_product` = ' . (int)$idProduct . '
                AND `id_product_attribute` = ' . (int)$idProductAttribute . '
                AND `id_shop` = ' . (int)$idShop . '
                ORDER BY `date_add` DESC';
        
        $result = Db::getInstance()->getValue($sql);
        
        return $result !== false ? (float)$result : false;
    }

    /**
     * Get price history for a product
     */
    public function getPriceHistory($idProduct, $idProductAttribute = 0, $limit = 50)
    {
        $idShop = (int)Context::getContext()->shop->id;
        
        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'price_history`
                WHERE `id_product` = ' . (int)$idProduct . '
                AND `id_product_attribute` = ' . (int)$idProductAttribute . '
                AND `id_shop` = ' . $idShop . '
                ORDER BY `date_add` DESC
                LIMIT ' . (int)$limit;
        
        return Db::getInstance()->executeS($sql);
    }

    /**
     * Get lowest price in last 30 days for a product
     */
    public function getLowestPrice30d($idProduct, $idProductAttribute = 0)
    {
        $idShop = (int)Context::getContext()->shop->id;
        
        $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'lowest_price_30d`
                WHERE `id_product` = ' . (int)$idProduct . '
                AND `id_product_attribute` = ' . (int)$idProductAttribute . '
                AND `id_shop` = ' . $idShop;

        $result = Db::getInstance()->getRow($sql);

        // if no result check only with id product
        if (!$result) {
            $sql = 'SELECT *
                FROM `' . _DB_PREFIX_ . 'lowest_price_30d`
                WHERE `id_product` = ' . (int)$idProduct . '
                AND `id_shop` = ' . $idShop;
            $result = Db::getInstance()->getRow($sql);
        }

        // if a lower price is 0 return false
        if ($result && (float)$result['lowest_price'] > 0) {
            return $result;
        }

        return false;
    }
    
    /**
     * Hook to display lowest price on product page
     */
    public function hookDisplayProductLowestPrice($params)
    {
        $product = $params['product'];
        if (!isset($product['id_product'])) {
            return;
        }

        $idProduct = (int)$product['id_product'];
        $idProductAttribute = isset($product['id_product_attribute']) ? (int)$product['id_product_attribute'] : 0;

        $lowestPriceData = $this->getLowestPrice30d($idProduct, $idProductAttribute);

        if (!$lowestPriceData) {
            return;
        }

        $this->context->smarty->assign([
            'lowest_price_data' => $lowestPriceData,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/lowest_price_display.tpl');
    }
}
