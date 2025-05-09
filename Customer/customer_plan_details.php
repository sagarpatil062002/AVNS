<?php
include 'config.php'; // Include database connection

// Check if the plan ID is provided in the URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $planId = $_GET['id'];

    // Query to fetch the specific plan details
    $query = "
    SELECT id, name, base_price, max_support_calls, rewards_points, most_popular
    FROM customer_plan
    WHERE id = '$planId'
";

    $result = mysqli_query($conn, $query);

    // Check if a plan was found
    if ($result && mysqli_num_rows($result) > 0) {
        $plan = mysqli_fetch_assoc($result);
    } else {
        echo "Plan not found.";
        exit;
    }
} else {
    echo "No plan selected.";
    exit;
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
        .card { border: none; border-radius: 15px; overflow: hidden; box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1); }
        .pricing-header { font-size: 2.5rem; font-weight: bold; }
        .btn-custom { background-color: #fff; color: #333; border: 2px solid #fff; font-weight: bold; transition: all 0.3s ease; }
        .btn-custom:hover { background-color: #333; color: #fff; }
        h1 { font-family: 'Poppins', sans-serif; font-size: 3rem; color: #333; }
    </style>
    <script>
        function updatePrice(basePrice) {
            const tenure = document.getElementById('tenure').value;
            const totalPrice = basePrice * tenure;
            document.getElementById('adjusted-price').textContent = `₹${totalPrice.toLocaleString()}`;
        }
    </script>
</head>
<body>
    <div class="container my-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold">Plan Details</h1>
        </div>

        <div class="card shadow-lg mx-auto" style="max-width: 800px;">
            <div class="card-body text-center">
                <h3 class="card-title fw-bold"><?php echo htmlspecialchars($plan['name']); ?></h3>
                <div class="pricing-header mb-3">
                    Base Price: ₹<?php echo number_format($plan['base_price']); ?>
                </div>
                <p><strong>Support Calls:</strong> <?php echo $plan['max_support_calls']; ?></p>
                <p><strong>Reward Points:</strong> <?php echo $plan['rewards_points']; ?></p>
                
                <?php if ($plan['most_popular']): ?>
                    <div class="badge bg-danger text-white mb-3">Most Popular</div>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="tenure" class="form-label"><strong>Select Tenure:</strong></label>
                    <select id="tenure" class="form-select w-50 mx-auto" onchange="updatePrice(<?php echo $plan['base_price']; ?>)">
                        <option value="1">1 Month</option>
                        <option value="3">3 Months</option>
                        <option value="6">6 Months</option>
                        <option value="12">12 Months</option>
                    </select>
                </div>

                <div class="pricing-header mb-3">
                    Adjusted Price: <span id="adjusted-price">₹<?php echo number_format($plan['base_price']); ?></span>
                </div>

                <a href="subscribe.php?id=<?php echo $plan['id']; ?>" class="btn btn-custom">Purchase Now</a>
            </div>
        </div>
    </div>
</body>
</html>
