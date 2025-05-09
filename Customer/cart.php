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
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
} else {
    die("No customer is logged in. Please log in.");
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Shopping Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        .cart-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-left: 310px;
            max-width: 1200px;
            transition: all 0.3s ease;
        }

        @media (max-width: 992px) {
            .cart-container {
                margin-left: 15px;
                margin-right: 15px;
                margin-top: 20px;
                max-width: 100%;
            }
        }

        .page-title {
            color: var(--secondary-color);
            font-weight: 700;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 8px;
            font-size: 1.5rem;
        }

        .page-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
        }

        .cart-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .cart-table thead th {
            background-color: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
            padding: 12px;
            border: none;
            font-size: 0.9rem;
        }

        .cart-table tbody tr {
            background-color: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .cart-table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .cart-table td {
            padding: 12px;
            vertical-align: middle;
            border-top: none;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }

        .cart-table td:first-child {
            border-radius: 6px 0 0 6px;
        }

        .cart-table td:last-child {
            border-radius: 0 6px 6px 0;
        }

        .product-name {
            font-weight: 600;
            color: var(--secondary-color);
        }

        .btn-remove {
            background-color: var(--accent-color);
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .btn-remove:hover {
            background-color: #c0392b;
            transform: translateY(-2px);
        }

        .btn-continue {
            background-color: var(--secondary-color);
            border: none;
            border-radius: 4px;
            padding: 8px 16px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-continue:hover {
            background-color: #1a252f;
            transform: translateY(-2px);
        }

        .btn-checkout {
            background-color: var(--success-color);
            border: none;
            border-radius: 4px;
            padding: 8px 20px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-checkout:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }

        .empty-cart {
            text-align: center;
            padding: 30px 0;
        }

        .empty-cart-icon {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 15px;
        }

        .empty-cart-message {
            font-size: 1.1rem;
            color: #777;
            margin-bottom: 15px;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            flex-wrap: wrap;
            gap: 10px;
        }

        @media (max-width: 576px) {
            .action-buttons {
                flex-direction: column;
            }

            .action-buttons a {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4 cart-container">
        <h1 class="page-title"><i class="fas fa-shopping-cart me-2"></i>Your Shopping Cart</h1>

        <?php
        // Check if the cart is empty
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            echo '<div class="table-responsive">';
            echo '<table class="table cart-table">';
            echo '<thead class="thead-light"><tr><th>Product</th><th>Action</th></tr></thead><tbody>';

            foreach ($_SESSION['cart'] as $cartItem) {
                $productId = $cartItem['product_id'];

                // Fetch product details from the database
                $sql = "SELECT * FROM Product WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();
                $product = $result->fetch_assoc();

                // Display cart item
                echo "<tr>";
                echo "<td class='product-name'><i class='fas fa-box-open me-2'></i>" . htmlspecialchars($product['name']) . "</td>";
                echo "<td><a href='remove_from_cart.php?id=$productId' class='btn btn-remove text-white'><i class='fas fa-trash-alt me-1'></i> Remove</a></td>";
                echo "</tr>";
            }

            echo "</tbody></table></div>";
        } else {
            echo '<div class="empty-cart">';
            echo '<div class="empty-cart-icon"><i class="fas fa-cart-arrow-down"></i></div>';
            echo '<h3 class="empty-cart-message">Your cart is empty</h3>';
            echo '<p>Looks like you haven\'t added any items to your cart yet.</p>';
            echo '</div>';
        }
        ?>

        <div class="action-buttons">
            <a href="customer_products.php" class="btn btn-continue text-white">
                <i class="fas fa-arrow-left me-2"></i> Continue Shopping
            </a>
            <?php if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])): ?>
                <a href="request_quotation.php" class="btn btn-checkout text-white">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Request Quotation
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
