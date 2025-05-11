<?php
// Start session at the very beginning
session_start();

require_once('Config.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// Fetch order details
$orderQuery = "SELECT * FROM order_details WHERE id = ? AND customerId = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows === 0) {
    die("Order not found or access denied");
}

$order = $orderResult->fetch_assoc();

// Now include the nav after all PHP processing is done
include('CustomerNav.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful | <?php echo htmlspecialchars($order['id']); ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --success-color: #28a745;
            --primary-color: #007bff;
            --light-bg: #f8f9fa;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .success-container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
            border-top: 5px solid var(--success-color);
            transition: transform 0.3s ease;
        }
        
        .success-container:hover {
            transform: translateY(-5px);
        }
        
        .success-icon {
            font-size: 5rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
            animation: bounce 1s;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-20px);}
            60% {transform: translateY(-10px);}
        }
        
        .order-details {
            background: rgba(40, 167, 69, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.6rem 1.5rem;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
            min-width: 160px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #0069d9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: var(--success-color);
            opacity: 0;
        }
        
        @media (max-width: 768px) {
            .success-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include('CustomerNav.php'); ?>
    
    <div class="container py-5">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2 class="mb-3">Payment Successful!</h2>
            <p class="lead">Thank you for your payment. Your order #<?php echo htmlspecialchars($order['id']); ?> has been confirmed.</p>
            
            <div class="order-details">
                <h5 class="text-center mb-4"><i class="fas fa-receipt mr-2"></i>Order Summary</h5>
                
                <div class="detail-item">
                    <span><strong>Order ID:</strong></span>
                    <span><?php echo htmlspecialchars($order['id']); ?></span>
                </div>
                
                <div class="detail-item">
                    <span><strong>Payment ID:</strong></span>
                    <span><?php echo htmlspecialchars($order['razorpay_payment_id']); ?></span>
                </div>
                
                <div class="detail-item">
                    <span><strong>Date & Time:</strong></span>
                    <span><?php echo date('d M Y, h:i A', strtotime($order['updatedAt'])); ?></span>
                </div>
                
                <div class="detail-item">
                    <span><strong>Status:</strong></span>
                    <span class="badge badge-success">Completed</span>
                </div>
            </div>
            
            <div class="actions">
                <a href="view_order.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Orders
                </a>
                <?php if ($invoice_id): ?>
                    <a href="view_invoices.php?id=<?php echo $invoice_id; ?>" class="btn btn-primary">
                        <i class="fas fa-file-invoice mr-2"></i>View Invoice
                    </a>
                <?php endif; ?>
            </div>
            
            <div class="mt-4">
                <p class="text-muted small">A confirmation email has been sent to your registered email address.</p>
            </div>
        </div>
    </div>

    <script>
        // Simple confetti effect
        function createConfetti() {
            const colors = ['#28a745', '#17a2b8', '#ffc107', '#dc3545', '#007bff'];
            const container = document.querySelector('.success-container');
            
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.top = -10 + 'px';
                confetti.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
                container.appendChild(confetti);
                
                const animationDuration = Math.random() * 3 + 2;
                
                confetti.style.animation = `fall ${animationDuration}s linear forwards`;
                
                // Create keyframes dynamically
                const style = document.createElement('style');
                style.innerHTML = `
                    @keyframes fall {
                        to {
                            transform: translateY(${window.innerHeight}px) rotate(${Math.random() * 360}deg);
                            opacity: 0;
                            left: ${Math.random() * 100}%;
                        }
                    }
                `;
                document.head.appendChild(style);
                
                // Remove confetti after animation
                setTimeout(() => {
                    confetti.remove();
                    style.remove();
                }, animationDuration * 1000);
            }
        }
        
        // Trigger confetti on page load
        window.addEventListener('load', createConfetti);
    </script>
</body>
</html>