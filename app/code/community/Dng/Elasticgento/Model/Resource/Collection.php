<?php

/**
 * Elasticgento base collection
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
abstract class Dng_Elasticgento_Model_Resource_Collection extends Varien_Data_Collection
{
    const SORT_ORDER_ASC = 'ASC';
    const SORT_ORDER_DESC = 'DESC';

    /**
     * Current scope (store Id)
     *
     * @var int
     */
    protected $_storeId;

    /**
     * Collection constructor
     *
     * @param Mage_Core_Model_Resource_Abstract $resource
     */
    public function __construct($resource = null)
    {
        $this->_construct();
        $this->setConnection($this->getEntity()->getReadConnection());
    }

    /**
     * Set store scope
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    public function setStore($store)
    {
        $this->setStoreId(Mage::app()->getStore($store)->getId());
        return $this;
    }

    /**
     * Set store scope
     *
     * @param int|string|Mage_Core_Model_Store $storeId
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    public function setStoreId($storeId)
    {
        if ($storeId instanceof Mage_Core_Model_Store) {
            $storeId = $storeId->getId();
        }
        $this->_storeId = (int)$storeId;
        return $this;
    }

    /**
     * Init select
     * this function is overloaded because its done within elasticsearch
     *
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    protected function _initSelect()
    {
        return $this;
    }

    /**
     * Return current store id
     *
     * @return int
     */
    public function getStoreId()
    {
        if (true === is_null($this->_storeId)) {
            $this->setStoreId(Mage::app()->getStore()->getId());
        }
        return $this->_storeId;
    }

    /**
     * Hook for operations before rendering filters
     *
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    protected function _renderFiltersBefore()
    {
        return $this;
    }

    /**
     * Hook for operations after rendering filters
     *
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    protected function _renderFiltersAfter()
    {
        return $this;
    }

    /**
     * Hook for operations before rendering facets
     *
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    protected function _renderFacetsBefore()
    {
        return $this;
    }

    /**
     * Hook for operations after rendering facets
     *
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    protected function _renderFacetsAfter()
    {
        return $this;
    }

    /**
     * render collection filters
     *
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    protected function _renderFilters()
    {
        return $this;
    }

    /**
     * render collection sort
     *
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    protected function _renderOrders()
    {
        return $this;
    }

    /**
     * render collection facets
     *
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    protected function _renderFacets()
    {
        return $this;
    }

    /**
     * Load collection data into object items
     *
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    public function load($printQuery = false, $logQuery = false)
    {
        Varien_Profiler::start('__ELASTICGENTO_COLLECTION_BEFORE_LOAD__');
        Mage::dispatchEvent('elasticgento_collection_abstract_load_before', array('collection' => $this));
        $this->_beforeLoad();
        Varien_Profiler::stop('__ELASTICGENTO_COLLECTION_BEFORE_LOAD__');

        /**
         * render filters
         */
        Varien_Profiler::start('__ELASTICGENTO_RENDER_FILTERS__');
        $this->_renderFiltersBefore();
        $this->_renderFilters();
        $this->_renderFiltersAfter();
        Varien_Profiler::stop('__ELASTICGENTO_RENDER_FILTERS__');

        /**
         * render orders
         */
        Varien_Profiler::start('__ELASTICGENTO_RENDER_ORDERS__');
        $this->_renderOrders();
        Varien_Profiler::stop('__ELASTICGENTO_RENDER_ORDERS__');

        /**
         * render facets
         */
        Varien_Profiler::start('__ELASTICGENTO_RENDER_FACETS__');
        $this->_renderFacetsBefore();
        $this->_renderFacets();
        $this->_renderFacetsAfter();
        Varien_Profiler::stop('__ELASTICGENTO_RENDER_FACETS__');


        $this->_afterLoad();
        Mage::dispatchEvent('elasticgento_collection_abstract_load_after', array('collection' => $this));
        Varien_Profiler::stop('__EAV_COLLECTION_AFTER_LOAD__');
        return $this;
    }
}