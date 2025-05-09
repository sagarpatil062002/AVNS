<?php
session_start();
include('config.php');
 // Include the database connection
 include('navbar.php');
// Fetch all subscriptions with transaction_id and QR code path
$sql = "
    SELECT ad.subscription_id, ad.qr_code_path, ad.transaction_id, ad.plan_name, ad.approval_status
    FROM admin_data ad
    WHERE ad.transaction_id IS NOT NULL AND ad.qr_code_path IS NOT NULL
    ORDER BY ad.subscription_id DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Subscriptions</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">

    <style>
        body { background-color: #f8f9fa; font-family: 'Arial', sans-serif; }
        h1 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; margin-top: 20px; background-color: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); }
        th, td { padding: 10px; text-align: center; border: 1px solid #ddd; font-size: 0.9rem; }
        th { background-color: #f7f9fc; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        img { max-width: 80px; border-radius: 5px; }
        .btn { font-size: 0.8rem; margin: 2px; }
        
        .container{            max-height: auto; /* Fixed height for the table body */
        overflow-y: auto; /* Enable vertical scrolling */
        margin-left: 250px; /* Space for the sidebar */
        width: 1200px;
        position: relative;
        word-spacing: 1px } 
    </style>
</head>
<body>
    <div class="container my-5">
        <h1>Subscription Details</h1>
        
        <?php if ($result->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10%;">Subscription ID</th>
                        <th style="width: 15%;">Plan Name</th>
                        <th style="width: 15%;">Transaction ID</th>
                        <th style="width: 20%;">QR Code</th>
                        <th style="width: 15%;">Approval Status</th>
                        <th style="width: 25%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['subscription_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['plan_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['transaction_id']); ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($row['qr_code_path']); ?>" target="_blank">
                                    <img src="<?php echo htmlspecialchars($row['qr_code_path']); ?>" alt="QR Code">
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($row['approval_status']); ?></td>
                            <td>
                                <?php if ($row['approval_status'] === 'pending' or $row['approval_status'] === 'rejected'): ?>
                                    <a href="approve_subscription.php?subscription_id=<?php echo $row['subscription_id']; ?>" class="btn btn-success btn-sm">Approve</a>
                                    <a href="reject_subscription.php?subscription_id=<?php echo $row['subscription_id']; ?>" class="btn btn-danger btn-sm">Reject</a>
                                <?php else: ?>
                                    <span class="text-muted">No actions available</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No subscriptions found with a transaction ID and QR code.</div>
        <?php endif; ?>

        <?php $conn->close(); ?>
    </div>
</body>
</html>