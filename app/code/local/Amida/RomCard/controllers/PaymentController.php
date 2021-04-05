<?php

class Amida_RomCard_PaymentController extends Mage_Core_Controller_Front_Action
{
    public function redirectAction()
    {
        $this->processRequest(function() {
            $this->_initQuoteSession();
            $this->_getaway()->authorize($this->_getOrder())->redirect();
        }, 'Payment transaction is created', 'Error while payment transaction creation');
    }

    public function returnAction()
    {
        $this->processRequest(function() {
            $order = $this->_getOrder(['protect_code']);
            $params = $this->getRequest()->getParams();
            $result = $this->_getaway()->finishAuthorize($order, $params, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

            if ($result) {
                $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $this->_data()->getPaymentHoldStatus())->save();
            } else {
                $msg[] = sprintf('Шлюз оплаты сообщил: %s', $params['MESSAGE'] ?: $this->__('Error when payment process'));
                $msg[] = sprintf('Код ошибки (Action Code): %s', $params['ACTION'] ?: 'none');
                $order->addStatusHistoryComment(implode(PHP_EOL, $msg));
            }
            $this->_initQuoteSession();
        }, 'Payment transaction is created','The order payment is failed');
    }

    public function cancelAction()
    {
        try {
            $order = $this->_getOrder(['protect_code']);
            $this->_getaway()->finishAuthorize($order, $this->getRequest()->getParams(), Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);
            $this->_getaway()->reversal($order);
            $this->_initQuoteSession();
        } catch (Exception $exception) {
            Mage::logException($exception);
        }

        $this->_getSession()->addError($this->__('The order payment is failed'));
    }

    protected function processRequest($callback, $successMessage, $errorMessage)
    {
        try {
            call_user_func($callback);
            $this->_getSession()->addSuccess($this->__($successMessage));
            $this->_redirectSuccess(Mage::getUrl('checkout/onepage/success'));
        } catch (Mage_Core_Exception $exception) {
            $this->_getSession()->addError($exception->getMessage());
            $this->_redirectError(Mage::getUrl('checkout/onepage/success'));
        } catch (Exception $exception) {
            $this->_getSession()->addError($this->__($errorMessage));
            $this->_redirectError(Mage::getUrl('checkout/onepage/success'));
            Mage::logException($exception);
        }
    }

    /**
     * @return Amida_RomCard_Helper_Data
     */
    protected function _data()
    {
        return Mage::helper('romcard');
    }

    /**
     * @return Amida_RomCard_Helper_Getaway
     */
    protected function _getaway()
    {
        return Mage::helper('romcard/getaway');
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * @param array $excludeStrategies
     * @return Mage_Sales_Model_Order
     *
     * @throws Amida_RomCard_Exception|Mage_Core_Exception
     */
    protected function _getOrder($excludeStrategies = [])
    {
        return Mage::helper('romcard/order')->getOrder($excludeStrategies);
    }

    protected function _initQuoteSession()
    {
        $this->_getSession()
            ->setLastSuccessQuoteId($this->_getOrder()->getQuoteId())
            ->setLastQuoteId($this->_getOrder()->getQuoteId())
            ->setLastOrderId($this->_getOrder()->getId());
    }
}