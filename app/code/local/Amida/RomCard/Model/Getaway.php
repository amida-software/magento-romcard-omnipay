<?php

use Omnipay\Common\Message\RequestInterface;

class Amida_RomCard_Model_Getaway extends \ByTIC\Omnipay\Romcard\Gateway
{
    /**
     * @return Amida_RomCard_Helper_Data
     */
    protected function _helper()
    {
        return Mage::helper('romcard');
    }

    public function __construct($httpClient = null, $httpRequest = null)
    {
        $httpClient = $this->getDefaultHttpClient();
        $httpRequest = $this->getDefaultHttpRequest();
        parent::__construct($httpClient, $httpRequest);
        $this->setTestMode($this->_helper()->isDevelop());
    }

    public function purchase(array $parameters = []): RequestInterface
    {
        $parameters['endpointUrl'] = $this->getEndpointUrl();

        return $this->createRequest(
            Amida_RomCard_Model_Request_Purchase::class,
            array_merge($this->getDefaultParameters(), $parameters)
        );
    }

    public function completePurchase(array $parameters = []): RequestInterface
    {
        /** @var Amida_RomCard_Model_Request_CompletePurchase $request */
        $request = $this->createRequest(
            Amida_RomCard_Model_Request_CompletePurchase::class,
            array_merge($this->getDefaultParameters(), $parameters)
        );
        $request->setSaleRequest($this->sale($parameters));
        return $request;
    }

    public function sale(array $parameters = []): RequestInterface
    {
        $parameters['endpointUrl'] = $this->getEndpointUrl();

        return $this->createRequest(
            Amida_RomCard_Model_Request_Sale::class,
            array_merge($this->getDefaultParameters(), $parameters)
        );
    }

    public function refund(array $parameters = []): RequestInterface
    {
        $parameters['endpointUrl'] = $this->getEndpointUrl();

        return $this->createRequest(
            Amida_RomCard_Model_Request_Reversal::class,
            array_merge($this->getDefaultParameters(), $parameters)
        );
    }
}