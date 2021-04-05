<?php

class Amida_RomCard_Helper_Order extends Mage_Core_Helper_Abstract
{
    /**
     * @var array
     */
    protected $strategies = null;
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $order = null;

    /**
     * @return Amida_RomCard_Helper_Data
     */
    protected function _data()
    {
        return Mage::helper('romcard');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param bool $throwIfError
     * @return bool
     */
    public function canProcessPayment($order, $throwIfError = false)
    {
        if (! $order->getPayment()->getAuthorizationTransaction()) {
            if ($throwIfError) {
                Mage::throwException($this->__('Payment already processed'));
            }

            return false;
        }

        return true;
    }

    public function getOrder($excludeStrategies = [])
    {
        if ($this->order === null) {
            $this->order = Mage::getModel('sales/order');

            foreach ($this->getStrategies() as $strategyCode => $strategyCallback) {
                if ($this->order->getId()) {
                    break;
                }

                if (! $this->isStrategyValid($strategyCode, $strategyCallback, $excludeStrategies)) {
                    continue;
                }

                if ($loadData = call_user_func($strategyCallback)) {
                    list($value, $attribute) = $loadData;
                    $this->order->loadByAttribute($attribute, $value);
                }
            }

            if (! $this->order->getId()) {
                throw Mage::exception('Amida_RomCard', $this->__('Invalid order'));
            }
        }

        return $this->order;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $responseData
     * @param $state
     */
    public function complete($order, $responseData, $state = Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW)
    {
        list($action, $transactionType) = $this->_data()->parseStatus($responseData);
        $amount = $responseData['AMOUNT'] ?? $responseData['f'] ?? $this->__('Cannot get payment amount');
        $status = $this->getOrderStatusByResponse($action, $transactionType);
        $comment = $this->getOrderCommentByResponse($action, $transactionType, $amount);
        $order->setState($state, $status, $comment)->save();
    }

    public function isPaid($status, $transactionType)
    {
        return $status == Amida_RomCard_Helper_Getaway::GETAWAY_ACTION_SUCCESS && $transactionType == Amida_RomCard_Helper_Data::PAYMENT_TRANSACTION_COMPLETE_SALES;
    }

    public function isReversal($status, $transactionType)
    {
        return $status == Amida_RomCard_Helper_Getaway::GETAWAY_ACTION_SUCCESS
            && in_array($transactionType, [
                Amida_RomCard_Helper_Data::PAYMENT_TRANSACTION_REVERSAL,
                Amida_RomCard_Helper_Data::PAYMENT_TRANSACTION_REVERSAL_PARTIAL
            ]);
    }

    public function getOrderStatusByResponse($responseMessage, $transactionType)
    {
        if ($this->isPaid($responseMessage, $transactionType)) {
            return $this->_data()->getSuccessStatus();
        }

        if ($this->isReversal($responseMessage, $transactionType)) {
            return $this->_data()->getReversalStatus();
        }

        return $this->_data()->getFailedStatus();
    }

    public function getOrderCommentByResponse($responseMessage, $transactionType, $amount)
    {
        if ($this->isPaid($responseMessage, $transactionType)) {
            return $this->__('Order paid with: %s', $amount);
        }

        if ($this->isReversal($responseMessage, $transactionType)) {
            return $this->__('Order payment refunds returned: %s', $amount);
        }

        return $this->__('Order payment failed with: %s', $amount);
    }

    protected function isStrategyValid($strategyCode, $strategyCallback, $excludeStrategies = [])
    {
        if (! is_callable($strategyCallback)) {
            return false;
        }

        if (in_array($strategyCode, $excludeStrategies)) {
            return false;
        }

        return true;
    }

    protected function getStrategies()
    {
        if ($this->strategies === null) {
            $this->strategies = [];
            $this->strategies['protect_code'] = function() {
                $code = Mage::app()->getRequest()->getParam('order', null);
                Mage::app()->getRequest()->setParam('order', null);

                return $code ? [$code, 'protect_code'] : null;
            };
            $this->strategies['order_id'] = function() {
                $id = Mage::app()->getRequest()->getParam('ORDER', null);
                return $id ? [$id, Mage::getModel('sales/order')->getResource()->getIdFieldName()] : null;
            };
            $this->strategies['session'] = function() {
                return [Mage::getSingleton('checkout/session')->getLastRealOrderId(), 'increment_id'];
            };
        }

        return $this->strategies;
    }
}