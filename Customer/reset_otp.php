<?php
session_start();
include 'config.php'; // Database connection
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is autoloaded

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$errors = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Generate a new OTP
    $otp = rand(100000, 999999);
    $expiryTime = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Update OTP and expiry in the database
    $updateSql = "UPDATE CustomerDistributor SET otp = '$otp', otp_expiry = '$expiryTime' WHERE mailId = '$email'";
    if ($conn->query($updateSql) === TRUE) {
        // Send OTP via email
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Replace with your SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your_email@example.com'; // Replace with your SMTP email
            $mail->Password   = 'your_email_password'; // Replace with your SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('your_email@example.com', 'YourAppName');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your New Password Reset OTP';
            $mail->Body    = "Your new OTP for password reset is: <b>$otp</b>. It is valid for 10 minutes.";

            $mail->send();
            $success = "A new OTP has been sent to your email.";
        } catch (Exception $e) {
            $errors = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $errors = "Failed to generate a new OTP. Please try again.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Resend OTP</title>
    <style>
        /* Your existing CSS styles */
        /* ... */
        .alert.error {
            color: red;
            margin-top: 15px;
            text-align: center;
        }
        .alert.success {
            color: green;
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="title">Resend OTP</div>
        <?php if (!empty($errors)): ?>
            <div class="alert error"><?php echo $errors; ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="field">
                <input type="submit" value="Resend OTP">
            </div>
        </form>
        <div class="back-link" style="text-align: center; margin-top: 10px;">
            <a href="verify_otp.php">Back to OTP Verification</a>
        </div>
    </div>
</body>
</html>
