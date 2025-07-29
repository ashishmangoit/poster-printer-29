<?php
namespace Mangoit\MultiplyOptions\Controller\Discount; 
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mangoit\MultiplyOptions\Model\ProductionTimeFactory;
use Mangoit\MultiplyOptions\Model\QuantityCalculateFactory;
    class DiscountQty extends Action
    {
        /**
         * @var \Tutorial\SimpleNews\Model\NewsFactory
         */
         protected $_resultPageFactory;
         protected $_productiontimeFactory;
         protected $_quantityCalculatorFactory;
        // protected $_helperData;

   /**
    * @param Context     $context
    * @param PageFactory $resultPageFactory
    */
   public function __construct(
       Context $context,
       PageFactory $resultPageFactory,
       ProductionTimeFactory $productionTimeFactory,
       QuantityCalculateFactory $quantityCalculateFactory
      //\Mangoit\MultiplyOptions\Helper\ServerTime $dataHelper
        
   ) {
       $this->_resultPageFactory = $resultPageFactory;
       $this->_productiontimeFactory = $productionTimeFactory;
       $this->_quantityCalculatorFactory = $quantityCalculateFactory;
       //$this->_helperData = $dataHelper;
       parent::__construct($context);

   }
     
        public function execute()
        {  
            if(isset($_POST['qty']) && isset($_POST['product_id'])) {
              $qty = $_POST['qty'];
              $product_id = $_POST['product_id'];              
            }else{
              $qty = '50';  
              $product_id = '16';            
            }  

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of Object manager
            $quantityDiscount = $objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');
             $result = $quantityDiscount->getDiscount($qty,$product_id);
             die($result);
             echo $result;
            /*$quantityDiscount = $objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');
            $discount = $quantityDiscount->getDiscount();
            print_r($discount);*/
            
        }
    }