<?php
require('fpdf/fpdf.php'); // Include the FPDF library

if (isset($_GET['quotation_id'])) {
    $quotation_id = $_GET['quotation_id'];

    // Database connection
    include('Config.php');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch quotation details
    $quotationQuery = "
    SELECT 
        qh.quotation_id, 
        cd.companyName AS customer_name, 
        qh.status, 
        qh.createdAt, 
        cd.address AS customer_address, 
        cd.gstNo AS customer_gstNo  
    FROM quotation_header AS qh
    INNER JOIN customerdistributor AS cd ON qh.customerId = cd.id
    WHERE qh.quotation_id = '$quotation_id'";

    $quotationResult = $conn->query($quotationQuery);
    if ($quotationResult->num_rows === 0) {
        die("No quotation found with the given ID.");
    }

    $quotation = $quotationResult->fetch_assoc();

    // Fetch quotation products and tax rate details
    $itemQuery = "
    SELECT 
        p.name AS product_name,  
        qp.quantity, 
        qp.priceOffered AS price, 
        (qp.priceOffered * qp.quantity) AS total,
        tr.tax_percentage AS tax_rate 
    FROM quotation_product AS qp
    INNER JOIN product AS p ON qp.productId = p.id
    LEFT JOIN tax_rates AS tr ON qp.tax_rate_id = tr.id
    WHERE qp.quotation_id = '$quotation_id'";

    $itemResult = $conn->query($itemQuery);

    if ($itemResult->num_rows === 0) {
        die("No products found for the given quotation ID.");
    }

    // Create the PDF document
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->Rect(5, 5, 200, 287); // Adjust dimensions as needed (A4 size with a margin)

    // *Header Section*
    $pdf->Image('C:/xampp/htdocs/AVNS/Customer/fpdf/download.png', 7, 10, 50);
    $pdf->Rect(6, 6, 198, 55); // Adjust dimensions as needed

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY(50, 10);
    $pdf->MultiCell(100, 5, "AVNS TECHNOSOFT\nOffice No 236, 2nd Floor, Vision9\nKunal Icon Road, Pimple Saudagar\nPune Maharashtra 411027 India\nGST No: 27BDUPG0727Q1ZV\nMail Id: accounts@avnstechnosoft.com\nWebsite: avnstechnosoft.com\nOffice No: 8237165766", 0, 'C');
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetXY(150, 10);
    $pdf->Cell(40, 10, 'QUOTATION', 0, 1, 'R');
    $pdf->Ln(30);

    // *Quotation Details Section*
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Quotation ID: ' . $quotation['quotation_id'], 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(150, $pdf->GetY() - 10);
    $pdf->Cell(40, 10, 'Quotation Date: ' . date('d-m-Y', strtotime($quotation['createdAt'])), 0, 1, 'R');
    $pdf->Ln(5);

    // "Bill To" Section
    $pdf->Rect(6, $pdf->GetY(), 198, 55);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(12, $pdf->GetY() + 2);
    $pdf->Cell(0, 10, 'Bill To:', 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, $quotation['customer_name'], 0, 1, 'L');
    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(190, 6, $quotation['customer_address'], 0, 'L');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, 'GSTIN: ' . $quotation['customer_gstNo'], 0, 1, 'L');
    $pdf->Ln(5);

    // *Table and Summary*
    $pdf->SetFont('Arial', 'B', 12);
    $colWidths = [50, 20, 30, 30, 35, 35];
    $x = 5;
    $y = 130; // Use the current Y position dynamically

    $pdf->SetXY($x, $y);
    $pdf->Cell($colWidths[0], 10, 'Item & Description', 1, 0, 'C');
    $pdf->Cell($colWidths[1], 10, 'Qty', 1, 0, 'C');
    $pdf->Cell($colWidths[2], 10, 'Price', 1, 0, 'C');
    $pdf->Cell($colWidths[3], 10, 'Tax Name', 1, 0, 'C');
    $pdf->Cell($colWidths[4], 10, 'Tax', 1, 0, 'C');
    $pdf->Cell($colWidths[5], 10, 'Total', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 12);
    $totalTax = 0;
    $totalAmount = 0;
    $itemY = $y + 10; // Track Y position for each row

    while ($item = $itemResult->fetch_assoc()) {
        // Product name with multi-line support
        $pdf->SetXY($x, $itemY);
        $pdf->MultiCell($colWidths[0], 10, $item['product_name'], 1, 'L');
        $currentY = $pdf->GetY(); // Get new Y position after MultiCell
        
        // Other cells in the row
        $pdf->SetXY($x + $colWidths[0], $itemY);
        $pdf->Cell($colWidths[1], ($currentY - $itemY), $item['quantity'], 1, 0, 'C');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1], $itemY);
        $pdf->Cell($colWidths[2], ($currentY - $itemY), 'INR ' . number_format($item['price'], 2), 1, 0, 'C');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2], $itemY);
        $pdf->Cell($colWidths[3], ($currentY - $itemY), 'GST', 1, 0, 'C');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3], $itemY);
        $pdf->Cell($colWidths[4], ($currentY - $itemY), $item['tax_rate'] . '%', 1, 0, 'C');
        
        $total = $item['quantity'] * $item['price'];
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3] + $colWidths[4], $itemY);
        $pdf->Cell($colWidths[5], ($currentY - $itemY), 'INR ' . number_format($total, 2), 1, 1, 'C');
        
        $tax = ($total * $item['tax_rate']) / 100;
        $totalTax += $tax;
        $totalAmount += $total;
        
        $itemY = $currentY; // Update Y position for next row
    }

    // Summary Section
    $summaryX = 140;
    $summaryY = $pdf->GetY() + 10;
    $summaryWidth = 65;
    $summaryHeight = 50; // Adjust dynamically if required based on content

    // Draw the rectangle for the Summary Section
    $pdf->Rect($summaryX, $summaryY, $summaryWidth, $summaryHeight);

    // Add "Summary" title
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY($summaryX + 5, $summaryY + 5); // Position for the title
    $pdf->Cell(0, 10, 'Summary:', 0, 1, 'L');

    // Add summary details
    $pdf->SetY($summaryY + 15);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Sub Total: INR ' . number_format($totalAmount, 2), 0, 1, 'R');
    $pdf->Cell(0, 10, 'Total Tax: INR ' . number_format($totalTax, 2), 0, 1, 'R');
    $pdf->Cell(0, 10, 'Grand Total: INR ' . number_format($totalAmount + $totalTax, 2), 0, 1, 'R');

    // *Authorized Signature Section*
    $authRectX = 145; // X position for the rectangle
    $authRectY = $summaryY + $summaryHeight + 10; // Y position (below the Summary Section)
    $authRectWidth = 60; // Width of the rectangle
    $authRectHeight = 45; // Height of the rectangle

    // Draw the rectangle for the Authorized Signature Section
    $pdf->Rect($authRectX, $authRectY, $authRectWidth, $authRectHeight);

    // Add the "Authorized Signature" label
    $pdf->SetFont('Arial', 'B', 12); // Bold font
    $pdf->SetXY($authRectX + 5, $authRectY + 5); // Adjust position inside the rectangle
    $pdf->Cell(0, 10, 'Authorized Signature:', 0, 1);

    // Add the signature image inside the rectangle
    $signatureImagePath = 'C:\xampp\htdocs\AVNS\Customer\fpdf\authorised signature.png';
    $pdf->Image($signatureImagePath, $authRectX + 10, $authRectY + 15, 40); // Adjust X, Y, and size as needed

    $pdf->Output();
}