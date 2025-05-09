<?php
include 'config.php';
include 'navbar.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = "";
$orderAdded = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch form data
    $customer = $_POST['customer'];
    $status = $_POST['status'];
    $products = $_POST['products']; // Array of product IDs
    $quantities = $_POST['quantities']; // Array of quantities

    // Insert into order_details table for standard products
    foreach ($products as $index => $productId) {
        $quantity = isset($quantities[$index]) ? $quantities[$index] : null;
        if ($productId) {
            $conn->query("INSERT INTO order_details (customerId, status, productId, quantity, custom_product_name) 
                          VALUES ('$customer', '$status', '$productId', '$quantity', NULL)");
        }
    }

    $successMessage = "New order created successfully.";
    $orderAdded = true;

    // Close the connection
    $conn->close();

    // Redirect to avoid form re-submission on page refresh
    if ($orderAdded) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Order</title>
    <link rel="stylesheet" href="styles.css">

    <style>
        /* Old CSS Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
        }

        h2, h3 {
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            font-weight: bold;
        }

        select, input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button[type="button"] {
            background-color: #f44336;
        }

        button:hover {
            background-color: #45a049;
        }

        .success-message {
            margin-top: 20px;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            text-align: center;
            border-radius: 5px;
        }

        .product-row {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .product-row select, .product-row input {
            width: auto;
            flex: 1;
        }

        .form-group button {
            background-color: #f44336;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Orders</h2>
        <h3>Add New Order</h3>
        <form method="POST" action="">
            <div class="form-group">
                <label for="customer">Customer:</label>
                <select id="customer" name="customer" required>
                    <option value="">Select...</option>
                    <?php
                    $result = $conn->query("SELECT id, companyName FROM customerdistributor");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['companyName']}</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div id="product-container">
                <div class="form-group product-row">
                    <select name="products[]">
                        <option value="">Select...</option>
                        <?php
                        $result = $conn->query("SELECT id, name FROM product");
                        if ($result) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<option value='{$row['id']}'>{$row['name']}</option>";
                            }
                        }
                        ?>
                    </select>
                    <input type="number" name="quantities[]" min="1" placeholder="Quantity">
                    <button type="button" onclick="removeProductRow(this)">✖</button>
                </div>
            </div>

            <div class="form-group" style="display: flex; gap: 10px; justify-content: flex-start;">
                <button type="button" onclick="addProductRow()">➕ Add Product</button>
            </div>

            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="IN_PROCESS">In Process</option>
                    <option value="SHIPPED">Shipped</option>
                    <option value="DELIVERED">Delivered</option>
                </select>
            </div>
            <div class="form-group" style="text-align: center;">
                <button type="submit" id="submitButton">✔ Submit</button>
            </div>
        </form>

        <?php if ($successMessage): ?>
            <div class="success-message">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function addProductRow() {
            const container = document.getElementById('product-container');
            const row = document.createElement('div');
            row.className = 'form-group product-row';
            row.innerHTML = ` 
                <select name="products[]">
                    <option value="">Select...</option>
                    <?php
                    $result = $conn->query("SELECT id, name FROM product");
                    if ($result) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['id']}'>{$row['name']}</option>";
                        }
                    }
                    ?>
                </select>
                <input type="number" name="quantities[]" min="1" placeholder="Quantity">
                <button type="button" onclick="removeProductRow(this)">✖</button>
            `;
            container.appendChild(row);
        }

        function removeProductRow(button) {
            const row = button.parentElement;
            row.remove();
        }
    </script>
</body>
</html>
