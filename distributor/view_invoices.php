<?php
// Start the session to access session variables
session_start();

// Check if the customer is logged in
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
} else {
    die("No customer is logged in. Please log in.");
}

// Database connection
include('Config.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch invoices for the logged-in customer
$invoiceQuery = "
    SELECT 
        invoices.id AS invoice_id, 
        distributor.companyName AS customer_name, 
        invoices.total_amount, 
        invoices.total_tax, 
        invoices.created_at 
    FROM invoices
    INNER JOIN distributor ON invoices.customer_id = distributor.id
    WHERE invoices.customer_id = '$customerId'
    ORDER BY invoices.created_at DESC";
$invoiceResult = $conn->query($invoiceQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <title>View Invoices</title>
    <style>
        body {
            background: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        text-align: center;
    }
    </style>
</head>
<body>
<nav>
    <div class="logo-name">
        <div class="logo-image">
           <img src="#" alt="">
        </div>
        <span class="logo_name">AVNS</span>
    </div>
    <div class="menu-items">
        <ul class="nav-links">
            <li><a href="dashboard.php">
                <i class="uil uil-estate"></i>
                <span class="link-name">Dashboard</span>
            </a></li>
            <li><a href="view_order.php">
                <i class="uil uil-clipboard"></i>
                <span class="link-name">Order</span>
            </a></li>
            <li><a href="view_Adminquotation.php">
                <i class="uil uil-laptop"></i>
                <span class="link-name">Quotation</span>
            </a></li>
            <li><a href="view_invoices.php">
                <i class="uil uil-laptop"></i>
                <span class="link-name">Invoices</span>
            </a></li>
            <li><a href="profile.php">
                <i class="uil uil-laptop"></i>
                <span class="link-name">Profile</span>
            </a></li>
        </ul>
        <ul class="logout-mode">
            <li><a href="../logout.php">
                <i class="uil uil-signout"></i>
                <span class="link-name">Logout</span>
            </a></li>
            <li>
              <div class="mode-toggle">
                <span class="switch"></span>
              </div>
            </li>
        </ul>
    </div>
</nav>

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
                            <a href="view_invoice_details.php?invoice_id=<?= $invoice['invoice_id']; ?>" 
                               class="btn btn-primary btn-sm">View Details</a>
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
