<?php
session_start();
include 'config.php';
include 'CustomerNav.php';
?>
<link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
<link rel="stylesheet" href="styles.css">

<?php
$customerId = $_SESSION['user_id'] ?? null;
$companyName = null;

if ($customerId) {
    $query = "SELECT companyName FROM customerdistributor WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $customerId);
    $stmt->execute();
    $stmt->bind_result($companyName);
    $stmt->fetch();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $description = $_POST['description'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $priority = $_POST['priority'] ?? '';

    if ($customerId && $description && $skills && $priority) {
        $insertQuery = "INSERT INTO ticket (customerId, description, skill_id, priority, createdAt) VALUES (?, ?, ?, ?, NOW())";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("isss", $customerId, $description, $skills, $priority);

        if ($insertStmt->execute()) {
            $successMessage = "Ticket created successfully!";
        } else {
            $errorMessage = "Failed to create ticket. Please try again.";
        }
        $insertStmt->close();
    } else {
        $errorMessage = "Please fill all required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Support Ticket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4e73df;
            --primary-dark: #2e59d9;
            --secondary: #f8f9fc;
            --success: #1cc88a;
            --danger: #e74a3b;
            --warning: #f6c23e;
            --light: #f8f9fc;
            --dark: #5a5c69;
            --gray: #858796;
            --gray-light: #dddfeb;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--dark);
        }
        
        .ticket-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .ticket-header {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--primary);
            font-weight: 700;
            font-size: 1.75rem;
            border-bottom: 1px solid var(--gray-light);
            padding-bottom: 1rem;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border: 1px solid var(--gray-light);
            border-radius: 0.35rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            font-size: 0.875rem;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .alert {
            border-radius: 0.35rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: rgba(28, 200, 138, 0.1);
            border-left: 4px solid var(--success);
            color: var(--success);
        }
        
        .alert-danger {
            background-color: rgba(231, 74, 59, 0.1);
            border-left: 4px solid var(--danger);
            color: var(--danger);
        }
        
        .form-icon {
            position: relative;
        }
        
        .form-icon i {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        @media (max-width: 768px) {
            .ticket-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .ticket-header {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10">
                <div class="ticket-container">
                    <h1 class="ticket-header">
                        <i class="fas fa-ticket-alt me-2"></i>Create Support Ticket
                    </h1>
                    
                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $errorMessage; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST">
                        <div class="mb-4">
                            <label for="customerId" class="form-label">Customer Name</label>
                            <div class="form-icon">
                                <select class="form-select" id="customerId" name="customerId" required>
                                    <?php if ($customerId && $companyName): ?>
                                        <option value="<?php echo $customerId; ?>"><?php echo $companyName; ?></option>
                                    <?php else: ?>
                                        <option value="">No customer found</option>
                                    <?php endif; ?>
                                </select>
                                <i class="fas fa-building"></i>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="form-label">Issue Description</label>
                            <textarea class="form-control" id="description" name="description" required></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label for="skills" class="form-label">Required Skills</label>
                            <div class="form-icon">
                                <select class="form-select" id="skills" name="skills" required>
                                    <?php
                                    $result = $conn->query("SELECT id, skill_name FROM skills");
                                    while ($row = $result->fetch_assoc()): ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['skill_name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                                <i class="fas fa-tools"></i>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="priority" class="form-label">Priority Level</label>
                            <div class="form-icon">
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="Low">Low Priority</option>
                                    <option value="Medium" selected>Medium Priority</option>
                                    <option value="High">High Priority</option>
                                </select>
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Submit Ticket
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add any necessary JavaScript here
    </script>
</body>
</html>