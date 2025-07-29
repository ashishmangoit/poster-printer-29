<?php
/**
 * Copyright © MagePal LLC. All rights reserved.
 * See license.txt for license details.
 * https://www.magepal.com | support@magepal.com
 */

namespace MagePal\AddressAutocomplete\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    public function isAdminAutoCompleteEnabled($store_id = null)
    {
        return $this->scopeConfig->isSetFlag(
            'magepal_address_autocomplete/general/admin_autocomplete',
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

    public function isFrontendAutoCompleteEnabled($store_id = null)
    {
        return $this->scopeConfig->isSetFlag(
            'magepal_address_autocomplete/general/frontend_autocomplete',
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

    public function getApiKey($store_id = null)
    {
        return $this->scopeConfig->getValue(
            'magepal_address_autocomplete/general/api_key',
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }

    /**
     * @param null $store_id
     * @return array
     */
    public function getFieldMapping($store_id = null)
    {
        return $this->scopeConfig->getValue(
            'magepal_address_autocomplete/field_mapping',
            ScopeInterface::SCOPE_STORE,
            $store_id
        );
    }
}
