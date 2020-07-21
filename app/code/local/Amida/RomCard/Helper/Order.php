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

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $responseData
     */
    public function complete($order, $responseData)
    {
        $action = $responseData['ACTION'] ?? null;
        $status = $this->getOrderStatusByResponse($action);
        $comment = $this->getOrderCommentByResponse($action, $responseData['AMOUNT'] ?? $this->__('Cannot get payment amount'));
        $order->setState(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, $status, $comment)->save();
    }

    public function isPaid($status)
    {
        return $status == Amida_RomCard_Helper_Getaway::GETAWAY_ACTION_SUCCESS;
    }

    public function getOrderStatusByResponse($responseMessage)
    {
        if ($this->isPaid($responseMessage)) {
            return $this->_data()->getSuccessStatus();
        }

        return $this->_data()->getFailedStatus();
    }

    public function getOrderCommentByResponse($responseMessage, $amount)
    {
        if ($this->isPaid($responseMessage)) {
            return $this->__('Order paid with: %s', $amount);

        }

        return $this->__('Order payment failed with: %s', $amount);
    }
}