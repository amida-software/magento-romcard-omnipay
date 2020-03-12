<?php

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
}