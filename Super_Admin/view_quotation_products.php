<?php
// Start the session
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "sales_management";

$conn = new mysqli($host, $username, $password, $database);

// Get the quotation ID from the GET parameter
if (isset($_GET['quotation_id'])) {
    $quotation_id = $_GET['quotation_id'];
} else {
    die("Quotation ID not provided.");
}

// Fetch products related to the quotation
$productQuery = "
    SELECT p.name AS productName, qp.quantity, qp.priceOffered, tr.tax_percentage AS taxPercentage
    FROM quotation_product qp
    JOIN product p ON qp.productId = p.id
    LEFT JOIN tax_rates tr ON qp.tax_rate_id = tr.id
    WHERE qp.quotation_id = ?
";
$stmt = $conn->prepare($productQuery);
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Quotation Products</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
   

</head>
<body>
<div class="container mt-5">
    <h1>Products in Quotation #<?= htmlspecialchars($quotation_id); ?></h1>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Price Offered</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['productName']); ?></td>
                    <td><?= htmlspecialchars($product['quantity']); ?></td>
                    <td><?= htmlspecialchars($product['priceOffered']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
