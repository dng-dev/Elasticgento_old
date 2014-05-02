<?php

/**
 * class to display info about version in admin
 *
 * @category  Dng
 * @package   Dng_Elasticgento
 * @author    Daniel Niedergesäß <daniel.niedergesaess@gmail.com>
 * @version   1.0.0
 */
class Dng_Elasticgento_Block_Adminhtml_System_Config_Fieldset_Version
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return Mage::helper('elasticgento')->__('<div class="comment">Elasticgento version: <strong>%s</strong></div>',
            Mage::getConfig()->getNode('modules/Dng_Elasticgento/version'));
    }
}