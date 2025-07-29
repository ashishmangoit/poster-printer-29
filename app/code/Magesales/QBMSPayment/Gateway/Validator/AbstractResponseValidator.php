<?php

namespace Magesales\QBMSPayment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;

/**
 * Class AbstractResponseValidator
 */
abstract class AbstractResponseValidator extends AbstractValidator
{
    /**
     * The transaction type that this transaction was processed under
     * One of: Purchase, MOTO, Recurring
     */
    const TRANSACTION_TYPE = 'TransType';

    /**
     * A unique identifier that represents the transaction in eWAY’s system
     */
    const TRANSACTION_ID = 'transactionId';

    /**
     * A code that describes the result of the action performed
     */
    /**
     * The two digit response code returned from the bank
     */
    const AUTH_CODE = 'authCode';

    /**
     * Value of response code
     */
    const RESPONSE_CODE = 'status';


    protected function validateResponseCode(array $response)
    {
        return isset($response[self::RESPONSE_CODE])
            && $response[self::RESPONSE_CODE] != 'null';
    }

    protected function validateAuthorisationCode(array $response)
    {
        return isset($response[self::AUTH_CODE])
            && $response[self::AUTH_CODE] != 'null';
    }

    protected function validateRefundCode(array $response)
    {
        return isset($response[self::RESPONSE_CODE])
            && $response[self::RESPONSE_CODE] != 'null';
    }

}
