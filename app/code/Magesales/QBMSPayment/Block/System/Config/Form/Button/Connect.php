<?php

namespace Magesales\QBMSPayment\Block\System\Config\Form\Button;

use Magento\Config\Block\System\Config\Form\Field as ConfigFormField;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Connect
 *
 * @package Magesales\QBMSPayment\Block\System\Config\Form\Button
 */
class Connect extends ConfigFormField
{
    /**
     * Set Template
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate('system/config/connection/button.phtml');
        }

        return $this;
    }

    /**
     * Return URL process connector
     *
     * @return string
     */
    public function getGrantUrl()
    {
        return $this->getUrl('qbmspayment/connection/start');
    }

    /**
     * Unset some non-related element parameters
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Get the button and scripts contents
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }
}
