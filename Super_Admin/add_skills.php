<?php
include 'admin_navbar.php';
// Database connection
include('Config.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = "";
$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fetch form data
    $skill_name = trim($_POST['skill_name']);

    // Validate input
    if (!empty($skill_name)) {
        // Insert into skills table
        $sql = "INSERT INTO skills (skill_name) VALUES ('$skill_name')";

        if ($conn->query($sql) === TRUE) {
            $successMessage = "New skill added successfully!";
        } else {
            // Handle duplicate skill or other database errors
            if ($conn->errno === 1062) {
                $errorMessage = "Skill already exists!";
            } else {
                $errorMessage = "Error: " . $conn->error;
            }
        }
    } else {
        $errorMessage = "Skill name cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add New Skill</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary-color: #4361ee;
            --primary-light: #4895ef;
            --primary-dark: #3a0ca3;
            --secondary-color: #7209b7;
            --success-color: #4cc9f0;
            --error-color: #f72585;
            --light-gray: #f8f9fa;
            --dark-gray: #6c757d;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: var(--light-gray);
            color: #333;
            line-height: 1.6;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        .content-wrapper {
            flex: 1;
            padding: 2rem;
            margin-left: 250px;
            transition: var(--transition);
        }

        .card {
            max-width: 600px;
            width: 100%;
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            padding: 2.5rem;
            margin: 2rem auto;
            border: none;
        }

        .card-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .card-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .card-subtitle {
            color: var(--dark-gray);
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #495057;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            transition: var(--transition);
            background-color: var(--light-gray);
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
            outline: none;
            background-color: var(--white);
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: rgba(76, 201, 240, 0.15);
            color: #0a9396;
            border-left: 4px solid #0a9396;
        }

        .alert-error {
            background-color: rgba(247, 37, 133, 0.15);
            color: var(--error-color);
            border-left: 4px solid var(--error-color);
        }

        .icon {
            font-size: 1.25rem;
        }

        @media (max-width: 992px) {
            .content-wrapper {
                margin-left: 0;
                padding: 1.5rem;
            }
            
            .card {
                padding: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            .card {
                padding: 1.25rem;
            }
            
            .card-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="main-container">
    <div class="content-wrapper">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Add New Skill</h1>
                <p class="card-subtitle">Add a new skill to your system</p>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="skill_name" class="form-label">Skill Name</label>
                    <input type="text" id="skill_name" name="skill_name" class="form-control" required placeholder="Enter skill name">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="uil uil-plus-circle icon"></i> Add Skill
                </button>
                
                <?php 
                if (!empty($successMessage)) {
                    echo '<div class="alert alert-success">
                            <i class="uil uil-check-circle icon"></i> '.$successMessage.'
                          </div>';
                }
                if (!empty($errorMessage)) {
                    echo '<div class="alert alert-error">
                            <i class="uil uil-exclamation-circle icon"></i> '.$errorMessage.'
                          </div>';
                }
                ?>
            </form>
        </div>
    </div>
</div>

<script>
    // Add animation to form elements
    document.addEventListener('DOMContentLoaded', function() {
        const formControls = document.querySelectorAll('.form-control');
        formControls.forEach(control => {
            control.addEventListener('focus', function() {
                this.parentElement.querySelector('.form-label').style.color = 'var(--primary-color)';
            });
            control.addEventListener('blur', function() {
                this.parentElement.querySelector('.form-label').style.color = '#495057';
            });
        });
    });
</script>
</body>
</html>