<?php

namespace Magesales\QBMSPayment\Gateway\Validator\Direct;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magesales\QBMSPayment\Gateway\Validator\AbstractResponseValidator;

class ResponseValidator extends AbstractResponseValidator
{
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);

        $errorMessages = [];

        $validationResult = $this->validateResponseCode($response)
            && $this->validateAuthorisationCode($response);

        if (!$validationResult) {
            $errorMessages = [__('Unable to complete your order. There is a processing your request with QBMS Order.')];
        }

        return $this->createResult($validationResult, $errorMessages);
    }
}
