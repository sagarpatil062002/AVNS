<?php
session_start();
include('config.php'); // Include the database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please log in to reject the subscription.");
}

// Check if subscription_id is passed in the URL
if (!isset($_GET['subscription_id'])) {
    die("Invalid subscription ID.");
}

$subscription_id = $_GET['subscription_id'];

// Update the approval_status to 'rejected' for the selected subscription
$sql = "UPDATE admin_data SET approval_status = 'rejected' WHERE subscription_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $subscription_id);

if ($stmt->execute()) {
    echo "Subscription rejected successfully!";
    // Optionally, you can redirect the user back to the view subscriptions page
    header("Location: manage_customer_plan.php");
    exit();
} else {
    die("Error rejecting subscription.");
}

$stmt->close();
$conn->close();
?>