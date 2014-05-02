<?php

/**
 * Elasticgento Adminhtml observer
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Model_Adminhtml_Observer
{
    /**
     * Adds additional fields to attribute edit form.
     *
     * @param Varien_Event_Observer $observer
     * @todo add more validation / configuration options
     * @todo add selection for search analyzer
     */
    public function catalog_product_attribute_edit_prepare_form(Varien_Event_Observer $observer)
    {

        /** @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
        $attribute = $observer->getEvent()->getAttribute();
        /** @var $form Varien_Data_Form */
        $form = $observer->getEvent()->getForm();
        $fieldset = $form->addFieldset('elasticgento',
            array('legend' => Mage::helper('elasticgento')->__('Elasticgento'))
        );

        $fieldset->addField(
            'search_weight',
            'text', array(
                'name' => 'search_weight',
                'value' => '0',
                'label' => Mage::helper('elasticgento')->__('Search Weight'),
            ));
        if ($attribute->getAttributeCode() == 'name') {
            $form->getElement('is_searchable')->setDisabled(1);
        };
    }

    /**
     * Check if index needs reindex after attribute save
     *
     * @param Varien_Event_Observer $observer
     * @todo better checks for fields which caused reindex
     */
    public function entity_attribute_save_after(Varien_Event_Observer $observer)
    {
        /** @var $attribute Mage_Catalog_Model_Resource_Eav_Attribute */
        $attribute = $observer->getEvent()->getAttribute();
        if ($attribute->getData('search_weight') != $attribute->getOrigData('search_weight')) {
            Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_flat')
                ->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
        }

    }
}