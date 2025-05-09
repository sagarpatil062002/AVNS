<?php
session_start();
require('Config.php');
require('../config/payment_config.php');
require('vendor/autoload.php');

use Razorpay\Api\Api;

if (isset($_POST['order_id']) && isset($_POST['amount'])) {
    $order_id = $_POST['order_id'];
    $amount = $_POST['amount'];
    
    $api = new Api(RAZORPAY_KEY_ID, RAZORPAY_KEY_SECRET);
    
    $orderData = [
        'receipt' => 'order_' . $order_id,
        'amount' => $amount * 100, // Convert to paise
        'currency' => 'INR',
        'payment_capture' => 1
    ];
    
    try {
        $razorpayOrder = $api->order->create($orderData);
        echo json_encode([
            'id' => $razorpayOrder['id'],
            'amount' => $amount
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>