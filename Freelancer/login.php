<?php
session_start();
include 'config.php'; // Ensure this file contains your database connection setup

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Check if email and password are provided
    if (!empty($email) && !empty($password)) {
        // Query to check user credentials and approval status
        $stmt = $conn->prepare("SELECT * FROM Freelancer WHERE email = ? AND is_approved = 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if user exists and is approved
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Store freelancer's ID in session
                $_SESSION['user_id'] = $user['id'];

                // Redirect to the freelancer's dashboard
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Invalid email or password!";
            }
        } else {
            $error = "Login failed or approval pending.";
        }
    } else {
        $error = "Please provide both email and password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
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
        .wrapper form .field input:focus {
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
        .wrapper form .field input:focus ~ label,
        .wrapper form .field input:valid ~ label {
            top: 0%;
            font-size: 16px;
            color: #4158d0;
            background: #fff;
            transform: translateY(-50%);
        }
        .wrapper form .content {
            display: flex;
            width: 100%;
            height: 50px;
            font-size: 16px;
            align-items: center;
            justify-content: space-around;
        }
        .wrapper form .field input[type="submit"] {
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
        .wrapper form .field input[type="submit"]:active {
            transform: scale(0.95);
        }
        .wrapper form .signup-link {
            color: #262626;
            margin-top: 20px;
            text-align: center;
        }
        .wrapper form .pass-link a,
        .wrapper form .signup-link a {
            color: #4158d0;
            text-decoration: none;
        }
        .wrapper form .pass-link a:hover,
        .wrapper form .signup-link a:hover {
            text-decoration: underline;
        }
        .alert {
            color: red;
            text-align: center;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="title">Login Form</div>
        <form method="POST" action="">
            <div class="field">
                <input type="email" name="email" required>
                <label>Email Address</label>
            </div>
            <div class="field">
                <input type="password" name="password" required>
                <label>Password</label>
            </div>
            <div class="content">
                <div class="checkbox">
                    <input type="checkbox" id="remember-me">
                    <label for="remember-me">Remember me</label>
                </div>
                <div class="pass-link">
                    <a href="forgot_password.php">Forgot password?</a>
                </div>
            </div>
            <?php if (isset($error)) { echo "<div class='alert'>$error</div>"; } ?>
            <div class="field">
                <input type="submit" value="Login">
            </div>
            <div class="signup-link">
                Not a member? <a href="register.php">Signup now</a>
            </div>
        </form>
    </div>
</body>
</html>
