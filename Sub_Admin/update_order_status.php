<?php
session_start();
include 'config.php';

// Ensure sub-admin access
if ($_SESSION['role'] != 'SUB_ADMIN') {
    header('Location: dashboard.php');
    exit;
}

// Check if 'id' is passed in the URL and is a valid integer
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Invalid order ID!";
    exit;
}

$order_id = intval($_GET['id']); // Safely convert 'id' to an integer

// Fetch order details, including product name from OrderItem and Product tables
$stmt = $conn->prepare("
    SELECT o.id, p.name AS product_name, o.status, o.customerId, oi.quantity 
    FROM `Order` o
    JOIN OrderItem oi ON o.id = oi.orderId
    JOIN Product p ON oi.productId = p.id
    WHERE o.id = ?
");
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    // Handle case if order not found
    echo "Order not found!";
    exit;
}

// Handle form submission for updating order status
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_status = $_POST['status'];

    // Update the order status
    $stmt = $conn->prepare("UPDATE `Order` SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $new_status, $order_id);
    
    if ($stmt->execute()) {
        // Send notification to the customer
        $customer_id = $order['customerId'];
        $message = "Your order status for " . $order['product_name'] . " has been updated to " . $new_status;
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->bind_param('is', $customer_id, $message);
        $stmt->execute();

        header('Location: manage_orders.php');
    } else {
        $error = "Failed to update order!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Order Status</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h3>Update Order Status</h3>
    <form method="POST">
        <div class="form-group">
            <label>Product</label>
            <input type="text" class="form-control" value="<?= $order['product_name'] ?>" disabled>
        </div>
        <div class="form-group">
            <label>Quantity</label>
            <input type="text" class="form-control" value="<?= $order['quantity'] ?>" disabled>
        </div>
        <div class="form-group">
            <label>Current Status</label>
            <input type="text" class="form-control" value="<?= $order['status'] ?>" disabled>
        </div>
        <div class="form-group">
            <label>Update Status</label>
            <select name="status" class="form-control">
                <option value="PENDING" <?= $order['status'] == 'PENDING' ? 'selected' : '' ?>>Pending</option>
                <option value="IN_PROCESS" <?= $order['status'] == 'IN_PROCESS' ? 'selected' : '' ?>>In Process</option>
                <option value="SHIPPED" <?= $order['status'] == 'SHIPPED' ? 'selected' : '' ?>>Shipped</option>
                <option value="DELIVERED" <?= $order['status'] == 'DELIVERED' ? 'selected' : '' ?>>Delivered</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update Status</button>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    </form>
</div>
</body>
</html>
