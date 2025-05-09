<?php
session_start();
include 'navbar.php';
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
    <link rel="stylesheet" href="styles.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            margin-top: 50px;
            background: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-right:20px;
        }

        h2 {
            text-align: center;
            color: #343a40;
            margin-bottom: 30px;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #007bff;
            color: #ffffff;
            text-align: center;
        }

        .table tbody td {
            text-align: center;
        }

        .btn {
            font-size: 0.9rem;
            padding: 5px 10px;
        }

        .btn-info {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-info:hover {
            background-color: #0056b3;
            border-color: #004085;
        }

        p {
            text-align: center;
            color: #6c757d;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Purchase Details</h2>
    <?php if (!empty($purchaseDetails)): ?>
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>ID</th>
                <th>Distributor ID</th>
                <th>Total Amount</th>
                <th>Total Tax</th>
                <th>Created At</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($purchaseDetails as $purchase): ?>
                <tr>
                    <td><?php echo htmlspecialchars($purchase['id']); ?></td>
                    <td><?php echo htmlspecialchars($purchase['distributor_id']); ?></td>
                    <td><?php echo htmlspecialchars($purchase['total_amount']); ?></td>
                    <td><?php echo htmlspecialchars($purchase['total_tax']); ?></td>
                    <td><?php echo htmlspecialchars($purchase['created_at']); ?></td>
                    <td>
                    <a href="download_purchase.php?download_pdf=1&purchase_id=<?php echo $purchase['id']; ?>" class="btn btn-info btn-sm">
    Download PDF
</a>


                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No purchases found.</p>
    <?php endif; ?>
</div>
</body>
</html>
