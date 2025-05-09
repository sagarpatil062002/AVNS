<?php
// Start the session
session_start();

// Database connection
include('Config.php');


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
    <style>
        .view-datasheet-btn {
            background-color: #007bff;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-left: 20px;
        }
        .view-datasheet-btn:hover {
            background-color: #0056b3;
            color: white;
            text-decoration: none;
        }
        .header-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="header-container">
        <h1>Products in Quotation #<?= htmlspecialchars($quotation_id); ?></h1>
        <a href="datasheet.php?quotation_id=<?= htmlspecialchars($quotation_id); ?>" class="view-datasheet-btn">View Datasheet</a>
    </div>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Product Name</th>
                <th>Quantity</th>
                <th>Price Offered</th>
                <th>Tax (%)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['productName']); ?></td>
                    <td><?= htmlspecialchars($product['quantity']); ?></td>
                    <td><?= htmlspecialchars($product['priceOffered']); ?></td>
                    <td><?= htmlspecialchars($product['taxPercentage']); ?>%</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
