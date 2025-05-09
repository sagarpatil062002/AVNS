<?php
// Start session
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "sales_management";
$conn = new mysqli($host, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the ID is provided
if (isset($_GET['id'])) {
    $quotationId = $_GET['id'];

    // Update the quotation status to 'APPROVED'
    $sql = "UPDATE quotation_header SET status = 'APPROVED', updatedAt = NOW() WHERE quotation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $quotationId);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Quotation ID $quotationId has been successfully approved.";
    } else {
        $_SESSION['message'] = "Error: Could not approve the quotation.";
    }

    $stmt->close();
} else {
    $_SESSION['message'] = "No quotation ID provided.";
}

// Redirect back to the distributor quotations page
header("Location: distributor_quotations.php");
exit;

// Close the database connection
$conn->close();
?>
