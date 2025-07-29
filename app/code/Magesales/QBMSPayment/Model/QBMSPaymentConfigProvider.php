<?php

namespace Magesales\QBMSPayment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Session\SessionManagerInterface;
use Magesales\QBMSPayment\Helper\Data;

class QBMSPaymentConfigProvider implements ConfigProviderInterface
{
    protected $helper;
    protected $checkoutSession;
    protected $coreSession;

    public function __construct(Data $helper, CheckoutSession $checkoutSession, SessionManagerInterface $coreSession)
    {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->coreSession = $coreSession;
    }

    public function getConfig()
    {
        $config = [];
        $showLogo = $this->helper->showLogo();
        $imageUrl = $this->helper->getPaymentLogo();
        $instructions = $this->helper->getInstructions();
        $config['qbmspayment_imageurl'] = ($showLogo) ? $imageUrl : '';
        $config['qbmspayment_instructions'] = ($instructions) ? $instructions : '';

        return $config;
    }
}
