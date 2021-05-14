<?php

class Amida_RomCard_Helper_Transaction extends Mage_Core_Helper_Abstract
{
    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $transactionId
     * @param string $transactionType
     *
     * @return Mage_Sales_Model_Order_Payment_Transaction|null
     */
    public function addTransaction($order, $transactionId, $transactionType, $parentTxnId = null)
    {
        if (empty($transactionId)) {
            return null;
        }

        if ($transaction = $order->getPayment()->setTransactionId($transactionId)->addTransaction($transactionType)) {
            if ($parentTxnId !== null) {
                $transaction->setParentTxnId($parentTxnId);
            }

            $transaction->save();
        }

        return $transaction;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $transactionId
     *
     * @return Mage_Sales_Model_Order_Payment_Transaction|null
     */
    public function addAuthTransaction($order, $transactionId)
    {
        return $this->addTransaction($order, $transactionId, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $transactionId
     *
     * @return Mage_Sales_Model_Order_Payment_Transaction|null
     */
    public function addRefundTransaction($order, $transactionId)
    {
        return $this->addTransaction(
            $order,
            $transactionId,
            Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND,
            $order->getPayment()->getAuthorizationTransaction()->getId()
        );
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string $transactionId
     *
     * @return Mage_Sales_Model_Order_Payment_Transaction|null
     */
    public function addPaymentTransaction($order, $transactionId)
    {
        return $this->addTransaction(
            $order,
            $transactionId,
            Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER,
            $order->getPayment()->getAuthorizationTransaction()->getId()
        );
    }
}