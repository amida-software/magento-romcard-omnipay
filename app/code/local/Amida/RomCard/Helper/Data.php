<?php

class Amida_RomCard_Helper_Data extends Mage_Core_Helper_Data
{
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_FAIL = 'payment_failed';
    const PAYMENT_STATUS_REVERSAL = 'payment_reversal';
    const PAYMENT_STATUS_REFUNDS_ON_HOLD = 'payment_refunds_on_hold';

    const PAYMENT_TRANSACTION_AUTHORIZE = 0;
    const PAYMENT_TRANSACTION_COMPLETE_SALES = 21;
    const PAYMENT_TRANSACTION_REVERSAL = 24;
    const PAYMENT_TRANSACTION_REVERSAL_PARTIAL = 14;

    protected $paymentMethodModel = null;

    public function isDevelop()
    {
        return Mage::getIsDeveloperMode() || $this->getConfig('is_dev');
    }

    /**
     * @return Amida_RomCard_Model_PaymentMethod
     */
    public function getPaymentMethodModel()
    {
        if ($this->paymentMethodModel === null) {
            $this->paymentMethodModel = Mage::getModel('romcard/paymentMethod');
        }

        return $this->paymentMethodModel;
    }

    public function getConfig($field)
    {
        return $this->getPaymentMethodModel()->getConfigData($field, Mage::app()->getStore()->getId());
    }

    public function getMerchantId()
    {
        return $this->getConfig('merchant_id');
    }

    public function getTerminalId()
    {
        return $this->getConfig('terminal_id');
    }

    public function getMerchantName()
    {
        return $this->getConfig('merchant_name');
    }

    public function getSaltKey()
    {
        return $this->getConfig('salt_key');
    }

    public function getMerchantUrl()
    {
        $url = $this->getConfig('merchant_url');
        $url = str_replace('https', '', $url);
        $url = str_replace('http', '', $url);
        $url = 'http://' . $url;

        return $url;
    }

    public function getMerchantEmail()
    {
        return trim(Mage::getStoreConfig('trans_email/ident_general/email'));
    }

    public function getNewStatus()
    {
        return $this->getConfig('order_status_new');
    }

    public function getPaymentHoldStatus()
    {
        return $this->getConfig('order_status_hold');
    }

    public function getSuccessStatus()
    {
        return $this->getConfig('order_status_success');
    }

    public function getFailedStatus()
    {
        return $this->getConfig('order_status_failed');
    }

    public function getReversalStatus()
    {
        return $this->getConfig('order_status_reversal');
    }

    /**
     * @param Mage_Sales_Model_Order|null $order
     * @return mixed
     */
    public function getDescription($order = null)
    {
        $template = $this->getConfig('description');

        if ($order !== null) {
            $filter = new Varien_Filter_Template();
            $products = [];

            foreach ($order->getAllVisibleItems() as $item) {
                $products[] = $item->getName();
            }

            $products = implode(', ', $products);
            $filter->setVariables(['products' => $products, 'order' => $order]);
            $template = $filter->filter($template);
        }

        $description = preg_replace('/[^a-zа-я0-9\s\.\-\_]/ui', '', $template);
        $description = mb_substr($description, 0, 50);

        return $description;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $unsetFields
     * @return mixed
     */
    public function generatePurchase($order, $unsetFields = [])
    {
        $purchase = $this->getMerchantAuthData();
        $purchase['order_id'] = $order->getId();
        $purchase['amount'] = $order->getPaymentGrandTotal() ? $order->getPaymentGrandTotal() : $order->getGrandTotal();
        $purchase['amount'] = $this->formatPrice($purchase['amount']);
        $purchase['currency'] = 'MDL'; // Mage::app()->getStore()->getCurrentCurrency()->getCurrencyCode();
        $purchase['description'] = $this->getDescription($order);

        foreach ($unsetFields as $fieldName) {
            unset($purchase[$fieldName]);
        }

        return $purchase;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    public function generateSuccessPurchase($order)
    {
        $additionalData = json_decode($order->getPayment()->getAdditionalData(), true);
        $purchase = $this->generatePurchase($order);
        $purchase['cardReference'] = $additionalData['RRN'];
        $purchase['transactionReference'] = $additionalData['INT_REF'];

        return $purchase;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    public function generateReversalPurchase($order)
    {
        $additionalData = json_decode($order->getPayment()->getAdditionalData(), true);
        $purchase = $this->generatePurchase($order);
        $purchase['cardReference'] = $additionalData['RRN'];
        $purchase['transactionReference'] = $additionalData['INT_REF'];
        $purchase['transaction_type'] = Amida_RomCard_Helper_Data::PAYMENT_TRANSACTION_REVERSAL;

        return $purchase;
    }

    public function getMerchantAuthData()
    {
        $auth['merchant_name'] = $this->getMerchantName();
        $auth['merchant_url'] = $this->getMerchantUrl();
        $auth['merchant'] = $this->getMerchantId();
        $auth['terminal'] = $this->getTerminalId();
        $auth['returnUrl'] = Mage::getUrl('romcard/payment/return', ['_secure' => true]);
        $auth['cancelUrl'] = Mage::getUrl('romcard/payment/cancel', ['_secure' => true]);
        $auth['merchant_email'] = $this->getMerchantEmail();
        $auth['key'] = $this->getSaltKey();

        return $auth;
    }

    public function formatPrice($price, $includeContainer = true)
    {
        return number_format($price, 2, '.', '');
    }

    public function parseStatus($responseData)
    {
        $action = $responseData['ACTION'] ?? $responseData['b'] ?? Amida_RomCard_Helper_Getaway::GETAWAY_ACTION_FAULT;
        $transactionType = $responseData['TRTYPE'] ?? $responseData['e'] ?? null;

        return [$action, $transactionType];
    }
}