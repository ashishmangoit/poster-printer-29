<?php
namespace Mangoit\AutoRefreshCache\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ObjectManager;

class ObserverforAddCustomVariable implements ObserverInterface
{

    public function __construct(
    ) {
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\Action\Action $controller */
        $transport = $observer->getTransport();
        $order = $transport->getOrder();
        $shippingMethod = $order->getShippingDescription();
        
        $freeShippingDescription = ($shippingMethod == 'Free Shipping - Pick Up') ? true : false;
        $transport['free_shipping_method'] = $freeShippingDescription;
    }
}