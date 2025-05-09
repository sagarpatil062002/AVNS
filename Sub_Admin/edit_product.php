<?php
session_start();
include 'config.php';

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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            min-height: 100vh;
            background: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 6px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        .container header {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        .container form .form-group {
            margin-bottom: 15px;
        }
        .container form .form-group label {
            font-weight: 500;
        }
        .container form .form-group input,
        .container form .form-group select,
        .container form .form-group textarea {
            border-radius: 4px;
            border: 1px solid #ddd;
            padding: 10px;
            width: 100%;
        }
        .container form .form-group textarea {
            resize: vertical;
        }
        .btn {
            background-color: #4070f4;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #265df2;
        }
        .alert {
            padding: 15px;
            border: 1px solid transparent;
            border-radius: 5px;
            margin-top: 20px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #842029;
            border-color: #f5c2c7;
        }
        .alert-success {
            background-color: #d1e7dd;
            color: #0f5132;
            border-color: #badbcc;
        }
    </style>
</head>
<body>
<div class="container">
    <header>Edit Product</header>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST" enctype="multipart/form-data"> <!-- Ensure enctype for file uploads -->
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
        </div>
        <div class="form-group">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="oemId">OEM</label>
            <select id="oemId" name="oemId" required>
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
        <div class="form-group">
            <label for="categoryId">Category</label>
            <select id="categoryId" name="categoryId" required>
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
        <div class="form-group">
            <label for="subcategories">Subcategories (comma separated)</label>
            <input type="text" id="subcategories" name="subcategories" value="<?php echo htmlspecialchars(implode(', ', json_decode($product['subcategories'], true) ?? [])); ?>">
        </div>
        <div class="form-group">
            <label for="partNo">Part No</label>
            <input type="text" id="partNo" name="partNo" value="<?php echo htmlspecialchars($product['partNo']); ?>" required>
        </div>
        <div class="form-group">
            <label for="model">Model</label>
            <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($product['model']); ?>" required>
        </div>
        <div class="form-group">
            <label for="hsnNo">HSN No</label>
            <input type="text" id="hsnNo" name="hsnNo" value="<?php echo htmlspecialchars($product['hsnNo']); ?>" required>
        </div>
        <div class="form-group">
            <label for="images">Images (upload files)</label>
            <input type="file" id="images" name="images[]" multiple>
            <?php
            if (!empty($product['images'])) {
                $images = json_decode($product['images'], true);
                if (is_array($images) && count($images) > 0) {
                    echo "<div class='mt-2'>";
                    echo "<strong>Existing Images:</strong><br>";
                    foreach ($images as $img) {
                        echo "<a href='" . htmlspecialchars($img) . "' target='_blank'>View Image</a><br>";
                    }
                    echo "</div>";
                }
            }
            ?>
        </div>
        <div class="form-group">
            <label for="datasheet">Datasheet</label>
            <?php if (!empty($product['datasheet']) && file_exists($product['datasheet'])): ?>
                <div class="mb-2">
                    <a href="<?php echo htmlspecialchars($product['datasheet']); ?>" target="_blank">View Existing Datasheet</a>
                </div>
            <?php endif; ?>
            <input type="file" id="datasheet" name="datasheet" accept=".pdf,.doc,.docx">
            <small class="form-text text-muted">Upload a new datasheet to replace the existing one. Allowed types: PDF, DOC, DOCX.</small>
        </div>
        <div class="form-group">
            <button type="submit" class="btn">Update Product</button>
        </div>
    </form>
</div>
</body>
</html>
