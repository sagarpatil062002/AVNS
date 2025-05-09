<?php
// Start the session
session_start();
include 'admin_navbar.php';

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "sales_management";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch quotation and product details based on quotation_id
if (isset($_GET['id'])) {
    $quotationId = $_GET['id'];
    $quotationDetailsQuery = "
        SELECT q.quotation_id, q.status, c.companyName AS customer_name, p.name AS product_name, qp.quantity, qp.priceOffered, qp.id AS product_quotation_id
        FROM quotation_header q
        JOIN CustomerDistributor c ON q.customerId = c.id
        JOIN quotation_product qp ON q.quotation_id = qp.quotation_id
        JOIN Product p ON qp.productId = p.id
        WHERE q.quotation_id = ?
    ";

    $stmt = $conn->prepare($quotationDetailsQuery);
    $stmt->bind_param("i", $quotationId);
    $stmt->execute();
    $result = $stmt->get_result();

    $quotation = $result->fetch_all(MYSQLI_ASSOC);
} else {
    echo "<div class='alert alert-danger'>Quotation ID not specified.</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Quotation Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-top: 50px;
        }
        .edit-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
        }
        .edit-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">Quotation Details</h1>
    <?php if ($quotation): ?>
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price Offered</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotation as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['product_name']); ?></td>
                    <td><?= htmlspecialchars($product['quantity']); ?></td>
                    <td><?= htmlspecialchars($product['priceOffered']); ?></td>
                    <td>
                        <a href="edit_quotation.php?quotation_product_id=<?= $product['product_quotation_id']; ?>&quotation_id=<?= $quotationId; ?>" class="edit-btn">Edit</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-danger">No details found for this quotation.</div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>
