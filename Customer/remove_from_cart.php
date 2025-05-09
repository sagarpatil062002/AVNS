<?php
session_start();
include('Config.php');

// Check if the product ID is provided in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $productId = $_GET['id'];

    // Check if the cart exists in the session and is not empty
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        // Loop through the cart items to find the matching product ID
        foreach ($_SESSION['cart'] as $key => $cartItem) {
            if ($cartItem['product_id'] == $productId) {
                // Remove the product from the cart
                unset($_SESSION['cart'][$key]);
                // Re-index the array to prevent gaps in the keys
                $_SESSION['cart'] = array_values($_SESSION['cart']);
                break;
            }
        }
    }
}

// Redirect back to the cart page
header("Location: cart.php");
exit();
?>
