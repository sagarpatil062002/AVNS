<?php
session_start();
ob_start();

include 'Dnav.php';
include('Config.php');

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$distributorId = $_SESSION['user_id'];

// SQL: Fetch quotations with grouped products
$sql = "
    SELECT qh.quotation_id, qh.status, qh.subject, 
           GROUP_CONCAT(CONCAT(p.name, ' (Qty: ', qp.quantity, ', Price: ', FORMAT(qp.priceOffered, 2), ')') SEPARATOR '; ') AS products, 
           d.companyName AS distributor_name,
           pd.id AS purchase_id
    FROM quotation_header qh
    JOIN quotation_product qp ON qh.quotation_id = qp.quotation_id
    JOIN product p ON qp.productId = p.id
    JOIN distributor d ON qh.distributorId = d.id
    LEFT JOIN purchase_details pd ON pd.quotation_id = qh.quotation_id
    WHERE qh.status IN ('PENDING', 'APPROVED', 'REJECTED')
      AND qh.distributorId = ?
    GROUP BY qh.quotation_id
    ORDER BY qh.createdAt DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $distributorId);
$stmt->execute();
$result = $stmt->get_result();

$groupedQuotations = [];
while ($quotation = $result->fetch_assoc()) {
    $groupedQuotations[$quotation['quotation_id']] = [
        'quotation_id' => $quotation['quotation_id'],
        'subject' => $quotation['subject'],
        'distributor_name' => $quotation['distributor_name'],
        'status' => $quotation['status'],
        'purchase_id' => $quotation['purchase_id'],
        'products' => $quotation['products']
    ];
}
$stmt->close();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $quotationId = $_POST['quotation_id'];
    $newStatus = $_POST['status'];

    $updateSql = "UPDATE quotation_header SET status = ? WHERE quotation_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newStatus, $quotationId);

    if ($updateStmt->execute()) {
        if (strtoupper($newStatus) === 'APPROVED') {
            // Fetch quotation
            $quotationQuery = "SELECT * FROM quotation_header WHERE quotation_id = ?";
            $quotationStmt = $conn->prepare($quotationQuery);
            $quotationStmt->bind_param("i", $quotationId);
            $quotationStmt->execute();
            $quotationResult = $quotationStmt->get_result();
            $quotationData = $quotationResult->fetch_assoc();
            $quotationStmt->close();

            // Fetch products
            $productsQuery = "SELECT qp.*, p.name FROM quotation_product qp 
                              JOIN product p ON qp.productId = p.id 
                              WHERE qp.quotation_id = ?";
            $productsStmt = $conn->prepare($productsQuery);
            $productsStmt->bind_param("i", $quotationId);
            $productsStmt->execute();
            $productsResult = $productsStmt->get_result();
            $productsStmt->close();

            // Tax info
            $taxResult = $conn->query("SELECT tax_name, tax_percentage FROM tax_rates LIMIT 1");
            $taxData = $taxResult->fetch_assoc();
            $taxRate = $taxData['tax_percentage'] ?? 0;
            $taxName = $taxData['tax_name'] ?? 'Tax';

            // Insert into purchase_details
            $purchaseSql = "INSERT INTO purchase_details 
                            (distributor_id, super_admin_id, quotation_id, total_amount, total_tax, created_at) 
                            VALUES (?, ?, ?, 0, 0, NOW())";
            $purchaseStmt = $conn->prepare($purchaseSql);
            $purchaseStmt->bind_param("iii", $distributorId, $quotationData['superAdminId'], $quotationId);
            $purchaseStmt->execute();
            $purchase_id = $conn->insert_id;
            $purchaseStmt->close();

            // Process purchase items
            $total_amount = 0;
            $total_tax = 0;

            while ($product = $productsResult->fetch_assoc()) {
                $quantity = $product['quantity'];
                $price = $product['priceOffered'];
                $total_before_tax = $quantity * $price;
                $tax_for_product = $total_before_tax * ($taxRate / 100);
                $total_with_tax = $total_before_tax + $tax_for_product;

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
                    $tax_for_product, 
                    $taxName
                );
                $itemStmt->execute();
                $itemStmt->close();

                $total_amount += $total_before_tax;
                $total_tax += $tax_for_product;
            }

            // Update purchase_details with final totals
            $updatePurchaseSql = "UPDATE purchase_details 
                                  SET total_amount = ?, total_tax = ? 
                                  WHERE id = ?";
            $updatePurchaseStmt = $conn->prepare($updatePurchaseSql);
            $updatePurchaseStmt->bind_param("ddi", $total_amount, $total_tax, $purchase_id);
            $updatePurchaseStmt->execute();
            $updatePurchaseStmt->close();

            $_SESSION['success_message'] = "Quotation approved and converted to purchase order!";
            header("Location: view_purchase_detials.php?id=" . $purchase_id);
            exit();
        } else {
            $_SESSION['success_message'] = "Status updated successfully!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Failed to update status.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Retrieve messages safely
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quotations</title>
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
        
        .quotations-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-left: 200px;
            transition: all 0.3s ease;
        }
        
        .quotations-header {
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
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: rgba(246, 194, 62, 0.2);
            color: #b78a00;
        }
        
        .status-approved {
            background-color: rgba(28, 200, 138, 0.2);
            color: #0d8a5a;
        }
        
        .status-rejected {
            background-color: rgba(231, 74, 59, 0.2);
            color: #c23321;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            border-radius: 0.35rem;
            transition: all 0.2s ease;
        }
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-info {
            background-color: #36b9cc;
            border-color: #36b9cc;
        }
        
        .btn-warning {
            background-color: var(--warning);
            border-color: var(--warning);
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
        
        .status-form {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .form-select {
            border-radius: 0.35rem;
            border: 1px solid var(--gray-light);
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
            font-size: 0.8rem;
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
        
        .alert-success {
            background-color: rgba(28, 200, 138, 0.1);
            border-color: var(--success);
            color: #0d8a5a;
        }
        
        .alert-danger {
            background-color: rgba(231, 74, 59, 0.1);
            border-color: var(--danger);
            color: #c23321;
        }
        
        .alert-warning {
            background-color: rgba(246, 194, 62, 0.1);
            border-color: var(--warning);
            color: #b78a00;
        }
        
        @media (max-width: 992px) {
            .quotations-container {
                margin-left: 0;
                margin-top: 1rem;
                padding: 1.5rem;
            }
            
            .quotations-header {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .action-buttons, .status-form {
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
                <div class="quotations-container">
                    <h1 class="quotations-header">
                        <i class="fas fa-file-invoice me-2"></i>My Quotations
                    </h1>

                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($successMessage); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= htmlspecialchars($errorMessage); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($groupedQuotations)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Quotation ID</th>
                                        <th>Subject</th>
                                        <th>Products</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groupedQuotations as $quotation): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($quotation['quotation_id']); ?></td>
                                            <td><?= htmlspecialchars($quotation['subject']); ?></td>
                                            <td><?= htmlspecialchars($quotation['products']); ?></td>
                                            <td>
                                                <?php if ($quotation['status'] == 'APPROVED'): ?>
                                                    <span class="status-badge status-approved">
                                                        <i class="fas fa-check-circle me-1"></i> Approved
                                                    </span>
                                                <?php elseif ($quotation['status'] == 'REJECTED'): ?>
                                                    <span class="status-badge status-rejected">
                                                        <i class="fas fa-times-circle me-1"></i> Rejected
                                                    </span>
                                                <?php else: ?>
                                                    <form method="POST" action="" class="status-form">
                                                        <input type="hidden" name="quotation_id" value="<?= htmlspecialchars($quotation['quotation_id']); ?>">
                                                        <select name="status" class="form-select" required>
                                                            <option value="PENDING" <?= $quotation['status'] == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="APPROVED" <?= $quotation['status'] == 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                                                            <option value="REJECTED" <?= $quotation['status'] == 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                                                        </select>
                                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-sync-alt me-1"></i> Update
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($quotation['status'] == 'APPROVED'): ?>
                                                        <a href="download_quotation.php?quotation_id=<?= htmlspecialchars($quotation['quotation_id']); ?>" class="btn btn-success">
                                                            <i class="fas fa-file-pdf me-1"></i> Download
                                                        </a>
                                                        <?php if (!empty($quotation['purchase_id'])): ?>
                                                            <a href="view_purchase_detials.php?id=<?= htmlspecialchars($quotation['purchase_id']); ?>" class="btn btn-info">
                                                                <i class="fas fa-eye me-1"></i> View Purchase
                                                            </a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <a href="editAdminquotation.php?id=<?= htmlspecialchars($quotation['quotation_id']); ?>" class="btn btn-info">
    <i class="fas fa-edit me-1"></i> Edit
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
                            <i class="fas fa-file-invoice-dollar fa-3x mb-3" style="color: var(--gray);"></i>
                            <h4>No Quotations Found</h4>
                            <p>You don't have any quotations yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add confirmation for status changes
        document.querySelectorAll('form.status-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const statusSelect = this.querySelector('select[name="status"]');
                if (statusSelect.value === 'REJECTED') {
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