<?php
/**
 * Copyright © MagePal LLC. All rights reserved.
 * See license.txt for license details.
 * https://www.magepal.com | support@magepal.com
 */

namespace MagePal\AddressAutocomplete\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class FieldMapping implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'short_name', 'label' => __('Short Name')],
            ['value' => 'long_name', 'label' => __('Long Name')]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'short_name' => __('Short Name'),
            'long_name' => __('Long Name')
        ];
    }
}
