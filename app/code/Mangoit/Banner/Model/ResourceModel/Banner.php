<?php
namespace Mangoit\Banner\Model\ResourceModel;

class Banner extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{      
    protected function _construct()
    {
        $this->_init('mangoit_banner', 'id');
    }
}