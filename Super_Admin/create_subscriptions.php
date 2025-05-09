<?php
session_start();
include 'config.php'; // Include database configuration
include 'admin_navbar.php'; 
// Initialize variables to store form input
$name = $base_price = $max_support_calls = $rewards_points = $most_popular = $sectorId = '';
$errors = [];
$successMessage = ''; // Variable to store success message

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize form input
    $name = $_POST['name'] ?? '';
    $base_price = $_POST['base_price'] ?? '';
    $max_support_calls = $_POST['max_support_calls'] ?? '';
    $rewards_points = $_POST['rewards_points'] ?? '';
    $most_popular = isset($_POST['most_popular']) ? 1 : 0;
    $sectorId = $_POST['sector'] ?? '';

    // Validate inputs
    if (empty($name)) $errors[] = 'Plan Name is required';
    if (empty($base_price) || !is_numeric($base_price)) $errors[] = 'Valid base price is required';
    if (empty($max_support_calls) || !is_numeric($max_support_calls)) $errors[] = 'Valid max support calls are required';
    if (empty($rewards_points) || !is_numeric($rewards_points)) $errors[] = 'Valid rewards points are required';
    if (empty($sectorId)) $errors[] = 'Sector selection is required';

    // If there are no errors, proceed to insert into the database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO customer_plan (name, base_price, max_support_calls, rewards_points, most_popular, sectorName) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sdiiis', $name, $base_price, $max_support_calls, $rewards_points, $most_popular, $sectorId);
        if ($stmt->execute()) {
            $successMessage = 'Subscription plan created for sector successfully!';
        } else {
            $errors[] = 'Error creating subscription: ' . $conn->error;
        }
    }
}

// Fetch available sectors from the sectors table
$sector_sql = "SELECT id, sectorName FROM sectors"; // Correct column name
$sector_result = $conn->query($sector_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Subscription Plan</title>
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
            --border-radius: 12px;
            --card-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #4a4a4a;
        }

        .main-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0;
        }

        .subscription-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 25px;
            text-align: center;
        }

        .card-title {
            font-weight: 600;
            margin: 0;
            font-size: 1.75rem;
        }

        .card-body {
            padding: 30px;
            background-color: white;
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

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            margin-top: 0.3rem;
            margin-right: 0.5rem;
        }

        .form-check-label {
            font-weight: 500;
            cursor: pointer;
        }

        .alert {
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background-color: rgba(247, 37, 133, 0.1);
            border-left: 4px solid var(--danger-color);
            color: var(--dark-color);
        }

        .alert-success {
            background-color: rgba(76, 201, 240, 0.1);
            border-left: 4px solid var(--success-color);
            color: var(--dark-color);
        }

        .error-list {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .error-item {
            color: var(--danger-color);
            font-weight: 500;
        }

        .success-message {
            color: var(--success-color);
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 15px;
            }
            
            .card-header {
                padding: 20px;
            }
            
            .card-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="subscription-card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-file-invoice-dollar me-2"></i>Create Subscription Plan
            </h2>
        </div>
        
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li class="error-item"><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success">
                    <p class="success-message">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($successMessage); ?>
                    </p>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-4">
                    <label for="name" class="form-label">Plan Name</label>
                    <input type="text" name="name" id="name" class="form-control" 
                           value="<?php echo htmlspecialchars($name); ?>" required
                           placeholder="Enter plan name">
                </div>

                <div class="mb-4">
                    <label for="base_price" class="form-label">Base Price (₹)</label>
                    <input type="number" name="base_price" id="base_price" class="form-control" 
                           value="<?php echo htmlspecialchars($base_price); ?>" required
                           placeholder="Enter base price" step="0.01" min="0">
                </div>

                <div class="mb-4">
                    <label for="max_support_calls" class="form-label">Max Support Calls</label>
                    <input type="number" name="max_support_calls" id="max_support_calls" class="form-control" 
                           value="<?php echo htmlspecialchars($max_support_calls); ?>" required
                           placeholder="Enter maximum support calls" min="0">
                </div>

                <div class="mb-4">
                    <label for="rewards_points" class="form-label">Rewards Points</label>
                    <input type="number" name="rewards_points" id="rewards_points" class="form-control" 
                           value="<?php echo htmlspecialchars($rewards_points); ?>" required
                           placeholder="Enter rewards points" min="0">
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" name="most_popular" id="most_popular" 
                           class="form-check-input" <?php echo ($most_popular == 1) ? 'checked' : ''; ?>>
                    <label for="most_popular" class="form-check-label">Mark as Most Popular</label>
                </div>

                <div class="mb-4">
                    <label for="sector" class="form-label">Select Sector</label>
                    <select name="sector" id="sector" class="form-select" required>
                        <option value="" disabled selected>-- Select a Sector --</option>
                        <?php if ($sector_result->num_rows > 0): ?>
                            <?php while ($sector = $sector_result->fetch_assoc()): ?>
                                <option value="<?php echo $sector['id']; ?>" <?php echo ($sectorId == $sector['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sector['sectorName']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No sectors available</option>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-plus-circle me-2"></i>Create Subscription Plan
                </button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>>