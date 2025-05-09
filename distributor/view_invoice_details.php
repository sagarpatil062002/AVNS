<?php
session_start();

// Check if the distributor is logged in
if (isset($_SESSION['user_mail'])) {
    $distributorEmail = $_SESSION['user_mail']; // Logged-in distributor email
} else {
    die("No distributor is logged in. Please log in.");
}

// Database connection
include('Config.php');


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch distributor details from the distributor table using the email
$distributorQuery = "
    SELECT id, companyName FROM distributor WHERE mailId = '$distributorEmail'";
$distributorResult = $conn->query($distributorQuery);
$distributor = $distributorResult->fetch_assoc();

// Fetch invoice details
$invoice_id = $_GET['invoice_id'];
$invoiceQuery = "
    SELECT 
        invoices.id AS invoice_id, 
        CustomerDistributor.companyName AS customer_name, 
        invoices.total_amount, 
        invoices.total_tax, 
        invoices.created_at 
    FROM invoices
    INNER JOIN CustomerDistributor ON invoices.customer_id = CustomerDistributor.id
    WHERE invoices.id = '$invoice_id' AND invoices.distributor_id = '{$distributor['id']}'"; // Ensure invoices belong to the distributor
$invoiceResult = $conn->query($invoiceQuery);
$invoice = $invoiceResult->fetch_assoc();

// Fetch invoice items
$itemQuery = "
    SELECT 
        product_name, 
        quantity, 
        price, 
        total, 
        tax, 
        tax_name 
    FROM invoice_items
    WHERE invoice_id = '$invoice_id'";
$itemResult = $conn->query($itemQuery);

// Calculate total invoice amount (Total Amount + Total Tax)
$totalInvoiceAmount = $invoice['total_amount'] + $invoice['total_tax'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Invoice Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
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
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">Invoice Details</h1>
    <?php if ($invoice): ?>
        <h4>Invoice ID: <?= $invoice['invoice_id']; ?></h4>
        <h4>Customer Name: <?= htmlspecialchars($invoice['customer_name']); ?></h4>
        <h4>Total Amount: <?= number_format($invoice['total_amount'], 2); ?></h4>
        <h4>Total Tax: <?= number_format($invoice['total_tax'], 2); ?></h4>
        <h4>Total Invoice Amount (Amount + Tax): <?= number_format($totalInvoiceAmount, 2); ?></h4>
        <h4>Created At: <?= $invoice['created_at']; ?></h4>
        
        <h3 class="mt-4">Invoice Items</h3>
        <?php if ($itemResult && $itemResult->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Tax</th>
                        <th>Total</th>
                        <th>Tax Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $itemResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']); ?></td>
                            <td><?= $item['quantity']; ?></td>
                            <td><?= number_format($item['price'], 2); ?></td>
                            <td><?= number_format($item['tax'], 2); ?></td>
                            <td><?= number_format($item['total'], 2); ?></td>
                            <td><?= htmlspecialchars($item['tax_name']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No items found for this invoice.</p>
        <?php endif; ?>

        <!-- Button to download invoice as PDF -->
        <a href="download_invoice.php?invoice_id=<?= $invoice['invoice_id']; ?>" class="btn btn-primary">Download Invoice PDF</a>
    <?php else: ?>
        <p class="text-center">Invoice not found.</p>
    <?php endif; ?>
</div>
</body>
</html>

<?php
// Close the connection
$conn->close();
?>
