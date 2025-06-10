<?php
session_start();
require 'config.php';
require 'CustomerNav.php';

// Check if customer is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$customerId = $_SESSION['user_id'];
$hasExpiredSubscription = false;


// Update expired subscriptions
$updateSql = "
    UPDATE customer_subscription 
    SET isexpired = 1 
    WHERE user_id = ? 
       AND (
      (end_date < NOW() AND isexpired = 0)
      OR remaining_calls = 0
  )
";
$updateStmt = $conn->prepare($updateSql);
if ($updateStmt) {
    $updateStmt->bind_param("i", $customerId);
    $updateStmt->execute();
    $updateStmt->close();
}


// Query to fetch subscription details
$sql = "
    SELECT 
            cs.id, cs.plan_id, cs.tenure, cs.status, cs.created_at, 
            cs.isexpired, cs.remaining_calls, cs.start_date, cs.end_date,
            cp.name AS plan_name,
            cs.payment_id, cs.amount,
            cd.sectorId
        FROM customer_subscription cs
        JOIN customer_plan cp ON cs.plan_id = cp.id
        JOIN customerdistributor cd ON cd.id = cs.user_id
        WHERE cs.user_id = ?
        ORDER BY cs.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

$hasExpiredSubscription = false;
$subscriptions = [];

while ($row = $result->fetch_assoc()) {
    $subscriptions[] = $row;
    if ($row['isexpired'] == 1) {
        $hasExpiredSubscription = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscription Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="styles.css">

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
        
        .subscription-container {
            max-width: 1100px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-left: 350px;
       }
        
        .subscription-header {
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
        
        .subscription-card {
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .subscription-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .action-btn {
            min-width: 120px;
            margin: 5px;
        }
        
        .reminder-banner {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
        }
        
        .detail-label {
            font-weight: 600;
            color: #6c757d;
        }
    </style>
</head>
<body>
<?php if ($hasExpiredSubscription): ?>
    <div id="expired-alert" class="alert alert-danger alert-dismissible fade show text-center mb-0" role="alert" style="z-index: 9999;">
        <strong>Your subscription has expired!</strong> Renew it now to continue using our services.
    </div>

    <script>
        // Auto-dismiss the alert after 3 seconds
        setTimeout(() => {
            const alert = document.getElementById('expired-alert');
            if (alert) {
                alert.classList.remove('show');
                alert.classList.add('hide');
            }
        }, 3000);
    </script>
<?php endif; ?>


    <div class="subscription-container">
        <div class="subscription-header">
            <h1 class="display-5 fw-bold">
                <i class="bi bi-card-checklist"></i> My Subscription Details
            </h1>
            <p class="lead">View and manage your active subscriptions</p>
        </div>
        
        <?php if (count($subscriptions) > 0): ?>
           <div class="row">
        <?php foreach ($subscriptions as $row):
                    $isExpired = $row['isexpired'] == 1;
                    $isActive = $row['status'] === 'Approved' && !$isExpired;
                    $isPending = $row['status'] === 'Pending';
                    
                    
                ?>
                    <div class="col-md-6 mb-4">
                        <div class="card subscription-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h3 class="card-title">
                                        <?php echo htmlspecialchars($row['plan_name']); ?>
                                        <span class="status-badge <?php 
                                            echo $isActive ? 'status-active' : 
                                                ($isExpired ? 'status-expired' : 'status-pending'); 
                                        ?>">
                                            <?php echo $isExpired ? 'EXPIRED' : $row['status']; ?>
                                        </span>
                                    </h3>
                                    <span class="badge bg-secondary">#<?php echo $row['id']; ?></span>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="detail-label">Start Date</div>
                                            <div><?php echo date('M d, Y', strtotime($row['start_date'])); ?></div>
                                        </div>
                                        <div class="col-6">
                                            <div class="detail-label">End Date</div>
                                            <div><?php echo date('M d, Y', strtotime($row['end_date'])); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="detail-label">Tenure</div>
                                    <div><?php echo $row['tenure']; ?> Months</div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="detail-label">Payment Details</div>
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted">Amount</small>
                                            <div>₹<?php echo number_format($row['amount'], 2); ?></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Payment ID</small>
                                            <div class="text-truncate"><?php echo htmlspecialchars($row['payment_id']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                              <?php
                               $plan_id = $row['plan_id'];
                               $tenure = $row['tenure'];
                               $amount = $row['amount'];
                               $subscription_id = $row['id'];
                               $sector_id = $row['sectorId']; // From user table
                              ?>  
                                <div class="d-flex flex-wrap mt-4">
                                    <?php if ($isExpired): ?>
                                        <a href="payment_gateway.php?plan_id=<?php echo $row['plan_id']; ?>&amount=<?php echo $row['amount']; ?>&tenure=<?php echo $row['tenure']; ?>&customer_id=<?php echo $customerId; ?>&sectorId=<?php echo $sector_id; ?>" 
                                            class="btn btn-danger action-btn">
                                            <i class="bi bi-arrow-repeat"></i> Renew Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <small class="text-muted">
                                    Created on <?php echo date('M d, Y h:i A', strtotime($row['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center py-4">
                <i class="bi bi-info-circle-fill fs-4"></i>
                <h4 class="alert-heading mt-2">No Active Subscriptions</h4>
                <p>You don't have any subscriptions yet. Click below to get started.</p>
                <a href="new_subscriptionplan.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Subscribe Now
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide expired subscription banner after 10 seconds
        if (document.querySelector('.reminder-banner')) {
            setTimeout(() => {
                document.querySelector('.reminder-banner').style.display = 'none';
            }, 10000);
        }
    </script>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>