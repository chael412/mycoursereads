<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start session if none has started
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: ../adminlogin.php");
    exit();
}

// Retrieve user's name from session
$userName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : 'Admin';
?>

<nav class="navbar navbar-expand px-4 py-2">

    <img src="icons/isulogo.png" class="avatar img-fluid" alt="">
    <!-- <img src="icons/logo1.png" class="avatar img-fluid" alt=""> -->

    <form class="d-flex ms-3" role="search">
        <input class="form-control me-2" type="search" placeholder="Search" aria-label="Search">
    </form>

    <!-- <div class="navbar-title">
        <a href="#">MyCourseReads</a>
    </div> -->

    <div class="navbar-collapse collapse">
        <ul class="navbar-nav ms-auto">

            <!-- Avatar Dropdown -->
            <li class="nav-item dropdown ms-3">
                <a href="#" data-bs-toggle="dropdown" class="nav-icon pe-md-0">
                    <img src="icons/account.png" class="avatar img-fluid" alt="">
                    <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end small">
                    <a class="dropdown-item" href="recovery.php">Settings</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.php">Logout</a>
                </div>
            </li>
        </ul>
    </div>

</nav>