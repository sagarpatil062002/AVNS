<?php
// Start the session
session_start();

// Database connection
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if freelancer is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$freelancerId = $_SESSION['user_id'];

// Fetch freelancer data
$freelancerQuery = "SELECT name, email, image FROM freelancer WHERE id = $freelancerId";
$freelancerResult = $conn->query($freelancerQuery);
$freelancerData = $freelancerResult->fetch_assoc();

// Fetch counts for dashboard
$query = "
    SELECT 
        (SELECT COUNT(*) FROM ticket WHERE freelancerId = $freelancerId) AS totalTickets,
        (SELECT COUNT(*) FROM ticket WHERE freelancerId = $freelancerId AND status = 'RESOLVED') AS resolvedTickets,
        (SELECT COUNT(*) FROM ticket WHERE freelancerId = $freelancerId AND status = 'IN_PROGRESS') AS inProgressTickets,
        (SELECT COUNT(*) FROM freelancer_skills WHERE freelancer_id = $freelancerId AND is_approved = 1) AS approvedSkills,
        (SELECT COUNT(*) FROM order_details WHERE customerId = $freelancerId) AS totalOrders,
        (SELECT COUNT(*) FROM customer_subscription WHERE user_id = $freelancerId AND isexpired = 0) AS activeSubscriptions
";

$result = $conn->query($query);

// Initialize variables
$totalTickets = $resolvedTickets = $inProgressTickets = $approvedSkills = $totalOrders = $activeSubscriptions = 0;

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $totalTickets = $data['totalTickets'] ?? 0;
    $resolvedTickets = $data['resolvedTickets'] ?? 0;
    $inProgressTickets = $data['inProgressTickets'] ?? 0;
    $approvedSkills = $data['approvedSkills'] ?? 0;
    $totalOrders = $data['totalOrders'] ?? 0;
    $activeSubscriptions = $data['activeSubscriptions'] ?? 0;
}

// Fetch ticket status distribution for chart
$ticketStatusQuery = "
    SELECT status, COUNT(*) AS count 
    FROM ticket 
    WHERE freelancerId = $freelancerId
    GROUP BY status
";
$ticketStatusResult = $conn->query($ticketStatusQuery);
$ticketStatusData = [];
while ($row = $ticketStatusResult->fetch_assoc()) {
    $ticketStatusData[$row['status']] = $row['count'];
}

// Fetch recent tickets
$recentTicketsQuery = "
    SELECT t.id, t.description, t.status, t.createdAt, s.skill_name, cd.companyName
    FROM ticket t
    JOIN skills s ON t.skill_id = s.id
    JOIN customerdistributor cd ON t.customerId = cd.id
    WHERE t.freelancerId = $freelancerId
    ORDER BY t.createdAt DESC
    LIMIT 5
