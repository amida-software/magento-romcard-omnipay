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
        $response = $this->_logger()->decorateRequest($request, 'Authorize');

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

        if (isset($responseData['INT_REF']) && $transactionType !== null) {
            $this->_transaction()->addTransaction($order, $responseData['INT_REF'], $transactionType);
            $order->getPayment()->setAdditionalData(json_encode($responseData))->save();

            $amount = $responseData['AMOUNT'] ?? $order->getGrandTotal();
            $order->getPayment()->setBaseAmountAuthorized($amount);
            $order->getPayment()->setAmountAuthorized($amount);
            $order->setPaymentAuthorizationAmount($amount);
            $order->save();
            return true;
        }

        return  false;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @throws Exception|Amida_RomCard_Exception
     *
     * @return Omnipay\Common\Message\ResponseInterface
     */
    public function complete($order)
    {
        $responseData = json_decode($order->getPayment()->getAdditionalData(), true);

        if (Amida_RomCard_Helper_Getaway::GETAWAY_ACTION_SUCCESS != $responseData['ACTION'] ?? null) {
            throw Mage::exception('Amida_RomCard', $this->__('The order payment is failed'));
        }

        $requestData = $this->_data()->generateSuccessPurchase($order);
        $partialRefund = $requestData['amount'];
        $reversalAmount = $order->getGrandTotal() - $partialRefund;
        $requestData['amount'] = $order->getGrandTotal();

        if ($response = $this->_logger()->decorateRequest($this->_getaway()->sale($requestData), 'Complete')) {
            $responseData = $response->getData();
            $responseData = $responseData['input_values'];
            list($action, $transactionType) = $this->_data()->parseStatus($responseData);

            if (! $this->_order()->isPaid($action, $transactionType)) {
                throw Mage::exception('Amida_RomCard', $this->__($responseData['MESSAGE'] ?? 'The order payment is failed'));
            }

            $this->_transaction()->addPaymentTransaction($order, $responseData['j']);

            $paidAmount = $requestData['amount'];
            $order->getPayment()->setAmountPaid($paidAmount);
            $order->getPayment()->setBaseAmountPaid($paidAmount);
            $order->getPayment()->setBaseAmountPaidOnline($paidAmount);
            $order->setTotalDue($reversalAmount);
            $order->setBaseTotalDue($reversalAmount);
            $order->setTotalPaid($paidAmount);
            $order->setBaseTotalPaid($paidAmount);
            $order->save();

            if ($reversalAmount > 0) {
                $this->reversal($order, $partialRefund);
            }

            $this->_order()->complete($order, $responseData);
        }

        return $response;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param $reversal
     *
     * @throws Exception|Amida_RomCard_Exception
     */
    public function reversal($order, $reversal = null)
    {
        $requestData = $this->_data()->generateReversalPurchase($order);

        if ($reversal !== null) {
            $requestData['amount'] = $reversal;
            $requestData['transaction_type'] = Amida_RomCard_Helper_Data::PAYMENT_TRANSACTION_REVERSAL_PARTIAL;
        }

        $requestData['amount'] = $this->_data()->formatPrice($requestData['amount']);

        if ($response = $this->_logger()->decorateRequest($this->_getaway()->refund($requestData), 'Reversal')) {
            $responseData = $response->getData();
            $responseData = $responseData['input_values'];
            list($action, $transactionType) = $this->_data()->parseStatus($responseData);

            if (! $this->_order()->isReversal($action, $transactionType)) {
                throw Mage::exception('Amida_RomCard', $this->__($responseData['MESSAGE'] ?? 'The order payment is failed'));
            }

            $this->_transaction()->addRefundTransaction($order, $responseData['j'] . '_refund');

            $reversalAmount = $requestData['amount'];
            $order->getPayment()->setAmountRefunded($reversalAmount);
            $order->getPayment()->setBaseAmountRefunded($reversalAmount);
            $order->getPayment()->setBaseAmountRefundedOnline($reversalAmount);

            $order->setTotalDue($reversalAmount);
            $order->setBaseTotalDue($reversalAmount);
            $order->setTotalRefunded($reversalAmount);
            $order->setBaseTotalRefunded($reversalAmount);
            $order->setBaseTotalOnlineRefunded($reversalAmount);
            $order->setBaseSubtotalRefunded($reversalAmount);
            $order->save();

            $this->_order()->complete($order, $responseData);

            return;
        }

        throw Mage::exception('Amida_RomCard', $this->__('The order payment is failed'));
    }
}