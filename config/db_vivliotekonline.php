<?php
$servername = "localhost"; // Replace with your database server name
$username = "u419133586_inertia"; // Replace with your database username
$password = "Inertia@1234"; // Replace with your database password
$dbname = "u419133586_dbinertia"; // Replace with your database name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>

