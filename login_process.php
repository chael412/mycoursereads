<?php
session_start();
include("config/db_localhost.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    $error = "";

    if ($user_type == 'student') {
        $query = "SELECT * FROM studentuser WHERE user_id='$user_id'";
    } else {
        $query = "SELECT * FROM facultyuser WHERE user_id='$user_id'";
    }

    $result = mysqli_query($conn, $query);

    if ($result) {
        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $_SESSION['user_type'] = $user_type;
                header("Location: index.php"); // Redirect to homepage or another page after successful login
                exit();
            } else {
                $_SESSION['login_error'] = "Invalid password.";
            }
        } else {
            $_SESSION['login_error'] = "Invalid user ID.";
        }
    } else {
        $_SESSION['login_error'] = "An error occurred while processing your request.";
    }

    // Redirect back to the index page to show the modal with the error message
    header("Location: index.php");
    exit();
}
?>
