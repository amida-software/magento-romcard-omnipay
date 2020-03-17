<?php

class Amida_RomCard_Helper_Data extends Mage_Core_Helper_Data
{
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_FAIL = 'payment_failed';

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

    public function getSuccessStatus()
    {
        return $this->getConfig('order_status_success');
    }

    public function getFailedStatus()
    {
        return $this->getConfig('order_status_failed');
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

            $products = array_map(function($product) {
                return "\"{$product}\"";
            }, $products);
            $products = implode(', ', $products);
            $filter->setVariables(['products' => $products, 'order' => $order]);
            $template = $filter->filter($template);
        }

        return $template;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    public function generatePurchase($order)
    {
        $purchase = $this->getMerchantAuthData();
        $purchase['order_id'] = $order->getId();
        $purchase['amount'] = $order->getGrandTotal();
        $purchase['currency'] = Mage::app()->getStore()->getCurrentCurrency()->getCurrencyCode();
        $purchase['description'] = $this->getDescription($order);

        return $purchase;
    }

    public function getMerchantAuthData()
    {
        $auth['merchant_name'] = $this->getMerchantName();
        $auth['merchant_url'] = $this->getMerchantUrl();
        $auth['merchant'] = $this->getMerchantId();
        $auth['terminal'] = $this->getTerminalId();
        $auth['returnUrl'] = Mage::getUrl('romcard/payment/return', ['_secure' => true, '_current' => true]);
        $auth['cancelUrl'] = Mage::getUrl('romcard/payment/cancel', ['_secure' => true, '_current' => true]);
        $auth['merchant_email'] = $this->getMerchantEmail();
        $auth['key'] = $this->getSaltKey();

        return $auth;
    }

    public function getLogFile()
    {
        return 'romcard.log';
    }

    public function logEnabled()
    {
        return $this->getConfig('log_enabled');
    }

    /**
     * @var ByTIC\Omnipay\Romcard\Message\AbstractRequest $request
     */
    public function logRequest($request)
    {
        $data = $request->getData();
        $data = ! is_string($data) ? json_encode($data) : $data;
        $this->log("REQUEST. url: {$request->getEndpointUrl()},
data: $data");
    }

    /**
     * @var array|ByTIC\Omnipay\Romcard\Message\AbstractResponse|\Exception $response
     */
    public function logResponse($response)
    {
        if (is_array($response)) {
            $data = $response;
        } else {
            $data = $response instanceof Exception ? $response->getMessage() : $response->getData();
        }

        $data = ! is_string($data) ? json_encode($data) : $data;
        $this->log("RESPONSE. {$data}");
    }

    public function log($message)
    {
        if (! $this->logEnabled()) {
            return;
        }

        Mage::log($message, null, $this->getLogFile());
    }
}