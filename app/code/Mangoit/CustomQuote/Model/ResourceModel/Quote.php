<?php
namespace Mangoit\CustomQuote\Model\ResourceModel;
 
use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;
 
/**
 * Quote post mysql resource
 */
class Quote extends AbstractDb
{
 
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        // Table Name and Primary Key column
        $this->_init('custom_quote', 'quote_id');
    }
 
}