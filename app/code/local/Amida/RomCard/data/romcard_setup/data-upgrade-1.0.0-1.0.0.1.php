<?php
/**
 * @var Mage_Sales_Model_Order_Status $status
 */

/**
 * Status paid
 */
$status = Mage::getModel('sales/order_status');
$status->load(Amida_RomCard_Helper_Data::PAYMENT_STATUS_REVERSAL, 'status');
$status->setStatus(Amida_RomCard_Helper_Data::PAYMENT_STATUS_REVERSAL)
    ->setLabel('Order payment is reversal')
    ->save();
$status->assignState(Mage_Sales_Model_Order::STATE_COMPLETE);
$status->assignState(Mage_Sales_Model_Order::STATE_PROCESSING);