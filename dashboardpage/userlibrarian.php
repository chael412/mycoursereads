<?php
include("session_check.php"); // session check to protect pages
ob_start(); // Start output buffering
include("../config/db_localhost.php");

// Insert new librarian user
if (isset($_POST['add_librarian'])) {
    $lastName = $_POST['last_name'];
    $firstName = $_POST['first_name'];
    $userId = $_POST['user_id'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash the password for security

    // Check if the librarian user already exists
    $checkDuplicateQuery = "SELECT * FROM `librarianuser` WHERE user_id = '$userId' OR email = '$email'";
    $resultDuplicate = mysqli_query($conn, $checkDuplicateQuery);

    if (mysqli_num_rows($resultDuplicate) > 0) {
        // if already exists, set an error message
        $errorMessage = "User ID or Email already exists.";
    } else {
        // if does not exist, proceed with the insertion
        $insertQuery = "INSERT INTO `librarianuser` (`last_name`, `first_name`, `user_id`, `email`, `password`) VALUES ('$lastName', '$firstName', '$userId', '$email', '$password')";

        // Execute the query
        if (mysqli_query($conn, $insertQuery)) {
            // Redirect to the same page to avoid resubmitting the form
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch all librarian users
$result = mysqli_query($conn, "SELECT * FROM `librarianuser`");

// Update librarian user
if (isset($_POST['edit'])) {
    $Id = $_POST['id'];
    $fetchQuery = "SELECT * FROM `librarianuser` WHERE id = $Id";
    $librarianData = mysqli_fetch_assoc(mysqli_query($conn, $fetchQuery));

    // Modal for updating librarian user record
    echo '
        <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content col-sm mx-3 my-3">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateModalLabel">Update librarian User Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post">
                            <input type="hidden" name="id" value="' . $librarianData['id'] . '">
                            <div class="row">
                                <div class="col-lg-4 col-md-6 col-sm-12 mt-3">
                                    <label>Last Name</label>
                                    <input type="text" id="last_name" name="last_name" required="required" class="form-control form-control-sm" value="' . $librarianData['last_name'] . '" />
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12 mt-3">
                                    <label>First Name</label>
                                    <input type="text" id="first_name" name="first_name" required="required" class="form-control form-control-sm" value="' . $librarianData['first_name'] . '" />
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12 mt-3">
                                    <label>User ID</label>
                                    <input type="text" id="user_id" name="user_id" required="required" class="form-control form-control-sm" value="' . $librarianData['user_id'] . '" />
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12 mt-3">
                                    <label>Email</label>
                                    <input type="email" id="email" name="email" required="required" class="form-control form-control-sm" value="' . $librarianData['email'] . '" />
                                </div>
                                <div class="col-lg-4 col-md-6 col-sm-12 mt-3">
                                    <label>Password</label>
                                    <input type="text" id="password" name="password" class="form-control form-control-sm" />
                                </div>
                            </div>
                
                            <div class="row">
                                <div class="col-sm">
                                    <button type="submit" name="update" class="btn float-end mt-3">UPDATE</button>
                                </div>
                            </div>
                         </form>
                    </div>
                </div>
            </div>
        </div>
    ';

    // Trigger script to show the modal
    echo '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var myModal = new bootstrap.Modal(document.getElementById("updateModal"), {
                    keyboard: false
                });
                myModal.show();
            });
        </script>
    ';
}


