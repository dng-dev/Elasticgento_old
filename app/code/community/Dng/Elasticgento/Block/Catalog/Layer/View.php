<?php

/**
 * Catalog layered navigation view block
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Block_Catalog_Layer_View extends Mage_Catalog_Block_Layer_View
{
    /**
     * Boolean block name.
     *
     * @var string
     */
    protected $_booleanFilterBlockName;

    /**
     * Registers current layer in registry.
     *
     * @see Mage_Catalog_Block_Product_List::getLayer()
     */
    protected function _construct()
    {
        parent::_construct();
        Mage::register('current_layer', $this->getLayer());
    }

    /**
     * Modifies default block names to specific ones if engine is active.
     * @todo add blocks
     */
    protected function _initBlocks()
    {
        parent::_initBlocks();
        $this->_categoryBlockName = 'elasticgento/catalog_layer_filter_category';
        $this->_attributeFilterBlockName = 'elasticgento/catalog_layer_filter_attribute';
        $this->_priceFilterBlockName = 'elasticgento/catalog_layer_filter_price';
        $this->_decimalFilterBlockName = 'elasticgento/catalog_layer_filter_decimal';
        $this->_booleanFilterBlockName = 'elasticgento/catalog_layer_filter_boolean';


    }

    /**
     * Prepare child blocks
     *
     * @return Dng_Elasticgento_Block_Catalog_Layer_View
     */
    protected function _prepareLayout()
    {
        $stateBlock = $this->getLayout()->createBlock($this->_stateBlockName)->setLayer($this->getLayer());
        $categoryBlock = $this->getLayout()->createBlock($this->_categoryBlockName)->setLayer($this->getLayer())->init();
        $this->setChild('layer_state', $stateBlock);
        $this->setChild('category_filter', $categoryBlock->addFacetCondition());
        $this->getLayer()->apply();
        $filterableAttributes = $this->_getFilterableAttributes();
        $filters = array();
        foreach ($filterableAttributes as $attribute) {
            if ($attribute->getIsFilterable()) {
                if ($attribute->getAttributeCode() == 'price') {
                    #$filterBlockName = $this->_priceFilterBlockName;
                } elseif ($attribute->getBackendType() == 'decimal') {
                    #$filterBlockName = $this->_decimalFilterBlockName;
                } elseif ($attribute->getSourceModel() == 'eav/entity_attribute_source_boolean') {
                    #$filterBlockName = $this->_booleanFilterBlockName;
                } else {
                    $filterBlockName = $this->_attributeFilterBlockName;
                    $filters[$attribute->getAttributeCode() . '_filter'] = $this->getLayout()->createBlock($filterBlockName)
                        ->setLayer($this->getLayer())
                        ->setAttributeModel($attribute)
                        ->init();
                }

            }
        }
        foreach ($filters as $filterName => $block) {
            $this->setChild($filterName, $block->addFacetCondition());
        }
        $this->getLayer()->apply();
        return $this;
    }

    /**
     * Get layer object
     *
     * @return Dng_Elasticgento_Model_Catalog_Layer
     */
    public function getLayer()
    {
        return Mage::getSingleton('elasticgento/catalog_layer');
    }

    /**
     * Get all layer filters
     *
     * @return array
     */
    public function getFilters()
    {
        $filters = array();
        if ($categoryFilter = $this->_getCategoryFilter()) {
            $filters[] = $categoryFilter;
        }

        $filterableAttributes = $this->_getFilterableAttributes();
        foreach ($filterableAttributes as $attribute) {
            $child = $this->getChild($attribute->getAttributeCode() . '_filter');
            //check child is an object
            if (true === is_object($child)) {
                $filters[] = $this->getChild($attribute->getAttributeCode() . '_filter');
            }
        }

        return $filters;
    }
}