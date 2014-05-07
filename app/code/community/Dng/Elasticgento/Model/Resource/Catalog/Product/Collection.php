<?php

/**
 * Elasticgento catalog product collection
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Model_Resource_Catalog_Product_Collection extends Dng_Elasticgento_Model_Resource_Collection
{
    /**
     * Product limitation filters
     * Allowed filters
     *  store_id                int;
     *  category_id             int;
     *  category_is_anchor      int;
     *  visibility              array|int;
     *  store_table             string;
     *  use_price_index         bool;   join price index table flag
     *  customer_group_id       int;    required for price; customer group limitation for price
     *
     * @var array
     */
    protected $_productLimitationFilters = array();

    /**
     * Is add tax percents to product collection flag
     *
     * @var bool
     */
    protected $_addTaxPercents = false;

    /**
     * Is add URL rewrites to collection flag
     *
     * @var bool
     */
    protected $_addUrlRewrite = false;

    /**
     * attribute set ids in current collection
     *
     * @var null
     */
    protected $_setIds = null;

    /**
     * Catalog factory instance
     *
     * @var Mage_Catalog_Model_Factory
     */
    protected $_factory;

    /**
     * Initialize resources
     *
     */
    protected function _construct()
    {
        $this->_init('catalog/product');
        $this->_factory = !empty($args['factory']) ? $args['factory'] : Mage::getSingleton('catalog/factory');
    }

    /**
     * @return Dng_Elasticgento_Model_Resource_Collection
     */
    protected function _renderFiltersBefore()
    {
        //apply category filters
        if (true === isset($this->_productLimitationFilters['category_id']) && (int)$this->_productLimitationFilters['category_id'] > 0) {
            //check if category is anchor
            if (false === isset($this->_productLimitationFilters['category_is_anchor'])) {
                $filter = new Elastica\Filter\BoolOr();
                $filterAnchors = new Elastica\Filter\Term();
                $filterAnchors->setTerm('anchors', $this->_productLimitationFilters['category_id']);
            }
            $filterCategory = new Elastica\Filter\Term();
            $filterCategory->setTerm('categories', $this->_productLimitationFilters['category_id']);
            if (false === isset($this->_productLimitationFilters['category_is_anchor'])) {
                $filter->addFilter($filterCategory);
                $filter->addFilter($filterAnchors);
                $filter->setName('category');
                $this->_queryAttributeFilters['category'] = $filter;
            } else {
                $filterCategory->setName('category');
                $this->_queryAttributeFilters['category'] = $filterCategory;
            }
        }
        //apply visibility filters
        if (true === isset($this->_productLimitationFilters['visibility'])) {
            if (true === is_array($this->_productLimitationFilters['visibility'])) {
                $visibilityFilters = new Elastica\Filter\BoolOr();
                foreach ($this->_productLimitationFilters['visibility'] as $visibility) {
                    $visibilityFilter = new Elastica\Filter\Term();
                    $visibilityFilter->setTerm('visibility', $visibility);
                    $visibilityFilters->addFilter($visibilityFilter);
                }
                $visibilityFilters->setName('visibility');
                $this->_queryAttributeFilters['visibility'] = $visibilityFilters;
            }
        }
        return parent::_renderFiltersBefore();
    }

    /**
     * Add URL rewrites to collection
     *
     */
    protected function _addUrlRewrite()
    {
        $urlRewrites = null;
        if ($this->_cacheConf) {
            if (!($urlRewrites = Mage::app()->loadCache($this->_cacheConf['prefix'] . 'urlrewrite'))) {
                $urlRewrites = null;
            } else {
                $urlRewrites = unserialize($urlRewrites);
            }
        }

        if (!$urlRewrites) {
            $productIds = array();
            foreach ($this->_items as $item) {
                $productIds[] = $item->getEntityId();
            }
            #if (!count($productIds)) {
            #    return;
            #}

            $select = $this->_factory->getProductUrlRewriteHelper()
                ->getTableSelect($productIds, $this->_urlRewriteCategory, Mage::app()->getStore()->getId());

            $urlRewrites = array();
            foreach (Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($select) as $row) {
                if (!isset($urlRewrites[$row['product_id']])) {
                    $urlRewrites[$row['product_id']] = $row['request_path'];
                }
            }

            if ($this->_cacheConf) {
                Mage::app()->saveCache(
                    serialize($urlRewrites),
                    $this->_cacheConf['prefix'] . 'urlrewrite',
                    array_merge($this->_cacheConf['tags'], array(Mage_Catalog_Model_Product_Url::CACHE_TAG)),
                    $this->_cacheLifetime
                );
            }
        }

        foreach ($this->_items as $item) {
            if (empty($this->_urlRewriteCategory)) {
                $item->setDoNotUseCategoryId(true);
            }
            if (isset($urlRewrites[$item->getEntityId()])) {
                $item->setData('request_path', $urlRewrites[$item->getEntityId()]);
            } else {
                $item->setData('request_path', false);
            }
        }
    }

    /**
     * after load callback
     *
     * @return Dng_Elasticgento_Model_Resource_Collection_Abstract
     */
    protected function _afterLoad()
    {
        if ($this->_addUrlRewrite) {
            $this->_addUrlRewrite($this->_urlRewriteCategory);
        }
    }

    /**
     * Add minimal price data to result
     *
     * @return Dng_Elasticgento_Model_Resource_Catalog_Product_Collection
     */
    public function addMinimalPrice()
    {
        return $this->addPriceData();
    }

    /**
     * Add final price to the product
     *
     * @return Dng_Elasticgento_Model_Resource_Catalog_Product_Collection
     */
    public function addFinalPrice()
    {
        $this->addPriceData();
        return $this;
    }

    /**
     * Add Price Data to result
     *
     * @param int $customerGroupId
     * @param int $websiteId
     * @return Dng_Elasticgento_Model_Resource_Catalog_Product_Collection
     */
    public function addPriceData($customerGroupId = null)
    {
        $this->_productLimitationFilters['use_price_index'] = true;
        if (false === isset($this->_productLimitationFilters['customer_group_id']) && true === is_null($customerGroupId)) {
            $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        }

        if (null !== $customerGroupId) {
            $this->_selectExtraAttributes['price_index.price_customer_group_' . $customerGroupId] = 'addPriceDataToProduct';
        }
        return $this;
    }

    /**
     * Add requere tax percent flag for product collection
     *
     * @return Dng_Elasticgento_Model_Resource_Catalog_Product_Collection
     */
    public function addTaxPercents()
    {
        $this->_addTaxPercents = true;
        return $this;
    }


    /**
     * Add URL rewrites data to product
     * If collection loadded - run processing else set flag
     *
     * @param int|string $categoryId
     * @return Dng_Elasticgento_Model_Resource_Catalog_Product_Collection
     */
    public function addUrlRewrite($categoryId = '')
    {
        $this->_addUrlRewrite = true;
        if (Mage::getStoreConfig(Mage_Catalog_Helper_Product::XML_PATH_PRODUCT_URL_USE_CATEGORY, $this->getStoreId())) {
            $this->_urlRewriteCategory = $categoryId;
        } else {
            $this->_urlRewriteCategory = 0;
        }
        if ($this->isLoaded()) {
            $this->_addUrlRewrite();
        }
        return $this;
    }

    /**
     * Set product visibility filter for enabled products
     *
     * @param array $visibility
     * @return Dng_Elasticgento_Model_Resource_Catalog_Product_Collection
     */
    public function setVisibility($visibility)
    {
        $this->_productLimitationFilters['visibility'] = (array)$visibility;
        return $this;
    }

    /**
     * add category filter to collection
     *
     * @param Mage_Catalog_Model_Category $category
     */
    public function addCategoryFilter(Mage_Catalog_Model_Category $category)
    {
        $this->_productLimitationFilters['category_id'] = $category->getId();
        if ($category->getIsAnchor() == 1) {
            unset($this->_productLimitationFilters['category_is_anchor']);
        } else {
            $this->_productLimitationFilters['category_is_anchor'] = 1;
        }
        $this->_fieldMap['position'] = 'category_sort.category_' . $category->getId();
        return $this;
    }

    /**
     * callback to add price data after collection loaded
     *
     * @param Mage_Catalog_Model_Product $object
     */
    protected function addPriceDataToProduct(Mage_Catalog_Model_Product $object, $field)
    {
        $nestedFields = explode('.', $field);
        foreach ($nestedFields as $nestedField) {
            if (false === isset($data)) {
                $data = $object->getData($nestedField);
            } elseif (false !== $data) {
                $data = true === isset($data[$nestedField]) ? $data[$nestedField] : false;
            }
        }
        if (null !== $data && false !== $data && true === is_array($data)) {
            foreach ($data as $key => $value) {
                $object->setData($key, $value);
            }
        }
        //remove price index
        $object->unsetData('price_index');
    }

    /**
     * get attribute sets for current collection
     *
     * @return mixed
     */
    public function getSetIds()
    {
        if (false == $this->isLoaded()) {
            $tmpSize = $this->getPageSize();
            $this->setPageSize(0);
            $facet = new \Elastica\Facet\Terms('attribute_set_id');
            $facet->setField('attribute_set_id');
            $facet->setSize(10);
            $this->addFacet($facet);
            $this->load();
            $this->removeFacet($facet->getName());
            $this->setPageSize($tmpSize);
            $this->_setIsLoaded(false);
        }
        if (0 == count($this->_setIds)) {
            foreach ($this->_responseFacets['attribute_set_id']['terms'] as $term) {
                $this->_setIds[$term['term']] = $term['term'];
            }
        }
        return $this->_setIds;
    }
}