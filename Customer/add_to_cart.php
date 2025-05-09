<?php
session_start();
$productId = $_GET['id'];
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!in_array($productId, $_SESSION['cart'])) {
    $_SESSION['cart'][] = $productId;
}
header("Location: products.php"); // Redirect to the products page
exit();
?>
