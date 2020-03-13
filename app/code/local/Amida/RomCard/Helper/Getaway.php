<?php

class Amida_RomCard_Helper_Getaway extends Mage_Core_Helper_Abstract
{
    /**
     * @return Amida_RomCard_Helper_Data
     */
    protected function _data()
    {
        return Mage::helper('romcard');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param bool $logResponse
     * @return \ByTIC\Omnipay\Romcard\Message\PurchaseResponse|\Omnipay\Common\Message\ResponseInterface
     * @throws Exception
     */
    public function purchase($order, $logResponse = true)
    {
        $order->setState(Mage_Sales_Model_Order::STATE_NEW, $this->_data()->getNewStatus())->save();
        /**
         * @var Amida_RomCard_Model_Getaway $getaway
         */
        $getaway = Mage::getModel('romcard/getaway');
        $purchase = $getaway->purchase($this->_data()->generatePurchase($order));
        $this->_data()->logRequest($purchase);

        try {
            $result = $purchase->send();
            $this->_data()->logResponse($result);
            return $result;
        } catch (Exception $exception) {
            if ($logResponse) {
                $this->_data()->logResponse($exception);
            }

            throw $exception;
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $responseData
     */
    public function finishPurchase($order, $responseData)
    {
        if ($this->isPaid($responseData['MESSAGE'] ?? null)) {
            $comment = $this->__('Order paid with: %s', $responseData['AMOUNT'] ?? $this->__('Cannot get payment amount'));
            $status = $this->_data()->getFailedStatus();
        } else {
            $comment = $this->__('Order payment failed with: %s', $responseData['AMOUNT'] ?? $this->__('Cannot get payment amount'));
            $status = $this->_data()->getFailedStatus();
        }

        $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, $status, $comment)->save();
    }

    public function isPaid($status)
    {
        return $status == 'Approved';
    }
}