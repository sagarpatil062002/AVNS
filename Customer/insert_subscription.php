<?php
session_start(); // Start session management
include 'config.php'; // Include database connection

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logged-in customer ID from session
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
    $sectorId = $_SESSION['sector_id']; // Taking out sector ID
} else {
    die("No customer is logged in. Please log in.");
}

// Fetch subscription plan details from the form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $planId = $_POST['plan_id'];
    $basePrice = $_POST['base_price'];
    $tenure = $_POST['tenure'];

    // Set default subscription status as 'Pending'
    $status = 'Pending';

    // Fetch created_at from the customer_plan table
    $fetchTimestampsQuery = "SELECT created_at FROM customer_plan WHERE id = ?";
    $stmt = $conn->prepare($fetchTimestampsQuery);
    $stmt->bind_param("i", $planId);
    $stmt->execute();
    $timestampResult = $stmt->get_result();

    if ($timestampResult && $timestampResult->num_rows > 0) {
        $row = $timestampResult->fetch_assoc();
        $createdAt = $row['created_at'];
    } else {
        die("Error: Plan details not found for the given plan_id.");
    }

    // Check if the user already has an active or pending subscription
    $checkSubscriptionQuery = "SELECT * FROM customer_subscription WHERE user_id = ? AND status IN ('Pending', 'Approved')";
    $stmt = $conn->prepare($checkSubscriptionQuery);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $subscriptionResult = $stmt->get_result();

    if ($subscriptionResult && $subscriptionResult->num_rows > 0) {
        // User already has a subscription, do not allow another one
        echo "if you are applying for the first time then pay the amount and upload the screenshot.and if you already had subscription and it is expired then go to view_subscription page.";
    } else {
        // Insert subscription data into the customer_subscription table
        $insertQuery = "
            INSERT INTO customer_subscription (user_id, plan_id, tenure, status, created_at)
            VALUES (?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iiiss", $customerId, $planId, $tenure, $status, $createdAt);

        if ($stmt->execute()) {
            // Redirect back to the subscription view page with a success message
            header("Location: view_subscription.php?subscription=success");
            exit(); // Stop further script execution
        } else {
            // Handle error if insertion fails
            echo "Error inserting subscription: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>