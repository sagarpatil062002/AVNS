<?php
session_start();
include('Config.php');


if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
} else {
    die("No customer is logged in. Please log in.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['quotation_id'])) {
        $quotationId = $_POST['quotation_id'];
    } else {
        die("Quotation ID is required.");
    }

    // Start a transaction to update all products in the quotation
    $conn->begin_transaction();

    try {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'quantity_') === 0) {
                $productId = substr($key, 9); // Extract productId from the key
                $quantity = $value;
                $priceKey = 'priceOffered_' . $productId;
                $priceOffered = $_POST[$priceKey];

                // Update the quotation product
                $updateQuery = "UPDATE quotation_product SET quantity = ?, priceOffered = ? WHERE quotation_id = ? AND productId = ?";
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param("diii", $quantity, $priceOffered, $quotationId, $productId);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Commit the transaction
        $conn->commit();
        echo "Quotation updated successfully.";
    } catch (Exception $e) {
        // Rollback the transaction if something goes wrong
        $conn->rollback();
        echo "Error updating quotation: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>
