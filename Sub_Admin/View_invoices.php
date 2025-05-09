<?php

include 'navbar.php';
// Database connection
$host = 'localhost';
$dbname = 'sales_management';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch invoices
$invoiceQuery = "
    SELECT 
        invoices.id AS invoice_id, 
        CustomerDistributor.companyName AS customer_name, 
        invoices.total_amount, 
        invoices.total_tax, 
        invoices.created_at 
    FROM invoices
    INNER JOIN CustomerDistributor ON invoices.customer_id = CustomerDistributor.id
    ORDER BY invoices.created_at DESC";
$invoiceResult = $conn->query($invoiceQuery);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Invoices</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">

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
            margin-right:25px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">Invoices</h1>
    <?php if ($invoiceResult && $invoiceResult->num_rows > 0): ?>
        <table class="table table-bordered">
            <thead class="thead-dark">
                <tr>
                    <th>Invoice ID</th>
                    <th>Customer Name</th>
                    <th>Total Amount</th>
                    <th>Total Tax</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($invoice = $invoiceResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= $invoice['invoice_id']; ?></td>
                        <td><?= htmlspecialchars($invoice['customer_name']); ?></td>
                        <td><?= number_format($invoice['total_amount'], 2); ?></td>
                        <td><?= number_format($invoice['total_tax'], 2); ?></td>
                        <td><?= $invoice['created_at']; ?></td>
                        <td>
                            <a href="download_invoice.php?invoice_id=<?= $invoice['invoice_id']; ?>" 
                               class="btn btn-primary btn-sm">Download PDF</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center">No invoices found.</p>
    <?php endif; ?>
</div>
</body>
</html>
