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
            CustomerDistributor.companyName AS customer_name, 
            invoices.total_amount, 
            invoices.total_tax, 
            invoices.created_at, 
            CustomerDistributor.address AS customer_address, 
            CustomerDistributor.gstNo AS customer_gstNo  
        FROM invoices
        INNER JOIN CustomerDistributor ON invoices.customer_id = CustomerDistributor.id
        WHERE invoices.id = '$invoice_id'";
    $invoiceResult = $conn->query($invoiceQuery);
    $invoice = $invoiceResult->fetch_assoc();

    // Fetch invoice items
    $itemQuery = "
        SELECT 
            product_name, 
            quantity, 
            price, 
            total, 
            tax, 
            tax_name,
            (price * quantity) AS subtotal,
            ((price * quantity * tax )/ 100) AS tax_amount
        FROM invoice_items
        WHERE invoice_id = '$invoice_id'";
    $itemResult = $conn->query($itemQuery);


    function addNewPage($pdf) {
        $pdf->AddPage();
        $pdf->Rect(5, 5, 200, 287);}
    // Create the PDF document
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->Rect(5, 5, 200, 287); // Adjust dimensions as needed (A4 size with a margin)

    
    // **Header Section**
    $pdf->Image('C:\xampp\htdocs\AVNS\Customer\fpdf\download.png', 7, 10, 50);
    $pdf->Rect(6, 6, 198, 55); // Adjust dimensions as needed

    $pdf->SetFont('Arial', 'B', 14);
    $pdf->SetXY(50, 10);
    $pdf->MultiCell(100, 5, "AVNS TECHNOSOFT\nOffice No 236, 2nd Floor, Vision9\nKunal Icon Road, Pimple Saudagar\nPune Maharashtra 411027 India\nGST No: 27BDUPG0727Q1ZV\nMail Id: accounts@avnstechnosoft.com\nWebsite: avnstechnosoft.com\nOffice No: 8237165766", 0, 'C');
    $pdf->SetFont('Arial', 'B', 18);
    $pdf->SetXY(150, 10);
    $pdf->Cell(40, 10, 'TAX INVOICE', 0, 1, 'R');
    $pdf->Ln(30);

    // **Invoice Details Section**
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Invoice ID: ' . $invoice['invoice_id'], 0, 1);
    
    // Displaying created_at (Invoice Date) on the same line as Invoice ID, right aligned
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(150, $pdf->GetY() - 10); // Adjust X position to align with the right side of the rectangle
    $pdf->Cell(40, 10, 'Invoice Date: ' . date('d-m-Y', strtotime($invoice['created_at'])), 0, 1, 'R');
    $pdf->Ln(5); // Add some spacing before next section

    // Add a rectangle around the "Bill To" section
    $pdf->Rect(6, $pdf->GetY(), 198, 55); // Adjust dimensions and position as needed

    // "Bill To" Section Header
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->SetXY(12, $pdf->GetY() + 2); // Adjust position inside the rectangle
    $pdf->Cell(0, 10, 'Bill To:', 0, 1);

    // Customer details inside the rectangle
    $pdf->SetFont('Arial', '', 12);
    $pdf->SetXY(12, $pdf->GetY()); // Adjust for proper spacing

    // Customer Name (Bold)
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, $invoice['customer_name'], 0, 1, 'L');

    // Customer Address (Wrapped)
    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(190, 6, $invoice['customer_address'], 0, 'L');

    // GSTIN (Bold)
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, 'GSTIN: ' . $invoice['customer_gstNo'], 0, 1, 'L');

    // Adjust Y position after rectangle
    $pdf->SetY($pdf->GetY() + 5);

    // Add space between the Bill To section and table
    $pdf->Ln(5); // This adds a space between the Bill To section and table

// **Table and Summary**
$pdf->SetXY(5, $pdf->GetY() + 2); // Move to the position after the rectangle's top line
    
// Table Header
$pdf->SetFont('Arial', 'B', 12);
$colWidths = [50, 20, 30, 30, 35, 35];

// Starting X and Y position
$x = 5; // Initial X position
$y = 130; // Initial Y position (starting point for table)

$pdf->SetFont('Arial', 'B', 12); // Set the font for the header

// Table Header with X, Y positioning
$pdf->SetXY($x, $y); // Set the initial X and Y coordinates for the first header row
$pdf->Cell($colWidths[0], 10, 'Item & Description', 1, 0, 'C');
$pdf->SetXY($x + $colWidths[0], $y); // Move to the next column's X position
$pdf->Cell($colWidths[1], 10, 'Qty', 1, 0, 'C');
$pdf->SetXY($x + $colWidths[0] + $colWidths[1], $y); // Update X for next column
$pdf->Cell($colWidths[2], 10, 'Price', 1, 0, 'C');
$pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2], $y); 
$pdf->Cell($colWidths[3], 10, 'Tax Name', 1, 0, 'C');
$pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3], $y); 
$pdf->Cell($colWidths[4], 10, 'Tax', 1, 0, 'C');
$pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3] + $colWidths[4], $y); 
$pdf->Cell($colWidths[5], 10, 'Total', 1, 1, 'C');

