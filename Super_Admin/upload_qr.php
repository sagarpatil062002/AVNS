<?php
// Include database connection
include('config.php');

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $subscription_id = $_POST['subscription_id'];
    $bank_details = $_POST['bank_details'];

    // Handle file upload
    if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] == 0) {
        $target_dir = "uploads/"; // Directory to store uploaded files
        $qr_code_path = $target_dir . basename($_FILES['qr_code']['name']);

        // Ensure directory exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES['qr_code']['tmp_name'], $qr_code_path)) {
            // Insert or update data in admin_data table
            $stmt = $conn->prepare("REPLACE INTO admin_data (subscription_id, qr_code_path, bank_details) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $subscription_id, $qr_code_path, $bank_details);

            if ($stmt->execute()) {
                $message = "QR Code and Bank Details uploaded successfully.";
            } else {
                $message = "Error: " . $stmt->error;
            }
        } else {
            $message = "Failed to upload the QR code.";
        }
    } else {
        $message = "Please select a valid QR code image.";
    }
}

// Fetch subscription ID from URL if set
if (isset($_GET['subscription_id'])) {
    $subscription_id = $_GET['subscription_id'];
} else {
    $subscription_id = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload QR Code</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f4f4; font-family: Arial, sans-serif; }
        .container { margin-top: 50px; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); }
    </style>
</head>
<body>
<div class="container">
    <h2>Upload QR Code</h2>

    <?php if (!empty($message)): ?>
        <div class="alert alert-info"><?php echo $message; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="subscription_id" class="form-label">Subscription ID</label>
            <input type="number" class="form-control" id="subscription_id" name="subscription_id" value="<?php echo htmlspecialchars($subscription_id); ?>" required readonly>
        </div>
        <div class="mb-3">
            <label for="qr_code" class="form-label">Upload QR Code</label>
            <input type="file" class="form-control" id="qr_code" name="qr_code" accept="image/*" required>
        </div>
        <div class="mb-3">
            <label for="bank_details" class="form-label">Bank Details (optional)</label>
            <textarea class="form-control" id="bank_details" name="bank_details" rows="4" placeholder="Enter bank details if necessary..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
</div>
</body>
</html>
