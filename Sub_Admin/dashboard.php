<?php

include 'navbar.php';

// Database connection
$host = 'localhost'; // Your database host
$user = 'root'; // Your database username
$password = ''; // Your database password
$dbname = 'sales_management'; // Your database name

$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch total products for the cards
$query = "SELECT (SELECT COUNT(*) FROM product) AS totalProducts";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
    $totalProducts = $data['totalProducts'];
} else {
    $totalProducts = 0;
}


// Fetch total invoices and total invoice amount from the invoices table
$invoiceQuery = "SELECT 
                    (SELECT COUNT(*) FROM invoices) AS totalInvoices,
                    (SELECT SUM(total_amount) FROM invoices) AS totalInvoiceAmount";
$invoiceResult = $conn->query($invoiceQuery);

if ($invoiceResult->num_rows > 0) {
    $invoiceData = $invoiceResult->fetch_assoc();
    $totalInvoices = $invoiceData['totalInvoices'];
    $totalInvoiceAmount = $invoiceData['totalInvoiceAmount'];
} else {
    $totalInvoices = 0;
    $totalInvoiceAmount = 0;
}


// Fetch total quotations and total quoted amount from the quotation table
$quotationQuery = "SELECT 
                    (SELECT COUNT(*) FROM quotation WHERE status = 'PENDING' OR status = 'APPROVED' OR status = 'REJECTED') AS totalQuotations,
                    (SELECT SUM(priceOffered * quantity) FROM quotation WHERE status = 'PENDING' OR status = 'APPROVED' OR status = 'REJECTED') AS totalQuotedAmount";
$quotationResult = $conn->query($quotationQuery);

if ($quotationResult->num_rows > 0) {
    $quotationData = $quotationResult->fetch_assoc();
    $totalQuotations = $quotationData['totalQuotations'];
    $totalQuotedAmount = $quotationData['totalQuotedAmount'];
} else {
    $totalQuotations = 0; $totalQuotedAmount = 0;
}


// Fetch the number of customers
$customerQuery = "SELECT COUNT(*) AS totalCustomers FROM customerdistributor";
$customerResult = $conn->query($customerQuery);
$customerData = $customerResult->fetch_assoc();
$totalCustomers = $customerData['totalCustomers'];

// Fetch the number of freelancers
$freelancerQuery = "SELECT COUNT(*) AS totalFreelancers FROM freelancer";
$freelancerResult = $conn->query($freelancerQuery);
$freelancerData = $freelancerResult->fetch_assoc();
$totalFreelancers = $freelancerData['totalFreelancers'];

// Fetch the number of users (admins)
$userQuery = "SELECT COUNT(*) AS totalUsers FROM users";
$userResult = $conn->query($userQuery);
$userData = $userResult->fetch_assoc();
$totalUsers = $userData['totalUsers'];

// Fetch order status counts for chart
$orderQuery = "SELECT status, COUNT(*) AS statusCount FROM order_details GROUP BY status";
$orderResult = $conn->query($orderQuery);

$orderStatuses = ['PENDING', 'IN_PROCESS', 'SHIPPED', 'DELIVERED'];
$orderCounts = [0, 0, 0, 0]; // Initialize with zero counts for each status

while ($orderData = $orderResult->fetch_assoc()) {
    $statusIndex = array_search($orderData['status'], $orderStatuses);
    if ($statusIndex !== false) {
        $orderCounts[$statusIndex] = (int)$orderData['statusCount'];
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
    
    <!--======== CSS ========-->
    <link rel="stylesheet" href="style.css">
     
    <!--===== Iconscout CSS =====-->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="styles.css">
    <title>Super_Admin Dashboard Panel</title>

    <!-- ApexCharts Library -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body>

    <section class="dashboard">

        <div class="dash-content">
            <div class="overview">
                <div class="title">
                    <i class="uil uil-tachometer-fast alt"></i>
                    <span class="text">Dashboard</span>
                </div>

                <div class="cards">
                    <!-- Card for Total Products -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Total Products</h3>
                        </div>
                        <div class="card-body">
                            <span class="card-count"><?php echo $totalProducts; ?></span>
                        </div>
                    </div>

                    <!-- Card for Total Quotations and Amount Quoted -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Total Quotations</h3>
                        </div>
                        <div class="card-body">
                            <span class="card-count"><?php echo $totalQuotations; ?></span>
                            <br>
                            <h3>Total Amount Quoted</h3>
                            <span class="card-count"><?php echo number_format($totalQuotedAmount, 2); ?></span>
                        </div>
                    </div>
                    
                    <!-- Card for Total Invoices and Invoice Amount -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Total Invoices</h3>
                        </div>
                        <div class="card-body">
                            <span class="card-count"><?php echo $totalInvoices; ?></span>
                            <br>
                            <h3>Total Invoice Amount</h3>
                            <span class="card-count"><?php echo number_format($totalInvoiceAmount, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar Chart for Order Status -->
            <div id="order-status-chart" class="chart-container"></div>
            <div id="No-of-users" class = "chart-container"></div>
        </div>
    </section>

    <script>

        
        var options = {
            chart: {
                type: 'bar',
                height: 400, // Smaller size for the chart
                width: 800 // Adjusted to fill the space below the cards
            },
            series: [{
                name: 'Orders',
                data: <?php echo json_encode($orderCounts); ?>
            }],
            xaxis: {
                categories: <?php echo json_encode($orderStatuses); ?>,
                title: {
                    text: 'Order Status'
                }
            },
            yaxis: {
                title: {
                    text: 'Number of Orders'
                },
                min: 0, // Ensure Y-axis starts from 0
            },
            title: {
                text: '',
                align: 'center'
            }
        };

        var chart = new ApexCharts(document.querySelector("#order-status-chart"), options);
        chart.render();


            // Data fetched from PHP (using PHP echo statements to insert values dynamically)
    var totalCustomers = <?php echo $totalCustomers; ?>;
    var totalFreelancers = <?php echo $totalFreelancers; ?>;
    var totalUsers = <?php echo $totalUsers; ?>;

                // ApexCharts configuration for the second chart
                var userOptions = {
            chart: {
                type: 'bar',
                height: 400,
                width: 800,
            },
            series: [{
                name: 'Count',
                data: [totalCustomers, totalFreelancers, totalUsers]
            }],
            xaxis: {
                categories: ['Customers', 'Freelancers', 'Admins']
            },
            title: {
                text: '',
                align: 'center'
            },
            colors: ['#FF5733', '#33FF57', '#3357FF']
        };

        // Create the second chart
        var userChart = new ApexCharts(document.querySelector("#No-of-users"), userOptions);
        userChart.render();
    </script>
</body>
</html>

<!-- Additional CSS to style the cards and chart -->
<style>

.dashboard{
    margin-left:50px;
}

    /* Card container styling */
    .cards {
        display: flex;
        gap: 20px;
        justify-content: flex-start;
        margin-top: 20px;
    }

    /* Card styling */
    .card {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        padding: 20px;
        width: 250px;
        text-align: center;
    }

    /* Card header styling */
    .card-header h3 {
        font-size: 18px;
        margin-bottom: 10px;
        color: #333;
    }

    /* Card count styling */
    .card-body .card-count {
        font-size: 30px;
        font-weight: bold;
        color: #4CAF50; /* Green color for count */
    }


</style>