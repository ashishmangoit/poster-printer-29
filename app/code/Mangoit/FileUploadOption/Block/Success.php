<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mangoit\FileUploadOption\Block;

/**
 * One page checkout success page
 */
class Success extends \Magento\Checkout\Block\Onepage\Success
{
  public function getOrder() {
   $orderId = $this->_checkoutSession->getLastOrderId();
   $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
   $orderObject = $objectManager->get('\Magento\Sales\Model\Order'); 
   
   $order = $orderObject->load($orderId);
   $items = $order->getAllVisibleItems();
   $orderItemsArray = array();
    $quote_item = $objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');
   $checkoutSession = $objectManager->create('Magento\Checkout\Model\Session');
   $product2 = $objectManager->get('Magento\Catalog\Model\ProductFactory'); 
   $data = $checkoutSession->getImgData();   
   foreach ($items as $item) {
      $product = $product2->create();
      $quote_item_id =  $item->getQuoteItemId();  
      $product->load($item['product_id']); 
       $orderItemsArray[$item['item_id']]['product_id'] = $item['product_id'];
       $orderItemsArray[$item['item_id']]['name'] = $item['name'];
       $orderItemsArray[$item['item_id']]['base_row_total'] = $item['base_row_total'];
       $orderItemsArray[$item['item_id']]['uploaded_file'] = '';
       $imageHelper  = $objectManager->get('\Magento\Catalog\Helper\Image');
       $productImage = $imageHelper->init($product,'cart_page_product_thumbnail') ->constrainOnly(true)->keepAspectRatio(true)->keepFrame(false)->setImageFile($product->getImage())->getUrl();
        $orderItemsArray[$item['item_id']]['product_image'] = $productImage;
       $options = $item->getProductOptions();        
       if (isset($options['options']) && !empty($options['options'])) {
        foreach ($options['options'] as $option) {
         if($option['option_type'] == 'file') {
            $image_data = unserialize($option['option_value']);    
            $quote_path = $image_data['quote_path'];
            $title = explode('.',$image_data['title']);
            $fileExtension = array_pop($title);  
            $data = $checkoutSession->getImgData();                 
            if($fileExtension == 'doc' || $fileExtension == 'docx'){
                $quote_path = 'custom_options/doc.png';
            }
            else if($fileExtension == 'pdf'){               
              $quote_path = $quote_item->getImgData($quote_item_id);
              if($quote_path==''){
                $quote_path = 'custom_options/pdf.png';
              } 
              //$quote_path = 'custom_options/pdf.png';
            }else if($fileExtension == 'ppt' || $fileExtension == 'pptx'){
            $quote_path = 'custom_options/ppt.png';          

          }else if($fileExtension == 'ai'){
            $quote_path = $quote_item->getImgData($quote_item_id);
              if($quote_path==''){
                $quote_path = 'custom_options/ai.png';
              }
            
          }else if($fileExtension == 'tiff'){
            $quote_path = $quote_item->getImgData($quote_item_id);
              if($quote_path==''){
                $quote_path = 'custom_options/tiff.png';
              }
            
          }else if($fileExtension == 'psd'){
            $quote_path = $quote_item->getImgData($quote_item_id);
              if($quote_path==''){
               $quote_path = 'custom_options/psd.png';          
              }

          }else if($fileExtension == 'eps'){
            $quote_path = $quote_item->getImgData($quote_item_id);
              if($quote_path==''){
               $quote_path = 'custom_options/eps.jpg';          
              }
            
          }

           if(isset($quote_path) && $quote_path!=''){
             $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
              $base_url = $storeManager->getStore()->getBaseUrl();
              
              $image_path =  $base_url.'pub/media/'.$quote_path;

             $imgpath = $objectManager->create('Mangoit\MultiplyOptions\Helper\Image')->imageResize($quote_path,'460','460');
              $orderItemsArray[$item['item_id']]['uploaded_file'] = $imgpath; 
            }
           
          }
          $orderItemsArray[$item['item_id']]['quote_item_id'] = $quote_item_id; 
        }

      } 
   }
   
   return $orderItemsArray;
  } 
  
}
