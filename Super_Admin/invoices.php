<?php
include 'admin_navbar.php';
// Database connection
$host = 'localhost';
$dbname = 'sales_management';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch customers for dropdown
$customers = [];
$customerQuery = "SELECT id, companyName FROM CustomerDistributor";
$customerResult = $conn->query($customerQuery);
if ($customerResult && $customerResult->num_rows > 0) {
    while ($row = $customerResult->fetch_assoc()) {
        $customers[] = $row;
    }
}

// Fetch products for dropdown
$products = [];
$productQuery = "SELECT id, name FROM product";
$productResult = $conn->query($productQuery);
if ($productResult && $productResult->num_rows > 0) {
    while ($row = $productResult->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fetch tax rates for dropdown
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
    $customer_id = $_POST['customer_id'];
    $product_ids = $_POST['product_id'];
    $product_names = $_POST['product_name']; // Manually entered product names
    $qty = $_POST['qty'];
    $price = $_POST['price'];
    $tax_percentage = $_POST['tax_percentage'];

    // Initialize total variables
    $total_amount = 0;
    $total_tax = 0;

    // Insert invoice header
    $invoiceSql = "INSERT INTO invoices (customer_id, total_amount, total_tax, created_at) 
                   VALUES ('$customer_id', 0, 0, NOW())";
    if ($conn->query($invoiceSql)) {
        $invoice_id = $conn->insert_id; // Get the ID of the inserted invoice

        // Insert products for this invoice and calculate totals
        foreach ($product_ids as $index => $product_id) {
            if ($product_id == "other") {
                // Handle manual product entry
                $product_name = $product_names[$index];
            } else {
                // Fetch product name from the database using the product_id
                $product_index = array_search($product_id, array_column($products, 'id'));
                if ($product_index !== false) {
                    $product_name = $products[$product_index]['name']; // Fetch product name from the database
                } else {
                    $product_name = 'Unknown Product'; // Fallback if product is not found
                }
            }

            $quantity = $qty[$index];
            $product_price = $price[$index];
            $tax_rate = $tax_percentage[$index];

            // Get the tax_name based on the selected tax_percentage
            $tax_name = $taxRates[array_search($tax_rate, array_column($taxRates, 'tax_percentage'))]['tax_name'];

            // Calculate total and tax for the product
            $total_before_tax = $quantity * $product_price;
            $total_tax_for_product = $total_before_tax * ($tax_rate / 100);
            $total_with_tax = $total_before_tax + $total_tax_for_product;

            // Insert into invoice_items with tax_name
            $sql = "INSERT INTO invoice_items (invoice_id, product_name, quantity, price, total, tax, tax_name)
                    VALUES ('$invoice_id', '$product_name', '$quantity', '$product_price', '$total_with_tax', '$total_tax_for_product', '$tax_name')";

            if ($conn->query($sql)) {
                // Update the invoice totals
                $total_amount += $total_before_tax;
                $total_tax += $total_tax_for_product;
            } else {
                echo "<div class='alert alert-danger'>Error inserting product: " . $conn->error . "</div>";
            }
        }

        // Update the invoice total amount and total tax
        $updateInvoiceSql = "UPDATE invoices SET total_amount = '$total_amount', total_tax = '$total_tax' 
                             WHERE id = '$invoice_id'";
        if ($conn->query($updateInvoiceSql)) {
            echo "<div class='alert alert-success'>Invoice saved successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error updating invoice totals: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Error creating invoice: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Invoice</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">

<style>
        /* Custom Styles */
        body {
            background: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-top: 50px;
            margin-left:310px;

        }
        .delete-btn, .add-btn {
            padding: 5px 10px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
        }
        .delete-btn { 
            background: #dc3545; 
            color: white; 
        }
        .add-btn { 
            background: #28a745; 
            color: white; 
        }
        .form-control {
            font-size: 16px;
            padding: 10px;
        }
        .button-group {
            display: flex; /* Align buttons side by side */
            gap: 10px; /* Space between buttons */
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">Add Invoice</h1>
    <form method="POST" action="">
        <!-- Customer Dropdown -->
        <div class="form-group">
            <label for="customer">Select Customer:</label>
            <select name="customer_id" id="customer" class="form-control" required>
                <option value="">Select Customer</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= $customer['id']; ?>"><?= htmlspecialchars($customer['companyName']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Invoice Table -->
        <table class="table table-bordered" id="invoice-table">
            <thead class="thead-dark">
                <tr>
                    <th>Product Name</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Tax</th>
                    <th>Total</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <select name="product_id[]" class="form-control product-select">
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id']; ?>"><?= htmlspecialchars($product['name']); ?></option>
                            <?php endforeach; ?>
                            <option value="other">Other (Enter Name)</option>
                        </select>
                        <input type="text" name="product_name[]" class="form-control product-name" placeholder="Product Name (if 'Other')" style="display:none;">
                    </td>
                    <td><input type="number" name="qty[]" class="form-control qty" min="1" placeholder="Qty" required></td>
                    <td><input type="number" name="price[]" class="form-control price" min="0" placeholder="Price" required></td>
                    <td>
                        <select name="tax_percentage[]" class="form-control tax-select" required>
                            <option value="">Select Tax</option>
                            <?php foreach ($taxRates as $tax): ?>
                                <option value="<?= $tax['tax_percentage']; ?>">
                                    <?= htmlspecialchars($tax['tax_name']) . " (" . $tax['tax_percentage'] . "%)"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="text" name="total[]" class="form-control total" readonly></td>
                    <td>
                        <div class="button-group">
                            <button type="button" class="add-btn">Add</button>
                            <button type="button" class="delete-btn">Delete</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <button type="submit" class="btn btn-success btn-block" style="font-size: 18px;">Save Invoice</button>
    </form>
</div>

<script>
// Show input field for other product selection
document.addEventListener('change', function (e) {
    if (e.target.classList.contains('product-select')) {
        const productSelect = e.target;
        const productNameInput = productSelect.closest('tr').querySelector('.product-name');
        if (productSelect.value === "other") {
            productNameInput.style.display = "block";
        } else {
            productNameInput.style.display = "none";
        }
    }
});

// Calculate totals for each row
document.addEventListener('input', function (e) {
    if (e.target.classList.contains('qty') || e.target.classList.contains('price') || e.target.classList.contains('tax-select')) {
        const row = e.target.closest('tr');
        const qty = row.querySelector('.qty').value;
        const price = row.querySelector('.price').value;
        const taxSelect = row.querySelector('.tax-select');
        const totalField = row.querySelector('.total');
        const taxRate = taxSelect.value;
        if (qty && price && taxRate) {
            const totalBeforeTax = qty * price;
            const totalTax = totalBeforeTax * (taxRate / 100);
            const totalWithTax = totalBeforeTax + totalTax;
            totalField.value = totalWithTax.toFixed(2);
        } else {
            totalField.value = '';
        }
    }
});

// Add a new row when 'Add' button is clicked
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('add-btn')) {
        const newRow = document.querySelector('#invoice-table tbody tr').cloneNode(true);
        newRow.querySelectorAll('input').forEach(input => input.value = ''); // Clear the inputs
        newRow.querySelector('.product-name').style.display = 'none'; // Hide the "Other" product name input field
        newRow.querySelector('.product-select').value = ''; // Reset product select
        document.querySelector('#invoice-table tbody').appendChild(newRow);
    }
});

// Delete a row when 'Delete' button is clicked
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('delete-btn')) {
        const row = e.target.closest('tr');
        if (document.querySelectorAll('#invoice-table tbody tr').length > 1) { // Keep at least one row
            row.remove();
        }
    }
});
</script>

</body>
</html>
