<?php
/**
 * Copyright © MagePal LLC. All rights reserved.
 * See license.txt for license details.
 * https://www.magepal.com | support@magepal.com
 */

namespace MagePal\AddressAutocomplete\Block\Adminhtml;

use Magento\Framework\View\Element\Template;
use MagePal\AddressAutocomplete\Helper\Data;

class OrderCreate extends Template
{

    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * OrderCreate constructor.
     * @param Template\Context $context
     * @param Data $dataHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dataHelper = $dataHelper;
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        return $this->dataHelper->isAdminAutoCompleteEnabled() ? parent::_toHtml() : '';
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return trim($this->dataHelper->getApiKey());
    }

    public function getFieldMappingAsJson()
    {
        return json_encode($this->dataHelper->getFieldMapping());
    }
}
