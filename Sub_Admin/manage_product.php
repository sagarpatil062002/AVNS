<?php
session_start();
include 'config.php';
include 'navbar.php';

// Check for a session message and then clear it
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);

// Fetch products from the database
$product_sql = "SELECT p.id, p.name, o.name AS oemName, c.name AS categoryName FROM Product p
                JOIN OEM o ON p.oemId = o.id
                JOIN Category c ON p.categoryId = c.id";
$product_result = $conn->query($product_sql);

// Check if the query was successful
if (!$product_result) {
    $error = "Error fetching products: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Google Font Import */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: #f4f4f4;
        }
        .container {
            margin-top: 30px;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            margin-left: 300px;
        }
        table {
            margin-top: 20px;
        }
        .btn {
            font-size: 14px;
        }
        /* Responsive Table */
        @media (max-width: 768px) {
            table thead {
                display: none; /* Hide table headers */
            }
            table tbody tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 6px;
                padding: 10px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }
            table tbody tr td {
                display: block;
                text-align: left;
                padding: 5px 0;
            }
            table tbody tr td::before {
                content: attr(data-label);
                font-weight: bold;
                display: inline-block;
                margin-right: 10px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <header class="d-flex justify-content-between align-items-center">
        <h4>Manage Products</h4>
        <a href="add_product.php" class="btn btn-primary">Add New Product</a>
    </header>
    <?php if (!empty($message)) echo "<div class='alert alert-success'>$message</div>"; ?>
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <div class="table-responsive">
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Name</th>
                    <th>OEM</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (isset($product_result) && $product_result->num_rows > 0) {
                    while ($row = $product_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td data-label='Name'>" . htmlspecialchars($row['name']) . "</td>";
                        echo "<td data-label='OEM'>" . htmlspecialchars($row['oemName']) . "</td>";
                        echo "<td data-label='Category'>" . htmlspecialchars($row['categoryName']) . "</td>";
                        echo "<td data-label='Actions'>
                                <a href='edit_product.php?id=" . htmlspecialchars($row['id']) . "' class='btn btn-sm btn-info'>Edit</a>
                                <a href='delete_product.php?id=" . htmlspecialchars($row['id']) . "' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to delete this product?');\">Delete</a>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4' class='text-center'>No products found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>
