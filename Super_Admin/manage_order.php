<?php
include 'admin_navbar.php';
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'sales_management';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all order details with product or custom product name
$sql = "SELECT od.id AS orderDetailId, c.companyName AS customerName, od.status, od.createdAt, 
               od.quantity, COALESCE(od.custom_product_name, p.name) AS productName 
        FROM order_details od
        INNER JOIN customerdistributor c ON od.customerId = c.id
        LEFT JOIN product p ON od.productId = p.id
        ORDER BY od.createdAt DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            margin-left: 320px;
            margin-top: 30px;
        }
        
        .card {
            border: none;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
            font-weight: 700;
            color: #4e73df;
        }
        
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #858796;
        }
        
        .table th {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e3e6f0;
            font-weight: 700;
            color: #4e73df;
            background-color: #f8f9fc;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.1em;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e3e6f0;
        }
        
        .table tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
        }
        
        .badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 0.25rem;
        }
        
        .badge-success {
            background-color: var(--success-color);
        }
        
        .badge-warning {
            background-color: var(--warning-color);
            color: #1f2d3d;
        }
        
        .badge-danger {
            background-color: var(--danger-color);
        }
        
        .badge-info {
            background-color: var(--info-color);
        }
        
        .action-btns {
            white-space: nowrap; /* Keep buttons in one line */
        }
        
        .action-btns .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
            margin-right: 0.25rem;
            display: inline-block; /* Ensure buttons stay inline */
        }
        
        .page-title {
            color: var(--dark-color);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.35em 0.65em;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                margin-left: 0;
                padding: 15px;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .action-btns .btn {
                margin-bottom: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800 page-title">Order Management</h1>
            <a href="add_neworder.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Add New Order
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $statusClass = '';
                                    switch(strtolower($row['status'])) {
                                        case 'completed':
                                            $statusClass = 'badge-success';
                                            break;
                                        case 'pending':
                                            $statusClass = 'badge-warning';
                                            break;
                                        case 'cancelled':
                                            $statusClass = 'badge-danger';
                                            break;
                                        default:
                                            $statusClass = 'badge-info';
                                    }
                                    
                                    echo "<tr>
                                            <td>#{$row['orderDetailId']}</td>
                                            <td>{$row['customerName']}</td>
                                            <td><span class='badge $statusClass status-badge'>{$row['status']}</span></td>
                                            <td>{$row['productName']}</td>
                                            <td>{$row['quantity']}</td>
                                            <td>" . date('M d, Y', strtotime($row['createdAt'])) . "</td>
                                            <td class='action-btns'>
                                                <button class='btn btn-sm btn-primary' onclick=\"editOrder({$row['orderDetailId']})\">
                                                    <i class='fas fa-edit'></i> Edit
                                                </button>
                                                <button class='btn btn-sm btn-danger' onclick=\"deleteOrder({$row['orderDetailId']})\">
                                                    <i class='fas fa-trash'></i> Delete
                                                </button>
                                            </td>
                                          </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center'>No orders found</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function editOrder(orderDetailId) {
            window.location.href = `edit_order.php?id=${orderDetailId}`;
        }

        function deleteOrder(orderDetailId) {
            if (confirm("Are you sure you want to delete this order?")) {
                window.location.href = `delete_order.php?id=${orderDetailId}`;
            }
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>