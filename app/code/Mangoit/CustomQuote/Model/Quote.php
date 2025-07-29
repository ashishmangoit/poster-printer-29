<?php
namespace Mangoit\CustomQuote\Model;
 
use \Magento\Framework\Model\AbstractModel;
 
class Quote extends AbstractModel
{
	const QUOTE_ID = 'quote_id';

    protected function _construct()
    {
        $this->_init('Mangoit\CustomQuote\Model\ResourceModel\Quote');
    }
 
}
