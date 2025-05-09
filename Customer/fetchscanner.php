<?php
// Include the database connection
include('config.php');
include 'CustomerNav.php';

// You may want to fetch the path of the scanner image from the database based on the Super Admin's settings
// For now, I am hardcoding the path. You can modify this according to your requirement.
$scannerPath = "http://localhost/AVNS/Super_Admin/scanner/scanner.jpeg"; // Replace with the dynamic path if needed
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Scanner</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">

    
    <!-- Custom CSS for image resizing -->
    <style>
        .scanner-img {
            max-width: 300px;  /* Set max width */
            height: 200px;     /* Set height */
            object-fit: cover; /* To preserve aspect ratio */
            background-color: ;
        }

        body{
            background-color: #f0f0f0;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center">View Scanner</h1>
        <div class="text-center">
            <!-- Apply the custom class to control image size -->
            <img src="<?php echo $scannerPath; ?>" alt="Scanner Image" class="scanner-img">
        </div>
        <div class="text-center mt-4">
            <a href="javascript:history.back()" class="btn btn-secondary btn-sm">Go Back</a>
        </div>
    </div>
</body>
</html>