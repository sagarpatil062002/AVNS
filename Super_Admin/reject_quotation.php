<?php
session_start();
include 'config.php'; // Include your database connection

// Check if the quotation ID is passed in the URL
if (isset($_GET['id'])) {
    $quotationId = intval($_GET['id']); // Sanitize the input

    // Update the quotation status to 'REJECTED'
    $sql = "UPDATE Quotation SET status = 'REJECTED' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $quotationId);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Quotation ID $quotationId has been rejected.";
    } else {
        $_SESSION['message'] = "Error rejecting quotation: " . $stmt->error;
    }

    $stmt->close();
} else {
    $_SESSION['message'] = "Invalid quotation ID.";
}

// Redirect back to the Quotation Requests page
header("Location: view_quotation.php");
exit();
?>
