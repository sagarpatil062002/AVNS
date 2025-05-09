<?php
// Database connection
include('Config.php');


// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all products
$productResult = $conn->query("SELECT * FROM Product");

// Fetch all customers from CustomerDistributor
$customerResult = $conn->query("SELECT * FROM CustomerDistributor");

// Insert quotation request into the database
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $productId = $_POST['productId'];
    $customerId = $_POST['customerId']; // customerId from CustomerDistributor
    $quantity = $_POST['quantity']; // Get product quantity
    $priceOffered = $_POST['priceOffered'];
    $status = 'PENDING';  // Initial status

    // Validation
    if (!empty($productId) && !empty($customerId) && !empty($quantity) && !empty($priceOffered)) {
        $stmt = $conn->prepare("INSERT INTO Quotation (productId, customerId, quantity, priceOffered, status, createdAt) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiids", $productId, $customerId, $quantity, $priceOffered, $status);

        if ($stmt->execute()) {
            echo "Quotation request submitted successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "All fields are required.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Quotation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 600px; /* Center the form */
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 24px;
            color: #343a40;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .btn {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        @media (max-width: 576px) {
            .container {
                padding: 15px;
            }

            h2 {
                font-size: 20px;
            }

            .btn {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

    <div class="container mt-5">
        <h2>Request a Quotation</h2>
        <form method="post" action="">
            <!-- Product Selection -->
            <div class="form-group mb-3">
                <label for="productId">Select Product:</label>
                <select class="form-control" id="productId" name="productId" required>
                    <?php while ($product = $productResult->fetch_assoc()) { ?>
                        <option value="<?php echo $product['id']; ?>">
                            <?php echo htmlspecialchars($product['name'] . " (Part No: " . $product['partNo'] . ")"); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- Customer Selection -->
            <div class="form-group mb-3">
                <label for="customerId">Select Customer:</label>
                <select class="form-control" id="customerId" name="customerId" required>
                    <?php while ($customer = $customerResult->fetch_assoc()) { ?>
                        <option value="<?php echo $customer['id']; ?>">
                            <?php echo htmlspecialchars($customer['companyName']); ?>  <!-- Use companyName instead of name -->
                        </option>
                    <?php } ?>
                </select>
            </div>

            <!-- Quantity -->
            <div class="form-group mb-3">
                <label for="quantity">Quantity:</label>
                <input type="number" class="form-control" id="quantity" name="quantity" required>
            </div>

            <!-- Price Offered -->
            <div class="form-group mb-3">
                <label for="priceOffered">Price Offered:</label>
                <input type="number" step="0.01" class="form-control" id="priceOffered" name="priceOffered" required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">Submit Quotation Request</button>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>