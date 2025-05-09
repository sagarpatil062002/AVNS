<?php



include('Config.php');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pending_freelancers = $conn->query("SELECT * FROM freelancer WHERE is_approved = 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        .freelancer-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fafafa;
        }
        .freelancer-card h3 {
            margin: 0 0 10px;
            font-size: 1.2em;
            color: #333;
        }
        .freelancer-card p {
            margin: 5px 0;
            color: #555;
        }
        .freelancer-card a {
            color: #007BFF;
            text-decoration: none;
        }
        .freelancer-card a:hover {
            text-decoration: underline;
        }
        form {
            margin-top: 10px;
        }
        button {
            padding: 8px 15px;
            margin-right: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
        }
        button[name="approve"] {
            background-color: #28a745;
            color: #fff;
        }
        button[name="approve"]:hover {
            background-color: #218838;
        }
        button[name="reject"] {
            background-color: #dc3545;
            color: #fff;
        }
        button[name="reject"]:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Pending Freelancers</h1>
        <?php while ($freelancer = $pending_freelancers->fetch_assoc()): ?>
            <div class="freelancer-card">
                <h3><?php echo htmlspecialchars($freelancer['name']); ?> (<?php echo htmlspecialchars($freelancer['email']); ?>)</h3>
                <?php
                $freelancer_id = $freelancer['id'];
                $skills = $conn->query("SELECT fs.skill_id, s.skill_name, fs.certificate_path FROM freelancer_skills fs 
                                        JOIN skills s ON fs.skill_id = s.id WHERE fs.freelancer_id = $freelancer_id");
                ?>
                <?php while ($skill = $skills->fetch_assoc()): ?>
                    <p>Skill: <?php echo htmlspecialchars($skill['skill_name']); ?></p>
                    <a href="<?php echo htmlspecialchars($skill['certificate_path']); ?>" target="_blank">View Certificate</a>
                
                <?php endwhile; ?>
                <form method="POST" action="approve_freelancer.php">
                    <input type="hidden" name="freelancer_id" value="<?php echo $freelancer_id; ?>">
                    <button type="submit" name="approve">Approve</button>
                    <button type="submit" name="reject">Reject</button>
                </form>
            </div>
        <?php endwhile; ?>
    </div>
</body>
</html>
<?php
$conn->close();
?>
