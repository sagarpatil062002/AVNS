<?php
require('fpdf/fpdf.php'); // Include the FPDF library

// Start output buffering to avoid any unwanted output before generating the PDF
ob_start();

// Database connection
include('Config.php');


// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure a quotation ID is provided
if (isset($_GET['quotation_id'])) {
    $quotationId = $_GET['quotation_id'];

    // Fetch quotation details
    $sql = "SELECT 
    qh.quotation_id, 
    qh.subject, 
    qh.status, 
    qh.createdAt, 
    qh.updatedAt, 
    d.companyName AS distributor_name,
    d.gstNo AS distributor_gstNo  -- Include gstNo field
FROM quotation_header qh
JOIN distributor d ON qh.distributorId = d.id
WHERE qh.quotation_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $quotationId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $quotation = $result->fetch_assoc();

        // Fetch products associated with the quotation
        $productSql = "SELECT 
                       qp.productId AS product_id,  -- Alias the column to product_id
                       p.name AS product_name, 
                       qp.quantity, 
                       qp.priceOffered
                   FROM quotation_product qp
                   JOIN product p ON qp.productId = p.id
                   WHERE qp.quotation_id = ?";
        $productStmt = $conn->prepare($productSql);
        $productStmt->bind_param("i", $quotationId);
        $productStmt->execute();
        $productResult = $productStmt->get_result();

        // Initialize PDF generation
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        // Header Section
        $pdf->Image('C:/xampp/htdocs/AVNS/Customer/fpdf/download.png', 7, 10, 50);
        $pdf->Rect(6, 6, 198, 55); // Adjust dimensions as needed

        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetXY(50, 10);
        $pdf->MultiCell(100, 5, "AVNS TECHNOSOFT\nOffice No 236, 2nd Floor, Vision9\nKunal Icon Road, Pimple Saudagar\nPune Maharashtra 411027 India\nGST No: 27BDUPG0727Q1ZV\nMail Id: accounts@avnstechnosoft.com\nWebsite: avnstechnosoft.com\nOffice No: 8237165766", 0, 'C');
        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetXY(150, 10);
        $pdf->Cell(40, 10, 'QUOTATION', 0, 1, 'R');
        $pdf->Ln(30);

        // Quotation Details Section
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'Quotation ID: ' . $quotation['quotation_id'], 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY(150, $pdf->GetY() - 10);
        $pdf->Cell(40, 10, 'Quotation Date: ' . date('d-m-Y', strtotime($quotation['createdAt'])), 0, 1, 'R');
        $pdf->Ln(5);

        // Add Subject Section
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 10, 'Subject: ' . $quotation['subject'], 0, 1); // Display subject
        $pdf->Ln(5);

        // Bill To Section
        $pdf->Rect(6, $pdf->GetY(), 198, 40);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY(12, $pdf->GetY() + 2);
        $pdf->Cell(0, 10, 'Bill To:', 0, 1);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 6, $quotation['distributor_name'], 0, 1, 'L');
        $pdf->SetFont('Arial', '', 12);
        $pdf->MultiCell(190, 6, $quotation['distributor_address'], 0, 'L'); // Assuming distributor_address is a field
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 6, 'GSTIN: ' . $quotation['distributor_gstNo'], 0, 1, 'L'); // Display the GST No
        $pdf->Ln(5);

        // Table Section (Products)
        $pdf->SetFont('Arial', 'B', 12);
        $colWidths = [50, 20, 30, 30, 35, 35];
        $x = 5;
        $y = 130; // Start after Bill To section

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

        while ($product = $productResult->fetch_assoc()) {
            $pdf->SetX($x); // Reset X coordinate for each row
            $pdf->Cell($colWidths[0], 10, $product['product_name'], 1);
            $pdf->Cell($colWidths[1], 10, $product['quantity'], 1, 0, 'C');
            $pdf->Cell($colWidths[2], 10, 'INR ' . number_format($product['priceOffered'], 2), 1, 0, 'C');
            $pdf->Cell($colWidths[3], 10, 'GST', 1, 0, 'C');
            $pdf->Cell($colWidths[4], 10, '5%', 1, 0, 'C'); // Example tax rate
            $total = $product['quantity'] * $product['priceOffered'];
            $pdf->Cell($colWidths[5], 10, 'INR ' . number_format($total, 2), 1, 1, 'C');
            $tax = ($total * 5) / 100; // Example tax rate of 5%
            $totalTax += $tax;
            $totalAmount += $total;
        }

        // Summary Section
        $summaryX = 140;
        $summaryY = $pdf->GetY() + 10;
        $summaryWidth = 65;
        $summaryHeight = 50;

        // Draw the rectangle for the Summary Section
        $pdf->Rect($summaryX, $summaryY, $summaryWidth, $summaryHeight);

        // Add Summary Details
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetXY($summaryX + 5, $summaryY + 5); // Position for the title
        $pdf->Cell(0, 10, 'Summary:', 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetY($summaryY + 15);
        $pdf->Cell(0, 10, 'Sub Total: INR ' . number_format($totalAmount, 2), 0, 1, 'R');
        $pdf->Cell(0, 10, 'Total Tax: INR ' . number_format($totalTax, 2), 0, 1, 'R');
        $pdf->Cell(0, 10, 'Grand Total: INR ' . number_format($totalAmount + $totalTax, 2), 0, 1, 'R');
        // *Authorized Signature Section*
    // Define dimensions for Authorized Signature Rectangle
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
    } else {
        $pdf->Cell(0, 10, 'No quotation found with the given ID.', 0, 1, 'C');
    }
}

// Output the generated PDF
ob_end_clean();
$pdf->Output();
?>
