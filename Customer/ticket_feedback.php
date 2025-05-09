<?php
session_start();
include 'config.php';

// Ensure customer access
if ($_SESSION['role'] != 'CUSTOMER') {
    header('Location: dashboard.php');
    exit;
}

$ticket_id = $_GET['id'];

// Fetch ticket details
$stmt = $conn->prepare("SELECT t.id, f.name as freelancer_name, t.description 
                        FROM tickets t 
                        JOIN users f ON t.freelancer_id = f.id 
                        WHERE t.id = ?");
$stmt->bind_param('i', $ticket_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $feedback = $_POST['feedback'];
    $rating = $_POST['rating'];

    // Insert feedback into the database (assuming a feedback table exists)
    $stmt = $conn->prepare("INSERT INTO feedback (ticket_id, feedback, rating) VALUES (?, ?, ?)");
    $stmt->bind_param('isi', $ticket_id, $feedback, $rating);

    if ($stmt->execute()) {
        header('Location: view_tickets.php');
    } else {
        $error = "Failed to submit feedback!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Ticket Feedback</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <h3>Provide Feedback for Ticket</h3>
    <div class="card">
        <div class="card-header">
            Ticket ID: <?= $ticket['id'] ?> | Freelancer: <?= $ticket['freelancer_name'] ?>
        </div>
        <div class="card-body">
            <p><strong>Description:</strong> <?= $ticket['description'] ?></p>

            <form method="POST">
                <div class="form-group">
                    <label>Feedback</label>
                    <textarea name="feedback" class="form-control" required></textarea>
                </div>
                <div class="form-group">
                    <label>Rating</label>
                    <select name="rating" class="form-control" required>
                        <option value="1">1 - Poor</option>
                        <option value="2">2 - Fair</option>
                        <option value="3">3 - Good</option>
                        <option value="4">4 - Very Good</option>
                        <option value="5">5 - Excellent</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
                <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
            </form>
        </div>
    </div>
</div>
</body>
</html>
