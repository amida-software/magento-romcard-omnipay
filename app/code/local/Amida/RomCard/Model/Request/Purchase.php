<?php

class Amida_RomCard_Model_Request_Purchase extends \ByTIC\Omnipay\Romcard\Message\PurchaseRequest
{
    private $data = null;

    protected function getResponseClass()
    {
        return Amida_RomCard_Model_Response_Purchase::class;
    }

    public function getData()
    {
        if ($this->data === null) {
            $this->data = parent::getData();
            $this->data['TRTYPE'] = (string)$this->data['TRTYPE'];
            unset($this->data['redirectUrl']);
        }

        return $this->data;
    }
}