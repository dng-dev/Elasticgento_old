<?php

/**
 * Elasticgento CatalogSearch fulltext indexer model replacement
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Model_Indexer_Catalog_Search extends Mage_CatalogSearch_Model_Indexer_Fulltext
{
    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('elasticgento')->__('Rebuild Catalog product fulltext search index (indexing done within Elasticgento)');
    }

    /**
     * make indexer not usable because indexing is done withing catalog_product_flat
     *
     * @param Mage_Index_Model_Event $event
     * @return bool
     */
    public function matchEvent(Mage_Index_Model_Event $event)
    {
        return false;
    }

    /**
     * Rebuild all index data
     *
     */
    public function reindexAll()
    {
    }
}