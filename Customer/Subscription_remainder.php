<?php
include 'config.php'; // Include database connection

// Current date
$currentDate = date('Y-m-d');

// Query to find subscriptions nearing expiry (e.g., within 7 days)
$query = "
    SELECT cs.id, cs.user_id, u.email, cs.subscription_id, cp.name AS plan_name, cs.expiry_date
    FROM customer_subscription cs
    INNER JOIN users u ON cs.user_id = u.id
    INNER JOIN customer_plan cp ON cs.subscription_id = cp.id
    WHERE DATEDIFF(cs.expiry_date, '$currentDate') <= 7 AND DATEDIFF(cs.expiry_date, '$currentDate') > 0
";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $userId = $row['user_id'];
        $userEmail = $row['email'];
        $planName = $row['plan_name'];
        $expiryDate = $row['expiry_date'];

        // Send reminder email
        $subject = "Subscription Expiry Reminder";
        $message = "
            Dear User,

            This is a reminder that your subscription for the plan '$planName' will expire on $expiryDate.

            Please renew your subscription to continue enjoying our services.

            Best regards,
            Your Company Name
        ";

        // Uncomment in production
        // mail($userEmail, $subject, $message);

        echo "Reminder sent to $userEmail for plan '$planName' expiring on $expiryDate.<br>";
    }
} else {
    echo "No subscriptions nearing expiry.";
}

// Optional: Update expired subscriptions
$updateQuery = "
    UPDATE customer_subscription
    SET status = 'expired'
    WHERE expiry_date < '$currentDate' AND status = 'active'
";
mysqli_query($conn, $updateQuery);
?>
