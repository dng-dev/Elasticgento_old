<?php

/**
 * Catalog Product Elasticgento Indexer Resource Model
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Model_Resource_Catalog_Product_Indexer_Elasticgento extends Mage_Index_Model_Resource_Abstract
{

    /**
     * Eav Catalog_Product Entity Type Id
     *
     * @var int
     */
    protected $_entityTypeId;

    /**
     * Elasticgento client instance
     *
     * @var Dng_Elasticgento_Model_Resource_Client
     */
    protected $_client = null;

    /**
     * Flat tables which were prepared
     *
     * @var array
     */
    protected $_preparedIndexes = array();

    /**
     * Initialize connection
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/product', 'entity_id');
    }

    /**
     * Retrieve entity type
     *
     * @return string
     */
    public function getEntityType()
    {
        return Mage_Catalog_Model_Product::ENTITY;
    }

    /**
     * Retrieve Catalog Entity Type Id
     *
     * @return int
     */
    public function getEntityTypeId()
    {
        if ($this->_entityTypeId === null) {
            $this->_entityTypeId = Mage::getResourceModel('catalog/config')
                ->getEntityTypeId();
        }
        return $this->_entityTypeId;
    }

    /**
     * get elasticsearch client instance
     *
     * @return Dng_Elasticgento_Model_Resource_Client
     */
    protected function _getClient()
    {
        if (null === $this->_client) {
            $this->_client = Mage::getResourceSingleton('elasticgento/client');
        }
        return $this->_client;
    }

    /**
     * prepare elasticsearch index for store
     *
     * @param integer $storeId
     * @return Dng_Elasticgento_Model_Resource_Catalog_Product_Indexer_Elasticgento
     * @todo implement alias handling for non blocking reindex
     */
    protected function _prepareIndex($storeId)
    {
        if (true === isset($this->_preparedIndexes[$storeId])) {
            return $this;
        }
        //handle index creation / deletition
        $idx = $this->_getClient()->getIndex($storeId);
        $settings = Mage::getModel('elasticgento/catalog_product_elasticgento_settings')->setStoreId($storeId)->getIndexSettings();
        if (false === $idx->exists()) {
            $idx->create($settings);
        } else {
            $idx->delete();
            $idx->create($settings);
        }
        //handle type
        //load settings
        $typeMappings = Mage::getModel('elasticgento/catalog_product_elasticgento_mappings')->setStoreId($storeId)->getMappings();
        $dynamicTemplates = Mage::getModel('elasticgento/catalog_product_elasticgento_mappings')->setStoreId($storeId)->getDynamicTemplates();
        $type = $this->_getClient()->getIndex($storeId)->getType($this->getEntityType());
        $elasticaMapping = new \Elastica\Type\Mapping($type);
        $elasticaMapping->setParam('_all', array('enabled' => false));
        #$elasticaMapping->setParam('dynamic', false);
        $elasticaMapping->setParam('dynamic_templates', $dynamicTemplates);
        $elasticaMapping->setProperties($typeMappings);
        $elasticaMapping->send();
        $this->_preparedIndexes[$storeId] = true;
        return $this;
    }

    /**
     * calculate the ranging steps for a total reindex for each store
     *
     * @param int $storeId
     * @return array
     *      - from
     *      - to
     */
    protected function _getIndexRangeChunks($storeId)
    {
        $chunksize = Mage::helper('elasticgento/config')->getChunkSize();
        $adapter = $this->_getReadAdapter();
        $websiteId = (int)Mage::app()->getStore($storeId)->getWebsite()->getId();
        $select = $adapter->select()
            ->from(array('e' => $this->getTable('catalog/product')),
                array('offsetStart' => new Zend_Db_Expr('min(e.entity_id)'), 'offsetEnd' => new Zend_Db_Expr('max(e.entity_id)')))
            ->join(
                array('wp' => $this->getTable('catalog/product_website')),
                'e.entity_id = wp.product_id AND wp.website_id = :website_id',
                array())
            ->limit(1);
        $range = $adapter->query($select, array('website_id' => $websiteId))->fetch();
        $offsetStart = (int)$range['offsetStart'];
        $offsetEnd = (int)$range['offsetEnd'];
        $total = $offsetEnd - $offsetStart;
        $chunksCount = ceil($total / $chunksize);
        $chunks = array();
        for ($i = 0; $i < $chunksCount; $i++) {
            $chunks[] = array('from' => $offsetStart + ($chunksize * $i), 'to' => $offsetStart + (($chunksize * $i) + $chunksize - 1));
        }
        return $chunks;
    }

    /**
     * Retrieve Catalog Product Flat helper
     *
     * @return Mage_Catalog_Helper_Product_Flat
     */
    public function getFlatHelper()
    {
        return Mage::helper('catalog/product_flat');
    }

    /**
     * rebuild elasticgento catalog product data
     *
     * @param Mage_Core_Model_Store|int $store
     * @return Dng_Elasticgento_Model_Resource_Catalog_Product_Indexer_Elasticgento
     */
    public function rebuild($store = null)
    {
        if ($store === null) {
            if (true === is_array($store)) {
                foreach (Mage::app()->getStores() as $store) {
                    $this->rebuild($store->getId());
                }
            }
            return $this;
        }
        //check store exists
        $storeId = (int)Mage::app()->getStore($store)->getId();
        //prepare index and mappings
        $this->_prepareIndex($storeId);
        //get reindex chunks on catalog_product primary key because in is faster then working with limits
        $chunks = $this->_getIndexRangeChunks($storeId);


        $flag = $this->getFlatHelper()->getFlag();
        $flag->setIsBuilt(true)->setStoreBuilt($storeId, true)->save();
        return $this;
    }

    /**
     * rebuild elasticgento catalog product data for all stores
     *
     * @return Mage_Catalog_Model_Resource_Product_Flat_Indexer
     */
    public function reindexAll()
    {
        foreach (Mage::app()->getStores() as $storeId => $store) {
            try {
                if (true === function_exists('xdebug_time_index')) {
                    $timeStart = xdebug_time_index();
                }
                $this->rebuild($store);
                if (true === function_exists('xdebug_time_index')) {
                    var_dump(xdebug_time_index() - $timeStart);
                }
            } catch (Exception $e) {
                throw $e;
            }
        }
        return $this;
    }
}