";
$recentTicketsResult = $conn->query($recentTicketsQuery);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Dashboard</title>
    
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
        
        .card-tickets::before {
            background: var(--primary-color);
        }
        
        .card-resolved::before {
            background: var(--secondary-color);
        }
        
        .card-in-progress::before {
            background: var(--info-color);
        }
        
        .card-skills::before {
            background: #9c27b0;
        }
        
        .card-orders::before {
            background: #607d8b;
        }
        
        .card-subscriptions::before {
            background: #e91e63;
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
        
        .card-tickets .card-icon {
            color: var(--primary-color);
            background-color: var(--primary-light);
        }
        
        .card-resolved .card-icon {
            color: var(--secondary-color);
            background-color: var(--secondary-light);
        }
        
        .card-in-progress .card-icon {
            color: var(--info-color);
            background-color: var(--info-light);
        }
        
        .card-skills .card-icon {
            color: #9c27b0;
            background-color: #f3e5f5;
        }
        
        .card-orders .card-icon {
            color: #607d8b;
            background-color: #eceff1;
        }
        
        .card-subscriptions .card-icon {
            color: #e91e63;
            background-color: #fce4ec;
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
        
        /* Recent Tickets Table */
        .recent-tickets {
            background-color: white;
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            margin-top: 2rem;
            transition: var(--transition-speed);
        }
        
        .recent-tickets:hover {
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
        
        .status-in-progress {
            background-color: var(--info-light);
            color: var(--info-color);
        }
        
        .status-resolved {
            background-color: var(--secondary-light);
            color: var(--secondary-color);
        }
        
        .status-assigned {
            background-color: var(--primary-light);
            color: var(--primary-color);
        }
        
        .status-rejected {
            background-color: var(--danger-light);
            color: var(--danger-color);
        }
        
        .status-closed {
            background-color: #f5f5f5;
            color: #757575;
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
            border-radius: var(--card-radius);
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            transition: var(--transition-speed);
        }
        
        .chart-card:hover {
            box-shadow: var(--shadow-md);
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
        .card:nth-child(5) { animation-delay: 0.5s; }
        .card:nth-child(6) { animation-delay: 0.6s; }
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
                    <a href="ticket_listing.php">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="link-name">My Tickets</span>
                    </a>
                </li>
                
                <li>
                    <a href="Profile.php">
                        <i class="fas fa-user-circle"></i>
                        <span class="link-name">My Profile</span>
                    </a>
                </li>
                
                
            
            <ul class="logout-mode">
                <li>
                    <a href="logout.php">
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
                <i class="fas fa-tachometer-alt"></i> Freelancer Dashboard
            </h1>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <?php if (!empty($freelancerData['image'])): ?>
                        <img src="<?php echo $freelancerData['image']; ?>" alt="Profile Image">
                    <?php else: ?>
                        <?php echo strtoupper(substr($freelancerData['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($freelancerData['name']); ?></h4>
                    <p><?php echo htmlspecialchars($freelancerData['email']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Summary Cards -->
        <div class="cards-container">
            <!-- Tickets Card -->
            <div class="card card-tickets">
                <div class="card-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h3 class="card-title">Total Tickets</h3>
                <div class="card-value"><?php echo $totalTickets; ?></div>
                <div class="card-footer">Assigned to you</div>
            </div>
            
            <!-- Resolved Tickets Card -->
            <div class="card card-resolved">
                <div class="card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h3 class="card-title">Resolved Tickets</h3>
                <div class="card-value"><?php echo $resolvedTickets; ?></div>
                <div class="card-footer">Successfully closed</div>
            </div>
            
            <!-- In Progress Tickets Card -->
            <div class="card card-in-progress">
                <div class="card-icon">
                    <i class="fas fa-spinner"></i>
                </div>
                <h3 class="card-title">In Progress</h3>
                <div class="card-value"><?php echo $inProgressTickets; ?></div>
                <div class="card-footer">Currently working on</div>
            </div>
            
            <!-- Approved Skills Card -->
            <div class="card card-skills">
                <div class="card-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <h3 class="card-title">Approved Skills</h3>
                <div class="card-value"><?php echo $approvedSkills; ?></div>
                <div class="card-footer">Your expertise</div>
            </div>
        </div>
        
        <!-- Ticket Status Chart -->
        <div class="charts-container">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">
                        <i class="fas fa-chart-pie"></i> Ticket Status Distribution
                    </h3>
                </div>
                <div id="ticket-status-chart"></div>
            </div>
        </div>
        
        <!-- Recent Tickets Table -->
        <div class="recent-tickets">
            <h3 class="section-title">
                <i class="fas fa-history"></i> Recent Tickets
            </h3>
            <table>
                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Description</th>
                        <th>Company</th>
                        <th>Skill</th>
                        <th>Created At</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recentTicketsResult->num_rows > 0): ?>
                        <?php while ($ticket = $recentTicketsResult->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $ticket['id']; ?></td>
                                <td><?php echo substr($ticket['description'], 0, 50) . '...'; ?></td>
                                <td><?php echo htmlspecialchars($ticket['companyName']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['skill_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($ticket['createdAt'])); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace('_', '-', $ticket['status'])); ?>">
                                        <?php echo $ticket['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No recent tickets found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        // Ticket Status Chart
        var ticketStatusOptions = {
            chart: {
                type: 'donut',
                height: 350,
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            series: [
                <?php echo $ticketStatusData['RESOLVED'] ?? 0; ?>,
                <?php echo $ticketStatusData['IN_PROGRESS'] ?? 0; ?>,
                <?php echo $ticketStatusData['PENDING'] ?? 0; ?>,
                <?php echo $ticketStatusData['ASSIGNED'] ?? 0; ?>,
                <?php echo $ticketStatusData['REJECTED'] ?? 0; ?>,
                <?php echo $ticketStatusData['CLOSED'] ?? 0; ?>
            ],
            labels: ['Resolved', 'In Progress', 'Pending', 'Assigned', 'Rejected', 'Closed'],
            colors: ['#1cc88a', '#36b9cc', '#f6c23e', '#4e73df', '#e74a3b', '#858796'],
            legend: {
                position: 'bottom',
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                showAlways: true,
                                label: 'Total Tickets',
                                fontSize: '16px',
                                fontWeight: 600,
                                color: '#5a5c69'
                            }
                        }
                    }
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: '100%'
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }]
        };

        var ticketStatusChart = new ApexCharts(document.querySelector("#ticket-status-chart"), ticketStatusOptions);
        ticketStatusChart.render();

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