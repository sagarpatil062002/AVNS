<?php
// Start the session
session_start();
include('Config.php');

if (isset($_GET['quotation_id'])) {
    $quotationId = $_GET['quotation_id'];
} else {
    die("Quotation ID is missing.");
}

// Fetch existing quotation data
$quotationQuery = "SELECT qp.id AS product_row_id, qp.productId, qp.quantity, qp.priceOffered, p.name AS product_name 
                   FROM quotation_product qp 
                   JOIN Product p ON qp.productId = p.id 
                   WHERE qp.quotation_id = ?";
$stmt = $conn->prepare($quotationQuery);
$stmt->bind_param("i", $quotationId);
$stmt->execute();
$quotationResult = $stmt->get_result();
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
    // Initialize arrays with empty values if not set
    $product_ids = $_POST['product_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $price_offered = $_POST['price_offered'] ?? [];
    $product_row_ids = $_POST['product_row_id'] ?? [];

    // Validate that all arrays have the same length
    if (count($product_row_ids) !== count($product_ids) || 
        count($product_row_ids) !== count($quantities) || 
        count($product_row_ids) !== count($price_offered)) {
        $message = "Invalid form data submitted.";
        $messageType = "danger";
    } else {
        // Process each row
        foreach ($product_row_ids as $index => $row_id) {
            // Make sure all required fields exist for this index
            if (!isset($product_ids[$index]) || !isset($quantities[$index]) || !isset($price_offered[$index])) {
                continue; // Skip this row if data is missing
            }

            $product_id = $product_ids[$index];
            $quantity = $quantities[$index];
            $price = $price_offered[$index];

            // Validate inputs
            if (!empty($product_id) && !empty($quantity) && !empty($price) && $quantity > 0 && $price > 0) {
                // Update the quotation product
                $stmt = $conn->prepare("UPDATE quotation_product 
                                        SET productId = ?, quantity = ?, priceOffered = ? 
                                        WHERE id = ?");
                $stmt->bind_param("iidi", $product_id, $quantity, $price, $row_id);
                if (!$stmt->execute()) {
                    $message = "Error updating product row: " . $conn->error;
                    $messageType = "danger";
                    break; // Stop processing if there's an error
                }
            } else {
                $message = "Please ensure all fields are filled correctly.";
                $messageType = "danger";
                break; // Stop processing if validation fails
            }
        }
        
        if (!isset($message)) {
            $message = "Quotation updated successfully!";
            $messageType = "success";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Rest of your head section remains the same -->
    <!-- ... -->
     <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quotation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --secondary: #f8f9fc;
            --success: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --gray: #858796;
            --gray-light: #dddfeb;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--dark);
        }
        
        .quotation-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-left: 250px;
            transition: all 0.3s ease;
        }
        
        .quotation-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.75rem;
            border-bottom: 1px solid var(--gray-light);
            padding-bottom: 1rem;
        }
        
        .table {
            margin-top: 1.5rem;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 0.35rem;
            overflow: hidden;
            box-shadow: 0 0 0.5rem rgba(0, 0, 0, 0.05);
        }
        
        .table thead th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
            text-align: center;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid var(--gray-light);
        }
        
        .form-control {
            border-radius: 0.35rem;
            border: 1px solid var(--gray-light);
            padding: 0.75rem 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            border-radius: 0.35rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: rgba(28, 200, 138, 0.1);
            border-color: var(--success);
            color: #0d8a5a;
        }
        
        .alert-danger {
            background-color: rgba(231, 74, 59, 0.1);
            border-color: var(--danger);
            color: #c23321;
        }
        
        @media (max-width: 992px) {
            .quotation-container {
                margin-left: 0;
                margin-top: 1rem;
                padding: 1.5rem;
            }
            
            .quotation-header {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include('CustomerNav.php'); ?>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="quotation-container">
                    <h1 class="quotation-header">
                        <i class="fas fa-edit me-2"></i>Edit Quotation
                    </h1>

                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i>
                            <?= htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="quotation_id" value="<?= $quotationId; ?>">

                        <div class="table-responsive">
                            <table class="table" id="quotation-table">
                                <thead>
                                    <tr>
                                        <th style="width: 45%">Product</th>
                                        <th style="width: 25%">Quantity</th>
                                        <th style="width: 25%">Price Offered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($quotationData as $row): ?>
                                    <tr>
                                        <td>
                                            <select name="product_id[]" class="form-control">
                                                <?php foreach ($products as $product): ?>
                                                    <option value="<?= $product['id']; ?>" 
                                                        <?= $product['id'] == $row['productId'] ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($product['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="quantity[]" class="form-control" 
                                                min="1" placeholder="Quantity" 
                                                value="<?= htmlspecialchars($row['quantity']); ?>" required>
                                        </td>
                                        <td>
                                            <input type="number" name="price_offered[]" class="form-control" 
                                                min="0" step="0.01" placeholder="Price" 
                                                value="<?= htmlspecialchars($row['priceOffered']); ?>" required>
                                        </td>
                                        <input type="hidden" name="product_row_id[]" value="<?= htmlspecialchars($row['product_row_id']); ?>">
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Quotation
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>