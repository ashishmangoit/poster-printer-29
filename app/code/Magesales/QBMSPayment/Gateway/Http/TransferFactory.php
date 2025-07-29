<?php

namespace Magesales\QBMSPayment\Gateway\Http;

use Magesales\QBMSPayment\Gateway\Http\Client\Curl;

/**
 * Class TransferFactory
 */
class TransferFactory extends AbstractTransferFactory
{

    public function create(array $request)
    {
        return $this->transferBuilder
            ->setMethod(Curl::POST)
            ->setBody($request)
            ->setUri($this->getUrl())
            ->build();
    }
}
