<?php
// Ensure no output before headers
ob_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1);

// Database connection
require_once('Config.php');

// Include Razorpay SDK
require __DIR__ . '/vendor/autoload.php';
use Razorpay\Api\Api;

// Set headers first
header('Content-Type: application/json');

// Replace with your actual Razorpay API keys
$razorpayKeyId = 'rzp_test_7ZhFXaT3z3ethj';
$razorpayKeySecret = 'hCRBftuV7yvlLwvMVeXAW4Fk';

try {
    // Verify database connection
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get POST data
    $json = file_get_contents('php://input');
    if (empty($json)) {
        throw new Exception("No input data received");
    }

    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input: " . json_last_error_msg());
    }

    $orderId = $data['order_id'] ?? null;
    $amount = $data['amount'] ?? null;
    $quotationId = $data['quotation_id'] ?? null;

    if (!$orderId || !$amount || !$quotationId) {
        throw new Exception("Missing required parameters");
    }

    // Initialize Razorpay API
    $api = new Api($razorpayKeyId, $razorpayKeySecret);

    // Create Razorpay order
    $razorpayOrder = $api->order->create([
        'amount' => $amount,
        'currency' => 'INR',
        'receipt' => 'order_'.$orderId,
        'payment_capture' => 1,
        'notes' => [
            'quotation_id' => $quotationId
        ]
    ]);
    
    // Update database
    $amountInRupees = $amount / 100;
    $stmt = $conn->prepare("UPDATE order_details SET razorpay_order_id = ?, amount = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("sdi", $razorpayOrder->id, $amountInRupees, $orderId);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'razorpay_order_id' => $razorpayOrder->id,
        'amount' => $amountInRupees
    ]);

} catch (Exception $e) {
    // Log the error
    error_log("Razorpay Order Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} finally {
    // Clean up
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
    ob_end_flush();
}
?>