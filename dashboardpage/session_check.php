<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../adminlogin.php");
    exit();
}
// above is session check to protect pages
?>