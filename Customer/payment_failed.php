<?php
require_once('Config.php');
include('CustomerNav.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$error = isset($_GET['error']) ? $_GET['error'] : '';

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
    <title>Payment Failed</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-icon {
            font-size: 72px;
            color: #dc3545;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-icon">✗</div>
            <h2>Payment Failed</h2>
            
            <?php if ($error === 'verification'): ?>
                <p class="text-danger">Payment verification failed. Please try again.</p>
            <?php elseif ($error === 'system'): ?>
                <p class="text-danger">Payment was successful but we encountered a system error. Please contact support.</p>
            <?php else: ?>
                <p class="text-danger">Your payment could not be processed. Please try again.</p>
            <?php endif; ?>
            
            <div class="order-details mt-4">
                <h5>Order Details</h5>
                <p><strong>Order ID:</strong> <?php echo $order['id']; ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y H:i', strtotime($order['updatedAt'])); ?></p>
            </div>
            
            <div class="actions mt-4">
                <a href="view_order.php" class="btn btn-secondary">Back to Orders</a>
                <?php if ($order['payment_status'] === 'failed'): ?>
                    <a href="checkout.php?order_id=<?php echo $order_id; ?>" class="btn btn-primary">Try Again</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>