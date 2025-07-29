<?php

namespace Magesales\QBMSPayment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\StoreResolver;

class Data extends AbstractHelper
{
    const CONFIG_QBMS_AUTHORIZATION_URL = 'https://appcenter.intuit.com/connect/oauth2';
    const CONFIG_QBMS_TOKEN_URL = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
    const CONFIG_QBMS_OAUTH_SCOPE = 'com.intuit.quickbooks.payment';
    const CONFIG_QBMS_RESPONSE_TYPE = 'code';
    const CONFIG_QBMS_GRANT_TYPE = 'authorization_code';
    const CONFIG_QBMS_STATE = 'RandomState';

    const CONFIG_QBMS_LOGO = 'payment/qbmspayment/show_logo';
    const CONFIG_QBMS_MODE = 'payment/qbmspayment/mode';

    const CONFIG_QBMS_SANDBOX_CLIENT_ID = 'payment/qbmspayment/sandbox_client_id';
    const CONFIG_QBMS_LIVE_CLIENT_ID = 'payment/qbmspayment/live_client_id';

    const CONFIG_QBMS_SANDBOX_GATEWAY_URL = 'payment/qbmspayment/sandbox_gateway_url';
    const CONFIG_QBMS_LIVE_GATEWAY_URL = 'payment/qbmspayment/live_gateway_url';

    const CONFIG_QBMS_SANDBOX_CLIENT_SECRET = 'payment/qbmspayment/sandbox_client_secret';
    const CONFIG_QBMS_LIVE_CLIENT_SECRET = 'payment/qbmspayment/live_client_secret';

    const CONFIG_QBMS_PAYMENT_ACTION = 'payment/qbmspayment/payment_action';
    const CONFIG_QBMS_CONNECTION = 'payment/qbmspayment/is_connected';
    const CONFIG_QBMS_ACCESS_TOKEN = 'payment/qbmspayment/access_token';
    const CONFIG_QBMS_REFRESH_TOKEN = 'payment/qbmspayment/refresh_token';

    const CONFIG_QBMS_INSTRUCTION = 'payment/qbmspayment/instructions';
    const CONFIG_QBMS_DEBUG = 'payment/qbmspayment/debug';

    private $encryptor;
    private $curlFactory;
    private $storeResolver;
    private $storeManager;
    private $repository;
    private $request;

    public function __construct(Context $context, EncryptorInterface $encryptor, CurlFactory $curlFactory, StoreResolver $storeResolver, StoreManagerInterface $storeManager, Repository $repository, RequestInterface $request)
    {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->curlFactory = $curlFactory;
        $this->storeResolver = $storeResolver;
        $this->storeManager = $storeManager;
        $this->repository = $repository;
        $this->request = $request;
    }

    public function showLogo()
    {
        return $this->scopeConfig->getValue(self::CONFIG_QBMS_LOGO, ScopeInterface::SCOPE_STORE);
    }

    public function getPaymentLogo()
    {
        $params = ['_secure' => $this->request->isSecure()];
        return $this->repository->getUrlWithParams('Magesales_QBMSPayment::images/qbms.png', $params);
    }

    public function getInstructions()
    {
        return $this->scopeConfig->getValue(self::CONFIG_QBMS_INSTRUCTION, ScopeInterface::SCOPE_STORE);
    }

    public function getGatewayUrl()
    {
        if ($this->getMode()) {
            return $this->scopeConfig->getValue(self::CONFIG_QBMS_SANDBOX_GATEWAY_URL, ScopeInterface::SCOPE_STORE);
        } else {
            return $this->scopeConfig->getValue(self::CONFIG_QBMS_LIVE_GATEWAY_URL, ScopeInterface::SCOPE_STORE);
        }
    }

    public function getClientID()
    {
        if ($this->getMode()) {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(self::CONFIG_QBMS_SANDBOX_CLIENT_ID, ScopeInterface::SCOPE_STORE));
        } else {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(self::CONFIG_QBMS_LIVE_CLIENT_ID, ScopeInterface::SCOPE_STORE));
        }
    }

    public function getClientSecret()
    {
        if ($this->getMode()) {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(self::CONFIG_QBMS_SANDBOX_CLIENT_SECRET, ScopeInterface::SCOPE_STORE));
        } else {
            return $this->encryptor->decrypt($this->scopeConfig->getValue(self::CONFIG_QBMS_LIVE_CLIENT_SECRET, ScopeInterface::SCOPE_STORE));
        }
    }

    public function getMode()
    {
        return $this->scopeConfig->getValue(self::CONFIG_QBMS_MODE, ScopeInterface::SCOPE_STORE);
    }

    public function isLoggerEnabled()
    {
        return $this->scopeConfig->getValue(self::CONFIG_QBMS_DEBUG, ScopeInterface::SCOPE_STORE);
    }

    public function getPaymentType()
    {
        $action = $this->scopeConfig->getValue(self::CONFIG_QBMS_PAYMENT_ACTION, ScopeInterface::SCOPE_STORE);
        if ($action == 'authorize_capture') {
            return true;
        } else {
            return false;
        }
    }

    public function getRefreshToken()
    {
        return $this->scopeConfig->getValue(self::CONFIG_QBMS_REFRESH_TOKEN, ScopeInterface::SCOPE_STORE);
    }

    public function getAccessToken()
    {
        return $this->scopeConfig->getValue(self::CONFIG_QBMS_ACCESS_TOKEN, ScopeInterface::SCOPE_STORE);
    }

    public function isConnected()
    {
        return $this->scopeConfig->getValue(self::CONFIG_QBMS_CONNECTION, ScopeInterface::SCOPE_STORE);
    }

    public function getRedirectUrl()
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        return $baseUrl . "qbmspayment/connection/success";
    }

    public function getStoreName()
    {
        return $this->storeManager->getStore()->getName();
    }


}
