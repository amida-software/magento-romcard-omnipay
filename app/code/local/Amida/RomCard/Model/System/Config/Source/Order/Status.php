<?php

class Amida_RomCard_Model_System_Config_Source_Order_Status
{
    /**
     * @var Mage_Sales_Model_Resource_Order_Status_Collection
     */
    private $statuses = null;

    public function toArray()
    {
        return $this->init()->toArray();
    }

    public function toOptionArray()
    {
        return $this->init()->toOptionArray();
    }

    protected function init()
    {
        if ($this->statuses === null) {
            $this->statuses = Mage::getModel('sales/order_status')->getCollection();
        }

        return $this->statuses;
    }
}