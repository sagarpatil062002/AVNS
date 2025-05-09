<?php
include('Config.php');

function generateInvoice($orderId, $customerId, $conn) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get order details with tax information
        $orderQuery = "
            SELECT 
                od.id, od.customerId, od.productId, od.quantity, 
                COALESCE(p.name, od.custom_product_name) AS product_name,
                qp.priceOffered AS price,
                tr.tax_percentage,
                tr.tax_name,
                (od.quantity * qp.priceOffered) AS subtotal,
                (od.quantity * qp.priceOffered * tr.tax_percentage / 100) AS tax_amount,
                (od.quantity * qp.priceOffered * (1 + tr.tax_percentage / 100)) AS total_amount,
                qh.quotation_id
            FROM order_details od
            LEFT JOIN product p ON od.productId = p.id
            LEFT JOIN quotation_product qp ON od.productId = qp.productId
            LEFT JOIN tax_rates tr ON qp.tax_rate_id = tr.id
            LEFT JOIN quotation_header qh ON qp.quotation_id = qh.quotation_id
            WHERE od.id = ? AND od.customerId = ? AND od.payment_status = 'paid'
            GROUP BY od.id
        ";
        
        $stmt = $conn->prepare($orderQuery);
        $stmt->bind_param("ii", $orderId, $customerId);
        $stmt->execute();
        $orderResult = $stmt->get_result();
        
        if ($orderResult->num_rows === 0) {
            throw new Exception("Order not found or not paid");
        }
        
        $order = $orderResult->fetch_assoc();
        
        // Create invoice header
        $invoiceHeader = "
            INSERT INTO invoices (customer_id, total_amount, total_tax, created_at)
            VALUES (?, ?, ?, NOW())
        ";
        
        $stmt = $conn->prepare($invoiceHeader);
        $totalAmount = $order['total_amount'];
        $totalTax = $order['tax_amount'];
        $stmt->bind_param("idd", $customerId, $totalAmount, $totalTax);
        $stmt->execute();
        $invoiceId = $conn->insert_id;
        
        // Create invoice items
        $invoiceItem = "
            INSERT INTO invoice_items (invoice_id, product_name, quantity, price, total, tax, tax_name)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ";
        
        $stmt = $conn->prepare($invoiceItem);
        $stmt->bind_param(
            "isiddds", 
            $invoiceId, 
            $order['product_name'], 
            $order['quantity'], 
            $order['price'], 
            $order['subtotal'], 
            $order['tax_amount'], 
            $order['tax_name']
        );
        $stmt->execute();
        
        // Mark order as invoiced (optional - you could add an invoice_id field to order_details)
        
        // Commit transaction
        $conn->commit();
        
        return $invoiceId;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Invoice generation failed: " . $e->getMessage());
        return false;
    }
}
?>