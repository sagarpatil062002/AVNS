<?php
ob_start(); // Start output buffering
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "admin_navbar.php";

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "sales_management";

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Debug form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<pre>POST data: ";
    print_r($_POST);
    echo "</pre>";
}

// Fetch all quotations with customer and product details, grouped by quotation_id
$quotationsQuery = "
    SELECT q.quotation_id AS quotation_id, q.status, c.companyName AS customer_name, 
           p.name AS product_name, qp.quantity, qp.priceOffered, q.createdAt
    FROM quotation_header q
    JOIN CustomerDistributor c ON q.customerId = c.id
    JOIN quotation_product qp ON q.quotation_id = qp.quotation_id
    JOIN Product p ON qp.productId = p.id
    ORDER BY q.createdAt DESC
";

$quotationsResult = $conn->query($quotationsQuery);
$quotations = [];
if ($quotationsResult && $quotationsResult->num_rows > 0) {
    while ($row = $quotationsResult->fetch_assoc()) {
        $quotations[$row['quotation_id']]['quotation_id'] = $row['quotation_id'];
        $quotations[$row['quotation_id']]['status'] = $row['status'];
        $quotations[$row['quotation_id']]['createdAt'] = $row['createdAt'];
        $quotations[$row['quotation_id']]['customer_name'] = $row['customer_name'];
        $quotations[$row['quotation_id']]['products'][] = [
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'priceOffered' => $row['priceOffered']
        ];
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $quotation_id = $_POST['quotation_id'];
    $new_status = $_POST['status'];

    $updateQuery = "UPDATE quotation_header SET status = ? WHERE quotation_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $new_status, $quotation_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0 && strtolower($new_status) === 'approved') {
        // Fetch customerId from quotation_header
        $headerStmt = $conn->prepare("SELECT customerId FROM quotation_header WHERE quotation_id = ?");
        $headerStmt->bind_param("i", $quotation_id);
        $headerStmt->execute();
        $headerResult = $headerStmt->get_result();
        $headerRow = $headerResult->fetch_assoc();
        $customerId = $headerRow['customerId'];
        $headerStmt->close();

        // Fetch all products for this quotation
        $productStmt = $conn->prepare("SELECT productId, quantity FROM quotation_product WHERE quotation_id = ?");
        $productStmt->bind_param("i", $quotation_id);
        $productStmt->execute();
        $productResult = $productStmt->get_result();

        // Insert each product as an order
        while ($productRow = $productResult->fetch_assoc()) {
            $productId = $productRow['productId'];
            $quantity = $productRow['quantity'];
            $orderStatus = 'IN_PROCESS';
            $insertOrder = $conn->prepare("INSERT INTO order_details (customerId, status, productId, quantity, custom_product_name) VALUES (?, ?, ?, ?, NULL)");
            $insertOrder->bind_param("isii", $customerId, $orderStatus, $productId, $quantity);
            $insertOrder->execute();
            $insertOrder->close();
        }
        $productStmt->close();
    }

    $message = ($stmt->affected_rows > 0) ? "Quotation status updated successfully!" : "Failed to update quotation status.";
    $stmt->close();

    header("Location: view_quotation.php?message=" . urlencode($message));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Quotations</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #34495e;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin-left: 350px;
        }
        
        .container-fluid {
            padding: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 30px;
        }
        
        .card-header {
            background-color: var(--secondary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .table th {
            background-color: var(--light-color);
            border-top: none;
        }
        
        .btn-action {
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-action i {
            margin-right: 5px;
        }
        
        .btn-view {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-download {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-update {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .alert {
            border-radius: 8px;
        }
        
        .select-status {
            border-radius: 5px;
            padding: 8px 15px;
            border: 1px solid #ddd;
            width: 100%;
        }
        
        .quotation-details {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
        
        .quotation-details h6 {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }
        
        .product-item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .badge-date {
            background-color: #e9ecef;
            color: #495057;
            font-weight: normal;
        }
        
        .form-container {
            min-width: 200px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-file-invoice-dollar mr-2"></i>Quotation Management</h4>
                    <span class="badge badge-date">
                        <i class="far fa-calendar-alt mr-1"></i> <?= date('F j, Y') ?>
                    </span>
                </div>
                <div class="card-body">
                    <?php if (isset($_GET['message'])): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle mr-2"></i>
                            <?= htmlspecialchars($_GET['message']); ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Products</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quotations as $quotation): 
                                    $statusClass = strtolower($quotation['status']) == 'pending' ? 'status-pending' : 
                                                  (strtolower($quotation['status']) == 'approved' ? 'status-approved' : 'status-rejected');
                                ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($quotation['quotation_id']); ?></strong></td>
                                    <td><?= htmlspecialchars($quotation['customer_name']); ?></td>
                                    <td><?= date('M d, Y', strtotime($quotation['createdAt'])) ?></td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= htmlspecialchars($quotation['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="collapse" 
                                                data-target="#products-<?= $quotation['quotation_id'] ?>" aria-expanded="false">
                                            <i class="fas fa-boxes mr-1"></i> View Products (<?= count($quotation['products']) ?>)
                                        </button>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap">
                                            <div class="form-container mr-2 mb-2">
                                                <form method="POST" action="view_quotation.php">
                                                    <input type="hidden" name="quotation_id" value="<?= $quotation['quotation_id'] ?>">
                                                    <select name="status" class="select-status" required <?= $quotation['status'] == 'APPROVED' ? 'disabled' : '' ?>>
                                                        <option value="PENDING" <?= $quotation['status'] == 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="APPROVED" <?= $quotation['status'] == 'APPROVED' ? 'selected' : '' ?>>Approved</option>
                                                        <option value="REJECTED" <?= $quotation['status'] == 'REJECTED' ? 'selected' : '' ?>>Rejected</option>
                                                    </select>
                                                    <?php if ($quotation['status'] != 'APPROVED'): ?>
                                                        <button type="submit" name="update_status" class="btn btn-update btn-action btn-block mt-2">
                                                            <i class="fas fa-sync-alt"></i> Update
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            </div>
                                            
                                            <div class="d-flex align-items-center">
                                                <?php if ($quotation['status'] == 'APPROVED'): ?>
                                                    <a href="download_customer_quotation.php?quotation_id=<?= $quotation['quotation_id'] ?>" 
                                                       class="btn btn-download btn-action">
                                                        <i class="fas fa-download"></i> PDF
                                                    </a>
                                                <?php else: ?>
                                                    <a href="edit_quotation.php?quotation_id=<?= $quotation['quotation_id'] ?>" 
                                                       class="btn btn-view btn-action">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="p-0">
                                        <div class="collapse" id="products-<?= $quotation['quotation_id'] ?>">
                                            <div class="quotation-details">
                                                <h6><i class="fas fa-box-open mr-2"></i>Products in Quotation #<?= $quotation['quotation_id'] ?></h6>
                                                <?php foreach ($quotation['products'] as $product): ?>
                                                    <div class="product-item">
                                                        <div class="d-flex justify-content-between">
                                                            <div>
                                                                <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                                                            </div>
                                                            <div>
                                                                <span class="text-muted mr-3">Qty: <?= $product['quantity'] ?></span>
                                                                <span>Price: ₹<?= number_format($product['priceOffered'], 2) ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (empty($quotations)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No quotations found</h4>
                            <p class="text-muted">There are currently no quotations to display</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        // Add animation to status badges
        $('.status-badge').hover(
            function() {
                $(this).css('transform', 'scale(1.05)');
            },
            function() {
                $(this).css('transform', 'scale(1)');
            }
        );
        
        // Smooth scroll for alerts
        $('.alert').hide().slideDown();
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').slideUp();
        }, 5000);
    });
</script>
</body>
</html>

<?php ob_end_flush(); ?>