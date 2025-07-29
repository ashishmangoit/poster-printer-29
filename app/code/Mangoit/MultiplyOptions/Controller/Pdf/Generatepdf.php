<?php
/**
 * File is used for Amortised module in Magento 2 MIS171051
 * MangoIt Amortised
 *
 * @category Amortised MIS171051
 * @package  MangoIt
 */
namespace Mangoit\MultiplyOptions\Controller\Pdf;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

/**
 * Class UnsetQuote  MIS171051
 * @package MangoIt\Amortised\Controller\Index
 */
class Generatepdf extends Action
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory MIS171051
     */
    protected $fileFactory;

    /**
     * Constructor MIS171051
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->sendPdf();
    }

    public function sendPdf()
    {
        $_objectManager =  \Magento\Framework\App\ObjectManager::getInstance(); //instance of Object manager
        $debugData = $_objectManager->create('Psr\Log\LoggerInterface');
        $debugData->info('controller is working');
        $orderDatamodel = $_objectManager->get('Magento\Sales\Model\Order')->getCollection()->getLastItem();
        $orderId   =   $orderDatamodel->getId();
        $quote_item = $_objectManager->create('\Mangoit\MultiplyOptions\Helper\QuantityCalculate');
        $directory = $_objectManager->get('\Magento\Framework\Filesystem\DirectoryList');
        if(count($orderId)) {
            $order = $_objectManager->create('\Magento\Sales\Model\Order')->load($orderId);
            $items = $order->getAllVisibleItems();

            $order_subTotal = number_format((float)$order->getSubtotal() , 2, '.', '');
            $order_tax = number_format((float)$order->getTax() , 2, '.', '');
            $shipping_amount = number_format((float)$order->getShippingAmount() , 2, '.', '');

            $quote_item_id = $order->getData('quote_id');
            $increment_id = $order->getIncrementId();
            $created_at = $order->getCreatedAtFormatted(2);
            $shipping_method = ($order->getShippingDescription() == 'Free Shipping - Pick Up') ? 'Pick Up' : $order->getShippingDescription();
            $shipping_address = $order->getShippingAddress()->getData();
            $shipping_country_name = $_objectManager->create('\Magento\Directory\Model\Country')->load($shipping_address['country_id'])->getName();
            $shipping_company = '';
            if($shipping_address['company']){
                $shipping_company = '<p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$shipping_address['company'].'</p>';
            }
            if($order->getShippingDescription() == 'Free Shipping - Pick Up'){
                $shipping_company = '<p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">Will Call</p>';
                $shipping_address['street'] = '3 Dorman Avenue,';
                $shipping_address['city'] = 'San Francisco';
                $shipping_address['region'] = 'CA';
                $shipping_address['postcode'] = '94124';
                $shipping_country_name = 'United States';
                $shipping_address['telephone'] = '415-853-2500';
            }
            
            $billingAddress = $order->getBillingAddress()->getData();
            $billingcountry_name = $_objectManager->create('\Magento\Directory\Model\Country')->load($shipping_address['country_id'])->getName();
            $billing_company = '';
            if($billingAddress['company']){
                $billing_company ='<p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$billingAddress['company'].'</p>';
            }
            $_storeManager = $_objectManager->create('\Magento\Store\Model\StoreManagerInterface');
            $store = $_storeManager->getStore()->getId();
            $base_url = $_storeManager->getStore()->getBaseUrl().'/pub/media/logo/stores/1/logo.png';
            $delivery_date = $order->getDeliveryDate();
            $customer_email = $order->getCustomerEmail();
            $finalHtml = '<!DOCTYPE html>
            <html>
            <head>
            <title>Order Summary</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <meta http-equiv="content-type" content="text/html; charset=utf-8">
            <meta name="keywords" content="">
            </head>
            <body style="margin: auto; background:#FFF;font-family:Arial">
                <table align="center" bgcolor="#fff" border="0" cellspacing="0" width="600" style="font-family: "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;">
                    <tbody>
                    <tr>
                    <td valign="top">
                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="padding: 20px 0;">
                    <tbody>
                    <tr>
                    <td align="left" valign="middle" width="320"><img src="'.$base_url.'" alt="Posterprint center"></td>
                    <td align="left" valign="top" style="line-height: 20px;">
                    <p style="margin: 0;font-size: 18px;"><strong>Order: '.$increment_id.'</strong></p>
                    <p style="margin: 0;font-size: 14px;">Date: '.$created_at.'</p>
                    <p style="margin: 0;font-size: 14px;">Shipping Method: '.$shipping_method.'</p>
                    <p style="margin: 0;font-size: 14px;">On: '.$delivery_date.'</p>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <thead style="background: #A9A9A9;color:#fff;">
                    <tr>
                    <th style="padding: 5px 20px;text-transform: uppercase;font-size: 14px;border-right: 1px solid #fff;text-align: left;background: #A9A9A9;color:#fff; font-weight: 500;">Billing Address</th>
                    <th style="padding: 5px 20px;text-transform: uppercase;font-size: 14px;text-align: left;background: #A9A9A9;color:#fff;    font-weight: 500;">Shipping Address</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                    <td style="padding:15px 20px;">
                    <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$billingAddress['firstname'].' '.$billingAddress['lastname'].'</p>
                    '.$billing_company.'   
                    <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$billingAddress['street'].'</p>
                    <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$billingAddress['city'].', '.$billingAddress['region'].', '.$billingAddress['postcode'].'</p>
                    <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$billingcountry_name.'</p>                    
                    <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">T: '.$billingAddress['telephone'].'</p>
                    <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$customer_email.'</p>
                    </td>
                    <td style="padding:15px 20px;">
                     <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$shipping_address['firstname'].' '.$shipping_address['lastname'].'</p>
                    '.$shipping_company.'
                    <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$shipping_address['street'].'</p>
                    <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$shipping_address['city'].', '.$shipping_address['region'].', '.$shipping_address['postcode'].'</p>
                    <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">'.$shipping_country_name.'</p> 
                    <p style="margin: 0;font-size: 14px;font-weight: 600;line-height: 20px;">T: '.$shipping_address['telephone'].'</p>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="background:#fff;">
                    <thead style="background: #fff;;color:#fff;">
                    <tr>
                    <th style="padding: 5px 20px;text-transform: uppercase;font-size: 16px;border-right: 1px solid #fff;text-align: left;background: #A9A9A9;color:#fff;    font-weight: 500;">ORDER SUMMARY</th>
                    <th style="padding: 5px 20px;text-transform: uppercase;font-size: 16px;border-right: 1px solid #fff;text-align: center;background: #A9A9A9;color:#fff;    font-weight: 500;">QTY</th>
                    </tr>
                    </thead>
                    <tbody>';
            foreach($items as $item)
            {
                $itemId = $item->getQuoteItemId();     
                $_options = $item->getProductOptions();
                $productname = $item->getName();
                $image_show = '';
                if (isset($_options['options']) && !empty($_options['options'])) {
                    foreach ($_options['options'] as $_option) {
                        if ($_option['option_type'] == 'file') {
                            // override here as per your requirements
                            if (isset($_option['option_value'])) {
                                $image_data = unserialize($_option['option_value']);
                                $title = explode('.', $image_data['title']);
                                $fileExtension = array_pop($title);
                                if ($fileExtension == 'doc' || $fileExtension == 'docx') {
                                    $quote_path = 'custom_options/doc.png';
                                } elseif ($fileExtension == 'pdf') {
                                    $quote_path = $quote_item->getImgData($itemId);
                                    if ($quote_path == '') $quote_path = 'custom_options/pdf.png';
                                } elseif ($fileExtension == 'ppt' || $fileExtension == 'pptx') {
                                    $quote_path = 'custom_options/ppt.png';
                                } elseif ($fileExtension == 'ai') {
                                    $quote_path = $quote_item->getImgData($itemId);
                                    if ($quote_path == '') $quote_path = 'custom_options/ai.png';
                                } elseif ($fileExtension == 'tiff') {
                                    $quote_path = $quote_item->getImgData($itemId);
                                    if ($quote_path == '') $quote_path = 'custom_options/tiff.png';
                                } elseif ($fileExtension == 'psd') {
                                    $quote_path = $quote_item->getImgData($itemId);
                                    if ($quote_path == '') $quote_path = 'custom_options/psd.png';
                                } elseif ($fileExtension == 'tiff') {
                                    $quote_path = $quote_item->getImgData($itemId);
                                    if ($quote_path == '') $quote_path = 'custom_options/tiff.png';
                                } elseif ($fileExtension == 'eps') {
                                    $quote_path = $quote_item->getImgData($itemId);
                                    if ($quote_path == '') $quote_path = 'custom_options/eps.jpg';
                                } else {
                                    $quote_path = $image_data['quote_path'];
                                }

                                $storeManager = $_objectManager->get('\Magento\Store\Model\StoreManagerInterface');
                                $base_url = $storeManager->getStore()->getBaseUrl();
                                $image_path = $base_url . 'pub/media/' . $quote_path;

                                $image_show = '<span class="product-image-container" style="width:165px;"><span class="product-image-wrapper" style="padding-bottom: 100%;">
                                    <img src="'.$image_path.'" width="165" height="165" alt="Poster Printing">';
                                    
                            }
                        }

                        if ($image_show == null) {
                            $imageSize = 135;
                            $_imagehelper = $_objectManager->create('Magento\Catalog\Helper\Image');
                            if ($childProd = current($item->getChildrenItems())) {
                                $productImage = $_imagehelper->init($childProd->getProduct() , 'category_page_list', array(
                                'height' => $imageSize,
                                'width' => $imageSize
                                ))->getUrl();
                            } else {
                                $productImage = $_imagehelper->init($item->getProduct() , 'category_page_list', array(
                                  'height' => $imageSize,
                                  'width' => $imageSize
                                ))->getUrl();
                            }            
                            $image_show = '<span class="product-image-container" style="width:165px;">
                            <span class="product-image-wrapper" style="padding-bottom: 100%;">
                            <img src="'.$productImage.'" width="165" height="165" alt="Poster Printing">
                            </span></span>';
                        }        
                    }
                }

                $_optionsData = $quote_item->updateOptionPosition($_options['options']);
                $qty = $item->getQtyOrdered() * 1;
                //$price = number_format((float)$item->getPrice(), 2, '.', '');

                $finalHtml .= '<tr><td><table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tbody>
                    <tr><td align="left" valign="middle" width="150" style="padding:10px 15px;display: table-cell;vertical-align: middle;">'.$image_show.'</td>';

                $finalHtml .= '<td align="left" valign="top" style="padding: 10px 10px;border-right: 1px solid #fff;">
                    <table width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="background: #fff;margin:0;padding: 10px;color: #4f6b90;font-size: 14px;font-weight: 600; width:100%">'.$productname.'
                        </td>
                    </tr>
                    </table>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="line-height: 24px;background: #fff;padding:0px 10px 10px;">
                        <tbody>';


                foreach ($_optionsData as $_option) {
                    if(!($_option['label'] == 'Quantity')){
                        $finalHtml .= '<tr>
                        <td style="font-size: 14px;color: #727375;"><strong>'.$_option['label'].':</strong></td>
                        <td style="font-size: 14px;color: #727375;padding-left: 5px;">'.$_option['value'].'</td>
                    </tr>';
                    }
                }

                $finalHtml .= '</tbody>
                            </table>
                            </td>
                            </tr>
                            </tbody>
                            </table>
                            </td>
                            <td style="padding: 5px 10px;font-size: 16px;border-right: 1px solid #fff;text-align: center;">'.$qty.'</td></tr>';
            }

            $finalHtml .= ' </tbody>
                            </table><table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="padding: 30px 0px;">							<tbody>							
                            <tr>							
                            <td align="left" valign="middle" width="320">							
                          	
                            <p style="margin: 0;font-size: 14px;">posterprintcenter.com</p>							<p style="margin: 0;font-size: 14px;">3 Dorman Avenue</p>							<p style="margin: 0;font-size: 14px;">San Francisco, CA 94124</p>							<p style="margin: 0;font-size: 14px;">415-853-2500</p>							<p style="margin: 0;font-size: 14px;">print@posterprintcenter.com</p>							</td>							</tr>							</tbody>							</table>
                            </td>
                            </tr>
                        </tbody>
                    </table>					
                </body>
            </html>';			// footer information
        }
        //echo $finalHtml;

        $pdfTempDir = $directory->getPath('media') . "/mpdf_temp";

        $mpdf = new \Mpdf\Mpdf(['tempDir' => $pdfTempDir, 'default_font_size' => 25]);
        $mpdf->WriteHTML($finalHtml);
        $mpdf->SetDisplayMode('fullpage');
        // $pdfFilePath="/var/www/html/posterprintcenter/var/OrderDetails.pdf";
        $pdfFilePath = $directory
                ->getPath('var').'/OrderDetails' .".pdf";
        $mpdf->output($pdfFilePath, "F");

        return $pdfFilePath;
    }
}
