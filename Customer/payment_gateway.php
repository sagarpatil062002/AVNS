<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
require_once 'config.php';

use Razorpay\Api\Api;

// Validate all required parameters
$required_params = ['plan_id', 'amount', 'tenure', 'customer_id', 'sectorId'];
foreach ($required_params as $param) {
    if (!isset($_GET[$param])) {
        die("Missing parameter: $param");
    }
}

// Sanitize inputs
$plan_id = intval($_GET['plan_id']);
$amount = floatval($_GET['amount']);
$tenure = intval($_GET['tenure']);
$customer_id = intval($_GET['customer_id']);
$sector_id = intval($_GET['sectorId']);

// Check if customer already has an active subscription
$checkSubscriptionQuery = "SELECT * FROM customer_subscription 
                         WHERE user_id = ? AND status = 'Approved' 
                         AND (end_date IS NULL OR end_date > NOW() ) AND isexpired=0 ";
$stmt = $conn->prepare($checkSubscriptionQuery);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$subscriptionResult = $stmt->get_result();

if ($subscriptionResult && $subscriptionResult->num_rows > 0) {
    // Customer already has an active subscription
    header("Location: customer_subscription.php?message=You+already+have+an+active+subscription");
    exit();
}
$stmt->close();

// Fetch customer details using correct column names
$customerQuery = "SELECT companyName, mailId, mobileNo FROM customerdistributor WHERE id = ?";
$stmt = $conn->prepare($customerQuery);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$customerResult = $stmt->get_result();

if ($customerResult->num_rows === 0) {
    die("Invalid customer");
}

$customer = $customerResult->fetch_assoc();
$stmt->close();

// Fetch plan details
$plan_query = "SELECT name FROM customer_plan WHERE id = ?";
$stmt = $conn->prepare($plan_query);
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$plan_result = $stmt->get_result();

if ($plan_result->num_rows === 0) {
    die("Invalid plan selected");
}

$plan = $plan_result->fetch_assoc();
$stmt->close();

// Initialize Razorpay
$razorpayKeyId = 'rzp_test_7ZhFXaT3z3ethj';
$razorpayKeySecret = 'hCRBftuV7yvlLwvMVeXAW4Fk';
$api = new Api($razorpayKeyId, $razorpayKeySecret);

try {
    // Create Razorpay order
    $order = $api->order->create([
        'receipt' => 'order_'.time(),
        'amount' => $amount * 100, // in paise
        'currency' => 'INR',
        'payment_capture' => 1
    ]);

    // Store data in session for verification
    $_SESSION['razorpay_order_id'] = $order->id;
    $_SESSION['payment_data'] = [
        'plan_id' => $plan_id,
        'amount' => $amount,
        'tenure' => $tenure,
        'customer_id' => $customer_id,
        'sector_id' => $sector_id
    ];

} catch (Exception $e) {
    die('Error creating Razorpay order: '.$e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Gateway</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-container text-center">
            <h2>Redirecting to Payment Gateway...</h2>
            <div class="spinner-border text-primary mt-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>

    <script>
    var options = {
        "key": "<?php echo $razorpayKeyId; ?>",
        "amount": "<?php echo $amount * 100; ?>", 
        "currency": "INR",
        "name": "<?php echo addslashes($customer['companyName']); ?>",
        "description": "<?php echo addslashes($plan['name']); ?> Plan (<?php echo $tenure; ?> Months)",
        "image": "/your_logo.png",
        "order_id": "<?php echo $order->id; ?>",
        "handler": function (response) {
            window.location.href = 'payment_verify.php?payment_id=' + response.razorpay_payment_id + 
                                  '&order_id=' + response.razorpay_order_id + 
                                  '&signature=' + response.razorpay_signature;
        },
        "prefill": {
            "name": "<?php echo addslashes($customer['companyName']); ?>",
            "email": "<?php echo addslashes($customer['mailId']); ?>",
            "contact": "<?php echo addslashes($customer['mobileNo']); ?>"
        },
        "theme": {
            "color": "#F37254"
        },
        "modal": {
            "ondismiss": function() {
                window.location.href = 'customer_subscription.php';
            }
        }
    };
    var rzp = new Razorpay(options);
    rzp.open();
    </script>
</body>
</html>