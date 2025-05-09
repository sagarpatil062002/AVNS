<?php
// Start the session
session_start();

// Database connection
include('Config.php');
include 'CustomerNav.php';

// Get the quotation ID from the GET parameter
if (isset($_GET['quotation_id'])) {
    $quotation_id = $_GET['quotation_id'];
} else {
    die("Quotation ID not provided.");
}

// Fetch products related to the quotation
$productQuery = "
    SELECT p.name AS productName, qp.quantity, qp.priceOffered, tr.tax_percentage AS taxPercentage
    FROM quotation_product qp
    JOIN product p ON qp.productId = p.id
    LEFT JOIN tax_rates tr ON p.tax_rate_id = tr.id
    WHERE qp.quotation_id = ?
";
$stmt = $conn->prepare($productQuery);
$stmt->bind_param("i", $quotation_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Products</title>
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
        
        .products-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-left: 250px;
            transition: all 0.3s ease;
        }
        
        .products-header {
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
            text-align: center;
        }
        
        .back-btn {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.875rem;
            margin-top: 1.5rem;
            transition: all 0.2s ease;
        }
        
        .back-btn:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .price-cell {
            font-weight: 600;
            color: var(--dark);
        }
        
        .tax-cell {
            color: var(--success);
            font-weight: 500;
        }
        
        @media (max-width: 992px) {
            .products-container {
                margin-left: 0;
                margin-top: 1rem;
                padding: 1.5rem;
            }
            
            .products-header {
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
                <div class="products-container">
                    <h1 class="products-header">
                        <i class="fas fa-boxes me-2"></i>Products in Quotation #<?= htmlspecialchars($quotation_id); ?>
                    </h1>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Quantity</th>
                                    <th>Price Offered</th>
                                    <th>Tax Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td data-label="Product Name"><?= htmlspecialchars($product['productName']); ?></td>
                                        <td data-label="Quantity"><?= htmlspecialchars($product['quantity']); ?></td>
                                        <td data-label="Price Offered" class="price-cell">₹<?= number_format($product['priceOffered'], 2); ?></td>
                                        <td data-label="Tax Rate" class="tax-cell"><?= htmlspecialchars($product['taxPercentage']); ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center">
                        <a href="javascript:history.back()" class="btn back-btn">
                            <i class="fas fa-arrow-left me-2"></i>Back to Quotations
                        </a>
                    </div>
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