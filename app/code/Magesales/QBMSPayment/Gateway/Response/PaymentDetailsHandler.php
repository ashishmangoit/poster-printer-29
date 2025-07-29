<?php

namespace Magesales\QBMSPayment\Gateway\Response;

use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magesales\QBMSPayment\Gateway\Validator\AbstractResponseValidator;

class PaymentDetailsHandler implements HandlerInterface
{
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $payment = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($payment);

        $payment->setTransactionId($response[AbstractResponseValidator::AUTH_CODE]);
        $payment->setLastTransId($response[AbstractResponseValidator::AUTH_CODE]);
        $payment->setIsTransactionClosed(false);
        $payment->setAdditionalInformation('transaction_id', $response['authCode']);
        $payment->setAdditionalInformation('response_code', $response['status']);
        $payment->setAdditionalInformation('reference_num', $response['id']);
    }
}
