<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require_once 'config.php';

use Razorpay\Api\Api;

// Verify required parameters
if (empty($_GET['payment_id']) || empty($_GET['order_id']) || empty($_GET['signature'])) {
    die("Invalid payment verification data");
}

// Initialize Razorpay
$razorpayKeyId = 'rzp_test_7ZhFXaT3z3ethj';
$razorpayKeySecret = 'hCRBftuV7yvlLwvMVeXAW4Fk';
$api = new Api($razorpayKeyId, $razorpayKeySecret);

try {
    // Verify payment signature
    $attributes = [
        'razorpay_order_id' => $_GET['order_id'],
        'razorpay_payment_id' => $_GET['payment_id'],
        'razorpay_signature' => $_GET['signature']
    ];
    $api->utility->verifyPaymentSignature($attributes);

    // Get data from session
    if (!isset($_SESSION['payment_data'])) {
        die("Session data missing");
    }

    $data = $_SESSION['payment_data'];
    
    // Insert subscription into database
    $stmt = $conn->prepare("INSERT INTO customer_subscription 
                          (user_id, plan_id, start_date, end_date, status, payment_id, amount, tenure) 
                          VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL ? MONTH), 'Approved', ?, ?, ?)");
    $stmt->bind_param("iisdii", 
        $data['customer_id'],
        $data['plan_id'],
        $data['tenure'],
        $_GET['payment_id'],
        $data['amount'],
        $data['tenure']
    );

    if ($stmt->execute()) {
        // Clear session data
        unset($_SESSION['razorpay_order_id']);
        unset($_SESSION['payment_data']);
        
        // Redirect to success page
        header("Location: Subscription_payment_success.php?payment_id=" . $_GET['payment_id']);
        exit();
    } else {
        die("Database error: Failed to create subscription");
    }

} catch (Exception $e) {
    die("Payment verification failed: " . $e->getMessage());
}
?>