<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "sales_management";


$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve freelancer information
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password for security
    $skills = isset($_POST['skills']) ? $_POST['skills'] : []; // Array of selected skill IDs

    // Insert freelancer data into the freelancer table
    $query = "INSERT INTO freelancer (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $name, $email, $password);
    
    if ($stmt->execute()) {
        $freelancerId = $stmt->insert_id; // Get the newly inserted freelancer's ID

        // Now, insert the selected skills into the freelancer_skills table
        $insertQuery = "INSERT INTO freelancer_skills (freelancer_id, skill_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insertQuery);

        // Loop through the selected skills and insert them
        foreach ($skills as $skillId) {
            $stmt->bind_param("ii", $freelancerId, $skillId);
            $stmt->execute();
        }

        echo "<div class='alert alert-success'>Freelancer registered successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error registering freelancer: " . $conn->error . "</div>";
    }
    
    $stmt->close();
    $conn->close();
}
?>
