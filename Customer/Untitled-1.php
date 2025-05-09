<?php
session_start();

// Database connection
include('Config.php');
include('CustomerNav.php');


// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logged-in customer ID from session
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Use user_id as customer_id
} else {
    die("No customer is logged in. Please log in.");
}
echo $customerId;

// Fetch customer data (assuming user_id is in session)
$sql = "SELECT companyName FROM customerdistributor WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();
$companyName = $customer['companyName']; // Store the company name

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch customer ID and subject from the form
    $customerId = $_POST['customer_id']; // Use the customer_id passed from the form
    $subject = $_POST['subject']; // Fetch the subject entered by the user
    $superAdminId = 1; // Replace with the actual super admin ID
    $status = 'PENDING';
    $quotationDate = date('Y-m-d H:i:s');
    
    // Insert the quotation record into the `quotation_header` table
    $sql = "INSERT INTO quotation_header (customerId, subject, status, createdAt, superAdminId) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssi", $customerId, $subject, $status, $quotationDate, $superAdminId);
    $stmt->execute();
    $quotationId = $stmt->insert_id; // Get the last inserted quotation ID

    // Loop through the cart and add products with quantity and target price to the `quotation_product` table
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $cartItem) {
            $productId = $cartItem['product_id'];
            $quantity = $_POST['quantity_' . $productId]; // Get quantity from the form
            $priceOffered = $_POST['target_price_' . $productId]; // Get target price from the form

            // Insert the product into `quotation_product` table
            $sql = "INSERT INTO quotation_product (quotation_id, productId, quantity, priceOffered, createdAt) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiids", $quotationId, $productId, $quantity, $priceOffered, $quotationDate);
            $stmt->execute();
        }
    }

    // Redirect to a confirmation page or back to the cart
    header("Location: view_Adminquotation.php");
    exit();
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request Quotation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">

    <style>
        body {
            background: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        .container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-top: 50px;
            width: 80%;
            margin-left:310px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center">Request Quotation</h2>

        <form method="POST" action="request_quotation.php">
            <div class="form-group">
                <label for="customer">Customer: </label>
                <select class="form-control" name="customer_id" required>
                    <!-- Displaying the company name here -->
                    <option value="<?= $customerId; ?>" selected><?= htmlspecialchars($companyName); ?></option>
                    <!-- Add more customer options if needed -->
                </select>
            </div>

            <!-- New Subject Field -->
            <div class="form-group">
                <label for="subject">Subject: </label>
                <input type="text" name="subject" class="form-control" placeholder="Enter subject" required>
            </div>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Target Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Check if the cart is not empty
                    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                        foreach ($_SESSION['cart'] as $cartItem) {
                            $productId = $cartItem['product_id'];

                            // Fetch product details from the database
                            $sql = "SELECT * FROM product WHERE id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $productId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $product = $result->fetch_assoc();

                            // Display product with input fields for quantity and target price
                            echo "<tr>";
                            echo "<td>" . $product['name'] . "</td>";
                            echo "<td><input type='number' name='quantity_" . $productId . "' class='form-control' min='1' placeholder='Quantity' required></td>";
                            echo "<td><input type='number' step='0.01' name='target_price_" . $productId . "' class='form-control' placeholder='Target Price' required></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3'>Your cart is empty.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <button type="submit" class="btn btn-primary">Submit Quotation Request</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
