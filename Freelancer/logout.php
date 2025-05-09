<?php
session_start();

// Destroy the session to log the user out
session_unset();
session_destroy();

// Redirect to the login page after logout
header('Location: login.php');
exit();
?>
