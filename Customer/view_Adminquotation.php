<?php
// Start the session
ob_start();
session_start();
include('CustomerNav.php');

// Database connection
include('Config.php');

// Check if the user is logged in and retrieve the customer ID
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id'];
} else {
    die("No customer is logged in. Please log in.");
}

// Fetch customer name (optional)
$customerQuery = "SELECT companyName FROM CustomerDistributor WHERE id = ?";
$stmt = $conn->prepare($customerQuery);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$stmt->bind_result($customerName);
$stmt->fetch();
$stmt->close();

// Fetch quotations and related product details
$quotationQuery = "
    SELECT qh.quotation_id, qh.subject, qp.productId, p.name AS productName, qp.quantity, qp.priceOffered, 
           tr.tax_percentage AS taxPercentage, qh.status, qh.createdAt,
           qh.customer_approval, qh.superadmin_approval
    FROM quotation_header qh
    JOIN quotation_product qp ON qh.quotation_id = qp.quotation_id
    JOIN product p ON qp.productId = p.id
    LEFT JOIN tax_rates tr ON qp.tax_rate_id = tr.id
    WHERE qh.customerId = ?
    ORDER BY qh.createdAt DESC, qh.quotation_id
";
$stmt = $conn->prepare($quotationQuery);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

$groupedQuotations = [];
while ($row = $result->fetch_assoc()) {
    $quotation_id = $row['quotation_id'];
    if (!isset($groupedQuotations[$quotation_id])) {
        $groupedQuotations[$quotation_id] = [
            'quotation_id' => $row['quotation_id'],
            'subject' => $row['subject'],
            'createdAt' => $row['createdAt'],
            'status' => $row['status'],
            'customer_approval' => $row['customer_approval'],
            'superadmin_approval' => $row['superadmin_approval'],
            'products' => []
        ];
    }
    $groupedQuotations[$quotation_id]['products'][] = [
        'productName' => $row['productName'],
        'quantity' => $row['quantity'],
        'priceOffered' => $row['priceOffered'],
        'taxPercentage' => $row['taxPercentage']
    ];
}
$stmt->close();

