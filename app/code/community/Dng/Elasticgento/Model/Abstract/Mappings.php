<?php

/**
 * Elasticgento Abstract Index Mappings
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
abstract class Dng_Elasticgento_Model_Abstract_Mappings
{
    /**
     * Current scope (store Id)
     *
     * @var int
     */
    protected $_storeId;

    /**
     * Entity object to define collection's attributes
     *
     * @var Mage_Eav_Model_Entity_Abstract
     */
    protected $_entity = null;

    /**
     * Eav Entity Type Id
     *
     * @var int
     */
    protected $_entityTypeId;

    /**
     * array with Attribute codes for index
     *
     * @var array
     */
    protected $_attributeCodes;

    /**
     * Attribute objects
     *
     * @var array
     */
    protected $_attributes = null;

    /**
     * index templates for dynamic fields
     *
     * @var array
     */
    protected $_dynamicTemplates = array();

    /**
     * already created mappings as array cache by store id
     *
     * @var array
     */
    protected $_MappingsCache = array();

    /**
     * Retrieve entity type
     *
     * @return string
     */
    abstract function getEntityType();

    /**
     * Set store scope
     *
     * @param int|string|Mage_Core_Model_Store $store
     * @return Mage_Catalog_Model_Resource_Collection_Abstract
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
     * @return Mage_Catalog_Model_Resource_Collection_Abstract
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
     * Return current store id
     *
     * @return int
     */
    public function getStoreId()
    {
        if (null === $this->_storeId) {
            $this->setStoreId(Mage::app()->getStore()->getId());
        }
        return $this->_storeId;
    }

    /**
     * Retrieve default store id
     *
     * @return int
     */
    public function getDefaultStoreId()
    {
        return Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID;
    }

    /**
     * Get mapping entity object
     *
     * @return Mage_Eav_Model_Entity_Abstract
     */
    public function getEntity()
    {
        if (null === $this->_entity) {
            $this->_entity = Mage::getModel('eav/entity')->setType($this->getEntityType());
        }
        return $this->_entity;
    }

    /**
     * Retrieve Catalog Entity Type Id
     *
     * @return int
     */
    public function getEntityTypeId()
    {
        if ($this->_entityTypeId === null) {
            $this->_entityTypeId = $this->getEntity()->getTypeId();
        }
        return $this->_entityTypeId;
    }

    /**
     * Retrieve attribute objects for flat
     *
     * @return array
     */
    public function getAttributes()
    {
        if ($this->_attributes === null) {
            $this->_attributes = array();
            $attributeCodes = $this->getAttributeCodes();
            $entity = Mage::getSingleton('eav/config')->getEntityType($this->getEntityType())->getEntity();
            foreach ($attributeCodes as $attributeCode) {
                $attribute = Mage::getSingleton('eav/config')
                    ->getAttribute($this->getEntityType(), $attributeCode)->setEntity($entity);
                try {
                    // check if exists source and backend model.
                    // To prevent exception when some module was disabled
                    $attribute->usesSource() && $attribute->getSource();
                    $attribute->getBackend();
                    $this->_attributes[$attributeCode] = $attribute;
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }
        }

        return $this->_attributes;
    }

    /**
     * get list of all attribute codes
     * @todo maybe add some filters to reduce amount of data
     */
    public function getAttributeCodes()
    {
        if ($this->_attributeCodes === null) {
            /** @var Mage_Core_Model_Resource $resource */
            $resource = Mage::getSingleton('core/resource');
            $select = $resource->getConnection('core_read')->select();

            $this->_attributeCodes = array();
            $select = $select
                ->from(array('main_table' => $resource->getTableName('eav/attribute')))
                ->join(
                    array('additional_table' => $resource->getTableName('catalog/eav_attribute')),
                    'additional_table.attribute_id = main_table.attribute_id'
                )
                ->where('main_table.entity_type_id = :entity_type_id');
            $result = $resource->getConnection('core_read')->fetchAll($select, array('entity_type_id' => $this->getEntityTypeId()));
            Mage::getSingleton('eav/config')
                ->importAttributesData($this->getEntityType(), $result);
            foreach ($result as $data) {
                $this->_attributeCodes[$data['attribute_id']] = $data['attribute_code'];
            }
            unset($result);
        }
        return $this->_attributeCodes;
    }
}