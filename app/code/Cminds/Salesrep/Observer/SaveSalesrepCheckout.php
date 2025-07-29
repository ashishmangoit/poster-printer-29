<?php
namespace Cminds\Salesrep\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;

class SaveSalesrepCheckout implements ObserverInterface
{
    protected $checkoutSession;

    protected $salesrepRepositoryInterface;

    protected $adminUsers;

    protected $scopeConfig;

    protected $salesrepHelper;

     protected $_logger;

    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Cminds\Salesrep\Api\SalesrepRepositoryInterface $salesrepRepositoryInterface,
        \Magento\User\Model\ResourceModel\User\Collection $adminUsers,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Cminds\Salesrep\Helper\Data $salesrepHelper,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->salesrepRepositoryInterface = $salesrepRepositoryInterface;
        $this->adminUsers = $adminUsers;
        $this->scopeConfig = $scopeConfig;
        $this->salesrepHelper = $salesrepHelper;
        $this->_logger = $logger;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {   
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $observer->getOrder();
        $quote = $observer->getQuote();
       // $uniqueId = $quote->getUniqueId();
        $quote_id = $this->checkoutSession->getData('quote_id_1');
        $quoteFactory = $objectManager->create('\Magento\Quote\Model\Quote');
        $q = $quoteFactory->load($quote_id);
        $uniqueId = $q->getData('unique_id');
        //$q->setUniqueId($uniqueId);
        
        $uniqueIdData = $objectManager->create('Mangoit\GuestRegister\Helper\UniqueIdData');
        $data = $uniqueIdData->getUrlData($uniqueId);
       // $this->clog($string = '... Data...',$data);
        $defaultStatus = $this->salesrepHelper->getDefaultCommissionStatus();

        if ($order->getId()) {
            $salesrepId = '';
            //$selectedSalesrepId = $this->checkoutSession->getSelectedSalesrepId();
            $selectedSalesrepId = $data[0]['sales_id'];
            if ($selectedSalesrepId) {
                $salesrepId = $selectedSalesrepId;
            } else {
                $customer = $quote->getCustomer();
                if ($customer->getId()) {
                    $customAttr = $customer->getCustomAttributes();
                    if (isset($customAttr['salesrep_rep_id'])) {
                        $salesrepIdData = $customAttr['salesrep_rep_id'];
                        $salesrepId = $salesrepIdData->getValue();
                    }
                }
            }

            if ($salesrepId) {
                $salesrepModel = $this->salesrepRepositoryInterface->get();
                $salesrepModel
                    ->setOrderId($order->getId())
                    ->setRepId($salesrepId);

                $adminUser = $this->adminUsers->getItemById($salesrepId);

                $this->salesrepRepositoryInterface->save($salesrepModel);

                if ($adminUser && $adminUser->getUserId()) {
                    $adminName = $adminUser->getFirstname() . ' ' . $adminUser->getLastname();

                    $salesrepModel = $this->salesrepRepositoryInterface
                        ->getByOrderId($order->getId());

                    $salesrepModel->setRepName($adminName);

                    $salesrepCommissionEarned = $this->salesrepRepositoryInterface
                        ->getRepCommissionEarned(
                            $order->getId(),
                            $adminUser->getSalesrepRepCommissionRate()
                        );

                    if ($salesrepCommissionEarned != null) {
                        $salesrepModel->setRepCommisionEarned(
                            $salesrepCommissionEarned
                        );
                    }

                    $salesrepModel->setRepCommisionStatus($defaultStatus);

                    if ($adminUser->getSalesrepManagerId()) {
                        $managerData = $this->adminUsers->getItemById(
                            $adminUser->getSalesrepManagerId()
                        );

                        if ($managerData && $managerData->getUserId()) {
                            $salesrepModel->setManagerId($managerData->getUserId());

                            $managerName = $managerData->getFirstname()
                                . ' ' . $managerData->getLastname();

                            $salesrepModel->setManagerName($managerName);

                            $managerCommission = $this->salesrepRepositoryInterface
                                ->getManagerCommissionEarned(
                                    $order->getId(),
                                    $managerData->getSalesrepManagerCommissionRate(),
                                    $salesrepCommissionEarned
                                );

                            if ($managerCommission != null) {
                                $salesrepModel->setManagerCommissionEarned(
                                    $managerCommission
                                );
                            }
                            $salesrepModel->setManagerCommissionStatus(
                                $defaultStatus
                            );
                        }
                    }
                }
                $this->salesrepRepositoryInterface->save($salesrepModel);
                $this->checkoutSession->setSelectedSalesrepId('');
            }
        }
    }
   
}
