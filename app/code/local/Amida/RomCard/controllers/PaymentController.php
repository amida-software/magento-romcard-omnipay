<?php

class Amida_RomCard_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order = null;

    /**
     * @return Amida_RomCard_Helper_Getaway
     */
    protected function _helper()
    {
        return Mage::helper('romcard/getaway');
    }

    /**
     * @return Amida_RomCard_Helper_Data
     */
    protected function _data()
    {
        return Mage::helper('romcard');
    }

    /**
     * @return Mage_Sales_Model_Order
     *
     * @throws Amida_RomCard_Exception|Mage_Core_Exception
     */
    protected function _getOrder()
    {
        if ($this->_order === null) {
            $this->_order = Mage::getModel('sales/order');

            if ($id = $this->getRequest()->getParam('ORDER', null)) {
                $this->_order->loadByIncrementId($id);
            } elseif ($code = $this->getRequest()->getParam('order', null)) {
                $this->_order->load($code, 'protect_code');
            } else {
                $this->_order->loadByIncrementId($this->_getSession()->getLastRealOrderId());
            }

            if (! $this->_order->getId()) {
                throw Mage::exception('Amida_RomCard', $this->__('Invalid order'));
            }

            $this->getRequest()->setParam('order', null);
        }

        return $this->_order;
    }

    /**
     * @return Mage_Checkout_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function processRequest($callback, $successMessage, $errorMessage)
    {
        try {
            call_user_func($callback);
            $this->_getSession()->addSuccess($this->__($successMessage));
            $this->_redirectSuccess(Mage::getUrl('checkout/onepage/success'));
        } catch (Mage_Core_Exception $exception) {
            $this->_getSession()->addError($exception->getMessage());
            $this->_data()->logResponse($exception);
            $this->_redirectError(Mage::getUrl('checkout/onepage/success'));
        } catch (Exception $exception) {
            $this->_getSession()->addError($this->__($errorMessage));
            $this->_data()->logResponse($exception);
            $this->_redirectError(Mage::getUrl('checkout/onepage/success'));
        }
    }

    public function redirectAction()
    {
        if (! $this->getRequest()->isGet() || $this->getRequest()->isAjax()) {
            return $this->norouteAction();
        }

        return $this->processRequest(function() {
            $response = $this->_helper()->purchase($this->_getOrder(), false);

            if ($response->isRedirect()) {
                $response->redirect();
            } else {
                throw Mage::exception('Amida_RomCard', $response->getMessage());
            }
        }, 'Payment transaction is created', 'Error while payment transaction creation');
    }

    public function returnAction()
    {
        $this->processRequest(function() {
            $this->_helper()->finishPurchase($this->_getOrder(), $this->getRequest()->getParams());
            $this->_getSession()
                ->setLastSuccessQuoteId($this->_getOrder()->getQuoteId())
                ->setLastQuoteId($this->_getOrder()->getQuoteId())
                ->setLastOrderId($this->_getOrder()->getId());

            if (! $this->_helper()->isPaid($this->getRequest()->getParam('MESSAGE', null))) {
                throw Mage::exception('Amida_RomCard', $this->__('The order payment is failed'));
            }
        }, 'The order is paid successfully','The order payment is failed');
    }

    public function cancelAction()
    {
        $this->processRequest(function() {
            $this->_helper()->finishPurchase($this->_getOrder(), $this->getRequest()->getParams());
            throw Mage::exception('Amida_RomCard', $this->__('The order payment is failed'));
        }, 'The order payment is failed','The order payment is failed');
    }
}