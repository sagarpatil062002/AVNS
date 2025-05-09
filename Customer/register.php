<?php
// Database connection
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch sectors from the database
$sectors = [];
$sql = "SELECT id, sectorName FROM Sectors";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $sectors[] = $row;
    }
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize input data
    $companyName = $conn->real_escape_string(trim($_POST['companyName']));
    $mailId = $conn->real_escape_string(trim($_POST['mailId']));
    $gstType = $conn->real_escape_string(trim($_POST['gstType']));
    $gstNo = $conn->real_escape_string(trim($_POST['gstNo']));
    $size = $conn->real_escape_string(trim($_POST['size']));
    $address = $conn->real_escape_string(trim($_POST['address']));
    $itAdmin = $conn->real_escape_string(trim($_POST['itAdmin']));
    $purchase = $conn->real_escape_string(trim($_POST['purchase']));
    $ownerName = $conn->real_escape_string(trim($_POST['ownerName']));
    $mobileNo = $conn->real_escape_string(trim($_POST['mobileNo']));
    $image = $conn->real_escape_string(trim($_POST['image']));
    $password = $conn->real_escape_string(trim($_POST['password']));
    $sector = $conn->real_escape_string(trim($_POST['sector']));

    // Validate sector
    if (empty($sector)) {
        die("<div class='alert error'>Sector is required.</div>");
    }

    // Hash the password before storing it
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Prepare the SQL insert statement
    $sql = "INSERT INTO CustomerDistributor 
                (companyName, mailId, gstType, gstNo, size, address, itAdmin, purchase, ownerName, mobileNo, image, password, sectorId) 
            VALUES 
                ('$companyName', '$mailId', '$gstType', '$gstNo', '$size', '$address', '$itAdmin', '$purchase', '$ownerName', '$mobileNo', '$image', '$hashedPassword', '$sector')";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        // Redirect to success page after successful registration
        header("Location: login.php");
        exit();
    } else {
        echo "<div class='alert error'>Error: " . $conn->error . "</div>";
    }
}

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Registration</title>
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
            width: 650px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0px 15px 20px rgba(0,0,0,0.1);
        }
        .wrapper .title {
            font-size: 30px;
            font-weight: 600;
            text-align: center;
            line-height: 60px;
            color: #fff;
            border-radius: 15px 15px 0 0;
            background: #0e4bf1;
        }
        .wrapper form {
            padding: 20px;
        }
        .field {
            margin-top: 20px;
        }
        .field label {
            display: block;
            margin-bottom: 10px;
            color: #999999;
            font-weight: 400;
            font-size: 17px;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        .field input, .field select {
            height: 50px;
            width: 100%;
            outline: none;
            font-size: 17px;
            padding-left: 20px;
            border: 1px solid lightgrey;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .field input:focus, .field select:focus {
            border-color: #4158d0;
        }
        .field input[type="submit"] {
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 20px;
            font-weight: 500;
            cursor: pointer;
            background: #0e4bf1;
            border-radius: 25px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .field input[type="submit"]:active {
            transform: scale(0.95);
        }
        .alert {
            text-align: center;
            margin: 10px 0;
        }
        .alert.success {
            color: green;
        }
        .alert.error {
            color: red;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="title">Customer Registration</div>
    <form method="post" action="">
        <div class="field">
            <label>Company Name <span style="color: red;">*</span></label>
            <input type="text" name="companyName" required>
        </div>
        <div class="field">
            <label>Email ID <span style="color: red;">*</span></label>
            <input type="email" name="mailId" required>
        </div>
        <div class="field">
            <label>Mobile No <span style="color: red;">*</span></label>
            <input type="text" name="mobileNo" required>
        </div>
        <div class="field">
            <label>GST Type <span style="color: red;">*</span></label>
            <select name="gstType" required>
                <option value="" disabled selected>Select GST Type</option>
                <option value="REGISTERED">Registered</option>
                <option value="UNREGISTERED">Unregistered</option>
            </select>
        </div>
        <div class="field">
            <label>GST No (if registered)</label>
            <input type="text" name="gstNo">
        </div>
        <div class="field">
            <label>Company Size <span style="color: red;">*</span></label>
            <select name="size" required>
                <option value="" disabled selected>Select Company Size</option>
                <option value="SMALL">Small</option>
                <option value="MEDIUM">Medium</option>
                <option value="LARGE">Large</option>
            </select>
        </div>
        <div class="field">
            <label>Address <span style="color: red;">*</span></label>
            <input type="text" name="address" required>
        </div>
        <div class="field">
            <label>Sector <span style="color: red;">*</span></label>
            <select name="sector" required>
                <option value="" disabled selected>Select Sector</option>
                <?php foreach ($sectors as $sector): ?>
                    <option value="<?= $sector['id']; ?>"><?= $sector['sectorName']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>IT Admin</label>
            <input type="text" name="itAdmin">
        </div>
        <div class="field">
            <label>Purchase</label>
            <input type="text" name="purchase">
        </div>
        <div class="field">
            <label>Owner Name</label>
            <input type="text" name="ownerName">
        </div>
        <div class="field">
            <label>Image (path or filename)</label>
            <input type="text" name="image">
        </div>
        <div class="field">
            <label>Password <span style="color: red;">*</span></label>
            <input type="password" name="password" required>
        </div>
        <div class="field">
            <input type="submit" value="Register">
        </div>
    </form>
</div>
</body>
</html>
