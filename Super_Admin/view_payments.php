<?php
include('Config.php');
session_start();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$query = "
    SELECT 
        od.id AS orderId,
        cd.companyName AS customerName,
        COALESCE(p.name, od.custom_product_name) AS productName,
        od.quantity,
        od.amount AS unit_price,
        od.payment_status,
        od.createdAt,
        od.updatedAt,
        od.razorpay_payment_id,
        od.razorpay_order_id
    FROM order_details od
    LEFT JOIN customerdistributor cd ON od.customerId = cd.id
    LEFT JOIN product p ON od.productId = p.id
    ORDER BY od.createdAt DESC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Super Admin - Payment Records</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --dark-color: #5a5c69;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .container-fluid {
            padding: 20px 30px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 30px;
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
            border-radius: 10px 10px 0 0 !important;
        }
        
        .card-title {
            font-weight: 700;
            color: var(--dark-color);
            font-size: 1.2rem;
            margin-bottom: 0;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-bottom: none;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #b7b9cc;
            font-weight: 800;
            padding: 1rem;
            vertical-align: middle;
            background-color: #f8f9fc;
        }
        
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #e3e6f0;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.35rem 0.65rem;
            border-radius: 0.35rem;
        }
        
        .badge-paid {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success-color);
        }
        
        .badge-pending {
            background-color: rgba(246, 194, 62, 0.1);
            color: var(--warning-color);
        }
        
        .badge-failed {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger-color);
        }
        
        .amount {
            font-weight: 700;
            color: var(--dark-color);
        }
        
        .customer-name {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .product-name {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .date-col {
            min-width: 120px;
        }
        
        .search-container {
            margin-bottom: 20px;
        }
        
        .dropdown-filter {
            min-width: 150px;
        }
        
        .payment-details {
            font-size: 0.8rem;
            color: #858796;
        }
        
        .action-btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .container-fluid {
                padding: 15px;
            }
            
            .table-responsive {
                border: none;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title"><i class="fas fa-credit-card mr-2"></i>Payment Records</h5>
            <div class="d-flex">
                <div class="input-group search-container mr-3" style="max-width: 300px;">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle dropdown-filter" type="button" id="filterDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="filterDropdown">
                        <h6 class="dropdown-header">Payment Status</h6>
                        <a class="dropdown-item" href="#" data-filter="all">All Payments</a>
                        <a class="dropdown-item" href="#" data-filter="paid">Paid</a>
                        <a class="dropdown-item" href="#" data-filter="pending">Pending</a>
                        <a class="dropdown-item" href="#" data-filter="failed">Failed</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover" id="paymentTable">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Status</th>
                                <th class="date-col">Order Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $result->fetch_assoc()): 
                            $badgeClass = '';
                            switch($row['payment_status']) {
                                case 'paid': $badgeClass = 'badge-paid'; break;
                                case 'failed': $badgeClass = 'badge-failed'; break;
                                default: $badgeClass = 'badge-pending'; break;
                            }
                            
                            $paymentDate = ($row['payment_status'] == 'paid' && $row['updatedAt']) ? 
                                date('M d, Y', strtotime($row['updatedAt'])) : '';
                        ?>
                            <tr>
                                <td>#<?= $row['orderId']; ?></td>
                                <td>
                                    <div class="customer-name"><?= htmlspecialchars($row['customerName']); ?></div>
                                    <?php if ($row['razorpay_payment_id']): ?>
                                        <small class="payment-details">PID: <?= substr($row['razorpay_payment_id'], 0, 8) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td class="product-name" title="<?= htmlspecialchars($row['productName']); ?>">
                                    <?= htmlspecialchars($row['productName']); ?>
                                </td>
                                <td class="text-center"><?= $row['quantity']; ?></td>
                                <td class="text-right amount">₹<?= number_format($row['unit_price'], 2); ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $badgeClass; ?>">
                                        <?php if ($row['payment_status'] == 'paid'): ?>
                                            <i class="fas fa-check-circle mr-1"></i>
                                        <?php elseif ($row['payment_status'] == 'failed'): ?>
                                            <i class="fas fa-times-circle mr-1"></i>
                                        <?php else: ?>
                                            <i class="fas fa-clock mr-1"></i>
                                        <?php endif; ?>
                                        <?= ucfirst($row['payment_status']); ?>
                                    </span>
                                    <?php if ($paymentDate): ?>
                                        <div class="payment-details mt-1">
                                            <?= $paymentDate ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="date-col">
                                    <?= date('M d, Y', strtotime($row['createdAt'])); ?>
                                    <div class="payment-details">
                                        <?= date('h:i A', strtotime($row['createdAt'])); ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary action-btn" title="View Details" onclick="viewOrderDetails(<?= $row['orderId']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($row['payment_status'] != 'paid'): ?>
                                        <button class="btn btn-sm btn-outline-success action-btn" title="Mark as Paid" onclick="markAsPaid(<?= $row['orderId']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-secondary action-btn" title="Download Invoice" onclick="downloadInvoice(<?= $row['orderId']; ?>)">
                                        <i class="fas fa-file-invoice"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        Showing <span id="showingCount"><?= $result->num_rows; ?></span> of <?= $result->num_rows; ?> records
                    </div>
                    <nav>
                        <ul class="pagination pagination-sm">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">Previous</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No payment records found</h5>
                    <p class="text-muted">There are currently no payment records in the system.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1" role="dialog" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailsModalLabel">Order Details #<span id="modalOrderId"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Include jQuery, Popper.js, and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    // Search functionality
    $('#searchInput').keyup(function() {
        const searchText = $(this).val().toLowerCase();
        let visibleRows = 0;
        
        $('#paymentTable tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            if (rowText.indexOf(searchText) > -1) {
                $(this).show();
                visibleRows++;
            } else {
                $(this).hide();
            }
        });
        
        $('#showingCount').text(visibleRows);
    });
    
    // Filter functionality
    $('[data-filter]').click(function(e) {
        e.preventDefault();
        const filter = $(this).data('filter');
        $('#filterDropdown').html($(this).html());
        
        // Filter rows based on payment status
        $('#paymentTable tbody tr').each(function() {
            const status = $(this).find('.badge').text().toLowerCase();
            if (filter === 'all' || status.includes(filter)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});

function viewOrderDetails(orderId) {
    $('#modalOrderId').text(orderId);
    $('#orderDetailsContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i><p>Loading order details...</p></div>');
    $('#orderDetailsModal').modal('show');
    
    // In a real implementation, you would fetch details via AJAX here
    // For now, we'll just show a placeholder
    setTimeout(function() {
        $('#orderDetailsContent').html(`
            <div class="alert alert-info">
                Order details for #${orderId} would be loaded here via AJAX in a real implementation.
            </div>
        `);
    }, 1000);
}

function markAsPaid(orderId) {
    if (confirm('Are you sure you want to mark order #' + orderId + ' as paid?')) {
        // In a real implementation, you would make an AJAX call here
        alert('In a real implementation, this would mark order #' + orderId + ' as paid via AJAX');
    }
}

function downloadInvoice(orderId) {
    // In a real implementation, this would generate/download the invoice
    alert('In a real implementation, this would download invoice for order #' + orderId);
}
</script>
</body>
</html>

<?php $conn->close(); ?>