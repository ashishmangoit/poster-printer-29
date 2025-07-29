<?php
namespace Mangoit\Banner\Model;

class Banner extends \Magento\Framework\Model\AbstractModel
{    
    protected function _construct()
    {
        $this->_init('Mangoit\Banner\Model\ResourceModel\Banner');
    }
           
    public function getAvailableStatuses()
    {        
        $availableOptions = ['1' => 'Enable',
                          '0' => 'Disable'];
                
        return $availableOptions;
    }
}