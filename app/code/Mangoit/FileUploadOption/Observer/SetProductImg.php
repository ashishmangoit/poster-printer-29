<?php
namespace Mangoit\FileUploadOption\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;


class SetProductImg implements ObserverInterface
{
  protected $_logger;

  public function __construct(
        \Psr\Log\LoggerInterface $logger, //log injection
        array $data = []
  ) 
  {
        $this->_logger = $logger;
       // parent::__construct($data);
  }

  public function execute(\Magento\Framework\Event\Observer $observer)
  {
     $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
     $checkoutSession = $objectManager->create('Magento\Checkout\Model\Session');
     $catalogSession = $objectManager->create('\Magento\Catalog\Model\Session');
     $thumbImg = $catalogSession->getCustomThumbImg();
     $quote = $checkoutSession->getQuote();
     $items = $quote->getItems();
     $maxId = 0;
      foreach ($items as $item){
       if ($item->getId() > $maxId) {
          $maxId = $item->getId();
       }
      }

      $lastItemId = $maxId;
      $quote_item = $objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');
      $quote_item->addImgData($lastItemId,$thumbImg);     
      
  }
  

}