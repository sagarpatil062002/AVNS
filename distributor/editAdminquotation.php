<?php
// Start the session
session_start();

// Database connection
include('Config.php');
include('DNav.php');

// Check if quotation ID is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='alert alert-danger'>Invalid quotation ID.</div>");
}

$quotationId = (int)$_GET['id'];

// Fetch existing quotation data
$quotationQuery = "SELECT qp.id AS product_row_id, qp.productId, qp.quantity, qp.priceOffered, p.name AS product_name 
                   FROM quotation_product qp 
                   JOIN Product p ON qp.productId = p.id 
                   WHERE qp.quotation_id = ?";
$stmt = $conn->prepare($quotationQuery);
$stmt->bind_param("i", $quotationId);
$stmt->execute();
$quotationResult = $stmt->get_result();

// Check if quotation exists
if ($quotationResult->num_rows === 0) {
    die("<div class='alert alert-danger'>No products found for this quotation.</div>");
}

$quotationData = $quotationResult->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all products for dropdown
$products = [];
$productQuery = "SELECT id, name FROM Product";
$productResult = $conn->query($productQuery);
if ($productResult && $productResult->num_rows > 0) {
    while ($row = $productResult->fetch_assoc()) {
        $products[] = $row;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $price_offered = $_POST['price_offered'];
    $product_row_ids = $_POST['product_row_id'];

    foreach ($product_row_ids as $index => $row_id) {
        $product_id = $product_ids[$index];
        $quantity = $quantities[$index];
        $price = $price_offered[$index];

        if (!empty($product_id) && !empty($quantity) && !empty($price) && $quantity > 0 && $price > 0) {
            // Update the quotation product
            $stmt = $conn->prepare("UPDATE quotation_product 
                                    SET productId = ?, quantity = ?, priceOffered = ? 
                                    WHERE id = ?");
            $stmt->bind_param("iidi", $product_id, $quantity, $price, $row_id);
            if (!$stmt->execute()) {
                echo "<div class='alert alert-danger'>Error updating product row: " . $conn->error . "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Please ensure all fields are filled correctly.</div>";
        }
    }
    echo "<div class='alert alert-success'>Quotation updated successfully!</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Quotation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --danger-color: #ff3333;
            --warning-color: #ffc107;
            --border-radius: 8px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        body {
            background: #f5f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            color: var(--dark-color);
            line-height: 1.6;
        }
        
        .container {
            background: #fff;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-top: 2rem;
            margin-left: 350px;
            max-width: 950px;
            transition: var(--transition);
        }
        
        h1 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            font-weight: 700;
            font-size: 2rem;
            position: relative;
            display: inline-block;
        }
        
        h1:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 2px;
        }
        
        .table {
            margin-bottom: 2rem;
            border-collapse: separate;
            border-spacing: 0 0.75rem;
        }
        
        .table thead th {
            background-color: var(--primary-color);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem 1.5rem;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .table tbody tr {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            transition: var(--transition);
            border-radius: var(--border-radius);
        }
        
        .table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .table td {
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
            border-top: none;
            border-bottom: 1px solid #f1f3f5;
        }
        
        .table td:first-child {
            border-left: 1px solid #f1f3f5;
            border-top-left-radius: var(--border-radius);
            border-bottom-left-radius: var(--border-radius);
        }
        
        .table td:last-child {
            border-right: 1px solid #f1f3f5;
            border-top-right-radius: var(--border-radius);
            border-bottom-right-radius: var(--border-radius);
        }
        
        .form-control {
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            border: 1px solid #e0e3e7;
            transition: var(--transition);
            height: auto;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(72, 149, 239, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 1.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: var(--transition);
            border-radius: var(--border-radius);
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2);
        }
        
        .btn-outline-secondary {
            border-radius: var(--border-radius);
            padding: 0.75rem 1.75rem;
            font-weight: 600;
            transition: var(--transition);
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        
        .btn-outline-secondary:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: none;
            box-shadow: var(--box-shadow);
        }
        
        .disabled-select {
            background-color: #f8f9fa;
            cursor: not-allowed;
            color: #6c757d;
        }
        
        .quantity-input, .price-input {
            max-width: 120px;
        }
        
        .input-group-text {
            background-color: #f1f3f5;
            border: 1px solid #e0e3e7;
            color: #495057;
            font-weight: 500;
        }
        
        .input-group-prepend .input-group-text {
            border-right: none;
            border-top-left-radius: var(--border-radius) !important;
            border-bottom-left-radius: var(--border-radius) !important;
        }
        
        .input-group-append .input-group-text {
            border-left: none;
            border-top-right-radius: var(--border-radius) !important;
            border-bottom-right-radius: var(--border-radius) !important;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
        }
        
        .badge-quotation {
            background-color: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        @media (max-width: 1200px) {
            .container {
                margin-left: 300px;
            }
        }
        
        @media (max-width: 992px) {
            .container {
                margin-left: 0;
                margin-top: 80px;
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1.5rem;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .badge-quotation {
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container animate__animated animate__fadeIn">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-file-invoice-dollar mr-2"></i>Edit Quotation</h1>
            <span class="badge-quotation">Quotation ID: #<?= $quotationId ?></span>
        </div>
    </div>

    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeInDown" role="alert">
            <i class="fas fa-check-circle mr-2"></i>Quotation updated successfully!
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="quotation_id" value="<?= $quotationId; ?>">

        <!-- Products Table -->
        <div class="table-responsive">
            <table class="table" id="quotation-table">
                <thead>
                    <tr>
                        <th width="45%">Product</th>
                        <th width="25%">Quantity</th>
                        <th width="25%">Price Offered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotationData as $row): ?>
                    <tr class="animate__animated animate__fadeIn">
                        <td>
                            <select name="product_id[]" class="form-control disabled-select" disabled>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id']; ?>" 
                                        <?= $product['id'] == $row['productId'] ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($product['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <div class="input-group">
                                <input type="number" name="quantity[]" class="form-control quantity-input" min="1" 
                                       placeholder="Quantity" value="<?= $row['quantity']; ?>" required>
                                <div class="input-group-append">
                                    <span class="input-group-text">units</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">₹</span>
                                </div>
                                <input type="number" name="price_offered[]" class="form-control price-input" min="0" 
                                       step="0.01" placeholder="0.00" value="<?= $row['priceOffered']; ?>" required>
                            </div>
                        </td>
                        <input type="hidden" name="product_row_id[]" value="<?= $row['product_row_id']; ?>">
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
            <button type="submit" class="btn btn-primary px-4">
                <i class="fas fa-save mr-2"></i>Update Quotation
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>
    $(document).ready(function() {
        // Add animation to form elements
        $('input, select').focus(function() {
            $(this).parent().addClass('animate__animated animate__pulse');
        }).blur(function() {
            $(this).parent().removeClass('animate__animated animate__pulse');
        });
        
        // Smooth scroll to top when there's an error
        if ($('.alert-danger').length) {
            $('html, body').animate({
                scrollTop: $('.alert-danger').offset().top - 100
            }, 500);
        }
    });
</script>
</body>
</html>