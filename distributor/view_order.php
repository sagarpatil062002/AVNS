<?php
// Database connection
include('Config.php');


session_start();

// Check if the customer is logged in
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
} else {
    die("No customer is logged in. Please log in.");
}


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch customer data to display name (optional)
$customerQuery = "SELECT companyName FROM distributor WHERE id = '$customerId'";
$customerResult = $conn->query($customerQuery);
$customerName = "";
if ($customerResult && $customerResult->num_rows > 0) {
    $customerData = $customerResult->fetch_assoc();
    $customerName = $customerData['companyName'];
}

// Fetch orders with product names or custom product names
$orderQuery = "
    SELECT od.id AS orderId, od.status, od.quantity, od.createdAt, od.updatedAt, 
           COALESCE(p.name, od.custom_product_name) AS productName
    FROM order_details od
    LEFT JOIN product p ON od.productId = p.id
    WHERE od.customerId = '$customerId'
    ORDER BY od.createdAt DESC
";
$orderResult = $conn->query($orderQuery);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <title>Customer Orders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 900px;
            margin: 30px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .order-table th, .order-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .order-table th {
            background-color: #007bff;
            color: white;
        }

        .order-table td {
            background-color: #f9f9f9;
        }

        .order-table tr:hover {
            background-color: #f1f1f1;
        }

        .no-orders {
            text-align: center;
            color: #777;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav>
        <div class="logo-name">
            <div class="logo-image">
               <img src="#" alt="">
            </div>
            <span class="logo_name">AVNS</span>
        </div>
        <div class="menu-items">
            <ul class="nav-links">
                <li><a href="dashboard.php">
                    <i class="uil uil-estate"></i>
                    <span class="link-name">Dashboard</span>
                </a></li>
                
                <li><a href="view_order.php">
                    <i class="uil uil-clipboard"></i>
                    <span class="link-name">Order</span>
                </a></li>
                <li><a href="view_Adminquotation.php">
                    <i class="uil uil-laptop"></i>
                    <span class="link-name">Quotation</span>
                </a></li>
                <li><a href="view_invoices.php">
                    <i class="uil uil-laptop"></i>
                    <span class="link-name">Invoices</span>
                </a></li>
                <li><a href="profile.php">
                    <i class="uil uil-laptop"></i>
                    <span class="link-name">Profile</span>
                </a></li>
            </ul>
            <ul class="logout-mode">
                <li><a href="../logout.php">
                    <i class="uil uil-signout"></i>
                    <span class="link-name">Logout</span>
                </a></li>
                <li>
                  <div class="mode-toggle">
                    <span class="switch"></span>
                  </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <h2>Orders for <?php echo $customerName ? htmlspecialchars($customerName) : 'Customer'; ?></h2>

        <?php if ($orderResult && $orderResult->num_rows > 0): ?>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product Name</th>
                        <th>Status</th>
                        <th>Quantity</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orderResult->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $order['orderId']; ?></td>
                            <td><?php echo htmlspecialchars($order['productName']); ?></td>
                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td><?php echo $order['createdAt']; ?></td>
                            <td><?php echo $order['updatedAt'] ? $order['updatedAt'] : 'N/A'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-orders">No orders found for this customer.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
