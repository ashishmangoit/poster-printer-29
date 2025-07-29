<?php

namespace Mangoit\MultiplyOptions\Model\ResourceModel;
     
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
     
    class ProductionTime extends AbstractDb
    {
        /**
         * Initialize resource model
         *
         * @return void
        */
        protected function _construct()
        {
            $this->_init('production_time', 'id');
        }
    }