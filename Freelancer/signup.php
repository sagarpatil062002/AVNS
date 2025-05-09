<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "sales_management";

$conn = new mysqli($host, $username, $password, $database);

// Fetch all available skills for the user to select during registration
$query = "SELECT * FROM skills";
$result = $conn->query($query);
$allSkills = [];
while ($row = $result->fetch_assoc()) {
    $allSkills[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Freelancer Sign-Up</title>
    <style>
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
    padding: 20px;
}

.wrapper {
    width: 100%;
    max-width: 500px;
    background: #fff;
    border-radius: 15px;
    box-shadow: 0px 10px 20px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.wrapper .title {
    font-size: 30px;
    font-weight: 700;
    text-align: center;
    color: #fff;
    background: linear-gradient(45deg, #0e4bf1, #4158d0);
    padding: 20px;
    border-radius: 15px 15px 0 0;
}

.wrapper form {
    padding: 20px;
}

.wrapper form .field {
    width: 100%;
    margin-bottom: 20px;
}

.wrapper form .field label {
    font-size: 16px;
    font-weight: 500;
    margin-bottom: 5px;
    color: #333;
    display: block;
}

.wrapper form .field input,
.wrapper form .field select {
    width: 100%;
    padding: 12px 15px;
    font-size: 16px;
    border-radius: 25px;
    border: 1px solid #ccc;
    outline: none;
    transition: all 0.3s ease;
    background: #f9f9f9;
}

.wrapper form .field input:focus,
.wrapper form .field select:focus {
    border-color: #4158d0;
    background: #fff;
}

.wrapper form .field input[type="submit"] {
    background: linear-gradient(45deg, #0e4bf1, #4158d0);
    color: #fff;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    padding: 15px;
    transition: all 0.3s ease;
}

.wrapper form .field input[type="submit"]:hover {
    background: linear-gradient(45deg, #4158d0, #0e4bf1);
    transform: scale(1.02);
}

.skills-container {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.skills-container .skill-item {
    width: calc(50% - 10px);
    display: flex;
    align-items: center;
    font-size: 14px;
}

.skills-container input[type="checkbox"] {
    margin-right: 10px;
    cursor: pointer;
    accent-color: #4158d0;
}

.signup-link {
    text-align: center;
    margin-top: 15px;
    font-size: 14px;
}

.signup-link a {
    color: #4158d0;
    text-decoration: none;
    font-weight: 500;
}

.signup-link a:hover {
    text-decoration: underline;
}

.alert {
    color: red;
    text-align: center;
    margin-top: 10px;
    font-weight: 500;
}

/* Responsive Design */
@media (max-width: 768px) {
    .wrapper {
        width: 90%;
    }

    .skills-container .skill-item {
        width: 100%;
    }

    .wrapper .title {
        font-size: 24px;
    }
}

    </style>
</head>
<body>
<div class="wrapper">
    <div class="title">Freelancer Sign-Up</div>
    <form action="signup_process.php" method="POST">
        <div class="field">
        <label>Full Name</label>
            <input type="text" id="name" name="name" required>
        </div>
        <div class="field">
        <label>Email Address</label>
            <input type="email" id="email" name="email" required>
           
        </div>
        <div class="field">
        <label>Password</label>
            <input type="password" id="password" name="password" required>
           
        </div>
        <div class="field">
            <label>Select Skills:</label>
            <div class="skills-container">
                <?php foreach ($allSkills as $skill): ?>
                    <div class="skill-item">
                        <label><?= htmlspecialchars($skill['skill_name']); ?></label>
                        <input type="checkbox" name="skills[]" value="<?= $skill['id']; ?>">

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="field">
            <input type="submit" value="Sign Up">
        </div>
        <div class="signup-link">
            Already a member? <a href="login.php">Login now</a>
        </div>
    </form>
</div>
</body>
</html>