// Add invoice items data (You can similarly set the X and Y positions for each row)
$pdf->SetFont('Arial', '', 12); // Set font for the data rows
$itemY = $y + 10; // Move the Y position down for data rows
$totalTax = 0; // Initialize total tax
$totalAmount = 0; // Initialize total amount

while ($item = $itemResult->fetch_assoc()) {
    // Set X, Y for each row
    $pdf->SetXY($x, $itemY);
    $pdf->Cell($colWidths[0], 10, $item['product_name'], 1, 0, 'L');

    $pdf->SetXY($x + $colWidths[0], $itemY);
    $pdf->Cell($colWidths[1], 10, $item['quantity'], 1, 0, 'C');

    $pdf->SetXY($x + $colWidths[0] + $colWidths[1], $itemY);
    $pdf->Cell($colWidths[2], 10, "INR " . number_format($item['price'], 2), 1, 0, 'R');

    $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2], $itemY);
    $pdf->Cell($colWidths[3], 10, $item['tax_name'], 1, 0, 'C');

    $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3], $itemY);
    $pdf->Cell($colWidths[4], 10, 'INR ' . number_format($item['tax'], 2), 1, 0, 'R');

    $pdf->SetXY($x + $colWidths[0] + $colWidths[1] + $colWidths[2] + $colWidths[3] + $colWidths[4], $itemY);
    $pdf->Cell($colWidths[5], 10, 'INR ' . number_format($item['subtotal'], 2), 1, 1, 'R');

    // Add tax and amount to totals
    $totalTax += $item['tax'];
    $totalAmount += $item['total'];

    // Move to next row (adjust Y position)
    $itemY += 10; // You can adjust the space between rows here
}
    // **Summary Section**
// **Summary Section**
$pdf->Ln(5); // Add space
$summaryX = 145; // X position for the summary rectangle
$summaryY = $pdf->GetY(); // Y position for the summary rectangle
$summaryWidth = 60; // Width of the rectangle
$summaryHeight = 45; // Height of the rectangle

// Draw the rectangle for the Summary Section
$pdf->Rect($summaryX, $summaryY, $summaryWidth, $summaryHeight);

// Add content inside the rectangle
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY($summaryX + 5, $summaryY + 5); // Adjust position inside the rectangle
$pdf->Cell(0, 10, 'Summary:', 0, 1);

$pdf->SetFont('Arial', '', 12);
$pdf->SetX($summaryX + 5); // Align text inside the rectangle
$pdf->Cell(0, 10, 'Sub Total: INR ' . number_format($invoice['total_amount'], 2), 0, 1);
$pdf->SetX($summaryX + 5);
$pdf->Cell(0, 10, 'Total Tax: INR ' . number_format($totalTax, 2), 0, 1);
$pdf->SetX($summaryX + 5);
$pdf->Cell(0, 10, 'Total: INR ' . number_format($totalAmount, 2), 0, 1);


    // **Authorized Signature Section**
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


    // **Declaration Section**
    $declarationText = "Declaration:
We hereby confirm that hardware or software supplied vide this invoice is
acquired in a subsequent transfer and it is transferred without any modification and tax has been deducted under section 195 deposited under PAN NO:
BDUPG07270 by the PAN holder. Hence no TDS is deducted on this invoice as per the notification no: 21/2012 (F. No 142/10/2012-50 1323 (E), dated 13-06-2012 issued by the Ministry of Finance (CBDT).
Cheque should be made by the name of AVNS TECHNOSOFT by the buyer.

BANK ACCOUNT DETAILS:
Name of Account: AVNS TECHNOSOFT
Name of Bank: HDFC Bank Ltd.
Bank Account No: 50200046575231
RTGS/NEFT/IFSC: HDFC0003981
MICR Code: 411240052
Account Type: Current A/c
Branch Name: Aundh 2
Branch Address: Nagras Tower, Building A, Shop No. 2, S. No.
162-4A/SA, Naras Road Aundh.

Terms & Conditions:
1. Terms of Payment: 100% Advance along with the PO.
2. We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.
3. We hereby confirm that Hardware or Software supplied this invoice are
sold without any modification.
4. The Company has already deducted TDS under section 194J of the
Income Tax on these software & Hardware and made necessary arrangement for remitting the same as per timeline prescribed by Income Tax.";

    $pdf->SetFont('Arial', '', 10); // Set a smaller font size for the declaration text
    $pdf->SetXY(10, $itemY + 10); // Position left of the summary section and below the table
    $pdf->MultiCell(130, 6, $declarationText, 0, 'L'); // Wrap the declaration text neatly

    // Output the PDF
    $pdf->Output();
}
