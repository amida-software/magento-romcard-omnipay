<?php
/**
 * @var Mage_Sales_Model_Order_Status $status
 */

/**
 * Status paid
 */
$status = Mage::getModel('sales/order_status');
$status->load(Amida_RomCard_Helper_Data::PAYMENT_STATUS_PAID, 'status');
$status->setStatus(Amida_RomCard_Helper_Data::PAYMENT_STATUS_PAID)
    ->setLabel('Order is paid')
    ->save();
$status->assignState(Mage_Sales_Model_Order::STATE_COMPLETE);
$status->assignState(Mage_Sales_Model_Order::STATE_PROCESSING);

/**
 * Status payment failed
 */
$status = Mage::getModel('sales/order_status');
$status->load(Amida_RomCard_Helper_Data::PAYMENT_STATUS_FAIL, 'status');
$status->setStatus(Amida_RomCard_Helper_Data::PAYMENT_STATUS_FAIL)
    ->setLabel('Order payment is failed')
    ->save();
$status->assignState(Mage_Sales_Model_Order::STATE_COMPLETE);
$status->assignState(Mage_Sales_Model_Order::STATE_CLOSED);
$status->assignState(Mage_Sales_Model_Order::STATE_PROCESSING);