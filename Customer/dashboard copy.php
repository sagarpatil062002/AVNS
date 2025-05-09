
<?php
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'sales_management';

$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Customer ID (replace with logged-in customer ID)
$customerId = 1; // Example customer ID

// Fetch counts
$query = "
    SELECT 
        (SELECT COUNT(*) FROM product) AS totalProducts,
        (SELECT COUNT(*) FROM quotation WHERE id = $customerId) AS totalQuotations,
        (SELECT COUNT(*) FROM invoices WHERE id = $customerId) AS totalInvoices
";
$result = $conn->query($query);

// Initialize variables for counts
$totalProducts = $totalQuotations = $totalInvoices = 0;

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $totalProducts = $data['totalProducts'];
    $totalQuotations = $data['totalQuotations'];
    $totalInvoices = $data['totalInvoices'];
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <title>Customer Dashboard Panel</title>
</head>
<body>
<nav>
        <div class="logo-name">
            <div class="logo-image">
               <img src="#" alt="">
            </div>
            <span class="logo_name">AVNS</span>
        </div>
        <div class="menu-items">
        <ul class="nav-links">
    <li><a href="#">
        <i class="uil uil-estate"></i>
        <span class="link-name">Dashboard</span>
    </a></li>
    
    

    <li class="dropdown">
        <a href="customer_products.php">
        <i class="uil uil-box"></i>
        <span class="link-name">Products</span>
        </a>
    </li>
    
    <li class="dropdown">
                    <a href="#">
                        <i class="uil uil-receipt"></i>
                        <span class="link-name">Tickets</span>
                    </a>
                    <div class="dropdown-content">
                        <a href="create_ticket.php">Create Ticket</a>
                        <a href="manage_subscriptions.php">View Ticket Status</a>
                    </div>
                </li>
               
    
    <li class="dropdown">
    <a href="customer_subscription.php">
        <i class="uil uil-receipt"></i>
        <span class="link-name">Subscription</span>
    </a>
    </li>
    
    <li><a href="view_order.php">
        <i class="uil uil-clipboard"></i>
        <span class="link-name">Order</span>
        </a>
    </li>
    
    <li><a href="accept_freelancer.php">
        <i class="uil uil-laptop"></i>
        <span class="link-name">Quotation</span>
    </a></li>

    <li><a href="view_invoices.php">
        <i class="uil uil-laptop"></i>
        <span class="link-name">Invoices</span>
    </a></li>
    
    <li><a href="profile.php">
        <i class="uil uil-laptop"></i>
        <span class="link-name">Profile</span>
    </a></li>
</ul>

</ul>

            <ul class="logout-mode">
                <li><a href="../logout.php">
                    <i class="uil uil-signout"></i>
                    <span class="link-name">Logout</span>
                </a></li>
                <li>
                  <div class="mode-toggle">
                    <span class="switch"></span>
                  </div>
                </li>
            </ul>
        </div>
    </nav>
    <section class="dashboard">
        <div class="top">
            <i class="uil uil-bars sidebar-toggle"></i>
        </div>
        <div class="dash-content">
            <div class="overview">
                <div class="title">
                    <i class="uil uil-tachometer-fast-alt"></i>
                    <span class="text">Dashboard</span>
                </div>
                <div class="boxes">
                    <div class="box box1">
                        <i class="uil uil-box"></i>
                        <span class="text">Total Products</span>
                        <span class="number"><?php echo $totalProducts; ?></span>
                    </div>
                    <div class="box box2">
                        <i class="uil uil-file-info-alt"></i>
                        <span class="text">Quotations</span>
                        <span class="number"><?php echo $totalQuotations; ?></span>
                    </div>
                    <div class="box box3">
                        <i class="uil uil-invoice"></i>
                        <span class="text">Invoices</span>
                        <span class="number"><?php echo $totalInvoices; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>

