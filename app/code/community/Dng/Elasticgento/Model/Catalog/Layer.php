<?php

/**
 * Catalog view layer model
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Model_Catalog_Layer extends Mage_Catalog_Model_Layer
{
    /**
     * Returns product collection for current category.
     *
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection|Dng_Elasticgento_Model_Resource_Catalog_Product_Collection
     */
    public function getProductCollection()
    {
        /** @var $category Mage_Catalog_Model_Category */
        $category = $this->getCurrentCategory();
        /** @var $collection Dng_Elasticgento_Model_Resource_Catalog_Product_Collection */
        if (true === isset($this->_productCollections[$category->getId()])) {
            $collection = $this->_productCollections[$category->getId()];
        } else {
            $collection = Mage::getResourceModel('elasticgento/catalog_product_collection');
            $collection->setStoreId($category->getStoreId());
            $this->prepareProductCollection($collection);
            $this->_productCollections[$category->getId()] = $collection;

        }
        return $collection;
    }

    /**
     * Initialize product collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection
     * @return Dng_Elasticgento_Model_Catalog_Layer
     */
    public function prepareProductCollection($collection)
    {
        $collection
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->setPageSize(0)
            ->addUrlRewrite($this->getCurrentCategory()->getId());
        $collection->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds());
        return $this;
    }
}
