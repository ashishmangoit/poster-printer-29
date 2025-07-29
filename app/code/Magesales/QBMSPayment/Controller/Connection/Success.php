<?php

namespace Magesales\QBMSPayment\Controller\Connection;

use \Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Validator\Exception;
use Magesales\QBMSPayment\Model\Client;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;
use Magesales\QBMSPayment\Helper\Data;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Success extends Action
{
    protected $client;
    protected $configWriter;
    protected $helper;

    public function __construct(
        Context $context,
        Client $client,
        Data $helper,
        ConfigWriter $configWriter
    )
    {
        parent::__construct($context);
        $this->client = $client;
        $this->helper = $helper;
        $this->configWriter = $configWriter;
    }

    public function execute()
    {

        try {
            $code = $this->getRequest()->getParam('code');
            $state = $this->getRequest()->getParam('state');

            if (strcmp($state, "RandomState") != 0) {
                throw new \Exception("The state is not correct from Intuit Server. Consider your app is hacked.");
            }

            $token = $this->client->getAccessToken($code);
            if (array_key_exists('refresh_token', $token) && array_key_exists('access_token', $token)) {
                $this->configWriter->save(DATA::CONFIG_QBMS_CONNECTION, 1, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
                $this->configWriter->save(DATA::CONFIG_QBMS_ACCESS_TOKEN, $token['access_token'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
                $this->configWriter->save(DATA::CONFIG_QBMS_REFRESH_TOKEN, $token['refresh_token'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
            }
            $this->messageManager->addSuccessMessage(__('You are successfully connected with Quickbooks Payment API.'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this->_redirect('qbmspayment/connection/index');
    }
}
