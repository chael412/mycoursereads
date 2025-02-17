<?php
include("config/db_localhost.php");

// Check if a session is not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate session
if (isset($_SESSION['user_id'])) {
    if (empty($_SESSION['user_name'])) {
        // If there's a user_id but no user_name, something's wrong with the session.
        session_unset();
        session_destroy();
        header("Location: index.php"); // Redirect to the homepage or login page
        exit();
    } else {
        $isLoggedIn = true;
        $userName = $_SESSION['user_name'];
    }
} else {
    $isLoggedIn = false;
    $userName = '';
}

// Optional: Clear any lingering error messages after displaying them
$error_message = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
unset($_SESSION['login_error']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyCourseReads</title>
    <link rel="icon" href="img/isulogo.png">

    <!-- CDN LINKS -->
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Show the login modal if there's an error message
            var errorMessage = <?php echo json_encode($error_message); ?>;
            if (errorMessage) {
                var loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            }
        });
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

<body>

    <!-- navbar -->
    <nav class="navbar navbar-expand-md fixed-top py-3">
        <div class="container-xxl">
            <!-- Left section: Brand and Search Bar -->
            <div class="d-flex">
                <a href="#" class="navbar-brand d-none d-lg-block">
                    <span class="logo1 fw-bold">
                        <img src="img/isulogo.png" alt="Logo" width="30px" height="30px" class="d-inline-block align-text-top">
                        MyCourseReads
                    </span>
                </a>

                <!-- Search Bar -->
                <?php if ($isLoggedIn) : ?>
                    <form class="d-flex ms-4" role="search" style="width: 500px;" action="library.php" method="GET">
                        <input class="form-control me-2" type="search" placeholder="Search by title, author, year, subject..." name="search" aria-label="Search">
                        <button class="btn" type="submit"><i class="lni lni-search-alt"></i></button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- toggle for mobile navbar -->
            <button class="navbar-toggler custom-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav" aria-controls="main-nav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Center section: Navbar links -->
            <div class="collapse navbar-collapse justify-content-center align-center" tabindex="-1" id="main-nav">
                <ul class="navbar-nav">
                    <!-- HOME -->
                    <li class="nav-item ms-3">
                        <a class="nav-link color-text" href="index.php">
                            <i class="lni lni-home"></i> Home
                        </a>
                    </li>

                    <!-- ABOUT -->
                    <li class="nav-item ms-3">
                        <a class="nav-link color-text" href="index.php#about">About Us</a>
                        </a>
                    </li>

                    <!-- Library -->
                    <?php if ($isLoggedIn) : ?>
                        <li class="nav-item ms-3">
                            <a class="nav-link color-text" href="library.php">
                                Library
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Account -->
                    <li class="nav-item ms-3 dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?php echo $isLoggedIn ? htmlspecialchars($userName) : 'Login'; ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="accountDropdown">
                            <?php if (!$isLoggedIn) : ?>
                                <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a></li>
                            <?php else : ?>
                                <li><a class="dropdown-item" href="account.php">Account</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginModalLabel">Login</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($error_message) : ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    <form method="post" action="login_process.php">
                        <div class="mb-3 text-center">
                            <!-- User Type Buttons -->
                            <button type="button" id="student-btn" class="btn user-type-btn active" onclick="setUserType('student')">Student</button>
                            <button type="button" id="faculty-btn" class="btn user-type-btn" onclick="setUserType('faculty')">Faculty</button>
                            <a href="adminlogin.php" class="btn user-type-btn">Admin</a>
                        </div>
                        <!-- Hidden Input for User Type -->
                        <input type="hidden" name="user_type" id="user_type" value="student" required>
                        <div class="mb-3">
                            <label class="form-label">User ID</label>
                            <input class="form-control form-control-sm" id="user_id" name="user_id" required="required">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control form-control-sm" id="password" name="password" required="required">
                        </div>
                        <div class="mb-3">
                            <input type="checkbox" onclick="togglePassword()"> Show Password
                        </div>
                        <div class="d-grid gap-2 col mx-auto">
                            <button class="btn" type="submit">Login</button>
                        </div>
                        <div class="mt-3 text-center">
                            <p class="mb-0">Don't have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#studentRegisterModal">Register here</a></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Student Registration Modal -->
    <div class="modal fade" id="studentRegisterModal" tabindex="-1" aria-labelledby="studentRegisterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="studentRegisterModalLabel">Student Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="registerstudent_process.php">
                        <div class="mb-3 text-center">
                            <button class="btn" style="background-color: #F3CA52;" type="button">Student</button>
                            <button class="btn" type="button" data-bs-toggle="modal" data-bs-target="#facultyRegisterModal">Faculty</button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control form-control-sm" name="first_name" required="required">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control form-control-sm" name="last_name" required="required">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-sm" id="email" name="email" required="required">
                        </div>

                        <div id="program-container" class="mb-3">
                            <label class="form-label">Program</label>
                            <select id="program_id" name="program_id" class="form-control form-control-sm">
                                <option value="" disabled selected>Select Program</option>
                                <?php
                                // Fetch program data from the programs table
                                $programQuery = "SELECT * FROM programs";
                                $programResult = mysqli_query($conn, $programQuery);
                                while ($program = mysqli_fetch_assoc($programResult)) {
                                    echo '<option value="' . $program['id'] . '">' . $program['program_name'] . ' - ' . $program['major'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User ID</label>
                            <input type="text" class="form-control form-control-sm" id="user_id" name="user_id" required="required">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control form-control-sm" id="studentPassword" name="password" required="required">
                        </div>
                        <div class="mb-3">
                            <input type="checkbox" onclick="togglePasswordVisibility('studentPassword')"> Show Password
                        </div>
                        <button type="submit" class="btn">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Faculty Registration Modal -->
    <div class="modal fade" id="facultyRegisterModal" tabindex="-1" aria-labelledby="facultyRegisterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="facultyRegisterModalLabel">Faculty Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="registerfaculty_process.php">
                        <div class="mb-3 text-center">
                            <button class="btn" type="button" data-bs-toggle="modal" data-bs-target="#studentRegisterModal">Student</button>
                            <button class="btn" style="background-color: #F3CA52;" type="button">Faculty</button>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control form-control-sm" name="first_name" required="required">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control form-control-sm" name="last_name" required="required">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control form-control-sm" name="email" required="required">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">College</label>
                            <input type="text" class="form-control form-control-sm" name="college">
                        </div>
                        <div class="mb-3">
                            <label class=" form-label">Department</label>
                            <input type="text" class="form-control form-control-sm" name="department">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User ID</label>
                            <input type="text" class="form-control form-control-sm" name="user_id" required="required">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control form-control-sm" id="facultyPassword" name="password" required="required">
                        </div>
                        <div class="mb-3">
                            <input type="checkbox" onclick="togglePasswordVisibility('facultyPassword')"> Show Password
                        </div>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Modal -->
    <div class="modal fade" id="alertModal" tabindex="-1" aria-labelledby="alertModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="alertMessage">
                    <!-- Dynamic alert message will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- might be unnecessary -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alertMessage = "<?php echo isset($_SESSION['registration_error']) ? $_SESSION['registration_error'] : (isset($_SESSION['registration_success']) ? $_SESSION['registration_success'] : ''); ?>";
            if (alertMessage) {
                document.getElementById('alertMessage').textContent = alertMessage;
                new bootstrap.Modal(document.getElementById('alertModal')).show();
                <?php unset($_SESSION['registration_error']);
                unset($_SESSION['registration_success']); ?>
            }
        });
    </script>

    <!-- SCRIPT FOR TOGGLING LOGIN USER TYPE -->
    <script>
        function setUserType(userType) {
            // Set the hidden input value
            document.getElementById('user_type').value = userType;

            // Remove the active class from all buttons
            document.querySelectorAll('.user-type-btn').forEach(button => {
                button.classList.remove('active');
            });

            // Add the active class to the clicked button
            if (userType === 'student') {
                document.getElementById('student-btn').classList.add('active');
            } else if (userType === 'faculty') {
                document.getElementById('faculty-btn').classList.add('active');
            }
        }

        function togglePassword() {
            const passwordField = document.getElementById('password');
            passwordField.type = passwordField.type === 'password' ? 'text' : 'password';
        }

        document.addEventListener("DOMContentLoaded", function() {
            // Show login modal if there’s an error
            const errorMessage = <?php echo json_encode($error_message); ?>;
            if (errorMessage) {
                const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
                loginModal.show();
            }
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Validate name inputs (letters and spaces only)
            document.querySelectorAll("input[name='first_name'], input[name='last_name']").forEach(input => {
                input.addEventListener("input", function() {
                    if (!/^[a-zA-Z\s]*$/.test(this.value)) {
                        this.setCustomValidity("Names can only contain letters and spaces.");
                    } else {
                        this.setCustomValidity("");
                    }
                });
            });

            // Validate user ID inputs (numbers and symbols only)
            document.querySelectorAll("input[name='user_id']").forEach(input => {
                input.addEventListener("input", function() {
                    if (!/^[0-9\-\.\+\/]*$/.test(this.value)) {
                        this.setCustomValidity("User ID can only contain numbers and symbols.");
                    } else {
                        this.setCustomValidity("");
                    }
                });
            });
        });
    </script>

    <script>
        // Function to toggle password visibility
        function togglePasswordVisibility(passwordFieldId) {
            var passwordField = document.getElementById(passwordFieldId);
            if (passwordField.type === "password") {
                passwordField.type = "text";
            } else {
                passwordField.type = "password";
            }
        }
    </script>

</body>

</html>