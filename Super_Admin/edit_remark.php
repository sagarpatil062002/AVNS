<?php
ob_start(); // Start output buffering
include 'admin_navbar.php'; // Include the navbar
include('Config.php');

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if ticket_id is provided
if (isset($_GET['ticket_id'])) {
    $ticket_id = $_GET['ticket_id'];

    // Fetch the ticket details from the database
    $sql = "SELECT * FROM ticket WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $ticket = $result->fetch_assoc();
    } else {
        echo "Ticket not found.";
        exit;
    }
}

// Update the remark and status if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remark'], $_POST['status'])) {
    $new_remark = $_POST['remark'];
    $new_status = $_POST['status'];

    // Check if the new status is 'PENDING'
    if ($new_status === 'PENDING') {
        // Update remark, status, and set freelancerId to NULL
        $sql = "UPDATE ticket SET remark = ?, status = ?, freelancerId = NULL WHERE id = ?";
    } else {
        // Update only remark and status
        $sql = "UPDATE ticket SET remark = ?, status = ? WHERE id = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $new_remark, $new_status, $ticket_id);

    if ($stmt->execute()) {
        header("Location: ticket_assignment.php");
        exit();
    } else {
        echo "Error updating ticket: " . $conn->error;
    }
}

ob_end_flush(); // Flush the output buffer and send headers
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --info-color: #4895ef;
            --border-radius: 12px;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #4a4a4a;
        }

        .main-container {
            margin: 30px auto;
            padding: 30px;
            max-width: 900px;
            margin-left: 310px;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px 25px;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            font-size: 1.2em;
        }

        .card-body {
            padding: 30px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }

        textarea.form-control {
            min-height: 150px;
        }

        .btn {
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: #fff7e6;
            color: #fa8c16;
        }

        .status-assigned {
            background-color: #e6f7ff;
            color: #1890ff;
        }

        .status-in_progress {
            background-color: #f6ffed;
            color: #52c41a;
        }

        .status-resolved {
            background-color: #e6f7ee;
            color: #00a854;
        }

        .status-rejected {
            background-color: #fff1f0;
            color: #f5222d;
        }

        .status-closed {
            background-color: #f0f0f0;
            color: #595959;
        }

        .ticket-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .ticket-info-item {
            display: flex;
            margin-bottom: 8px;
        }

        .ticket-info-label {
            font-weight: 600;
            min-width: 120px;
            color: #6c757d;
        }

        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
                padding: 20px;
            }
        }

        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .card-body {
                padding: 20px;
            }
            
            .ticket-info-item {
                flex-direction: column;
            }
            
            .ticket-info-label {
                margin-bottom: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-ticket-alt"></i>Edit Ticket #<?php echo htmlspecialchars($ticket_id); ?>
                </h2>
                <span class="status-badge status-<?php echo strtolower($ticket['status'] ?? 'pending'); ?>">
                    <?php echo htmlspecialchars($ticket['status'] ?? 'PENDING'); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="ticket-info">
                    <div class="ticket-info-item">
                        <span class="ticket-info-label">Current Status:</span>
                        <span><?php echo htmlspecialchars($ticket['status'] ?? 'PENDING'); ?></span>
                    </div>
                    <div class="ticket-info-item">
                        <span class="ticket-info-label">Current Remark:</span>
                        <span><?php echo htmlspecialchars($ticket['remark'] ?? 'No remarks'); ?></span>
                    </div>
                </div>
                
                <form action="edit_remark.php?ticket_id=<?php echo $ticket_id; ?>" method="POST">
                    <div class="mb-4">
                        <label for="remark" class="form-label">New Remark</label>
                        <textarea name="remark" class="form-control" id="remark" rows="5" required><?php echo htmlspecialchars($ticket['remark'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="status" class="form-label">Update Status</label>
                        <select name="status" class="form-select" id="status" required>
                            <option value="PENDING" <?php if (($ticket['status'] ?? '') === 'PENDING') echo 'selected'; ?>>PENDING</option>
                            <option value="ASSIGNED" <?php if (($ticket['status'] ?? '') === 'ASSIGNED') echo 'selected'; ?>>ASSIGNED</option>
                            <option value="IN_PROGRESS" <?php if (($ticket['status'] ?? '') === 'IN_PROGRESS') echo 'selected'; ?>>IN PROGRESS</option>
                            <option value="RESOLVED" <?php if (($ticket['status'] ?? '') === 'RESOLVED') echo 'selected'; ?>>RESOLVED</option>
                            <option value="REJECTED" <?php if (($ticket['status'] ?? '') === 'REJECTED') echo 'selected'; ?>>REJECTED</option>
                            <option value="CLOSED" <?php if (($ticket['status'] ?? '') === 'CLOSED') echo 'selected'; ?>>CLOSED</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-3">
                        <a href="ticket_assignment.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add animation to form elements
        document.addEventListener('DOMContentLoaded', function() {
            const formElements = document.querySelectorAll('.form-control, .form-select, .btn');
            formElements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>