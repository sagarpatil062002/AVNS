<?php
session_start(); // Start session management
include 'config.php'; // Include database connection
include 'CustomerNav.php';
$res=true;

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logged-in customer ID from session
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
    $sectorId = $_SESSION['sector_id']; // Taking out sector ID
} else {
    die("No customer is logged in. Please log in.");
}

if (!$sectorId) {
    die("Unable to determine your sector. Please contact support.");
}



// Check if the customer's subscription has expired
$expiredSubscriptionQuery = "SELECT * FROM customer_subscription WHERE user_id = ? AND status = 'Approved' AND isexpired = 1";
$stmt = $conn->prepare($expiredSubscriptionQuery);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$expiredResult = $stmt->get_result();

$showReminder = false;
if ($expiredResult && $expiredResult->num_rows > 0) {
    $showReminder = true;
}
$stmt->close();

// Query to fetch the subscription plans available for the sector
$query = "SELECT id, name, base_price, max_support_calls, rewards_points, most_popular FROM customer_plan WHERE sectorName = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sectorId);
$stmt->execute();
$result = $stmt->get_result();

$plans = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $plans[] = $row;
    }
} else {
    echo "No subscription plans available for your sector.";
    exit;
}
$stmt->close();

// Query to fetch max_support_calls and subscription id based on relationships
$maxSupportCallsQuery = "SELECT cp.max_support_calls, cs.id FROM customer_subscription cs
                        INNER JOIN customer_plan cp ON cs.plan_id = cp.id
                        INNER JOIN customerdistributor cd ON cs.user_id = cd.id
                        WHERE cd.sectorId = cp.sectorName AND cs.user_id = ?";
$stmt = $conn->prepare($maxSupportCallsQuery);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$maxSupportCallsResult = $stmt->get_result();

$maxSupportCalls = [];
if ($maxSupportCallsResult && $maxSupportCallsResult->num_rows > 0) {
    while ($row = $maxSupportCallsResult->fetch_assoc()) {
        $subscriptionId = $row['id'];
        $maxCalls = $row['max_support_calls'];
        $maxSupportCalls[] = $maxCalls;

        $updateRemainingCallsQuery = "UPDATE customer_subscription SET remaining_calls = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateRemainingCallsQuery);
        $updateStmt->bind_param("ii", $maxCalls, $subscriptionId);
        $updateStmt->execute();
        $updateStmt->close();
    }
}
$stmt->close(); 

