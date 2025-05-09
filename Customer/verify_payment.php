<?php
require_once('Config.php');
require __DIR__ . '/vendor/autoload.php';
use Razorpay\Api\Api;

// Start session to access user data
session_start();

// Replace with your actual Razorpay API keys
$razorpayKeyId = 'rzp_test_7ZhFXaT3z3ethj';
$razorpayKeySecret = 'hCRBftuV7yvlLwvMVeXAW4Fk';

$api = new Api($razorpayKeyId, $razorpayKeySecret);

// Get POST data
$razorpay_payment_id = $_POST['razorpay_payment_id'];
$razorpay_order_id = $_POST['razorpay_order_id'];
$razorpay_signature = $_POST['razorpay_signature'];
$order_id = $_POST['order_id'];
$quotation_id = $_POST['quotation_id'];

// Verify payment signature
$generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $razorpayKeySecret);

if ($generated_signature == $razorpay_signature) {
    // Payment is successful, update your database
    $conn->begin_transaction();
    
    try {
        // Update order payment status
        $stmt = $conn->prepare("UPDATE order_details SET 
            payment_status = 'paid', 
            razorpay_payment_id = ?, 
            razorpay_signature = ?, 
            updatedAt = NOW() 
            WHERE id = ?");
        $stmt->bind_param("ssi", $razorpay_payment_id, $razorpay_signature, $order_id);
        $stmt->execute();
        
        // Update the quotation status
        $stmt = $conn->prepare("UPDATE quotation_header SET status = 'APPROVED' WHERE quotation_id = ?");
        $stmt->bind_param("i", $quotation_id);
        $stmt->execute();
        
        // Generate invoice
        $customerId = $_SESSION['user_id'];
        
        // Get order details with tax information
        $orderQuery = "
            SELECT 
                od.id, od.customerId, od.productId, od.quantity, 
                COALESCE(p.name, od.custom_product_name) AS product_name,
                qp.priceOffered AS price,
                tr.tax_percentage,
                tr.tax_name,
                (od.quantity * qp.priceOffered) AS subtotal,
                (od.quantity * qp.priceOffered * tr.tax_percentage / 100) AS tax_amount,
                (od.quantity * qp.priceOffered * (1 + tr.tax_percentage / 100)) AS total_amount,
                qh.quotation_id
            FROM order_details od
            LEFT JOIN product p ON od.productId = p.id
            LEFT JOIN quotation_product qp ON od.productId = qp.productId
            LEFT JOIN tax_rates tr ON qp.tax_rate_id = tr.id
            LEFT JOIN quotation_header qh ON qp.quotation_id = qh.quotation_id
            WHERE od.id = ? AND od.customerId = ?
            GROUP BY od.id
        ";
        
        $stmt = $conn->prepare($orderQuery);
        $stmt->bind_param("ii", $order_id, $customerId);
        $stmt->execute();
        $orderResult = $stmt->get_result();
        
        if ($orderResult->num_rows === 0) {
            throw new Exception("Order details not found");
        }
        
        $order = $orderResult->fetch_assoc();
        
        // Create invoice header
        $invoiceHeader = "
            INSERT INTO invoices (customer_id, total_amount, total_tax, created_at)
            VALUES (?, ?, ?, NOW())
        ";
        
        $stmt = $conn->prepare($invoiceHeader);
        $totalAmount = $order['total_amount'];
        $totalTax = $order['tax_amount'];
        $stmt->bind_param("idd", $customerId, $totalAmount, $totalTax);
        $stmt->execute();
        $invoiceId = $conn->insert_id;
        
        // Create invoice items
        $invoiceItem = "
            INSERT INTO invoice_items (invoice_id, product_name, quantity, price, total, tax, tax_name)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $conn->prepare($invoiceItem);
        $stmt->bind_param(
            "isiddds", 
            $invoiceId, 
            $order['product_name'], 
            $order['quantity'], 
            $order['price'], 
            $order['subtotal'], 
            $order['tax_amount'], 
            $order['tax_name']
        );
        $stmt->execute();
        
        // Update order with invoice ID (if you have this column)
        // $stmt = $conn->prepare("UPDATE order_details SET invoice_id = ? WHERE id = ?");
        // $stmt->bind_param("ii", $invoiceId, $order_id);
        // $stmt->execute();
        
        // Commit all changes
        $conn->commit();
        
        // Redirect to success page with invoice ID
        header("Location: payment_success.php?order_id=$order_id&invoice_id=$invoiceId");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        // Log the error
        error_log("Payment verification error: " . $e->getMessage());
        
        // Update order as failed due to system error
        $stmt = $conn->prepare("UPDATE order_details SET payment_status = 'failed', updatedAt = NOW() WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        
        // Redirect to failure page with error message
        header("Location: payment_failed.php?order_id=$order_id&error=system");
        exit();
    }
} else {
    // Payment signature verification failed
    $stmt = $conn->prepare("UPDATE order_details SET payment_status = 'failed', updatedAt = NOW() WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    
    // Redirect to failure page
    header("Location: payment_failed.php?order_id=$order_id&error=verification");
    exit();
}
?>