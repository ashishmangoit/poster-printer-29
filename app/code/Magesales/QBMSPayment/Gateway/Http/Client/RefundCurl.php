<?php

namespace Magesales\QBMSPayment\Gateway\Http\Client;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Adapter;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Framework\HTTP\Client\Curl as ClientCurl;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magesales\QBMSPayment\Helper\Data as SagePayHelper;
use Magesales\QBMSPayment\Helper\Logger as SagePayLogger;
use Magento\Payment\Model\Method\Logger;

class RefundCurl implements ClientInterface
{
    /**
     * HTTP protocol versions
     */
    const HTTP_1 = '1.1';
    const HTTP_0 = '1.0';

    /**
     * HTTP request methods
     */
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const HEAD = 'HEAD';
    const DELETE = 'DELETE';
    const TRACE = 'TRACE';
    const OPTIONS = 'OPTIONS';
    const CONNECT = 'CONNECT';
    const MERGE = 'MERGE';
    const PATCH = 'PATCH';

    /**
     * Request timeout
     */
    const REQUEST_TIMEOUT = 30;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Adapter\Curl
     */
    private $curl;

    private $helper;

    private $qbmspaymentLogger;
    private $clientCurl;

    public function __construct(
        Logger $logger,
        ResponseFactory $responseFactory,
        Adapter\Curl $curl,
        SagePayHelper $helper,
        SagePayLogger $qbmspaymentLogger,
        ClientCurl $clientCurl
    )
    {
        $this->logger = $logger;
        $this->responseFactory = $responseFactory;
        $this->curl = $curl;
        $this->helper = $helper;
        $this->qbmspaymentLogger = $qbmspaymentLogger;
        $this->clientCurl = $clientCurl;
    }

    /**
     * @inheritdoc
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $log = [
            'request' => json_encode($transferObject->getBody(), JSON_UNESCAPED_SLASHES),
            'request_uri' => $transferObject->getUri()
        ];

        $this->qbmspaymentLogger->debug('Refund Curl Request', $log);


        try {
            $headers = $this->convertHeaderArrayToHeaders($transferObject->getBody()['httpHeader']);
            $body = json_encode($transferObject->getBody()['paymentMethod']);
            $id = $transferObject->getBody()['paymentMethod']['id'];
            $url = $transferObject->getUri() . '/' . $id . '/refunds';

            $curl_options = [];
            $curl_options[CURLOPT_POST] = '1';
            $curl_options[CURLOPT_POSTFIELDS] = $body;
            $curl_options[CURLOPT_HTTPHEADER] = $headers;
            $curl_options[CURLOPT_URL] = $url;

            $ch = curl_init();

            curl_setopt_array($ch, $curl_options);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $response = curl_exec($ch);

            $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT);

            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($curl_error = curl_error($ch)) {
                throw new \Exception($curl_error);
            } else {
                $data = json_decode($response, true);
            }
            if ($responseCode != 200 && $responseCode != 201 && $responseCode != 422 && $responseCode != 400) {
                throw new LocalizedException(__('Unexpected HTTP RESPONSE CODE #' . $responseCode));
                return;
            }

        } catch (\Exception $e) {
            throw new ClientException(__($e->getMessage()));
        } finally {
        }

        return (array)$data;
    }

    public function read()
    {
        return $this->responseFactory->create($this->curl->read())->getBody();
    }

    public function convertHeaderArrayToHeaders(array $headerArray)
    {
        $headers = array();
        foreach ($headerArray as $k => $v) {
            $headers[] = $k . ":" . $v;
        }
        return $headers;
    }
}
