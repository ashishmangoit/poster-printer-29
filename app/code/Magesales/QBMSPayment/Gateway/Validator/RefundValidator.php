<?php

namespace Magesales\QBMSPayment\Gateway\Validator;

use Magento\Payment\Gateway\Helper\SubjectReader;

/**
 * Class RefundValidator
 *
 * @package Magesales\QBMSPayment\Gateway\Validator
 */
class RefundValidator extends AbstractResponseValidator
{
    /**
     * @inheritdoc
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);

        $errorMessages = [];

        $validationResult = $this->validateResponseCode($response)
            && $this->validateRefundCode($response);

        if (!$validationResult) {
            $errorMessages = [__('Unable to complete your refund process. There is a processing your request with QBMS Order.')];
        }

        return $this->createResult($validationResult, $errorMessages);
    }
}
