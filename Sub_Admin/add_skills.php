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
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch form data
    $skill_name = trim($_POST['skill_name']);

    // Validate input
    if (!empty($skill_name)) {
        // Insert into skills table
        $sql = "INSERT INTO skills (skill_name) VALUES ('$skill_name')";

        if ($conn->query($sql) === TRUE) {
            $successMessage = "New skill added successfully!";
        } else {
            // Handle duplicate skill or other database errors
            if ($conn->errno === 1062) {
                $errorMessage = "Skill already exists!";
            } else {
                $errorMessage = "Error: " . $conn->error;
            }
        }
    } else {
        $errorMessage = "Skill name cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New Skill</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500&display=swap');

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
            background: #f4f4f4;
        }

        .container {
            max-width: 500px;
            width: 100%;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }

        .container header {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            display: block;
            color: #333;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-input:focus {
            border-color: #4070f4;
            outline: none;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #4070f4;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #265df2;
        }

        .alert {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }

        .alert-success {
            background: #4caf50;
            color: #fff;
        }

        .alert-error {
            background: #f44336;
            color: #fff;
        }
    </style>
</head>
<body>
<div class="container">
    <header>Add New Skill</header>
    <form method="POST">
        <div class="form-group">
            <label class="form-label">Skill Name</label>
            <input type="text" name="skill_name" class="form-input" required>
        </div>
        <button type="submit">Add Skill</button>
        <?php 
        if (!empty($successMessage)) {
            echo "<div class='alert alert-success'>$successMessage</div>";
        }
        if (!empty($errorMessage)) {
            echo "<div class='alert alert-error'>$errorMessage</div>";
        }
        ?>
    </form>
</div>
</body>
</html>
