<?php 
// Start the session
session_start();
include 'navbar.php';

// Database connection
include('Config.php');


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_SESSION['user_id'])) {
    $superAdminId = $_SESSION['user_id']; // Logged-in Super Admin ID
} else {
    die("No Super Admin is logged in. Please log in.");
}

// Fetch Super Admin details based on session
$superAdminQuery = "SELECT name FROM users WHERE id = ?";
$stmt = $conn->prepare($superAdminQuery);
$stmt->bind_param("i", $superAdminId);
$stmt->execute();
$stmt->bind_result($superAdminName);
$stmt->fetch();
$stmt->close();

// Fetch products for dropdown
$products = [];
$productQuery = "SELECT id, name FROM Product";
$productResult = $conn->query($productQuery);
if ($productResult && $productResult->num_rows > 0) {
    while ($row = $productResult->fetch_assoc()) {
        $products[] = $row;
    }
}

// Fetch distributors for dropdown
$distributors = [];
$distributorQuery = "SELECT id, companyName FROM distributor";
$distributorResult = $conn->query($distributorQuery);
if ($distributorResult && $distributorResult->num_rows > 0) {
    while ($row = $distributorResult->fetch_assoc()) {
        $distributors[] = $row;
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
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject = $_POST['subject'];
    $distributor_id = $_POST['distributor_id'];
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $price_offered = $_POST['price_offered'];
    $other_product_names = $_POST['other_product_name'];
    $status = 'PENDING';

    if (!empty($product_ids)) {
        // Insert quotation header with customerId as NULL
        $stmt = $conn->prepare("INSERT INTO quotation_header (subject, customerId, distributorId, superAdminId, status, createdAt) VALUES (?, NULL, ?, ?, ?, NOW())");
        $stmt->bind_param("siis", $subject, $distributor_id, $superAdminId, $status);
        if ($stmt->execute()) {
            // Get the inserted quotation header ID
            $quotationHeaderId = $stmt->insert_id;

            // Insert each product into the quotation_product table
            foreach ($product_ids as $index => $product_id) {
                $quantity = $quantities[$index];
                $price = $price_offered[$index];

                if ($product_id === 'other') {
                    // Handle "Other" product
                    $productName = $other_product_names[$index];
                    if (!empty($productName)) {
                        // Insert new product into the Product table
                        $stmt = $conn->prepare("INSERT INTO Product (name) VALUES (?)");
                        $stmt->bind_param("s", $productName);
                        if ($stmt->execute()) {
                            $product_id = $stmt->insert_id; // Use the new product ID
                        } else {
                            echo "<div class='alert alert-danger'>Error adding custom product: " . $conn->error . "</div>";
                            continue;
                        }
                    } else {
                        echo "<div class='alert alert-danger'>Please provide a name for the custom product.</div>";
                        continue;
                    }
                }

                // Validate input data
                if (!empty($product_id) && !empty($quantity) && !empty($price) && $quantity > 0 && $price > 0) {
                    // Insert into quotation_product without total calculations
                    $stmt = $conn->prepare("INSERT INTO quotation_product (quotation_id, productId, quantity, priceoffered) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiid", $quotationHeaderId, $product_id, $quantity, $price);
                    $stmt->execute();
                } else {
                    echo "<div class='alert alert-danger'>Invalid input. Please ensure quantity and price are valid.</div>";
                    continue;
                }
            }

            echo "<div class='alert alert-success'>Quotation saved successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Error creating quotation: " . $conn->error . "</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>No products selected.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Quotation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .container{
            margin-left: 310px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">Add Quotation</h1>
    <form method="POST" action="">
        <!-- Quotation Subject -->
        <div class="form-group">
            <label for="subject">Subject:</label>
            <input type="text" name="subject" class="form-control" required>
        </div>

        <!-- Distributor Dropdown -->
        <div class="form-group">
            <label for="distributor">Select Distributor:</label>
            <select name="distributor_id" id="distributor" class="form-control" required>
                <option value="">Select Distributor</option>
                <?php foreach ($distributors as $distributor): ?>
                    <option value="<?= $distributor['id']; ?>"><?= htmlspecialchars($distributor['companyName']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Product Table -->
        <table class="table table-bordered" id="product-table">
            <thead class="thead-dark">
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Tax</th>
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
                        <input type="text" name="other_product_name[]" class="form-control" placeholder="Product Name (if 'Other')" style="display:none;">
                    </td>
                    <td><input type="number" name="quantity[]" class="form-control" min="1" placeholder="Quantity" required></td>
                    <td><input type="number" name="price_offered[]" class="form-control" min="0" placeholder="Price" required></td>
                    <td>
                        <select name="tax_name[]" class="form-control" required>
                            <option value="">Select Tax</option>
                            <?php foreach ($taxRates as $tax): ?>
                                <option value="<?= htmlspecialchars($tax['tax_name']); ?>">
                                    <?= htmlspecialchars($tax['tax_name']) . " (" . $tax['tax_percentage'] . "%)"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-success">Add</button>
                        <button type="button" class="btn btn-danger">Delete</button>
                    </td>
                </tr>
            </tbody>
        </table>

        <button type="submit" class="btn btn-primary btn-block">Save Quotation</button>
    </form>
</div>

<script>
// JavaScript to handle product row addition and deletion
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-success')) {
        const tableBody = document.querySelector('#product-table tbody');
        const newRow = tableBody.querySelector('tr').cloneNode(true);
        tableBody.appendChild(newRow);
    } else if (e.target.classList.contains('btn-danger')) {
        const row = e.target.closest('tr');
        if (document.querySelectorAll('#product-table tbody tr').length > 1) {
            row.remove();
        }
    } else if (e.target.classList.contains('product-select')) {
        const otherInput = e.target.closest('td').querySelector('input[name="other_product_name[]"]');
        otherInput.style.display = e.target.value === 'other' ? 'block' : 'none';
    }
});
</script>

</body>
</html>