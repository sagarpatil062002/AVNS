<?php
// Start session
session_start();

// Database connection
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}



// Fetch all required data from database
$query = "
    SELECT 
        (SELECT COUNT(*) FROM customerdistributor) AS totalCustomers,
        (SELECT COUNT(*) FROM freelancer) AS totalFreelancers,
        (SELECT COUNT(*) FROM distributor) AS totalDistributors,
        (SELECT COUNT(*) FROM order_details) AS totalOrders,
        (SELECT COUNT(*) FROM purchase_details) AS totalPurchases,
        (SELECT COUNT(*) FROM ticket) AS totalTickets,
        (SELECT COUNT(*) FROM customer_subscription WHERE status = 'Approved') AS activeSubscriptions,
        (SELECT COUNT(*) FROM oem) AS totalOems,
        (SELECT SUM(total_amount + total_tax) FROM invoices) AS totalRevenue,
        (SELECT SUM(total_amount) FROM purchase_details) AS totalPurchaseCost,
        (SELECT COUNT(*) FROM product) AS totalProducts
";

$result = $conn->query($query);

// Initialize variables
$totalCustomers = $totalFreelancers = $totalDistributors = $totalOrders = $totalPurchases = $totalTickets = 0;
$activeSubscriptions = $totalOems = $totalRevenue = $totalPurchaseCost = $totalProducts = 0;

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $totalCustomers = $data['totalCustomers'] ?? 0;
    $totalFreelancers = $data['totalFreelancers'] ?? 0;
    $totalDistributors = $data['totalDistributors'] ?? 0;
    $totalOrders = $data['totalOrders'] ?? 0;
    $totalPurchases = $data['totalPurchases'] ?? 0;
    $totalTickets = $data['totalTickets'] ?? 0;
    $activeSubscriptions = $data['activeSubscriptions'] ?? 0;
    $totalOems = $data['totalOems'] ?? 0;
    $totalRevenue = $data['totalRevenue'] ?? 0;
    $totalPurchaseCost = $data['totalPurchaseCost'] ?? 0;
    $totalProducts = $data['totalProducts'] ?? 0;
}

// Fetch data for charts
$orderStatusQuery = "SELECT status, COUNT(*) AS count FROM order_details GROUP BY status";
$orderStatusResult = $conn->query($orderStatusQuery);
$orderStatusData = [];
while ($row = $orderStatusResult->fetch_assoc()) {
    $orderStatusData[$row['status']] = $row['count'];
}

$subscriptionPlanQuery = "
    SELECT cp.name, COUNT(*) AS count 
    FROM customer_subscription cs
    JOIN customer_plan cp ON cs.plan_id = cp.id
    WHERE cs.status = 'Approved'
    GROUP BY cp.name
";
$subscriptionPlanResult = $conn->query($subscriptionPlanQuery);
$subscriptionPlanData = [];
$subscriptionPlanLabels = [];
$subscriptionPlanCounts = [];
while ($row = $subscriptionPlanResult->fetch_assoc()) {
    $subscriptionPlanLabels[] = $row['name'];
    $subscriptionPlanCounts[] = $row['count'];
}

