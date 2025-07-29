<?php

namespace Magesales\QBMSPayment\Gateway\Request;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magesales\QBMSPayment\Helper\Data as QBMSPaymentHelper;
use Magesales\QBMSPayment\Helper\Logger as QBMSPaymentLogger;
use Magesales\QBMSPayment\Model\Client;
use Magento\Framework\App\Config\Storage\WriterInterface as ConfigWriter;

class RefundDataBuilder implements BuilderInterface
{
    const PAYMENT_METHOD = 'paymentMethod';

    const HTTP_HEADER = 'httpHeader';

    private $qbmspaymentHelper;

    private $qbmspaymentLogger;

    private $configWriter;

    private $client;

    public function __construct(QBMSPaymentHelper $qbmspaymentHelper, QBMSPaymentLogger $qbmspaymentLogger, Client $client, ConfigWriter $configWriter)
    {
        $this->qbmspaymentHelper = $qbmspaymentHelper;
        $this->qbmspaymentLogger = $qbmspaymentLogger;
        $this->client = $client;
        $this->configWriter = $configWriter;
    }

    public function build(array $buildSubject)
    {
        $paymentDO = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $transactionId = $payment->getAdditionalInformation("reference_num");
        $order = $paymentDO->getOrder();

        $amount = SubjectReader::readAmount($buildSubject);


        $body = [
            "amount" => $amount,
            "description" => "first refund",
            "id" => $transactionId,
            "context" => [
                "deviceInfo" => [
                    "macAddress" => "",
                    "ipAddress" => "",
                    "longitude" => "",
                    "phoneNumber" => "",
                    "latitude" => "",
                    "type" => "",
                    "id" => ""
                ],
                "tax" => 0,
                "recurring" => "false"
            ],
            "created" => ""
        ];

        $this->qbmspaymentLogger->debug('QBMS Refund Body', $body);

        $refreshToken = $this->qbmspaymentHelper->getRefreshToken();

        $token = $this->client->refreshAccessToken($refreshToken,'refresh_token');

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
            'Request-Id' => $order->getOrderIncrementId().rand(),
            'Authorization' => "Bearer " . $token['access_token'],
            'Content-Type' => 'application/json;charset=UTF-8'
        ];

        return [
            self::PAYMENT_METHOD => $body,
            self::HTTP_HEADER => $http_header
        ];
    }
}
