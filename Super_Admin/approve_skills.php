<?php
ob_start(); // Start output buffering

// Database connection
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
include('admin_navbar.php');

// Check if super admin is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please log in to approve or reject.");
}

// Approve or reject freelancer or skill
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['freelancer_id'])) {
        $freelancerId = $_POST['freelancer_id'];
        $action = $_POST['action'];

        if ($action === 'approve_freelancer') {
            $approveFreelancerSql = "UPDATE freelancer SET is_approved = 1 WHERE id = ?";
            $stmt = $conn->prepare($approveFreelancerSql);
            $stmt->bind_param("i", $freelancerId);
            $stmt->execute();
        } elseif ($action === 'reject_freelancer') {
            $rejectFreelancerSql = "DELETE FROM freelancer WHERE id = ?";
            $stmt = $conn->prepare($rejectFreelancerSql);
            $stmt->bind_param("i", $freelancerId);
            $stmt->execute();
        }
    } elseif (isset($_POST['freelancer_skill_id'])) {
        $freelancerSkillId = $_POST['freelancer_skill_id'];
        $action = $_POST['action'];

        if ($action === 'approve_skill') {
            $approveSkillSql = "UPDATE freelancer_skills SET is_approved = 1 WHERE id = ?";
            $stmt = $conn->prepare($approveSkillSql);
            $stmt->bind_param("i", $freelancerSkillId);
            $stmt->execute();
        } elseif ($action === 'reject_skill') {
            $rejectSkillSql = "DELETE FROM freelancer_skills WHERE id = ?";
            $stmt = $conn->prepare($rejectSkillSql);
            $stmt->bind_param("i", $freelancerSkillId);
            $stmt->execute();
        }
    }

    ob_end_clean(); // Clean the output buffer
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Fetch pending freelancers
$pendingFreelancersSql = "SELECT * FROM freelancer WHERE is_approved = 0";
$pendingFreelancersResult = $conn->query($pendingFreelancersSql);

// Fetch pending skills
$pendingSkillsSql = "
SELECT 
    freelancer_skills.id AS freelancer_skill_id,
    freelancer.name AS freelancer_name,
    skills.skill_name,
    freelancer_skills.certificate_path
FROM 
    freelancer_skills
JOIN 
    freelancer ON freelancer_skills.freelancer_id = freelancer.id
JOIN 
    skills ON freelancer_skills.skill_id = skills.id
WHERE 
    freelancer_skills.is_approved = 0";
$pendingSkillsResult = $conn->query($pendingSkillsSql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Freelancers and Skills</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary: #4e73df;
            --success: #1cc88a;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', sans-serif;
        }
        
        .container {
            margin-left: 280px;
            padding: 2rem;
            max-width: 1200px;
        }
        
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .table {
            background-color: white;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }
        
        .table th {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        
        .btn-danger {
            background-color: var(--danger);
            border-color: var(--danger);
        }
        
        .certificate-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }
        
        .certificate-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 992px) {
            .container {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="fas fa-user-check me-2"></i>Approve Freelancers and Skills</h2>
    </div>
    
    <div class="card mb-5">
        <div class="card-header bg-white">
            <h3 class="mb-0"><i class="fas fa-users me-2"></i>Pending Freelancers</h3>
        </div>
        <div class="card-body">
            <?php if ($pendingFreelancersResult->num_rows > 0): ?>
                <div class="row">
                    <?php while ($freelancer = $pendingFreelancersResult->fetch_assoc()): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($freelancer['name']); ?></h5>
                                    <p class="card-text"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($freelancer['email']); ?></p>
                                    <form method="POST" class="mt-3">
                                        <input type="hidden" name="freelancer_id" value="<?php echo $freelancer['id']; ?>">
                                        <button type="submit" name="action" value="approve_freelancer" class="btn btn-success me-2">
                                            <i class="fas fa-check me-1"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject_freelancer" class="btn btn-danger">
                                            <i class="fas fa-times me-1"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No pending freelancers at this time.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white">
            <h3 class="mb-0"><i class="fas fa-tools me-2"></i>Pending Skills</h3>
        </div>
        <div class="card-body">
            <?php if ($pendingSkillsResult->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Freelancer</th>
                                <th>Skill</th>
                                <th>Certificate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($skill = $pendingSkillsResult->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($skill['freelancer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($skill['skill_name']); ?></td>
                                    <td>
                                        <?php if (!empty($skill['certificate_path'])): ?>
                                            <?php 
                                                // Extract just the filename from the path
                                                $certificatePath = $skill['certificate_path'];
                                                $filename = basename($certificatePath);
                                            ?>
                                            <a href="uploads/<?php echo htmlspecialchars($filename); ?>" 
                                               target="_blank" 
                                               class="certificate-link">
                                                <i class="fas fa-file-pdf me-1"></i>View Certificate
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No Certificate</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="freelancer_skill_id" value="<?php echo $skill['freelancer_skill_id']; ?>">
                                            <button type="submit" name="action" value="approve_skill" class="btn btn-success btn-sm me-2">
                                                <i class="fas fa-check me-1"></i> Approve
                                            </button>
                                            <button type="submit" name="action" value="reject_skill" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times me-1"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No pending skills at this time.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
ob_end_flush(); // Flush the output buffer
$conn->close();
?>