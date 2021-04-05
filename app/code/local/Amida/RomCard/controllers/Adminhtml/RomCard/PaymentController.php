<?php

class Amida_RomCard_Adminhtml_RomCard_PaymentController extends Mage_Core_Controller_Front_Action
{
    public function chargeAction()
    {
        $order = $this->getOrder();
        $this->process(function() use ($order) {
            $this->_orderHelper()->canProcessPayment($order, true);

            if ($this->getRequest()->has('charge')) {
                $grandTotal = (float)$this->getRequest()->getParam('charge');

                if ($order->getGrandTotal() < $grandTotal) {
                    throw Mage::exception('Amida_RomCard', $this->__('Selected grand total cannot be more then %s', $this->_helper()->formatPrice($order->getGrandTotal())));
                }

                if (0.01 > $grandTotal) {
                    throw Mage::exception('Amida_RomCard', $this->__('Selected grand total cannot be less then %s', 0.01));
                }
            } else {
                $grandTotal = $order->getGrandTotal();
            }

            $order->setPaymentGrandTotal($grandTotal);
            $this->_getaway()->complete($order);
        }, 'The order is paid successfully');
    }

    public function unholdAction()
    {
        $order = $this->getOrder();
        $this->process(function() use ($order) {
            $this->_orderHelper()->canProcessPayment($order, true);
            $this->_getaway()->reversal($order);
        }, 'The transaction is refund successfully');
    }

    /**
     * @return Mage_Sales_Model_Order
     */
    protected function getOrder()
    {
        $order = Mage::getModel('sales/order');
        $order->load($this->getRequest()->getParam('order'));

        return $order;
    }

    /**
     * @return Amida_RomCard_Helper_Getaway
     */
    protected function _getaway()
    {
        return Mage::helper('romcard/getaway');
    }

    /**
     * @return Amida_RomCard_Helper_Order
     */
    protected function _orderHelper()
    {
        return Mage::helper('romcard/order');
    }

    /**
     * @return Amida_RomCard_Helper_Data
     */
    protected function _helper()
    {
        return Mage::helper('romcard');
    }

    protected function process($callback, $successMessage)
    {
        $response['status'] = 'error';

        try {
            call_user_func($callback);
            $response['status'] = 'success';
            $response['message'] = $this->__($successMessage);
        } catch (Mage_Core_Exception $exception) {
            $response['message'] = $exception->getMessage();
        } catch (Exception $exception) {
            Mage::logException($exception);
            $response['message'] = $this->__('The order payment operation is failed');
        }

        $this->getResponse()->setBody(json_encode($response));
        $this->getResponse()->setHeader('Content-Type', 'application/json');
    }
}