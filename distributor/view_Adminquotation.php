<?php
// Start session to retrieve success or error messages
session_start();

// Ensure no output has been sent before this point
ob_start();

include 'Dnav.php';

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

// Ensure user is logged in as distributor
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$distributorId = $_SESSION['user_id'];

// Fetch the distributor's quotations with purchase order info if exists
$sql = "SELECT 
            qh.quotation_id, 
            qh.status, 
            qh.createdAt, 
            qh.updatedAt, 
            qh.subject,
            qh.superadmin_approval,
            qh.distributor_approval,
            u.name AS superadmin_name,
            pd.id AS purchase_id
        FROM quotation_header qh
        JOIN users u ON qh.superAdminId = u.id
        LEFT JOIN purchase_details pd ON pd.quotation_id = qh.quotation_id
        WHERE qh.distributorId = ?
        GROUP BY qh.quotation_id
        ORDER BY qh.createdAt DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $distributorId);
$stmt->execute();
$result = $stmt->get_result();

// Group quotations by quotation_id
$groupedQuotations = [];
while ($quotation = $result->fetch_assoc()) {
    $quotation_id = $quotation['quotation_id'];
    $groupedQuotations[$quotation_id] = [
        'quotation_id' => $quotation['quotation_id'],
        'subject' => $quotation['subject'],
        'superadmin_name' => $quotation['superadmin_name'],
        'status' => $quotation['status'],
        'superadmin_approval' => $quotation['superadmin_approval'],
        'distributor_approval' => $quotation['distributor_approval'],
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

    // Update distributor approval
    $updateSql = "UPDATE quotation_header SET distributor_approval = ? WHERE quotation_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newStatus, $quotationId);
    $updateStmt->execute();

    // Check current approvals to determine final status
    $approvalQuery = "SELECT superadmin_approval, distributor_approval 
                     FROM quotation_header WHERE quotation_id = ?";
    $approvalStmt = $conn->prepare($approvalQuery);
    $approvalStmt->bind_param("i", $quotationId);
    $approvalStmt->execute();
    $approvalResult = $approvalStmt->get_result();
    $approvalRow = $approvalResult->fetch_assoc();
    $approvalStmt->close();

    // Determine final status
    if ($approvalRow['superadmin_approval'] == 'APPROVED' && $approvalRow['distributor_approval'] == 'APPROVED') {
        $finalStatus = 'APPROVED';
    } elseif ($approvalRow['superadmin_approval'] == 'REJECTED' || $approvalRow['distributor_approval'] == 'REJECTED') {
        $finalStatus = 'REJECTED';
    } else {
        $finalStatus = 'PENDING';
    }

    // Update final status
    $updateStatusQuery = "UPDATE quotation_header SET status = ? WHERE quotation_id = ?";
    $statusStmt = $conn->prepare($updateStatusQuery);
    $statusStmt->bind_param("si", $finalStatus, $quotationId);
    $statusStmt->execute();
    $statusStmt->close();

    // Only create purchase order if final status is APPROVED and distributor approved
    if ($finalStatus == 'APPROVED' && strtoupper($newStatus) == 'APPROVED') {
        // Get quotation products
        $productsQuery = "SELECT qp.*, p.name FROM quotation_product qp 
                         JOIN product p ON qp.productId = p.id 
                         WHERE qp.quotation_id = ?";
        $productsStmt = $conn->prepare($productsQuery);
        $productsStmt->bind_param("i", $quotationId);
        $productsStmt->execute();
        $productsResult = $productsStmt->get_result();
        $productsStmt->close();

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
        $superAdminId = $groupedQuotations[$quotationId]['superadmin_id'] ?? 1; // Default to admin ID 1 if not set
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
    
    // Redirect to reload page and show updated status
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
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
        
        .badge-info {
            background-color: var(--info-color);
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
        
        .approval-status {
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .approval-approved {
            color: var(--success-color);
        }
        
        .approval-pending {
            color: var(--warning-color);
        }
        
        .approval-rejected {
            color: var(--danger-color);
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
                                <th>Super Admin</th>
                                <th>Subject</th>
                                <th>Status</th>
                                <th>Approvals</th>
                                <th>Created</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($groupedQuotations as $quotation): ?>
                                <tr>
                                    <td class="font-weight-bold">#<?= htmlspecialchars($quotation['quotation_id']); ?></td>
                                    <td><?= htmlspecialchars($quotation['superadmin_name']); ?></td>
                                    <td><?= htmlspecialchars($quotation['subject']); ?></td>
                                    <td>
                                        <?php if ($quotation['status'] == 'APPROVED'): ?>
                                            <span class="badge badge-success"><i class="fas fa-check mr-1"></i> Approved</span>
                                        <?php elseif ($quotation['status'] == 'REJECTED'): ?>
                                            <span class="badge badge-danger"><i class="fas fa-times mr-1"></i> Rejected</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="approval-status">
                                            <div class="<?= $quotation['superadmin_approval'] == 'APPROVED' ? 'approval-approved' : ($quotation['superadmin_approval'] == 'REJECTED' ? 'approval-rejected' : 'approval-pending') ?>">
                                                <i class="fas fa-user-shield mr-1"></i> Super Admin: <?= $quotation['superadmin_approval'] ?: 'PENDING' ?>
                                            </div>
                                            <div class="<?= $quotation['distributor_approval'] == 'APPROVED' ? 'approval-approved' : ($quotation['distributor_approval'] == 'REJECTED' ? 'approval-rejected' : 'approval-pending') ?>">
                                                <i class="fas fa-user-tie mr-1"></i> Distributor: <?= $quotation['distributor_approval'] ?: 'PENDING' ?>
                                            </div>
                                        </div>
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
            <a href="editquotation.php?quotation_id=<?= $quotation['quotation_id']; ?>" class="btn btn-warning btn-sm" title="Edit Quotation">
                <i class="fas fa-edit mr-1"></i> View & Edit 
            </a>
            <form method="POST" action="" class="status-form">
                <input type="hidden" name="quotation_id" value="<?= $quotation['quotation_id']; ?>">
                <select name="status" class="form-control form-control-sm status-select" required>
                    <option value="PENDING" <?= $quotation['distributor_approval'] == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                    <option value="APPROVED" <?= $quotation['distributor_approval'] == 'APPROVED' ? 'selected' : ''; ?>>Approve</option>
                    <option value="REJECTED" <?= $quotation['distributor_approval'] == 'REJECTED' ? 'selected' : ''; ?>>Reject</option>
                </select>
                <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                    <i class="fas fa-save"></i>
                </button>
            </form>
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
                    <p class="text-muted">You haven't received any quotations yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>
    // Add confirmation for reject action
    $(document).ready(function() {
        $('form.status-form').on('submit', function(e) {
            const statusSelect = $(this).find('select[name="status"]');
            if (statusSelect.val() === 'REJECTED') {
                if (!confirm('Are you sure you want to reject this quotation?')) {
                    e.preventDefault();
                }
            }
        });
    });
</script>

</body>
</html>
<?php ob_end_flush(); ?>