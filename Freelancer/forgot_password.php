<?php
// Start session
session_start();

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Include Composer autoload or manually include PHPMailer files

// Database connection
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mailId = $conn->real_escape_string(trim($_POST['mailId']));

    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM CustomerDistributor WHERE mailId = ?");
    $stmt->bind_param("s", $mailId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(50)); // Generate a secure random token
        $hashedToken = password_hash($token, PASSWORD_BCRYPT);

        // Store token in the database
        $stmt = $conn->prepare("UPDATE CustomerDistributor SET reset_token = ?, reset_token_expiry = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE mailId = ?");
        $stmt->bind_param("ss", $hashedToken, $mailId);
        $stmt->execute();

        // Send password reset email
        $mail = new PHPMailer(true);

        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'sagarpatil062002@gmail.com'; // Use environment variables
            $mail->Password = "tleeljiyjxxtfuxv"; // Use environment variables
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Email settings
            $mail->setFrom('your-email@example.com', 'AVNS Technosoft');
            $mail->addAddress($mailId);
            $mail->isHTML(true);

            $resetLink = "http://localhost/AVNS/customer/reset_password.php?token=" . urlencode($token);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Hi, <br><br>Click the link below to reset your password:<br><br>
                           <a href='$resetLink'>$resetLink</a><br><br>
                           This link will expire in 1 hour.";

            $mail->send();
            echo "<div class='alert success'>Password reset link sent to your email.</div>";
        } catch (Exception $e) {
            echo "<div class='alert error'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";
        }
    } else {
        echo "<div class='alert error'>Email not registered</div>";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
        /* Add your preferred styles */
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
            box-shadow: 0px 15px 20px rgba(0, 0, 0, 0.1);
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
        .wrapper form .field input:focus,
        .wrapper form .field input:valid {
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
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="title">Forgot Password</div>
        <form method="POST" action="">
            <div class="field">
                <input type="email" name="mailId" required>
                <label>Email Address</label>
            </div>
            <div class="field">
                <input type="submit" value="Send Reset Link">
            </div>
        </form>
    </div>
</body>
</html>
