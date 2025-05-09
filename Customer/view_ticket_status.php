<?php
// Start session
session_start();
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch logged-in customer ID from session
if (isset($_SESSION['user_id'])) {
    $customerId = $_SESSION['user_id']; // Logged-in customer ID
} else {
    die("No customer is logged in. Please log in.");
}

// Query to get all tickets raised by the customer (removed created_at)
$sql = "
    SELECT t.id, t.description, t.status, t.remark
    FROM ticket t
    WHERE t.customerId = ?
    ORDER BY t.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customerId);
$stmt->execute();
$result = $stmt->get_result();

// Include navigation and styles
include 'CustomerNav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Tickets | Customer Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #f94144;
            --border-radius: 8px;
            --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: var(--dark-color);
            line-height: 1.6;
        }

        .container-main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 15px;
            margin-left: 350px;
        }

        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            margin-bottom: 2rem;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.25rem;
            border-bottom: none;
        }

        .card-title {
            margin: 0;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title i {
            margin-right: 10px;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
            border-top: none;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid #eceff1;
        }

        .status-badge {
            padding: 0.35rem 0.65rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: rgba(248, 150, 30, 0.1);
            color: var(--warning-color);
        }

        .status-resolved {
            background-color: rgba(76, 201, 240, 0.1);
            color: var(--success-color);
        }

        .status-processing {
            background-color: rgba(72, 149, 239, 0.1);
            color: var(--accent-color);
        }

        .status-rejected {
            background-color: rgba(249, 65, 68, 0.1);
            color: var(--danger-color);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .btn-new-ticket {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            margin-top: 1rem;
        }

        .btn-new-ticket:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-new-ticket i {
            margin-right: 8px;
        }

        .ticket-description {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .ticket-description:hover {
            white-space: normal;
            overflow: visible;
            position: absolute;
            background: white;
            padding: 10px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            z-index: 100;
            max-width: 400px;
        }

        @media (max-width: 768px) {
            .container-main {
                padding: 0 10px;
            }
            
            .card-header {
                padding: 1rem;
            }
            
            .table th, .table td {
                padding: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-ticket-alt"></i>Your Support Tickets
                </h2>
            </div>
            <div class="card-body">
                <?php if ($result->num_rows > 0) { ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Ticket #</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ticket = $result->fetch_assoc()) { 
                                    // Determine status badge class
                                    $statusClass = '';
                                    switch (strtolower($ticket['status'])) {
                                        case 'pending':
                                            $statusClass = 'status-pending';
                                            break;
                                        case 'resolved':
                                            $statusClass = 'status-resolved';
                                            break;
                                        case 'in progress':
                                        case 'processing':
                                            $statusClass = 'status-processing';
                                            break;
                                        case 'rejected':
                                        case 'closed':
                                            $statusClass = 'status-rejected';
                                            break;
                                        default:
                                            $statusClass = 'status-pending';
                                    }
                                ?>
                                    <tr>
                                        <td><strong>#<?php echo htmlspecialchars($ticket['id']); ?></strong></td>
                                        <td>
                                            <div class="ticket-description" title="<?php echo htmlspecialchars($ticket['description']); ?>">
                                                <?php echo htmlspecialchars(substr($ticket['description'], 0, 50)); ?>
                                                <?php if (strlen($ticket['description']) > 50) echo '...'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo htmlspecialchars($ticket['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($ticket['remark'])): ?>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" 
                                                    title="<?php echo htmlspecialchars($ticket['remark']); ?>">
                                                    <i class="fas fa-comment-alt"></i> View
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">No remarks</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="create_ticket.php" class="btn btn-new-ticket">
                            <i class="fas fa-plus"></i> Create New Ticket
                        </a>
                    </div>
                <?php } else { ?>
                    <div class="empty-state">
                        <i class="far fa-folder-open"></i>
                        <h3>No Tickets Found</h3>
                        <p>You haven't raised any support tickets yet.</p>
                        <a href="create_ticket.php" class="btn btn-new-ticket">
                            <i class="fas fa-plus"></i> Create Your First Ticket
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Bootstrap tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Make description tooltips work on mobile too
            const descriptions = document.querySelectorAll('.ticket-description');
            descriptions.forEach(desc => {
                desc.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        this.classList.toggle('expanded');
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
// Close the connection
$conn->close();
?>