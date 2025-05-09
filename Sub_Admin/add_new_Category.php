<?php

include 'navbar.php';

// Database connection
$host = 'localhost';
$username = 'root'; // Replace with your database username
$password = ''; // Replace with your database password
$dbname = 'sales_management';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch form data
    $category_name = trim($_POST['category_name']);

    // Validate input
    if (!empty($category_name)) {
        // Insert into category table
        $sql = "INSERT INTO category (name) VALUES ('$category_name')";
        if ($conn->query($sql) === TRUE) {
            $successMessage = "New category added successfully!";
        } else {
            $successMessage = "Error: " . $conn->error;
        }
    } else {
        $successMessage = "Category name cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New Category</title>

    <!-- Include the navbar -->
    <?php include 'navbar.php'; ?>

    <!-- External stylesheets -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="styles.css">

    <style>
        /* ===== Google Font Import - Poppins ===== */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4f4f4; /* Light gray background for the whole page */
        }

        .container {
            position: relative;
            max-width: 900px;
            width: 100%;
            border-radius: 6px;
            padding: 30px;
            margin: 0 10px;
            background-color: #fff; /* White background for the form */
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
            margin-top: 15px;
            margin-left: 300px;
            position: flex;
            margin-bottom: 350px;
        }

        .container header {
            position: relative;
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .container header::before {
            content: "";
            position: absolute;
            left: 0;
            bottom: -2px;
            height: 3px;
            width: 27px;
            border-radius: 8px;
            background-color: #4070f4;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-size: 16px;
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
        }

        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #aaa;
            border-radius: 5px;
            font-size: 15px;
            color: #333;
            outline: none;
        }

        .form-input:focus {
            border-color: #4070f4;
        }

        button {
            display: block;
            width: 100%;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            color: #fff;
            background-color: #4070f4;
            cursor: pointer;
            transition: 0.3s ease;
        }

        button:hover {
            background-color: #265df2;
        }

        .alert {
            margin-top: 20px;
            padding: 10px;
            color: #fff;
            background-color: #f44336;
            border-radius: 5px;
            text-align: center;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .form-label {
                font-size: 14px;
            }

            .form-input {
                font-size: 14px;
            }

            button {
                font-size: 14px;
            }
        }

        @media (max-width: 576px) {
            .container {
                padding: 15px;
            }

            .form-label {
                font-size: 12px;
            }

            .form-input {
                font-size: 12px;
            }

            button {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <header>Add New Category</header>
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Category Name</label>
            <input type="text" name="category_name" class="form-input" required>
        </div>
        <button type="submit">Add Category</button>
        <?php if (!empty($successMessage)) echo "<div class='alert'>$successMessage</div>"; ?>
    </form>
</div>

</body>
</html>

<?php
$conn->close();
?>
