<?php

class Amida_RomCard_Adminhtml_RomCard_PaymentController extends Mage_Core_Controller_Front_Action
{
    public function chargeAction()
    {
        $order = $this->getOrder();
        $this->process(function() use ($order) {
            $grandTotal = $this->getRequest()->getParam('charge');

            if (! empty($grandTotal)) {
                if ($order->getGrandTotal() < $grandTotal) {
                    throw Mage::exception('Amida_RomCard', $this->__('Selected grand total cannot be more then %s', $order->getGrandTotal()));
                }

                $order->setPaymentGrandTotal($grandTotal);
            }

            $this->_getaway()->completeSales($order);
        }, 'The order is paid successfully');
    }

    public function unholdAction()
    {
        $order = $this->getOrder();

        $this->process(function() use ($order) {
            $this->_getaway()->reversal($order, $this->getRequest()->getParams());
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