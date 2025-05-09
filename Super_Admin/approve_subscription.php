<?php
session_start();
include('config.php'); // Include the database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please log in to approve the subscription.");
}

// Check if subscription_id is passed in the URL
if (!isset($_GET['subscription_id'])) {
    die("Invalid subscription ID.");
}

$subscription_id = $_GET['subscription_id'];

// Fetch tenure from the database
$fetchTenureQuery = "SELECT tenure FROM customer_subscription WHERE id = ?";
$stmtFetch = $conn->prepare($fetchTenureQuery);
$stmtFetch->bind_param("i", $subscription_id);
$stmtFetch->execute();
$resultFetch = $stmtFetch->get_result();

if ($resultFetch->num_rows === 0) {
    die("Invalid subscription ID or tenure not found.");
}

$row = $resultFetch->fetch_assoc();
$tenure = (int)$row['tenure']; // Assumes tenure is in months

// Validate tenure
if ($tenure <= 0) {
    die("Invalid tenure value.");
}

// Calculate start_date and end_date
$start_date = date('Y-m-d H:i:s'); // Current timestamp as start_date
$end_date = date('Y-m-d H:i:s', strtotime("+$tenure months")); // Add tenure months

// Update the approval_status, start_date, end_date, and tenure
$updateQuery = "
    UPDATE admin_data 
    SET 
        approval_status = 'approved', 
        start_date = COALESCE(start_date, ?), 
        end_date = ?, 
        tenure = ? 
    WHERE subscription_id = ?
";
$stmtUpdate = $conn->prepare($updateQuery);
$stmtUpdate->bind_param("ssii", $start_date, $end_date, $tenure, $subscription_id);

if ($stmtUpdate->execute()) {
    echo "Subscription approved successfully with tenure and dates updated!";
    // Redirect to subscriptions page
    header("Location: manage_customer_plan.php");
    exit();
} else {
    die("Error approving subscription and updating dates.");
}

// Close statements and connection
$stmtFetch->close();
$stmtUpdate->close();
$conn->close();
?>