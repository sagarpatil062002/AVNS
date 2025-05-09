<?php
include 'navbar.php';

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "sales_management";

$conn = new mysqli($host, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = "";
$errorMessage = "";
$quotation = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update quotation details
    $id = $_POST['id'];
    $productId = $_POST['productId'];
    $customerId = $_POST['customerId'];
    $quantity = $_POST['quantity'];
    $priceOffered = $_POST['priceOffered'];
    $status = $_POST['status'];

    $sql = "UPDATE Quotation SET productId = ?, customerId = ?, quantity = ?, priceOffered = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiidss", $productId, $customerId, $quantity, $priceOffered, $status, $id);

    if ($stmt->execute()) {
        $successMessage = "Quotation updated successfully!";
    } else {
        $errorMessage = "Error: " . $stmt->error;
    }
} else {
    // Ensure there's an 'id' in the URL
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = $_GET['id'];

        // Fetch the existing quotation details
        $sql = "SELECT * FROM Quotation WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $quotation = $result->fetch_assoc();

        // If no quotation was found, show an error
        if (!$quotation) {
            $errorMessage = "Quotation not found.";
        }
    } else {
        $errorMessage = "Invalid quotation ID.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Quotation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css"> <!-- Added unicons stylesheet -->
    <link rel="stylesheet" href="styles.css"> <!-- Added custom styles.css -->
    <style>
        /* Custom container style */
        .container {
            margin-left: 300px; /* Space for the sidebar */
            margin-top: 50px;
            width: 1200px;
            position: relative;
            word-spacing: 1px;
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?> <!-- Included the navbar -->

<div class="container">
    <header>
        <h2>Edit Quotation Request</h2>
    </header>

    <?php if ($successMessage) { ?>
        <div class="alert alert-success">
            <?php echo $successMessage; ?>
        </div>
    <?php } ?>

    <?php if ($errorMessage) { ?>
        <div class="alert alert-danger">
            <?php echo $errorMessage; ?>
        </div>
    <?php } ?>

    <?php if ($quotation) { ?>
        <form action="edit_quotation.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $quotation['id']; ?>">

            <div class="form-group">
                <label for="productId">Product</label>
                <input type="text" class="form-control" id="productId" name="productId" value="<?php echo $quotation['productId']; ?>" required>
            </div>

            <div class="form-group">
                <label for="customerId">Customer</label>
                <input type="text" class="form-control" id="customerId" name="customerId" value="<?php echo $quotation['customerId']; ?>" required>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input type="number" class="form-control" id="quantity" name="quantity" value="<?php echo $quotation['quantity']; ?>" required>
            </div>

            <div class="form-group">
                <label for="priceOffered">Price Offered</label>
                <input type="number" step="0.01" class="form-control" id="priceOffered" name="priceOffered" value="<?php echo $quotation['priceOffered']; ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="PENDING" <?php echo $quotation['status'] == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                    <option value="APPROVED" <?php echo $quotation['status'] == 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                    <option value="REJECTED" <?php echo $quotation['status'] == 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary">Update Quotation</button>
        </form>
    <?php } ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the connection
$conn->close();
?>
