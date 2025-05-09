<?php
session_start();
include('dnav.php');
include('config.php');

// Initialize variables
$ticketId = '';
$description = '';
$status = 'pending';
$freelancerId = '';
$customerId = '';
$planDetails = null;
$newRemainingCalls = 0;
$remainingCalls = 0;
$maxSupportCalls = 0;
$successMessage = '';
$errorMessage = '';

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch ticket details
if (isset($_GET['id'])) {
    $ticketId = $_GET['id'];

    $sql = "SELECT description, status, freelancerId, customerId FROM ticket WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($ticket = $result->fetch_assoc()) {
        $description = $ticket['description'];
        $status = strtolower($ticket['status']);
        $freelancerId = $ticket['freelancerId'];
        $customerId = $ticket['customerId'];
    } else {
        echo "Ticket not found.";
        exit();
    }
} else {
    echo "No ticket ID provided.";
    exit();
}

// Fetch plan details and remaining calls
if (isset($customerId)) {
    $planQuery = "SELECT cp.name AS plan_name, cp.max_support_calls, cp.base_price,
                 cs.remaining_calls, cs.id AS subscription_id
                 FROM CustomerDistributor cd
                 JOIN customer_subscription cs ON cd.id = cs.user_id
                 JOIN customer_plan cp ON cs.plan_id = cp.id
                 WHERE cd.id = ?
                 ORDER BY cs.id DESC LIMIT 1";
    
    $planStmt = $conn->prepare($planQuery);
    $planStmt->bind_param("i", $customerId);
    $planStmt->execute();
    $planResult = $planStmt->get_result();

    if ($planResult->num_rows > 0) {
        $planDetails = $planResult->fetch_assoc();
        $maxSupportCalls = (int)$planDetails['max_support_calls'];
        $remainingCalls = $planDetails['remaining_calls'] !== null ? (int)$planDetails['remaining_calls'] : $maxSupportCalls;
        $subscriptionId = $planDetails['subscription_id'];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept Ticket
    if (isset($_POST['accept_ticket'])) {
        $freelancerId = $_SESSION['user_id'];
        $updateStatusSql = "UPDATE ticket SET status = 'ASSIGNED', freelancerId = ? WHERE id = ?";
        $stmtUpdateStatus = $conn->prepare($updateStatusSql);
        $stmtUpdateStatus->bind_param("ii", $freelancerId, $ticketId);

        if ($stmtUpdateStatus->execute()) {
            $successMessage = "Ticket accepted successfully!";
            $status = "assigned";
        } else {
            $errorMessage = "Failed to accept the ticket.";
        }
    }

    // Add Remark
    if (isset($_POST['submit_remark'])) {
        $remark = htmlspecialchars(trim($_POST['remark']), ENT_QUOTES, 'UTF-8');
        $updateRemarkSql = "UPDATE ticket SET remark = ? WHERE id = ?";
        $stmtUpdateRemark = $conn->prepare($updateRemarkSql);
        $stmtUpdateRemark->bind_param("si", $remark, $ticketId);

        if ($stmtUpdateRemark->execute()) {
            $successMessage = "Remark added successfully!";
        } else {
            $errorMessage = "Failed to add remark.";
        }
    }

    // Change Status
    if (isset($_POST['change_status'])) {
        $newStatus = strtolower($_POST['status']);
        
        if (in_array($newStatus, ['assigned', 'in_progress', 'resolved', 'rejected', 'closed'])) {
            $freelancerId = $_SESSION['user_id'];
            $updateStatusSql = "UPDATE ticket SET status = ?, freelancerId = ? WHERE id = ?";
            $stmtUpdateStatus = $conn->prepare($updateStatusSql);
            $stmtUpdateStatus->bind_param("sii", $newStatus, $freelancerId, $ticketId);
        } else {
            $updateStatusSql = "UPDATE ticket SET status = ? WHERE id = ?";
            $stmtUpdateStatus = $conn->prepare($updateStatusSql);
            $stmtUpdateStatus->bind_param("si", $newStatus, $ticketId);
        }

        if ($stmtUpdateStatus->execute()) {
            $successMessage = "Status changed successfully!";
            $status = $newStatus;
        } else {
            $errorMessage = "Failed to change status.";
        }
    }

    // Support Calls
    if (isset($_POST['submit_support_calls'])) {
        $completedCalls = (int)$_POST['support_calls'];
        
        if ($completedCalls <= 0) {
            $errorMessage = "Please enter a valid number of support calls.";
        } elseif ($completedCalls > $remainingCalls) {
            $errorMessage = "Cannot complete more calls than remaining. You have {$remainingCalls} calls remaining.";
        } else {
            $newRemainingCalls = $remainingCalls - $completedCalls;
            
            // Update customer_subscription
            $updateQuery = "UPDATE customer_subscription SET remaining_calls = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("ii", $newRemainingCalls, $subscriptionId);

            if ($updateStmt->execute()) {
                $successMessage = "Support calls completed and remaining calls updated successfully.";
                $remainingCalls = $newRemainingCalls;
                
                // Update ticket with support calls information
            //     $updateTicketSql = "UPDATE ticket SET support_calls_completed = ? WHERE id = ?";
            //     $stmtUpdateTicket = $conn->prepare($updateTicketSql);
            //     $stmtUpdateTicket->bind_param("ii", $completedCalls, $ticketId);
            //     $stmtUpdateTicket->execute();
            // } else {
                $errorMessage = "Failed to update remaining calls.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details | <?php echo htmlspecialchars($ticketId); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f72585;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fb;
            color: #4a4a4a;
        }

        .main-container {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
            margin-left: 320px;
        }

        .container {
            margin-top: 30px;
            padding: 30px;
            background-color: #fff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            width: 100%;
            position: relative;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .ticket-header h2 {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 600;
            margin: 0;
        }

        .ticket-id {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .ticket-description {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            line-height: 1.7;
            font-size: 16px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-assigned {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-in_progress {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .status-resolved {
            background-color: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-closed {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .action-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }

        .action-card h5 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 18px;
        }

        .btn-custom {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            font-weight: 500;
            padding: 10px 20px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .btn-custom:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-success {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }

        .btn-warning {
            background-color: var(--warning-color);
            border-color: var(--warning-color);
        }

        .plan-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }

        .plan-card h3 {
            color: var(--primary-color);
            font-size: 20px;
            margin-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }

        .plan-detail {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px dashed #dee2e6;
        }

        .plan-detail:last-child {
            border-bottom: none;
        }

        .plan-detail-label {
            font-weight: 600;
            color: #495057;
        }

        .plan-detail-value {
            font-weight: 500;
            color: var(--dark-color);
        }

        .remaining-calls {
            font-size: 18px;
            font-weight: 600;
        }

        .remaining-high {
            color: #2ecc71;
        }

        .remaining-medium {
            color: #f39c12;
        }

        .remaining-low {
            color: #e74c3c;
        }

        .form-control, .form-select {
            border-radius: 6px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: border-color 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        textarea.form-control {
            min-height: 120px;
        }

        #remarkTextarea, #supportCallsInput {
            display: none;
            margin-top: 20px;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert {
            border-radius: 8px;
            padding: 15px 20px;
            margin-top: 20px;
        }

        .badge-icon {
            margin-right: 8px;
        }

        .support-calls-info {
            margin-top: 15px;
            padding: 15px;
            background-color: #e8f4fd;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="container">
            <div class="ticket-header">
                <h2>Ticket Details</h2>
                <span class="ticket-id">Ticket #<?php echo htmlspecialchars($ticketId); ?></span>
            </div>

            <div class="ticket-description">
                <p><?php echo htmlspecialchars($description); ?></p>
            </div>

            <div class="d-flex align-items-center mb-4">
                <span class="status-badge status-<?php echo $status; ?>">
                    <i class="fas fa-tag badge-icon"></i>
                    <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                </span>
            </div>

            <?php if ($status === 'assigned') { ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> This ticket has already been assigned.
                </div>
            <?php } elseif ($status === 'pending' || $status === 'rejected') { ?>
                <div class="action-card">
                    <h5>Accept Ticket</h5>
                    <form method="POST">
                        <button type="submit" name="accept_ticket" class="btn btn-success">
                            <i class="fas fa-check-circle me-2"></i>Accept Ticket
                        </button>
                    </form>
                </div>
            <?php } ?>

            <div class="action-card">
                <h5>Update Ticket Status</h5>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-8">
                            <select name="status" id="status" class="form-select" required>
                                <option value="<?php echo $status; ?>" selected><?php echo ucfirst(str_replace('_', ' ', $status)); ?></option>
                                <?php 
                                $statusOptions = [
                                    'pending' => 'Pending',
                                    'assigned' => 'Assigned',
                                    'in_progress' => 'In Progress',
                                    'resolved' => 'Resolved',
                                    'rejected' => 'Rejected',
                                    'closed' => 'Closed'
                                ];
                                
                                foreach ($statusOptions as $key => $label) {
                                    if ($status !== $key) {
                                        echo "<option value=\"$key\">$label</option>";
                                    }
                                } 
                                ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="change_status" class="btn btn-custom w-100">
                                <i class="fas fa-sync-alt me-2"></i>Update Status
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="action-card">
                <h5>Add Remarks</h5>
                <button type="button" class="btn btn-custom" id="remarkButton" onclick="toggleRemarkTextarea()">
                    <i class="fas fa-comment-alt me-2"></i>Add Remark
                </button>

                <div id="remarkTextarea">
                    <form method="POST" class="mt-3">
                        <div class="form-group">
                            <textarea name="remark" class="form-control" rows="4" 
                                      placeholder="Enter your detailed remarks here..." required></textarea>
                        </div>
                        <button type="submit" name="submit_remark" class="btn btn-success mt-2">
                            <i class="fas fa-paper-plane me-2"></i>Submit Remark
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($status === 'resolved') { ?>
                <div class="action-card">
                    <h5>Support Calls</h5>
                    <?php if ($remainingCalls > 0): ?>
                        <button type="button" class="btn btn-warning" id="supportCallsButton" onclick="toggleSupportCallsInput()">
                            <i class="fas fa-phone-alt me-2"></i>Record Support Calls
                        </button>

                        <div id="supportCallsInput">
                            <form method="POST" class="mt-3">
                                <div class="form-group">
                                    <label for="supportCalls" class="form-label">Number of Support Calls Completed:</label>
                                    <input type="number" name="support_calls" id="supportCalls" 
                                           class="form-control" placeholder="Enter number of calls" 
                                           min="1" max="<?php echo $remainingCalls; ?>" required>
                                    <small class="text-muted">Maximum available: <?php echo $remainingCalls; ?> calls</small>
                                </div>
                                <button type="submit" name="submit_support_calls" class="btn btn-success mt-2">
                                    <i class="fas fa-save me-2"></i>Submit Calls
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i> No support calls remaining for this customer.
                        </div>
                    <?php endif; ?>
                </div>
            <?php } ?>

            <?php if ($planDetails) { ?>
                <div class="plan-card">
                    <h3><i class="fas fa-clipboard-list me-2"></i>Customer Plan Details</h3>
                    
                    <div class="plan-detail">
                        <span class="plan-detail-label">Plan Name:</span>
                        <span class="plan-detail-value"><?php echo htmlspecialchars($planDetails['plan_name']); ?></span>
                    </div>
                    
                    <div class="plan-detail">
                        <span class="plan-detail-label">Max Support Calls:</span>
                        <span class="plan-detail-value"><?php echo htmlspecialchars($maxSupportCalls); ?></span>
                    </div>
                    
                    <div class="plan-detail">
                        <span class="plan-detail-label">Base Price:</span>
                        <span class="plan-detail-value">⟨₹⟩
                        <?php echo htmlspecialchars($planDetails['base_price']); ?></span>
                    </div>
                    
                    <div class="plan-detail">
                        <span class="plan-detail-label">Remaining Calls:</span>
                        <span class="plan-detail-value remaining-calls 
                            <?php 
                            if ($remainingCalls > $maxSupportCalls * 0.5) {
                                echo 'remaining-high';
                            } elseif ($remainingCalls > $maxSupportCalls * 0.2) {
                                echo 'remaining-medium';
                            } else {
                                echo 'remaining-low';
                            }
                            ?>">
                            <?php echo $remainingCalls; ?>
                        </span>
                    </div>
                </div>
            <?php } ?>

            <?php if (isset($successMessage)) { ?>
                <div class="alert alert-success mt-4">
                    <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                </div>
            <?php } elseif (isset($errorMessage)) { ?>
                <div class="alert alert-danger mt-4">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
                </div>
            <?php } ?>
        </div>
    </div>

    <script>
        function toggleRemarkTextarea() {
            const textarea = document.getElementById('remarkTextarea');
            const button = document.getElementById('remarkButton');
            
            if (textarea.style.display === 'none' || !textarea.style.display) {
                textarea.style.display = 'block';
                button.innerHTML = '<i class="fas fa-times me-2"></i>Cancel';
                button.classList.remove('btn-custom');
                button.classList.add('btn-secondary');
            } else {
                textarea.style.display = 'none';
                button.innerHTML = '<i class="fas fa-comment-alt me-2"></i>Add Remark';
                button.classList.remove('btn-secondary');
                button.classList.add('btn-custom');
            }
        }

        function toggleSupportCallsInput() {
            const input = document.getElementById('supportCallsInput');
            const button = document.getElementById('supportCallsButton');
            
            if (input.style.display === 'none' || !input.style.display) {
                input.style.display = 'block';
                button.innerHTML = '<i class="fas fa-times me-2"></i>Cancel';
                button.classList.remove('btn-warning');
                button.classList.add('btn-secondary');
            } else {
                input.style.display = 'none';
                button.innerHTML = '<i class="fas fa-phone-alt me-2"></i>Record Support Calls';
                button.classList.remove('btn-secondary');
                button.classList.add('btn-warning');
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close the connection
$conn->close();
?>