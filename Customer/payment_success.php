<?php
require_once('Config.php');
include('CustomerNav.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// Fetch order details
$orderQuery = "SELECT * FROM order_details WHERE id = ? AND customerId = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows === 0) {
    die("Order not found or access denied");
}

$order = $orderResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="stylescss">

    <style>
        .success-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .success-icon {
            font-size: 72px;
            color: #28a745;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h2>Payment Successful</h2>
            <p>Thank you for your payment. Your order has been processed successfully.</p>
            
            <div class="order-details mt-4">
                <h5>Order Details</h5>
                <p><strong>Order ID:</strong> <?php echo $order['id']; ?></p>
                <p><strong>Payment ID:</strong> <?php echo $order['razorpay_payment_id']; ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y H:i', strtotime($order['updatedAt'])); ?></p>
            </div>
            
            <div class="actions mt-4">
                <a href="view_order.php" class="btn btn-secondary">Back to Orders</a>
                <?php if ($invoice_id): ?>
                    <a href="view_invoices.php?id=<?php echo $invoice_id; ?>" class="btn btn-primary">View Invoice</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>