<?php
session_start();
include 'config.php'; // Database connection

// Check if the user has reached this page via the reset process
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];
$errors = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $enteredOtp = $conn->real_escape_string(trim($_POST['otp']));

    // Retrieve OTP and expiry from the database
    $sql = "SELECT otp, otp_expiry FROM CustomerDistributor WHERE mailId = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $storedOtp = $user['otp'];
        $otpExpiry = strtotime($user['otp_expiry']);
        $currentTime = time();

        if ($storedOtp === $enteredOtp) {
            if ($currentTime <= $otpExpiry) {
                // OTP is valid and not expired
                // Remove OTP from database
                $updateSql = "UPDATE CustomerDistributor SET otp = NULL, otp_expiry = NULL WHERE mailId = '$email'";
                if ($conn->query($updateSql) === TRUE) {
                    // Redirect to reset password page
                    header("Location: reset_password.php");
                    exit();
                } else {
                    $errors = "Failed to verify OTP. Please try again.";
                }
            } else {
                $errors = "OTP has expired. Please request a new one.";
            }
        } else {
            $errors = "Invalid OTP. Please try again.";
        }
    } else {
        $errors = "No account associated with this email.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify OTP</title>
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
        <div class="title">Verify OTP</div>
        <?php if (!empty($errors)): ?>
            <div class="alert error"><?php echo $errors; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="field">
                <input type="text" name="otp" pattern="\d{6}" title="Enter the 6-digit OTP" required>
                <label>Enter OTP</label>
            </div>
            <div class="field">
                <input type="submit" value="Verify OTP">
            </div>
        </form>
        <div class="resend-link" style="text-align: center; margin-top: 10px;">
            <a href="resend_otp.php">Resend OTP</a>
        </div>
    </div>
</body>
</html>
