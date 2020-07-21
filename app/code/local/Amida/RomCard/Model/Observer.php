<?php

class Amida_RomCard_Model_Observer
{
    public function setPaymentFormTemplate($observer)
    {
        $block = $observer->getBlock();

        if ($this->canUpdateBlockTemplate($block)) {
            $block->setTemplate('payment/info/romcard.phtml');
        }
    }

    protected function canUpdateBlockTemplate($block)
    {
        /**
         * @var Mage_Sales_Model_Order $order
         */
        if ($order = Mage::registry('current_order')) {
            if (! $order->getPayment()->getAuthorizationTransaction()) {
                return false;
            }
        }

        return $block instanceof Mage_Payment_Block_Info;
    }
}