<?php
// Database connection
include('Config.php');


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if 'id' parameter is provided
if (isset($_GET['id'])) {
    $orderDetailId = intval($_GET['id']); // Sanitize input

    // Prepare and execute the delete query
    $sql = "DELETE FROM order_details WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderDetailId);

    if ($stmt->execute()) {
        echo "<script>
            alert('Order deleted successfully.');
            window.location.href = 'manage_order.php'; // Redirect to the orders page
        </script>";
    } else {
        echo "<script>
            alert('Error deleting order: " . $conn->error . "');
            window.location.href = 'manage_order.php';
        </script>";
    }

    $stmt->close();
} else {
    echo "<script>
        alert('Invalid request.');
        window.location.href = 'manage_order.php';
    </script>";
}

$conn->close();
?>
