<?php
namespace Mangoit\MultiplyOptions\Observer;

class SetOrderAttribute implements \Magento\Framework\Event\ObserverInterface
{

    protected $checkoutSession;
    protected $serverTime;

    public function __construct(
              \Magento\Checkout\Model\Session $checkoutSession,
              \Mangoit\MultiplyOptions\Helper\ServerTime $serverTime)
    {
        $this->checkoutSession = $checkoutSession;
        $this->serverTime = $serverTime;
    }

    public function getQuotes()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        if ($order) {
            $allItems = $this->getQuotes()->getAllVisibleItems();
            foreach ($allItems as $item) {
                $options = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
                if (isset($options['options'])) {
                    $customOptions = $options['options'];
                    if (!empty($customOptions)) {
                        foreach ($customOptions as $option) {
                            if($optionTitle = $option['label'] == 'Production Time'){
                                $optionId = $option['option_id'];
                                $production_day = $option['value'];    
                            }  
                        }
                    }
                }
            }
            $day_add=0;
            if(isset($production_day)){
                if($production_day == 'Next Business Day'){
                    $day_add=1;
                }elseif($production_day == '2 Business Days'){
                    $day_add=2;
                }elseif($production_day == '3 Business Days'){
                    $day_add=3;
                }elseif($production_day == '4 Business Days'){
                    $day_add=4;
                }elseif($production_day == '5-6 Business Days'){
                    $day_add=5;
                }elseif($production_day == '6-7 Business Days'){
                    $day_add=6;
                }elseif($production_day == '8-10 Business Days'){
                    $day_add=8;
                }else{
                    $day_add =0;
                }
                $production_time = $this->serverTime->getDeliveryDate($day_add);    
                $order->setDeliveryDate($production_time);
            }
        }
        return $this;
    }    
}