// Random colors for plans
$planColors = ['#e0eafc', '#ffdb58', '#7f00ff', '#32cd32', '#ff6347'];
shuffle($planColors);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sector Specific Subscription Plans</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="style.css">
<style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --secondary: #f8f9fc;
            --success: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --gray: #858796;
            --gray-light: #dddfeb;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--dark);
        }
        
        .plans-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-left: 350px;
            
        }
        
        .plans-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.75rem;
            border-bottom: 1px solid var(--gray-light);
            padding-bottom: 1rem;
        }
        
        .plan-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 0.5rem;
            overflow: hidden;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(58, 59, 69, 0.2);
        }
        
        .popular-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: linear-gradient(45deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0 0 0 0.5rem;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .card-title {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 1rem;
        }
        
        .pricing-header {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }
        
        .plan-feature {
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .plan-feature strong {
            color: var(--dark);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .form-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: 0.35rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            margin-bottom: 1.5rem;
        }
        
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.875rem;
            width: 100%;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .alert {
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: rgba(28, 200, 138, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-danger {
            background-color: rgba(231, 74, 59, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }
        
        .reminder {
            background-color: var(--danger);
            color: white;
            padding: 1rem;
            border-radius: 0.35rem;
            text-align: center;
            font-weight: 600;
            margin-bottom: 2rem;
            box-shadow: 0 0.15rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        
        .plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .plans-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .plans-header {
                font-size: 1.5rem;
            }
            
            .plan-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<?php
                                       // Assuming $customerId is the ID of the customer you want to check
$customerId = $_SESSION['user_id']; // Example, you can get this from session or request

// Step 1: Check if the user exists in the database
// $checkUserQuery = "SELECT * FROM customers WHERE id = ?";
// $stmt = $conn->prepare($checkUserQuery);
// $stmt->bind_param("i", $customerId);
// $stmt->execute();
// $userResult = $stmt->get_result();

if ($customerId >= 0) {
    // Customer exists, proceed to check subscription
    // Step 2: Check if the customer has an active subscription
    $checkSubscriptionQuery = "SELECT * FROM customer_subscription WHERE user_id = ? AND isexpired = 0";
    $stmt = $conn->prepare($checkSubscriptionQuery);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $subscriptionResult = $stmt->get_result();

    if ($subscriptionResult && $subscriptionResult->num_rows > 0) {
        // Customer has an active subscription, stop execution
        // echo "You already have an active subscription.";
        $res=false;
        // exit; // Stop further processing
    }
    $stmt->close();
}
                                       
                                       ?>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="plans-container">
                    <h1 class="plans-header">
                        <i class="fas fa-crown me-2"></i>Sector Specific Subscription Plans
                    </h1>

                    <!-- ↓ INSERT THIS BLOCK ↓ -->
  <?php if (!$res): ?>
    <div id="activeAlert" class="alert alert-warning text-center" role="alert">
      <i class="fas fa-exclamation-triangle me-2"></i>
      You already have an active subscription.
    </div>
  <?php endif; ?>
  <!-- ↑ END INSERTION ↑ -->
                    
                    
                    <?php if ($showReminder): ?>
                        <div id="reminder" class="reminder">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Your subscription has expired. Please renew it to continue enjoying our services.
                        </div>
                    <?php endif; ?>
                    
 
                   




                    <div class="plan-grid">
                        <?php foreach ($plans as $index => $plan): ?>
                            <div class="card plan-card" style="background-color: <?php echo $planColors[$index % count($planColors)]; ?>">
                                <?php if ($plan['most_popular']): ?>
                                    <div class="popular-badge">Most Popular</div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h3 class="card-title text-center"><?php echo htmlspecialchars($plan['name']); ?></h3>
                                    <div class="pricing-header text-center" id="price-display-<?php echo $plan['id']; ?>">
                                        ₹<?php echo number_format($plan['base_price'], 2); ?>
                                    </div>
                                    
                                    <div class="plan-features mb-4">
                                        <p class="plan-feature">
                                            <strong><i class="fas fa-phone-alt me-2"></i>Support Calls:</strong> 
                                            <span id="support-calls-<?php echo $plan['id']; ?>" data-original="<?php echo $plan['max_support_calls']; ?>">
                                                <?php echo $plan['max_support_calls']; ?>
                                            </span>
                                        </p>
                                        <p class="plan-feature">
                                            <strong><i class="fas fa-star me-2"></i>Reward Points:</strong> 
                                            <span id="reward-points-<?php echo $plan['id']; ?>" data-original="<?php echo $plan['rewards_points']; ?>">
                                                <?php echo $plan['rewards_points']; ?>
                                            </span>
                                        </p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tenure-<?php echo $plan['id']; ?>" class="form-label">Select Tenure:</label>
                                        <select class="form-select" id="tenure-<?php echo $plan['id']; ?>">
                                            <option value="1">1 Month</option>
                                            <option value="3">3 Months</option>
                                            <option value="6">6 Months</option>
                                            <option value="12">12 Months</option>
                                        </select>
                                    </div>

                                           
                                   



                                    <form action="payment_gateway.php" method="GET" class="purchase-form" id="form-<?php echo $plan['id']; ?>">
                                        <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                        <input type="hidden" id="base-price-<?php echo $plan['id']; ?>" name="base_price" value="<?php echo $plan['base_price']; ?>">
                                        <input type="hidden" id="amount-<?php echo $plan['id']; ?>" name="amount" value="<?php echo $plan['base_price']; ?>">
                                        <input type="hidden" name="sectorId" value="<?php echo $sectorId; ?>">
                                        <input type="hidden" id="tenure-hidden-<?php echo $plan['id']; ?>" name="tenure" value="1">
                                        <input type="hidden" name="customer_id" value="<?php echo $customerId; ?>">
                                        <button type="submit" class="btn btn-primary" <?php echo ($res == false) ? 'disabled' : ''; ?>>
                                            <i class="fas fa-shopping-cart me-2"></i>Buy Now
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updatePlanDetails(basePrice, tenureId, priceDisplayId, planId) {
            const tenure = document.getElementById(tenureId).value;
            const totalPrice = basePrice * tenure;
            
            // Update displayed price
            document.getElementById(priceDisplayId).innerText = "₹" + totalPrice.toLocaleString();
            
            // Update hidden form fields
            document.getElementById('amount-' + planId).value = totalPrice;
            document.getElementById('tenure-hidden-' + planId).value = tenure;
            
            // Update support calls and reward points
            const originalCalls = parseInt(document.getElementById(`support-calls-${planId}`).dataset.original);
            const originalPoints = parseInt(document.getElementById(`reward-points-${planId}`).dataset.original);
            document.getElementById(`support-calls-${planId}`).innerText = originalCalls * tenure;
            document.getElementById(`reward-points-${planId}`).innerText = originalPoints * tenure;
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.form-select').forEach(function(selectElement) {
                const planId = selectElement.id.split('-')[1];
                const basePrice = parseFloat(document.getElementById('base-price-' + planId).value);
                
                // Initialize with default values
                updatePlanDetails(basePrice, selectElement.id, `price-display-${planId}`, planId);
                
                // Add change event listener
                selectElement.addEventListener('change', function() {
                    updatePlanDetails(basePrice, selectElement.id, `price-display-${planId}`, planId);
                });
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
    // Check if the reminder element exists
    const reminder = document.getElementById('reminder');
    if (reminder) {
        // Hide the reminder after 3 seconds
        setTimeout(function() {
            reminder.style.display = 'none';
        }, 3000); // 3000 milliseconds = 3 seconds
    }
});
    </script>

    <script>
        
document.addEventListener('DOMContentLoaded', function() {
  const alertBox = document.getElementById('activeAlert');
  if (alertBox) {
    setTimeout(() => {
      // Option 1: Fade it out by adding Bootstrap’s fade class, then remove
      alertBox.classList.add('fade');
      alertBox.classList.remove('show');
      setTimeout(() => alertBox.remove(), 150); // remove after fade completes
      
      // —OR—
      // Option 2: Just remove it immediately:
      // alertBox.remove();
    }, 3000);
  }
});


    </script>



    
</body>
</html>