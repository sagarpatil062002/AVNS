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
    $tax_name = trim($_POST['tax_name']);
    $tax_percentage = trim($_POST['tax_percentage']);

    // Validate input
    if (!empty($tax_name) && !empty($tax_percentage)) {
        // Insert into tax_rates table
        $sql = "INSERT INTO tax_rates (tax_name, tax_percentage) VALUES ('$tax_name', '$tax_percentage')";
        if ($conn->query($sql) === TRUE) {
            $successMessage = "New tax rate added successfully!";
        } else {
            $successMessage = "Error: " . $conn->error;
        }
    } else {
        $successMessage = "Tax name and percentage cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New Tax Rate</title>
    
    <!-- Include the navbar -->
    <?php include 'navbar.php'; ?>

    <!-- External stylesheets -->
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
            background: #f4f4f4; /* Light gray background for the whole page */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Main container styling */
        .container {
            margin-left: 100px; /* Space for the sidebar */
            width: 100%; /* Ensure it's 100% width */
            max-width: 800px; /* Set a maximum width */
            background-color: #fff; /* White background for the form */
            border-radius: 6px;
            padding: 40px;
            margin-top: 30px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            text-align: center; /* Center text */
            position: flex;
            margin-top:0px;
            margin-bottom:300px;
        }

        .container header {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .container header::before {
            content: "";
            position: absolute;
            left: 0;
            bottom: -2px;
            height: 3px;
            width: 40px;
            border-radius: 8px;
            background-color: #4070f4;
        }

        /* Form styling */
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
            padding: 12px;
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
            padding: 12px;
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
        
            
        }
    </style>
</head>
<body>

<div class="container">
    <header>Add New Tax Rate</header>
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Tax Name</label>
            <input type="text" name="tax_name" class="form-input" required>
        </div>
        <div class="form-group">
            <label class="form-label">Tax Percentage</label>
            <input type="number" name="tax_percentage" class="form-input" required>
        </div>
        <button type="submit">Add Tax Rate</button>
        <?php if (!empty($successMessage)) echo "<div class='alert'>$successMessage</div>"; ?>
    </form>
</div>

</body>
</html>

<?php
$conn->close();
?>
