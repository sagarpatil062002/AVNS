<?php
session_start();
include('Config.php');


if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
} else {
    die("No customer is logged in. Please log in.");
}

if (isset($_GET['id'])) {
    $quotationId = $_GET['id'];
} else {
    die("Quotation ID is required.");
}

// Mark the quotation as accepted
$updateQuery = "UPDATE quotation_header SET status = 'Accepted' WHERE quotation_id = ? AND customerId = ?";
$stmt = $conn->prepare($updateQuery);
$stmt->bind_param("ii", $quotationId, $customerId);

if ($stmt->execute()) {
    echo "Quotation accepted successfully.";
} else {
    echo "Error accepting quotation.";
}

$stmt->close();
$conn->close();
?>
