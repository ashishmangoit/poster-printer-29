<?php

namespace Mangoit\Minicart\Plugin\Checkout\Model;
use Magento\Checkout\Model\Session as CheckoutSession;
/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DefaultConfigProvider extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Quote\Api\CartItemRepositoryInterface
     */
    protected $checkoutSession;


    /**
     * @param \Magento\Quote\Api\CartItemRepositoryInterface $itemRepository
     * @param \Magento\Checkout\CustomerData\ItemPoolInterface $itemPool
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;        
    }

    /**
     * {@inheritdoc}
     */
     public function afterGetConfig(
        \Magento\Checkout\Model\DefaultConfigProvider $subject,
        array $result
    ) {
         $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of Object manager  
        $quote_item = $objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');
        $items = $result['totalsData']['items'];
        foreach ($items as $index => $item) {
            $data =json_decode($item['options']);
            $data = $this->updateOptionPosition($data);
            $item['options'] = json_encode($data); 
            $result['totalsData']['items'][$index]['options'] = $item['options'];           
        }
        return $result;
    }

    public function updateOptionPosition($_options){
        $_newArray = array();
        $file_name='';
        $artwork_index=0;
        $count =1;
        foreach ($_options as $key => $value) {            
          if($value->label == 'Quantity'){
            $_newArray[0] = $value;
          }else if(($value->label == 'UPLOAD YOUR FILE') || ($value->label == 'UPLOAD YOUR FILE AND ORDER NOW')){
            $file_name = $value->value;                        
          }else if($value->label == 'Artwork'){
            $_newArray[$count++] = $value;  
          }    
          else {
            $_newArray[$count++] = $value;
          }
        }
        
        foreach ($_newArray as $key=>$value)
        {
            if ($value->label == 'Artwork' && $file_name!='')
               $value->value = $file_name;
        }
        ksort($_newArray);    
        return $_newArray;
    }
}    
