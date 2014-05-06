<?php

/**
 * Catalog Product Elasticgento Index Mappings
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Model_Catalog_Product_Elasticgento_Mappings extends Dng_Elasticgento_Model_Abstract_Mappings
{
    /**
     * index templates for dynamic fields
     *
     * @var array
     */
    protected $_dynamicTemplates = array(
        array(
            'template_price_index' =>
                array(
                    "path_match" => 'price_index.price_customer_group_*',
                    'match' => 'price_customer_group_*',
                    'mapping' => array(
                        'type' => 'object',
                        'properties' => array(
                            'price' => array('type' => 'double'),
                            'min_price' => array('type' => 'double'),
                            'final_price' => array('type' => 'double'),
                            'max_price' => array('type' => 'double'),
                            'tier_price' => array('type' => 'double'),
                            'group_price' => array('type' => 'double')
                        )
                    )
                ),
        ),
        array('template_category_sort' =>
            array(
                "path_match" => "category_sort.category_*",
                'match' => 'category_sort',
                'mapping' => array(
                    'type' => 'integer'
                )
            )
        ),
        array('template_link_types' =>
            array(
                "path_match" => "product_link.*",
                'match' => '*',
                'mapping' => array(
                    'type' => 'integer'
                )
            )
        )
    );

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
     * default mappings like entity_id etc
     *
     * @return array
     */
    protected function getDefaultMappings()
    {
        return array('entity_id' => array('type' => 'integer'),
            'attribute_set_id' => array('type' => 'integer'),
            'type_id' => array('type' => 'string', 'index' => 'not_analyzed'),
            'categories' => array('type' => 'integer'),
            'anchors' => array('type' => 'integer', 'store' => false));
    }
}