<?php 

namespace Mangoit\MultiplyOptions\Helper;


class QuantityCalculate extends \Magento\Framework\App\Helper\AbstractHelper
{	
 protected $qtyDiscount;
 // server time slot display the server time 
 //mon_fri_01_01_pm_12_0_am in this formate
	protected $_scopeConfig;
  public function __construct(
\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
) {
    $this->_scopeConfig = $scopeConfig;
}
 //compare database value with option production time
	public function getDiscount($qty = null,$product_id = null) {
		$production_time = array();
    $qtyDsc ='0';
    $productNotQty =array();    
    $productNotQty = explode(',',$this->_scopeConfig->getValue('helloworld/general/display_text', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
     if(in_array($product_id, $productNotQty)){
        return $qtyDsc = '+ 0';
     }
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Collection')
    ->addFieldToFilter('entity_id', array('eq' => $product_id));        
    $productCollection->load();
    $data =  $productCollection->getData();  
    $attribute_set_id = $data[0]['attribute_set_id'];
    $attributeCollection = $objectManager->create('Magento\Eav\Api\AttributeSetRepositoryInterface')->get($attribute_set_id);
    $attributeName = $attributeCollection->getAttributeSetName();
    
	  $qtyDiscountCollection = $objectManager->get('Mangoit\MultiplyOptions\Model\QuantityCalculate')->getCollection();	  
	  $qtyDiscount =  $qtyDiscountCollection->getData();
	  if(sizeof($qtyDiscount)) {
      
       foreach ($qtyDiscount as $key => $value) {
          if($value['attribute_name'] == $attributeName){
             if(sizeof($value)) {
              $qtyLimit = explode('-', str_replace(' ', '', $value['limit']));
              
              if(sizeof($qtyLimit) > 1) {
               if($qty >= $qtyLimit[0] && $qty <= $qtyLimit[1]) {
                 $qtyDsc =  $value['discount'];
                 //break;
               }
              } else {
                if($value['id'] <= 1) {
                 if($qty <= $qtyLimit[0]) {
                  $qtyDsc =  $value['discount'];
                 // break;
                 }
                } else {
                 if($qty >= $qtyLimit[0]) {
                  $qtyDsc =  $value['discount'];
                 // break;
                 }
                }
              }
             }
          }   
       }
      }
       return $qtyDsc = $qtyDsc == '0' ?'+ 0' :$qtyDsc ;
      //echo json_encode($qtyDiscount);
	}
	public function addImgData($itemId,$thumbImg){
    
     $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
     $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection();
            $tableName = $resource->getTableName('quote_item'); //gives table name with prefix             
            //Select Data from table                            
            $sql = 'Select * FROM '.$tableName.' where (item_id="'.$itemId.'")';      
            $result = $connection->fetchAll($sql); // gives associated array, table fields as key in array.
            //Update Data into table
            $sql = "Update ".$tableName ." Set img_path = '".$thumbImg."' where ( item_id='".$itemId."')";
            $connection->query($sql);
  }
  public function getImgData($itemId){
     $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
     $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
      $connection = $resource->getConnection();
      $tableName = $resource->getTableName('quote_item'); //gives table name with prefix             
      //Select Data from table                            
      $sql = 'Select img_path FROM '.$tableName.' where (item_id="'.$itemId.'")';      
      $result = $connection->fetchAll($sql); // gives associated array, table fields as key in array.
    return $result[0]['img_path'];
    
  }

  public function updateOptionPosition($_options){
    $_newArray = array();
    $file_name='';
    $artwork_index=0;
    $count =1;
    foreach ($_options as $key => $value) {
      if($value['label'] == 'Quantity'){
        $_newArray[0] = $value;

      }else if(($value['label'] == 'UPLOAD YOUR FILE') || ($value['label'] == 'UPLOAD YOUR FILE AND ORDER NOW')){
        $file_name = $value['value'];
      }else if($value['label'] == 'Artwork'){
        $artwork_index = $count;
        $_newArray[$count++] = $value;  
      }    
      else {
        $_newArray[$count++] = $value;
      }
    }
    
    foreach ($_newArray as $key=>$value)
    {
        if ($value['label'] == 'Artwork' && $file_name!='')
           $_newArray[$artwork_index]['value'] = $file_name;
    }
    ksort($_newArray);    
    
    return $_newArray;
  }

  

}	
