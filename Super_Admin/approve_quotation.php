<?php
session_start();
include 'config.php'; // Include your database connection

// Check if the quotation ID is passed in the URL
if (isset($_GET['id'])) {
    $quotationId = intval($_GET['id']); // Sanitize the input

    // Update the quotation status to 'APPROVED'
    $sql = "UPDATE Quotation SET status = 'APPROVED' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $quotationId);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Quotation ID $quotationId has been approved.";
    } else {
        $_SESSION['message'] = "Error approving quotation: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['message'] = "Invalid quotation ID.";
}

// Redirect back to the Quotation Requests page
header("Location: view_quotation.php");
exit();
?>