if (isset($_POST['update'])) {
    $Id = $_POST['id'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $user_id = $_POST['user_id'];
    $email = $_POST['email'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    if ($password) {
        $updateQuery = "UPDATE `librarianuser` SET `last_name`='$last_name', `first_name`='$first_name', `user_id`='$user_id', `email`='$email', `password`='$password' WHERE id = $Id";
    } else {
        $updateQuery = "UPDATE `librarianuser` SET `last_name`='$last_name', `first_name`='$first_name', `user_id`='$user_id', `email`='$email' WHERE id = $Id";
    }

    mysqli_query($conn, $updateQuery);

    // Redirect to the same page to avoid resubmitting the form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Delete librarian user
if (isset($_POST['delete'])) {
    $Id = $_POST['id'];

    $query = "DELETE FROM librarianuser WHERE id = $Id";
    mysqli_query($conn, $query);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

ob_end_flush();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Librarian</title>

    <!-- CDN LINKS -->
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">

    <!-- FOR MULTI SELECT MIGHT CAUSE SOME ALIGNMENT ISSUES OR OVER READ??????? -->

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>

    <!-- DataTables Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>
</head>

<body>

    <div class="wrapper">
        <?php include('sidebar.php'); ?>

        <div class="main-content">
            <?php include('navbar.php'); ?>

            <!-- CONTENT PAGE HERE / SECTION -->

            <section id="intro">

                <div class="container">

                    <div class="title text-center text-md-start mt-5">
                        <h1 class="titlepage display-8"> <i class="lni lni-user"></i> Manage Librarian</h1>
                    </div>

                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="dashboards.php">
                                    <i class="lni lni-home"></i> <!-- Home Icon -->
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Users</li>
                            <li class="breadcrumb-item active" aria-current="page">Librarian</li>
                        </ol>
                    </nav>

                </div>

                <div class="container ">

                    <div class="row my-3 align-items-center justify-content-center">

                        <div class="card col-sm mx-3 my-3" style="width: 18rem;">

                            <!-- Display error message -->
                            <?php if (isset($errorMessage)) : ?>
                                <div class="alert alert-danger mt-3" role="alert">
                                    <?= $errorMessage ?>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">

                                <h4 class="card-title"> <i class="lni lni-user"></i> Librarian Information</h4>

                                <form method="post">

                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 col-sm-12 mt-3">
                                            <label>Last Name</label>
                                            <input type="text" id="last_name" name="last_name" required="required" class="form-control form-control-sm" placeholder="Enter Last Name" />
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12 mt-3">
                                            <label>First Name</label>
                                            <input type="text" id="first_name" name="first_name" required="required" class="form-control form-control-sm" placeholder="Enter First Name" />
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12 mt-3">
                                            <label>User ID</label>
                                            <input type="text" id="user_id" name="user_id" required="required" class="form-control form-control-sm" placeholder="Enter User ID" />
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-4 col-md-6 col-sm-12 mt-3">
                                            <label>Email</label>
                                            <input type="email" id="email" name="email" required="required" class="form-control form-control-sm" placeholder="Email" />
                                        </div>
                                        <div class="col-lg-4 col-md-6 col-sm-12 mt-3">
                                            <label>Password</label>
                                            <input type="text" id="password" name="password" class="form-control form-control-sm" placeholder="Enter Password" />
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-sm-12 mt-3 text-center">
                                            <button type="submit" name="add_librarian" id="add_student" class="btn btn-md float-end mt-3">Add Librarian</button>
                                        </div>
                                    </div>

                                </form>

                            </div>

                        </div>
                    </div>

                    <!-- TABLE -->

                    <div class="row my-3 align-items-center justify-content-center">
                        <div class="card col-sm mx-3 my-3" style="width: 100%em;">
                            <div class="card-body">
                                <h4 class="card-title"><i class="lni lni-user"></i> Librarian Information</h4>
                                <div class="table-responsive">
                                    <table id="librarianinfo" class="table table-bordered" role="grid" aria-describedby="all_courses_info">
                                        <thead>
                                            <tr>
                                                <th scope="col">ID</th>
                                                <th scope="col">Last Name</th>
                                                <th scope="col">First Name</th>
                                                <th scope="col">User ID</th>
                                                <th scope="col">Email</th>
                                                <th scope="col">Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php
                                            while ($row = mysqli_fetch_assoc($result)) { ?>
                                                <tr>
                                                    <td><?php echo $row['id']; ?></td>
                                                    <td><?php echo $row['last_name']; ?></td>
                                                    <td><?php echo $row['first_name']; ?></td>
                                                    <td><?php echo $row['user_id']; ?></td>
                                                    <td><?php echo $row['email']; ?></td>
                                                    <td>
                                                        <form method="post" class="d-inline-block">
                                                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                            <button type='submit' name='edit' class='btn' data-bs-toggle='modal' data-bs-target='#updateModal'>
                                                                <i class='lni lni-pencil-alt'></i>
                                                            </button>
                                                            <button type='submit' name='delete' class='btn'>
                                                                <i class='lni lni-trash-can'></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#librarianinfo').DataTable({
                "pageLength": 7, // Limit the number of rows per page to 8
                "lengthChange": false, // Hide the "Show # entries" dropdown
                "info": true, // Display the "Showing # to # of # entries" information
                "pagingType": "simple_numbers" // Use simple pagination controls
            });
        });
    </script>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="script.js"></script>

</body>

</html>