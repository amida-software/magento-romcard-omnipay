<?php

class Amida_RomCard_Model_Request_Sale extends \ByTIC\Omnipay\Romcard\Message\SaleRequest
{
    protected function getResponseClass()
    {
        return Amida_RomCard_Model_Response_Sale::class;
    }

    public function sendData($data)
    {
        $url = $this->getEndpointUrl();
        $httpRequest = $this->httpClient->post($url, [], $data);
        $httpResponse = $httpRequest->send();

        $data = self::parseResponseHtml($httpResponse->getBody(true));

        if (is_array($data) && count($data)) {
            $class = $this->getResponseClass();

            return $this->response = new $class($this, $data);
        }

        return false;
    }
}