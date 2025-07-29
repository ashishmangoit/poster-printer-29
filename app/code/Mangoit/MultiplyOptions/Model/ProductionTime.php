<?php
     
namespace  Mangoit\MultiplyOptions\Model;
use Magento\Framework\Model\AbstractModel;
     
class ProductionTime extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Mangoit\MultiplyOptions\Model\ResourceModel\ProductionTime');
    }
}