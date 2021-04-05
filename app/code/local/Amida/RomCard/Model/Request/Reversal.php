<?php

use ByTIC\Omnipay\Romcard\Helper;

class Amida_RomCard_Model_Request_Reversal extends Amida_RomCard_Model_Request_Sale
{
    private $data = null;

    public function setTransactionType($type)
    {
        return $this->setParameter('transaction_type', $type);
    }

    protected function getResponseClass()
    {
        return Amida_RomCard_Model_Response_Reversal::class;
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

    public function getData()
    {
        if ($this->data === null) {
            $data = [
                'ORDER' => Helper::formatOrderId($this->getOrderId()),
                'AMOUNT' => Helper::formatAmount($this->getAmount()),
                'CURRENCY' => $this->getCurrency(),
                'RRN' => $this->getCardReference(),
                'INT_REF' => $this->getTransactionReference(),
                'TRTYPE' => $this->getParameters()['transaction_type'],
                'TERMINAL' => $this->getTerminal(),
                'TIMESTAMP' => gmdate('YmdHis'),
                'NONCE' => self::generateNonce(),
                'BACKREF' => $this->getReturnUrl(),
            ];
            $data['P_SIGN'] = Helper::generateSignHash($data, $this->getKey());
            $this->data = $data;
        }

        return $this->data;
    }
}