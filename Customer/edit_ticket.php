<?php
// Database connection (replace with your database credentials)
include('Config.php');
// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $customerId = $_POST['customerId'];
    $freelancerId = $_POST['freelancerId'];
    $status = $_POST['status'];
    $description = $_POST['description'];

    $sql = "UPDATE Ticket SET customerId = ?, freelancerId = ?, status = ?, description = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissi", $customerId, $freelancerId, $status, $description, $id);
    if ($stmt->execute()) {
        echo "Ticket updated successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Fetch the ticket to edit
$id = $_GET['id'];
$sql = "SELECT * FROM Ticket WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Ticket</title>
</head>
<body>
    <h1>Edit Ticket</h1>
    <form method="post">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($ticket['id']); ?>">
        <label for="customerId">Customer ID:</label>
        <input type="number" name="customerId" id="customerId" value="<?php echo htmlspecialchars($ticket['customerId']); ?>" required><br>
        <label for="freelancerId">Freelancer ID:</label>
        <input type="number" name="freelancerId" id="freelancerId" value="<?php echo htmlspecialchars($ticket['freelancerId']); ?>" required><br>
        <label for="status">Status:</label>
        <select name="status" id="status" required>
            <option value="PENDING" <?php echo ($ticket['status'] == 'PENDING') ? 'selected' : ''; ?>>PENDING</option>
            <option value="ASSIGNED" <?php echo ($ticket['status'] == 'ASSIGNED') ? 'selected' : ''; ?>>ASSIGNED</option>
            <option value="IN_PROGRESS" <?php echo ($ticket['status'] == 'IN_PROGRESS') ? 'selected' : ''; ?>>IN_PROGRESS</option>
            <option value="RESOLVED" <?php echo ($ticket['status'] == 'RESOLVED') ? 'selected' : ''; ?>>RESOLVED</option>
            <option value="REJECTED" <?php echo ($ticket['status'] == 'REJECTED') ? 'selected' : ''; ?>>REJECTED</option>
        </select><br>
        <label for="description">Description:</label>
        <textarea name="description" id="description" required><?php echo htmlspecialchars($ticket['description']); ?></textarea><br>
        <button type="submit">Update Ticket</button>
    </form>
</body>
</html>
<?php
// Close the connection
$conn->close();
?>
