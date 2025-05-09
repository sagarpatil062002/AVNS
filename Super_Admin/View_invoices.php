<?php
include 'admin_navbar.php';
// Database connection
$host = 'localhost';
$dbname = 'sales_management';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch invoices with additional details
$invoiceQuery = "
    SELECT 
        invoices.id AS invoice_id, 
        CustomerDistributor.companyName AS customer_name, 
        invoices.total_amount, 
        invoices.total_tax, 
        invoices.created_at,
        COUNT(invoice_items.id) AS item_count
    FROM invoices
    INNER JOIN CustomerDistributor ON invoices.customer_id = CustomerDistributor.id
    LEFT JOIN invoice_items ON invoices.id = invoice_items.invoice_id
    GROUP BY invoices.id
    ORDER BY invoices.created_at DESC";
$invoiceResult = $conn->query($invoiceQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Invoice Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <link rel="stylesheet" href="style.css">
<style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .invoice-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 30px;
            margin-left: 180px;
        }
        
        .invoice-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .invoice-title {
            color: var(--secondary-color);
            font-weight: 700;
        }
        
        .badge-items {
            background-color: var(--success-color);
            color: white;
            font-weight: 500;
        }
        
        .table th {
            background-color: var(--light-color);
            border-top: none;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .btn-download {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: all 0.3s;
        }
        
        .btn-download:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(214, 46, 46, 0.1);
        }
        
        .amount-cell {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .date-cell {
            color: #6c757d;
        }
        
        .empty-state {
            padding: 50px 0;
            text-align: center;
        }
        
        .empty-state i {
            font-size: 60px;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="invoice-container">
                <div class="invoice-header d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="invoice-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Invoice Management</h2>
                        <p class="text-muted mb-0">View and manage all customer invoices</p>
                    </div>
                    <div>
                        <span class="badge badge-secondary">
                            <i class="far fa-calendar-alt mr-1"></i> <?= date('F j, Y') ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($invoiceResult && $invoiceResult->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Tax</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($invoice = $invoiceResult->fetch_assoc()): 
                                    $createdDate = new DateTime($invoice['created_at']);
                                    $formattedDate = $createdDate->format('M j, Y');
                                    $formattedTime = $createdDate->format('h:i A');
                                ?>
                                <tr>
                                    <td><strong>#<?= $invoice['invoice_id']; ?></strong></td>
                                    <td><?= htmlspecialchars($invoice['customer_name']); ?></td>
                                    <td><span class="badge badge-items"><?= $invoice['item_count'] ?> items</span></td>
                                    <td class="amount-cell">₹<?= number_format($invoice['total_amount'], 2); ?></td>
                                    <td>₹<?= number_format($invoice['total_tax'], 2); ?></td>
                                    <td class="date-cell">
                                        <small><?= $formattedDate ?><br><?= $formattedTime ?></small>
                                    </td>
                                    <td>
                                        <span class="status-badge status-paid">
                                            <i class="fas fa-check-circle mr-1"></i> Paid
                                        </span>
                                    </td>
                                    <td>
                                        <a href="download_invoice.php?invoice_id=<?= $invoice['invoice_id']; ?>" 
                                           class="btn btn-download btn-sm">
                                            <i class="fas fa-file-pdf mr-2"></i> Download
                                        </a>
                                        
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-invoice"></i>
                        <h4 class="text-muted">No Invoices Found</h4>
                        <p class="text-muted">There are currently no invoices to display</p>
                        <a href="#" class="btn btn-primary mt-3">
                            <i class="fas fa-plus mr-2"></i>Create New Invoice
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();
        
        // Add animation to buttons
        $('.btn-download').hover(
            function() {
                $(this).css('transform', 'translateY(-3px)');
                $(this).css('box-shadow', '0 6px 12px rgba(0,0,0,0.15)');
            },
            function() {
                $(this).css('transform', 'translateY(0)');
                $(this).css('box-shadow', '0 4px 8px rgba(0,0,0,0.1)');
            }
        );
    });
</script>
</body>
</html>