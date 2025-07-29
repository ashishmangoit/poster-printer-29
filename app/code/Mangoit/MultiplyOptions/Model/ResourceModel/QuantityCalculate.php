<?php

namespace Mangoit\MultiplyOptions\Model\ResourceModel;
     
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
     
    class QuantityCalculate extends AbstractDb
    {
        /**
         * Initialize resource model
         *
         * @return void
        */
        protected function _construct()
        {
            $this->_init('quantity_discount', 'id');
        }
    }