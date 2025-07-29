<?php

namespace Magesales\QBMSPayment\Model;

use Magesales\QBMSPayment\Helper\Data;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Client
{
    protected $scopeConfig;

    protected $helper;

    protected $_httpClientFactory;

    protected $oauthModel;

    const HTTP_METHOD_POST = 'POST';

    public function __construct(
        ZendClientFactory $httpClientFactory,
        ScopeConfigInterface $scopeConfig,
        Data $helper
    )
    {
        $this->_httpClientFactory = $httpClientFactory;
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
    }

    public function getAuthorizationURL()
    {
        $authorizationRequestUrl = Data::CONFIG_QBMS_AUTHORIZATION_URL;
        $clinetId = $this->helper->getClientID();
        $scope = Data::CONFIG_QBMS_OAUTH_SCOPE;
        $redirectUrl = $this->helper->getRedirectUrl();
        $responseType = Data::CONFIG_QBMS_RESPONSE_TYPE;
        $state = Data::CONFIG_QBMS_STATE;
        $parameters = [
            'client_id' => $clinetId,
            'scope' => $scope,
            'redirect_uri' => $redirectUrl,
            'response_type' => $responseType,
            'state' => $state
        ];
        $authorizationRequestUrl .= '?' . http_build_query($parameters, null, '&', PHP_QUERY_RFC1738);
        return $authorizationRequestUrl;
    }

    public function getAccessToken($code)
    {
        $tokenEndPointUrl = Data::CONFIG_QBMS_TOKEN_URL;
        $grantType = Data::CONFIG_QBMS_GRANT_TYPE;
        $redirectUrl = $this->helper->getRedirectUrl();

        $parameters = [
            'grant_type' => $grantType,
            'code' => $code,
            'redirect_uri' => $redirectUrl
        ];
        $authorizationHeaderInfo = $this->generateAuthorizationHeader();
        $http_header = [
            'Accept' => 'application/json',
            'Authorization' => $authorizationHeaderInfo,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];

        //Try catch???
        $result = $this->executeRequest($tokenEndPointUrl, $parameters, $http_header);
        return $result;
    }

    public function generateAuthorizationHeader()
    {
        $encodedClientIDClientSecrets = base64_encode($this->helper->getClientID() . ':' . $this->helper->getClientSecret());
        $authorizationheader = 'Basic ' . $encodedClientIDClientSecrets;
        return $authorizationheader;
    }

    public function refreshAccessToken($refresh_token, $grantType)
    {
        $tokenEndPointUrl = Data::CONFIG_QBMS_TOKEN_URL;
        $parameters = array(
            'grant_type' => $grantType,
            'refresh_token' => $refresh_token
        );

        $authorizationHeaderInfo = $this->generateAuthorizationHeader();
        $http_header = [
            'Accept' => 'application/json',
            'Authorization' => $authorizationHeaderInfo,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ];
        $result = $this->executeRequest($tokenEndPointUrl, $parameters, $http_header);
        return $result;
    }

    private function executeRequest($url, $http_header, $parameters = [])
    {
        $curl_options = [];

        $curl_options[CURLOPT_POST] = '1';

        $body = http_build_query($parameters);
        $curl_options[CURLOPT_POSTFIELDS] = $body;

        if (is_array($http_header)) {
            $header = [];
            foreach ($http_header as $key => $value) {
                $header[] = "$key: $value";
            }
            $curl_options[CURLOPT_HTTPHEADER] = $header;
        }

        $curl_options[CURLOPT_URL] = $url;
        $ch = curl_init();

        curl_setopt_array($ch, $curl_options);
        // Require SSL Certificate

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //Execute the Curl Request
        $result = curl_exec($ch);

        $headerSent = curl_getinfo($ch, CURLINFO_HEADER_OUT);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        if ($curl_error = curl_error($ch)) {
            throw new \Exception($curl_error);
        } else {
            $json_decode = json_decode($result, true);
        }
        curl_close($ch);

        return $json_decode;
    }
}
