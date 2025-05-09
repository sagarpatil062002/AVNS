<?php
session_start();
include('config.php'); // Include the database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please log in to upload an image.");
}

// Check if subscription_id is passed in the URL
if (!isset($_GET['subscription_id'])) {
    die("Invalid subscription ID.");
}

// Get the subscription ID from the URL
$subscription_id = $_GET['subscription_id'];

// Fetch subscription details from the database
$sql = "
    SELECT 
        cs.id, 
        cs.status, 
        cs.tenure, 
        cp.name AS plan_name, 
        cp.base_price, 
        cp.max_support_calls, 
        cp.rewards_points
    FROM 
        customer_subscription cs
    JOIN 
        customer_plan cp ON cs.plan_id = cp.id
    WHERE 
        cs.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $subscription_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Subscription not found.");
}

$subscription = $result->fetch_assoc();

// Initialize variables for subscription details
$plan_name = $subscription['plan_name'];
$approval_status = $subscription['status'];                             // 'pending'; // Default approval status
$base_price = $subscription['base_price'];
$max_support_calls = $subscription['max_support_calls'];
$rewards_points = $subscription['rewards_points'];
$tenure = $subscription['tenure'];

// Handle file upload and transaction ID submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['qr_code'])) {
    $file = $_FILES['qr_code'];
    $transaction_id = $_POST['transaction_id']; // Get transaction ID from the form

    // Validate transaction ID
    if (empty($transaction_id)) {
        die("Transaction ID is required.");
    }

    // Check if the file is uploaded successfully
    if ($file['error'] != 0) {
        die("Error uploading the file.");
    }

    // Set the target directory (Super_Admin/payment)
    $target_dir = "../Super_Admin/payment/";
    $target_file = $target_dir . basename($file['name']);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if file is an image
    if (!getimagesize($file['tmp_name'])) {
        die("File is not an image.");
    }

    // Check file size (limit to 2MB)
    if ($file['size'] > 2000000) {
        die("Sorry, your file is too large.");
    }

    // Allow certain file formats (e.g., jpg, jpeg, png, gif)
    $allowed_formats = array("jpg", "jpeg", "png", "gif");
    if (!in_array($imageFileType, $allowed_formats)) {
        die("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
    }

    // Try to upload the file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Insert into the admin_data table
        $insert_sql = "
            INSERT INTO admin_data (subscription_id, qr_code_path, approval_status, plan_name, transaction_id)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                qr_code_path = VALUES(qr_code_path), 
                approval_status = VALUES(approval_status), 
                plan_name = VALUES(plan_name),
                transaction_id = VALUES(transaction_id)
        ";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("issss", $subscription_id, $target_file, $approval_status, $plan_name, $transaction_id);
        $stmt->execute();

        echo "The file " . htmlspecialchars(basename($file['name'])) . " has been uploaded, and data updated successfully.";
    } else {
        die("Sorry, there was an error uploading your file.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload QR Code</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center mb-4">Upload Screenshot of the Payment done </h1>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="qr_code" class="form-label">Select Screenshot</label>
                <input type="file" class="form-control" id="qr_code" name="qr_code" required>
            </div>
            <div class="mb-3">
                <label for="transaction_id" class="form-label">Transaction ID</label>
                <input type="text" class="form-control" id="transaction_id" name="transaction_id" placeholder="Enter Transaction ID" required>
            </div>
            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Upload Image</button>
            </div>
        </form>

        <div class="mt-4">
            <h3>Subscription Details:</h3>
            <p><strong>Plan Name:</strong> <?php echo htmlspecialchars($plan_name); ?></p>
            <p><strong>Approval Status:</strong> <?php echo htmlspecialchars($approval_status); ?></p>
            <p><strong>Price:</strong> <?php echo htmlspecialchars($base_price); ?></p>
            <p><strong>Support Calls:</strong> <?php echo htmlspecialchars($max_support_calls); ?></p>
            <p><strong>Reward Points:</strong> <?php echo htmlspecialchars($rewards_points); ?></p>
            <p><strong>Tenure:</strong> <?php echo htmlspecialchars($tenure); ?> months</p>
        </div>
    </div>
</body>
</html>