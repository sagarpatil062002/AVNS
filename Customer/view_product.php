<?php
session_start();
ob_start(); // Add this at the very top

include 'CustomerNav.php';

include('Config.php');


// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logged-in customer ID from session
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
} else {
    die("No customer is logged in. Please log in.");
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($product_id) {
    // Fetch product details based on product ID
    $sql = "SELECT * FROM Product WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if product is found
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        echo "Product not found!";
        exit;
    }
} else {
    echo "Invalid product ID!";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $productId = $_POST['product_id'];
    $description = $_POST['description'];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = array();
    }

    if (isset($_SESSION['cart'][$productId])) {
        $productExistsMessage = "This product is already in your cart!";
    } else {
        $_SESSION['cart'][$productId] = array('product_id' => $productId, 'description' => $description);
        ob_end_clean(); // Clean the output buffer before redirect
        header('Location: cart.php');
        exit();
    }
}
// Define the base paths
$imageBasePath = "../Super_Admin/uploads/images/";
$datasheetBasePath = "../Super_Admin/uploads/datasheets/";

// Debug: Check datasheet path
if (!empty($product['datasheet'])) {
    $datasheetPath = $datasheetBasePath . basename($product['datasheet']);
    if (!file_exists($datasheetPath)) {
        error_log("Datasheet not found at: " . $datasheetPath);
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Product View - <?php echo $product['name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> <!-- Link to external CSS file -->
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            overflow-x: hidden; /* Remove horizontal scrollbar */
        }

        /* Container for the product page */
        .container {
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            margin-left: 310px;
            max-width: 1200px; /* Limit container width */
        }

        /* Cart Icon Styles */
        .cart-icon-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .cart-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 50px;
            height: 50px;
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            font-size: 24px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .cart-icon:hover {
            background-color: #0056b3;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            font-size: 14px;
            font-weight: bold;
            border-radius: 50%;
            padding: 3px 7px;
        }

        /* Datasheet Styles */
        .datasheet-section {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .datasheet-link {
            margin-top: 20px;
            padding: 10px 30px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            text-decoration: none;
            transition: background-color 0.3s ease;
            display: inline-block;
        }

        .datasheet-link:hover {
            background-color: #218838;
            color: white;
            text-decoration: none;
        }

        /* Product image section */
        .product-view img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Product name */
        .product-name {
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* Product path */
        .product-path {
            font-size: 0.9em;
            color: #555;
        }

        /* Selling price and product details */
        .selling-price {
            font-size: 1.2em;
            font-weight: bold;
            color: #007bff;
        }

        .hsn-no {
            font-size: 1em;
            margin-top: 10px;
            color: #666;
        }

        /* Description section */
        .description h5 {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
        }

        .description p {
            font-size: 1em;
            color: #555;
            line-height: 1.5;
        }

        /* Add to Cart button */
        .add-to-cart {
            margin-top: 20px;
            padding: 10px 30px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
        }

        .add-to-cart:hover {
            background-color: #0056b3;
        }

        /* Alert message */
        .alert {
            margin-top: 20px;
            font-size: 1.1em;
        }

        /* Carousel styles */
        .carousel {
            max-width: 500px;
            margin: 0 auto;
        }

        .carousel-item img {
            width: 100%;
            height: auto;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <!-- Cart Icon -->
    <div class="cart-icon-container">
        <a href="cart.php" class="cart-icon">
            <i class="fa fa-shopping-cart"></i>
            <span class="cart-count"><?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?></span>
        </a>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <!-- Product Image and Details Section -->
            <div class="col-md-5">
                <div class="product-view">
                    <?php
                    $images = $product['images'];
                    $imagesArray = json_decode($images, true);
                    if (is_array($imagesArray) && count($imagesArray) > 0) {
                        // Debug: Check if images exist
                        foreach ($imagesArray as $img) {
                            $fullPath = $imageBasePath . basename($img);
                            if (!file_exists($fullPath)) {
                                error_log("Image not found: " . $fullPath);
                            }
                        }
                    ?>
                    <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-indicators">
                            <?php foreach ($imagesArray as $index => $img): ?>
                                <button type="button" data-bs-target="#productCarousel" data-bs-slide-to="<?php echo $index; ?>" <?php echo $index === 0 ? 'class="active"' : ''; ?>></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-inner">
                            <?php foreach ($imagesArray as $index => $img): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($imageBasePath . basename($img)); ?>" 
                                         class="d-block w-100" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                         onerror="this.onerror=null; this.src='../assets/no-image.jpg';">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                    <?php } else { ?>
                        <img src="<?php echo htmlspecialchars($imageBasePath . basename($images)); ?>" class="w-100" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php } ?>
                </div>
            </div>

            <!-- Product Details Section -->
            <div class="col-md-7">
                <div class="product-view">
                    <h4 class="product-name"><?php echo $product['name']; ?></h4>
                    <p class="product-path">
                        Home / Category / Product / <?php echo $product['name']; ?>
                    </p>
                    <div>
                        <span class="selling-price">Part No: <?php echo $product['partNo']; ?></span>
                    </div>
                    <div>
                        <span class="selling-price">Model: <?php echo $product['model']; ?></span>
                    </div>
                    <div class="hsn-no">
                        <span class="selling-price">HSN No: <?php echo $product['hsnNo']; ?></span>
                    </div>

                    <div class="mt-3 description">
                        <h5>Description</h5>
                        <p><?php echo $product['description']; ?></p>
                    </div>

                    <!-- Action Buttons Section -->
                    <div class="d-flex gap-3 mt-4">
                        <!-- Add to Cart Form -->
                        <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>" />
                            <input type="hidden" name="description" value="<?php echo $product['description']; ?>" />
                            <button type="submit" name="add_to_cart" class="btn btn-primary add-to-cart">Add to Cart</button>
                        </form>

                        <!-- Datasheet Button -->
                        <?php if (!empty($product['datasheet'])): 
                            $datasheetPath = $datasheetBasePath . basename($product['datasheet']);
                            if (file_exists($datasheetPath)): ?>
                        <a href="<?php echo htmlspecialchars($datasheetPath); ?>" 
                           class="datasheet-link" 
                           target="_blank">
                            <i class="fa fa-file-pdf-o"></i> Download Datasheet
                        </a>
                        <?php endif; endif; ?>
                    </div>

                    <!-- Show message if product already exists in cart -->
                    <?php if (isset($productExistsMessage)) { ?>
                        <div class="alert alert-warning" role="alert">
                            <?php echo $productExistsMessage; ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
