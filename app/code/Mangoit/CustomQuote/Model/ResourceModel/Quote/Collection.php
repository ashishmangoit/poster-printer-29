<?php
namespace Mangoit\CustomQuote\Model\ResourceModel\Quote;
 
use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
 
class Collection extends AbstractCollection
{
 
    protected $_idFieldName = \Mangoit\CustomQuote\Model\Quote::QUOTE_ID;
     
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mangoit\CustomQuote\Model\Quote', 'Mangoit\CustomQuote\Model\ResourceModel\Quote');
    }
 
}