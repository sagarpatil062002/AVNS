<?php
require('fpdf/fpdf.php');

if (isset($_GET['quotation_id'])) {
    $quotation_id = $_GET['quotation_id'];
    include('Config.php');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch quotation details between admin and distributor
    $quotationQuery = "SELECT qh.quotation_id, 
                      d.companyName AS distributor_name, 
                      qh.status, 
                      qh.createdAt, 
                      d.address AS distributor_address, 
                      d.gstNo AS distributor_gstNo, 
                      qh.subject,
                      u.name AS admin_name
                      FROM quotation_header AS qh
                      LEFT JOIN distributor AS d ON qh.distributorId = d.id
                      LEFT JOIN users AS u ON qh.superAdminId = u.id
                      WHERE qh.quotation_id = ?";
    
    $stmt = $conn->prepare($quotationQuery);
    $stmt->bind_param("i", $quotation_id);
    $stmt->execute();
    $quotationResult = $stmt->get_result();
    
    if ($quotationResult->num_rows === 0) {
        die("No quotation found with the given ID.");
    }
    $quotation = $quotationResult->fetch_assoc();

    // Fetch quotation products
    $itemQuery = "SELECT 
                    p.name AS product_name, 
                    p.partNo AS product_part_no,
                    p.hsnNo AS product_hsn,
                    qp.quantity, 
                    qp.priceOffered AS price, 
                    (qp.priceOffered * qp.quantity) AS total, 
                    tr.tax_percentage AS tax_rate,
                    (qp.priceOffered * qp.quantity * tr.tax_percentage / 100) AS tax_amount
                 FROM quotation_product AS qp 
                 INNER JOIN product AS p ON qp.productId = p.id
                 LEFT JOIN tax_rates AS tr ON qp.tax_rate_id = tr.id
                 WHERE qp.quotation_id = ?";
    
    $stmt = $conn->prepare($itemQuery);
    $stmt->bind_param("i", $quotation_id);
    $stmt->execute();
    $itemResult = $stmt->get_result();

    if ($itemResult->num_rows === 0) {
        die("No products found for the given quotation ID.");
    }

    // Create PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->Rect(5, 5, 200, 287);

    // Header Section
    $pdf->Image('C:/xampp/htdocs/AVNS/Customer/fpdf/download.png', 7, 10, 50);
    $pdf->Rect(6, 6, 198, 55);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY(50, 10);
    $pdf->MultiCell(100, 5, "AVNS Solutions\nOffice No 236, 2nd Floor, Vision9\nKunal Icon Road, Pimple Saudagar\nPune Maharashtra 411027 India\nGST No: 27BDUPG0727Q1ZV", 0, 'C');
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetXY(150, 10);
    $pdf->Cell(40, 10, 'QUOTATION', 0, 1, 'R');
    $pdf->Ln(30);

    // Quotation Details
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Quotation ID: ' . $quotation['quotation_id'], 0, 1);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(150, $pdf->GetY() - 10);
    $pdf->Cell(40, 10, 'Date: ' . date('d-m-Y', strtotime($quotation['createdAt'])), 0, 1, 'R');
    $pdf->Ln(5);

    // Distributor Details Section
    $pdf->Rect(6, $pdf->GetY(), 198, 55);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(12, $pdf->GetY() + 2);
    $pdf->Cell(0, 10, 'Distributor Details:', 0, 1);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, $quotation['distributor_name'], 0, 1, 'L');
    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(190, 6, $quotation['distributor_address'], 0, 'L');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, 'GSTIN: ' . $quotation['distributor_gstNo'], 0, 1, 'L');
    $pdf->Ln(5);

    // Subject Section
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Subject: ' . $quotation['subject'], 0, 1);
    $pdf->Ln(5);

    // Table Section
    $pdf->SetFont('Arial', 'B', 12);
    $colWidths = [50, 20, 30, 30, 35, 35];
    $x = 5; $y = 130;
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
    $itemY = $y + 10;

    while ($item = $itemResult->fetch_assoc()) {
        $pdf->SetXY($x, $itemY);
        $pdf->MultiCell($colWidths[0], 10, $item['product_name'], 1, 'L');
        $currentY = $pdf->GetY();
        
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
        $itemY = $currentY;
    }

    // Summary Section
    $summaryX = 140; $summaryY = $pdf->GetY() + 10;
    $summaryWidth = 65; $summaryHeight = 50;
    $pdf->Rect($summaryX, $summaryY, $summaryWidth, $summaryHeight);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY($summaryX + 5, $summaryY + 5);
    $pdf->Cell(0, 10, 'Summary:', 0, 1, 'L');
    $pdf->SetY($summaryY + 15);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Sub Total: INR ' . number_format($totalAmount, 2), 0, 1, 'R');
    $pdf->Cell(0, 10, 'Total Tax: INR ' . number_format($totalTax, 2), 0, 1, 'R');
    $pdf->Cell(0, 10, 'Grand Total: INR ' . number_format($totalAmount + $totalTax, 2), 0, 1, 'R');

    // Signature Section
    $authRectX = 145; $authRectY = $summaryY + $summaryHeight + 10;
    $authRectWidth = 60; $authRectHeight = 45;
    $pdf->Rect($authRectX, $authRectY, $authRectWidth, $authRectHeight);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY($authRectX + 5, $authRectY + 5);
    $pdf->Cell(0, 10, 'Authorized Signature:', 0, 1);
    $signatureImagePath = 'C:\xampp\htdocs\AVNS\Customer\fpdf\authorised signature.png';
    $pdf->Image($signatureImagePath, $authRectX + 10, $authRectY + 15, 40);

    // Output for view mode (inline PDF)
    $pdf->Output('I', 'Quotation_'.$quotation_id.'.pdf');
} else {
    die("Quotation ID is not provided.");
}