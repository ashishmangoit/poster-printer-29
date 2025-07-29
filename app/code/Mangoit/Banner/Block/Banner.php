<?php
namespace Mangoit\Banner\Block;
use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
        
class Banner extends \Magento\Framework\View\Element\Template
{  
    protected $date;    
    protected $scopeConfig;
    protected $collectionFactory;
    public $_storeManager;
    public $storeId;
        
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Mangoit\Banner\Model\ResourceModel\Banner\CollectionFactory $collectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->collectionFactory = $collectionFactory;
        $this->_storeManager=$storeManager;
        $this->date = $date;        
        parent::__construct($context);
    }
    
    public function getFrontBanners()
    {  
        $collection = $this->collectionFactory->create()->addFieldToFilter('status', 1)->setOrder('sort_order','ASC');        
        return $collection;
    }
        
    public function getMediaDirectoryUrl()
    {   
        $media_dir = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            
        return $media_dir;
    }

    public function getCurrentDate()
    {  
        $date = $this->date->gmtDate();      
        return $date;
    }

    public function getCurrentStoreView()
    {  
        $storeId = $this->_storeManager->getStore()->getStoreId();     
        return $storeId;
    }
}