<?php
session_start();
include 'config.php';

// Get plan ID and duration from URL
$planId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$currentDuration = isset($_GET['duration']) ? $_GET['duration'] : 'monthly';

// Fetch plan details from the database
$query = "SELECT id, name, base_price, max_support_calls, rewards_points FROM subscriptions WHERE id = $planId";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $plan = mysqli_fetch_assoc($result);
    $durations = [
        'monthly' => 1,
        'quarterly' => 3,
        'halfyearly' => 6,
        'yearly' => 12
    ];
    $durationMonths = $durations[$currentDuration];
    $finalPriceINR = $plan['base_price'] * $durationMonths;
    $finalRewards = $plan['rewards_points'] * $durationMonths;
    $finalSupportCalls = $plan['max_support_calls'] * $durationMonths;
} else {
    die("Invalid plan selected.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f0f4f8; font-family: 'Arial', sans-serif; }
        h1 { font-family: 'Poppins', sans-serif; font-size: 3rem; color: #333; }
        .card { transition: transform 0.4s ease, box-shadow 0.4s ease; border: none; border-radius: 15px; overflow: hidden; position: relative; }
        .card:hover { transform: translateY(-10px); box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2); }
        .pricing-header { font-size: 2.5rem; font-weight: bold; }
        .btn-custom { background-color: #fff; color: #333; border: 2px solid #fff; font-weight: bold; transition: all 0.3s ease; }
        .btn-custom:hover { background-color: #333; color: #fff; }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Plan Details</h1>
        </div>

        <div class="card mx-auto" style="width: 50%; background-color: #e0eafc;">
            <div class="card-body">
                <h3 class="card-title text-center"><?php echo htmlspecialchars($plan['name']); ?></h3>
                <p class="text-center"><strong>Duration:</strong> <?php echo ucfirst($currentDuration); ?> (<?php echo $durationMonths; ?> months)</p>
                <div class="pricing-header text-center mb-3">
                    ₹<?php echo number_format($finalPriceINR); ?>
                </div>
                <p><strong>Reward Points:</strong> <?php echo $finalRewards; ?></p>
                <p><strong>Support Calls:</strong> <?php echo $finalSupportCalls; ?></p>
                <a href="purchase.php?id=<?php echo $plan['id']; ?>&duration=<?php echo $currentDuration; ?>" class="btn btn-custom w-100">Purchase</a>
            </div>
        </div>
    </div>
</body>
</html>
