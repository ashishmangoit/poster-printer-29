<?php 
namespace Mangoit\MultiplyOptions\Observer;
 
use Magento\Framework\Event\ObserverInterface;
 
class SendMailOnOrderSuccess implements ObserverInterface
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderModel;
 
    /**
     * @var \Magento\Sales\Model\Order\Email\Sender\OrderSender
     */
    protected $orderSender;
 
    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;
    protected $_objectManager;
 
    /**
     * @param \Magento\Sales\Model\OrderFactory $orderModel
     * @param \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender
     * @param \Magento\Checkout\Model\Session $checkoutSession
     *
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderModel,
        \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->orderModel = $orderModel;
        $this->orderSender = $orderSender;
        $this->checkoutSession = $checkoutSession;
    }
 
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
    	$_objectManager =  \Magento\Framework\App\ObjectManager::getInstance(); //instance of Object manager
    	$debugData = $_objectManager->create('Psr\Log\LoggerInterface');
       	$debugData->info('observer is working');
        $orderIds = $observer->getEvent()->getOrderIds();
        if(count($orderIds))
        {
            $pdfFile = $_objectManager->create('Mangoit\MultiplyOptions\Controller\Pdf\Generatepdf')->sendPdf();
            $this->checkoutSession->setForceOrderMailSentOnSuccess(true);
            $order = $this->orderModel->create()->load($orderIds[0]);
            // $this->orderSender->send($order, true);
            $_items = $order->getAllItems();
		    $quote_item_id = $order->getData('quote_id');
		    $increment_id = $order->getIncrementId();
		    $created_at = $order->getCreatedAtFormatted(2);
		    $delivery_date = $order->getDeliveryDate();
		    //$shipping_method = explode("-",$order->getShippingDescription());
		    $shipping_method = $order->getShippingDescription();
		    $payment = $order->getPayment();
        	$method = $payment->getMethodInstance();
        	$payment_method = $method->getTitle();
		    $free_shipping_method = ($shipping_method == 'Free Shipping - Pick Up') ? true : false;
		    $shipping_address = $order->getShippingAddress()->getData();
		    $customer_email = $order->getCustomerEmail();
		    $shipping_country_name = $_objectManager->create('\Magento\Directory\Model\Country')->load($shipping_address['country_id'])->getName();
		    $shipping_company = '';
		    if($shipping_address['company']){
		    	$shipping_company = $shipping_address['company'].'<br>';
		    }
		    $shipping_address_data = '<p style="margin: 0;font-size: 14px;line-height: 20px;">' . $shipping_address['firstname'] . ' ' . $shipping_address['lastname'] . '<br/>'.$shipping_company . $shipping_address['street'] . '<br/>' . $shipping_address['city'].', '.$shipping_address['region'].', '.$shipping_address['postcode'] . '<br/>'.$shipping_country_name.'
		                    <br>T : '. $shipping_address['telephone'] .'</p>';
		    $billingAddress = $order->getBillingAddress()->getData();
		    $billingcountry_name = $_objectManager->create('\Magento\Directory\Model\Country')->load($shipping_address['country_id'])->getName();
		    $billing_company = '';
		    if($billingAddress['company']){
		    	$billing_company = $billingAddress['company'].'<br>';
		    }
		    $billing_address_data = '<p style="margin: 0;font-size: 14px;line-height: 20px;">' . $billingAddress['firstname'] . ' ' . $billingAddress['lastname'] . '<br/>'.$billing_company . $billingAddress['street'] . '<br/>' . $billingAddress['city'].', '.$billingAddress['region'].', '.$billingAddress['postcode'] . '<br/>'.$billingcountry_name.'
		                    <br>T : ' . $billingAddress['telephone'] . '<br>'.$customer_email.'</p>';
		    
		    $_storeManager = $_objectManager->create('\Magento\Store\Model\StoreManagerInterface');
		    $store = $_storeManager->getStore()->getId();
		    $userModel = $_objectManager->create('\Magento\User\Model\User');
		    $usermodel = $userModel->getCollection()->addFieldToFilter('is_active', 1);  
		    $count = 0;
		    $roleId=1;
		    $email=array();
		    foreach ($usermodel->getData() as $key => $value) {
		      	$role = $userModel->loadByUsername($value['username'])->getRoles();
		      	if(sizeof($role))
		          {
		            if($roleId == $role[0]){
		                //$email = $userList['email'] = $value['email'];       
		                $email[] = $value['email'];       
		                }else{
		                      continue;
		                }    
		         }
		    };
		   	//$admin_email = implode(',',$email);
		    //echo $admin_email;
		    $admin_email = 'orders@posterprintcenter.com,testuser.mango@gmail.com';
		    $_storeManager = $_objectManager->create('\Magento\Store\Model\StoreManagerInterface');
    		$store = $_storeManager->getStore()->getId();
		    $_transportBuilder = $_objectManager->create('Mangoit\MultiplyOptions\Model\TransportBuilder');
		    $_transportBuilder->clearFrom();
		    $_transportBuilder->clearRecipients();
            $_transportBuilder->clearSubject();
            $_transportBuilder->clearMessageId();
            $_transportBuilder->clearBody();  
			$transport = $_transportBuilder
			    ->setTemplateIdentifier('3')
			    ->setTemplateOptions(['area' => 'frontend', 'store' => $store])
			    ->setTemplateVars(
			    [
			    'store' => $_storeManager->getStore(),
			    'increment_id'=>$increment_id,
			    'created_at'=>$created_at,
			    'delivery_date'=>$delivery_date,
			    'shipping_method'=>$shipping_method,
			    'payment_method'=>$payment_method,
			    'shipping_address_data'=>$shipping_address_data,
			    'billing_address_data'=>$billing_address_data,
			    'order'=>$order,
			    'free_shipping_method'=> $free_shipping_method,
			    'admin'=>1
			    ]
			    )
			    ->addAttachment(file_get_contents($pdfFile)) //Attachment goes here.
			    ->setFrom('general')
			    ->addTo('store@posterprintcenter.com')
			    ->getTransport();
			    
	    	$transport->sendMessage();
	    
	    	$debugData->info('Admin email send successfully.');
		}

    }
}