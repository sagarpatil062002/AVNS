<?php
// Start session to access distributor ID
session_start();
include('Dnav.php');
include('Config.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in and session is set
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view the purchase details.");
}

// Fetch purchase details
$distributor_id = $_SESSION['user_id']; // Use distributor ID from session
$purchasesQuery = "
    SELECT pd.id, pd.super_admin_id, u.name AS super_admin_name, pd.total_amount, pd.total_tax, pd.created_at
    FROM purchase_details pd
    JOIN users u ON pd.super_admin_id = u.id
    WHERE pd.distributor_id = '$distributor_id'
    ORDER BY pd.created_at DESC
";
$purchasesResult = $conn->query($purchasesQuery);

// Prepare an array to store purchase data
$purchases = [];
if ($purchasesResult && $purchasesResult->num_rows > 0) {
    while ($row = $purchasesResult->fetch_assoc()) {
        $purchases[$row['id']] = [
            'id' => $row['id'],
            'super_admin_name' => $row['super_admin_name'],
            'total_amount' => $row['total_amount'],
            'total_tax' => $row['total_tax'],
            'created_at' => $row['created_at'],
            'items' => [],
        ];
    }
}

// Fetch purchase items
if (!empty($purchases)) {
    $purchaseIds = implode(',', array_keys($purchases));
    $itemsQuery = "
        SELECT pi.purchase_id, pi.product_name, pi.quantity, pi.price, pi.total, pi.tax, pi.tax_name
        FROM purchase_items pi
        WHERE pi.purchase_id IN ($purchaseIds)
    ";
    $itemsResult = $conn->query($itemsQuery);

    if ($itemsResult && $itemsResult->num_rows > 0) {
        while ($item = $itemsResult->fetch_assoc()) {
            $purchases[$item['purchase_id']]['items'][] = $item;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Purchases</title>
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
        
        .purchases-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-left: 250px;
            transition: all 0.3s ease;
        }
        
        .purchases-header {
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
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 0.5rem 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            border-radius: 0.35rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .amount-cell {
            font-weight: 600;
            color: var(--dark);
        }
        
        .tax-cell {
            color: var(--success);
            font-weight: 500;
        }
        
        .date-cell {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        @media (max-width: 992px) {
            .purchases-container {
                margin-left: 0;
                margin-top: 1rem;
                padding: 1.5rem;
            }
            
            .purchases-header {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .table thead {
                display: none;
            }
            
            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid var(--gray-light);
                border-radius: 0.35rem;
            }
            
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none;
                border-bottom: 1px solid var(--gray-light);
            }
            
            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--dark);
                margin-right: 1rem;
            }
            
            .table tbody td:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="purchases-container">
                    <h1 class="purchases-header">
                        <i class="fas fa-shopping-cart me-2"></i>Purchase History
                    </h1>
                    
                    <?php if (!empty($purchases)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Purchase ID</th>
                                        <th>Super Admin</th>
                                        <th>Total Amount</th>
                                        <th>Total Tax</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($purchases as $purchase): ?>
                                        <tr>
                                            <td data-label="Purchase ID"><?= htmlspecialchars($purchase['id']); ?></td>
                                            <td data-label="Super Admin"><?= htmlspecialchars($purchase['super_admin_name']); ?></td>
                                            <td data-label="Total Amount" class="amount-cell">₹<?= number_format($purchase['total_amount'], 2); ?></td>
                                            <td data-label="Total Tax" class="tax-cell">₹<?= number_format($purchase['total_tax'], 2); ?></td>
                                            <td data-label="Date" class="date-cell"><?= date('M d, Y h:i A', strtotime($purchase['created_at'])); ?></td>
                                            <td data-label="Actions">
                                                <a href="download_purchase.php?purchase_id=<?= $purchase['id']; ?>" class="btn btn-primary">
                                                    <i class="fas fa-file-pdf me-1"></i>Download
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart fa-3x mb-3" style="color: var(--gray);"></i>
                            <h4>No Purchase Records Found</h4>
                            <p>You haven't made any purchases yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add any necessary JavaScript here
        document.addEventListener("DOMContentLoaded", function() {
            // Add responsive behavior for mobile
            if (window.innerWidth < 768) {
                const tableCells = document.querySelectorAll('.table tbody td');
                tableCells.forEach(cell => {
                    const headerText = cell.parentNode.children[0].textContent;
                    cell.setAttribute('data-label', headerText);
                });
            }
        });
    </script>
</body>
</html>