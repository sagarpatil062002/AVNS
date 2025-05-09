<?php
include 'admin_navbar.php';
// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Replace with your database password
$dbname = 'sales_management';

// Create a connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch ticket details with company name and freelancer name
$sql = "SELECT t.id AS ticket_id, 
                cd.companyName AS company_name, 
                f.name AS freelancer_name, 
                t.description, 
                t.remark,
                t.status
        FROM ticket t
        LEFT JOIN customerdistributor cd ON t.customerId = cd.id
        LEFT JOIN freelancer f ON t.freelancerId = f.id
        ORDER BY t.id DESC";  // Changed from created_at to id for sorting

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Management System</title>
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
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #4a4a4a;
        }

        .main-container {
            margin: 20px auto;
            padding: 20px;
            max-width: 95%;
            margin-left:300px;
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
            padding: 15px 25px;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-weight: 600;
            margin: 0;
            font-size: 1.5rem;
        }

        .badge-count {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 500;
        }

        .table-responsive {
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: var(--dark-color);
            font-weight: 600;
            padding: 15px;
            border-bottom: 2px solid #e9ecef;
            position: sticky;
            top: 0;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-top: 1px solid #f0f0f0;
        }

        .table tbody tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-open {
            background-color: #e6f7ff;
            color: #1890ff;
        }

        .status-pending {
            background-color: #fff7e6;
            color: #fa8c16;
        }

        .status-resolved {
            background-color: #e6f7ee;
            color: #00a854;
        }

        .status-closed {
            background-color: #f6ffed;
            color: #52c41a;
        }

        .status-cancelled {
            background-color: #fff1f0;
            color: #f5222d;
        }

        .action-btn {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .action-btn i {
            font-size: 0.9rem;
        }

        .btn-edit {
            background-color: var(--info-color);
            color: white;
        }

        .btn-edit:hover {
            background-color: #3a7bd5;
            transform: translateY(-2px);
            color: white;
        }

        .empty-state {
            padding: 40px 20px;
            text-align: center;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 60px;
            color: #dee2e6;
            margin-bottom: 15px;
        }

        .empty-state h4 {
            font-weight: 600;
            margin-bottom: 10px;
        }

        .description-cell {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Responsive styles */
        @media (max-width: 992px) {
            .main-container {
                padding: 15px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }

        @media (max-width: 768px) {
            .table thead {
                display: none;
            }
            
            .table tbody tr {
                display: block;
                margin-bottom: 20px;
                border: 1px solid #e9ecef;
                border-radius: 8px;
                padding: 15px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            }
            
            .table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none;
                padding: 10px 5px;
            }
            
            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--dark-color);
                margin-right: 15px;
                flex: 1;
            }
            
            .table tbody td .cell-content {
                flex: 2;
                text-align: right;
            }
            
            .status-badge, .action-btn {
                margin-left: auto;
            }
            
            .description-cell {
                white-space: normal;
                max-width: 100%;
            }
        }    </style>
</head>
<body>
    <div class="main-container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-ticket-alt me-2"></i>Ticket Management
                </h2>
                <span class="badge-count">
                    <?php echo $result->num_rows; ?> ticket(s)
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Company</th>
                                <th>Freelancer</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td data-label="Ticket ID">
                                            <span class="cell-content">#<?php echo htmlspecialchars($row['ticket_id']); ?></span>
                                        </td>
                                        <td data-label="Company">
                                            <span class="cell-content"><?php echo htmlspecialchars($row['company_name']); ?></span>
                                        </td>
                                        <td data-label="Freelancer">
                                            <span class="cell-content"><?php echo htmlspecialchars($row['freelancer_name'] ?? 'Unassigned'); ?></span>
                                        </td>
                                        <td data-label="Description" class="description-cell">
                                            <span class="cell-content" title="<?php echo htmlspecialchars($row['description']); ?>">
                                                <?php echo htmlspecialchars($row['description']); ?>
                                            </span>
                                        </td>
                                        <td data-label="Status">
                                            <span class="cell-content">
                                                <?php 
                                                $status = $row['status'] ?? 'open';
                                                $statusClass = 'status-' . strtolower($status);
                                                echo "<span class='status-badge $statusClass'>$status</span>";
                                                ?>
                                            </span>
                                        </td>
                                        <td data-label="Actions">
                                            <div class="d-flex gap-2">
                                                <a href="edit_remark.php?ticket_id=<?php echo $row['ticket_id']; ?>" 
                                                   class="action-btn btn-edit"
                                                   title="Edit Ticket">
                                                    <i class="fas fa-edit"></i>
                                                    <span class="d-none d-md-inline">Edit</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <h4>No Tickets Found</h4>
                                            <p>There are currently no tickets in the system.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>