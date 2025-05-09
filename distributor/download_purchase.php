<?php
require('fpdf/fpdf.php');

if (isset($_GET['purchase_id'])) {
    $purchase_id = $_GET['purchase_id'];
    include('Config.php');

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch purchase details
    $purchaseQuery = "SELECT purchase_details.id AS purchase_id, 
                     purchase_details.total_amount, purchase_details.total_tax, 
                     purchase_details.created_at, distributor.companyName AS distributor_name, 
                     distributor.address AS distributor_address, distributor.gstNo AS distributor_gstNo  
                     FROM purchase_details LEFT JOIN distributor 
                     ON purchase_details.distributor_id = distributor.id
                     WHERE purchase_details.id = '$purchase_id'";
    $purchaseResult = $conn->query($purchaseQuery);

    if (!$purchaseResult || $purchaseResult->num_rows == 0) {
        die("Invalid Purchase ID or no data found.");
    }
    $purchase = $purchaseResult->fetch_assoc();

    // Fetch purchase items
    $itemQuery = "SELECT product_name, quantity, price, tax, total, tax_name
                 FROM purchase_items WHERE purchase_id = '$purchase_id'";
    $itemResult = $conn->query($itemQuery);

    if (!$itemResult || $itemResult->num_rows == 0) {
        die("No items found for this purchase.");
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
    $pdf->MultiCell(100, 5, "AVNS TECHNOSOFT\nOffice No 236, 2nd Floor, Vision9\nKunal Icon Road, Pimple Saudagar\nPune Maharashtra 411027 India\nGST No: 27BDUPG0727Q1ZV\nMail Id: accounts@avnstechnosoft.com\nWebsite: avnstechnosoft.com\nOffice No: 8237165766", 0, 'C');
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetXY(150, 10);
    $pdf->Cell(54, 10, 'PURCHASE ORDER', 0, 1, 'R');
    $pdf->Ln(30);

    // Purchase Details
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Purchase ID: ' . $purchase['purchase_id'], 0, 1);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(150, $pdf->GetY() - 10);
    $pdf->Cell(40, 10, 'Purchase Date: ' . date('d-m-Y', strtotime($purchase['created_at'])), 0, 1, 'R');
    $pdf->Ln(5);

    // Distributor Details
    $pdf->Rect(6, $pdf->GetY(), 198, 55);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(12, $pdf->GetY() + 2);
    $pdf->Cell(0, 10, 'Distributor Details:', 0, 1);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, $purchase['distributor_name'], 0, 1, 'L');
    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(190, 6, $purchase['distributor_address'], 0, 'L');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, 'GSTIN: ' . $purchase['distributor_gstNo'], 0, 1, 'L');
    $pdf->Ln(5);

    // Table Section
    $pdf->SetFont('Arial', 'B', 12);
    $colWidths = [50, 20, 30, 30, 30, 30];
    $x = 6; $y = 125;
    $pdf->SetXY($x, $y);
    $pdf->Cell($colWidths[0], 10, 'Item Name', 1, 0, 'C');
    $pdf->Cell($colWidths[1], 10, 'Qty', 1, 0, 'C');
    $pdf->Cell($colWidths[2], 10, 'Price', 1, 0, 'C');
    $pdf->Cell($colWidths[3], 10, 'Tax', 1, 0, 'C');
    $pdf->Cell($colWidths[4], 10, 'Tax Name', 1, 0, 'C');
    $pdf->Cell($colWidths[5], 10, 'Total', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 12);
    $totalAmount = 0;
    $itemY = $y + 10;

    while ($item = $itemResult->fetch_assoc()) {
        $pdf->SetXY($x, $itemY);
        $pdf->MultiCell($colWidths[0], 10, $item['product_name'], 1, 'L');
        $currentY = $pdf->GetY();
        
        $itemTotal = $item['quantity'] * $item['price'];
        
        $pdf->SetXY($x + $colWidths[0], $itemY);
        $pdf->Cell($colWidths[1], ($currentY - $itemY), $item['quantity'], 1, 0, 'C');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1], $itemY);
        $pdf->Cell($colWidths[2], ($currentY - $itemY), 'INR ' . number_format($item['price'], 2), 1, 0, 'C');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2], $itemY);
        $pdf->Cell($colWidths[3], ($currentY - $itemY), 'INR ' . number_format($item['tax'], 2), 1, 0, 'C');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3], $itemY);
        $pdf->Cell($colWidths[4], ($currentY - $itemY), $item['tax_name'], 1, 0, 'C');
        
        $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3] + $colWidths[4], $itemY);
        $pdf->Cell($colWidths[5], ($currentY - $itemY), 'INR ' . number_format($itemTotal, 2), 1, 1, 'C');
        
        $totalAmount += $itemTotal;
        $itemY = $currentY;
    }

    // Summary Section
    $summaryX = 140; $summaryY = $pdf->GetY() + 10;
    $summaryWidth = 65; $summaryHeight = 50;
    $pdf->Rect($summaryX, $summaryY, $summaryWidth, $summaryHeight);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY($summaryX + 5, $summaryY + 5);
    $pdf->Cell(0, 10, 'Summary:', 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetY($summaryY + 15);
    $pdf->Cell(0, 10, 'Sub Total: INR ' . number_format($totalAmount, 2), 0, 1, 'R');
    $pdf->Cell(0, 10, 'Total Tax: INR ' . number_format($purchase['total_tax'], 2), 0, 1, 'R');
    $pdf->Cell(0, 10, 'Grand Total: INR ' . number_format($totalAmount + $purchase['total_tax'], 2), 0, 1, 'R');

    // Signature Section
    $authRectX = 145; $authRectY = $summaryY + $summaryHeight + 10;
    $authRectWidth = 60; $authRectHeight = 45;
    $pdf->Rect($authRectX, $authRectY, $authRectWidth, $authRectHeight);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY($authRectX + 5, $authRectY + 5);
    $pdf->Cell(0, 10, 'Authorized Signature:', 0, 1);
    $signatureImagePath = 'C:\xampp\htdocs\AVNS\Customer\fpdf\authorised signature.png';
    $pdf->Image($signatureImagePath, $authRectX + 10, $authRectY + 15, 40);

    ob_clean();
    $pdf->Output();
} else {
    echo "Purchase ID is not provided.";
}