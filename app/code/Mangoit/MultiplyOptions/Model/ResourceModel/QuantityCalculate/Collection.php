<?php
     
namespace Mangoit\MultiplyOptions\Model\ResourceModel\QuantityCalculate;
 
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
 
class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Mangoit\MultiplyOptions\Model\QuantityCalculate',
            'Mangoit\MultiplyOptions\Model\ResourceModel\QuantityCalculate'
        );
    }
}