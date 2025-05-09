<?php
session_start();
require_once 'config.php';

// Check if payment_id exists in session (more reliable than URL)
if (empty($_SESSION['last_payment_id'])) {
    header('Location: customer_subscription.php');
    exit;
}

// Get payment details
$stmt = $conn->prepare("SELECT cs.*, cp.name as plan_name 
                       FROM customer_subscription cs
                       JOIN customer_plan cp ON cs.plan_id = cp.id
                       WHERE cs.payment_id = ?");
$stmt->bind_param("s", $_SESSION['last_payment_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Log error for debugging
    error_log("No subscription found for payment_id: " . $_SESSION['last_payment_id']);
    $_SESSION['error'] = "Invalid payment ID or subscription not found";
    header('Location: customer_subscription.php');
    exit;
}

$subscription = $result->fetch_assoc();

// Store subscription ID in session for the redirect
$_SESSION['last_subscription_id'] = $subscription['id'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta http-equiv="refresh" content="5;url=view_subscription.php">
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h2 class="text-center">Payment Successful</h2>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    Your subscription has been activated successfully! You will be redirected to your subscription details in 5 seconds.
                </div>
                
                <h4>Subscription Details</h4>
                <table class="table table-bordered">
                    <tr>
                        <th>Plan Name</th>
                        <td><?php echo htmlspecialchars($subscription['plan_name']); ?></td>
                    </tr>
                    <tr>
                        <th>Amount Paid</th>
                        <td>₹<?php echo number_format($subscription['amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <th>Payment ID</th>
                        <td><?php echo htmlspecialchars($subscription['payment_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Validity</th>
                        <td><?php echo $subscription['tenure']; ?> Months</td>
                    </tr>
                    <tr>
                        <th>Start Date</th>
                        <td><?php echo date('d M Y', strtotime($subscription['start_date'])); ?></td>
                    </tr>
                    <tr>
                        <th>End Date</th>
                        <td><?php echo date('d M Y', strtotime($subscription['end_date'])); ?></td>
                    </tr>
                </table>
                
                <div class="text-center mt-4">
                    <a href="view_subscription.php" class="btn btn-primary">View Subscription Now</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>