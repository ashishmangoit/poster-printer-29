<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mangoit\FileUploadOption\Controller\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
class Upload extends \Magento\Framework\App\Action\Action
{   
    public function __construct(
        \Magento\Framework\App\Action\Context $context            
    ) {      
         
        return parent::__construct($context);

    }
     
    public function execute()
    {   
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        $checkoutSession = $objectManager->create('Magento\Checkout\Model\Session');
        $fileSystem = $objectManager->create('\Magento\Framework\Filesystem');
        $mediaPath  =   $fileSystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath();         
        $item_id = $_POST['item_id'];
        $itemImgData = $checkoutSession->getImgData();
        $quote_item = $objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');
        //$quote_item_id = $_POST['quote_item_id'];      
        $product_id = $_POST['product_id'];        
        $file_field = $_FILES['fileupload'];        
        $media  =  $mediaPath.'custom_options/uploads/';
        $mediathumb  = $mediaPath.'files/';   
        $data = explode('.',$_FILES['fileupload']['name']);
        $extension = strtolower(array_pop($data));    
        $key = md5(uniqid(rand(), true));
        $file_name = $_FILES['fileupload']['name'];        
        $file_size =$_FILES['fileupload']['size'];
        $file_tmp =$_FILES['fileupload']['tmp_name'];
        $file_type=$_FILES['fileupload']['type'];
        $optionId='' ;
        if($extension =='pdf' || $extension == 'png' || $extension == 'jpeg' || $extension == 'jpg' ||$extension== 'tiff' || $extension == 'ai' || $extension == 'psd' || $extension == 'ppt' || $extension == 'pptx'|| $extension == 'eps' || $extension == 'doc' || $extension == 'docx'){
            if (move_uploaded_file($file_tmp,$media.$file_name))
            {   
                if($extension =='pdf' ||$extension =='psd' ||$extension =='eps' ||$extension =='tiff'||$extension =='ai'||$extension =='ai'){
                    try {
                            
                          $file = 'thumbnails/'.time().'.png';
                          $mediathumbnails  = $mediathumb.$file;
                          $filepath = 'files/'.$file; 
                           $quote_item = $objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');
                            $quote_item->addImgData($item_id,$filepath);     
                           
                          $mediathumbnails = preg_replace('/\s+/', '', $mediathumbnails);
                          $im = new \Imagick($media.$file_name."[0]");
                          $im->setimageformat('png');
                          $im->thumbnailimage(500, 500); // width and height
                          $im->writeimage($mediathumbnails);
                          $im->clear();
                          $im->destroy();
                    }
                    catch(Exception $e) {
                      print_r($e); die;
                    }
                }
                $path = 'custom_options/uploads/'.$key.$file_name;                     
                $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                $connection = $resource->getConnection();
                $tableName = $resource->getTableName('quote_item_option'); //gives table name with prefix           
                $product = $objectManager->get('\Magento\Catalog\Model\Product')->load($product_id);
                $customOptions = $objectManager->get('Magento\Catalog\Model\Product\Option')->getProductOptionCollection($product);
                $customData = $customOptions->getData();
                foreach($customData as $option){
                    if($option['default_title'] == 'UPLOAD YOUR FILE' ||$option['default_title'] =='UPLOAD YOUR FILE AND ORDER NOW'){                    
                        $optionId = $option['option_id'];
                    }
                } 
                
               $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                $connection = $resource->getConnection();
                $tableName = $resource->getTableName('quote_item_option'); //gives table name with prefix             
                //Select Data from table                            
                $sql = 'Select * FROM '.$tableName.' where (code = "option_ids" and product_id ="'.$product_id.'" and item_id="'.$item_id.'")';      
                $result = $connection->fetchAll($sql); // gives associated array, table fields as key in array.
                $update_row = $result[0]['value'].','.$optionId;
                 //Update Data into table
                $sql = "Update ".$tableName ." Set value = '".$update_row."' where (code = 'option_ids' and product_id ='".$product_id."' and item_id='".$item_id."')";
                $connection->query($sql);
               
                 $image = array
                        (
                            'type' => $file_type,
                            'title' => $file_name,
                            'quote_path' => 'custom_options/uploads/'.$file_name,
                            'order_path' => 'custom_options/uploads/'.$file_name,
                            'fullpath' => '"'.$mediaPath.'/custom_options/uploads/'.$file_name, 
                            'size' => $file_size,
                            'secret_key' => $key,
                            'url' => array (
                                        'route' => 'sales/download/downloadCustomOption',
                                        'params' => 
                                        array (
                                          'id' => $optionId,
                                          'key' => $key,
                                          'custom'=>'yes',
                                        ),
                                      ),
                        );
                $image_ser = serialize($image);  
               // Insert Data into table
               $sql = "Insert Into " . $tableName . " (item_id, product_id,code,value) Values ('".$item_id."','".$product_id."','option_".$optionId."','".$image_ser."')";
               $connection->query($sql);  
               $last_id = $connection->lastInsertId();
                //update sales_quote_item
                $tableName1 = $resource->getTableName('sales_order_item');
                $sql = 'Select * FROM '.$tableName1.' where (quote_item_id = "'.$item_id.'" and product_id ="'.$product_id.'")';
                $result = $connection->fetchAll($sql);
                $order_item  = unserialize($result[0]['product_options']);
                $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
                $base_url = $storeManager->getStore()->getBaseUrl();               
                 $image = array
                        (
                            'type' => $file_type,
                            'title' => $file_name,
                            'quote_path' => 'custom_options/uploads/'.$file_name,
                            'order_path' => 'custom_options/uploads/'.$file_name,
                            'fullpath' => '"'.$mediaPath.'/custom_options/uploads/'.$file_name, 
                            'size' => $file_size,
                            'secret_key' => $key,
                            'url' => array (
                                        'route' => 'sales/download/downloadCustomOption',
                                        'params' => 
                                        array (
                                          'id' => $last_id,
                                          'key' => $key,
                                          'custom'=>'yes',
                                        ),
                                      ),
                        );        
                $image_ser = serialize($image); 
                $option_array = Array
                (
                    'label' => 'UPLOAD YOUR FILE',
                    'value' =>  '<a href="'.$base_url.'sales/download/downloadCustomOption/id/'.$last_id.'/key/'.$key.'" target="_blank">'.$file_name.'</a> 256 x 256 px.',
                    'print_value' => $file_name.' 256 x 256 px.',
                    'option_id' => $optionId,
                    'option_type' => 'file',
                    'option_value' => $image_ser,
                    'custom_view' => 1,
                );
                array_push($order_item['options'], $option_array);
                //Update Data into table   
                $order_item_data = serialize($order_item);
                $sql = "Update ".$tableName1 ." Set product_options = '".$order_item_data."' where (quote_item_id ='".$item_id."')";
                $connection->query($sql);
            }
            else
            {
                $path =  'Error';            
            }
            echo json_encode(array("path" => $path));die();
        }else{
            echo json_encode(array("path" => 'My Error Error'));die();     
        }
        
    }
}
           
        
