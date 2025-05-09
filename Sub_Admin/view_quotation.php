<?php
// Start the session
session_start();
include ('navbar.php');

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "sales_management";

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all quotations with customer and product details, grouped by quotation_id
$quotationsQuery = "
    SELECT q.quotation_id AS quotation_id, q.status, c.companyName AS customer_name, 
           p.name AS product_name, qp.quantity, qp.priceOffered
    FROM quotation_header q
    JOIN CustomerDistributor c ON q.customerId = c.id
    JOIN quotation_product qp ON q.quotation_id = qp.quotation_id
    JOIN Product p ON qp.productId = p.id
    ORDER BY q.createdAt DESC
";

$quotationsResult = $conn->query($quotationsQuery);
$quotations = [];
if ($quotationsResult && $quotationsResult->num_rows > 0) {
    while ($row = $quotationsResult->fetch_assoc()) {
        $quotations[$row['quotation_id']]['quotation_id'] = $row['quotation_id'];
        $quotations[$row['quotation_id']]['status'] = $row['status'];
        $quotations[$row['quotation_id']]['customer_name'] = $row['customer_name'];
        $quotations[$row['quotation_id']]['products'][] = [
            'product_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'priceOffered' => $row['priceOffered']
        ];
    }
}

// Handle status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $quotationId = $_POST['quotation_id'];
    $newStatus = $_POST['status'];

    // Update quotation status
    $stmt = $conn->prepare("UPDATE quotation_header SET status = ? WHERE quotation_id = ?");
    $stmt->bind_param("si", $newStatus, $quotationId);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Quotation status updated successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error updating quotation: " . $conn->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Quotations</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
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
            margin-right: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">View Quotations</h1>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Quotation ID</th>
                <th>Customer Name</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($quotations as $quotation): ?>
            <tr>
                <td><?= htmlspecialchars($quotation['quotation_id']); ?></td>
                <td><?= htmlspecialchars($quotation['customer_name']); ?></td>
                <td>
                    <form method="POST" action="">
                        <input type="hidden" name="quotation_id" value="<?= $quotation['quotation_id']; ?>">
                        <div class="form-group">
                            <select name="status" id="status_<?= $quotation['quotation_id']; ?>" class="form-control" required <?= $quotation['status'] == 'APPROVED' ? 'disabled' : ''; ?>>
                                <option value="PENDING" <?= $quotation['status'] == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                <option value="APPROVED" <?= $quotation['status'] == 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                                <option value="REJECTED" <?= $quotation['status'] == 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <?php if ($quotation['status'] != 'APPROVED'): ?>
                            <button type="submit" name="update_status" class="btn btn-primary btn-block mt-2">Update</button>
                        <?php endif; ?>
                    </form>
                </td>
                <td>
                    <?php if ($quotation['status'] == 'APPROVED'): ?>
                        <a href="download_customer_quotation.php?quotation_id=<?= $quotation['quotation_id']; ?>" class="btn btn-success btn-block mt-2">Download PDF</a>
                    <?php else: ?>
                        <a href="edit_quotation.php?quotation_id=<?= $quotation['quotation_id']; ?>" class="btn btn-warning btn-block mt-2">View Product & Edit</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</body>
</html>
