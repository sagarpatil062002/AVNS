<?php
session_start();
include 'config.php';
include 'admin_navbar.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage_products.php');
    exit;
}

$product_id = $_GET['id'];

// Fetch product details from the database
$product_sql = "SELECT * FROM Product WHERE id = ?";
$stmt = $conn->prepare($product_sql);
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product_result = $stmt->get_result();

if ($product_result->num_rows == 0) {
    header('Location: manage_products.php');
    exit;
}

$product = $product_result->fetch_assoc();

// Initialize variables for error and success messages
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize input data
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $oemId = $_POST['oemId'];
    $categoryId = $_POST['categoryId'];
    $subcategories = json_encode(array_map('trim', explode(',', $_POST['subcategories'])));
    $partNo = trim($_POST['partNo']);
    $model = trim($_POST['model']);
    $hsnNo = trim($_POST['hsnNo']);

    // Initialize variables for images and datasheet
    $image_paths = [];
    $images = $product['images'] ? json_decode($product['images'], true) : [];
    $new_images = [];

    // Handle image uploads
    if (isset($_FILES['images']) && $_FILES['images']['error'][0] != UPLOAD_ERR_NO_FILE) {
        $target_dir = "uploads/images/"; // Directory for images

        // Ensure the uploads directory exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // Process each uploaded image file
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
                $new_images[] = $target_file;
            } else {
                $error = "Failed to upload one or more images!";
                break;
            }
        }

        // Merge existing images with new ones
        if (empty($error)) {
            $images = array_merge($images, $new_images);
            $images = array_unique($images); // Remove duplicates
            $images = json_encode($images);
        }
    } else {
        // If no new images uploaded, keep existing images
        $images = json_encode($images);
    }

    // Handle datasheet upload
    $datasheet_path = $product['datasheet']; // Existing datasheet path

    if (isset($_FILES['datasheet']) && $_FILES['datasheet']['error'] != UPLOAD_ERR_NO_FILE) {
        $datasheet_dir = "uploads/datasheets/"; // Directory for datasheets

        // Ensure the uploads directory exists
        if (!is_dir($datasheet_dir)) {
            mkdir($datasheet_dir, 0755, true);
        }

        $datasheet_file = $_FILES['datasheet'];
        $file_error = $datasheet_file['error'];
        $tmp_name = $datasheet_file['tmp_name'];
        $filename = basename($datasheet_file['name']);

        // Validate file type (allow only PDF and DOC/DOCX)
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            $error = "Only PDF, DOC, and DOCX files are allowed for datasheets.";
        } elseif ($file_error === UPLOAD_ERR_OK) {
            // Sanitize file name
            $safe_filename = preg_replace("/[^A-Za-z0-9.\-_]/", '_', $filename);
            $target_file = $datasheet_dir . uniqid() . "_" . $safe_filename;

            if (move_uploaded_file($tmp_name, $target_file)) {
                // Optionally, delete the old datasheet file
                if (!empty($datasheet_path) && file_exists($datasheet_path)) {
                    unlink($datasheet_path);
                }
                $datasheet_path = $target_file;
            } else {
                $error = "Failed to upload the datasheet file!";
            }
        } else {
            $error = "Error uploading the datasheet file.";
        }
    }

    if (empty($error)) {
        // Update product in the database
        $update_sql = "UPDATE Product SET name = ?, description = ?, oemId = ?, categoryId = ?, subcategories = ?, partNo = ?, model = ?, hsnNo = ?, images = ?, datasheet = ?, updatedAt = NOW() WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        if ($stmt === false) {
            $error = "Prepare failed: (" . $conn->errno . ") " . $conn->error;
        } else {
            // Corrected bind_param: 11 types for 11 variables
            $stmt->bind_param('ssiiisssssi', $name, $description, $oemId, $categoryId, $subcategories, $partNo, $model, $hsnNo, $images, $datasheet_path, $product_id);

            if ($stmt->execute()) {
                $success = "Product updated successfully!";
                // Fetch the updated product details
                $stmt->close();
                $product_sql = "SELECT * FROM Product WHERE id = ?";
                $stmt = $conn->prepare($product_sql);
                $stmt->bind_param('i', $product_id);
                $stmt->execute();
                $product_result = $stmt->get_result();
                $product = $product_result->fetch_assoc();
            } else {
                $error = "Failed to update product! " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: #495057;
            line-height: 1.6;
        }
        
        .main-container {
            display: flex;
            min-height: calc(100vh - 60px);
            margin-left:50px;
        }
        
        .content-container {
            flex: 1;
            padding: 30px;
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 20px 25px;
        }
        
        .card-header h3 {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
        }
        
        .card-header h3 i {
            margin-right: 10px;
            color: #4e73df;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            font-weight: 500;
            color: #4e73df;
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control {
            height: 45px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .custom-file-label::after {
            content: "Browse";
            background-color: #e9ecef;
            color: #495057;
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
            padding: 10px 25px;
            font-weight: 500;
            letter-spacing: 0.5px;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #3a5bc7;
            border-color: #3a5bc7;
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 6px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-color: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }
        
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        
        .image-preview {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(220, 53, 69, 0.8);
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
        }
        
        .file-upload-wrapper {
            position: relative;
            margin-bottom: 20px;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background-color: #f8f9fa;
            border: 1px dashed #d1d3e2;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-upload-label:hover {
            background-color: #e9ecef;
        }
        
        .file-upload-label i {
            font-size: 24px;
            margin-right: 10px;
            color: #4e73df;
        }
        
        .file-upload-text {
            flex: 1;
        }
        
        .file-upload-text h5 {
            font-size: 14px;
            margin-bottom: 5px;
            color: #4e73df;
        }
        
        .file-upload-text p {
            font-size: 12px;
            color: #6c757d;
            margin: 0;
        }
        
        .existing-file {
            display: flex;
            align-items: center;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 6px;
            margin-top: 10px;
        }
        
        .existing-file i {
            color: #4e73df;
            margin-right: 10px;
        }
        
        .existing-file a {
            color: #4e73df;
            text-decoration: none;
            font-weight: 500;
        }
        
        .existing-file a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .content-container {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="main-container">
    <?php include 'admin_navbar.php'; ?>
    
    <div class="content-container">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-edit"></i> Edit Product</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="partNo">Part Number</label>
                                <input type="text" class="form-control" id="partNo" name="partNo" value="<?php echo htmlspecialchars($product['partNo']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="oemId">OEM</label>
                                <select class="form-control" id="oemId" name="oemId" required>
                                    <?php
                                    $oem_sql = "SELECT id, name FROM OEM";
                                    $oem_result = $conn->query($oem_sql);
                                    while ($oem = $oem_result->fetch_assoc()) {
                                        $selected = $oem['id'] == $product['oemId'] ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($oem['id']) . "' $selected>" . htmlspecialchars($oem['name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="categoryId">Category</label>
                                <select class="form-control" id="categoryId" name="categoryId" required>
                                    <?php
                                    $category_sql = "SELECT id, name FROM Category";
                                    $category_result = $conn->query($category_sql);
                                    while ($category = $category_result->fetch_assoc()) {
                                        $selected = $category['id'] == $product['categoryId'] ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($category['id']) . "' $selected>" . htmlspecialchars($category['name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="model">Model</label>
                                <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($product['model']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="hsnNo">HSN Number</label>
                                <input type="text" class="form-control" id="hsnNo" name="hsnNo" value="<?php echo htmlspecialchars($product['hsnNo']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="subcategories">Subcategories</label>
                        <input type="text" class="form-control" id="subcategories" name="subcategories" value="<?php echo htmlspecialchars(implode(', ', json_decode($product['subcategories'], true) ?? [])); ?>">
                        <small class="text-muted">Enter comma separated subcategories</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Product Images</label>
                        <div class="file-upload-wrapper">
                            <label for="images" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div class="file-upload-text">
                                    <h5>Upload Product Images</h5>
                                    <p>Click to browse or drag & drop (Max size: 5MB each)</p>
                                </div>
                            </label>
                            <input type="file" id="images" name="images[]" multiple style="display: none;">
                        </div>
                        
                        <?php if (!empty($product['images'])): 
                            $images = json_decode($product['images'], true);
                            if (is_array($images) && count($images) > 0): ?>
                                <div class="image-preview-container">
                                    <?php foreach ($images as $img): ?>
                                        <div class="image-preview">
                                            <img src="<?php echo htmlspecialchars($img); ?>" alt="Product Image">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <small class="text-muted">Existing images will be retained unless replaced</small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Product Datasheet</label>
                        <div class="file-upload-wrapper">
                            <label for="datasheet" class="file-upload-label">
                                <i class="fas fa-file-upload"></i>
                                <div class="file-upload-text">
                                    <h5>Upload Datasheet</h5>
                                    <p>PDF, DOC, or DOCX (Max size: 10MB)</p>
                                </div>
                            </label>
                            <input type="file" id="datasheet" name="datasheet" accept=".pdf,.doc,.docx" style="display: none;">
                        </div>
                        
                        <?php if (!empty($product['datasheet']) && file_exists($product['datasheet'])): ?>
                            <div class="existing-file">
                                <i class="fas fa-file-alt"></i>
                                <a href="<?php echo htmlspecialchars($product['datasheet']); ?>" target="_blank">View Current Datasheet</a>
                            </div>
                            <small class="text-muted">Uploading a new file will replace the existing one</small>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>
    // Preview image before upload
    document.getElementById('images').addEventListener('change', function(event) {
        const previewContainer = document.querySelector('.image-preview-container');
        if (!previewContainer) return;
        
        previewContainer.innerHTML = ''; // Clear existing previews
        
        const files = event.target.files;
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            if (!file.type.match('image.*')) continue;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.createElement('div');
                preview.className = 'image-preview';
                preview.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <div class="remove-image" onclick="this.parentNode.remove()">
                        <i class="fas fa-times"></i>
                    </div>
                `;
                previewContainer.appendChild(preview);
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Show file name when selected
    document.getElementById('datasheet').addEventListener('change', function(e) {
        const fileName = e.target.files.length ? e.target.files[0].name : 'No file selected';
        const label = this.previousElementSibling;
        const textElement = label.querySelector('.file-upload-text p');
        if (textElement) {
            textElement.textContent = fileName;
        }
    });
</script>
</body>
</html>