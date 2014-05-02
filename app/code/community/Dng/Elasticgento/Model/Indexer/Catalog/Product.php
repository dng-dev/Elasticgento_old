<?php

/**
 * Elasticgento catalog product flat indexer replacement
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Model_Indexer_Catalog_Product extends Mage_Catalog_Model_Product_Indexer_Flat
{

    /**
     * Whether the indexer should be displayed on process/list page
     *
     * @return bool
     */
    public function isVisible()
    {
        return true;
    }

    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('elasticgento')->__('Reorganize EAV product structure to Elasticgento index');
    }


    /**
     * Retrieve Catalog Product Flat Indexer model
     *
     * @return Mage_Catalog_Model_Product_Flat_Indexer
     */
    #protected function _getIndexer()
    #{
    #    return Mage::getSingleton('elasticgento/catalog_product_elasticgento_indexer');
    #}
}
