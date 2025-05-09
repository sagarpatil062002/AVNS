<?php
// Database connection
include('Config.php');
include('CustomerNav.php');
session_start();

// Check if the customer is logged in
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
} else {
    die("No customer is logged in. Please log in.");
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch customer data to display name
$customerQuery = "SELECT companyName, mailId, mobileNo FROM customerdistributor WHERE id = '$customerId'";
$customerResult = $conn->query($customerQuery);
$customerName = "";
$customerEmail = "";
$customerMobile = "";
if ($customerResult && $customerResult->num_rows > 0) {
    $customerData = $customerResult->fetch_assoc();
    $customerName = $customerData['companyName'];
    $customerEmail = $customerData['mailId'];
    $customerMobile = $customerData['mobileNo'];
}

// Fetch orders with calculated amounts from quotation_product and include invoice_id if exists
$orderQuery = "
    SELECT 
        od.id AS orderId, 
        od.status, 
        od.quantity, 
        COALESCE(p.name, od.custom_product_name) AS productName,
        od.payment_status, 
        od.razorpay_order_id,
        tr.tax_percentage,
        tr.tax_name,
        ROUND((od.quantity * qp.priceOffered) * (1 + tr.tax_percentage / 100), 2) AS amount,
        qh.quotation_id,
        (
            SELECT ii.invoice_id 
            FROM invoice_items ii 
            WHERE ii.product_name = COALESCE(p.name, od.custom_product_name)
            AND ii.invoice_id IN (
                SELECT i.id 
                FROM invoices i 
                WHERE i.customer_id = od.customerId
            )
            ORDER BY ii.id DESC
            LIMIT 1
        ) AS invoice_id
    FROM order_details od
    LEFT JOIN product p ON od.productId = p.id
    LEFT JOIN quotation_product qp ON od.productId = qp.productId
    LEFT JOIN tax_rates tr ON qp.tax_rate_id = tr.id
    LEFT JOIN quotation_header qh ON qp.quotation_id = qh.quotation_id
    WHERE od.customerId = '$customerId' AND qh.customerId = '$customerId'
    GROUP BY od.id
    ORDER BY od.createdAt DESC
";

$orderResult = $conn->query($orderQuery);

// Include Razorpay PHP SDK
require __DIR__ . '/vendor/autoload.php';
use Razorpay\Api\Api;

// Replace with your actual Razorpay API keys
$razorpayKeyId = 'rzp_test_7ZhFXaT3z3ethj';
$razorpayKeySecret = 'hCRBftuV7yvlLwvMVeXAW4Fk';

$api = new Api($razorpayKeyId, $razorpayKeySecret);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Orders</title>
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
        
        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-left: 250px;
            transition: all 0.3s ease;
        }
        
        .orders-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.75rem;
            border-bottom: 1px solid var(--gray-light);
            padding-bottom: 1rem;
        }
        
        .welcome-text {
            text-align: center;
            color: var(--gray);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .table {
            margin-top: 1.5rem;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 0.35rem;
            overflow: hidden;
            box-shadow: 0 0 0.5rem rgba(0, 0, 0, 0.05);
        }
        
        .table thead th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem;
            text-align: center;
        }
        
        .table tbody tr {
            transition: all 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid var(--gray-light);
            text-align: center;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: rgba(246, 194, 62, 0.2);
            color: #b78a00;
        }
        
        .status-process {
            background-color: rgba(155, 89, 182, 0.2);
            color: #8e44ad;
        }
        
        .status-shipped {
            background-color: rgba(52, 152, 219, 0.2);
            color: #2980b9;
        }
        
        .status-delivered {
            background-color: rgba(28, 200, 138, 0.2);
            color: #0d8a5a;
        }
        
        .payment-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .payment-pending {
            background-color: rgba(246, 194, 62, 0.2);
            color: #b78a00;
        }
        
        .payment-paid {
            background-color: rgba(28, 200, 138, 0.2);
            color: #0d8a5a;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            font-size: 0.8rem;
            border-radius: 0.35rem;
            transition: all 0.2s ease;
        }
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-info {
            background-color: #36b9cc;
            border-color: #36b9cc;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
            font-size: 1.1rem;
        }
        
        .alert {
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .amount-cell {
            font-weight: 700;
            color: var(--primary);
        }
        
        @media (max-width: 992px) {
            .orders-container {
                margin-left: 0;
                margin-top: 1rem;
                padding: 1.5rem;
            }
            
            .orders-header {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-10 col-lg-12">
                <div class="orders-container">
                    <h1 class="orders-header">
                        <i class="fas fa-shopping-bag me-2"></i>Your Orders
                    </h1>
                    
                    <p class="welcome-text">
                        Welcome back, <strong><?php echo htmlspecialchars($customerName); ?></strong>
                    </p>

                    <?php if ($orderResult && $orderResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Product</th>
                                        <th>Status</th>
                                        <th>Quantity</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $orderResult->fetch_assoc()): 
                                        $amount = $order['amount'] ?? ($order['quantity'] * $order['unit_price']);
                                    ?>
                                        <tr>
                                            <td>#<?php echo $order['orderId']; ?></td>
                                            <td><?php echo htmlspecialchars($order['productName']); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', $order['status'])); ?>">
                                                    <?php echo htmlspecialchars(str_replace('_', ' ', $order['status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $order['quantity']; ?></td>
                                            <td class="amount-cell">₹<?php echo number_format($amount, 2); ?></td>
                                            <td>
                                                <span class="payment-badge payment-<?php echo strtolower($order['payment_status']); ?>">
                                                    <i class="fas <?php echo $order['payment_status'] == 'paid' ? 'fa-check-circle' : 'fa-clock'; ?> me-1"></i>
                                                    <?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($order['payment_status'] != 'paid' && $amount > 0): ?>
                                                        <button class="btn btn-success" onclick="initiatePayment(<?php echo $order['orderId']; ?>, <?php echo $amount * 100; ?>, <?php echo $order['quotation_id']; ?>)">
                                                            <i class="fas fa-rupee-sign me-1"></i> Pay Now
                                                        </button>
                                                    <?php endif; ?>
                                                    <?php if ($order['payment_status'] == 'paid' && isset($order['invoice_id'])): ?>
                                                        <a href="view_invoices.php?id=<?php echo $order['invoice_id']; ?>" class="btn btn-primary" target="_blank">
                                                            <i class="fas fa-file-invoice me-1"></i> Invoice
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-box-open fa-3x mb-3" style="color: var(--gray);"></i>
                            <h4>No Orders Found</h4>
                            <p>You haven't placed any orders yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Razorpay Payment Form -->
    <form id="razorpay-form" action="verify_payment.php" method="POST">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
        <input type="hidden" name="order_id" id="order_id">
        <input type="hidden" name="quotation_id" id="quotation_id">
    </form>

    <!-- Include Razorpay Checkout script -->
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    function initiatePayment(orderId, amount, quotationId) {
        // Show loading state
        const btn = event.target;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
        
        fetch('create_razorpay_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_id: orderId,
                amount: amount,
                quotation_id: quotationId
            })
        })
        .then(async response => {
            // First check if the response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Expected JSON but got: ${text.substring(0, 100)}...`);
            }
            return response.json();
        })
        .then(data => {
            if(data.error) {
                throw new Error(data.error);
            }

            var options = {
                "key": "<?php echo $razorpayKeyId; ?>",
                "amount": data.amount * 100, // Use the amount from server response
                "currency": "INR",
                "name": "<?php echo htmlspecialchars($customerName); ?>",
                "description": "Payment for Order #" + orderId,
                "image": "https://example.com/your_logo.png",
                "order_id": data.razorpay_order_id,
                "handler": function (response) {
                    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                    document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                    document.getElementById('razorpay_signature').value = response.razorpay_signature;
                    document.getElementById('order_id').value = orderId;
                    document.getElementById('quotation_id').value = quotationId;
                    document.getElementById('razorpay-form').submit();
                },
                "prefill": {
                    "name": "<?php echo htmlspecialchars($customerName); ?>",
                    "email": "<?php echo htmlspecialchars($customerEmail); ?>",
                    "contact": "<?php echo htmlspecialchars($customerMobile); ?>"
                },
                "notes": {
                    "order_id": orderId,
                    "quotation_id": quotationId
                },
                "theme": {
                    "color": "#4e73df"
                }
            };
            
            var rzp = new Razorpay(options);
            rzp.open();
        })
        .catch(error => {
            console.error('Payment Error:', error);
            alert('Payment Error: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-rupee-sign me-1"></i> Pay Now';
        });
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>