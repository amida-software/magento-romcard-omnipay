<?php

class Amida_RomCard_Model_Request_Sale extends \ByTIC\Omnipay\Romcard\Message\SaleRequest
{
    private $data = null;

    protected function getResponseClass()
    {
        return Amida_RomCard_Model_Response_Sale::class;
    }

    public function getData()
    {
        if ($this->data === null) {
            $this->data = parent::getData();
        }

        return $this->data;
    }
}