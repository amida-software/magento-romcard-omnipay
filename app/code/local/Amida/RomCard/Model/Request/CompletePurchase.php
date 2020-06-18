<?php

class Amida_RomCard_Model_Request_CompletePurchase extends \ByTIC\Omnipay\Romcard\Message\CompletePurchaseRequest
{
    private $generatedData = null;

    protected function getResponseClass()
    {
        return Amida_RomCard_Model_Response_CompletePurchase::class;
    }

    public function getData()
    {
        if ($this->generatedData === null) {
            $this->generatedData = parent::getData();
        }

        return $this->generatedData;
    }
}