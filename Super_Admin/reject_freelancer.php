<?php
$conn = new mysqli("localhost", "root", "", "sales_management");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $freelancer_id = $_POST['freelancer_id'] ?? null;
    if ($freelancer_id) {
        if (isset($_POST['reject'])) {
            // Set the freelancer status to 'rejected' (e.g., 2)
            $update_query = "UPDATE freelancer SET is_approved = 2 WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('i', $freelancer_id);
            $stmt->execute();
            $stmt->close();
            echo "Freelancer rejected successfully!";
        }
    }
}

$conn->close();
?>
