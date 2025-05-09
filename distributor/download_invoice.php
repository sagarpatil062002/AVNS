<?php
require('fpdf/fpdf.php'); // Include the FPDF library

if (isset($_GET['invoice_id'])) {
    $invoice_id = $_GET['invoice_id'];

    // Database connection
    include('Config.php');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch invoice details
    $invoiceQuery = "
        SELECT 
            invoices.id AS invoice_id, 
            distributor.companyName AS customer_name, 
            invoices.total_amount, 
            invoices.total_tax, 
            invoices.created_at, 
            distributor.address AS customer_address, 
            distributor.gstNo AS customer_gstNo 
        FROM invoices
        INNER JOIN distributor ON invoices.customer_id = distributor.id
        WHERE invoices.id = '$invoice_id'";
    $invoiceResult = $conn->query($invoiceQuery);

    if ($invoiceResult->num_rows === 0) {
        die("Invoice not found.");
    }

    $invoice = $invoiceResult->fetch_assoc();

    // Fetch invoice items
    $itemQuery = "
        SELECT 
            product_name, 
            quantity, 
            price, 
            total, 
            tax, 
            tax_name 
        FROM invoice_items
        WHERE invoice_id = '$invoice_id'";
    $itemResult = $conn->query($itemQuery);

    // Create the PDF document
    $pdf = new FPDF();
    $pdf->AddPage();

    // Set font
    $pdf->SetFont('Arial', 'B', 16);

    // Add invoice title
    $pdf->Cell(0, 10, 'Invoice Details', 0, 1, 'C');

    // Invoice Details
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Invoice ID: ' . $invoice['invoice_id'], 0, 1);
    $pdf->Cell(0, 10, 'Customer Name: ' . $invoice['customer_name'], 0, 1);
    $pdf->Cell(0, 10, 'Customer Address: ' . $invoice['customer_address'], 0, 1);
    $pdf->Cell(0, 10, 'GST No: ' . $invoice['customer_gstNo'], 0, 1);
    $pdf->Cell(0, 10, 'Total Amount: ' . number_format($invoice['total_amount'], 2), 0, 1);
    $pdf->Cell(0, 10, 'Total Tax: ' . number_format($invoice['total_tax'], 2), 0, 1);
    $pdf->Cell(0, 10, 'Created At: ' . $invoice['created_at'], 0, 1);
    $pdf->Ln(10); // Add a line break

    // Add table header for items
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 10, 'Product Name', 1);
    $pdf->Cell(30, 10, 'Quantity', 1);
    $pdf->Cell(30, 10, 'Price', 1);
    $pdf->Cell(30, 10, 'Tax', 1);
    $pdf->Cell(30, 10, 'Total', 1);
    $pdf->Cell(50, 10, 'Tax Name', 1);
    $pdf->Ln();

    // Add invoice items data
    $pdf->SetFont('Arial', '', 12);
    while ($item = $itemResult->fetch_assoc()) {
        $pdf->Cell(50, 10, $item['product_name'], 1);
        $pdf->Cell(30, 10, $item['quantity'], 1);
        $pdf->Cell(30, 10, number_format($item['price'], 2), 1);
        $pdf->Cell(30, 10, number_format($item['tax'], 2), 1);
        $pdf->Cell(30, 10, number_format($item['total'], 2), 1);
        $pdf->Cell(50, 10, $item['tax_name'], 1);
        $pdf->Ln();
    }

    // Output the PDF as a download
    $pdf->Output('D', 'invoice_' . $invoice['invoice_id'] . '.pdf');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Invoice</title>
</head>
<body>
    <h1>Generate Invoice PDF</h1>
    <form action="invoice.php" method="get">
        <label for="invoice_id">Enter Invoice ID:</label>
        <input type="text" id="invoice_id" name="invoice_id" required>
        <button type="submit">Download Invoice</button>
    </form>

    <h2>Distributor Details</h2>
    <table border="1">
        <thead>
            <tr>
                <th>Company Name</th>
                <th>Email</th>
                <th>GST Type</th>
                <th>GST No</th>
                <th>Size</th>
                <th>Address</th>
                <th>Owner Name</th>
                <th>Mobile No</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $conn = new mysqli('localhost', 'root', '', 'sales_management');
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }
            $query = "SELECT * FROM distributor";
            $result = $conn->query($query);
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['companyName']}</td>
                    <td>{$row['mailId']}</td>
                    <td>{$row['gstType']}</td>
                    <td>{$row['gstNo']}</td>
                    <td>{$row['size']}</td>
                    <td>{$row['address']}</td>
                    <td>{$row['ownerName']}</td>
                    <td>{$row['mobileNo']}</td>
                </tr>";
            }
            $conn->close();
            ?>
        </tbody>
    </table>
</body>
</html>
