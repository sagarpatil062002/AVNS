<?php
ob_start();
session_start();
include 'admin_navbar.php';
include 'config.php';

// Fetch tax rates for dropdown
$tax_rates_sql = "SELECT id, tax_name, tax_percentage FROM tax_rates";
$tax_rates_result = $conn->query($tax_rates_sql);

// Fetch OEM options
$oem_sql = "SELECT id, name FROM OEM";
$oem_result = $conn->query($oem_sql);

// Fetch category options
$category_sql = "SELECT id, name FROM Category";
$category_result = $conn->query($category_sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $oemId = $_POST['oemId'];
    $categoryId = $_POST['categoryId'];
    $subcategories = json_encode(explode(',', $_POST['subcategories'])); // Handle subcategories as JSON
    $partNo = $_POST['partNo'];
    $model = $_POST['model'];
    $hsnNo = $_POST['hsnNo'];
    $tax_rate_id = $_POST['tax_rate_id'];

    // Handle file uploads for images
    $target_dir = "uploads/images/"; // Directory where images will be stored
    $image_paths = [];

    // Ensure the uploads directory exists
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Process each uploaded file for images
    if (isset($_FILES['images']) && $_FILES['images']['error'][0] != UPLOAD_ERR_NO_FILE) {
        foreach ($_FILES['images']['name'] as $key => $filename) {
            $tmp_name = $_FILES['images']['tmp_name'][$key];
            $file_error = $_FILES['images']['error'][$key];
            $file_size = $_FILES['images']['size'][$key];

            // Skip file if any error occurred
            if ($file_error !== UPLOAD_ERR_OK) {
                continue;
            }

            // Validate file type (allow only images)
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);

            if (!in_array($mime_type, $allowed_types)) {
                $error = "Only JPG, PNG, and GIF files are allowed for images.";
                break;
            }

            // Sanitize file name
            $basename = basename($filename);
            $safe_filename = preg_replace("/[^A-Za-z0-9.\-_]/", '_', $basename);
            $target_file = $target_dir . uniqid() . "_" . $safe_filename;

            if (move_uploaded_file($tmp_name, $target_file)) {
                $image_paths[] = $target_file;
            } else {
                $error = "Failed to upload one or more images!";
                break;
            }
        }
    }

    // Handle file upload for datasheet
    $datasheet_path = '';
    if (isset($_FILES['datasheet']) && $_FILES['datasheet']['error'] == UPLOAD_ERR_OK) {
        $datasheet_file = $_FILES['datasheet'];
        $datasheet_dir = "uploads/datasheets/"; // Separate directory for datasheets
        
        // Ensure the datasheets directory exists
        if (!is_dir($datasheet_dir)) {
            mkdir($datasheet_dir, 0755, true);
        }
        
        // Sanitize the filename
        $safe_filename = preg_replace("/[^A-Za-z0-9.\-_]/", '_', basename($datasheet_file['name']));
        $datasheet_path = $datasheet_dir . uniqid() . "_" . $safe_filename;
        
        if (!move_uploaded_file($datasheet_file['tmp_name'], $datasheet_path)) {
            $error = "Failed to upload datasheet!";
        }
    }

    // Store image paths as JSON in the database
    $images = json_encode($image_paths);

    if (!isset($error)) {
        // Prepare the SQL statement with all fields
        $stmt = $conn->prepare("INSERT INTO Product (name, description, oemId, categoryId, subcategories, partNo, model, hsnNo, images, datasheet, tax_rate_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('ssssssssssi', $name, $description, $oemId, $categoryId, $subcategories, $partNo, $model, $hsnNo, $images, $datasheet_path, $tax_rate_id);
        if ($stmt->execute()) {
            header('Location: manage_product.php');
            exit;
        } else {
            $error = "Failed to add product!";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="style.css">
    
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --success-color: #4cc9f0;
            --error-color: #f72585;
            --light-gray: #f8f9fa;
            --dark-gray: #6c757d;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            min-height: 120vh;
            background-color: var(--light-gray);
            color: #333;
            line-height: 1.6;
        }

        .main-container {
            display: flex;
            min-height: 120vh;
            width: 100%;
        }

        .content-wrapper {
            flex: 1;
            padding: 2rem;
            margin-left: 250px;
            transition: var(--transition);
        }

        .card {
            max-width: 800px;
            width: 100%;
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2.5rem;
            margin: 2rem auto;
            border: none;
        }

        .card-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #333; /* Changed for better visibility */
            font-size: 0.95rem;
        }

        .form-control {
    font-size: 16px;       /* Increase text size */
    padding: 10px 14px;    /* Adjust spacing inside the dropdown */
    height: auto;          /* Allow height to adjust based on content */
    min-height: 48px;      /* Optional: Ensures minimum clickable area */
    line-height: 1.5;      /* Better vertical alignment */
}


        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
            padding-right: 2.5rem;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
            background-color: var(--white);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 38px;
            color: var(--dark-gray);
        }

        .file-input-container {
            position: relative;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border: 1px dashed #aaa;
            border-radius: 8px;
            background-color: var(--light-gray);
            cursor: pointer;
            transition: var(--transition);
        }

        .file-input-label:hover {
            border-color: var(--primary-light);
            background-color: rgba(67, 97, 238, 0.05);
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 0.1px;
            height: 0.1px;
        }

        .file-name {
            margin-left: 10px;
            color: var(--dark-gray);
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 70%;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: rgba(76, 201, 240, 0.15);
            color: #0a9396;
            border-left: 4px solid #0a9396;
        }

        .alert-error {
            background-color: rgba(247, 37, 133, 0.15);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        .icon {
            font-size: 1.25rem;
        }

        .grid-2-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 992px) {
            .content-wrapper {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .grid-2-col {
                grid-template-columns: 1fr;
                
            }
        }

        @media (max-width: 576px) {
            .card {
                padding: 1.25rem;
            }
            
            .card-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Add New Product</h1>
                <p class="card-subtitle">Fill in the details to add a new product</p>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name" class="form-label">Product Name</label>
                    <input type="text" id="name" name="name" class="form-control" required placeholder="Enter product name">
                    <i class="uil uil-label-alt input-icon"></i>
                </div>
                
                <div class="form-group">
                    <label for="description" class="form-label">Description</label>
                    <textarea id="description" name="description" class="form-control" required placeholder="Enter product description"></textarea>
                </div>
                
                <div class="grid-2-col">
                    <div class="form-group">
                        <label for="oemId" class="form-label">OEM</label>
                        <select id="oemId" name="oemId" class="form-control" required>
                            <option value="">Select OEM</option>
                            <?php while ($oem_row = $oem_result->fetch_assoc()): ?>
                                <option value="<?= $oem_row['id'] ?>"><?= htmlspecialchars($oem_row['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="categoryId" class="form-label">Category</label>
                        <select id="categoryId" name="categoryId" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php while ($category_row = $category_result->fetch_assoc()): ?>
                                <option value="<?= $category_row['id'] ?>"><?= htmlspecialchars($category_row['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="subcategories" class="form-label">Subcategories (comma-separated)</label>
                    <input type="text" id="subcategories" name="subcategories" class="form-control" required placeholder="e.g. subcategory1, subcategory2">
                </div>
                
                <div class="grid-2-col">
                    <div class="form-group">
                        <label for="partNo" class="form-label">Part Number</label>
                        <input type="text" id="partNo" name="partNo" class="form-control" placeholder="Enter part number">
                    </div>
                    
                    <div class="form-group">
                        <label for="model" class="form-label">Model</label>
                        <input type="text" id="model" name="model" class="form-control" placeholder="Enter model number">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="hsnNo" class="form-label">HSN Number</label>
                    <input type="text" id="hsnNo" name="hsnNo" class="form-control" placeholder="Enter HSN code">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Product Images</label>
                    <div class="file-input-container">
                        <label for="images" class="file-input-label">
                            <i class="uil uil-image-upload icon"></i>
                            <span class="file-name" id="imageFileName">Click to upload images (multiple allowed)</span>
                        </label>
                        <input type="file" id="images" name="images[]" class="file-input" multiple onchange="updateFileName(this, 'imageFileName')">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Datasheet</label>
                    <div class="file-input-container">
                        <label for="datasheet" class="file-input-label">
                            <i class="uil uil-file-upload icon"></i>
                            <span class="file-name" id="datasheetFileName">Click to upload datasheet</span>
                        </label>
                        <input type="file" id="datasheet" name="datasheet" class="file-input" onchange="updateFileName(this, 'datasheetFileName')">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tax_rate_id" class="form-label">Tax Rate</label>
                    <select id="tax_rate_id" name="tax_rate_id" class="form-control" required>
                        <option value="">Select Tax Rate</option>
                        <?php while($tax_rate = $tax_rates_result->fetch_assoc()): ?>
                            <option value="<?= $tax_rate['id'] ?>">
                                <?= htmlspecialchars($tax_rate['tax_name']) ?> (<?= $tax_rate['tax_percentage'] ?>%)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="uil uil-plus-circle icon"></i> Add Product
                </button>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <i class="uil uil-exclamation-circle icon"></i> <?= $error ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
    function updateFileName(input, labelId) {
        const fileName = input.files.length > 0 
            ? input.files.length === 1 
                ? input.files[0].name 
                : `${input.files.length} files selected`
            : input.id === 'images' 
                ? 'Click to upload images (multiple allowed)' 
                : 'Click to upload datasheet';
        
        document.getElementById(labelId).textContent = fileName;
    }

    // Add animation to form elements
    document.addEventListener('DOMContentLoaded', function() {
        const formControls = document.querySelectorAll('.form-control');
        formControls.forEach(control => {
            control.addEventListener('focus', function() {
                this.parentElement.querySelector('.form-label').style.color = 'var(--primary-color)';
            });
            control.addEventListener('blur', function() {
                this.parentElement.querySelector('.form-label').style.color = '#495057';
            });
        });
    });
</script>
</body>
</html>