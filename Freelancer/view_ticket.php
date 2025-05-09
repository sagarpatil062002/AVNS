<?php
// Include database configuration
include 'config.php';

// Assuming the freelancer is logged in and their ID is stored in a session
//session_start();
//$freelancerId = $_SESSION['freelancerId'];

//if (!$freelancerId) {
  //  die("Freelancer not logged in.");
//}

try {
    // Fetch freelancer's skills
    $freelancerSkillsQuery = $conn->prepare("SELECT skills FROM freelancer WHERE id = ?");
    $freelancerSkillsQuery->bind_param("i", $freelancerId);
    $freelancerSkillsQuery->execute();
    $freelancerSkillsQuery->bind_result($freelancerSkills);
    $freelancerSkillsQuery->fetch();
    $freelancerSkillsQuery->close();

    if (!$freelancerSkills) {
        die("No skills found for the freelancer.");
    }

    // Fetch tickets with matching skills
    $ticketQuery = $conn->prepare(
        "SELECT t.id, t.customerId, t.description, t.skills, t.priority, t.createdAt 
         FROM ticket t 
         WHERE FIND_IN_SET(t.skills, ?) > 0"
    );
    $ticketQuery->bind_param("s", $freelancerSkills);
    $ticketQuery->execute();
    $result = $ticketQuery->get_result();

    if ($result->num_rows > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Ticket ID</th><th>Customer ID</th><th>Description</th><th>Skills</th><th>Priority</th><th>Created At</th></tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['customerId']) . "</td>";
            echo "<td>" . htmlspecialchars($row['description']) . "</td>";
            echo "<td>" . htmlspecialchars($row['skills']) . "</td>";
            echo "<td>" . htmlspecialchars($row['priority']) . "</td>";
            echo "<td>" . htmlspecialchars($row['createdAt']) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "No tickets match your skills.";
    }

    $ticketQuery->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
