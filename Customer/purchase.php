<?php
// Database connection
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch form data
    $user_id = 2; // Replace with session-based user ID in production
    $plan_id = intval($_POST['plan_id']);
    $tenure = intval($_POST['tenure']);
    $sectorName = intval($_POST['sectorName']); // Optional, if needed for validation

    // Validate input data
    if ($user_id > 0 && $plan_id > 0 && $tenure > 0) {
        // Insert data into customer_subscription table
        $query = "INSERT INTO customer_subscription (user_id, plan_id, tenure, created_at, updated_at) 
                  VALUES (?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $user_id, $plan_id, $tenure);

        if ($stmt->execute()) {
            echo "Subscription successfully purchased!";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Invalid input data.";
    }
} else {
    echo "Invalid request method.";
}
?>
