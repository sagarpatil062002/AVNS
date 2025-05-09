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

// Fetch customer ID from session or form (assuming customer is logged in)
$customerId = 2; // You can replace this with the actual customer ID from session

// Query to fetch orders for the customer
$orderQuery = "SELECT od.id, od.status, od.createdAt, od.updatedAt, p.name as productName, od.quantity 
               FROM order_details od
               LEFT JOIN product p ON od.productId = p.id
               WHERE od.customerId = '$customerId'";

$result = $conn->query($orderQuery);

// Check if orders exist
if ($result->num_rows > 0) {
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
} else {
    $orders = null;
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Orders</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 800px;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #f4f4f9;
        }

        .status {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Your Orders</h2>

        <?php if ($orders !== null): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo $order['productName']; ?></td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td class="status"><?php echo $order['status']; ?></td>
                            <td><?php echo $order['createdAt']; ?></td>
                            <td><?php echo $order['updatedAt'] ? $order['updatedAt'] : 'N/A'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders found for this customer.</p>
        <?php endif; ?>
    </div>
</body>
</html>
