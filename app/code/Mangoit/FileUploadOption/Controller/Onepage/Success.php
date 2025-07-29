<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mangoit\FileUploadOption\Controller\Onepage;

class Success extends \Magento\Checkout\Controller\Onepage
{
    /**
     * Order success action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    { 
        $session = $this->getOnepage()->getCheckout();
        $orderObject = $this->_objectManager->get('\Magento\Sales\Model\Order'); 
        $orderId = $session->getLastOrderId();  

        $order = $orderObject->load($orderId);
        $items = $order->getAllVisibleItems();
        $orderItemsArray = array();
       // $item_id = $item['item_id'];
         $product2 = $this->_objectManager->get('Magento\Catalog\Model\ProductFactory'); 
         $imageHelper  = $this->_objectManager->get('\Magento\Catalog\Helper\Image');

         foreach ($items as $item) {            
          $quote_item_id =  $item->getQuoteItemId();            
          $product = $product2->create();
          $product->load($item['product_id']);
          $productImage = $imageHelper->init($product,'cart_page_product_thumbnail') ->constrainOnly(true)->keepAspectRatio(true)->keepFrame(false)->setImageFile($product->getImage())->getUrl();                    
          $orderItemsArray[$item['item_id']]['product_id'] = $item['product_id'];          
          $orderItemsArray[$item['item_id']]['base_row_total'] = $item['base_row_total'];
          $orderItemsArray[$item['item_id']]['uploaded_file'] = '';
          $orderItemsArray[$item['item_id']]['product_image'] = $productImage;            
          $options = $item->getProductOptions();        
          if (isset($options['options']) && !empty($options['options'])) {
            foreach ($options['options'] as $option) {
              if($option['option_type'] == 'file') {
                $optionValue = unserialize($option['option_value']);
                $orderItemsArray[$item['item_id']]['uploaded_file'] = $optionValue['order_path']; 
              }else{
              $orderItemsArray[$item['item_id']]['uploaded_file'] = $quote_item_id;
            }
          }
        }
      }
      
        if (!$this->_objectManager->get('Magento\Checkout\Model\Session\SuccessValidator')->isValid()) {
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
        $session->clearQuote();
        $resultPage = $this->resultPageFactory->create();
        $this->_eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            ['order_ids' => [$session->getLastOrderId()]]
        );
         
       // $salesOrder = $this->_objectManager->get('Magento\Sales\Model\Order\Item')->load(44);
        
        return $resultPage;
    }
}
