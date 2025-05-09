<?php
session_start();
include 'config.php';

// Check if subscription ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['message'] = "No subscription ID provided!";
    header("Location: manage_subscriptions.php");
    exit();
}

$subscriptionId = $_GET['id'];

// Delete the subscription plan
$delete_sql = "DELETE FROM customer_plan WHERE id = ?";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("i", $subscriptionId);

if ($stmt->execute()) {
    $_SESSION['message'] = "Subscription deleted successfully!";
} else {
    $_SESSION['message'] = "Error deleting subscription: " . $stmt->error;
}

$stmt->close();
header("Location: manage_subscriptions.php"); // Redirect back to the manage subscriptions page
exit();
?>
