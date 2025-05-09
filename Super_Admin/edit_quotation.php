<?php
// Start the session
session_start();
include 'admin_navbar.php';
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get quotation_id from query string
$quotationId = $_GET['quotation_id'] ?? null;

if ($quotationId) {
    // Fetch quotation details
    $quotationQuery = "
        SELECT q.quotation_id, q.status, c.companyName AS customer_name, 
               p.name AS product_name, qp.quantity, qp.priceOffered, qp.tax_rate_id
        FROM quotation_header q
        JOIN CustomerDistributor c ON q.customerId = c.id
        JOIN quotation_product qp ON q.quotation_id = qp.quotation_id
        JOIN Product p ON qp.productId = p.id
        WHERE q.quotation_id = ?
    ";

    $stmt = $conn->prepare($quotationQuery);
    $stmt->bind_param("i", $quotationId);
    $stmt->execute();
    $quotationResult = $stmt->get_result();

    $quotation = [];
    while ($row = $quotationResult->fetch_assoc()) {
        $quotation['quotation_id'] = $row['quotation_id'];
        $quotation['status'] = $row['status'];
        $quotation['customer_name'] = $row['customer_name'];
        $quotation['products'][] = [
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'priceOffered' => $row['priceOffered'],
            'tax_rate_id' => $row['tax_rate_id']
        ];
    }
}

// Fetch all tax rates
$taxRatesQuery = "SELECT id, tax_name, tax_percentage FROM tax_rates";
$taxRatesResult = $conn->query($taxRatesQuery);
$taxRates = [];
if ($taxRatesResult && $taxRatesResult->num_rows > 0) {
    while ($row = $taxRatesResult->fetch_assoc()) {
        $taxRates[$row['id']] = [
            'tax_name' => $row['tax_name'],
            'tax_percentage' => $row['tax_percentage']
        ];
    }
}

// Handle form submission to update quotation details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_quotation'])) {
    $quantities = $_POST['quantity'] ?? [];
    $prices = $_POST['priceOffered'] ?? [];

    foreach ($quantities as $productName => $quantity) {
        $stmt = $conn->prepare("
            UPDATE quotation_product 
            SET quantity = ? 
            WHERE quotation_id = ? AND productId = (
                SELECT id FROM Product WHERE name = ? 
            )
        ");
        $stmt->bind_param("iis", $quantity, $quotationId, $productName);
        $stmt->execute();
    }

    foreach ($prices as $productName => $price) {
        $stmt = $conn->prepare("
            UPDATE quotation_product 
            SET priceOffered = ? 
            WHERE quotation_id = ? AND productId = (
                SELECT id FROM Product WHERE name = ? 
            )
        ");
        $stmt->bind_param("dis", $price, $quotationId, $productName);
        $stmt->execute();
    }

    echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
            Quotation updated successfully!
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
          </div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Quotation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            margin-top: 30px;
            margin-right: 20px;
            max-width: 1200px;
        }
        .page-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .page-title {
            color: #343a40;
            font-weight: 600;
            font-size: 28px;
        }
        .quotation-id {
            color: #6c757d;
            font-weight: 400;
            font-size: 18px;
        }
        .customer-info {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .customer-label {
            font-weight: 600;
            color: #495057;
        }
        .table {
            margin-bottom: 30px;
        }
        .table thead th {
            background-color: #495057;
            color: white;
            border: none;
            font-weight: 500;
        }
        .table tbody tr {
            transition: all 0.2s;
        }
        .table tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .form-control {
            border-radius: 4px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            padding: 10px 25px;
            font-weight: 500;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
            transform: translateY(-1px);
        }
        .quantity-input, .price-input {
            max-width: 120px;
        }
        .price-input-group .input-group-prepend .input-group-text {
            min-width: 40px;
        }
        .status-badge {
            font-size: 14px;
            padding: 6px 12px;
            border-radius: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="page-title">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>Edit Quotation
                </h1>
                <span class="quotation-id">#<?= htmlspecialchars($quotation['quotation_id']); ?></span>
            </div>
            <span class="status-badge badge 
                <?= $quotation['status'] == 'approved' ? 'badge-success' : 
                   ($quotation['status'] == 'pending' ? 'badge-warning' : 'badge-secondary'); ?>">
                <?= ucfirst(htmlspecialchars($quotation['status'])); ?>
            </span>
        </div>
    </div>

    <div class="customer-info">
        <div class="row">
            <div class="col-md-6">
                <div class="customer-label">Customer</div>
                <div class="customer-value h5"><?= htmlspecialchars($quotation['customer_name']); ?></div>
            </div>
            <div class="col-md-6 text-right">
                <div class="customer-label">Quotation Date</div>
                <div class="customer-value"><?= date('F j, Y'); ?></div>
            </div>
        </div>
    </div>

    <form method="POST" action="">
        <h4 class="mb-3"><i class="fas fa-boxes mr-2"></i>Products</h4>
        
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th width="45%">Product</th>
                        <th width="20%">Quantity</th>
                        <th width="25%">Unit Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotation['products'] as $index => $product): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="mr-2">
                                        <i class="fas fa-box text-muted"></i>
                                    </div>
                                    <div><?= htmlspecialchars($product['product_name']); ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="number" name="quantity[<?= $product['product_name']; ?>]" 
                                           class="form-control quantity-input" min="1" 
                                           value="<?= htmlspecialchars($product['quantity']); ?>" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">units</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="input-group price-input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">₹</span>
                                    </div>
                                    <input type="number" name="priceOffered[<?= $product['product_name']; ?>]" 
                                           class="form-control price-input" min="0" step="0.01"
                                           value="<?= htmlspecialchars($product['priceOffered']); ?>" required>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
            <button type="submit" name="update_quotation" class="btn btn-primary">
                <i class="fas fa-save mr-2"></i>Update Quotation
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>