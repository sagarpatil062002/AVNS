<?php
include 'admin_navbar.php';
// Database connection
include('Config.php');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if (isset($_POST['action']) && isset($_POST['freelancer_id'])) {
    $freelancerId = intval($_POST['freelancer_id']);
    $action = $_POST['action'];

    // Prepare the SQL query
    if ($action === 'approve') {
        $sql = "UPDATE Freelancer SET status='approved' WHERE id=?";
    } else if ($action === 'reject') {
        $sql = "UPDATE Freelancer SET status='rejected' WHERE id=?";
    } else {
        die("Invalid action");
    }

    // Prepare and bind
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $freelancerId);

    // Execute the query
    if ($stmt->execute()) {
        echo "Freelancer profile updated successfully.";
    } else {
        echo "Error updating freelancer profile: " . $stmt->error;
    }

    // Close statement
    $stmt->close();
}

// Fetch freelancers for display
$sql = "SELECT id, name, status FROM Freelancer WHERE status='pending'";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Freelancer Approval</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <h1>Freelancer Approval</h1>
    <table border="1">
        <tr>
            <th>Name</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td>
                    <form method="post">
                        <input type="hidden" name="freelancer_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="action" value="approve">Approve</button>
                        <button type="submit" name="action" value="reject">Reject</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

    <?php
    // Close the database connection
    $conn->close();
    ?>
</body>
</html>
