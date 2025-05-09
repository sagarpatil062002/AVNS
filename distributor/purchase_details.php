<?php
// Start session to access distributor id
session_start();
include('Dnav.php');
// Database connection
include('Config.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in and session is set
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to submit the purchase details.");
}

// Fetch Super Admins
$superAdmins = [];
$superAdminQuery = "SELECT id, name FROM users";
$superAdminResult = $conn->query($superAdminQuery);
if ($superAdminResult && $superAdminResult->num_rows > 0) {
    while ($row = $superAdminResult->fetch_assoc()) {
        $superAdmins[] = $row;
    }
}

// Fetch products
$products = [];
$productQuery = "SELECT id, name FROM product";
$productResult = $conn->query($productQuery);
if ($productResult && $productResult->num_rows > 0) {
    while ($row = $productResult->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fetch tax rates
$taxRates = [];
$taxQuery = "SELECT tax_name, tax_percentage FROM tax_rates";
$taxResult = $conn->query($taxQuery);
if ($taxResult && $taxResult->num_rows > 0) {
    while ($row = $taxResult->fetch_assoc()) {
        $taxRates[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $distributor_id = $_SESSION['user_id']; // Use the distributor ID from session
    $super_admin_id = $_POST['super_admin_id'];
    $product_ids = $_POST['product_id'];
    $qty = $_POST['qty'];
    $price = $_POST['price'];
    $tax_percentage = $_POST['tax_percentage'];

    $total_amount = 0;
    $total_tax = 0;

    // Insert purchase details
    $purchaseSql = "INSERT INTO purchase_details (distributor_id, super_admin_id, total_amount, total_tax, created_at) 
                    VALUES ('$distributor_id', '$super_admin_id', 0, 0, NOW())";
    if ($conn->query($purchaseSql)) {
        $purchase_id = $conn->insert_id;

        foreach ($product_ids as $index => $product_id) {
            // Handle custom product name if selected
            $product_name = $product_id === 'other' ? $_POST['product_name'][$index] : $products[array_search($product_id, array_column($products, 'id'))]['name'];
            $quantity = $qty[$index];
            $product_price = $price[$index];
            $tax_rate = $tax_percentage[$index];
            $tax_name = $taxRates[array_search($tax_rate, array_column($taxRates, 'tax_percentage'))]['tax_name'];

            // Calculate totals
            $total_before_tax = $quantity * $product_price;
            $total_tax_for_product = $total_before_tax * ($tax_rate / 100);
            $total_with_tax = $total_before_tax + $total_tax_for_product;

            // Insert purchase item
            $itemSql = "INSERT INTO purchase_items (purchase_id, product_name, quantity, price, total, tax, tax_name) 
                        VALUES ('$purchase_id', '$product_name', '$quantity', '$product_price', '$total_with_tax', '$total_tax_for_product', '$tax_name')";
            if ($conn->query($itemSql)) {
                // Update totals for purchase
                $total_amount += $total_before_tax;
                $total_tax += $total_tax_for_product;
            }
        }

        // Update purchase details with final totals
        $updatePurchaseSql = "UPDATE purchase_details SET total_amount = '$total_amount', total_tax = '$total_tax' WHERE id = '$purchase_id'";
        $conn->query($updatePurchaseSql);

        echo "<div class='alert alert-success'>Purchase details saved successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error creating purchase details: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Purchase Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">   
     <link rel="stylesheet" href="style.css">
     

<style>
        /* Custom Styles */
        body {
            background: #fff;
            font-family: Arial, sans-serif;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-top: 50px;
            margin-left: 310px; /* Space for the sidebar */
        width: 1200px;
        position: relative;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-top: 20px;
        }
        label {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
        }
        select,
        input {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        thead {
            background-color: #f8f9fa;
        }
        th,
        td {
            text-align: left;
            padding: 10px;
            border: 1px solid #ddd;
        }
        th {
            font-weight: bold;
            font-size: 14px;
        }
        td {
            vertical-align: middle;
        }
        td button {
            display: inline-block;
            padding: 8px 12px;
            font-size: 14px;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 2px;
        }
        button[type="button"] {
            background-color: #007bff;
        }
        button[type="button"]:hover {
            background-color: #0056b3;
        }
        button[type="button"]:last-child {
            background-color: #dc3545;
        }
        button[type="button"]:last-child:hover {
            background-color: #a71d2a;
        }
        p {
            font-size: 16px;
            font-weight: bold;
            text-align: right;
            margin: 0;
            padding: 0 10px;
        }
        #grand-total {
            color: #007bff;
        }
        button[type="submit"] {
            display: block;
            width: 100%;
            padding: 10px;
            font-size: 16px;
            color: #fff;
            background-color: #28a745;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
        }
        button[type="submit"]:hover {
            background-color: #218838;
        }
        @media (max-width: 768px) {
            table {
                font-size: 12px;
            }
            th,
            td {
                padding: 8px;
            }
            td button {
                padding: 6px 10px;
                font-size: 12px;
            }
            button[type="submit"] {
                font-size: 14px;
                padding: 8px;
            }
        }
        @media (max-width: 576px) {
            form {
                max-width: 100%;
                padding: 10px;
            }
            table {
                font-size: 12px;
            }
            th,
            td {
                padding: 6px;
            }
            p {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Purchase Details</h1>
        <form method="POST">
            <label for="super_admin">Select Super Admin:</label>
            <select name="super_admin_id" id="super_admin" required>
                <option value="">Select Super Admin</option>
                <?php foreach ($superAdmins as $admin): ?>
                    <option value="<?= $admin['id']; ?>"><?= htmlspecialchars($admin['name']); ?></option>
                <?php endforeach; ?>
            </select>

            <table id="purchase-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Tax</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <select name="product_id[]" onchange="handleProductChange(this)" required>
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id']; ?>"><?= htmlspecialchars($product['name']); ?></option>
                                <?php endforeach; ?>
                                <option value="other">Other</option>
                            </select>
                            <input type="text" name="product_name[]" placeholder="Enter Product Name" style="display:none;">
                        </td>
                        <td><input type="number" name="qty[]" oninput="calculateTotal(this)" min="1" required></td>
                        <td><input type="number" name="price[]" oninput="calculateTotal(this)" min="0" required></td>
                        <td>
                            <select name="tax_percentage[]" onchange="calculateTotal(this)" required>
                                <?php foreach ($taxRates as $tax): ?>
                                    <option value="<?= $tax['tax_percentage']; ?>">
                                        <?= htmlspecialchars($tax['tax_name']) . " (" . $tax['tax_percentage'] . "%)"; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><input type="number" name="total[]" readonly></td>
                        <td>
                            <button type="button" onclick="addRow()">Add</button>
                            <button type="button" onclick="removeRow(this)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p>Grand Total: <span id="grand-total">0.00</span></p>
            <button type="submit">Submit</button>
        </form>
    </div>

    <script>
        function handleProductChange(selectElement) {
            const row = selectElement.closest('tr');
            const productSelect = row.querySelector('[name="product_id[]"]');
            const productNameInput = row.querySelector('[name="product_name[]"]');
            
            if (productSelect.value === 'other') {
                productNameInput.style.display = 'block'; // Show the custom product name input
            } else {
                productNameInput.style.display = 'none'; // Hide the custom product name input
            }
        }

        function calculateTotal(element) {
            const row = element.closest('tr');
            const qty = parseFloat(row.querySelector('[name="qty[]"]').value) || 0;
            const price = parseFloat(row.querySelector('[name="price[]"]').value) || 0;
            const tax = parseFloat(row.querySelector('[name="tax_percentage[]"]').value) || 0;

            const totalBeforeTax = qty * price;
            const totalTax = totalBeforeTax * (tax / 100);
            const total = totalBeforeTax + totalTax;

            row.querySelector('[name="total[]"]').value = total.toFixed(2);
            updateGrandTotal();
        }

        function updateGrandTotal() {
            const totals = document.querySelectorAll('[name="total[]"]');
            let grandTotal = 0;
            totals.forEach(totalField => {
                grandTotal += parseFloat(totalField.value) || 0;
            });
            document.getElementById('grand-total').textContent = grandTotal.toFixed(2);
        }

        function addRow() {
            const table = document.getElementById('purchase-table').querySelector('tbody');
            const newRow = table.querySelector('tr').cloneNode(true);
            newRow.querySelectorAll('input, select').forEach(input => input.value = '');
            newRow.querySelector('[name="product_name[]"]').style.display = 'none'; // Hide the input for new rows
            table.appendChild(newRow);
        }

        function removeRow(button) {
            const row = button.closest('tr');
            row.remove();
            updateGrandTotal();
        }
    </script>
</body>
</html>