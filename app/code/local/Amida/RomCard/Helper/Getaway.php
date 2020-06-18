<?php

class Amida_RomCard_Helper_Getaway extends Mage_Core_Helper_Abstract
{
    const GETAWAY_ACTION_SUCCESS = 0;
    const GETAWAY_ACTION_DUPLICATE = 1;
    const GETAWAY_ACTION_DECLINED = 2;
    const GETAWAY_ACTION_FAULT = 3;

    /**
     * @return Amida_RomCard_Helper_Data
     */
    protected function _data()
    {
        return Mage::helper('romcard');
    }

    /**
     * @return Amida_RomCard_Helper_Transaction
     */
    protected function _transaction()
    {
        return Mage::helper('romcard/transaction');
    }

    /**
     * @return Amida_RomCard_Helper_Order
     */
    protected function _order()
    {
        return Mage::helper('romcard/order');
    }

    /**
     * @return Amida_RomCard_Helper_Logger
     */
    protected function _logger()
    {
        return Mage::helper('romcard/logger');
    }

    /**
     * @return Amida_RomCard_Model_Getaway
     */
    protected function _getaway()
    {
        return Mage::getModel('romcard/getaway');
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return \ByTIC\Omnipay\Romcard\Message\PurchaseResponse|\Omnipay\Common\Message\ResponseInterface
     * @throws Exception|Amida_RomCard_Exception
     */
    public function authorize($order)
    {
        $order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $this->_data()->getNewStatus())->save();
        $request = $this->_getaway()->purchase($this->_data()->generatePurchase($order));
        $response = $this->_logger()->decorateRequest($request, 'PreAuthorize');

        if (! $response->isRedirect()) {
            throw Mage::exception('Amida_RomCard', $response->getMessage());
        }

        return $response;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $responseData
     * @param null|string $transactionType
     *
     * @throws Exception|Amida_RomCard_Exception
     */
    public function finishAuthorize($order, $responseData, $transactionType = null)
    {
        $this->_logger()->logResponse('RedirectBack', $responseData);

        if ($this->_getaway()->supportsCompletePurchase()) {
            $this->_logger()->decorateRequest($this->_getaway()->completePurchase($this->_data()->generateSuccessPurchase($order)), 'FinishAuthorization');
        }

        $this->_order()->complete($order, $responseData);

        if (isset($responseData['INT_REF']) && $transactionType !== null) {
            $this->_transaction()->addTransaction($order, $responseData['INT_REF'], $transactionType);
        }
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $responseData
     *
     * @throws Exception|Amida_RomCard_Exception
     *
     * @return Omnipay\Common\Message\ResponseInterface
     */
    public function completeSales($order, $responseData)
    {
        if (! $this->_order()->isPaid($responseData['ACTION'] ?? null)) {
            return $this->reversal($order, $responseData);
        }

        $this->finishAuthorize($order, $responseData, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

        $request = $this->_getaway()->sale($this->_data()->generateSuccessPurchase($order));
        if ($response = $this->_logger()->decorateRequest($request, 'CompleteSales')) {
            $responseData = $response->getData();

            if (isset($responseData['INT_REF'])) {
                $this->_transaction()->addPaymentTransaction($order, $responseData['INT_REF']);
            }
        }

        return $response;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $responseData
     *
     * @throws Exception|Amida_RomCard_Exception
     */
    public function reversal($order, $responseData)
    {
        $this->finishAuthorize($order, $responseData, Mage_Sales_Model_Order_Payment_Transaction::TYPE_REFUND);

        if ($this->_getaway()->supportsRefund()) {
            $this->_logger()->decorateRequest($this->_getaway()->refund($this->_data()->generateReversalPurchase($order)), 'Reversal');
        }

        throw Mage::exception('Amida_RomCard', $this->__('The order payment is failed'));
    }
}