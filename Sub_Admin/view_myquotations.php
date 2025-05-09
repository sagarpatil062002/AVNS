<?php
// Start session to retrieve success or error messages
session_start();

// Ensure no output has been sent before this point
ob_start();

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

// Ensure user is logged in
if (isset($_SESSION['user_id'])) {
    $superAdminId = $_SESSION['user_id'];

    // Fetch the quotations created by the logged-in Super Admin
    $sql = "SELECT 
                qh.quotation_id, 
                qh.status, 
                qh.createdAt, 
                qh.updatedAt, 
                qh.subject, 
                d.companyName AS distributor_name
            FROM quotation_header qh
            JOIN distributor d ON qh.distributorId = d.id
            WHERE qh.superAdminId = ?
            GROUP BY qh.quotation_id";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $superAdminId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Group quotations by quotation_id
    $groupedQuotations = [];
    while ($quotation = $result->fetch_assoc()) {
        $quotation_id = $quotation['quotation_id'];
        if (!isset($groupedQuotations[$quotation_id])) {
            $groupedQuotations[$quotation_id] = [
                'quotation_id' => $quotation['quotation_id'],
                'subject' => $quotation['subject'],
                'distributor_name' => $quotation['distributor_name'],
                'status' => $quotation['status'],
                'createdAt' => $quotation['createdAt'],
                'updatedAt' => $quotation['updatedAt'],
                'products' => []
            ];
        }
    }

    // Handle status update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
        $quotationId = $_POST['quotation_id'];
        $newStatus = $_POST['status'];

        // Update the quotation status in the database
        $updateSql = "UPDATE quotation_header SET status = ? WHERE quotation_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $newStatus, $quotationId);
        if ($updateStmt->execute()) {
            $_SESSION['success_message'] = "Status updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update status.";
        }
        // Redirect to reload page and show updated status
        header("Location: " . $_SERVER['PHP_SELF']); 
        exit(); // Make sure no further code is executed after header()
    }
} else {
    $message = "You must be logged in to view your quotations!";
}

// Retrieve success or error messages
$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;

// Clear messages after displaying them
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quotations</title>
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
            position: absolute;
            margin-left: 360px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .btn-group {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>My Quotations</h2>

    <?php if (isset($message)): ?>
        <div class="alert alert-warning"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?php echo $successMessage; ?></div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <?php if (isset($groupedQuotations) && count($groupedQuotations) > 0): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Quotation ID</th>
                    <th>Distributor Name</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Updated At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($groupedQuotations as $quotation): ?>
                    <tr>
                        <td><?php echo $quotation['quotation_id']; ?></td>
                        <td><?php echo $quotation['distributor_name']; ?></td>
                        <td><?php echo $quotation['subject']; ?></td>
                        <td><?php echo $quotation['status']; ?></td>
                        <td><?php echo $quotation['createdAt']; ?></td>
                        <td><?php echo $quotation['updatedAt']; ?></td>
                        <td>
                            <div class="btn-group">
                                <?php if ($quotation['status'] == 'APPROVED'): ?>
                                    <form action="download_quotation.php" method="GET" style="display:inline;">
                                    <input type="hidden" name="quotation_id" value="<?= htmlspecialchars($quotation['quotation_id']); ?>">
                                    <button type="submit" name="download_pdf" class="btn btn-danger action-btn">Download PDF</button>
                                </form>  <?php else: ?>
                                    <form method="POST" action="" class="d-inline-block">
                                        <input type="hidden" name="quotation_id" value="<?php echo $quotation['quotation_id']; ?>">
                                        <select name="status" class="form-control" required>
                                            <option value="PENDING" <?php echo $quotation['status'] == 'PENDING' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="APPROVED" <?php echo $quotation['status'] == 'APPROVED' ? 'selected' : ''; ?>>Approved</option>
                                            <option value="REJECTED" <?php echo $quotation['status'] == 'REJECTED' ? 'selected' : ''; ?>>Rejected</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-primary btn-sm mt-2">Update Status</button>
                                    </form>
                                    <form method="GET" action="edit_my_quotation.php" class="d-inline-block">
                                        <input type="hidden" name="quotation_id" value="<?php echo $quotation['quotation_id']; ?>">
                                        <button type="submit" class="btn btn-info btn-sm">Edit</button>
                                    </form>
                                <?php endif; ?>
                                <!-- View Products Button -->
                                <form method="GET" action="view_quotation_products.php" class="d-inline-block">
                                    <input type="hidden" name="quotation_id" value="<?php echo $quotation['quotation_id']; ?>">
                                    <button type="submit" class="btn btn-warning btn-sm">View Products</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="alert alert-warning">No quotations found.</p>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

</body>
</html>
