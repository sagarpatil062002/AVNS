<?php
// Start session
session_start();

// Database connection
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logged-in customer ID from session
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id'];
} else {
    die("No customer is logged in. Please log in.");
}

// Fetch all required data from database
$query = "
    SELECT 
        (SELECT COUNT(*) FROM order_details WHERE customerId = $customerId) AS totalOrders,
        (SELECT COUNT(*) FROM quotation_header WHERE customerId = $customerId) AS totalQuotations,
        (SELECT COUNT(*) FROM invoices WHERE customer_id = $customerId) AS totalInvoices,
        (SELECT COUNT(*) FROM ticket WHERE customerId = $customerId) AS totalTickets,
        (SELECT status FROM customer_subscription WHERE user_id = $customerId ORDER BY id DESC LIMIT 1) AS subscriptionStatus,
        (SELECT name FROM customer_plan cp 
         JOIN customer_subscription cs ON cp.id = cs.plan_id 
         WHERE cs.user_id = $customerId ORDER BY cs.id DESC LIMIT 1) AS subscriptionPlan,
        (SELECT COUNT(*) FROM order_details WHERE customerId = $customerId AND status = 'DELIVERED') AS deliveredOrders,
        (SELECT COUNT(*) FROM order_details WHERE customerId = $customerId AND status = 'SHIPPED') AS shippedOrders,
        (SELECT COUNT(*) FROM order_details WHERE customerId = $customerId AND status = 'IN_PROCESS') AS processingOrders,
        (SELECT COUNT(*) FROM ticket WHERE customerId = $customerId AND status = 'RESOLVED') AS resolvedTickets,
        (SELECT COUNT(*) FROM ticket WHERE customerId = $customerId AND status = 'PENDING') AS pendingTickets
";

$result = $conn->query($query);

// Initialize variables
$totalOrders = $totalQuotations = $totalInvoices = $totalTickets = 0;
$subscriptionStatus = 'No active subscription';
$subscriptionPlan = 'None';
$deliveredOrders = $shippedOrders = $processingOrders = 0;
$resolvedTickets = $pendingTickets = 0;

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $totalOrders = $data['totalOrders'] ?? 0;
    $totalQuotations = $data['totalQuotations'] ?? 0;
    $totalInvoices = $data['totalInvoices'] ?? 0;
    $totalTickets = $data['totalTickets'] ?? 0;
    $subscriptionStatus = $data['subscriptionStatus'] ?? 'No active subscription';
    $subscriptionPlan = $data['subscriptionPlan'] ?? 'None';
    $deliveredOrders = $data['deliveredOrders'] ?? 0;
    $shippedOrders = $data['shippedOrders'] ?? 0;
    $processingOrders = $data['processingOrders'] ?? 0;
    $resolvedTickets = $data['resolvedTickets'] ?? 0;
    $pendingTickets = $data['pendingTickets'] ?? 0;
}

// Fetch customer data for profile display
$customerQuery = "SELECT companyname, mailid, image FROM customerdistributor WHERE id = $customerId";
$customerResult = $conn->query($customerQuery);
$customerData = $customerResult->fetch_assoc();

// Fetch recent orders
$recentOrdersQuery = "
    SELECT od.id, od.status, od.createdAt, 
           COALESCE(p.name, od.custom_product_name) AS productName,
           od.quantity, od.amount
    FROM order_details od
    LEFT JOIN product p ON od.productId = p.id
    WHERE od.customerId = $customerId
    ORDER BY od.createdAt DESC
    LIMIT 5
