<?php
// Start session
session_start();

// Database connection
include('Config.php');

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if quotation ID and product ID are provided
if (isset($_GET['id']) && isset($_GET['product_id'])) {
    $quotationId = $_GET['id'];
    $productId = $_GET['product_id'];

    // Fetch the existing data for the quotation
    $sql = "
        SELECT qh.quotation_id, qp.quantity, qp.priceOffered, p.name AS product_name
        FROM quotation_header qh
        JOIN quotation_product qp ON qh.quotation_id = qp.quotation_id
        JOIN product p ON qp.productId = p.id
        WHERE qh.quotation_id = ? AND qp.productId = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $quotationId, $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $quotation = $result->fetch_assoc();

    if (!$quotation) {
        $_SESSION['message'] = "Quotation not found.";
        header("Location: distributor_quotations.php");
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle form submission
    $quotationId = $_POST['quotation_id'];
    $productId = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $priceOffered = $_POST['price_offered'];

    // Update the quotation in the database
    $sql = "
        UPDATE quotation_product 
        SET quantity = ?, priceOffered = ? 
        WHERE quotation_id = ? AND productId = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idii", $quantity, $priceOffered, $quotationId, $productId);

    if ($stmt->execute()) {
        $_SESSION['message'] = "Quotation updated successfully.";
    } else {
        $_SESSION['message'] = "Error: Could not update the quotation.";
    }

    // Redirect back to the distributor quotations page
    header("Location: distributor_quotations.php");
    exit;
} else {
    $_SESSION['message'] = "Invalid request.";
    header("Location: distributor_quotations.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quotation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Edit Quotation</h2>

    <form method="POST" action="edit_quotation.php">
        <input type="hidden" name="quotation_id" value="<?php echo htmlspecialchars($quotation['quotation_id']); ?>">
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($productId); ?>">

        <div class="form-group">
            <label for="product_name">Product Name</label>
            <input type="text" id="product_name" class="form-control" value="<?php echo htmlspecialchars($quotation['product_name']); ?>" disabled>
        </div>

        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input type="number" id="quantity" name="quantity" class="form-control" value="<?php echo htmlspecialchars($quotation['quantity']); ?>" required>
        </div>

        <div class="form-group">
            <label for="price_offered">Price Offered</label>
            <input type="text" id="price_offered" name="price_offered" class="form-control" value="<?php echo htmlspecialchars($quotation['priceOffered']); ?>" required>
        </div>

        <button type="submit" class="btn btn-primary">Update Quotation</button>
        <a href="distributor_quotations.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
