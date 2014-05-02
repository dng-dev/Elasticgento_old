<?php

/**
 * Elasticgento setup
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */

/* @var $installer Mage_Catalog_Model_Resource_Setup */
$installer = $this;
$installer->startSetup();

try {
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog/eav_attribute'),
        'search_weight', "tinyint(1) unsigned NOT NULL DEFAULT '1' after `is_searchable`"
    );
    $installer->getConnection()->addColumn(
        $installer->getTable('catalog/eav_attribute'),
        'search_analyzer', "varchar(255)NOT NULL DEFAULT '' after `search_weight`"
    );
} catch (Exception $e) {
    // ignore
}

$installer->endSetup();
