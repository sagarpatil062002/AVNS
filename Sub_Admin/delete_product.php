<?php
session_start();
include 'config.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Prepare the SQL statement to delete the product
    $stmt = $conn->prepare("DELETE FROM Product WHERE id = ?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        // Set a session variable to indicate success
        $_SESSION['message'] = "Product has been deleted successfully.";
    } else {
        // Set an error message if deletion fails
        $_SESSION['message'] = "Error deleting product: " . $conn->error;
    }

    // Close the statement
    $stmt->close();
}

// Redirect back to the manage products page
header("Location: manage_product.php");
exit();
?>
