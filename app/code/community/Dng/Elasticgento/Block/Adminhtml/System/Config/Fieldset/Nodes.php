<?php

/**
 * class to display info about version in admin
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Block_Adminhtml_System_Config_Fieldset_Nodes
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    /**
     * overide method _prepareToRender in Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     * this is needed to display custom dynamic fieldset
     * @param void
     * @return void
     */
    protected function _prepareToRender()
    {

        $this->_typeRenderer = null;

        $this->addColumn('Host', array(
            'label' => Mage::helper('elasticgento')->__('Host')
        ));
        $this->addColumn('Port', array(
            'label' => Mage::helper('elasticgento')->__('Port'),
            'style' => 'width:50px',
        ));
        // Disables "Add after" button
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('elasticgento')->__('Add Node');
    }

    /**
     * overide method _renderCellTemplate in Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     * this is needed to display custom dynamic fieldset and inject custom element
     *
     * @see Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
     * @param string $columnName
     * @return void
     */
    protected function _renderCellTemplate($columnName)
    {
        $inputName = $this->getElement()->getName() . '[#{_id}][' . $columnName . ']';
        switch ($columnName) {
            case 'type':
            {
                return $this->_getTypeRenderer()
                    ->setName($inputName)
                    ->setTitle($columnName)
                    ->setExtraParams('style="width:50px"')
                    ->setOptions(
                        $this->getElement()->getValues())
                    ->toHtml();
                break;
            }
            default:
                {
                return parent::_renderCellTemplate($columnName);
                }
        }
    }
}