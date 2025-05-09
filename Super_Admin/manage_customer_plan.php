<?php
session_start();
require '../config.php'; // Adjust path as needed

// Check if super admin is logged in

// Get all customer subscriptions with customer details
$sql = "
    SELECT 
        cs.*,
        cp.name AS plan_name,
        cd.companyName AS customer_name,
        cd.mailId AS customer_email,
        cd.mobileNo AS customer_phone
    FROM customer_subscription cs
    JOIN customer_plan cp ON cs.plan_id = cp.id
    JOIN customerdistributor cd ON cs.user_id = cd.id
    ORDER BY cs.created_at DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Customer Subscriptions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary-color: #007bff;
            --danger-color: #dc3545;
            --success-color: #28a745;
            --warning-color: #ffc107;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-container {
            max-width: 1800px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-left: 350px;
        }
        
        .admin-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background-color: #d4edda;
            color: var(--success-color);
        }
        
        .status-expired {
            background-color: #f8d7da;
            color: var(--danger-color);
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .subscription-row {
            transition: all 0.3s ease;
        }
        
        .subscription-row:hover {
            background-color: #f1f1f1;
        }
        
        .detail-label {
            font-weight: 600;
            color: #6c757d;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .customer-info {
            min-width: 250px;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>

    <div class="admin-container">
        <div class="admin-header">
            <h1 class="display-5 fw-bold">
                <i class="bi bi-people-fill"></i> Customer Subscriptions
            </h1>
            <p class="lead">View and manage all customer subscriptions</p>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Subscription ID</th>
                            <th>Customer Details</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Dates</th>
                            <th>Payment ID</th>
                            <!-- <th>Actions</th> -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): 
                            $isExpired = $row['isexpired'] == 1;
                            $isActive = $row['status'] === 'Approved' && !$isExpired;
                        ?>
                            <tr class="subscription-row">
                                <td>#<?php echo $row['id']; ?></td>
                                <td class="customer-info">
                                    <div class="fw-bold"><?php echo htmlspecialchars($row['customer_name']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($row['customer_email']); ?></div>
                                    <div class="small"><?php echo htmlspecialchars($row['customer_phone']); ?></div>
                                </td>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($row['plan_name']); ?></div>
                                    <div class="small"><?php echo $row['tenure']; ?> months</div>
                                </td>
                                <td class="fw-bold">₹<?php echo number_format($row['amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge <?php 
                                        echo $isActive ? 'status-active' : 
                                            ($isExpired ? 'status-expired' : 'status-pending'); 
                                    ?>">
                                        <?php echo $isExpired ? 'EXPIRED' : $row['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="small">Start: <?php echo date('d M Y', strtotime($row['start_date'])); ?></div>
                                    <div class="small">End: <?php echo date('d M Y', strtotime($row['end_date'])); ?></div>
                                </td>
                                <td class="text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($row['payment_id']); ?></td>
                                
                                <!-- <td>
                                    <div class="d-flex gap-2">
                                        <a href="admin_subscription_details.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-primary" title="View Details">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        <?php if ($isExpired): ?>
                                            <button class="btn btn-sm btn-warning" title="Notify Customer" onclick="notifyCustomer(<?php echo $row['user_id']; ?>, <?php echo $row['id']; ?>)">
                                                <i class="bi bi-bell-fill"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="admin_edit_subscription.php?id=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-info" title="Edit">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr> -->
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center py-4">
                <i class="bi bi-info-circle-fill fs-4"></i>
                <h4 class="alert-heading mt-2">No Subscriptions Found</h4>
                <p>There are no customer subscriptions in the system yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function notifyCustomer(customerId, subscriptionId) {
            if (confirm('Send renewal reminder to this customer?')) {
                // AJAX call to send notification
                fetch('admin_notify_customer.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'customer_id=' + customerId + '&subscription_id=' + subscriptionId
                })
                .then(response => response.text())
                .then(data => {
                    alert('Notification sent successfully!');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to send notification');
                });
            }
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>