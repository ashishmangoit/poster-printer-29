<?php

namespace Magesales\QBMSPayment\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magesales\QBMSPayment\Model\Client;
use Magento\Store\Model\StoreManagerInterface;

abstract class AbstractConnection extends Action
{
    protected $resultPageFactory;

    protected $client;

    protected $storeManager;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Client $client,
        StoreManagerInterface $storeManager
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->client = $client;
        $this->storeManager = $storeManager;
    }
}
