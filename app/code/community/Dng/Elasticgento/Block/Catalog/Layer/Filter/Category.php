<?php

/**
 * Category layer filter block for categories
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Block_Catalog_Layer_Filter_Category extends Mage_Catalog_Block_Layer_Filter_Abstract
{
    /**
     * overide filter model name.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_filterModelName = 'elasticgento/catalog_layer_filter_category';
    }

    /**
     * add category facet condition to filter
     *
     * @return Dng_Elasticgento_Block_Catalog_Layer_Filter_Category
     */
    public function addFacetCondition()
    {
        $this->_filter->addFacetToCollection();

        return $this;
    }
}