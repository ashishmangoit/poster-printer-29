<?php
namespace Mangoit\MultiplyOptions\Controller\Index; 
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Mangoit\MultiplyOptions\Model\ProductionTimeFactory;
use Mangoit\MultiplyOptions\Model\QuantityCalculateFactory;
    class Index extends Action
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
          $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); //instance of Object manager
            $title;$product_id;
            if(isset($_POST['title']) && isset($_POST['product_id'])){
              $title = $_POST['title'];
              $product_id = $_POST['product_id'];
            }else{
              $title = 'Next Business Day';              
              $product_id = '16';
            }
            
            $productionTime = $objectManager->create('\Mangoit\MultiplyOptions\Helper\ServerTime');
            $result['slot'] = $productionTime->compareToDatabase($title,$product_id);
            $show_same_date = $productionTime->sameDayShow($product_id);
            $result['show_same_day'] = $show_same_date;
            $show_business_day_3 = $productionTime->businessDay3Show($product_id);
            $result['show_business_day_3'] = $show_business_day_3;
            $show_business_day_2 = $productionTime->businessDay2Show($product_id);
            $result['show_business_day_2'] = $show_business_day_2;
            echo json_encode($result);die();
            
        }
    }