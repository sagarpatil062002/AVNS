<?php
session_start();
include('Config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please log in to view this page.");
}

if (!isset($_GET['subscription_id'])) {
    die("Invalid request. Subscription ID is missing.");
}

// Use the working HTTP URL for the image
$imageUrl = "http://localhost/AVNS/Super_Admin/scanner/scanner.jpeg";  // Update with your local server URL

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Now</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { 
            background-color: #f0f4f8; 
            font-family: 'Arial', sans-serif; 
            text-align: center; 
            margin-top: 50px; 
        }
        img { 
            max-width: 50%;  /* Set width to 50% of the container */
            height: auto;    /* Maintain aspect ratio */
            border: 5px solid #ccc; 
            border-radius: 10px; 
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); 
        }
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Scan to Pay</h1>
        <p>Scan the QR code below to complete your payment.</p>
        <img src="<?php echo $imageUrl; ?>" alt="QR Code">
    </div>
</body>
</html>