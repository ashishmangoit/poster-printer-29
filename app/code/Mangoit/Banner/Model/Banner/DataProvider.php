<?php
namespace Mangoit\Banner\Model\Banner;
use Mangoit\Banner\Model\ResourceModel\Banner\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{  
    protected $collection;
    protected $dataPersistor;
    public $_storeManager;
    public $request;
    protected $loadedData;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $blockCollectionFactory,
        DataPersistorInterface $dataPersistor,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $blockCollectionFactory->create();
        $this->request = $request;
        $this->_storeManager=$storeManager;
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData()
    {
        $baseurl =  $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems(); 
       
        foreach ($items as $block) {
            $this->loadedData[$block->getId()] = $block->getData();
  
            $temp = $block->getData();

            $img = [];
            $img[0]['name'] = $temp['image'];
            $img[0]['url'] = $baseurl.$temp['image'];
            $temp['image'] = $img; 

            $mobile_img = [];
            $mobile_img[0]['name'] = $temp['mobile_image'];
            $mobile_img[0]['url'] = $baseurl.$temp['mobile_image'];
            $temp['mobile_image'] = $mobile_img;

            $store_ids = $temp['stores'];
            $store_id = explode(",",$store_ids);

            $temp['stores'] = $store_id;
        }

        $data = $this->dataPersistor->get('mangoit_banner');
       
        if (!empty($data)) {
            $block = $this->collection->getNewEmptyItem();
            $block->setData($data);

            $this->loadedData[$block->getId()] = $block->getData();
             
            $this->dataPersistor->clear('mangoit_banner');
        }
        
        if (empty($this->loadedData)) {
            return $this->loadedData;
        } else {
            if ($block->getData('image') != null && $block->getData('mobile_image') != null) {
                $t2[$block->getId()] = $temp;
                return $t2;
            }
            else {
                return $this->loadedData;
            }
        }
    }
}