<?php

namespace Magesales\QBMSPayment\Block\Adminhtml\Connection;

use Magento\Backend\Block\Template;
use Magento\Config\Model\Config as ConfigModel;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magesales\QBMSPayment\Helper\Data;
use Magento\Config\Block\System\Config\Form\Field;

class Status extends Field
{
    const BUTTON_TEMPLATE = 'system/config/connection/status.phtml';

    protected $configModel;

    protected $helper;

    public function __construct(
        Template\Context $context,
        ConfigModel $config,
        Data $helper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->configModel = $config;
        $this->helper = $helper;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if (!$this->getTemplate()) {
            $this->setTemplate(static::BUTTON_TEMPLATE);
        }
        return $this;
    }

    public function render(AbstractElement $element)
    {
        // Remove scope label
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function isConnected()
    {
        return $this->helper->isConnected();
    }
}
