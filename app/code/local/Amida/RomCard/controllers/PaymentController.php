<?php

class Amida_RomCard_PaymentController extends Mage_Core_Controller_Front_Action
{
    /**
     * @var Mage_Sales_Model_Order
     */
    protected $_order = null;
    protected $_orderGetStrategy = [];

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
            $this->_initOrder();
            if (! $this->_order->getId()) {
                throw Mage::exception('Amida_RomCard', $this->__('Invalid order'));
            }

            $this->getRequest()->setParam('order', null);
        }

        return $this->_order;
    }

    protected function _initOrder()
    {
        $this->_order = Mage::getModel('sales/order');

        foreach ($this->_orderGetStrategy as $strategy) {
            if ($this->_order->getId()) {
                continue;
            }

            if ($loadData = call_user_func($strategy)) {
                list($value, $attribute) = $loadData;
                $this->_order->loadByAttribute($attribute, $value);
            }
        }

        return $this->_order;
    }

    protected function _initQuoteSession()
    {
        $this->_getSession()
            ->setLastSuccessQuoteId($this->_getOrder()->getQuoteId())
            ->setLastQuoteId($this->_getOrder()->getQuoteId())
            ->setLastOrderId($this->_getOrder()->getId());
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

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);

        $this->_orderGetStrategy['protect_code'] = function() {
            if ($code = $this->getRequest()->getParam('order', null)) {
                return [$code, 'protect_code'];
            }

            return null;
        };
        $this->_orderGetStrategy['order_id'] = function() {
            if ($id = $this->getRequest()->getParam('ORDER', null)) {
                return [$id, $this->_order->getResource()->getIdFieldName()];
            }

            return null;
        };
        $this->_orderGetStrategy['session'] = function() {
            return [$this->_getSession()->getLastRealOrderId(), 'increment_id'];
        };
    }

    public function redirectAction()
    {
        return $this->processRequest(function() {
            $this->_initQuoteSession();
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
        unset($this->_orderGetStrategy['protect_code']);
        $this->processRequest(function() {
            $this->_helper()->finishPurchase($this->_getOrder(), $this->getRequest()->getParams());
            $this->_initQuoteSession();

            if (! $this->_helper()->isPaid($this->getRequest()->getParam('MESSAGE', null))) {
                throw Mage::exception('Amida_RomCard', $this->__('The order payment is failed'));
            }
        }, 'The order is paid successfully','The order payment is failed');
    }

    public function cancelAction()
    {
        $this->processRequest(function() {
            $this->_helper()->finishPurchase($this->_getOrder(), $this->getRequest()->getParams());
            $this->_initQuoteSession();
            throw Mage::exception('Amida_RomCard', $this->__('The order payment is failed'));
        }, 'The order payment is failed','The order payment is failed');
    }
}