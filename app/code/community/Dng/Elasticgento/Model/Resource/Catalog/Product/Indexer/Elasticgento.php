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
     * attributes array key is store id
     *
     * @var array
     */
    protected $_attributes = array();

    /**
     * mapping object array key is store id
     *
     * @var array
     */
    protected $_mapping = array();

    /**
     * mappings array key is store id
     *
     * @var array
     */
    protected $_mappings = array();

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
     * get instance of mapping model by store scope
     *
     * @param integer $storeId
     * @return Dng_Elasticgento_Model_Catalog_Product_Elasticgento_Mappings
     */
    protected function _getMappingModel($storeId)
    {
        if (false === isset($this->_mapping[$storeId])) {
            $this->_mapping[$storeId] = Mage::getModel('elasticgento/catalog_product_elasticgento_mappings')->setStoreId($storeId);
        }
        return $this->_mapping[$storeId];
    }

    /**
     * get index mapping by store store scope
     *
     * @param integer $storeId
     * @return array
     */
    protected function getMappings($storeId)
    {
        if (false === isset($this->_mappings[$storeId])) {
            $this->_mappings[$storeId] = $this->_getMappingModel($storeId)->getMappings();
        }
        return $this->_mappings[$storeId];
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
        $typeMappings = $this->getMappings($storeId);
        $dynamicTemplates = $this->_getMappingModel($storeId)->getDynamicTemplates();
        $type = $this->_getClient()->getIndex($storeId)->getType($this->getEntityType());
        $elasticaMapping = new \Elastica\Type\Mapping($type);
        $elasticaMapping->setParam('_all', array('enabled' => false));
        $elasticaMapping->setParam('dynamic_templates', $dynamicTemplates);
        $elasticaMapping->setProperties($typeMappings);
        $elasticaMapping->send();
        //set index to be prepared
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
     * get Elastica documents for store scope
     *
     * @param int $storeId
     * @param array $productIds update only product(s)
     * @return array
     */
    private function _getDocuments($storeId, $parameters = array())
    {
        list($type) = array_keys($parameters);
        $adapter = $this->_getReadAdapter();
        $websiteId = (int)Mage::app()->getStore($storeId)->getWebsite()->getId();
        $fieldList = array('entity_id', 'type_id', 'attribute_set_id');
        $colsList = array('entity_id', 'type_id', 'attribute_set_id');

        $fields = $this->getMappings($storeId);
        $select = $this->_getReadAdapter()->select()
            ->from(array('e' => $this->getTable('catalog/product')), $colsList)
            ->join(
                array('wp' => $this->getTable('catalog/product_website')),
                'e.entity_id = wp.product_id AND wp.website_id = :website_id',
                array());
        foreach ($this->getAttributes($storeId) as $attributeCode => $attribute) {
            /** @var $attribute Mage_Eav_Model_Entity_Attribute */
            if ($attribute->getBackend()->getType() == 'static') {
                if (false === isset($fields[$attributeCode])) {
                    continue;
                }
                $fieldList[] = $attributeCode;
                $select->columns($attributeCode, 'e');
            }
        }
        if ($type !== 'range') {
            $select->where('e.entity_id BETWEEN ? AND ?', array_map('intval', $parameters['from']), array_map('intval', $parameters['to']));
        }
        $documents = array();
        //loop over result and create documents
        foreach ($adapter->query($select, array('website_id' => (int)$websiteId))->fetchAll() as $entity) {
            $document = $this->_getClient()->getDocument($this->getEntityType() . '_' . $entity['entity_id'], $entity);
            //enable autocreation on update
            $document->setDocAsUpsert(true);
            $documents[$entity['entity_id']] = $document;
        }
        return $documents;
    }

    /**
     * add product eav Attribute to document
     *
     * @param int $storeId
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param array $documents
     * @return array
     */
    public function _updateAttribute($storeId, $attribute, $documents = null)
    {
        $fields = $this->getMappings($storeId);
        $adapter = $this->_getReadAdapter();

        if ($attribute->getBackend()->getType() == 'static') {
            var_dump('not implemented yet');
            return $this;
            if (false === isset($describe[$attribute->getAttributeCode()])) {
                return $this;
            }

            $select = $adapter->select()
                ->join(
                    array('main_table' => $this->getTable('catalog/product')),
                    'main_table.entity_id = e.entity_id',
                    array($attribute->getAttributeCode() => 'main_table.' . $attribute->getAttributeCode())
                );
            $select->where('main_table.entity_id IN(?)', array_map('intval', array_keys($documents)));

            $sql = $select->crossUpdateFromSelect(array('e' => $flatTableName));
            var_dump($sql);
            die();
            $adapter->query($sql);
        } else {
            //non static attributes
            $columns = $attribute->getFlatColumns();
            if (!$columns) {
                return $this;
            }
            foreach (array_keys($columns) as $columnName) {
                if (false === isset($fields[$columnName])) {
                    return $this;
                }
            }
            $select = $attribute->getFlatUpdateSelect($storeId);
            $select->from(array('e' => $this->getTable('catalog/product')), 'entity_id');
            if ($select instanceof Varien_Db_Select) {
                $select->where('e.entity_id IN(?)', array_map('intval', array_keys($documents)));
                foreach ($adapter->query($select)->fetchAll() as $data) {
                    $documentId = $data['entity_id'];
                    //remove entity id Field
                    unset($data['entity_id']);
                    if (true === isset($documents[$documentId])) {
                        if (true === is_array($data) && count($data) > 0) {
                            foreach ($data as $field => $value) {
                                $documents[$documentId]->set($field, $value);
                            }
                        }
                    }
                }
            }
        }
        return $documents;
    }


    /**
     * add product price data to documents
     *
     * @param int $storeId
     * @param array $documents
     * @return array
     */
    private function _addPriceData($storeId, $documents)
    {
        $adapter = $this->_getReadAdapter();
        //we need the website id
        $websiteId = (int)Mage::app()->getStore($storeId)->getWebsite()->getId();
        $select = $adapter->select()
            ->from($this->getTable('catalog/product_index_price'),
                array(
                    'entity_id',
                    'customer_group_id',
                    'website_id', 'price',
                    'final_price',
                    'min_price',
                    'max_price',
                    'tier_price',
                    'group_price'
                )
            );
        //entity id must be first one becaus its in primary key and faster that regular index
        $select->where('entity_id IN (?)', array_map('intval', array_keys($documents)));
        $select->where('website_id = ?', (int)$websiteId);
        //order by null to avoid file sort on disc
        $select->order(new Zend_Db_Expr('NULL'));
        $tmp = array();
        //prebuild index data
        foreach ($adapter->query($select)->fetchAll() as $price) {
            if (false === isset($tmp[$price['entity_id']])) {
                $tmp[$price['entity_id']] = array();
            }
            $tmp[$price['entity_id']]['price_customer_group_' . $price['customer_group_id']] = array(
                'price' => (float)$price['price'],
                'final_price' => (float)$price['final_price'],
                'min_price' => (float)$price['min_price'],
                'max_price' => (float)$price['max_price'],
                'tier_price' => (float)$price['tier_price'],
                'group_price' => (float)$price['group_price']
            );
        }
        foreach ($tmp as $entity_id => $priceIndex) {
            $documents[$entity_id]->set('price_index', $priceIndex);
        }
        return $documents;
    }

    /**
     * get all attributes by store scope
     *
     * @param integer $storeId
     * @return mixed
     */
    public function getAttributes($storeId)
    {
        if (false === isset($this->_attributes[$storeId])) {
            $this->_attributes[$storeId] = Mage::getModel('elasticgento/catalog_product_elasticgento_mappings')->setStoreId($storeId)->getAttributes();
        }
        return $this->_attributes[$storeId];
    }

    /**
     * get specific attribute by code and store scope
     *
     * @param integer $storeId
     * @param string $attributeCode
     * @return mixed
     */
    public function getAttribute($storeId, $attributeCode)
    {
        $attributes = $this->getAttributes($storeId);
        if (!isset($attributes[$attributeCode])) {
            $attribute = Mage::getModel('catalog/resource_eav_attribute')
                ->loadByCode($this->getEntityTypeId(), $attributeCode);
            if (!$attribute->getId()) {
                Mage::throwException(Mage::helper('catalog')->__('Invalid attribute %s', $attributeCode));
            }
            $entity = Mage::getSingleton('eav/config')
                ->getEntityType($this->getEntityType())
                ->getEntity();
            $attribute->setEntity($entity);

            return $attribute;
        }

        return $attributes[$attributeCode];
    }

    /**
     * Update events observer attributes
     *
     * @param int $storeId
     */
    public function updateEventAttributes($storeId = null)
    {
        Mage::dispatchEvent('catalog_product_flat_rebuild', array(
            'store_id' => $storeId,
            'table' => $this->_getClient()->getIndexName($storeId)
        ));
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
        //loop over chunks
        foreach ($chunks as $chunk) {
            $documents = $this->_getDocuments($storeId, array('range' => $chunk));
            foreach ($this->getAttributes($storeId) as $attribute) {
                /* @var $attribute Mage_Eav_Model_Entity_Attribute */
                if ($attribute->getBackend()->getType() != 'static') {
                    $this->_updateAttribute($storeId, $attribute, $documents);
                }
            }
            $documents = $this->_addPriceData($storeId, $documents);
            //finally send documents to the index
            $this->_getClient()->getIndex($storeId)->getType($this->getEntityType())->updateDocuments($documents);
        }
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
