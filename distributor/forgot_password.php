<?php
include 'config.php'; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string(trim($_POST['email']));

    // Check if the email exists in the CustomerDistributor table
    $sql = "SELECT * FROM distributor WHERE mailId = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Email exists, redirect to reset_password.php with email as parameter
        header("Location: reset_password.php?email=" . $email);
        exit();
    } else {
        echo "<div class='alert error'>No account found with this email address.</div>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');
        *{
          margin: 0;
          padding: 0;
          box-sizing: border-box;
          font-family: 'Poppins', sans-serif;
        }
        html,body{
          display: grid;
          height: 100%;
          width: 100%;
          place-items: center;
          background: #f2f2f2;
        }
        .wrapper{
          width: 380px;
          background: #fff;
          border-radius: 15px;
          box-shadow: 0px 15px 20px rgba(0,0,0,0.1);
        }
        .wrapper .title{
          font-size: 35px;
          font-weight: 600;
          text-align: center;
          line-height: 100px;
          color: #fff;
          user-select: none;
          border-radius: 15px 15px 0 0;
          background: #0e4bf1;
        }
        .wrapper form{
          padding: 10px 30px 50px 30px;
        }
        .wrapper form .field{
          height: 50px;
          width: 100%;
          margin-top: 20px;
          position: relative;
        }
        .wrapper form .field input{
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
        form .field input:valid{
          border-color: #4158d0;
        }
        .wrapper form .field label{
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
        form .field input:valid ~ label{
          top: 0%;
          font-size: 16px;
          color: #4158d0;
          background: #fff;
          transform: translateY(-50%);
        }
        form .field input[type="submit"]{
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
        form .field input[type="submit"]:active{
          transform: scale(0.95);
        }
        .alert.error {
            color: red;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="title">Forgot Password</div>
        <form method="POST" action="">
            <div class="field">
                <input type="email" name="email" required>
                <label>Email Address</label>
            </div>
            <div class="field">
                <input type="submit" value="Verify Email">
            </div>
        </form>
    </div>
</body>
</html>
