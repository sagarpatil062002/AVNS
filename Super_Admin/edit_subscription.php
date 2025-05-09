<?php
// Include database configuration
include 'config.php';
include 'admin_navbar.php';

// Fetch subscription details to edit
if (isset($_GET['id'])) {
    $subscriptionId = $_GET['id'];
    $sql = "SELECT cp.id, cp.name AS type, cp.base_price AS price, cp.max_support_calls AS maxSupportCalls, 
                   cp.rewards_points AS rewards, cp.sectorName AS sector 
            FROM customer_plan cp 
            WHERE cp.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $subscriptionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $subscription = $result->fetch_assoc();
    } else {
        header("Location: manage_subscription.php?message=Subscription%20not%20found&type=error");
        exit;
    }
} else {
    header("Location: manage_subscription.php?message=Invalid%20request&type=error");
    exit;
}

// Handle form submission to update subscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $type = $_POST['type'];
    $price = $_POST['price'];
    $maxSupportCalls = $_POST['maxSupportCalls'];
    $rewards = $_POST['rewards'];
    $sector = $_POST['sector'];

    $updateSql = "UPDATE customer_plan SET name = ?, base_price = ?, max_support_calls = ?, rewards_points = ?, sectorName = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param('siiisi', $type, $price, $maxSupportCalls, $rewards, $sector, $id);

    if ($updateStmt->execute()) {
        header("Location: manage_subscription.php?message=Subscription%20updated%20successfully&type=success");
    } else {
        $error = "Error updating subscription: " . $conn->error;
    }
}

// Fetch sectors for dropdown
$sectorSql = "SELECT id, sectorName FROM sectors";
$sectorsResult = $conn->query($sectorSql);
$sectors = $sectorsResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Subscription Plan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --info-color: #4895ef;
            --border-radius: 12px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #4a4a4a;
        }

        .main-container {
            margin: 30px auto;
            padding: 30px;
            max-width: 900px;
            margin-left: 310px;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px 25px;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            font-size: 1.2em;
        }

        .card-body {
            padding: 30px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }

        .btn {
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-outline-secondary {
            border-color: #e0e0e0;
        }

        .btn-outline-secondary:hover {
            background-color: #f8f9fa;
        }

        .subscription-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .subscription-info-item {
            display: flex;
            margin-bottom: 8px;
        }

        .subscription-info-label {
            font-weight: 600;
            min-width: 160px;
            color: #6c757d;
        }

        .error-message {
            color: var(--danger-color);
            background-color: #fff1f0;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 1.2em;
        }

        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .subscription-info-item {
                flex-direction: column;
            }
            
            .subscription-info-label {
                margin-bottom: 4px;
                min-width: auto;
            }
            
            .d-flex {
                flex-direction: column;
                gap: 10px;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-file-invoice-dollar"></i> Edit Subscription Plan
                </h2>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <div class="subscription-info">
                    <div class="subscription-info-item">
                        <span class="subscription-info-label">Current Plan Name:</span>
                        <span><?php echo htmlspecialchars($subscription['type']); ?></span>
                    </div>
                    <div class="subscription-info-item">
                        <span class="subscription-info-label">Current Price:</span>
                        <span>₹<?php echo number_format($subscription['price'], 2); ?></span>
                    </div>
                    <div class="subscription-info-item">
                        <span class="subscription-info-label">Current Support Calls:</span>
                        <span><?php echo htmlspecialchars($subscription['maxSupportCalls']); ?></span>
                    </div>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($subscription['id']); ?>">

                    <div class="mb-4">
                        <label for="type" class="form-label">Plan Name</label>
                        <input type="text" class="form-control" id="type" name="type" 
                               value="<?php echo htmlspecialchars($subscription['type']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="price" class="form-label">Price (₹)</label>
                        <input type="number" class="form-control" id="price" name="price" 
                               value="<?php echo htmlspecialchars($subscription['price']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="maxSupportCalls" class="form-label">Max Support Calls</label>
                        <input type="number" class="form-control" id="maxSupportCalls" name="maxSupportCalls" 
                               value="<?php echo htmlspecialchars($subscription['maxSupportCalls']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="rewards" class="form-label">Reward Points</label>
                        <input type="number" class="form-control" id="rewards" name="rewards" 
                               value="<?php echo htmlspecialchars($subscription['rewards']); ?>" required>
                    </div>

                    <div class="mb-4">
                        <label for="sector" class="form-label">Sector</label>
                        <select class="form-select" id="sector" name="sector" required>
                            <option value="">Select a sector</option>
                            <?php foreach ($sectors as $sector): ?>
                                <option value="<?php echo htmlspecialchars($sector['id']); ?>" 
                                    <?php echo $sector['id'] == $subscription['sector'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sector['sectorName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-3">
                        <a href="manage_subscription.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add animation to form elements
        document.addEventListener('DOMContentLoaded', function() {
            const formElements = document.querySelectorAll('.form-control, .form-select, .btn');
            formElements.forEach((el, index) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>