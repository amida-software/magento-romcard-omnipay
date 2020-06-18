<?php

class Amida_RomCard_Model_Response_Purchase extends \ByTIC\Omnipay\Romcard\Message\PurchaseResponse
{
    public function getRedirectUrl()
    {
        return $this->_getaway()->getEndpointUrl();
    }

    /**
     * @return Amida_RomCard_Model_Getaway
     */
    protected function _getaway()
    {
        return Mage::getModel('romcard/getaway');
    }
}