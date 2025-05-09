<?php
// Database connection
include('Config.php');

include('Dnav.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// Check if the freelancer is logged in
if (isset($_SESSION['user_id'])) {
    $freelancerId = $_SESSION['user_id']; // Logged-in freelancer ID
} else {
    die("No freelancer is logged in. Please log in.");
}

// Ensure tables exist
$skillsTableSql = "
CREATE TABLE IF NOT EXISTS skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(100) NOT NULL UNIQUE
)";
$conn->query($skillsTableSql);

$freelancerSkillsTableSql = "
CREATE TABLE IF NOT EXISTS freelancer_skills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    freelancer_id INT NOT NULL,
    skill_id INT NOT NULL,
    certificate_path VARCHAR(255),
    is_approved BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (freelancer_id) REFERENCES freelancer(id),
    FOREIGN KEY (skill_id) REFERENCES skills(id)
)";
$conn->query($freelancerSkillsTableSql);

// Fetch freelancer data
$sql = "SELECT * FROM freelancer WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $freelancerId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $freelancer = $result->fetch_assoc();
} else {
    die("Freelancer not found.");
}

// Fetch all available skills
$skillsQuery = "SELECT * FROM skills";
$skillsResult = $conn->query($skillsQuery);

// Fetch skills associated with the freelancer
$freelancerSkillsSql = "
SELECT skills.skill_name, freelancer_skills.certificate_path, freelancer_skills.is_approved
FROM freelancer_skills
JOIN skills ON freelancer_skills.skill_id = skills.id
WHERE freelancer_skills.freelancer_id = ?";
$freelancerSkillsStmt = $conn->prepare($freelancerSkillsSql);
$freelancerSkillsStmt->bind_param("i", $freelancerId);
$freelancerSkillsStmt->execute();
$freelancerSkillsResult = $freelancerSkillsStmt->get_result();
$skills = $freelancerSkillsResult->fetch_all(MYSQLI_ASSOC);

// Handle form submission for updating profile image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $profileImage = $_FILES['profile_image'];

    if ($profileImage['error'] === 0 && in_array($profileImage['type'], ['image/jpeg', 'image/png', 'image/jpg'])) {
        $imageExtension = pathinfo($profileImage['name'], PATHINFO_EXTENSION);
        $profileImagePath = 'uploads/' . uniqid() . '.' . $imageExtension;
        move_uploaded_file($profileImage['tmp_name'], $profileImagePath);

        $updateImageSql = "UPDATE freelancer SET image = ? WHERE id = ?";
        $updateImageStmt = $conn->prepare($updateImageSql);
        $updateImageStmt->bind_param("si", $profileImagePath, $freelancerId);
        $updateImageStmt->execute();

        echo "<div class='alert alert-success text-center'>Profile image updated successfully.</div>";
        header("Refresh:0");
    } else {
        echo "<div class='alert alert-danger text-center'>Please upload a valid image file (JPG, JPEG, PNG).</div>";
    }
}

