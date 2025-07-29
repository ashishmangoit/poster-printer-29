<?php 
namespace Mangoit\CustomOptions\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface; 
 
class ProductWidget extends Template implements BlockInterface {

    protected $_template = "widget/homeblock1.phtml";

    public function __construct(\Magento\Framework\View\Element\Template\Context $context,\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,        
        array $data = [])
	{
		$this->_productCollectionFactory = $productCollectionFactory; 
		parent::__construct($context);
	}
	public function getProductCollection($id)
    {
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->addFieldToFilter('entity_id', ['in' => $id]); // fetching only 3 products
        return $collection;
    }

    public function getProductIds() {
        if ($this->hasData('productids')) {
            return $this->getData('productids');
        }
        return $this->getData('productids');
    }

}
 