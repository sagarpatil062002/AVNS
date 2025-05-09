<?php
session_start();
include 'config.php';

// Centralized configuration: Define pricing details without discount
$durations = [
    'monthly' => 1,
    'quarterly' => 3,
    'halfyearly' => 6,
    'yearly' => 12
];

$currentDuration = isset($_GET['duration']) ? $_GET['duration'] : 'monthly'; // Default to 'monthly'

// Fetch subscription plans from the database
$query = "SELECT id, name, base_price, most_popular, max_support_calls, rewards_points FROM subscriptions";
$result = mysqli_query($conn, $query);

$plans = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $plans[] = $row;
    }
} else {
    echo "Error fetching plans: " . mysqli_error($conn);
}

// Random colors for plans
$planColors = ['#e0eafc', '#ffdb58', '#7f00ff', '#32cd32', '#ff6347'];
shuffle($planColors); // Shuffle the color array to apply random colors
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Pricing Plans</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f0f4f8; font-family: 'Arial', sans-serif; }
        h1 { font-family: 'Poppins', sans-serif; font-size: 3rem; color: #333; }
        .card { transition: transform 0.4s ease, box-shadow 0.4s ease; border: none; border-radius: 15px; overflow: hidden; position: relative; }
        .card:hover { transform: translateY(-10px); box-shadow: 0 15px 25px rgba(0, 0, 0, 0.2); }
        .popular-badge { position: absolute; top: 0; left: 0; background: linear-gradient(45deg, #ff416c, #ff4b2b); color: #fff; padding: 0.5rem 1.5rem; border-radius: 0 0 12px 0; font-size: 0.9rem; font-weight: bold; z-index: 10; }
        .pricing-header { font-size: 2.5rem; font-weight: bold; }
        .btn-custom { background-color: #fff; color: #333; border: 2px solid #fff; font-weight: bold; transition: all 0.3s ease; }
        .btn-custom:hover { background-color: #333; color: #fff; }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Choose Your Subscription Plan</h1>
        </div>

        <!-- Dropdown to select the duration -->
        <form method="GET" action="">
            <div class="mb-4 text-center">
                <label for="duration" class="form-label">Select Duration</label>
                <select name="duration" id="duration" class="form-select w-25 mx-auto" onchange="this.form.submit()">
                    <?php foreach ($durations as $key => $months): ?>
                        <option value="<?php echo $key; ?>" <?php echo $currentDuration == $key ? 'selected' : ''; ?>>
                            <?php echo ucfirst($key); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <div class="row justify-content-center g-4">
            <?php foreach ($plans as $index => $plan): ?>
                <?php 
                    $durationMonths = $durations[$currentDuration];
                    $finalPriceINR = $plan['base_price'] * $durationMonths;
                    $finalRewards = $plan['rewards_points'] * $durationMonths;
                    $finalSupportCalls = $plan['max_support_calls'] * $durationMonths;
                ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card shadow" style="background-color: <?php echo $planColors[$index % count($planColors)]; ?>;">
                        <?php if ($plan['most_popular']): ?>
                            <div class="popular-badge">Most Popular</div>
                        <?php endif; ?>
                        <div class="card-body text-center">
                            <h3 class="card-title fw-bold"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <div class="pricing-header mb-3">
                                ₹<?php echo number_format($finalPriceINR); ?> for <?php echo $durationMonths; ?> month(s)
                            </div>
                            <p><strong>Reward Points:</strong> <?php echo $finalRewards; ?></p>
                            <p><strong>Support Calls:</strong> <?php echo $finalSupportCalls; ?></p>
                            <a href="plan_details.php?id=<?php echo $plan['id']; ?>&duration=<?php echo $currentDuration; ?>" class="btn btn-custom w-100">Choose Plan</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
