<?php
namespace Mangoit\Banner\Model\ResourceModel\Banner;
use \Mangoit\Banner\Model\ResourceModel\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'id';

    protected $_previewFlag;

    protected function _construct()
    {
        $this->_init('Mangoit\Banner\Model\Banner', 'Mangoit\Banner\Model\ResourceModel\Banner');
        $this->_map['fields']['id'] = 'main_table.id';
    }
}