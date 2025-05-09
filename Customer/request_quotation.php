<?php
session_start();

// Database connection
include('Config.php');
include('CustomerNav.php');

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logged-in customer ID from session
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Use user_id as customer_id
} else {
    die("No customer is logged in. Please log in.");
}

// Fetch customer data (assuming user_id is in session)
$sql = "SELECT companyName FROM customerdistributor WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$companyName = $customer['companyName']; // Store the company name

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch customer ID and subject from the form
    $customerId = $_POST['customer_id']; // Use the customer_id passed from the form
    $subject = $_POST['subject']; // Fetch the subject entered by the user
    $superAdminId = 1; // Replace with the actual super admin ID
    $status = 'PENDING';
    $quotationDate = date('Y-m-d H:i:s');
    
    // Insert the quotation record into the `quotation_header` table
    $sql = "INSERT INTO quotation_header (customerId, subject, status, createdAt, superAdminId) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $customerId, $subject, $status, $quotationDate, $superAdminId);
    $stmt->execute();
    $quotationId = $stmt->insert_id; // Get the last inserted quotation ID

    // Loop through the cart and add products with quantity and target price to the `quotation_product` table
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $cartItem) {
            $productId = $cartItem['product_id'];
            $quantity = $_POST['quantity_' . $productId]; // Get quantity from the form
            $priceOffered = $_POST['target_price_' . $productId]; // Get target price from the form
            
            // Fetch tax rate ID for the product
            $taxSql = "SELECT tax_rate_id FROM product WHERE id = ?";
            $taxStmt = $conn->prepare($taxSql);
            $taxStmt->bind_param("i", $productId);
            $taxStmt->execute();
            $taxResult = $taxStmt->get_result();
            $taxData = $taxResult->fetch_assoc();
            
            $taxRateId = $taxData['tax_rate_id'] ?? null;

            // Insert the product into `quotation_product` table with tax rate ID
            $sql = "INSERT INTO quotation_product (quotation_id, productId, quantity, priceOffered, tax_rate_id, createdAt) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiidis", $quotationId, $productId, $quantity, $priceOffered, $taxRateId, $quotationDate);
            $stmt->execute();
        }
    }

    // Clear the cart after submission
    unset($_SESSION['cart']);

    // Redirect to a confirmation page or back to the cart
    header("Location: view_Adminquotation.php");
    exit();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request Quotation</title>
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
            max-width: 1000px;
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
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: 0.35rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
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
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.875rem;
            margin-top: 1.5rem;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .empty-cart {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .form-icon {
            position: relative;
        }
        
        .form-icon i {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            color: var(--gray);
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
            
            .table thead {
                display: none;
            }
            
            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid var(--gray-light);
                border-radius: 0.35rem;
            }
            
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none;
                border-bottom: 1px solid var(--gray-light);
            }
            
            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--dark);
                margin-right: 1rem;
            }
            
            .table tbody td:last-child {
                border-bottom: none;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="quotation-container">
                    <h1 class="quotation-header">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Request Quotation
                    </h1>
                    
                    <form method="POST" action="request_quotation.php">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label for="customer" class="form-label">Customer</label>
                                <div class="form-icon">
                                    <select class="form-select" name="customer_id" required>
                                        <option value="<?= $customerId; ?>" selected><?= htmlspecialchars($companyName); ?></option>
                                    </select>
                                    <i class="fas fa-building"></i>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="subject" class="form-label">Subject</label>
                                <div class="form-icon">
                                    <input type="text" name="subject" class="form-control" placeholder="Enter subject" required>
                                    <i class="fas fa-heading"></i>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Quantity</th>
                                        <th>Target Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Check if the cart is not empty
                                    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                                        foreach ($_SESSION['cart'] as $cartItem) {
                                            $productId = $cartItem['product_id'];

                                            // Fetch product details from the database
                                            $sql = "SELECT * FROM product WHERE id = ?";
                                            $stmt = $conn->prepare($sql);
                                            $stmt->bind_param("i", $productId);
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            $product = $result->fetch_assoc();

                                            // Display product with input fields for quantity and target price
                                            echo "<tr>";
                                            echo "<td data-label='Product Name'>" . htmlspecialchars($product['name']) . "</td>";
                                            echo "<td data-label='Quantity'><input type='number' name='quantity_" . $productId . "' class='form-control' min='1' placeholder='Quantity' required></td>";
                                            echo "<td data-label='Target Price'><input type='number' step='0.01' name='target_price_" . $productId . "' class='form-control' placeholder='Target Price' required></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='empty-cart'><i class='fas fa-shopping-cart me-2'></i>Your cart is empty.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Quotation Request
                                </button>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add any necessary JavaScript here
        document.addEventListener("DOMContentLoaded", function() {
            // Add responsive behavior for mobile
            if (window.innerWidth < 768) {
                const tableCells = document.querySelectorAll('.table tbody td');
                tableCells.forEach(cell => {
                    const headerText = cell.parentNode.children[0].textContent;
                    cell.setAttribute('data-label', headerText);
                });
            }
        });
    </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>