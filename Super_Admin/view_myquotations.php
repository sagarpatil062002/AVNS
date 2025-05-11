<?php
// Start session to retrieve success or error messages
session_start();

// Ensure no output has been sent before this point
ob_start();

include 'admin_navbar.php';

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "sales_management";

$conn = new mysqli($host, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure user is logged in
if (isset($_SESSION['user_id'])) {
    $superAdminId = $_SESSION['user_id'];

    // Fetch the quotations with purchase order info if exists
    $sql = "SELECT 
                qh.quotation_id, 
                qh.status, 
                qh.createdAt, 
                qh.updatedAt, 
                qh.subject, 
                qh.distributorId,
                d.companyName AS distributor_name,
                pd.id AS purchase_id
            FROM quotation_header qh
            JOIN distributor d ON qh.distributorId = d.id
            LEFT JOIN purchase_details pd ON pd.quotation_id = qh.quotation_id
            WHERE qh.superAdminId = ?
            GROUP BY qh.quotation_id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $superAdminId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Group quotations by quotation_id
    $groupedQuotations = [];
    while ($quotation = $result->fetch_assoc()) {
        $quotation_id = $quotation['quotation_id'];
        $groupedQuotations[$quotation_id] = [
            'quotation_id' => $quotation['quotation_id'],
            'subject' => $quotation['subject'],
            'distributor_name' => $quotation['distributor_name'],
            'distributor_id' => $quotation['distributorId'],
            'status' => $quotation['status'],
            'createdAt' => $quotation['createdAt'],
            'updatedAt' => $quotation['updatedAt'],
            'purchase_id' => $quotation['purchase_id'],
            'products' => []
        ];
    }

    // Handle status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $quotationId = $_POST['quotation_id'];
        $newStatus = $_POST['status'];

        // Update the quotation status in the database
        $updateSql = "UPDATE quotation_header SET status = ? WHERE quotation_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $newStatus, $quotationId);
        
        if ($updateStmt->execute()) {
            // If status was changed to APPROVED, create purchase order
            if (strtoupper($newStatus) == 'APPROVED') {
                // Get quotation products
                $productsQuery = "SELECT qp.*, p.name FROM quotation_product qp 
                                 JOIN product p ON qp.productId = p.id 
                                 WHERE qp.quotation_id = ?";
                $productsStmt = $conn->prepare($productsQuery);
                $productsStmt->bind_param("i", $quotationId);
                $productsStmt->execute();
                $productsResult = $productsStmt->get_result();
                
                // Get default tax rate
                $taxQuery = "SELECT tax_name, tax_percentage FROM tax_rates LIMIT 1";
                $taxResult = $conn->query($taxQuery);
                $taxData = $taxResult->fetch_assoc();
                $taxRate = $taxData['tax_percentage'] ?? 0;
                $taxName = $taxData['tax_name'] ?? 'Tax';
                
                // Create purchase details
                $purchaseSql = "INSERT INTO purchase_details 
                               (distributor_id, super_admin_id, quotation_id, total_amount, total_tax, created_at) 
                               VALUES (?, ?, ?, 0, 0, NOW())";
                $purchaseStmt = $conn->prepare($purchaseSql);
                $distributorId = $groupedQuotations[$quotationId]['distributor_id'];
                $purchaseStmt->bind_param("iii", $distributorId, $superAdminId, $quotationId);
                $purchaseStmt->execute();
                $purchase_id = $conn->insert_id;
                
                $total_amount = 0;
                $total_tax = 0;
                
                // Insert purchase items
                while ($product = $productsResult->fetch_assoc()) {
                    $quantity = $product['quantity'];
                    $price = $product['priceOffered'];
                    $total_before_tax = $quantity * $price;
                    $total_tax_for_product = $total_before_tax * ($taxRate / 100);
                    $total_with_tax = $total_before_tax + $total_tax_for_product;
                    
                    $itemSql = "INSERT INTO purchase_items 
                               (purchase_id, product_name, quantity, price, total, tax, tax_name) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $itemStmt = $conn->prepare($itemSql);
                    $itemStmt->bind_param("isiddds", 
                        $purchase_id, 
                        $product['name'], 
                        $quantity, 
                        $price, 
                        $total_with_tax, 
                        $total_tax_for_product, 
                        $taxName
                    );
                    $itemStmt->execute();
                    
                    $total_amount += $total_before_tax;
                    $total_tax += $total_tax_for_product;
                }
                
                // Update purchase details with final totals
                $updatePurchaseSql = "UPDATE purchase_details 
                                    SET total_amount = ?, total_tax = ? 
                                    WHERE id = ?";
                $updatePurchaseStmt = $conn->prepare($updatePurchaseSql);
                $updatePurchaseStmt->bind_param("ddi", $total_amount, $total_tax, $purchase_id);
                $updatePurchaseStmt->execute();
                
                $_SESSION['success_message'] = "Quotation approved and purchase order #$purchase_id created successfully!";
            } else {
                $_SESSION['success_message'] = "Status updated successfully!";
            }
        } else {
            $_SESSION['error_message'] = "Failed to update status.";
        }
        
        // Redirect to reload page and show updated status
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
} else {
    $message = "You must be logged in to view your quotations!";
}

// Retrieve success or error messages
$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;

// Clear messages after displaying them
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quotations</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        
        body {
            background: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin-left: 300px;
        }
        
        .container-fluid {
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            border-top: none;
            color: var(--dark-color);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            padding: 1rem;
            background-color: #f8f9fc;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e3e6f0;
        }
        
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
        }
        
        .badge-success {
            background-color: var(--success-color);
        }
        
        .badge-danger {
            background-color: var(--danger-color);
        }
        
        .badge-warning {
            background-color: var(--warning-color);
            color: #1f2d3d;
        }
        
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
            border-radius: 0.2rem;
        }
        
        .status-form {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-select {
            width: 120px;
            font-size: 0.875rem;
            padding: 0.25rem 0.5rem;
            height: calc(1.5em + 0.5rem + 2px);
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .action-buttons .btn {
            flex: 1;
            min-width: 80px;
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .btn-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
        }
        
        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
            color: #1f2d3d;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .alert {
            border-radius: 0.35rem;
            padding: 1rem 1.25rem;
        }
        
        h1 {
            color: var(--dark-color);
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--dark-color);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #dddfeb;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            font-size: 1.1rem;
            margin-bottom: 0;
        }
        
        .text-muted {
            color: #858796 !important;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">My Quotations</h6>
        </div>
        <div class="card-body">
            <?php if (isset($message)): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($message); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?= htmlspecialchars($successMessage); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?= htmlspecialchars($errorMessage); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (isset($groupedQuotations) && count($groupedQuotations) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Quotation ID</th>
                                <th>Distributor</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groupedQuotations as $quotation): ?>
                                <tr>
                                    <td class="font-weight-bold">#<?= htmlspecialchars($quotation['quotation_id']); ?></td>
                                    <td><?= htmlspecialchars($quotation['distributor_name']); ?></td>
                                    <td><?= htmlspecialchars($quotation['subject']); ?></td>
                                    <td>
                                        <?php if ($quotation['status'] == 'APPROVED'): ?>
                                            <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Approved</span>
                                        <?php elseif ($quotation['status'] == 'REJECTED'): ?>
                                            <span class="badge badge-danger"><i class="fas fa-times mr-1"></i> Rejected</span>
                                        <?php else: ?>
                                            <form method="POST" action="" class="status-form">
                                                <input type="hidden" name="quotation_id" value="<?= $quotation['quotation_id']; ?>">
                                                <select name="status" class="form-control form-control-sm status-select" required>
                                                    <option value="PENDING" <?= $quotation['status'] == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="APPROVED" <?= $quotation['status'] == 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="REJECTED" <?= $quotation['status'] == 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-sync-alt"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted"><?= date('M d, Y', strtotime($quotation['createdAt'])); ?></td>
                                    <td class="text-muted"><?= date('M d, Y', strtotime($quotation['updatedAt'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($quotation['status'] == 'APPROVED'): ?>
                                                <a href="download_quotation.php?quotation_id=<?= $quotation['quotation_id']; ?>" class="btn btn-success btn-sm">
                                                    <i class="fas fa-download mr-1"></i> Download
                                                </a>
                                                <?php if ($quotation['purchase_id']): ?>
                                                    <a href="view_purchase_detials.php?id=<?= $quotation['purchase_id']; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-file-invoice mr-1"></i> PO
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <a href="edit_my_quotation.php?quotation_id=<?= $quotation['quotation_id']; ?>" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-edit mr-1"></i> Edit
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-invoice fa-4x"></i>
                    <h4>No quotations found</h4>
                    <p class="text-muted">You haven't created any quotations yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

</body>
</html>
<?php ob_end_flush(); ?>