";
$recentOrdersResult = $conn->query($recentOrdersQuery);
$recentOrders = [];
if ($recentOrdersResult->num_rows > 0) {
    while ($row = $recentOrdersResult->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-light: #e8f1ff;
            --secondary-color: #1cc88a;
            --secondary-light: #d1f7e8;
            --danger-color: #e74a3b;
            --danger-light: #fbe9e8;
            --warning-color: #f6c23e;
            --warning-light: #fff8e6;
            --info-color: #36b9cc;
            --info-light: #e3f6fa;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
            --sidebar-width: 250px;
            --header-height: 70px;
            --transition-speed: 0.3s;
            --card-radius: 12px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
            --shadow-lg: 0 10px 20px rgba(0,0,0,0.1), 0 6px 6px rgba(0,0,0,0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--shadow-md);
            transition: var(--transition-speed);
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            height: var(--header-height);
        }
        
        .logo-image {
            width: 40px;
            height: 40px;
            background-color: white;
            border-radius: 50%;
            margin-right: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .logo_name {
            font-weight: 800;
            font-size: 1.2rem;
            white-space: nowrap;
        }
        
        .menu-items {
            height: calc(100vh - var(--header-height));
            overflow-y: auto;
            padding: 1rem 0;
        }
        
        .nav-links, .logout-mode {
            list-style: none;
        }
        
        .nav-links li, .logout-mode li {
            position: relative;
        }
        
        .nav-links a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all var(--transition-speed);
            white-space: nowrap;
        }
        
        .nav-links a:hover, .nav-links a.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .nav-links i {
            font-size: 1.1rem;
            margin-right: 1rem;
            min-width: 20px;
            transition: var(--transition-speed);
        }
        
        .link-name {
            transition: opacity var(--transition-speed);
        }
        
        .dropdown-content {
            display: none;
            background-color: rgba(0, 0, 0, 0.2);
            padding-left: 2.5rem;
        }
        
        .dropdown-content a {
            padding: 0.6rem 1.5rem;
            font-size: 0.85rem;
        }
        
        .dropdown:hover .dropdown-content {
            display: block;
        }
        
        .logout-mode {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem;
            margin-top: 1rem;
        }
        
        /* Main Content Styles */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            transition: var(--transition-speed);
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            background: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-speed);
        }
        
        .user-profile:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 12px;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-info h4 {
            margin: 0;
            color: var(--dark-color);
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
        }
        
        .user-info p {
            margin: 0;
            color: var(--dark-color);
            opacity: 0.7;
            font-size: 0.8rem;
            white-space: nowrap;
        }
        
        /* Cards Section */
        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-speed);
            overflow: hidden;
            padding: 1.5rem;
            position: relative;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
        }
        
        .card-orders::before {
            background: var(--primary-color);
        }
        
        .card-quotations::before {
            background: var(--secondary-color);
        }
        
        .card-invoices::before {
            background: var(--info-color);
        }
        
        .card-tickets::before {
            background: var(--warning-color);
        }
        
        .card-subscription::before {
            background: #9c27b0;
        }
        
        .card-order-status::before {
            background: #607d8b;
        }
        
        .card-ticket-status::before {
            background: #ff9800;
        }
        
        .card-icon {
            font-size: 1.75rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--primary-light);
        }
        
        .card-orders .card-icon {
            color: var(--primary-color);
            background-color: var(--primary-light);
        }
        
        .card-quotations .card-icon {
            color: var(--secondary-color);
            background-color: var(--secondary-light);
        }
        
        .card-invoices .card-icon {
            color: var(--info-color);
            background-color: var(--info-light);
        }
        
        .card-tickets .card-icon {
            color: var(--warning-color);
            background-color: var(--warning-light);
        }
        
        .card-subscription .card-icon {
            color: #9c27b0;
            background-color: #f3e5f5;
        }
        
        .card-order-status .card-icon {
            color: #607d8b;
            background-color: #eceff1;
        }
        
        .card-ticket-status .card-icon {
            color: #ff9800;
            background-color: #fff3e0;
        }
        
        .card-title {
            font-size: 0.95rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
            opacity: 0.8;
        }
        
        .card-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.25rem;
        }
        
        .card-detail {
            font-size: 0.8rem;
            color: var(--dark-color);
            opacity: 0.6;
            margin-top: 0.5rem;
        }
        
        .card-footer {
            margin-top: 1rem;
        }
        
        .card-footer a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .card-footer a:hover {
            color: #224abe;
            text-decoration: underline;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            min-width: 90px;
            text-align: center;
        }
        
        .status-active {
            background-color: var(--secondary-light);
            color: var(--secondary-color);
        }
        
        .status-inactive {
            background-color: var(--danger-light);
            color: var(--danger-color);
        }
        
        .status-pending {
            background-color: var(--warning-light);
            color: var(--warning-color);
        }
        
        .status-delivered {
            background-color: var(--secondary-light);
            color: var(--secondary-color);
        }
        
        .status-shipped {
            background-color: var(--info-light);
            color: var(--info-color);
        }
        
        .status-processing {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        /* Recent Orders Table */
        .recent-orders {
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            margin-top: 2rem;
            transition: var(--transition-speed);
        }
        
        .recent-orders:hover {
            box-shadow: var(--shadow-md);
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title i {
            color: var(--primary-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        th {
            background-color: #f8f9fc;
            color: var(--dark-color);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            font-size: 0.9rem;
            color: var(--dark-color);
        }
        
        tr:hover {
            background-color: #f5f7ff;
        }
        
        /* Menu Toggle Button */
        .menu-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            background: var(--primary-color);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 1.25rem;
            cursor: pointer;
            box-shadow: var(--shadow-md);
            z-index: 1002;
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card {
            animation: fadeIn 0.5s ease-out forwards;
        }
        
        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.2s; }
        .card:nth-child(3) { animation-delay: 0.3s; }
        .card:nth-child(4) { animation-delay: 0.4s; }
        .card:nth-child(5) { animation-delay: 0.5s; }
        .card:nth-child(6) { animation-delay: 0.6s; }
        
        /* Responsive Styles */
        @media (max-width: 1200px) {
            .cards-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1001;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block !important;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-profile {
                width: 100%;
                justify-content: flex-start;
            }
        }
        
        @media (max-width: 576px) {
            .cards-container {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Menu Toggle Button (Mobile) -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Vertical Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-image">A</div>
            <span class="logo_name">AVNS</span>
        </div>
        
        <div class="menu-items">
            <ul class="nav-links">
                <li class="active">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="link-name">Dashboard</span>
                    </a>
                </li>
                
                <li>
                    <a href="customer_products.php">
                        <i class="fas fa-box-open"></i>
                        <span class="link-name">Products</span>
                    </a>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="link-name">Tickets</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="create_ticket.php"><i class="fas fa-plus-circle"></i> Create Ticket</a></li>
                        <li><a href="view_ticket_status.php"><i class="fas fa-eye"></i> View Ticket Status</a></li>
                    </ul>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span class="link-name">Subscription</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="customer_subscription.php"><i class="fas fa-cart-plus"></i> Buy Subscription</a></li>
                        <li><a href="view_subscription.php"><i class="fas fa-file-alt"></i> View Subscription</a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="view_order.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="link-name">Order</span>
                    </a>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-file-contract"></i>
                        <span class="link-name">Quotation</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="request_quotation.php"><i class="fas fa-file-upload"></i> Create Quotation</a></li>
                        <li><a href="view_Adminquotation.php"><i class="fas fa-file-search"></i> View Quotation</a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="view_invoices.php">
                        <i class="fas fa-file-invoice"></i>
                        <span class="link-name">Invoices</span>
                    </a>
                </li>
                
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        <span class="link-name">Profile</span>
                    </a>
                </li>
            </ul>
            
            <ul class="logout-mode">
                <li>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="link-name">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    
    <!-- Main Content Area -->
    <main class="main-content" id="mainContent">
        <div class="dashboard-header">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt"></i> Customer Dashboard
            </h1>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <?php if (!empty($customerData['image'])): ?>
                    <?php else: ?>
                        <?php echo strtoupper(substr($customerData['compayname'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($customerData['companyname']); ?></h4>
                    <p><?php echo htmlspecialchars($customerData['mailid']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="cards-container">
            <!-- Orders Card -->
            <div class="card card-orders">
                <div class="card-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3 class="card-title">Total Orders</h3>
                <div class="card-value"><?php echo $totalOrders; ?></div>
                <div class="card-detail">Delivered: <?php echo $deliveredOrders; ?></div>
                <div class="card-detail">Shipped: <?php echo $shippedOrders; ?></div>
                <div class="card-detail">Processing: <?php echo $processingOrders; ?></div>
                <div class="card-footer">
                    <a href="view_order.php">View All Orders</a>
                </div>
            </div>
            
            <!-- Quotations Card -->
            <div class="card card-quotations">
                <div class="card-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <h3 class="card-title">Quotations</h3>
                <div class="card-value"><?php echo $totalQuotations; ?></div>
                <div class="card-footer">
                    <a href="view_Adminquotation.php">View Quotations</a>
                </div>
            </div>
            
            <!-- Invoices Card -->
            <div class="card card-invoices">
                <div class="card-icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <h3 class="card-title">Invoices</h3>
                <div class="card-value"><?php echo $totalInvoices; ?></div>
                <div class="card-footer">
                    <a href="view_invoices.php">View Invoices</a>
                </div>
            </div>
            
            <!-- Tickets Card -->
            <div class="card card-tickets">
                <div class="card-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3 class="card-title">Support Tickets</h3>
                <div class="card-value"><?php echo $totalTickets; ?></div>
                <div class="card-detail">Resolved: <?php echo $resolvedTickets; ?></div>
                <div class="card-detail">Pending: <?php echo $pendingTickets; ?></div>
                <div class="card-footer">
                    <a href="view_ticket_status.php">View Tickets</a>
                </div>
            </div>
            
            <!-- Subscription Card -->
            <div class="card card-subscription">
                <div class="card-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3 class="card-title">Subscription Status</h3>
                <?php if ($subscriptionStatus === 'Approved'): ?>
                    <div class="card-value"><?php echo $subscriptionPlan; ?></div>
                    <span class="status-badge status-active">
                        <i class="fas fa-check-circle"></i> Active
                    </span>
                <?php elseif ($subscriptionStatus === 'Pending'): ?>
                    <div class="card-value"><?php echo $subscriptionPlan; ?></div>
                    <span class="status-badge status-pending">
                        <i class="fas fa-clock"></i> Pending
                    </span>
                <?php else: ?>
                    <div class="card-value">None</div>
                    <span class="status-badge status-inactive">
                        <i class="fas fa-times-circle"></i> No active subscription
                    </span>
                <?php endif; ?>
                <div class="card-footer">
                    <a href="customer_subscription.php">Manage Subscription</a>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders Section -->
        <div class="recent-orders">
            <h3 class="section-title">
                <i class="fas fa-history"></i> Recent Orders
            </h3>
            
            <?php if (!empty($recentOrders)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['productName']); ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td>₹<?php echo number_format($order['amount'], 2); ?></td>
                                <td><?php echo date('d M Y', strtotime($order['createdAt'])); ?></td>
                                <td>
                                    <?php if ($order['status'] === 'DELIVERED'): ?>
                                        <span class="status-badge status-delivered">Delivered</span>
                                    <?php elseif ($order['status'] === 'SHIPPED'): ?>
                                        <span class="status-badge status-shipped">Shipped</span>
                                    <?php else: ?>
                                        <span class="status-badge status-processing">Processing</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No recent orders found.</p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Mobile menu toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.getElementById('sidebar');
        
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnMenuToggle = menuToggle.contains(event.target);
            
            if (!isClickInsideSidebar && !isClickOnMenuToggle && window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        });
    </script>
</body>
</html>