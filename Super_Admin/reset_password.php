<?php
session_start();

// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'sales_management';

$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['token']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_GET['token'];
    $newPassword = $conn->real_escape_string(trim($_POST['password']));
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Validate the token
    $stmt = $conn->prepare("SELECT * FROM users WHERE reset_token IS NOT NULL AND reset_token_expiry > NOW()");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($token, $user['reset_token'])) {
            // Update password and clear token
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
            $stmt->bind_param("ss", $hashedPassword, $user['reset_token']);
            $stmt->execute();

            echo "<div class='alert success'>Password reset successfully. <a href='login.php'>Login here</a></div>";
        } else {
            echo "<div class='alert error'>Invalid or expired token</div>";
        }
    } else {
        echo "<div class='alert error'>Invalid or expired token</div>";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        html, body {
            display: grid;
            height: 100%;
            width: 100%;
            place-items: center;
            background: #f2f2f2;
        }
        .wrapper {
            width: 380px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0px 15px 20px rgba(0,0,0,0.1);
        }
        .wrapper .title {
            font-size: 35px;
            font-weight: 600;
            text-align: center;
            line-height: 100px;
            color: #fff;
            user-select: none;
            border-radius: 15px 15px 0 0;
            background: #0e4bf1;
        }
        .wrapper form {
            padding: 10px 30px 50px 30px;
        }
        .wrapper form .field {
            height: 50px;
            width: 100%;
            margin-top: 20px;
            position: relative;
        }
        .wrapper form .field input {
            height: 100%;
            width: 100%;
            outline: none;
            font-size: 17px;
            padding-left: 20px;
            border: 1px solid lightgrey;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .wrapper form .field input:focus,
        form .field input:valid {
            border-color: #4158d0;
        }
        .wrapper form .field label {
            position: absolute;
            top: 50%;
            left: 20px;
            color: #999999;
            font-weight: 400;
            font-size: 17px;
            pointer-events: none;
            transform: translateY(-50%);
            transition: all 0.3s ease;
        }
        form .field input:focus ~ label,
        form .field input:valid ~ label {
            top: 0%;
            font-size: 16px;
            color: #4158d0;
            background: #fff;
            transform: translateY(-50%);
        }
        form .field input[type="submit"] {
            color: #fff;
            border: none;
            padding-left: 0;
            margin-top: -10px;
            font-size: 20px;
            font-weight: 500;
            cursor: pointer;
            background: #0e4bf1;
            transition: all 0.3s ease;
        }
        form .field input[type="submit"]:active {
            transform: scale(0.95);
        }
        .alert {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            font-size: 16px;
            text-align: center;
        }
        .alert.success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="title">Reset Password</div>
        <form method="POST" action="">
            <div class="field">
                <input type="password" name="password" required>
                <label>New Password</label>
            </div>
            <div class="field">
                <input type="submit" value="Reset Password">
            </div>
        </form>
    </div>
</body>
</html>