// Handle form submission for adding skills
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skill'])) {
    $skillId = $_POST['skill'];
    $certificate = $_FILES['certificate'];

    if (!empty($skillId)) {
        // Check if the skill is already associated with the freelancer
        $checkAssociationSql = "SELECT * FROM freelancer_skills WHERE freelancer_id = ? AND skill_id = ?";
        $checkAssociationStmt = $conn->prepare($checkAssociationSql);
        $checkAssociationStmt->bind_param("ii", $freelancerId, $skillId);
        $checkAssociationStmt->execute();
        $associationResult = $checkAssociationStmt->get_result();

        if ($associationResult->num_rows > 0) {
            echo "<div class='alert alert-warning text-center'>This skill is already associated with your profile.</div>";
        } else {
            // Validate certificate upload
            if ($certificate['error'] === 0 && $certificate['type'] === 'application/pdf') {
                $certificatePath = 'uploads/' . uniqid() . '_' . $certificate['name'];
                move_uploaded_file($certificate['tmp_name'], $certificatePath);

                $addSkillSql = "INSERT INTO freelancer_skills (freelancer_id, skill_id, certificate_path) VALUES (?, ?, ?)";
                $addSkillStmt = $conn->prepare($addSkillSql);
                $addSkillStmt->bind_param("iis", $freelancerId, $skillId, $certificatePath);
                $addSkillStmt->execute();

                echo "<div class='alert alert-success text-center'>Skill added successfully and is pending approval.</div>";
                header("Refresh:0");
            } else {
                echo "<div class='alert alert-danger text-center'>Please upload a valid PDF certificate.</div>";
            }
        }
    } else {
        echo "<div class='alert alert-warning text-center'>Please select a skill before uploading a certificate.</div>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Freelancer Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .profile-container {
            max-width: 1200px;
            margin: 2rem auto 2rem 310px;
            padding: 0;
                        margin-top: 0px;

        }

        .profile-card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 1rem;
        }

        .profile-body {
            padding: 2rem;
            background: white;
        }

        .section-title {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eee;
        }

        .info-item {
            margin-bottom: 1.25rem;
        }

        .info-label {
            font-weight: 500;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }

        .info-value {
            background-color: var(--light-bg);
            padding: 0.75rem;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }

        .skills-list {
            list-style: none;
            padding: 0;
        }

        .skill-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            margin-bottom: 0.75rem;
            background-color: var(--light-bg);
            border-radius: 8px;
            transition: all 0.2s;
        }

        .skill-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .skill-status {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 500;
        }

        .status-approved {
            background-color: #e6f7ee;
            color: #00a854;
        }

        .status-pending {
            background-color: #fff7e6;
            color: #fa8c16;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.15);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .file-upload {
            position: relative;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .file-upload-input {
            position: absolute;
            font-size: 100px;
            opacity: 0;
            right: 0;
            top: 0;
        }

        .file-upload-label {
            display: block;
            padding: 1rem;
            background-color: var(--light-bg);
            border: 2px dashed #ced4da;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .file-upload-label:hover {
            background-color: #e9ecef;
        }

        @media (max-width: 1200px) {
            .profile-container {
                margin-left: 0;
                padding: 1rem;
            }
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 1.5rem;
            }
            
            .profile-body {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <h3><?php echo htmlspecialchars($freelancer['name']); ?></h3>
            <p class="text-white-50 mb-0">Freelancer Profile</p>
        </div>
        
        <div class="profile-body">
            <h5 class="section-title"><i class="fas fa-user-circle me-2"></i>Basic Information</h5>
            
            <div class="info-item">
                <div class="info-label">Email Address</div>
                <div class="info-value"><?php echo htmlspecialchars($freelancer['email']); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Experience</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($freelancer['experience']); ?> year(s)
                </div>
            </div>
            
            <h5 class="section-title mt-4"><i class="fas fa-tools me-2"></i>My Skills</h5>
            
            <?php if (!empty($skills)): ?>
                <ul class="skills-list">
                    <?php foreach ($skills as $skill): ?>
                        <li class="skill-item">
                            <span><?php echo htmlspecialchars($skill['skill_name']); ?></span>
                            <span class="skill-status <?php echo $skill['is_approved'] ? 'status-approved' : 'status-pending'; ?>">
                                <?php echo $skill['is_approved'] ? 'Approved' : 'Pending'; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>You haven't added any skills yet.
                </div>
            <?php endif; ?>
            
            <h5 class="section-title mt-4"><i class="fas fa-plus-circle me-2"></i>Add New Skill</h5>
            
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Select Skill</label>
                    <select name="skill" class="form-select" required>
                        <option value="">-- Select a Skill --</option>
                        <?php while ($row = $skillsResult->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>">
                                <?php echo htmlspecialchars($row['skill_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Upload Certificate</label>
                    <div class="file-upload">
                        <label class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt me-2"></i>
                            <span>Choose PDF file (Max 5MB)</span>
                            <input type="file" class="file-upload-input" name="certificate" accept="application/pdf" required>
                        </label>
                    </div>
                    <small class="text-muted">Only PDF files are accepted for certificates.</small>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Add Skill
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // File upload display
    document.querySelector('.file-upload-input').addEventListener('change', function() {
        const fileName = this.files[0]?.name || 'No file chosen';
        this.previousElementSibling.textContent = fileName;
    });
</script>
</body>
</html>