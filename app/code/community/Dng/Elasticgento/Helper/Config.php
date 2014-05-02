<?php

/**
 * Elasticgento module config helper
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Helper_Config extends Mage_Core_Helper_Abstract
{

    /**
     * get chunksize for bulk operations
     *
     * @return int
     */
    public function getChunkSize()
    {
        return (int)Mage::getStoreConfig('elasticgento/general/chunksize');
    }

    /**
     * get number of shards
     *
     * @return int
     */
    public function getNumberOfShards()
    {
        return (int)Mage::getStoreConfig('elasticgento/general/number_of_shards');
    }

    /**
     * get number of replicas
     *
     * @return int
     */
    public function getNumberOfReplicas()
    {
        return (int)Mage::getStoreConfig('elasticgento/general/number_of_replicas');
    }

    /**
     * get maximum number of items in facets
     *
     * @return int
     */
    public function getMaxFacetsSize()
    {
        return (int)Mage::getStoreConfig('elasticgento/general/facets_max_size');
    }

    /**
     * get maximum number of items in facets
     *
     * @return int
     */
    public function getIcuFoldingEnabled()
    {
        return Mage::getStoreConfigFlag('elasticgento/general/enable_icu_folding');
    }
}