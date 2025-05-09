<?php
// Start session
session_start();

// Database connection
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if distributor is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$distributorId = $_SESSION['user_id'];

// Fetch distributor data
$distributorQuery = "SELECT companyName, mailId, image FROM customerdistributor WHERE id = $distributorId";
$distributorResult = $conn->query($distributorQuery);
$distributorData = $distributorResult->fetch_assoc();

// Fetch counts for dashboard - only those sent to this distributor
$query = "
    SELECT 
        (SELECT COUNT(*) FROM product) AS totalProducts,
        (SELECT COUNT(*) FROM quotation_header WHERE distributorId = $distributorId) AS totalQuotations,
        (SELECT COUNT(*) FROM purchase_details WHERE distributor_id = $distributorId) AS totalPurchaseOrders
";

$result = $conn->query($query);

// Initialize variables
$totalProducts = $totalQuotations = $totalPurchaseOrders = 0;

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $totalProducts = $data['totalProducts'] ?? 0;
    $totalQuotations = $data['totalQuotations'] ?? 0;
    $totalPurchaseOrders = $data['totalPurchaseOrders'] ?? 0;
}

// Fetch recent purchase orders sent to this distributor
$recentOrdersQuery = "
    SELECT pd.id, pd.created_at AS order_date, 
           COUNT(pi.id) AS items_count, 
           SUM(pi.total) AS total_amount,
           'completed' AS status
    FROM purchase_details pd
    LEFT JOIN purchase_items pi ON pd.id = pi.purchase_id
    WHERE pd.distributor_id = $distributorId
    GROUP BY pd.id
    ORDER BY pd.created_at DESC
    LIMIT 5
";
$recentOrdersResult = $conn->query($recentOrdersQuery);

// Fetch recent quotations sent to this distributor
$recentQuotationsQuery = "
    SELECT qh.quotation_id, qh.createdAt, 
           COUNT(qp.id) AS items_count,
           SUM(qp.priceOffered * qp.quantity) AS total_amount,
           qh.status
    FROM quotation_header qh
    JOIN quotation_product qp ON qh.quotation_id = qp.quotation_id
    WHERE qh.distributorId = $distributorId
    GROUP BY qh.quotation_id
    ORDER BY qh.createdAt DESC
    LIMIT 5
";
$recentQuotationsResult = $conn->query($recentQuotationsQuery);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Distributor Dashboard | AVNS</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #7367f0;
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
        
        .card-products::before {
            background: var(--primary-color);
        }
        
        .card-quotations::before {
            background: var(--secondary-color);
        }
        
        .card-invoices::before {
            background: var(--info-color);
        }
        
        .card-orders::before {
            background: #9c27b0;
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
        
        .card-products .card-icon {
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
        
        .card-orders .card-icon {
            color: #9c27b0;
            background-color: #f3e5f5;
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
        
        .card-footer {
            font-size: 0.8rem;
            color: var(--dark-color);
            opacity: 0.6;
            margin-top: 0.5rem;
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
        
        .status-pending {
            background-color: var(--warning-light);
            color: var(--warning-color);
        }
        
        .status-processing {
            background-color: var(--info-light);
            color: var(--info-color);
        }
        
        .status-completed {
            background-color: var(--secondary-light);
            color: var(--secondary-color);
        }
        
        .status-cancelled {
            background-color: var(--danger-light);
            color: var(--danger-color);
        }
        
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
    </style></head>
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
                    <a href="view_purchase_detials.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="link-name">Purchase Orders</span>
                    </a>
                </li>
                
                <li>
                    <a href="view_Adminquotation.php">
                        <i class="fas fa-file-contract"></i>
                        <span class="link-name">Quotations</span>
                    </a>
                </li>
                
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user-circle"></i>
                        <span class="link-name">My Profile</span>
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
                <i class="fas fa-tachometer-alt"></i> Distributor Dashboard
            </h1>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <?php if (!empty($distributorData['image'])): ?>
                        <img src="<?php echo $distributorData['image']; ?>" alt="Profile Image">
                    <?php else: ?>
                        <?php echo strtoupper(substr($distributorData['companyName'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($distributorData['companyName']); ?></h4>
                    <p><?php echo htmlspecialchars($distributorData['mailId']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="cards-container">
            <!-- Products Card -->
            <div class="card card-products">
                <div class="card-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <h3 class="card-title">Available Products</h3>
                <div class="card-value"><?php echo $totalProducts; ?></div>
                <div class="card-footer">In our catalog</div>
            </div>
            
            <!-- Quotations Card -->
            <div class="card card-quotations">
                <div class="card-icon">
                    <i class="fas fa-file-contract"></i>
                </div>
                <h3 class="card-title">Your Quotations</h3>
                <div class="card-value"><?php echo $totalQuotations; ?></div>
                <div class="card-footer">Requests sent</div>
            </div>
            
            
            <!-- Purchase Orders Card -->
            <div class="card card-orders">
                <div class="card-icon">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h3 class="card-title">Purchase Orders</h3>
                <div class="card-value"><?php echo $totalPurchaseOrders; ?></div>
                <div class="card-footer">Orders placed</div>
            </div>
        </div>
       
    <!-- Recent Purchase Orders Table -->
    <div class="recent-orders">
        <h3 class="section-title">
            <i class="fas fa-shopping-cart"></i> Recent Purchase Orders
        </h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentOrdersResult->num_rows > 0): ?>
                    <?php while ($order = $recentOrdersResult->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td><?php echo $order['items_count']; ?></td>
                            <td>$<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No purchase orders found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Recent Quotations Table -->
    <div class="recent-orders" style="margin-top: 30px;">
        <h3 class="section-title">
            <i class="fas fa-file-contract"></i> Recent Quotations
        </h3>
        <table>
            <thead>
                <tr>
                    <th>Quotation ID</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentQuotationsResult->num_rows > 0): ?>
                    <?php while ($quotation = $recentQuotationsResult->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $quotation['quotation_id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($quotation['createdAt'])); ?></td>
                            <td><?php echo $quotation['items_count']; ?></td>
                            <td>$<?php echo number_format($quotation['total_amount'] ?? 0, 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($quotation['status']); ?>">
                                    <?php echo ucfirst($quotation['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No quotations found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- [Rest of the body content remains the same] -->
</body>
</html>