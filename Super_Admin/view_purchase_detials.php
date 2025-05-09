<?php
session_start();
include 'admin_navbar.php';
// Database connection
$host = '127.0.0.1';
$username = 'root';
$password = ''; // Update this with your database password
$database = 'sales_management';
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch purchase details for the logged-in user
$query = "SELECT * FROM purchase_details WHERE super_admin_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$purchaseDetails = [];
while ($row = $result->fetch_assoc()) {
    $purchaseDetails[] = $row;
}
$stmt->close();

$conn->close();

// Function to generate PDF (uses FPDF library)
function generatePDF($purchase, $items) {
    require('fpdf/fpdf.php'); // Include FPDF library

    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Purchase Details', 0, 1, 'C');

    // Purchase details
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(40, 10, 'Purchase ID: ' . $purchase['id'], 0, 1);
    $pdf->Cell(40, 10, 'Distributor ID: ' . $purchase['distributor_id'], 0, 1);
    $pdf->Cell(40, 10, 'Total Amount: ' . $purchase['total_amount'], 0, 1);
    $pdf->Cell(40, 10, 'Total Tax: ' . $purchase['total_tax'], 0, 1);
    $pdf->Cell(40, 10, 'Created At: ' . $purchase['created_at'], 0, 1);

    // Items table
    if (!empty($items)) {
        $pdf->Ln(10);
        $pdf->Cell(40, 10, 'Purchase Items:', 0, 1);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(50, 10, 'Product Name', 1);
        $pdf->Cell(20, 10, 'Qty', 1);
        $pdf->Cell(30, 10, 'Price', 1);
        $pdf->Cell(30, 10, 'Tax', 1);
        $pdf->Cell(30, 10, 'Total', 1);
        $pdf->Cell(30, 10, 'Tax Name', 1);
        $pdf->Ln();
        $pdf->SetFont('Arial', '', 12);

        foreach ($items as $item) {
            $pdf->Cell(50, 10, $item['product_name'], 1);
            $pdf->Cell(20, 10, $item['quantity'], 1);
            $pdf->Cell(30, 10, $item['price'], 1);
            $pdf->Cell(30, 10, $item['tax'], 1);
            $pdf->Cell(30, 10, $item['total'], 1);
            $pdf->Cell(30, 10, $item['tax_name'], 1);
            $pdf->Ln();
        }
    }

    $fileName = 'Purchase_' . $purchase['id'] . '.pdf';
    $pdf->Output('D', $fileName); // Download PDF
    exit();
}

// Handle PDF generation request
if (isset($_GET['download_pdf']) && isset($_GET['purchase_id'])) {
    $purchaseId = intval($_GET['purchase_id']);
    $selectedPurchase = null;
    $selectedItems = [];

    foreach ($purchaseDetails as $purchase) {
        if ($purchase['id'] == $purchaseId) {
            $selectedPurchase = $purchase;
            break;
        }
    }

    if ($selectedPurchase) {
        // Fetch purchase items for the selected purchase
        $conn = new mysqli($host, $username, $password, $database);
        $query = "SELECT * FROM purchase_items WHERE purchase_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $purchaseId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $selectedItems[] = $row;
        }
        $stmt->close();
        $conn->close();

        generatePDF($selectedPurchase, $selectedItems);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Details</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --info-color: #4895ef;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            color: #4a4a4a;
            margin-left: 350px;

        }

        .container-fluid {
            padding: 20px;
            margin-top: 20px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 25px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h2 {
            color: var(--dark-color);
            margin-bottom: 25px;
            font-weight: 700;
            position: relative;
            padding-bottom: 10px;
        }

        h2:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--accent-color);
        }

        .table {
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: 600;
            border-bottom: 2px solid #e9ecef;
            padding: 12px 15px;
            position: sticky;
            top: 0;
        }

        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
            border-top: 1px solid #e9ecef;
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .btn {
            font-size: 0.85rem;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: none;
            border: none;
        }

        .btn-info {
            background-color: var(--info-color);
        }

        .btn-info:hover {
            background-color: #3a7bd5;
            transform: translateY(-2px);
        }

        .no-purchases {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        .no-purchases i {
            font-size: 50px;
            color: #dee2e6;
            margin-bottom: 15px;
        }

        .badge {
            padding: 6px 10px;
            font-weight: 500;
            border-radius: 20px;
            font-size: 0.75rem;
        }

        .badge-primary {
            background-color: var(--primary-color);
        }

        .action-buttons .btn {
            margin-right: 5px;
        }

        .action-buttons .btn:last-child {
            margin-right: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-completed {
            background-color: #e6f7ee;
            color: #00a854;
        }

        .status-pending {
            background-color: #fff7e6;
            color: #fa8c16;
        }

        .status-cancelled {
            background-color: #fff1f0;
            color: #f5222d;
        }

        @media (max-width: 768px) {
            .table-responsive {
                border: none;
            }
            
            .table thead {
                display: none;
            }
            
            .table tbody tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 10px;
            }
            
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none;
                padding: 8px 10px;
            }
            
            .table tbody td:before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--dark-color);
                margin-right: 15px;
            }
            
            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Purchase Details</h3>
                    <div>
                        <span class="badge badge-primary">
                            Total Purchases: <?php echo count($purchaseDetails); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($purchaseDetails)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Distributor</th>
                                    <th>Total Amount</th>
                                    <th>Total Tax</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($purchaseDetails as $purchase): ?>
                                    <tr>
                                        <td data-label="ID">#<?php echo htmlspecialchars($purchase['id']); ?></td>
                                        <td data-label="Distributor"><?php echo htmlspecialchars($purchase['distributor_id']); ?></td>
                                        <td data-label="Amount">₹<?php echo number_format(htmlspecialchars($purchase['total_amount']), 2); ?></td>
                                        <td data-label="Tax">₹<?php echo number_format(htmlspecialchars($purchase['total_tax']), 2); ?></td>
                                        <td data-label="Date"><?php echo date('d M Y', strtotime(htmlspecialchars($purchase['created_at']))); ?></td>
                                        <td data-label="Actions" class="action-buttons">
                                            <a href="download_purchase.php?download_pdf=1&purchase_id=<?php echo $purchase['id']; ?>" 
                                               class="btn btn-info btn-sm" 
                                               title="Download Invoice">
                                                <i class="fas fa-file-pdf"></i> Download
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-purchases">
                            <i class="fas fa-box-open"></i>
                            <h4>No Purchases Found</h4>
                            <p>You haven't made any purchases yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script>
    // Add animation to table rows
    $(document).ready(function() {
        $('table tbody tr').each(function(i) {
            $(this).delay(i * 100).animate({
                opacity: 1
            }, 200);
        });
    });
</script>
</body>
</html>