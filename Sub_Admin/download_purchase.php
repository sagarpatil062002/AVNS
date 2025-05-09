<?php
require('fpdf/fpdf.php'); // Include the FPDF library

if (isset($_GET['purchase_id'])) {
    $purchase_id = $_GET['purchase_id'];

    // Database connection
    include('Config.php');


    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch purchase details
    $purchaseQuery = "
        SELECT 
            purchase_details.id AS purchase_id, 
            purchase_details.total_amount, 
            purchase_details.total_tax, 
            purchase_details.created_at, 
            distributor.companyName AS distributor_name, 
            distributor.address AS distributor_address, 
            distributor.gstNo AS distributor_gstNo  
        FROM purchase_details
        LEFT JOIN distributor ON purchase_details.distributor_id = distributor.id
        WHERE purchase_details.id = '$purchase_id'";
    $purchaseResult = $conn->query($purchaseQuery);

    if (!$purchaseResult || $purchaseResult->num_rows == 0) {
        die("Invalid Purchase ID or no data found.");
    }

    $purchase = $purchaseResult->fetch_assoc();

    // Fetch purchase items
    $itemQuery = "
        SELECT 
            product_name, 
            quantity, 
            price, 
            tax, 
            total, 
            tax_name
        FROM purchase_items
        WHERE purchase_id = '$purchase_id'";
    $itemResult = $conn->query($itemQuery);

    if (!$itemResult || $itemResult->num_rows == 0) {
        die("No items found for this purchase.");
    }

    // Create the PDF document
    $pdf = new FPDF();
    $pdf->AddPage();

    // *Header Section*
    $pdf->Image('C:/xampp/htdocs/AVNS/Customer/fpdf/download.png', 7, 10, 50); // Logo image
    $pdf->Rect(6, 6, 198, 55); // Border around the header section

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY(50, 10);
    $pdf->MultiCell(100, 5, "AVNS TECHNOSOFT\nOffice No 236, 2nd Floor, Vision9\nKunal Icon Road, Pimple Saudagar\nPune Maharashtra 411027 India\nGST No: 27BDUPG0727Q1ZV\nMail Id: accounts@avnstechnosoft.com\nWebsite: avnstechnosoft.com\nOffice No: 8237165766", 0, 'C');
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetXY(150, 10);
    $pdf->Cell(54, 10, 'PURCHASE ORDER', 0, 1, 'R');
    $pdf->Ln(30);

    // *Purchase Details Section*
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Purchase ID: ' . $purchase['purchase_id'], 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(150, $pdf->GetY() - 10);
    $pdf->Cell(40, 10, 'Purchase Date: ' . date('d-m-Y', strtotime($purchase['created_at'])), 0, 1, 'R');
    $pdf->Ln(5);

    // *Distributor Details Section*
    $pdf->Rect(6, $pdf->GetY(), 198, 55); // Border around distributor details
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

    // *Table Section (Items)*
    $pdf->SetFont('Arial', 'B', 12);
    $colWidths = [50, 20, 30, 30, 30, 30];
    $x = 6;
    $y = 125; // Start after Distributor Section

    $pdf->SetXY($x, $y);
    $pdf->Cell($colWidths[0], 10, 'Item Name', 1, 0, 'C');
    $pdf->Cell($colWidths[1], 10, 'Qty', 1, 0, 'C');
    $pdf->Cell($colWidths[2], 10, 'Price', 1, 0, 'C');
    $pdf->Cell($colWidths[3], 10, 'Tax', 1, 0, 'C');
    $pdf->Cell($colWidths[4], 10, 'Tax Name', 1, 0, 'C');
    $pdf->Cell($colWidths[5], 10, 'Total', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 12);
    $totalAmount = 0;

    while ($item = $itemResult->fetch_assoc()) {
        // Calculate the total amount for each item (quantity * price)
        $itemTotal = $item['quantity'] * $item['price'];

        $pdf->SetX($x); // Reset X coordinate for each row
        $pdf->Cell($colWidths[0], 10, $item['product_name'], 1);
        $pdf->Cell($colWidths[1], 10, $item['quantity'], 1, 0, 'C');
        $pdf->Cell($colWidths[2], 10, 'INR ' . number_format($item['price'], 2), 1, 0, 'C');
        $pdf->Cell($colWidths[3], 10, 'INR ' . number_format($item['tax'], 2), 1, 0, 'C');
        $pdf->Cell($colWidths[4], 10, $item['tax_name'], 1, 0, 'C');
        $pdf->Cell($colWidths[5], 10, 'INR ' . number_format($itemTotal, 2), 1, 1, 'C');
        $totalAmount += $itemTotal; // Add to the total amount
    }

    // *Summary Section*
    $summaryX = 140;
    $summaryY = $pdf->GetY() + 10;
    $summaryWidth = 65;
    $summaryHeight = 50;
    $pdf->Rect($summaryX, $summaryY, $summaryWidth, $summaryHeight);

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY($summaryX + 5, $summaryY + 5); // Position for the title
    $pdf->Cell(0, 10, 'Summary:', 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetY($summaryY + 15);
    $pdf->Cell(0, 10, 'Sub Total: INR ' . number_format($totalAmount, 2), 0, 1, 'R');
    $pdf->Cell(0, 10, 'Total Tax: INR ' . number_format($purchase['total_tax'], 2), 0, 1, 'R');
    $pdf->Cell(0, 10, 'Grand Total: INR ' . number_format($totalAmount + $purchase['total_tax'], 2), 0, 1, 'R');

    // *Authorized Signature Section*
    $authRectX = 145;
    $authRectY = $summaryY + $summaryHeight + 10;
    $authRectWidth = 60;
    $authRectHeight = 45;
    $pdf->Rect($authRectX, $authRectY, $authRectWidth, $authRectHeight);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY($authRectX + 5, $authRectY + 5);
    $pdf->Cell(0, 10, 'Authorized Signature:', 0, 1);

    // Optional: Add an image for the signature
    $signatureImagePath = 'C:\xampp\htdocs\AVNS\Customer\fpdf\authorised signature.png';
    $pdf->Image($signatureImagePath, $authRectX + 10, $authRectY + 15, 40); // Adjust X, Y, and size as needed

    // Output the PDF to the browser
    ob_clean(); // Clear any previous output
    $pdf->Output();
} else {
    echo "Purchase ID is not provided.";
}

?>
