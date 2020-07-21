<?php

use ByTIC\Omnipay\Romcard\Helper;

class Amida_RomCard_Model_Request_Reversal extends Amida_RomCard_Model_Request_Sale
{
    private $data = null;

    protected function getResponseClass()
    {
        return Amida_RomCard_Model_Response_Reversal::class;
    }

    public function getData()
    {
        if ($this->data === null) {
            $data = parent::getData();
            $data['TRTYPE'] = Amida_RomCard_Helper_Data::PAYMENT_TRANSACTION_REVERSAL;
            $data['P_SIGN'] = Helper::generateSignHash($data, $this->getKey());
            $this->data = $data;
            unset($data);
        }

        return $this->data;
    }
}