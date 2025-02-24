<?php
session_start();
include("config/db_vivliotekonline.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Use prepared statements to prevent SQL injection
    $query = "SELECT * FROM employees WHERE email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_assoc($result);
        $id = $row['id']; // Fetch user ID

        if (password_verify($password, $row['password'])) {
            // Store user information in session
            $_SESSION['user_id'] = $id; // Changed 'users' to 'user_id' for consistency
            $_SESSION['email'] = $email;
            $_SESSION['first_name'] = $row['first_name']; // Ensure 'first_name' exists in the table

            // Redirect to the dashboard after successful login
            header("Location: dashboardpage/dashboards.php");
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | MyCourseReads</title>

    <!-- CDN LINKS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="login/style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <script>
        function setUserType(type) {
            document.getElementById("user_type").value = type;
            document.getElementById("user_id").value = type === 'librarian' ? 'librarian123' : 'staff123';
            document.getElementById("password").value = '12345';
            // Update button styles
            document.querySelectorAll(".user-type-btn").forEach(btn => {
                btn.classList.remove("active");
            });
            document.getElementById(type + "-btn").classList.add("active");
        }
    </script>
    <style>
        .user-type-btn {
            margin: 0 5px;
        }

        .user-type-btn.active {
            background-color: #F3CA52;
            color: white;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <!-- LOGIN page  -->
    <section id="intro">
        <div class="container">
            <div class="row my-3 align-items-center justify-content-center">
                <div class="card col-5 mx-3 my-3" style="width: 20rem;">
                    <div class="card-body">
                        <div class="col text-center">
                            <i class="bi-10x bi-person-circle" style="font-size: 50px;"></i>
                        </div>
                        <h4 class="card-title text-center">LOGIN</h4>
                        <p class="text-center fs-7">MyCourseReads</p>
                        <form method="post" action="">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input class="form-control form-control-sm" id="email" name="email" required="required">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control form-control-sm" id="password" name="password" required="required">
                            </div>
                            <div class="mb-3">
                                <input type="checkbox" onclick="myFunction()"> Show Password
                            </div>
                            <script>
                                function myFunction() {
                                    var x = document.getElementById("password");
                                    if (x.type === "password") {
                                        x.type = "text";
                                    } else {
                                        x.type = "password";
                                    }
                                }
                            </script>
                            <div class="d-grid gap-2 col mx-auto">
                                <button type="submit" class="btn">LOGIN</button>
                            </div>
                        </form>
                        <?php
                        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($error)) {
                            echo '<div class="alert alert-danger mt-3" role="alert">' . $error . '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>