// Handle status update (Customer side - only updates customer_approval)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $quotation_id = $_POST['quotation_id'];
    $new_status = $_POST['status'];

    // Update only customer approval
    $updateQuery = "UPDATE quotation_header SET customer_approval = ? WHERE quotation_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $new_status, $quotation_id);
    $stmt->execute();

    // Check if both approvals are now APPROVED
    $approvalQuery = "SELECT customer_approval, superadmin_approval 
                     FROM quotation_header WHERE quotation_id = ?";
    $approvalStmt = $conn->prepare($approvalQuery);
    $approvalStmt->bind_param("i", $quotation_id);
    $approvalStmt->execute();
    $approvalResult = $approvalStmt->get_result();
    $approvalRow = $approvalResult->fetch_assoc();
    $approvalStmt->close();

    // Determine final status
    if ($approvalRow['superadmin_approval'] == 'APPROVED' && $approvalRow['customer_approval'] == 'APPROVED') {
        $finalStatus = 'APPROVED';
    } elseif ($approvalRow['superadmin_approval'] == 'REJECTED' || $approvalRow['customer_approval'] == 'REJECTED') {
        $finalStatus = 'REJECTED';
    } else {
        $finalStatus = 'PENDING';
    }

    // Update final status if needed
    if ($finalStatus != $approvalRow['status']) {
        $updateStatusQuery = "UPDATE quotation_header SET status = ? WHERE quotation_id = ?";
        $statusStmt = $conn->prepare($updateStatusQuery);
        $statusStmt->bind_param("si", $finalStatus, $quotation_id);
        $statusStmt->execute();
        $statusStmt->close();

        // Create orders if final status is APPROVED
        if ($finalStatus == 'APPROVED') {
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
    }

    $message = ($stmt->affected_rows > 0) ? "Quotation status updated successfully!" : "Failed to update quotation status.";
    $stmt->close();
    
    header("Location: view_Adminquotation.php?message=" . urlencode($message));
    exit;
}
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
        
        .quotation-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-left: 250px;
            transition: all 0.3s ease;
        }
        
        .quotation-header {
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
        
        .approval-status {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            color: var(--gray);
        }
        
        .approval-approved {
            color: var(--success);
        }
        
        .approval-pending {
            color: var(--warning);
        }
        
        .approval-rejected {
            color: var(--danger);
        }
        
        @media (max-width: 992px) {
            .quotation-container {
                margin-left: 0;
                margin-top: 1rem;
                padding: 1.5rem;
            }
            
            .quotation-header {
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
                <div class="quotation-container">
                    <h1 class="quotation-header">
                        <i class="fas fa-file-invoice me-2"></i>My Quotations
                    </h1>

                    <?php if (isset($_GET['message'])): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <?= htmlspecialchars($_GET['message']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (count($groupedQuotations) > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Quotation ID</th>
                                        <th>Subject</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($groupedQuotations as $quotation): ?>
                                        <tr>
                                            <td rowspan="<?= count($quotation['products']); ?>"><?= htmlspecialchars($quotation['quotation_id']); ?></td>
                                            <td rowspan="<?= count($quotation['products']); ?>"><?= htmlspecialchars($quotation['subject']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?= strtolower($quotation['status']); ?>">
                                                    <?= htmlspecialchars($quotation['status']); ?>
                                                </span>
                                                <div class="approval-status">
                                                    <div class="<?= strtolower($quotation['customer_approval']) == 'approved' ? 'approval-approved' : 
                                                                 (strtolower($quotation['customer_approval']) == 'pending' ? 'approval-pending' : 'approval-rejected') ?>">
                                                        Customer: <?= htmlspecialchars($quotation['customer_approval']); ?>
                                                    </div>
                                                    <div class="<?= strtolower($quotation['superadmin_approval']) == 'approved' ? 'approval-approved' : 
                                                                 (strtolower($quotation['superadmin_approval']) == 'pending' ? 'approval-pending' : 'approval-rejected') ?>">
                                                        Superadmin: <?= htmlspecialchars($quotation['superadmin_approval']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td rowspan="<?= count($quotation['products']); ?>"><?= date('M d, Y h:i A', strtotime($quotation['createdAt'])); ?></td>
                                            <td rowspan="<?= count($quotation['products']); ?>">
                                                <div class="action-buttons">
                                                    <?php if ($quotation['customer_approval'] == 'PENDING'): ?>
                                                        <form method="POST" action="">
                                                            <input type="hidden" name="quotation_id" value="<?= htmlspecialchars($quotation['quotation_id']); ?>">
                                                            <select name="status" class="form-select mb-2" required>
                                                                <option value="PENDING" <?= $quotation['customer_approval'] == 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                                                <option value="APPROVED">Approve</option>
                                                                <option value="REJECTED">Reject</option>
                                                            </select>
                                                            <button type="submit" name="update_status" class="btn btn-success w-100">
                                                                <i class="fas fa-save me-1"></i> Update Status
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <form action="editAdminquotation.php" method="GET">
                                                        <input type="hidden" name="quotation_id" value="<?= htmlspecialchars($quotation['quotation_id']); ?>">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-edit me-1"></i> Edit
                                                        </button>
                                                    </form>
                                                    <form action="view_quotation_products.php" method="GET">
                                                        <input type="hidden" name="quotation_id" value="<?= htmlspecialchars($quotation['quotation_id']); ?>">
                                                        <button type="submit" class="btn btn-info">
                                                            <i class="fas fa-eye me-1"></i> View
                                                        </button>
                                                    </form>
                                                    <?php if ($quotation['status'] == 'APPROVED'): ?>
                                                        <form action="download_quotation.php" method="GET">
                                                            <input type="hidden" name="quotation_id" value="<?= htmlspecialchars($quotation['quotation_id']); ?>">
                                                            <button type="submit" name="download_pdf" class="btn btn-danger">
                                                                <i class="fas fa-file-pdf me-1"></i> Download
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php foreach (array_slice($quotation['products'], 1) as $product): ?>
                                            <tr>
                                                <td></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-file-invoice-dollar fa-3x mb-3" style="color: var(--gray);"></i>
                            <h4>No Quotations Found</h4>
                            <p>You haven't created any quotations yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>