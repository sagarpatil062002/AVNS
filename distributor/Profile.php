<?php
// Database connection
$host = 'localhost';
$user = 'root'; // Replace with your DB username
$password = ''; // Replace with your DB password
$dbname = 'sales_management';
include('Dnav.php');

$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// Check if the customer is logged in
if (isset($_SESSION['user_id'])) {
    $distributorId = $_SESSION['user_id']; // Use the logged-in user's ID for distributorId
} else {
    die("No customer is logged in. Please log in.");
}

// Fetch distributor data
$sql = "SELECT * FROM distributor WHERE id = $distributorId";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $distributor = $result->fetch_assoc();
} else {
    die("Distributor not found.");
}

// Handle form submission for editing profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = $_POST['companyName'];
    $mailId = $_POST['mailId'];
    $gstType = $_POST['gstType'];
    $gstNo = $_POST['gstNo'];
    $size = $_POST['size'];
    $address = $_POST['address'];
    $ownerName = $_POST['ownerName'];
    $mobileNo = $_POST['mobileNo'];
    $image = $_POST['image'];
    $password = $_POST['password']; // Assuming you also want to update the password

    $updateSql = "UPDATE distributor SET 
        companyName='$companyName',
        mailId='$mailId',
        gstType='$gstType',
        gstNo='$gstNo',
        size='$size',
        address='$address',
        ownerName='$ownerName',
        mobileNo='$mobileNo',
        image='$image',
        password='$password',
        updatedAt=NOW()
        WHERE id=$distributorId";

    if ($conn->query($updateSql) === TRUE) {
        echo "Profile updated successfully.";
        // Refresh distributor data
        $result = $conn->query($sql);
        $distributor = $result->fetch_assoc();
    } else {
        echo "Error updating profile: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Distributor Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background: #f0f2f5;
            font-family: Arial, sans-serif;
        }

        .container {
            margin-top: 50px;
            margin-left: 310px; /* Space for the sidebar */
        width: 1200px;
        position: relative;
        }

        .profile-button {
            background: #007bff;
            color: white;
            border: none;
            box-shadow: none;
            cursor: pointer;
        }
        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ddd;
            margin-bottom: 20px;
        }

        .labels {
            font-weight: bold;
        }

        .mb-3 {
            margin-bottom: 30px !important;
        }

        h4.text-right {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .d-flex .flex-column {
            align-items: center;
            text-align: center;
        }

        .d-flex .flex-column .font-weight-bold {
            font-size: 20px;
            margin-top: 10px;
            color: #333;
        }

        .d-flex .flex-column .text-black-50 {
            color: #6c757d;
            font-size: 14px;
        }

        .row .col-md-6,
        .row .col-md-12 {
            padding-right: 15px;
            padding-left: 15px;
        }

        .row .col-md-6 .form-control {
            margin-bottom: 20px;
        }

        .mt-5 {
            margin-top: 40px;
        }
        .profile-button:hover {
            background: #0056b3;
        }

        .profile-button:active {
            background: #0056b3;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #007bff;
        }

        .labels {
            font-size: 12px;
            font-weight: bold;
        }

        .add-experience {
            background: #28a745;
            color: white;
            border: 1px solid #28a745;
            padding: 5px 15px;
            cursor: pointer;
        }

        .add-experience:hover {
            background: #218838;
        }

        .text-black-50 {
            color: #6c757d;
        }

        .rounded-circle {
            border-radius: 50%;
        }

        .border-right {
            border-right: 1px solid #ddd;
        }

        .py-5 {
            padding-top: 3rem;
            padding-bottom: 3rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>

<div class="container rounded bg-white mt-5 mb-5">
    <div class="row">
        <div class="col-md-3 border-right">
            <div class="d-flex flex-column align-items-center text-center p-3 py-5">
                <img class="rounded-circle mt-5" width="150px" src="<?php echo $distributor['image']; ?>" alt="Distributor Image">

                <span class="font-weight-bold"><?php echo $distributor['companyName']; ?></span>
                <span class="text-black-50"><?php echo $distributor['mailId']; ?></span>
            </div>
        </div>
        <div class="col-md-9">
            <div class="p-3 py-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="text-right">Profile Settings</h4>
                </div>
                <form method="post">
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label class="labels">Company Name</label>
                            <input type="text" class="form-control" name="companyName" value="<?php echo $distributor['companyName']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="labels">Email ID</label>
                            <input type="email" class="form-control" name="mailId" value="<?php echo $distributor['mailId']; ?>" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="labels">GST Type</label>
                            <select class="form-control" name="gstType" required>
                                <option value="REGISTERED" <?php if ($distributor['gstType'] === 'REGISTERED') echo 'selected'; ?>>Registered</option>
                                <option value="UNREGISTERED" <?php if ($distributor['gstType'] === 'UNREGISTERED') echo 'selected'; ?>>Unregistered</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="labels">GST No.</label>
                            <input type="text" class="form-control" name="gstNo" value="<?php echo $distributor['gstNo']; ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="labels">Company Size</label>
                            <select class="form-control" name="size" required>
                                <option value="SMALL" <?php if ($distributor['size'] === 'SMALL') echo 'selected'; ?>>Small</option>
                                <option value="MEDIUM" <?php if ($distributor['size'] === 'MEDIUM') echo 'selected'; ?>>Medium</option>
                                <option value="LARGE" <?php if ($distributor['size'] === 'LARGE') echo 'selected'; ?>>Large</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="labels">Address</label>
                            <input type="text" class="form-control" name="address" value="<?php echo $distributor['address']; ?>" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="labels">Mobile No.</label>
                            <input type="text" class="form-control" name="mobileNo" value="<?php echo $distributor['mobileNo']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="labels">Owner Name</label>
                            <input type="text" class="form-control" name="ownerName" value="<?php echo $distributor['ownerName']; ?>">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="labels">Image URL</label>
                            <input type="text" class="form-control" name="image" value="<?php echo $distributor['image']; ?>">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <label class="labels">Password</label>
                            <input type="password" class="form-control" name="password" value="<?php echo $distributor['password']; ?>" required>
                        </div>
                    </div>
                    <div class="mt-5 text-center">
                        <button type="submit" class="btn btn-primary profile-button">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
