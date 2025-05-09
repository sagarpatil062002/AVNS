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
    $sql = "SELECT od.*, COALESCE(od.custom_product_name, p.name) AS productName 
            FROM order_details od 
            LEFT JOIN product p ON od.productId = p.id 
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
    <title>Edit Order</title>
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 60%;
            margin: 50px auto;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            border-radius: 5px;
            padding: 20px;
            margin-right:200px;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .form-group label {
            width: 150px;
            font-weight: bold;
            color: #555;
        }

        .form-group select, 
        .form-group input[type="text"], 
        .form-group button {
            width: 60%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-group input[type="text"] {
            width: calc(60% - 10px);
        }

        .submit-btn {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }
        
        .cancel-link {
            margin-left: 20px;
            color: #777;
            text-decoration: none;
        }

        .cancel-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Order</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="product">Product/Service:</label>
                <select id="product" name="product" disabled>
                    <option value="">Select...</option>
                    <?php
                    // Fetch product list
                    $result = $conn->query("SELECT id, name FROM product");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'" . ($row['id'] == $order['productId'] ? ' selected' : '') . ">{$row['name']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="customer">Customer:</label>
                <select id="customer" name="customer" required>
                    <option value="">Select...</option>
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
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="IN_PROCESS" <?php echo ($order['status'] == 'IN_PROCESS') ? 'selected' : ''; ?>>In Process</option>
                    <option value="SHIPPED" <?php echo ($order['status'] == 'SHIPPED') ? 'selected' : ''; ?>>Shipped</option>
                    <option value="DELIVERED" <?php echo ($order['status'] == 'DELIVERED') ? 'selected' : ''; ?>>Delivered</option>
                </select>
            </div>
            <div class="form-group" style="display: flex; justify-content: center;">
                <button type="submit" class="submit-btn">✔ UPDATE</button>
            </div>
        </form>
    </div>
</body>
</html>

<?php
$conn->close();
ob_end_flush(); // End output buffering
?>
