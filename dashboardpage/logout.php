<?php
// logout.php
session_start();
session_unset();   // Unset all session variables
session_destroy(); // Destroy the session
header("Location: ../adminlogin.php"); // Redirect to the login page or homepage
exit();
?>
