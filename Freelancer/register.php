<?php
// Freelancer Registration Page (register_freelancer.php)
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    $experience = $_POST['experience'] ?? '';
    $skills = $_POST['skills'] ?? [];
    $certificates = $_FILES['certificates'] ?? [];

    // Start transaction
    $conn->begin_transaction();
    try {
        // Insert freelancer details
        $stmt = $conn->prepare("INSERT INTO freelancer (name, email, password, experience) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $email, $password, $experience);
        $stmt->execute();
        $freelancer_id = $stmt->insert_id;

        // Insert skills and upload certificates
        $stmt = $conn->prepare("INSERT INTO freelancer_skills (freelancer_id, skill_id, certificate_path) VALUES (?, ?, ?)");
        foreach ($skills as $skill_id) {
            $certificate_path = '';
            if (isset($certificates['tmp_name'][$skill_id]) && $certificates['tmp_name'][$skill_id]) {
                $certificate_path = 'uploads/' . basename($certificates['name'][$skill_id]);
                move_uploaded_file($certificates['tmp_name'][$skill_id], $certificate_path);
            }
            $stmt->bind_param('iis', $freelancer_id, $skill_id, $certificate_path);
            $stmt->execute();
        }

        $conn->commit();
        echo "Freelancer registered successfully! Please wait for approval.";
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error in registration: " . $e->getMessage();
    }
}

// Fetch skills from the database
$skills_result = $conn->query("SELECT id, skill_name FROM skills");
$skills_options = [];
if ($skills_result->num_rows > 0) {
    while ($row = $skills_result->fetch_assoc()) {
        $skills_options[] = $row;
    }
}
?>

<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : '';
    $experience = $_POST['experience'] ?? '';
    $skills = $_POST['skills'] ?? [];
    $certificates = $_FILES['certificates'] ?? [];

    // Start transaction
    $conn->begin_transaction();
    try {
        // Insert freelancer details
        $stmt = $conn->prepare("INSERT INTO freelancer (name, email, password, experience) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $name, $email, $password, $experience);
        $stmt->execute();
        $freelancer_id = $stmt->insert_id;

        // Insert skills and upload certificates
        $stmt = $conn->prepare("INSERT INTO freelancer_skills (freelancer_id, skill_id, certificate_path) VALUES (?, ?, ?)");
        foreach ($skills as $skill_id) {
            $certificate_path = '';
            if (isset($certificates['tmp_name'][$skill_id]) && $certificates['tmp_name'][$skill_id]) {
                $certificate_path = 'uploads/' . basename($certificates['name'][$skill_id]);
                move_uploaded_file($certificates['tmp_name'][$skill_id], $certificate_path);
            }
            $stmt->bind_param('iis', $freelancer_id, $skill_id, $certificate_path);
            $stmt->execute();
        }

        $conn->commit();
        echo "Freelancer registered successfully! Please wait for approval.";
    } catch (Exception $e) {
        $conn->rollback();
    }
}

// Fetch skills from the database
$skills_result = $conn->query("SELECT id, skill_name FROM skills");
$skills_options = [];
if ($skills_result->num_rows > 0) {
    while ($row = $skills_result->fetch_assoc()) {
        $skills_options[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Registration</title>
    <style>
        /* Same CSS block from the sample code */
        @import url('https://fonts.googleapis.com/css?family=Poppins:400,500,600,700&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        html, body {
            display: grid;
            height: 100%;
            width: 100%;
            place-items: center;
            background: #f2f2f2;
        }
        .wrapper {
            width: 420px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0px 15px 20px rgba(0, 0, 0, 0.1);
        }
        .wrapper .title {
            font-size: 35px;
            font-weight: 600;
            text-align: center;
            line-height: 100px;
            color: #fff;
            user-select: none;
            border-radius: 15px 15px 0 0;
            background: #0e4bf1;
        }
        .wrapper form {
            padding: 10px 30px 50px 30px;
        }
        .wrapper form .field {
            margin-top: 20px;
            position: relative;
        }
        .wrapper form .field input, 
        .wrapper form .field select {
            width: 100%;
            outline: none;
            font-size: 17px;
            padding: 10px;
            border: 1px solid lightgrey;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .wrapper form .field input:focus, 
        form .field select:focus {
            border-color: #4158d0;
        }
        .wrapper form .field label {
            display: block;
            margin-bottom: 5px;
            font-size: 16px;
            color: #333;
        }
        .wrapper form .field .skill-container {
            margin-top: 15px;
        }
        .skill-container .skill-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .skill-item span {
            margin-right: 10px;
            font-size: 15px;
            font-weight: 500;
        }
        form .field input[type="submit"] {
            color: #fff;
            border: none;
            margin-top: 20px;
            padding: 10px;
            font-size: 18px;
            font-weight: 500;
            cursor: pointer;
            background: #0e4bf1;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        form .field input[type="submit"]:active {
            transform: scale(0.95);
        }
        .alert {
            text-align: center;
            color: red;
            margin-top: 10px;
        }
    </style>
    <script>
        /* Same JavaScript logic for dynamically adding skills */
        function addSkill() {
            const skillDropdown = document.getElementById('skills-dropdown');
            const selectedSkillId = skillDropdown.value;
            const selectedSkillText = skillDropdown.options[skillDropdown.selectedIndex].text;

            if (!selectedSkillId) return;

            const container = document.getElementById('skills-container');
            
            // Check if skill already added
            if (document.getElementById(`skill-${selectedSkillId}`)) {
                alert('Skill already selected.');
                return;
            }

            // Create skill item
            const skillItem = document.createElement('div');
            skillItem.className = 'skill-item';
            skillItem.id = `skill-${selectedSkillId}`;

            // Add label
            const label = document.createElement('span');
            label.textContent = selectedSkillText;

            // Add hidden input for skill id
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'skills[]';
            hiddenInput.value = selectedSkillId;

            // Add file upload input
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.name = `certificates[${selectedSkillId}]`;
            fileInput.accept = 'application/pdf,image/*';

            // Add remove button
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.textContent = 'Remove';
            removeButton.style.marginLeft = '10px';
            removeButton.onclick = () => {
                container.removeChild(skillItem);
            };

            // Append to skill item
            skillItem.appendChild(label);
            skillItem.appendChild(hiddenInput);
            skillItem.appendChild(fileInput);
            skillItem.appendChild(removeButton);

            // Append to container
            container.appendChild(skillItem);
        }
    </script>
</head>
<body>
<div class="wrapper">
    <div class="title">Freelancer Register</div>
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="field">
            <label>Name</label>
            <input type="text" name="name" required>
        </div>
        <div class="field">
            <label>Email Address</label>
            <input type="email" name="email" required>
        </div>
        <div class="field">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="field">
            <label>Experience (years)</label>
            <input type="number" name="experience" required>
        </div>
        <div class="field">
            <label>Select Skills</label>
            <select id="skills-dropdown">
                <option value="">Select Skill</option>
                <?php foreach ($skills_options as $skill): ?>
                    <option value="<?php echo $skill['id']; ?>"><?php echo $skill['skill_name']; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" onclick="addSkill()">Add Skill</button>
        </div>
        <div class="field skill-container" id="skills-container">
            <!-- Dynamically added skills will appear here -->
        </div>
        <?php if (isset($error)) { echo "<div class='alert'>$error</div>"; } ?>
        <div class="field">
            <input type="submit" value="Register">
        </div>
    </form>
</div>
</body>
</html>
