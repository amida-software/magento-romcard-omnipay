<?php

class Amida_RomCard_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'romcard';

    public function getOrderPlaceRedirectUrl()
    {
        return Mage::getUrl('romcard/payment/redirect', ['_secure' => true, '_current' => true]);
    }
}