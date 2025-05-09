<?php
session_start();
include('CustomerNav.php');

// Check if the customer is logged in
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
} else {
    die("No customer is logged in. Please log in.");
}

include('Config.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch invoice details
$invoice_id = $_GET['invoice_id'];
$invoiceQuery = "
    SELECT 
        invoices.*, 
        CustomerDistributor.companyName, 
        CustomerDistributor.address, 
        CustomerDistributor.gstNo, 
        CustomerDistributor.mobileNo
    FROM invoices
    INNER JOIN CustomerDistributor ON invoices.customer_id = CustomerDistributor.id
    WHERE invoices.id = '$invoice_id' AND invoices.customer_id = '$customerId'";
$invoiceResult = $conn->query($invoiceQuery);

if ($invoiceResult->num_rows === 0) {
    die("Invoice not found or access denied");
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoice_id; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
        .invoice-container { max-width: 800px; margin: 30px auto; padding: 20px; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .invoice-header { border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .invoice-title { color: #333; }
        .invoice-details { margin-top: 20px; }
        .table { width: 100%; margin-bottom: 20px; }
        .table th { background-color: #f8f9fa; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; font-size: 1.1em; }
        
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            font-size: 12pt;
            background: white;
        }
        .invoice-container {
            box-shadow: none;
            border: 1px solid #ddd;
        }
        .table {
            page-break-inside: avoid;
        }
    }
    </style>
</head>
<body>
    <div class="container invoice-container">
        <div class="row invoice-header">
            <div class="col-md-6">
                <h2 class="invoice-title">INVOICE</h2>
                <p><strong>Invoice #:</strong> <?php echo $invoice_id; ?></p>
                <p><strong>Date:</strong> <?php echo date('d M Y', strtotime($invoice['created_at'])); ?></p>
            </div>
            <div class="col-md-6 text-right">
                <h4><?php echo htmlspecialchars($invoice['companyName']); ?></h4>
                <p><?php echo htmlspecialchars($invoice['address']); ?></p>
                <?php if ($invoice['gstNo']): ?>
                    <p><strong>GSTIN:</strong> <?php echo htmlspecialchars($invoice['gstNo']); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Tax</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        while ($item = $itemResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($item['tax_name']); ?> (₹<?php echo number_format($item['tax'], 2); ?>)</td>
                                <td>₹<?php echo number_format($item['total'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4"></td>
                            <td><strong>Subtotal:</strong></td>
                            <td>₹<?php echo number_format($invoice['total_amount'] - $invoice['total_tax'], 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="4"></td>
                            <td><strong>Tax:</strong></td>
                            <td>₹<?php echo number_format($invoice['total_tax'], 2); ?></td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="4"></td>
                            <td><strong>Total:</strong></td>
                            <td>₹<?php echo number_format($invoice['total_amount'], 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="row no-print">
            <div class="col-md-12 text-center mt-4">
                <a href="download_invoice.php?invoice_id=<?php echo $invoice_id; ?>" class="btn btn-success">Download PDF</a>
                <a href="view_order.php" class="btn btn-secondary">Back to Orders</a>
            </div>
        </div>
    </div>
</body>
</html>