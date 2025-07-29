<?php
/**
 * MageVision Blog10
 *
 * @category     Mangoit
 * @package      Mangoit Minicart
 * @author       Mangoit Team
 * @copyright    Copyright (c) 2016 MageVision (https://www.magevision.com)
 * @license      http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Mangoit\Minicart\Plugin\Checkout\CustomerData;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;

class DefaultItem
{
    protected $productRepo;
    protected $logger;

    public function __construct(ProductRepositoryInterface $productRepository,\Psr\Log\LoggerInterface $logger)
    { 
        $this->productRepo = $productRepository;
        $this->logger = $logger;
    }

    public function aroundGetItemData(
        \Magento\Checkout\CustomerData\AbstractItem $subject,
        \Closure $proceed,
         
        \Magento\Quote\Model\Quote\Item $item
    ) {
        $data = $proceed($item);
        $itemId = $item->getItemId();
       $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of Object manager  
        $quote_item = $objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');

        $result['custom_file'] = ''; 
        $stamp;
        foreach ($data['options'] as  $option) {
            if (in_array("UPLOAD YOUR FILE", $option) || in_array("UPLOAD YOUR FILE AND ORDER NOW", $option)) {
                 $_option = $objectManager->create('Magento\Quote\Model\Quote\Item\Option')->getCollection()
                ->addFieldToFilter('code', array('eq' => 'option_'.$option['option_id']))
                ->addFieldToFilter('item_id', array('eq' => $itemId));
                    $image = $_option->getData();                                     
                 // override here as per your requirements
                $image_data = unserialize($image[0]['value']);    
                $title = explode('.',$image_data['title']);
                $fileExtension = array_pop($title);
                if($fileExtension == 'doc' || $fileExtension == 'docx'){
                    $quote_path = 'custom_options/doc.png';
                }
                else if($fileExtension == 'pdf'){

                    $quote_path = $quote_item->getImgData($itemId);
                    if($quote_path==''){
                        $quote_path = 'custom_options/pdf.png';
                    } 

                }else if($fileExtension == 'ppt' || $fileExtension == 'pptx'){
                    $quote_path = 'custom_options/ppt.png';
                }
                else if($fileExtension == 'ai'){
                    $quote_path = $quote_item->getImgData($itemId);
                    if($quote_path==''){
                        $quote_path = 'custom_options/ai.png';
                    } 
                    
                } else if($fileExtension == 'tiff'){
                    $quote_path = $quote_item->getImgData($itemId);
                    if($quote_path==''){
                       $quote_path = 'custom_options/tiff.png';
                    } 
                    
                }
                 else if($fileExtension == 'psd'){
                    $quote_path = $quote_item->getImgData($itemId);
                    if($quote_path==''){
                       $quote_path = 'custom_options/psd.png';
                    }
                    
                }else if($fileExtension == 'tiff'){
                    $quote_path = $quote_item->getImgData($itemId);
                    if($quote_path==''){
                       $quote_path = 'custom_options/tiff.png';
                    }
                    
                }else if($fileExtension == 'eps'){
                    $quote_path = $quote_item->getImgData($itemId);
                    if($quote_path==''){
                      $quote_path = 'custom_options/eps.jpg';
                    }
                    
                }else{
                    $quote_path = $image_data['quote_path'];  
                }
                
                $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
                $base_url = $storeManager->getStore()->getBaseUrl();
               // $image_path =  $base_url.'pub/media/'.$image_data['quote_path']; 
                $imgpath = $objectManager->create('Mangoit\MultiplyOptions\Helper\Image')->imageResize($quote_path,'75','75');                
                $result['custom_file'] =  $imgpath;  
            }else if($result['custom_file']==''){
                $result['custom_file'] = $data['product_image']['src'];
            }           
        } 
        $data['options'] = $quote_item->updateOptionPosition($data['options']);
        return array_merge(
            $result,
            $data
        );
    }
   /* public function pdf_icon($itemId){
         $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of Object manager
         $path ='';
         $checkoutSession = $objectManager->create('Magento\Checkout\Model\Session');
        $data = $checkoutSession->getImgData();
        if(!empty($checkoutSession->getImgData())){
            $data = $checkoutSession->getImgData();
            foreach ($data as $key => $value) {
                if($key == $itemId){
                  $path = $value;        
                }
            }
        }

    return $path;    
    }*/
}