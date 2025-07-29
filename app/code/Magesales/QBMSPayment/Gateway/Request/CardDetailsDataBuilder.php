<?php

namespace Magesales\QBMSPayment\Gateway\Request;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magesales\QBMSPayment\Helper\Data as QBMSPaymentHelper;
use Magesales\QBMSPayment\Helper\Logger as QBMSPaymentLogger;
use Magesales\QBMSPayment\Observer\DataAssignObserver;
use Magesales\QBMSPayment\Model\Client;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;

/**
 * Class CardDetailsDataBuilder
 *
 * @package Magesales\QBMSPayment\Gateway\Request
 */
class CardDetailsDataBuilder implements BuilderInterface
{

    const PAYMENT_METHOD = 'paymentMethod';

    const HTTP_HEADER = 'httpHeader';

    private $curl;
    private $qbmspaymentHelper;
    private $qbmspaymentLogger;
    private $encryptor;
    private $client;
    private $configWriter;

    /**
     * CardDetailsDataBuilder constructor.
     * @param CurlFactory $curl
     * @param QBMSPaymentHelper $qbmspaymentHelper
     * @param QBMSPaymentLogger $qbmspaymentLogger
     * @param EncryptorInterface $encryptor
     * @param Client $client
     * @param ConfigWriter $configWriter
     */
    public function __construct(CurlFactory $curl, QBMSPaymentHelper $qbmspaymentHelper, QBMSPaymentLogger $qbmspaymentLogger, EncryptorInterface $encryptor, Client $client, ConfigWriter $configWriter)
    {
        $this->curl = $curl;
        $this->qbmspaymentHelper = $qbmspaymentHelper;
        $this->qbmspaymentLogger = $qbmspaymentLogger;
        $this->encryptor = $encryptor;
        $this->client = $client;
        $this->configWriter = $configWriter;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        $billingAddress = $order->getBillingAddress();

        $amount = $this->formatPrice(SubjectReader::readAmount($buildSubject));

        ContextHelper::assertOrderPayment($payment);

        $data = $payment->getAdditionalInformation();
        $month = $this->formatMonth($data[OrderPaymentInterface::CC_EXP_MONTH]);
        $year = $data[OrderPaymentInterface::CC_EXP_YEAR];
        $cardNumber = $this->encryptor->decrypt($data[OrderPaymentInterface::CC_NUMBER_ENC]);
        $cvn = $this->encryptor->decrypt($data[DataAssignObserver::CC_CID_ENC]);

        // Custom added code for Firstname and Lastname BEGINS
        if(!empty($billingAddress->getLastname()))
            $name = $billingAddress->getFirstname()." ".$billingAddress->getLastname();
        else
            $name = $billingAddress->getFirstname();
        // Custom added code for Firstname and Lastname ENDS

        $region = '';

        if ($billingAddress->getCountryId() == 'US') {
            $region = $billingAddress->getRegionCode();
        }

        $isCapture = "false";
        if ($this->qbmspaymentHelper->getPaymentType()) {
            $isCapture = "true";
        }

        $body = [
            "amount" => $amount,
            "card" => [
                "expYear" => $year,
                "expMonth" => $month,
                "address" => [
                    "region" => $region,
                    "postalCode" => $billingAddress->getPostcode(),
                    "streetAddress" => $billingAddress->getStreetLine1(),
                    "country" => $billingAddress->getCountryId(),
                    "city" => $billingAddress->getCity()
                ],
                "name" => $name,
                "cvc" => $cvn,
                "number" => $cardNumber
            ],
            "currency" => strtoupper($order->getCurrencyCode()),
            "context" => [
                "mobile" => "false",
                "isEcommerce" => "true"
            ],
            "capture" => $isCapture
        ];

        $this->qbmspaymentLogger->debug('QBMS Request Body', $body);

        $refreshToken = $this->qbmspaymentHelper->getRefreshToken();

        $token = $this->client->refreshAccessToken($refreshToken, 'refresh_token');

        $this->qbmspaymentLogger->debug('Refresh Token', $token);

        if (array_key_exists('refresh_token', $token) && array_key_exists('access_token', $token)) {
            $this->configWriter->save(QBMSPaymentHelper::CONFIG_QBMS_CONNECTION, 1, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
            $this->configWriter->save(QBMSPaymentHelper::CONFIG_QBMS_ACCESS_TOKEN, $token['access_token'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
            $this->configWriter->save(QBMSPaymentHelper::CONFIG_QBMS_REFRESH_TOKEN, $token['refresh_token'], $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
        } else {
            $error = 'Unable to generate access token. Please check data processing your request.';
            throw new LocalizedException(__($error));
        }

        $http_header = [
            'Accept' => 'application/json',
            'Request-Id' => $order->getOrderIncrementId(),
            'Authorization' => "Bearer " . $token['access_token'],
            'Content-Type' => 'application/json;charset=UTF-8'
        ];

        return [
            self::PAYMENT_METHOD => $body,
            self::HTTP_HEADER => $http_header
        ];
    }

    private function formatMonth($month)
    {
        return !empty($month) ? sprintf('%02d', $month) : null;
    }

    private function formatPrice($price)
    {
        return sprintf('%.2F', $price);
    }
}
