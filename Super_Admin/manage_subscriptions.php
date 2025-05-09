<?php
session_start();
include('config.php'); // Include database connection
include 'admin_navbar.php';

$sql = "SELECT * FROM customer_plan ORDER BY id DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subscriptions</title>
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
            --border-radius: 12px;
            --card-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #4a4a4a;
        }

        .main-container {
            margin: 30px auto;
            padding: 0;
            margin-left: 310px;
            max-width: 95%;
        }

        .subscription-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 25px;
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
            padding: 0;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: 600;
            padding: 15px;
            border-bottom: 2px solid #e9ecef;
            position: sticky;
            top: 0;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0;
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .badge {
            padding: 6px 10px;
            font-weight: 500;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .badge-yes {
            background-color: #e6f7ee;
            color: #00a854;
        }

        .badge-no {
            background-color: #fff1f0;
            color: #f5222d;
        }

        .action-buttons .btn {
            margin-right: 5px;
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-buttons .btn:last-child {
            margin-right: 0;
        }

        .btn-add {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            margin-top: 20px;
        }

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 60px;
            color: #dee2e6;
            margin-bottom: 15px;
        }

        .empty-state h4 {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .price-cell {
            font-weight: 600;
            color: var(--primary-color);
        }

        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
                padding: 15px;
            }
        }

        @media (max-width: 768px) {
            .table thead {
                display: none;
            }
            
            .table tbody tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 15px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            }
            
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none;
                padding: 10px 5px;
            }
            
            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--dark-color);
                margin-right: 15px;
                flex: 1;
            }
            
            .table tbody td .cell-content {
                flex: 2;
                text-align: right;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="subscription-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-file-invoice-dollar"></i>Manage Subscription Plans
                </h2>
                <a href="add_subscription.php" class="btn btn-light">
                    <i class="fas fa-plus me-2"></i>Add New
                </a>
            </div>
            
            <div class="card-body">
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Plan Name</th>
                                    <th>Price</th>
                                    <th>Support Calls</th>
                                    <th>Rewards</th>
                                    <th>Popular</th>
                                    <th>Sector</th>
                                    <th>Created</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="ID"><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td data-label="Plan Name"><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td data-label="Price" class="price-cell">₹<?php echo htmlspecialchars(number_format($row['base_price'], 2)); ?></td>
                                        <td data-label="Support Calls"><?php echo htmlspecialchars($row['max_support_calls']); ?></td>
                                        <td data-label="Rewards"><?php echo htmlspecialchars($row['rewards_points']); ?></td>
                                        <td data-label="Popular">
                                            <span class="badge <?php echo $row['most_popular'] ? 'badge-yes' : 'badge-no'; ?>">
                                                <?php echo $row['most_popular'] ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                        <td data-label="Sector"><?php echo htmlspecialchars($row['sectorName'] ?? 'N/A'); ?></td>
                                        <td data-label="Created"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td data-label="Updated"><?php echo date('M d, Y', strtotime($row['updated_at'])); ?></td>
                                        <td data-label="Actions" class="action-buttons">
                                            <a href="edit_subscription.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-primary btn-sm"
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete_subscription.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this subscription?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-file-invoice"></i>
                        <h4>No Subscription Plans Found</h4>
                        <p>You haven't created any subscription plans yet.</p>
                        <a href="add_subscription.php" class="btn btn-primary btn-add">
                            <i class="fas fa-plus me-2"></i>Create First Plan
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('table tbody tr');
            rows.forEach((row, index) => {
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>