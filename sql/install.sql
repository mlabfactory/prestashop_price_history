-- Mlab Price History Module
-- SQL Installation Script

-- Table for price change history
CREATE TABLE IF NOT EXISTS `PREFIX_dolcezampa_price_history` (
    `id_price_history` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_product` INT(11) UNSIGNED NOT NULL,
    `id_product_attribute` INT(11) UNSIGNED DEFAULT 0,
    `id_shop` INT(11) UNSIGNED DEFAULT 1,
    `old_price` DECIMAL(20,6) NOT NULL,
    `new_price` DECIMAL(20,6) NOT NULL,
    `price_type` VARCHAR(50) NOT NULL COMMENT 'regular, sale, specific_price',
    `reduction_type` VARCHAR(50) DEFAULT NULL COMMENT 'amount, percentage',
    `reduction_value` DECIMAL(20,6) DEFAULT NULL,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_price_history`),
    KEY `id_product` (`id_product`),
    KEY `id_product_attribute` (`id_product_attribute`),
    KEY `date_add` (`date_add`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;

-- Table for lowest price in last 30 days
CREATE TABLE IF NOT EXISTS `PREFIX_dolcezampa_lowest_price_30d` (
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
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=utf8;
