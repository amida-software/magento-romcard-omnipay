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
        if ($order = Mage::registry('current_order') and ! $this->_orderHelper()->canProcessPayment($order)) {
            return false;
        }

        if ($order && $order->getPayment()->getMethod() != 'romcard') {
            return false;
        }

        return $block instanceof Mage_Payment_Block_Info;
    }

    /**
     * @return Amida_RomCard_Helper_Order
     */
    protected function _orderHelper()
    {
        return Mage::helper('romcard/order');
    }
}