<?php
require('fpdf/fpdf.php'); // Include the FPDF library

session_start();
// Check if the customer is logged in
if (!isset($_SESSION['user_id'])) {
    die("No customer is logged in. Please log in.");
}
$customerId = $_SESSION['user_id'];

if (isset($_GET['invoice_id'])) {
    $invoice_id = $_GET['invoice_id'];

    include('Config.php');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch invoice details
    $invoiceQuery = "
        SELECT 
            invoices.*, 
            CustomerDistributor.companyName, 
            CustomerDistributor.address, 
            CustomerDistributor.gstNo, 
            CustomerDistributor.mobileNo
        FROM invoices
        INNER JOIN CustomerDistributor ON invoices.customer_id = CustomerDistributor.id
        WHERE invoices.id = '$invoice_id' AND invoices.customer_id = '$customerId'";
    $invoiceResult = $conn->query($invoiceQuery);
    
    if ($invoiceResult->num_rows === 0) {
        die("Invoice not found or access denied");
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
    $pdf->Rect(5, 5, 200, 287);

    // Header Section
    $pdf->Image('C:\xampp\htdocs\AVNS\Customer\fpdf\download.png', 7, 10, 50);
    $pdf->Rect(6, 6, 198, 55);

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY(50, 10);
    $pdf->MultiCell(100, 5, "AVNS TECHNOSOFT\nOffice No 236, 2nd Floor, Vision9\nKunal Icon Road, Pimple Saudagar\nPune Maharashtra 411027 India\nGST No: 27BDUPG0727Q1ZV\nMail Id: accounts@avnstechnosoft.com\nWebsite: avnstechnosoft.com\nOffice No: 8237165766", 0, 'C');
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetXY(150, 10);
    $pdf->Cell(40, 10, 'TAX INVOICE', 0, 1, 'R');
    $pdf->Ln(30);

    // Invoice Details Section
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Invoice ID: ' . $invoice_id, 0, 1);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(150, $pdf->GetY() - 10);
    $pdf->Cell(40, 10, 'Invoice Date: ' . date('d-m-Y', strtotime($invoice['created_at'])), 0, 1, 'R');
    $pdf->Ln(5);

    // "Bill To" Section
    $pdf->Rect(6, $pdf->GetY(), 198, 55);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(12, $pdf->GetY() + 2);
    $pdf->Cell(0, 10, 'Bill To:', 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(12, $pdf->GetY());
    $pdf->Cell(0, 6, $invoice['companyName'], 0, 1, 'L');

    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(190, 6, $invoice['address'], 0, 'L');

    if ($invoice['gstNo']) {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 6, 'GSTIN: ' . $invoice['gstNo'], 0, 1, 'L');
    }

    $pdf->SetY($pdf->GetY() + 5);
    $pdf->Ln(5);

    // Table Section
    $pdf->SetXY(5, $pdf->GetY() + 2);
    
    // Table Header
    $pdf->SetFont('Arial', 'B', 12);
    $colWidths = [50, 20, 30, 30, 35, 35];

    $x = 5;
    $y = 130;

    $pdf->SetXY($x, $y);
    $pdf->Cell($colWidths[0], 10, 'Item & Description', 1, 0, 'C');
    $pdf->SetXY($x + $colWidths[0], $y);
    $pdf->Cell($colWidths[1], 10, 'Qty', 1, 0, 'C');
    $pdf->SetXY($x + $colWidths[0] + $colWidths[1], $y);
    $pdf->Cell($colWidths[2], 10, 'Price', 1, 0, 'C');
    $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2], $y); 
    $pdf->Cell($colWidths[3], 10, 'Tax Name', 1, 0, 'C');
    $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3], $y); 
    $pdf->Cell($colWidths[4], 10, 'Tax', 1, 0, 'C');
    $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3] + $colWidths[4], $y); 
    $pdf->Cell($colWidths[5], 10, 'Total', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 12);
    $itemY = $y + 10;
    $totalTax = 0;
    $totalAmount = 0;

    while ($item = $itemResult->fetch_assoc()) {
        $pdf->SetXY($x, $itemY);
        $pdf->MultiCell($colWidths[0], 10, $item['product_name'], 1, 'L');
        $currentY = $pdf->GetY();
        
        $pdf->SetXY($x + $colWidths[0], $itemY);
        $pdf->Cell($colWidths[1], ($currentY - $itemY), $item['quantity'], 1, 0, 'C');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1], $itemY);
        $pdf->Cell($colWidths[2], ($currentY - $itemY), "INR " . number_format($item['price'], 2), 1, 0, 'R');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2], $itemY);
        $pdf->Cell($colWidths[3], ($currentY - $itemY), $item['tax_name'], 1, 0, 'C');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3], $itemY);
        $pdf->Cell($colWidths[4], ($currentY - $itemY), 'INR ' . number_format($item['tax'], 2), 1, 0, 'R');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3] + $colWidths[4], $itemY);
        $pdf->Cell($colWidths[5], ($currentY - $itemY), 'INR ' . number_format($item['total'], 2), 1, 1, 'R');

        $totalTax += $item['tax'];
        $totalAmount += $item['total'];

        $itemY = $currentY;
    }

    // Summary Section
    $pdf->Ln(5);
    $summaryX = 145;
    $summaryY = $pdf->GetY();
    $summaryWidth = 60;
    $summaryHeight = 45;

    $pdf->Rect($summaryX, $summaryY, $summaryWidth, $summaryHeight);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY($summaryX + 5, $summaryY + 5);
    $pdf->Cell(0, 10, 'Summary:', 0, 1);

    $pdf->SetFont('Arial', '', 12);
    $pdf->SetX($summaryX + 5);
    $pdf->Cell(0, 10, 'Sub Total: INR ' . number_format($invoice['total_amount'] - $invoice['total_tax'], 2), 0, 1);
    $pdf->SetX($summaryX + 5);
    $pdf->Cell(0, 10, 'Total Tax: INR ' . number_format($invoice['total_tax'], 2), 0, 1);
    $pdf->SetX($summaryX + 5);
    $pdf->Cell(0, 10, 'Total: INR ' . number_format($invoice['total_amount'], 2), 0, 1);

    // Authorized Signature Section
    $authRectX = 145;
    $authRectY = $summaryY + $summaryHeight + 10;
    $authRectWidth = 60;
    $authRectHeight = 45;

    $pdf->Rect($authRectX, $authRectY, $authRectWidth, $authRectHeight);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY($authRectX + 5, $authRectY + 5);
    $pdf->Cell(0, 10, 'Authorized Signature:', 0, 1);

    $signatureImagePath = 'C:\xampp\htdocs\AVNS\Customer\fpdf\authorised signature.png';
    $pdf->Image($signatureImagePath, $authRectX + 10, $authRectY + 15, 40);

    // Output the PDF
    $pdf->Output('I', 'Invoice_'.$invoice_id.'.pdf');
}