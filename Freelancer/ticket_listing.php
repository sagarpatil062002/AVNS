<?php
session_start();
include('Config.php');
include('dnav.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_SESSION['user_id'])) {
    $freelancerId = $_SESSION['user_id'];

    // Fetch freelancer's approved skills
    $sqlSkills = "SELECT fs.skill_id, s.skill_name 
                 FROM freelancer_skills fs
                 JOIN skills s ON fs.skill_id = s.id
                 WHERE fs.freelancer_id = ? AND fs.is_approved = 1";
    
    $stmt = $conn->prepare($sqlSkills);
    $stmt->bind_param("i", $freelancerId);
    $stmt->execute();
    $result = $stmt->get_result();

    $freelancerSkills = [];
    $skillNames = [];
    while ($row = $result->fetch_assoc()) {
        $freelancerSkills[] = $row['skill_id'];
        $skillNames[] = $row['skill_name'];
    }

    if (count($freelancerSkills) > 0) {
        $statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
        $sortOrder = isset($_GET['sort']) ? $_GET['sort'] : 'DESC';
        $priorityFilter = isset($_GET['priority']) ? $_GET['priority'] : '';

        $placeholders = implode(',', array_fill(0, count($freelancerSkills), '?'));
        $params = array_merge($freelancerSkills, [$freelancerId]);

        $sqlTickets = "SELECT t.id AS ticket_id, t.description, t.status, t.priority, t.createdAt,
                      c.companyName AS customer_name, s.skill_name
                      FROM Ticket t
                      JOIN customerdistributor c ON t.customerId = c.id
                      JOIN skills s ON t.skill_id = s.id
                      WHERE t.skill_id IN ($placeholders)
                      AND (t.freelancerId IS NULL OR t.freelancerId = ?)";

        if ($statusFilter) {
            $sqlTickets .= " AND t.status = ?";
            $params[] = $statusFilter;
        }

        if ($priorityFilter) {
            $sqlTickets .= " AND t.priority = ?";
            $params[] = $priorityFilter;
        }

        $sqlTickets .= " ORDER BY t.createdAt $sortOrder";

        $stmt = $conn->prepare($sqlTickets);
        $types = str_repeat('i', count($freelancerSkills)) . 'i';
        $types .= $statusFilter ? 's' : '';
        $types .= $priorityFilter ? 's' : '';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $ticketResult = $stmt->get_result();
    } else {
        $noSkillsMessage = "No approved skills found for your account.";
    }
} else {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Tickets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary: #4e73df;
            --primary-light: rgba(78, 115, 223, 0.1);
            --success: #1cc88a;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --dark: #5a5c69;
            --light: #f8f9fc;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', sans-serif;
        }
        
        .main-container {
            margin-left: 280px;
            padding: 2rem;
            transition: margin-left 0.3s;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e3e6f0;
        }
        
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 2rem;
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }
        
        .filter-section {
            background-color: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .filter-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table {
            background-color: white;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 1rem;
        }
        
        .table td {
            vertical-align: middle;
            padding: 1rem;
            border-top: 1px solid #e3e6f0;
        }
        
        .badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.5em 0.75em;
            border-radius: 0.25rem;
        }
        
        .badge-primary {
            background-color: var(--primary-light);
            color: var(--primary);
        }
        
        .badge-success {
            background-color: rgba(28, 200, 138, 0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background-color: rgba(246, 194, 62, 0.1);
            color: #856404;
        }
        
        .badge-danger {
            background-color: rgba(231, 74, 59, 0.1);
            color: var(--danger);
        }
        
        .btn-view {
            background-color: var(--primary);
            color: white;
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .btn-view:hover {
            background-color: #2e59d9;
            color: white;
        }
        
        .priority-high {
            color: var(--danger);
            font-weight: 600;
        }
        
        .priority-medium {
            color: var(--warning);
            font-weight: 600;
        }
        
        .priority-low {
            color: var(--success);
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--dark);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #dddfeb;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 992px) {
            .main-container {
                margin-left: 0;
                padding: 1rem;
            }
            
            .filter-row {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-ticket-alt me-2"></i>Available Tickets
            </h1>
            <div class="text-muted">
                Your Skills: <?php echo implode(', ', $skillNames); ?>
            </div>
        </div>
        
        <?php if (isset($noSkillsMessage)): ?>
            <div class="card">
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle"></i>
                    <h4>No Approved Skills</h4>
                    <p><?php echo $noSkillsMessage; ?></p>
                    <a href="profile.php" class="btn btn-primary mt-3">
                        <i class="fas fa-user-cog me-2"></i>Update Your Profile
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Tickets</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="filter-section">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Statuses</option>
                                    <option value="PENDING" <?= $statusFilter === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                                    <option value="ASSIGNED" <?= $statusFilter === 'ASSIGNED' ? 'selected' : '' ?>>Assigned</option>
                                    <option value="COMPLETED" <?= $statusFilter === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="form-label">Priority</label>
                                <select name="priority" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Priorities</option>
                                    <option value="High" <?= $priorityFilter === 'High' ? 'selected' : '' ?>>High</option>
                                    <option value="Medium" <?= $priorityFilter === 'Medium' ? 'selected' : '' ?>>Medium</option>
                                    <option value="Low" <?= $priorityFilter === 'Low' ? 'selected' : '' ?>>Low</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label class="form-label">Sort By</label>
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="DESC" <?= $sortOrder === 'DESC' ? 'selected' : '' ?>>Newest First</option>
                                    <option value="ASC" <?= $sortOrder === 'ASC' ? 'selected' : '' ?>>Oldest First</option>
                                </select>
                            </div>
                        </div>
                    </form>
                    
                    <?php if ($ticketResult->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ticket ID</th>
                                        <th>Description</th>
                                        <th>Customer</th>
                                        <th>Skill</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($ticket = $ticketResult->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?= htmlspecialchars($ticket['ticket_id']) ?></td>
                                            <td><?= htmlspecialchars(substr($ticket['description'], 0, 50)) . (strlen($ticket['description']) > 50 ? '...' : '') ?></td>
                                            <td><?= htmlspecialchars($ticket['customer_name']) ?></td>
                                            <td><?= htmlspecialchars($ticket['skill_name']) ?></td>
                                            <td>
                                                <?php if ($ticket['status'] === 'PENDING'): ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php elseif ($ticket['status'] === 'ASSIGNED'): ?>
                                                    <span class="badge badge-primary">Assigned</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Completed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="<?= 
                                                $ticket['priority'] === 'High' ? 'priority-high' : 
                                                ($ticket['priority'] === 'Medium' ? 'priority-medium' : 'priority-low') 
                                            ?>">
                                                <?= htmlspecialchars($ticket['priority']) ?>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($ticket['createdAt'])) ?></td>
                                            <td>
                                                <a href="ticket_details.php?id=<?= htmlspecialchars($ticket['ticket_id']) ?>" 
                                                   class="btn btn-view">
                                                   <i class="fas fa-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-ticket-alt"></i>
                            <h4>No Tickets Found</h4>
                            <p>There are no tickets matching your current filters.</p>
                            <a href="?" class="btn btn-primary mt-3">
                                <i class="fas fa-sync-alt me-2"></i>Reset Filters
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>