<?php
session_start();

// Database connection (replace with your database credentials)
include('Config.php');

include 'CustomerNav.php';

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

// Fetch all products
$sql = "SELECT * FROM Product"; 
$result = $conn->query($sql);

// Define the base path for the images
$basePath = "../Super_Admin/uploads/images/";

// Calculate the number of products in the cart
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Our Products</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --danger-color: #f44336;
            --warning-color: #ff9800;
            --info-color: #2196f3;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
        }

        .main-content {
            padding-left: 300px;
            padding-right: 20px;
            transition: padding 0.3s ease;
        }

        @media (max-width: 992px) {
            .main-content {
                padding-left: 20px;
            }
        }

        /* Header Styles */
        .page-header {
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid #e0e0e0;
        }

        .page-header h4 {
            font-weight: 600;
            color: var(--primary-color);
            position: relative;
            display: inline-block;
        }

        .page-header h4::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--accent-color);
            border-radius: 3px;
        }

        /* Product Card Styles */
        .product-card {
            background-color: #fff;
            border: none;
            border-radius: var(--border-radius);
            overflow: hidden;
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: var(--box-shadow);
            position: relative;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .product-card a {
            text-decoration: none;
            color: inherit;
        }

        .product-card .product-card-img {
            width: 100%;
            height: 200px;
            overflow: hidden;
            background-color: #f9f9f9;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .product-card .product-card-img img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transition: var(--transition);
        }

        .product-card:hover .product-card-img img {
            transform: scale(1.05);
        }

        .product-card .product-card-body {
            padding: 20px;
            background-color: #fff;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-card .product-name {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            min-height: 48px;
        }

        .product-card .product-name a:hover {
            color: var(--primary-color);
        }

        .product-card .product-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 8px;
        }

        .product-card .product-details strong {
            color: var(--dark-color);
            font-weight: 500;
        }

        .product-card .btn-view {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 500;
            margin-top: auto;
            transition: var(--transition);
            text-align: center;
            width: 100%;
        }

        .product-card .btn-view:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        /* Carousel Styles */
        .product-carousel {
            height: 100%;
        }

        .product-carousel .carousel-indicators {
            bottom: -25px;
        }

        .product-carousel .carousel-indicators button {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: rgba(0, 0, 0, 0.2);
            border: none;
            margin: 0 3px;
        }

        .product-carousel .carousel-indicators button.active {
            background-color: var(--primary-color);
        }

        .product-carousel .carousel-control-prev,
        .product-carousel .carousel-control-next {
            width: 30px;
            height: 30px;
            background-color: rgba(0, 0, 0, 0.2);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: var(--transition);
        }

        .product-card:hover .product-carousel .carousel-control-prev,
        .product-card:hover .product-carousel .carousel-control-next {
            opacity: 1;
        }

        .product-carousel .carousel-control-prev {
            left: 10px;
        }

        .product-carousel .carousel-control-next {
            right: 10px;
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
            width: 60px;
            height: 60px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            font-size: 24px;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
        }

        .cart-icon:hover {
            background-color: var(--secondary-color);
            transform: scale(1.05);
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: var(--danger-color);
            color: white;
            font-size: 12px;
            font-weight: bold;
            border-radius: 50%;
            padding: 4px 8px;
            min-width: 24px;
            text-align: center;
        }

        /* Grid Layout */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 10px 0;
        }

        /* No Image Fallback */
        .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }

        /* Loading Animation */
        .loading-placeholder {
            animation: pulse 1.5s infinite;
            background-color: #e9ecef;
            border-radius: var(--border-radius);
        }

        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 0.3; }
            100% { opacity: 0.6; }
        }
    </style>
</head>
<body>
<div class="main-content">
    <!-- Floating Cart Icon -->
    <div class="cart-icon-container">
        <a href="cart.php" class="cart-icon">
            <i class="fa fa-shopping-cart"></i>
            <span class="cart-count"><?php echo $cartCount; ?></span>
        </a>
    </div>

    <div class="container py-4 py-md-5">
        <!-- Page Header -->
        <div class="page-header">
            <h4>Our Products</h4>
            <p class="text-muted">Browse our wide range of high-quality products</p>
        </div>

        <!-- Products Grid -->
        <div class="products-grid">
            <?php while ($product = $result->fetch_assoc()) { ?>
                <div class="product-card">
                    <div class="product-card-img">
                        <?php 
                        $images = $product['images'];
                        $imagesArray = json_decode($images, true);
                        if (is_array($imagesArray) && count($imagesArray) > 0) {
                        ?>
                        <div id="productCarousel<?php echo $product['id']; ?>" class="carousel slide product-carousel" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($imagesArray as $index => $img): ?>
                                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars($basePath . basename($img)); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                                             class="d-block w-100"
                                             onerror="this.onerror=null; this.src='../assets/no-image.jpg';">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (count($imagesArray) > 1): ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel<?php echo $product['id']; ?>" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#productCarousel<?php echo $product['id']; ?>" data-bs-slide="next">
                                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                                <div class="carousel-indicators">
                                    <?php foreach ($imagesArray as $index => $img): ?>
                                        <button type="button" data-bs-target="#productCarousel<?php echo $product['id']; ?>" 
                                                data-bs-slide-to="<?php echo $index; ?>" 
                                                class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php } else { ?>
                            <div class="no-image">
                                <span>No Image Available</span>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="product-card-body">
                        <h5 class="product-name">
                           <a href="view_product.php?id=<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                           </a>
                        </h5>
                        <div class="product-details">
                            <div><strong>Part No:</strong> <?php echo htmlspecialchars($product['partNo']); ?></div>
                            <div><strong>Model:</strong> <?php echo htmlspecialchars($product['model']); ?></div>
                            <div><strong>HSN No:</strong> <?php echo htmlspecialchars($product['hsnNo']); ?></div>
                        </div>
                        <a href="view_product.php?id=<?php echo $product['id']; ?>" class="btn btn-view mt-3">
                            <i class="fa fa-eye"></i> View Details
                        </a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Add smooth scrolling to all links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
</script>
</body>
</html>

<?php
$conn->close();
?>