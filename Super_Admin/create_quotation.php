<?php 
// Start the session
session_start();
include 'admin_navbar.php';

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

// Fetch customer quotations for dropdown
$customerQuotations = [];
$quotationQuery = "SELECT qh.quotation_id AS id, qh.subject, 
                  IFNULL(cd.companyName, d.companyName) AS company_name,
                  IF(qh.customerId IS NULL, 'Distributor', 'Customer') AS quotation_type
                  FROM quotation_header qh
                  LEFT JOIN customerdistributor cd ON qh.customerId = cd.id
                  LEFT JOIN distributor d ON qh.distributorId = d.id
                  WHERE qh.status = 'PENDING'";
$quotationResult = $conn->query($quotationQuery);
if ($quotationResult && $quotationResult->num_rows > 0) {
    while ($row = $quotationResult->fetch_assoc()) {
        $customerQuotations[] = $row;
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['load_quotation'])) {
        // Load selected quotation
        $quotationId = $_POST['quotation_id'];
        $_SESSION['original_quotation_id'] = $quotationId;
        
        // Fetch original quotation details to pre-fill the form
        $headerQuery = "SELECT subject, customerId, distributorId FROM quotation_header WHERE quotation_id = ?";
        $stmt = $conn->prepare($headerQuery);
        $stmt->bind_param("i", $quotationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $originalQuotation = $result->fetch_assoc();
        $stmt->close();
        
        // Fetch original quotation products
        $quotationDetails = [];
        $detailQuery = "SELECT qp.productId, p.name, qp.quantity, qp.priceoffered 
                        FROM quotation_product qp
                        JOIN Product p ON qp.productId = p.id
                        WHERE qp.quotation_id = ?";
        $stmt = $conn->prepare($detailQuery);
        $stmt->bind_param("i", $quotationId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $quotationDetails[] = $row;
        }
        $stmt->close();
        
        // Pre-fill the form with original quotation data
        $_POST['subject'] = $originalQuotation['subject'] . " (Revised)";
        $_POST['distributor_id'] = $originalQuotation['distributorId'];
    } 
    elseif (isset($_POST['save_quotation'])) {
        // Save new quotation - always set customerId to NULL for super admin to distributor
        $subject = $_POST['subject'];
        $distributor_id = $_POST['distributor_id'];
        $product_ids = $_POST['product_id'];
        $quantities = $_POST['quantity'];
        $price_offered = $_POST['price_offered'];
        $status = 'PENDING';

        if (!empty($product_ids)) {
            // Insert new quotation header with NULL customerId
            $stmt = $conn->prepare("INSERT INTO quotation_header 
                                  (subject, customerId, distributorId, superAdminId, status, createdAt) 
                                  VALUES (?, NULL, ?, ?, ?, NOW())");
            $stmt->bind_param("siis", $subject, $distributor_id, $superAdminId, $status);
            
            if ($stmt->execute()) {
                $quotationHeaderId = $stmt->insert_id;
                
                // Insert each product into the quotation_product table
                foreach ($product_ids as $index => $product_id) {
                    $quantity = $quantities[$index];
                    $price = $price_offered[$index];

                    if (!empty($product_id) && !empty($quantity) && !empty($price) && $quantity > 0 && $price > 0) {
                        $stmt = $conn->prepare("INSERT INTO quotation_product 
                                              (quotation_id, productId, quantity, priceoffered) 
                                              VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("iiid", $quotationHeaderId, $product_id, $quantity, $price);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                
                echo "<div class='alert alert-success'>New distributor quotation created successfully!</div>";
                unset($_SESSION['original_quotation_id']);
            } else {
                echo "<div class='alert alert-danger'>Error creating quotation: " . $conn->error . "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>No products selected.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Distributor Quotation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --border-radius: 12px;
            --card-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #4a4a4a;
        }

        .main-container {
            margin: 30px auto;
            padding: 0;
            margin-left: 310px;
            max-width: 95%;
        }

        .quotation-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 20px 25px;
        }

        .card-title {
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            font-size: 1.2em;
        }

        .card-body {
            padding: 25px;
            background-color: white;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }

        .readonly-input {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        .btn {
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-info {
            background-color: var(--info-color);
            border-color: var(--info-color);
        }

        .btn-info:hover {
            background-color: #3a7bd5;
            border-color: #3a7bd5;
        }

        .table {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: 600;
            padding: 15px;
            border-bottom: 2px solid #e9ecef;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0;
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .quotation-type {
            font-style: italic;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .quotation-selector {
            background-color: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 25px;
            border-left: 4px solid var(--info-color);
        }

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 60px;
            color: #dee2e6;
            margin-bottom: 15px;
        }

        .alert {
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 20px;
        }

        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
                padding: 15px;
            }
        }

        @media (max-width: 768px) {
            .card-header {
                padding: 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .table-responsive {
                border: none;
            }
            
            .table thead {
                display: none;
            }
            
            .table tbody tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 15px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            }
            
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none;
                padding: 10px 5px;
            }
            
            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--dark-color);
                margin-right: 15px;
                flex: 1;
            }
            
            .table tbody td .cell-content {
                flex: 2;
                text-align: right;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="quotation-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-file-invoice-dollar"></i>Create Distributor Quotation
                </h2>
            </div>
            
            <div class="card-body">
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Quotation Selector Section -->
                <div class="quotation-selector">
                    <h5><i class="fas fa-copy me-2"></i>Base on Existing Quotation</h5>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="quotation_id" class="form-label">Select Quotation:</label>
                            <select name="quotation_id" id="quotation_id" class="form-select" required>
                                <option value="">-- Select Quotation --</option>
                                <?php foreach ($customerQuotations as $quotation): ?>
                                    <option value="<?= $quotation['id']; ?>">
                                        <?= htmlspecialchars($quotation['subject']) ?>
                                        <span class="quotation-type">
                                            (<?= htmlspecialchars($quotation['quotation_type']) ?>: <?= htmlspecialchars($quotation['company_name']) ?>)
                                        </span>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="load_quotation" class="btn btn-info">
                            <i class="fas fa-download me-2"></i>Load Quotation
                        </button>
                    </form>
                </div>

                <!-- Quotation Form -->
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="subject" class="form-label">Subject:</label>
                        <input type="text" name="subject" id="subject" class="form-control" required
                               value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : '' ?>">
                    </div>

                    <div class="mb-4">
                        <label for="distributor" class="form-label">Select Distributor:</label>
                        <select name="distributor_id" id="distributor" class="form-select" required>
                            <option value="">-- Select Distributor --</option>
                            <?php foreach ($distributors as $distributor): ?>
                                <option value="<?= $distributor['id']; ?>"
                                    <?= (isset($_POST['distributor_id']) && $_POST['distributor_id'] == $distributor['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($distributor['companyName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($quotationDetails) && !empty($quotationDetails)): ?>
                                    <?php foreach ($quotationDetails as $detail): ?>
                                        <tr>
                                            <td data-label="Product">
                                                <input type="hidden" name="product_id[]" value="<?= $detail['productId'] ?>">
                                                <input type="text" class="form-control readonly-input" 
                                                       value="<?= htmlspecialchars($detail['name']) ?>" readonly>
                                            </td>
                                            <td data-label="Quantity">
                                                <input type="number" name="quantity[]" class="form-control" min="1" 
                                                       value="<?= $detail['quantity'] ?>" required>
                                            </td>
                                            <td data-label="Price">
                                                <input type="number" name="price_offered[]" class="form-control" min="0" step="0.01"
                                                       value="<?= $detail['priceoffered'] ?>" required>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="empty-state">
                                            <i class="fas fa-box-open"></i>
                                            <h5>No Products Loaded</h5>
                                            <p>Please select a quotation to load products</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (isset($quotationDetails) && !empty($quotationDetails)): ?>
                        <div class="d-grid mt-4">
                            <button type="submit" name="save_quotation" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create New Distributor Quotation
                            </button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent modification of product names
        document.querySelectorAll('.readonly-input').forEach(input => {
            input.addEventListener('click', function(e) {
                e.preventDefault();
                this.blur();
            });
        });

        // Add animation to form elements
        document.addEventListener('DOMContentLoaded', function() {
            const formElements = document.querySelectorAll('.form-control, .form-select, .btn');
            formElements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 50);
            });
        });
    </script>
</body>
</html>