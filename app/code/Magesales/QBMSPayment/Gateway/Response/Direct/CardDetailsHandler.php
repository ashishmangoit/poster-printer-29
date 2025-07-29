<?php

namespace Magesales\QBMSPayment\Gateway\Response\Direct;

use Magento\Customer\Model\Session;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\Config;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order\Payment;
use Magesales\QBMSPayment\Helper\Data;

class CardDetailsHandler implements HandlerInterface
{
    private $config;

    private $customerSession;

    private $helper;

    private $encryptor;

    private $date;

    public function __construct(Config $config, Session $customerSession, Data $helper, EncryptorInterface $encryptor, TimezoneInterface $date)
    {
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        $this->encryptor = $encryptor;
        $this->date = $date;
    }

    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $payment = $paymentDO->getPayment();
        $order = $paymentDO->getOrder();
        ContextHelper::assertOrderPayment($payment);

        $cardDetails = $response['card']['number'];

        $ccTypes = $this->config->getCcTypes();
        $payment->setAdditionalInformation(
            'cc_type',
            $ccTypes[$payment->getAdditionalInformation(OrderPaymentInterface::CC_TYPE)]
        );

        $maskCcNumber = $cardDetails;

        $payment->setAdditionalInformation('card_number', $maskCcNumber);

        $payment->setAdditionalInformation(
            'card_expiry_date',
            sprintf(
                '%s/%s',
                $payment->getAdditionalInformation(OrderPaymentInterface::CC_EXP_MONTH),
                $payment->getAdditionalInformation(OrderPaymentInterface::CC_EXP_YEAR)
            )
        );

        //$payment->unsAdditionalInformation(OrderPaymentInterface::CC_NUMBER_ENC);
        //$payment->unsAdditionalInformation('cc_sid_enc');
        //$payment->unsAdditionalInformation('cc_number');
    }
}