$monthlyRevenueQuery = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') AS month,
        SUM(total_amount + total_tax) AS revenue
    FROM invoices
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
";
$monthlyRevenueResult = $conn->query($monthlyRevenueQuery);
$monthlyRevenueLabels = [];
$monthlyRevenueData = [];
while ($row = $monthlyRevenueResult->fetch_assoc()) {
    $monthlyRevenueLabels[] = date('M Y', strtotime($row['month'] . '-01'));
    $monthlyRevenueData[] = $row['revenue'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --dark-color: #5a5c69;
            --light-color: #f8f9fc;
            --sidebar-width: 250px;
            --transition-speed: 0.3s;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            height: 100vh;
            position: fixed;
            overflow: hidden;
            z-index: 1000;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .sidebar-header {
            padding: 1.5rem 1rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .logo-image {
            width: 40px;
            height: 40px;
            background-color: white;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .logo_name {
            font-weight: 800;
            font-size: 1.2rem;
            white-space: nowrap;
        }
        
        .menu-items {
            height: calc(100vh - 70px);
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
            transition: all 0.3s;
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
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: var(--dark-color);
            opacity: 0.8;
            font-size: 1rem;
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
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
            padding: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(58, 59, 69, 0.2);
        }
        
        .card-customers {
            border-left-color: var(--primary-color);
        }
        
        .card-freelancers {
            border-left-color: var(--secondary-color);
        }
        
        .card-distributors {
            border-left-color: var(--info-color);
        }
        
        .card-orders {
            border-left-color: #9c27b0;
        }
        
        .card-purchases {
            border-left-color: #607d8b;
        }
        
        .card-tickets {
            border-left-color: var(--warning-color);
        }
        
        .card-subscriptions {
            border-left-color: #e91e63;
        }
        
        .card-oems {
            border-left-color: #4caf50;
        }
        
        .card-revenue {
            border-left-color: #673ab7;
        }
        
        .card-products {
            border-left-color: #ff9800;
        }
        
        .card-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }
        
        .card-title {
            font-size: 1rem;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .card-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }
        
        .card-amount {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        /* Charts Section */
        .charts-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .chart-card {
            background-color: white;
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            padding: 1.5rem;
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        /* Responsive Styles */
        @media (max-width: 992px) {
            .cards-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .sidebar {
                width: 0;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <!-- Vertical Sidebar Navigation -->
    <nav class="sidebar">
        <div class="sidebar-header">
            <div class="logo-image"></div>
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
                    <a href="userlist.php">
                        <i class="fas fa-users-cog"></i>
                        <span class="link-name">User Management</span>
                    </a>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-cogs"></i>
                        <span class="link-name">Utilities</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="add_taxes.php"><i class="fas fa-money-bill-wave"></i> Add Taxes</a></li>
                        <li><a href="add_new_Category.php"><i class="fas fa-tags"></i> Add Categories</a></li>
                        <li><a href="add_oem.php"><i class="fas fa-microchip"></i> Add OEM</a></li>
                        <li><a href="add_skills.php"><i class="fas fa-brain"></i> Add Skills</a></li>
                        <li><a href="new_sectors.php"><i class="fas fa-chart-pie"></i> New Sector</a></li>
                    </ul>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-box-open"></i>
                        <span class="link-name">Product</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="add_product.php"><i class="fas fa-plus-circle"></i> Add Product</a></li>
                        <li><a href="manage_product.php"><i class="fas fa-edit"></i> Manage Product</a></li>
                    </ul>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="link-name">Order Management</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="manage_order.php"><i class="fas fa-clipboard-list"></i> Manage Order</a></li>
                        <li><a href="view_payments.php"><i class="fas fa-wallet"></i> View Payments</a></li>
                        <li><a href="view_purchase_detials.php"><i class="fas fa-eye"></i> View Purchase Order</a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="ticket_assignment.php">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="link-name">Ticket Management</span>
                    </a>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span class="link-name">Subscription</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="create_subscriptions.php"><i class="fas fa-file-plus"></i> Create Subscription</a></li>
                        <li><a href="manage_subscriptions.php"><i class="fas fa-file-alt"></i> Manage subscription</a></li>
                        <li><a href="manage_customer_plan.php"><i class="fas fa-user-cog"></i> Manage Customer Plans</a></li>
                    </ul>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-file-contract"></i>
                        <span class="link-name">Quotation</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="create_quotation.php"><i class="fas fa-file-upload"></i> Send Quotation</a></li>
                        <li><a href="view_myquotations.php"><i class="fas fa-file-check"></i> My Quotation</a></li>
                        <li><a href="view_quotation.php"><i class="fas fa-file-search"></i> View Quotation</a></li>
                    </ul>
                </li>

                <li>
                    <a href="view_invoices.php">
                        <i class="fas fa-file-invoice"></i>
                        <span class="link-name">Invoice</span>
                    </a>
                </li>
                
                <li>
                    <a href="approve_skills.php">
                        <i class="fas fa-user-tie"></i>
                        <span class="link-name">Freelancer Management</span>
                    </a>
                </li>

                <li>
                    <a href="reports.php">
                    <i class="fas fa-chart-bar"></i> <!-- Statistics/analytics report -->
                    <span class="link-name">Reports</span>
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
    <main class="main-content">
        <div class="dashboard-header">
            <h1 class="page-title">
                <i class="fas fa-tachometer-alt mr-2"></i>Super Admin Dashboard
            </h1>
        </div>
        
        <!-- Summary Cards -->
        <div class="cards-container">
            <!-- Customers Card -->
            <div class="card card-customers">
                <i class="fas fa-users card-icon"></i>
                <h3 class="card-title">Total Customers</h3>
                <div class="card-value"><?php echo $totalCustomers; ?></div>
            </div>
            
            <!-- Freelancers Card -->
            <div class="card card-freelancers">
                <i class="fas fa-user-tie card-icon"></i>
                <h3 class="card-title">Total Freelancers</h3>
                <div class="card-value"><?php echo $totalFreelancers; ?></div>
            </div>
            
            <!-- Distributors Card -->
            <div class="card card-distributors">
                <i class="fas fa-truck card-icon"></i>
                <h3 class="card-title">Total Distributors</h3>
                <div class="card-value"><?php echo $totalDistributors; ?></div>
            </div>
            
            <!-- Orders Card -->
            <div class="card card-orders">
                <i class="fas fa-shopping-cart card-icon"></i>
                <h3 class="card-title">Total Orders</h3>
                <div class="card-value"><?php echo $totalOrders; ?></div>
            </div>
            
            <!-- Purchases Card -->
            <div class="card card-purchases">
                <i class="fas fa-boxes card-icon"></i>
                <h3 class="card-title">Total Purchases</h3>
                <div class="card-value"><?php echo $totalPurchases; ?></div>
            </div>
            
            <!-- Tickets Card -->
            <div class="card card-tickets">
                <i class="fas fa-ticket-alt card-icon"></i>
                <h3 class="card-title">Total Tickets</h3>
                <div class="card-value"><?php echo $totalTickets; ?></div>
            </div>
            
            <!-- Active Subscriptions Card -->
            <div class="card card-subscriptions">
                <i class="fas fa-file-invoice-dollar card-icon"></i>
                <h3 class="card-title">Active Subscriptions</h3>
                <div class="card-value"><?php echo $activeSubscriptions; ?></div>
            </div>
            
            <!-- OEMs Card -->
            <div class="card card-oems">
                <i class="fas fa-microchip card-icon"></i>
                <h3 class="card-title">Total OEMs</h3>
                <div class="card-value"><?php echo $totalOems; ?></div>
            </div>
            
            <!-- Revenue Card -->
            <div class="card card-revenue">
                <i class="fas fa-money-bill-wave card-icon"></i>
                <h3 class="card-title">Total Revenue</h3>
                <div class="card-amount">₹<?php echo number_format($totalRevenue, 2); ?></div>
            </div>
            
            <!-- Products Card -->
            <div class="card card-products">
                <i class="fas fa-box-open card-icon"></i>
                <h3 class="card-title">Total Products</h3>
                <div class="card-value"><?php echo $totalProducts; ?></div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="charts-container">
            <!-- Order Status Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Order Status Distribution</h3>
                </div>
                <div id="order-status-chart"></div>
            </div>
            
            <!-- Subscription Plan Distribution Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Subscription Plan Distribution</h3>
                </div>
                <div id="subscription-plan-chart"></div>
            </div>
            
            <!-- Monthly Revenue Chart -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">Monthly Revenue (Last 6 Months)</h3>
                </div>
                <div id="monthly-revenue-chart"></div>
            </div>
        </div>
    </main>

    <script>
        // Order Status Chart
        var orderStatusOptions = {
            chart: {
                type: 'donut',
                height: 350
            },
            series: [
                <?php echo $orderStatusData['DELIVERED'] ?? 0; ?>,
                <?php echo $orderStatusData['SHIPPED'] ?? 0; ?>,
                <?php echo $orderStatusData['IN_PROCESS'] ?? 0; ?>,
                <?php echo $orderStatusData['PENDING'] ?? 0; ?>
            ],
            labels: ['Delivered', 'Shipped', 'Processing', 'Pending'],
            colors: ['#1cc88a', '#36b9cc', '#4e73df', '#f6c23e'],
            legend: {
                position: 'bottom'
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        var orderStatusChart = new ApexCharts(document.querySelector("#order-status-chart"), orderStatusOptions);
        orderStatusChart.render();

        // Subscription Plan Distribution Chart
        var subscriptionPlanOptions = {
            chart: {
                type: 'pie',
                height: 350
            },
            series: <?php echo json_encode($subscriptionPlanCounts); ?>,
            labels: <?php echo json_encode($subscriptionPlanLabels); ?>,
            colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
            legend: {
                position: 'bottom'
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        var subscriptionPlanChart = new ApexCharts(document.querySelector("#subscription-plan-chart"), subscriptionPlanOptions);
        subscriptionPlanChart.render();

        // Monthly Revenue Chart
        var monthlyRevenueOptions = {
            chart: {
                type: 'area',
                height: 350,
                stacked: false,
                zoom: {
                    enabled: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth'
            },
            series: [{
                name: 'Revenue',
                data: <?php echo json_encode($monthlyRevenueData); ?>
            }],
            xaxis: {
                categories: <?php echo json_encode($monthlyRevenueLabels); ?>,
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "₹" + val.toLocaleString('en-IN');
                    }
                }
            },
            colors: ['#4e73df']
        };

        var monthlyRevenueChart = new ApexCharts(document.querySelector("#monthly-revenue-chart"), monthlyRevenueOptions);
        monthlyRevenueChart.render();
    </script>
</body>
</html>