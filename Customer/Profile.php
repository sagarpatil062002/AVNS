<?php 
// Database connection
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// Check if the customer is logged in
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
} else {
    die("No customer is logged in. Please log in.");
}

// Fetch customer data
$sql = "SELECT * FROM customerdistributor WHERE id = $customerId";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $customer = $result->fetch_assoc();
} else {
    die("Customer not found.");
}

// Handle form submission for editing profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageName = $_FILES['image']['name'];
        $imagePath = 'C:/xampp/htdocs/AVNS/Customer/myimage/' . basename($imageName);

        // Move the uploaded image to the desired folder
        if (move_uploaded_file($imageTmpName, $imagePath)) {
            // File uploaded successfully
            $image = 'Customer/myimage/' . basename($imageName);
        } else {
            $image = $customer['image']; // If file upload fails, keep the old image
        }
    } else {
        // If no new image is uploaded, keep the old image
        $image = $customer['image'];
    }

    // Update customer data
    $companyName = $_POST['companyName'];
    $mailId = $_POST['mailId'];
    $gstType = $_POST['gstType'];
    $gstNo = $_POST['gstNo'];
    $size = $_POST['size'];
    $address = $_POST['address'];
    $itAdmin = $_POST['itAdmin'];
    $purchase = $_POST['purchase'];
    $ownerName = $_POST['ownerName'];
    $mobileNo = $_POST['mobileNo'];

    $updateSql = "UPDATE customerdistributor SET 
        companyName='$companyName',
        mailId='$mailId',
        gstType='$gstType',
        gstNo='$gstNo',
        size='$size',
        address='$address',
        itAdmin='$itAdmin',
        purchase='$purchase',
        ownerName='$ownerName',
        mobileNo='$mobileNo',
        image='$image',
        updatedAt=NOW()
        WHERE id=$customerId";

    if ($conn->query($updateSql) === TRUE) {
        $message = "Profile updated successfully.";
        // Refresh customer data
        $result = $conn->query($sql);
        $customer = $result->fetch_assoc();
    } else {
        $message = "Error updating profile: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Customer Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

<style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --secondary: #f8f9fc;
            --success: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --gray: #858796;
            --gray-light: #dddfeb;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--dark);
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-left: 250px;
            transition: all 0.3s ease;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.75rem;
            border-bottom: 1px solid var(--gray-light);
            padding-bottom: 1rem;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--primary);
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .profile-sidebar {
            border-right: 1px solid var(--gray-light);
            padding-right: 2rem;
        }
        
        .company-name {
            font-weight: 700;
            font-size: 1.25rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .company-email {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 0.35rem;
            border: 1px solid var(--gray-light);
            padding: 0.75rem 1rem;
            margin-bottom: 1.25rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem 2rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 0.35rem;
            transition: all 0.2s ease;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: rgba(28, 200, 138, 0.1);
            border-color: var(--success);
            color: #0d8a5a;
        }
        
        .alert-danger {
            background-color: rgba(231, 74, 59, 0.1);
            border-color: var(--danger);
            color: #c23321;
        }
        
        @media (max-width: 992px) {
            .profile-container {
                margin-left: 0;
                margin-top: 1rem;
                padding: 1.5rem;
            }
            
            .profile-header {
                font-size: 1.5rem;
            }
            
            .profile-sidebar {
                border-right: none;
                border-bottom: 1px solid var(--gray-light);
                padding-right: 0;
                padding-bottom: 2rem;
                margin-bottom: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .profile-image {
                width: 120px;
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <?php include 'CustomerNav.php'; ?>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="profile-container">
                    <h1 class="profile-header">
                        <i class="fas fa-user-edit me-2"></i>Profile Settings
                    </h1>

                    <?php if (isset($message)): ?>
                        <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
                            <i class="fas <?php echo strpos($message, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle'; ?> me-2"></i>
                            <?= htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4 profile-sidebar">
                            <div class="d-flex flex-column align-items-center text-center">
                                <img class="profile-image" src="http://localhost/AVNS/<?php echo $customer['image']; ?>" alt="Customer Image">
                                <div class="company-name"><?php echo htmlspecialchars($customer['companyName']); ?></div>
                                <div class="company-email"><?php echo htmlspecialchars($customer['mailId']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <form method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Company Name</label>
                                        <input type="text" class="form-control" name="companyName" value="<?php echo htmlspecialchars($customer['companyName']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email ID</label>
                                        <input type="email" class="form-control" name="mailId" value="<?php echo htmlspecialchars($customer['mailId']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">GST Type</label>
                                        <select class="form-control" name="gstType" required>
                                            <option value="REGISTERED" <?php if ($customer['gstType'] === 'REGISTERED') echo 'selected'; ?>>Registered</option>
                                            <option value="UNREGISTERED" <?php if ($customer['gstType'] === 'UNREGISTERED') echo 'selected'; ?>>Unregistered</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">GST No.</label>
                                        <input type="text" class="form-control" name="gstNo" value="<?php echo htmlspecialchars($customer['gstNo']); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Company Size</label>
                                        <select class="form-control" name="size" required>
                                            <option value="SMALL" <?php if ($customer['size'] === 'SMALL') echo 'selected'; ?>>Small</option>
                                            <option value="MEDIUM" <?php if ($customer['size'] === 'MEDIUM') echo 'selected'; ?>>Medium</option>
                                            <option value="LARGE" <?php if ($customer['size'] === 'LARGE') echo 'selected'; ?>>Large</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Address</label>
                                        <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($customer['address']); ?>" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label class="form-label">Mobile No.</label>
                                        <input type="text" class="form-control" name="mobileNo" value="<?php echo htmlspecialchars($customer['mobileNo']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Owner Name</label>
                                        <input type="text" class="form-control" name="ownerName" value="<?php echo htmlspecialchars($customer['ownerName']); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">IT Admin</label>
                                        <input type="text" class="form-control" name="itAdmin" value="<?php echo htmlspecialchars($customer['itAdmin']); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Purchase</label>
                                        <input type="text" class="form-control" name="purchase" value="<?php echo htmlspecialchars($customer['purchase']); ?>">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <label class="form-label">Profile Image</label>
                                        <input type="file" class="form-control" name="image">
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-md-12 text-center">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Save Profile
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>