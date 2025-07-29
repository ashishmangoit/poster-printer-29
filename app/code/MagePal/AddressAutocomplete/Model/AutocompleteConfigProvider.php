<?php
/**
 * Copyright © MagePal LLC. All rights reserved.
 * See license.txt for license details.
 * https://www.magepal.com | support@magepal.com
 */

namespace MagePal\AddressAutocomplete\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use MagePal\AddressAutocomplete\Helper\Data;

class AutocompleteConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Data
     */
    private $dataHelper;

    /**
     * OrderCreate constructor.
     * @param Data $dataHelper
     */
    public function __construct(
        Data $dataHelper
    ) {
        $this->dataHelper = $dataHelper;
    }

    public function getConfig()
    {
        return [
            'magepal_autocomplete' => [
                'active'        => $this->dataHelper->isFrontendAutoCompleteEnabled(),
                'field_mapping' => $this->dataHelper->getFieldMapping()
            ]
        ];
    }
}
