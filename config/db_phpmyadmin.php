<?php
$servername = "localhost"; // Replace with your database server name
$username = "gsoftedu_mcr"; // Replace with your database username
$password = "uuZBTMtsWmUnN6xVjRmU"; // Replace with your database password
$dbname = "gsoftedu_mcr"; // Replace with your database name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

