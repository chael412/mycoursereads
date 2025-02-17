<?php
session_start();
include("config/db_localhost.php");

// Helper function to display error messages
function setAlertMessage($message, $type) {
    $_SESSION['registration_error'] = $message;
    header("location: index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $program_id = $_POST['program_id'];
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    // Check if user ID already exists
    $checkUserIdQuery = "SELECT * FROM studentuser WHERE user_id='$user_id'";
    $userIdResult = mysqli_query($conn, $checkUserIdQuery);

    if (mysqli_num_rows($userIdResult) > 0) {
        setAlertMessage("User ID already exists.", "error");
    }

    // Check if email already exists
    $checkEmailQuery = "SELECT * FROM studentuser WHERE email='$email'";
    $emailResult = mysqli_query($conn, $checkEmailQuery);

    if (mysqli_num_rows($emailResult) > 0) {
        setAlertMessage("Email already exists.", "error");
    }

    // Insert new student record if both checks pass
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $insertQuery = "INSERT INTO studentuser (first_name, last_name, email, program_id, user_id, password) 
                    VALUES ('$first_name', '$last_name', '$email', '$program_id', '$user_id', '$hashed_password')";

    if (mysqli_query($conn, $insertQuery)) {
        $_SESSION['registration_success'] = "Registration successful!";
        header("location: index.php");
        exit();
    } else {
        setAlertMessage("Registration failed. Please try again.", "error");
    }
}
?>
