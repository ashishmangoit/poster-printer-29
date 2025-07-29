<?php
namespace Mangoit\MultiplyOptions\Controller\Test;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Mail\Template\TransportBuilder;
class Test extends Action
  {
  private $storeManager;
  protected $_resultPageFactory;
  protected $_objectManager;
  /**
   * @param \Magento\Framework\App\Action\Context $context
   * @param \Magento\Framework\ObjectManagerInterface $objectManager
   */
  protected $_transportBuilder;
  protected $directory_list;
  /**
   * @var  \Magento\Framework\Mail\Template\TransportBuilder
   */
  protected $_storeManager;
  protected $_scopeConfig;
  /**
   * @param Context     $context
   * @param PageFactory $resultPageFactory
   */
  public

  function __construct(Context $context, PageFactory $resultPageFactory)
    {
    $this->_resultPageFactory = $resultPageFactory;

    // $this->_helperData = $dataHelper;

    parent::__construct($context);
    }

  public

  function execute()
    {
    $_objectManager =  \Magento\Framework\App\ObjectManager::getInstance(); //instance of Object manager
    
    // $orderIds = $observer->getOrderIds();

    $orderId = '136';

    $orderObject = $_objectManager->create('\Magento\Sales\Model\Order');
    $order = $orderObject->load($orderId);
    $_items = $order->getAllItems();
    echo $order->getTotalItemCount();
    $quote_item_id = $order->getData('quote_id');
    $increment_id = $order->getIncrementId();
    echo 'increment Id - ' . $increment_id;
    $created_at = $order->getCreatedAtFormatted(2);
    echo '</br>created at  - ' . $created_at;
    $shipping_method = $order->getShippingDescription();
    echo '</br>Shipping method  - ' . $shipping_method;
    $shipping_address = $order->getShippingAddress()->getData();
    $country_name = $_objectManager->create('\Magento\Directory\Model\Country')->load($shipping_address['country_id'])->getName();
    $shipping_address_data = '<p>' . $shipping_address['firstname'] . ' ' . $shipping_address['lastname'] . '<br/>' . $shipping_address['street'] . '<br/>' . $shipping_address['city'] . ' ' . $shipping_address['postcode'] . '<br/>
                    T: <a href="tel:"' . $shipping_address['telephone'] . '">' . $shipping_address['telephone'] . '</a></p>';
    echo $shipping_address_data;
    $billingAddress = $order->getBillingAddress()->getData();
    $billingcountry_name = $_objectManager->create('\Magento\Directory\Model\Country')->load($shipping_address['country_id'])->getName();
    $billing_address_data = '<p>' . $billingAddress['firstname'] . ' ' . $billingAddress['lastname'] . '<br/>' . $billingAddress['street'] . '<br/>' . $billingAddress['city'] . ' ' . $billingAddress['postcode'] . '<br/>
                    T: <a href="tel:"' . $billingAddress['telephone'] . '">' . $billingAddress['telephone'] . '</a></p>';
    echo $billing_address_data;
    $quote_item = $_objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');

    
    $items = $order->getAllVisibleItems();

    foreach($items as $item)
      {      
      $itemId = $item->getQuoteItemId();     
      $item_name = $item->getName();
      echo 'product name '.$item_name;
      $_options = $item->getProductOptions();
      $image_show='';
      if (isset($_options['options']) && !empty($_options['options']))
        {
        foreach($_options['options'] as $_option)
        {

          // $_formatedOptionValue = $this->getFormatedOptionValue($_option);

          if ($_option['option_type'] == 'file')
            {
            // override here as per your requirements
            if (isset($_option['option_value']))
              {
              $image_data = unserialize($_option['option_value']);
              $title = explode('.', $image_data['title']);
              $fileExtension = array_pop($title);
              if ($fileExtension == 'doc' || $fileExtension == 'docx')
                {
                $quote_path = 'custom_options/doc.png';
                }
                else
              if ($fileExtension == 'pdf')
                {
                $quote_path = $quote_item->getImgData($itemId);
                if ($quote_path == '') $quote_path = 'custom_options/pdf.png';
                }
                else
              if ($fileExtension == 'ppt' || $fileExtension == 'pptx')
                {
                $quote_path = 'custom_options/ppt.png';
                }
                else
              if ($fileExtension == 'ai')
                {
                $quote_path = $quote_item->getImgData($itemId);
                if ($quote_path == '') $quote_path = 'custom_options/ai.png';
                }
                else
              if ($fileExtension == 'tiff')
                {
                $quote_path = $quote_item->getImgData($itemId);
                if ($quote_path == '') $quote_path = 'custom_options/tiff.png';
                }
                else
              if ($fileExtension == 'psd')
                {
                $quote_path = $quote_item->getImgData($itemId);
                if ($quote_path == '') $quote_path = 'custom_options/psd.png';
                }
                else
              if ($fileExtension == 'tiff')
                {
                $quote_path = $quote_item->getImgData($itemId);
                if ($quote_path == '') $quote_path = 'custom_options/tiff.png';
                }
                else
              if ($fileExtension == 'eps')
                {
                $quote_path = $quote_item->getImgData($itemId);
                if ($quote_path == '') $quote_path = 'custom_options/eps.jpg';
                }
                else
                {
                $quote_path = $image_data['quote_path'];
                }             
              }
               $storeManager = $_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
              $base_url = $storeManager->getStore()->getBaseUrl();
              $image_path = $base_url . 'pub/media/' . $quote_path;
              // $imgpath = $objectManager->create('Mangoit\MultiplyOptions\Helper\Image')->imageResize($quote_path,'165','165');
              $image_data['image'] = $image_path;
              $image_data['title']=$image_data['title'];
              
            //echo $image_show;

            }
            //echo $image_show;
            if(!isset($image_show))
            {
            $imageSize = 135;
            $_imagehelper = $_objectManager->create('Magento\Catalog\Helper\Image');
            if ($childProd = current($item->getChildrenItems()))
              {
              $productImage = $_imagehelper->init($childProd->getProduct() , 'category_page_list', array(
                'height' => $imageSize,
                'width' => $imageSize
              ))->getUrl();
              }
              else
              {
                $productImage = $_imagehelper->init($item->getProduct() , 'category_page_list', array(
                  'height' => $imageSize,
                  'width' => $imageSize
                ))->getUrl();
              }     
            $image_data['image'] = $productImage;
              $image_data['title']=$product_name;
              //echo $image_show;
            }

           
          }
        }

         
        if(isset($_options))
       // echo $image_show;                   
        $_optionsData = $quote_item->updateOptionPosition($_options['options']);
        $data='';
        foreach ($_optionsData as $_option){
            $data.= "<tr>
                    <td style='padding-left: 10px;'>
                    <strong><em>".$_option['label']."</em></strong>
                    </td>
                    <td>".$_option['value']."</td></tr>";
        }
        echo $data;
        $qtyOrder = $item->getQtyOrdered() * 1;
       echo '</br>quantity ordered -'.$qtyOrder;
       $item_price = number_format((float)$item->getPrice(), 2, '.', '');
       echo '</br>item price-'.$item_price;
      }
      $order_subTotal = number_format((float)$order->getSubtotal() , 2, '.', '');
    echo '</br>order_subTotal' . $order_subTotal;
    $order_tax = number_format((float)$order->getTax() , 2, '.', '');
    echo '</br>order_tax' . $order_tax;
    $shipping_amount = number_format((float)$order->getShippingAmount() , 2, '.', '');
    echo '</br>shipping_amount' . $shipping_amount;
    $grandTotal = number_format((float)$order->getGrandTotal() , 2, '.', '');
    echo '</br>grand Total' . $grandTotal;

    $checkoutSession = $_objectManager->create('Magento\Checkout\Model\Session');
    $checkoutSession->setAdminEmail('1');
    //*/
    // $store = $this->_storeManager->getStore()->getId();
    // $quoteEmail = $this->_scopeConfig->getValue('quote/general/quoteEmail');
    $_storeManager = $_objectManager->create('\Magento\Store\Model\StoreManagerInterface');
    $store = $_storeManager->getStore()->getId();
    $userModel = $_objectManager->create('\Magento\User\Model\User');
    $usermodel = $userModel->getCollection()->addFieldToFilter('is_active', 1);  
    $count = 0;
    $roleId=1;
    $email='';
    foreach ($usermodel->getData() as $key => $value) {
      $role = $userModel->loadByUsername($value['username'])->getRoles();
          if(sizeof($role))
          {
            if($roleId == $role[0]){
                $email = $userList['email'] = $value['email'];       
                }else{
                      continue;
                }    
                
            
         }
    }
          echo $email;
          //die();
    $_transportBuilder = $_objectManager->create('\Magento\Framework\Mail\Template\TransportBuilder');
    $transport = $_transportBuilder->setTemplateIdentifier('3')
    ->setTemplateOptions(['area' => 'frontend', 'store' => $store])
    ->setTemplateVars(
    [
    'store' => $storeManager->getStore(),
    'increment_id'=>$increment_id,
    'created_at'=>$created_at,
    'shipping_method'=>$shipping_method,
    'shipping_address_data'=>$shipping_address_data,
    'billing_address_data'=>$billing_address_data,
    'product_name'=>$item_name,    
    'image'=>$image_data['image'],
    'image_name'=>$image_data['title'],
    'data'=>$data,
    'item_quantity'=>$qtyOrder,
    'item_price'=>$item_price,
    'image_path'=>$image_path,
    'order_subTotal'=>$order_subTotal,
    'order_tax'=>$order_tax,
    'shipping_amount'=>$shipping_amount,
    'total_amount'=>$grandTotal,
    'order'=>$order,
    'admin'=>1    
    ]
    )
    ->setFrom('general')
    // you can config general email address in Store -> Configuration -> General -> Store Email Addresses
    ->addTo('testuser.mango@yopmail.com', 'test')
    ->getTransport();
    $transport->sendMessage();
    echo 'mail';
   die(); 
  }
}  
