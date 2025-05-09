<?php
// Start session and check authentication
session_start();
// if (!isset($_SESSION['super_admin']) || $_SESSION['super_admin'] !== true) {
//     header("Location: login.php");
//     exit();
// }

// Database connection (MySQLi)
require_once 'config.php';
include('admin_navbar.php');

// Function to fetch data with date range (MySQLi version)
function fetchData($conn, $query, $params = []) {
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $types = str_repeat('s', count($params)); // assumes all params are strings
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

// Function to calculate date range based on period
function getDateRange($period) {
    switch ($period) {
        case 'weekly':
            return [
                'start' => date('Y-m-d', strtotime('-1 week')),
                'end' => date('Y-m-d')
            ];
        case 'yearly':
            return [
                'start' => date('Y-m-d', strtotime('-1 year')),
                'end' => date('Y-m-d')
            ];
        default: // monthly
            return [
                'start' => date('Y-m-d', strtotime('-1 month')),
                'end' => date('Y-m-d')
            ];
    }
}

// Get selected report type from URL or default to 'all'
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'all';

// Get global period if set
$global_period = isset($_GET['global_period']) ? $_GET['global_period'] : 'monthly';

// Define all possible reports
$all_reports = [
    'all' => 'All Reports',
    'customer_quotations' => 'Customer Quotations',
    'distributor_quotations' => 'Distributor Quotations',
    'purchase_orders' => 'Purchase Orders',
    'orders' => 'Customer Orders',
    'invoices' => 'Invoices',
    'subscriptions' => 'Subscriptions',
    'tickets' => 'Support Tickets'
];

// Fetch data only for selected report(s)
$customer_quotations = [];
$distributor_quotations = [];
$purchase_orders = [];
$orders = [];
$invoices = [];
$subscriptions = [];
$tickets = [];

if ($report_type === 'all' || $report_type === 'customer_quotations') {
    $period = ($report_type === 'all') ? $global_period : (isset($_GET['customer_quotations_period']) ? $_GET['customer_quotations_period'] : 'monthly');
    $date_range = getDateRange($period);
    $customer_quotations = fetchData($conn, 
        "SELECT qh.quotation_id, qh.subject, cd.companyName as customer, qh.status, qh.createdAt 
         FROM quotation_header qh
         JOIN customerdistributor cd ON qh.customerId = cd.id
         WHERE qh.createdAt BETWEEN ? AND ? AND qh.distributorId IS NULL
         ORDER BY qh.createdAt DESC", 
        [$date_range['start'], $date_range['end']]);
}

if ($report_type === 'all' || $report_type === 'distributor_quotations') {
    $period = ($report_type === 'all') ? $global_period : (isset($_GET['distributor_quotations_period']) ? $_GET['distributor_quotations_period'] : 'monthly');
    $date_range = getDateRange($period);
    $distributor_quotations = fetchData($conn, 
        "SELECT qh.quotation_id, qh.subject, d.companyName as distributor, qh.status, qh.createdAt 
         FROM quotation_header qh
         JOIN distributor d ON qh.distributorId = d.id
         WHERE qh.createdAt BETWEEN ? AND ? AND qh.customerId IS NULL
         ORDER BY qh.createdAt DESC", 
        [$date_range['start'], $date_range['end']]);
}

if ($report_type === 'all' || $report_type === 'purchase_orders') {
    $period = ($report_type === 'all') ? $global_period : (isset($_GET['purchase_orders_period']) ? $_GET['purchase_orders_period'] : 'monthly');
    $date_range = getDateRange($period);
    $purchase_orders = fetchData($conn, 
        "SELECT pd.id, d.companyName as distributor, pd.total_amount, pd.total_tax, pd.created_at 
         FROM purchase_details pd
         JOIN distributor d ON pd.distributor_id = d.id
         WHERE pd.created_at BETWEEN ? AND ?
         ORDER BY pd.created_at DESC", 
        [$date_range['start'], $date_range['end']]);
}

if ($report_type === 'all' || $report_type === 'orders') {
    $period = ($report_type === 'all') ? $global_period : (isset($_GET['orders_period']) ? $_GET['orders_period'] : 'monthly');
    $date_range = getDateRange($period);
    $orders = fetchData($conn, 
        "SELECT od.id, cd.companyName as customer, p.name as product, od.quantity, od.amount, od.status, od.createdAt 
         FROM order_details od
         JOIN customerdistributor cd ON od.customerId = cd.id
         LEFT JOIN product p ON od.productId = p.id
         WHERE od.createdAt BETWEEN ? AND ?
         ORDER BY od.createdAt DESC", 
        [$date_range['start'], $date_range['end']]);
}

if ($report_type === 'all' || $report_type === 'invoices') {
    $period = ($report_type === 'all') ? $global_period : (isset($_GET['invoices_period']) ? $_GET['invoices_period'] : 'monthly');
    $date_range = getDateRange($period);
    $invoices = fetchData($conn, 
        "SELECT i.id, cd.companyName as customer, i.total_amount, i.total_tax, i.created_at 
         FROM invoices i
         JOIN customerdistributor cd ON i.customer_id = cd.id
         WHERE i.created_at BETWEEN ? AND ?
         ORDER BY i.created_at DESC", 
        [$date_range['start'], $date_range['end']]);
}

if ($report_type === 'all' || $report_type === 'subscriptions') {
    $period = ($report_type === 'all') ? $global_period : (isset($_GET['subscriptions_period']) ? $_GET['subscriptions_period'] : 'monthly');
    $date_range = getDateRange($period);
    $subscriptions = fetchData($conn, 
        "SELECT cs.id, cd.companyName as customer, cp.name as plan, cs.tenure, cs.amount, cs.status, cs.created_at 
         FROM customer_subscription cs
         JOIN customerdistributor cd ON cs.user_id = cd.id
         JOIN customer_plan cp ON cs.plan_id = cp.id
         WHERE cs.created_at BETWEEN ? AND ?
         ORDER BY cs.created_at DESC", 
        [$date_range['start'], $date_range['end']]);
}

if ($report_type === 'all' || $report_type === 'tickets') {
    $period = ($report_type === 'all') ? $global_period : (isset($_GET['tickets_period']) ? $_GET['tickets_period'] : 'monthly');
    $date_range = getDateRange($period);
    $tickets = fetchData($conn, 
        "SELECT t.id, cd.companyName as customer, f.name as freelancer, s.skill_name, t.status, t.priority, t.createdAt 
         FROM ticket t
         JOIN customerdistributor cd ON t.customerId = cd.id
         LEFT JOIN freelancer f ON t.freelancerId = f.id
         JOIN skills s ON t.skill_id = s.id
         WHERE t.createdAt BETWEEN ? AND ?
         ORDER BY t.createdAt DESC", 
        [$date_range['start'], $date_range['end']]);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
    .container{
        margin-left: 350px;
        max-width: 1200px;
    }
    .report-section {
        margin-bottom: 3rem;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        padding: 1.5rem;
    }
    .report-header {
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
    }
    .table-responsive {
        max-height: 400px;
        overflow-y: auto;
    }
    .period-selector {
        margin-bottom: 2rem;
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.25rem;
    }
    .badge-pending {
        background-color: #ffc107;
        color: #212529;
    }
    .badge-approved {
        background-color: #198754;
    }
    .badge-rejected {
        background-color: #dc3545;
    }
    .badge-delivered {
        background-color: #198754;
    }
    .badge-shipped {
        background-color: #0dcaf0;
        color: #212529;
    }
    @media print {
        body * {
            visibility: hidden;
        }
        .container, .container * {
            visibility: visible;
        }
        .container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 20px;
        }
        .no-print, .period-selector, .navbar {
            display: none !important;
        }
        .report-section {
            page-break-after: always;
        }
        .table-responsive {
            overflow: visible;
            max-height: none;
        }
    }
</style>
</head>
<body>
<div class="container">
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="bi bi-bar-chart-line"></i> Super Admin Reports</h1>
            </div>
        </div>

        <!-- Report Type Selector -->
        <div class="period-selector no-print">
            <form method="get" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label for="report_type" class="col-form-label">Select Report:</label>
                    <select name="report_type" id="report_type" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($all_reports as $key => $value): ?>
                            <option value="<?= $key ?>" <?= $report_type === $key ? 'selected' : '' ?>><?= $value ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if ($report_type === 'all'): ?>
                <div class="col-md-4">
                    <label for="global_period" class="col-form-label">Time Period:</label>
                    <select name="global_period" id="global_period" class="form-select" onchange="this.form.submit()">
                        <option value="weekly" <?= $global_period === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                        <option value="monthly" <?= $global_period === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="yearly" <?= $global_period === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="col-md-4">
                    <button type="button" class="btn btn-primary" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                </div>
            </form>
        </div>

        <!-- Customer Quotations Report -->
        <?php if ($report_type === 'all' || $report_type === 'customer_quotations'): ?>
        <div class="report-section" id="customer-quotations">
            <div class="report-header d-flex justify-content-between align-items-center">
                <h3><i class="bi bi-file-earmark-text"></i> Customer Quotations</h3>
                <div>
                    <span class="badge bg-primary"><?= count($customer_quotations) ?> records</span>
                    <?php if ($report_type !== 'all'): ?>
                    <form method="get" class="d-inline ms-2">
                        <input type="hidden" name="report_type" value="<?= $report_type ?>">
                        <select name="customer_quotations_period" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="weekly" <?= ($_GET['customer_quotations_period'] ?? 'monthly') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($_GET['customer_quotations_period'] ?? 'monthly') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= ($_GET['customer_quotations_period'] ?? 'monthly') === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customer_quotations as $quote): ?>
                        <tr>
                            <td><?= $quote['quotation_id'] ?></td>
                            <td><?= htmlspecialchars($quote['subject']) ?></td>
                            <td><?= htmlspecialchars($quote['customer']) ?></td>
                            <td>
                                <?php if ($quote['status'] === 'PENDING'): ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php elseif ($quote['status'] === 'APPROVED'): ?>
                                    <span class="badge badge-approved">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-rejected">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($quote['createdAt'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($customer_quotations)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No customer quotations found for this period</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Distributor Quotations Report -->
        <?php if ($report_type === 'all' || $report_type === 'distributor_quotations'): ?>
        <div class="report-section" id="distributor-quotations">
            <div class="report-header d-flex justify-content-between align-items-center">
                <h3><i class="bi bi-file-earmark-text"></i> Distributor Quotations</h3>
                <div>
                    <span class="badge bg-primary"><?= count($distributor_quotations) ?> records</span>
                    <?php if ($report_type !== 'all'): ?>
                    <form method="get" class="d-inline ms-2">
                        <input type="hidden" name="report_type" value="<?= $report_type ?>">
                        <select name="distributor_quotations_period" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="weekly" <?= ($_GET['distributor_quotations_period'] ?? 'monthly') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($_GET['distributor_quotations_period'] ?? 'monthly') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= ($_GET['distributor_quotations_period'] ?? 'monthly') === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Distributor</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($distributor_quotations as $quote): ?>
                        <tr>
                            <td><?= $quote['quotation_id'] ?></td>
                            <td><?= htmlspecialchars($quote['subject']) ?></td>
                            <td><?= htmlspecialchars($quote['distributor']) ?></td>
                            <td>
                                <?php if ($quote['status'] === 'PENDING'): ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php elseif ($quote['status'] === 'APPROVED'): ?>
                                    <span class="badge badge-approved">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-rejected">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($quote['createdAt'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($distributor_quotations)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No distributor quotations found for this period</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Purchase Orders Report -->
        <?php if ($report_type === 'all' || $report_type === 'purchase_orders'): ?>
        <div class="report-section" id="purchase-orders">
            <div class="report-header d-flex justify-content-between align-items-center">
                <h3><i class="bi bi-cart-check"></i> Purchase Orders</h3>
                <div>
                    <span class="badge bg-primary"><?= count($purchase_orders) ?> records</span>
                    <?php if ($report_type !== 'all'): ?>
                    <form method="get" class="d-inline ms-2">
                        <input type="hidden" name="report_type" value="<?= $report_type ?>">
                        <select name="purchase_orders_period" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="weekly" <?= ($_GET['purchase_orders_period'] ?? 'monthly') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($_GET['purchase_orders_period'] ?? 'monthly') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= ($_GET['purchase_orders_period'] ?? 'monthly') === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Distributor</th>
                            <th>Amount</th>
                            <th>Tax</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchase_orders as $order): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td><?= htmlspecialchars($order['distributor']) ?></td>
                            <td>₹<?= number_format($order['total_amount'], 2) ?></td>
                            <td>₹<?= number_format($order['total_tax'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($purchase_orders)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No purchase orders found for this period</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Customer Orders Report -->
        <?php if ($report_type === 'all' || $report_type === 'orders'): ?>
        <div class="report-section" id="customer-orders">
            <div class="report-header d-flex justify-content-between align-items-center">
                <h3><i class="bi bi-bag-check"></i> Customer Orders</h3>
                <div>
                    <span class="badge bg-primary"><?= count($orders) ?> records</span>
                    <?php if ($report_type !== 'all'): ?>
                    <form method="get" class="d-inline ms-2">
                        <input type="hidden" name="report_type" value="<?= $report_type ?>">
                        <select name="orders_period" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="weekly" <?= ($_GET['orders_period'] ?? 'monthly') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($_GET['orders_period'] ?? 'monthly') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= ($_GET['orders_period'] ?? 'monthly') === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= $order['id'] ?></td>
                            <td><?= htmlspecialchars($order['customer']) ?></td>
                            <td><?= htmlspecialchars($order['product'] ?? 'Custom Product') ?></td>
                            <td><?= $order['quantity'] ?></td>
                            <td>₹<?= number_format($order['amount'], 2) ?></td>
                            <td>
                                <?php if ($order['status'] === 'PENDING'): ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php elseif ($order['status'] === 'SHIPPED'): ?>
                                    <span class="badge badge-shipped">Shipped</span>
                                <?php elseif ($order['status'] === 'DELIVERED'): ?>
                                    <span class="badge badge-delivered">Delivered</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?= $order['status'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($order['createdAt'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No customer orders found for this period</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Invoices Report -->
        <?php if ($report_type === 'all' || $report_type === 'invoices'): ?>
        <div class="report-section" id="invoices">
            <div class="report-header d-flex justify-content-between align-items-center">
                <h3><i class="bi bi-receipt"></i> Invoices</h3>
                <div>
                    <span class="badge bg-primary"><?= count($invoices) ?> records</span>
                    <?php if ($report_type !== 'all'): ?>
                    <form method="get" class="d-inline ms-2">
                        <input type="hidden" name="report_type" value="<?= $report_type ?>">
                        <select name="invoices_period" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="weekly" <?= ($_GET['invoices_period'] ?? 'monthly') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($_GET['invoices_period'] ?? 'monthly') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= ($_GET['invoices_period'] ?? 'monthly') === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Tax</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><?= $invoice['id'] ?></td>
                            <td><?= htmlspecialchars($invoice['customer']) ?></td>
                            <td>₹<?= number_format($invoice['total_amount'], 2) ?></td>
                            <td>₹<?= number_format($invoice['total_tax'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($invoice['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($invoices)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">No invoices found for this period</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Subscriptions Report -->
        <?php if ($report_type === 'all' || $report_type === 'subscriptions'): ?>
        <div class="report-section" id="subscriptions">
            <div class="report-header d-flex justify-content-between align-items-center">
                <h3><i class="bi bi-credit-card"></i> Subscriptions</h3>
                <div>
                    <span class="badge bg-primary"><?= count($subscriptions) ?> records</span>
                    <?php if ($report_type !== 'all'): ?>
                    <form method="get" class="d-inline ms-2">
                        <input type="hidden" name="report_type" value="<?= $report_type ?>">
                        <select name="subscriptions_period" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="weekly" <?= ($_GET['subscriptions_period'] ?? 'monthly') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($_GET['subscriptions_period'] ?? 'monthly') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= ($_GET['subscriptions_period'] ?? 'monthly') === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Plan</th>
                            <th>Tenure</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscriptions as $sub): ?>
                        <tr>
                            <td><?= $sub['id'] ?></td>
                            <td><?= htmlspecialchars($sub['customer']) ?></td>
                            <td><?= htmlspecialchars($sub['plan']) ?></td>
                            <td><?= $sub['tenure'] ?> month(s)</td>
                            <td>₹<?= number_format($sub['amount'], 2) ?></td>
                            <td>
                                <?php if ($sub['status'] === 'Pending'): ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php elseif ($sub['status'] === 'Approved'): ?>
                                    <span class="badge badge-approved">Approved</span>
                                <?php else: ?>
                                    <span class="badge badge-rejected">Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($sub['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subscriptions)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No subscriptions found for this period</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Support Tickets Report -->
        <?php if ($report_type === 'all' || $report_type === 'tickets'): ?>
        <div class="report-section" id="support-tickets">
            <div class="report-header d-flex justify-content-between align-items-center">
                <h3><i class="bi bi-headset"></i> Support Tickets</h3>
                <div>
                    <span class="badge bg-primary"><?= count($tickets) ?> records</span>
                    <?php if ($report_type !== 'all'): ?>
                    <form method="get" class="d-inline ms-2">
                        <input type="hidden" name="report_type" value="<?= $report_type ?>">
                        <select name="tickets_period" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                            <option value="weekly" <?= ($_GET['tickets_period'] ?? 'monthly') === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                            <option value="monthly" <?= ($_GET['tickets_period'] ?? 'monthly') === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                            <option value="yearly" <?= ($_GET['tickets_period'] ?? 'monthly') === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                        </select>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Freelancer</th>
                            <th>Skill</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?= $ticket['id'] ?></td>
                            <td><?= htmlspecialchars($ticket['customer']) ?></td>
                            <td><?= htmlspecialchars($ticket['freelancer'] ?? 'Unassigned') ?></td>
                            <td><?= htmlspecialchars($ticket['skill_name']) ?></td>
                            <td>
                                <?php if ($ticket['status'] === 'PENDING'): ?>
                                    <span class="badge badge-pending">Pending</span>
                                <?php elseif ($ticket['status'] === 'RESOLVED'): ?>
                                    <span class="badge badge-approved">Resolved</span>
                                <?php elseif ($ticket['status'] === 'IN_PROGRESS'): ?>
                                    <span class="badge bg-info">In Progress</span>
                                <?php elseif ($ticket['status'] === 'REJECTED'): ?>
                                    <span class="badge badge-rejected">Rejected</span>
                                <?php elseif ($ticket['status'] === 'CLOSED'): ?>
                                    <span class="badge bg-secondary">Closed</span>
                                <?php else: ?>
                                    <span class="badge bg-primary"><?= $ticket['status'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($ticket['priority'] === 'High'): ?>
                                    <span class="badge bg-danger">High</span>
                                <?php elseif ($ticket['priority'] === 'Medium'): ?>
                                    <span class="badge bg-warning text-dark">Medium</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Low</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('M d, Y', strtotime($ticket['createdAt'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No support tickets found for this period</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>