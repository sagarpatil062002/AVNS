<?php
// Start session to access distributor ID
session_start();

// Database connection
$host = 'localhost';
$dbname = 'sales_management';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in and session is set
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to view the purchase details.");
}

// Fetch purchase details
$distributor_id = $_SESSION['user_id']; // Use distributor ID from session
$purchasesQuery = "
    SELECT pd.id, pd.super_admin_id, u.name AS super_admin_name, pd.total_amount, pd.total_tax, pd.created_at
    FROM purchase_details pd
    JOIN users u ON pd.super_admin_id = u.id
    WHERE pd.distributor_id = '$distributor_id'
    ORDER BY pd.created_at DESC
";
$purchasesResult = $conn->query($purchasesQuery);

// Prepare an array to store purchase data
$purchases = [];
if ($purchasesResult && $purchasesResult->num_rows > 0) {
    while ($row = $purchasesResult->fetch_assoc()) {
        $purchases[$row['id']] = [
            'id' => $row['id'],
            'super_admin_name' => $row['super_admin_name'],
            'total_amount' => $row['total_amount'],
            'total_tax' => $row['total_tax'],
            'created_at' => $row['created_at'],
            'items' => [],
        ];
    }
}

// Fetch purchase items
if (!empty($purchases)) {
    $purchaseIds = implode(',', array_keys($purchases));
    $itemsQuery = "
        SELECT pi.purchase_id, pi.product_name, pi.quantity, pi.price, pi.total, pi.tax, pi.tax_name
        FROM purchase_items pi
        WHERE pi.purchase_id IN ($purchaseIds)
    ";
    $itemsResult = $conn->query($itemsQuery);

    if ($itemsResult && $itemsResult->num_rows > 0) {
        while ($item = $itemsResult->fetch_assoc()) {
            $purchases[$item['purchase_id']]['items'][] = $item;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Purchases</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <style>
    body {
        background: #f8f9fa;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .container {
        background: #ffffff;
        padding: 20px 30px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        margin-top: 40px;
    }
    h1 {
        text-align: center;
        color: #333;
        font-size: 1.8em;
        margin-bottom: 20px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    thead {
        background-color: #007bff;
        color: #ffffff;
    }
    th, td {
        text-align: left;
        padding: 12px 15px;
        border: 1px solid #ddd;
        vertical-align: middle;
    }
    th {
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    tbody tr:hover {
        background-color: #f1f1f1;
    }
    tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .btn-primary {
        background-color: #007bff;
        color: #ffffff;
        border: none;
        border-radius: 5px;
        padding: 6px 12px;
        font-size: 0.9em;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }
    .btn-primary:hover {
        background-color: #0056b3;
    }
    .details {
        width: 90%;
        margin: 10px auto;
        border: none;
    }
    .details th {
        background-color: #f8f9fa;
        font-weight: 500;
        color: #555;
        border: none;
    }
    .details td {
        border: none;
        padding: 6px;
    }
    .details tbody tr:hover {
        background-color: #f1f1f1;
    }
    .no-data {
        text-align: center;
        color: #888;
        margin-top: 20px;
        font-size: 1.2em;
    }
    .no-data p {
        margin: 0;
    }
</style>

</head>
<body>
    <div class="container">
        <h1>View Purchases</h1>

        <?php if (!empty($purchases)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Purchase ID</th>
                        <th>Super Admin</th>
                        <th>Total Amount</th>
                        <th>Total Tax</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($purchases as $purchase): ?>
                        <tr>
                            <td><?= htmlspecialchars($purchase['id']); ?></td>
                            <td><?= htmlspecialchars($purchase['super_admin_name']); ?></td>
                            <td><?= number_format($purchase['total_amount'], 2); ?></td>
                            <td><?= number_format($purchase['total_tax'], 2); ?></td>
                            <td><?= htmlspecialchars($purchase['created_at']); ?></td>
                            <td>
                                <a href="download_pdf.php?purchase_id=<?= $purchase['id']; ?>" class="btn btn-primary">Download PDF</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <p>No purchase records found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
