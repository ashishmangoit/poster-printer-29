<?php
namespace Mangoit\CustomQuote\Block;
class Quote extends \Magento\Framework\View\Element\Template
{
	//To get dropdown option values
    public function getOptions($option)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $option_value = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')
                                     ->getValue('quote/general/'.$option);
        $option_values = explode(',',$option_value);
        return $option_values;
    }

}