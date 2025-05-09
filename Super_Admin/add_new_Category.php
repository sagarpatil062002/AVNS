<?php
include 'admin_navbar.php';
// Database connection
include 'config.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch form data
    $category_name = trim($_POST['category_name']);

    // Validate input
    if (!empty($category_name)) {
        // Check if category already exists
        $checkSql = "SELECT * FROM category WHERE name = '$category_name'";
        $checkResult = $conn->query($checkSql);
        
        if ($checkResult->num_rows > 0) {
            $errorMessage = "Category already exists!";
        } else {
            // Insert into category table
            $sql = "INSERT INTO category (name) VALUES ('$category_name')";
            if ($conn->query($sql) === TRUE) {
                $successMessage = "New category added successfully!";
                // Clear the form after successful submission
                $category_name = "";
            } else {
                $errorMessage = "Error: " . $conn->error;
            }
        }
    } else {
        $errorMessage = "Category name cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New Category</title>
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
            min-height: 100vh;
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

        .form-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .form-header h2 {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.8rem;
        }

        .form-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #495057;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
            outline: 0;
        }

        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: var(--border-radius);
            transition: var(--transition);
            cursor: pointer;
            width: 100%;
        }

        .btn-primary {
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-2px);
        }

        .alert {
            position: relative;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid transparent;
            border-radius: var(--border-radius);
            animation: fadeIn 0.3s ease;
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
            position: absolute;
            right: 1rem;
            top: 1rem;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
        }

        .form-icon {
            position: relative;
        }

        .form-icon i {
            position: absolute;
            top: 50%;
            left: 1rem;
            transform: translateY(-50%);
            color: var(--dark-gray);
        }

        .form-icon input {
            padding-left: 2.5rem;
        }

        @media (max-width: 768px) {
            .form-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .form-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

<div class="container-wrapper">
    <?php include 'admin_navbar.php'; ?>
    
    <div class="main-content">
        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-plus-circle mr-2"></i>Add New Category</h2>
            </div>
            
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger">
                    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none'">&times;</button>
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group form-icon">
                    <i class="fas fa-tag"></i>
                    <input type="text" 
                           name="category_name" 
                           class="form-control" 
                           placeholder="Enter category name"
                           value="<?php echo isset($category_name) ? htmlspecialchars($category_name) : ''; ?>"
                           required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save mr-2"></i>Add Category
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Simple animation for form elements
    document.addEventListener('DOMContentLoaded', function() {
        const formGroups = document.querySelectorAll('.form-group');
        formGroups.forEach((group, index) => {
            group.style.animationDelay = `${index * 0.1}s`;
            group.style.animation = 'fadeIn 0.5s ease forwards';
            group.style.opacity = '0';
        });
    });
</script>

</body>
</html>