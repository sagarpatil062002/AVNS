<?php
ob_start(); // Start output buffering
include 'admin_navbar.php';

// Database connection
include('Config.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch order details based on orderId from the URL
$orderId = isset($_GET['id']) ? $_GET['id'] : 0;

if ($orderId) {
    $sql = "SELECT od.*, COALESCE(od.custom_product_name, p.name) AS productName, 
                   cd.companyName AS customerName
            FROM order_details od 
            LEFT JOIN product p ON od.productId = p.id 
            LEFT JOIN customerdistributor cd ON od.customerId = cd.id
            WHERE od.id = $orderId";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $order = $result->fetch_assoc();
    } else {
        die("Order not found.");
    }
}

// Update order details when the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer = $_POST['customer'];
    $status = $_POST['status'];

    // Update the order details
    $updateSql = "UPDATE `order_details` 
                  SET customerId = '$customer', status = '$status', updatedAt = NOW() 
                  WHERE id = $orderId";

    if ($conn->query($updateSql) === TRUE) {
        // After updating, redirect to manage_orders.php
        header("Location: manage_order.php"); 
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order #<?= $orderId ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 60%;
            margin: 30px auto 30px calc(250px + 30px);
            background: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            padding: 30px;
            transition: all 0.3s ease;
            margin-left: 350px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .page-title {
            color: var(--secondary-color);
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-right: 10px;
            font-size: 1.5rem;
        }

        .order-id {
            background-color: var(--light-color);
            color: var(--dark-color);
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }

        .form-label i {
            margin-right: 8px;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .form-control {
            padding: 10px 15px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background-color: #fff;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
            outline: none;
        }

        .form-control:disabled {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .btn-group {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-outline {
            background-color: transparent;
            border: 1px solid #ced4da;
            color: #6c757d;
        }

        .btn-outline:hover {
            background-color: #f8f9fa;
            border-color: #adb5bd;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-in_process {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-shipped {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }

        .order-info-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            width: 150px;
        }

        .info-value {
            color: #212529;
        }

        @media (max-width: 992px) {
            .container {
                width: 80%;
                margin-left: calc(200px + 20px);
            }
        }

        @media (max-width: 768px) {
            .container {
                width: 90%;
                margin: 20px auto;
                padding: 20px;
            }
            
            .form-group {
                flex-direction: column;
            }
            
            .btn-group {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title"><i class="fas fa-edit"></i>Edit Order</h1>
            <span class="order-id">Order #<?= $orderId ?></span>
        </div>
        
        <div class="order-info-card">
            <div class="info-row">
                <div class="info-label">Product:</div>
                <div class="info-value"><?= htmlspecialchars($order['productName']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Current Customer:</div>
                <div class="info-value"><?= htmlspecialchars($order['customerName']) ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Current Status:</div>
                <div class="info-value">
                    <span class="status-badge status-<?= strtolower(explode('_', $order['status'])[0]) ?>">
                        <?= str_replace('_', ' ', $order['status']) ?>
                    </span>
                </div>
            </div>
        </div>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="customer" class="form-label"><i class="fas fa-user"></i>Customer</label>
                <select id="customer" name="customer" class="form-control" required>
                    <option value="">Select customer...</option>
                    <?php
                    // Fetch customer list
                    $result = $conn->query("SELECT id, companyName FROM customerdistributor");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'" . ($row['id'] == $order['customerId'] ? ' selected' : '') . ">{$row['companyName']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status" class="form-label"><i class="fas fa-tasks"></i>Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="IN_PROCESS" <?= ($order['status'] == 'IN_PROCESS') ? 'selected' : '' ?>>In Process</option>
                    <option value="SHIPPED" <?= ($order['status'] == 'SHIPPED') ? 'selected' : '' ?>>Shipped</option>
                    <option value="DELIVERED" <?= ($order['status'] == 'DELIVERED') ? 'selected' : '' ?>>Delivered</option>
                </select>
            </div>
            
            <div class="btn-group">
                <a href="manage_order.php" class="btn btn-outline">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Order
                </button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            // Add animation to buttons
            $('.btn').hover(
                function() {
                    $(this).css('transform', 'translateY(-2px)');
                },
                function() {
                    $(this).css('transform', 'translateY(0)');
                }
            );
            
            // Status color change on select
            $('#status').change(function() {
                const status = $(this).val().toLowerCase().replace('_', ' ');
                $('.status-badge')
                    .removeClass('status-in_process status-shipped status-delivered')
                    .addClass('status-' + status.replace(' ', '_'))
                    .text(status.charAt(0).toUpperCase() + status.slice(1));
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
ob_end_flush(); // End output buffering
?>