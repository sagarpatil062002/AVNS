<?php
// Start the session to access session variables
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

// Fetch customer name (optional)
$customerQuery = "SELECT companyName FROM CustomerDistributor WHERE id = ?";
$stmt = $conn->prepare($customerQuery);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$stmt->bind_result($customerName);
$stmt->fetch();
$stmt->close();

// Fetch invoices for the logged-in customer
$invoiceQuery = "
    SELECT 
        invoices.id AS invoice_id, 
        CustomerDistributor.companyName AS customer_name, 
        invoices.total_amount, 
        invoices.total_tax, 
        invoices.created_at 
    FROM invoices
    INNER JOIN CustomerDistributor ON invoices.customer_id = CustomerDistributor.id
    WHERE invoices.customer_id = ?
    ORDER BY invoices.created_at DESC";
$stmt = $conn->prepare($invoiceQuery);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$invoiceResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Invoices</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --secondary: #f8f9fc;
            --success: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --gray: #858796;
            --gray-light: #dddfeb;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--dark);
        }
        
        .invoice-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-left: 250px;
            transition: all 0.3s ease;
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.75rem;
            border-bottom: 1px solid var(--gray-light);
            padding-bottom: 1rem;
        }
        
        .table {
            margin-top: 1.5rem;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 0.35rem;
            overflow: hidden;
            box-shadow: 0 0 0.5rem rgba(0, 0, 0, 0.05);
        }
        
        .table thead th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
            text-align: center;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid var(--gray-light);
            text-align: center;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            border-radius: 0.35rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-info {
            background-color: #36b9cc;
            border-color: #36b9cc;
        }
        
        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .alert {
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .alert-info {
            background-color: rgba(54, 185, 204, 0.1);
            border-color: #36b9cc;
            color: #36b9cc;
        }
        
        .alert-warning {
            background-color: rgba(246, 194, 62, 0.1);
            border-color: #f6c23e;
            color: #b78a00;
        }
        
        @media (max-width: 992px) {
            .invoice-container {
                margin-left: 0;
                margin-top: 1rem;
                padding: 1.5rem;
            }
            
            .invoice-header {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="invoice-container">
                    <h1 class="invoice-header">
                        <i class="fas fa-file-invoice me-2"></i>My Invoices
                    </h1>

                    <?php if (isset($_GET['message'])): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <?= htmlspecialchars($_GET['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($invoiceResult && $invoiceResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
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
                                            <td><?= htmlspecialchars($invoice['invoice_id']); ?></td>
                                            <td><?= htmlspecialchars($invoice['customer_name']); ?></td>
                                            <td><?= number_format($invoice['total_amount'], 2); ?></td>
                                            <td><?= number_format($invoice['total_tax'], 2); ?></td>
                                            <td><?= date('M d, Y h:i A', strtotime($invoice['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="view_invoice_details.php?invoice_id=<?= $invoice['invoice_id']; ?>" 
                                                       class="btn btn-primary">
                                                        <i class="fas fa-eye me-1"></i> View Details
                                                    </a>
                                                    <a href="download_invoice.php?invoice_id=<?= $invoice['invoice_id']; ?>" 
                                                       class="btn btn-danger">
                                                        <i class="fas fa-file-pdf me-1"></i> Download
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice-dollar fa-3x mb-3" style="color: var(--gray);"></i>
                            <h4>No Invoices Found</h4>
                            <p>You don't have any invoices yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>

<?php
// Close the database connection
$stmt->close();
$conn->close();
?>