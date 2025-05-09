<?php
// Database connection
$host = 'localhost';
$username = 'root'; // Replace with your database username
$password = ''; // Replace with your database password
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
    
    <!-- Include the navbar -->
    <?php include 'navbar.php'; ?>

    <!-- External stylesheets -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="styles.css">

    <style>
        body { font-family: Arial, sans-serif; background-color: #f8f8f8; margin: 0; padding: 0; }
        .container {
            margin-left: 300px; /* Space for the sidebar */
            margin-top: 50px;
            width: 1100px;
            position: relative;
            word-spacing: 1px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            border-radius: 5px;
            padding: 20px;
        }
        h2 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; border: 1px solid #ddd; text-align: center; }
        table th { background-color: #007BFF; color: white; }
        table tr:nth-child(even) { background-color: #f9f9f9; }
        table tr:hover { background-color: #f1f1f1; }
        .action-btn { padding: 5px 10px; border: none; color: white; cursor: pointer; border-radius: 3px; }
        .edit-btn { background-color: #28a745; }
        .delete-btn { background-color: #dc3545; }
        .add-btn { display: inline-block; margin-bottom: 20px; background-color: #007BFF; color: white; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Orders</h2>
        <a href="add_neworder.php" class="add-btn">➕ Add New Order</a>
        <table>
            <thead>
                <tr>
                    <th>Order Detail ID</th>
                    <th>Customer Name</th>
                    <th>Status</th>
                    <th>Quantity</th>
                    <th>Product Name</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['orderDetailId']}</td>
                                <td>{$row['customerName']}</td>
                                <td>{$row['status']}</td>
                                <td>{$row['quantity']}</td>
                                <td>{$row['productName']}</td>
                                <td>{$row['createdAt']}</td>
                                <td>
                                    <button class='action-btn edit-btn' onclick=\"editOrder({$row['orderDetailId']})\">Edit</button>
                                    <button class='action-btn delete-btn' onclick=\"deleteOrder({$row['orderDetailId']})\">Delete</button>
                                </td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='7'>No orders found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

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
