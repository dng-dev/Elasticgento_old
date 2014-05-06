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
     * Initialize connection
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/product', 'entity_id');
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
     * calculate the ranging steps for a total reindex for each store
     *
     * @param int $storeId
     * @return array
     *      - from
     *      - to
     */
    public function getIndexChunks($storeId)
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
     * get prepared Elastica documents
     *
     * @param int $storeId
     * @param array $productIds update only product(s)
     * @return array
     * @todo get Documents from client
     */
    public function createDocuments($storeId, $parameters = array())
    {
        list($type) = array_keys($parameters);
        $adapter = $this->_getReadAdapter();
        $websiteId = (int)Mage::app()->getStore($storeId)->getWebsite()->getId();
        /* @var $status Mage_Eav_Model_Entity_Attribute */
        $status = $this->getAttribute('status');

        $fieldList = array('entity_id', 'type_id', 'attribute_set_id');
        $colsList = array('entity_id', 'type_id', 'attribute_set_id');

        $fields = $this->getIndexMappings($storeId);
        $bind = array(
            'website_id' => (int)$websiteId,
            'store_id' => (int)$storeId,
            'entity_type_id' => (int)$status->getEntityTypeId(),
            'attribute_id' => (int)$status->getId()
        );

        $fieldExpr = $adapter->getCheckSql('t2.value_id > 0', 't2.value', 't1.value');
        $select = $this->_getReadAdapter()->select()
            ->from(array('e' => $this->getTable('catalog/product')), $colsList)
            ->join(
                array('wp' => $this->getTable('catalog/product_website')),
                'e.entity_id = wp.product_id AND wp.website_id = :website_id',
                array())
            ->joinLeft(
                array('t1' => $status->getBackend()->getTable()),
                'e.entity_id = t1.entity_id',
                array())
            ->joinLeft(
                array('t2' => $status->getBackend()->getTable()),
                't2.entity_id = t1.entity_id'
                . ' AND t1.entity_type_id = t2.entity_type_id'
                . ' AND t1.attribute_id = t2.attribute_id'
                . ' AND t2.store_id = :store_id',
                array())
            ->where('t1.entity_type_id = :entity_type_id')
            ->where('t1.attribute_id = :attribute_id')
            ->where('t1.store_id = ?', Mage_Core_Model_App::ADMIN_STORE_ID)
            ->where("{$fieldExpr} = ?", Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        foreach ($this->getAttributes() as $attributeCode => $attribute) {
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
            $select->where('e.entity_id BETWEEN ? AND ?', (int)$parameters['from'], (int)$parameters['to']);
        }
        $documents = array();
        //loop over result and create documents
        foreach ($adapter->query($select, $bind)->fetchAll() as $entity) {
            $document = new Elastica\Document($this->getEntityType() . '_' . $entity['entity_id'], $entity);
            //enable autocreation on update
            $document->setDocAsUpsert(true);
            $documents[$entity['entity_id']] = $document;
        }
        return $documents;
    }
}
