<?php
session_start();
include 'config.php';
include 'admin_navbar.php';

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary-color: #4361ee;
            --primary-hover: #3a56d4;
            --success-color: #4cc9f0;
            --error-color: #f72585;
            --light-gray: #f8f9fa;
            --dark-gray: #6c757d;
            --border-radius: 8px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: #333;
        }

        .container-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 2rem;
            transition: var(--transition);
        }

        .content-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .page-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-right: 0.75rem;
            font-size: 1.5rem;
        }

        .btn {
            font-weight: 500;
            padding: 0.5rem 1.25rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .alert {
            position: relative;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: var(--border-radius);
            animation: fadeIn 0.3s ease;
            display: flex;
            align-items: center;
        }

        .alert i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
            margin-left: auto;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 0 0 1px #eee;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: var(--primary-color);
            color: white;
            border: none;
            font-weight: 500;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .table tbody td {
            vertical-align: middle;
            border-color: #eee;
        }

        .action-buttons .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }

        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }

        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--dark-gray);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #ddd;
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .content-container {
                padding: 1.5rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .page-title {
                margin-bottom: 1rem;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
        }

        @media (max-width: 576px) {
            .content-container {
                padding: 1.25rem;
            }
            
            .page-title {
                font-size: 1.3rem;
            }
            
            .action-buttons {
                display: flex;
                flex-direction: column;
            }
            
            .action-buttons .btn {
                margin-right: 0;
                margin-bottom: 0.5rem;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="container-wrapper">
    <?php include 'admin_navbar.php'; ?>
    
    <div class="main-content">
        <div class="content-container">
            <div class="page-header">
                <h1 class="page-title"><i class="fas fa-boxes"></i>Manage Products</h1>
                <a href="add_product.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i>Add New Product
                </a>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>OEM</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($product_result) && $product_result->num_rows > 0): ?>
                            <?php while ($row = $product_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['oemName']); ?></td>
                                    <td><?php echo htmlspecialchars($row['categoryName']); ?></td>
                                    <td class="action-buttons">
                                        <a href="edit_product.php?id=<?php echo htmlspecialchars($row['id']); ?>" 
                                           class="btn btn-info btn-sm">
                                            <i class="fas fa-edit"></i>Edit
                                        </a>
                                        <a href="delete_product.php?id=<?php echo htmlspecialchars($row['id']); ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Are you sure you want to delete this product?');">
                                            <i class="fas fa-trash-alt"></i>Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fas fa-box-open"></i>
                                        <h4>No products found</h4>
                                        <p>Get started by adding your first product</p>
                                        <a href="add_product.php" class="btn btn-primary mt-2">
                                            <i class="fas fa-plus"></i>Add Product
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>
    // Simple animation for table rows
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach((row, index) => {
            row.style.animationDelay = `${index * 0.05}s`;
            row.style.animation = 'fadeIn 0.3s ease forwards';
            row.style.opacity = '0';
        });
    });
</script>
</body>
</html>