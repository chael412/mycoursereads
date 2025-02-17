<?php
include("session_check.php"); // session check to protect pages
ob_start(); // Start output buffering
// include("../config/db_localhost.php");
include("../config/db_phpmyadmin.php"); // upload online

// Insert new authors
if (isset($_POST['submit'])) {
    $authorName = $_POST['author_name'];

    // Check if the author already exists
    $checkDuplicateQuery = "SELECT * FROM `authors` WHERE author_name = '$authorName'";
    $resultDuplicate = mysqli_query($conn, $checkDuplicateQuery);

    if (mysqli_num_rows($resultDuplicate) > 0) {
        // if already exists, set an error message
        $errorMessage = "Author already exists.";
    } else {
        // if does not exist, proceed with the insertion
        $insertQuery = "INSERT INTO `authors` (`author_name`) VALUES ('$authorName')";

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

// Fetch all authors
$result = mysqli_query($conn, "SELECT * FROM `authors`");

// Update authors
if (isset($_POST['edit'])) {
    $Id = $_POST['id'];
    $fetchQuery = "SELECT * FROM `authors` WHERE id = $Id";
    $authorData = mysqli_fetch_assoc(mysqli_query($conn, $fetchQuery));

    // Modal for updating author record
    echo '
        <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content col-sm mx-3 my-3">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateModalLabel">Update Author Name</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" onsubmit="return validateForm();">
                            <input type="hidden" name="id" value="' . $authorData['id'] . '">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10 mt-3">
                                    <label>Author Name</label>
                                    <input type="text" id="author_name" name="author_name" required="required" class="form-control form-control-sm" value="' . $authorData['author_name'] . '" />
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
    $authorName = $_POST['author_name'];

    $updateQuery = "UPDATE `authors` SET `author_name`='$authorName' WHERE id = $Id";

    mysqli_query($conn, $updateQuery);

    // Redirect to the same page to avoid resubmitting the form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Delete authors
if (isset($_POST['delete'])) {
    $Id = $_POST['id'];

    $query = "DELETE FROM authors WHERE id = $Id";
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
    <title>Add Authors</title>

    <!-- CDN LINKS -->
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
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
                        <h1 class="titlepage display-8"> <i class="lni lni-write"></i> Add Authors </h1>
                    </div>

                </div>

                <div class="container ">

                    <div class="row my-3 align-items-center justify-content-center">

                        <div class="card col-sm mx-3 my-3" style="width: 18rem;">

                            <!-- Display error message -->
                            <?php if (isset($errorMessage)) : ?>
                                <div class="alert alert-danger mt-3" role="alert">
                                    <?php echo $errorMessage; ?>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">

                                <h4 class="card-title"> <i class="bi bi-person-add"></i> Author Information</h4>

                                <form method="post">

                                    <div class="row">

                                        <div class="col-lg-4 col-md-4 col-sm-4 col-xs-10 mt-3">
                                            <label>Author's Name</label>
                                            <input type="text" id="author_name" name="author_name" required="required" class="form-control form-control-sm" />
                                        </div>
                                    </div>

                                    <div class="row">

                                        <div class="col-sm">
                                            <button type="submit" id="submit" name="submit" class="btn btn-md float-end mt-3">Submit</button>
                                        </div>

                                    </div>

                                </form>
                            </div>

                        </div>

                    </div>

                </div>

                <!-- TABLE -->

                <div class="container ">

                    <div class="row my-3 align-items-center justify-content-center">

                        <div class="card col-sm mx-3 my-3" style="width: 18rem;">

                            <div class="card-body d-flex justify-content-between">

                                <h4 class="card-title"><i class="lni lni-ruler-pencil"></i> Authors</h4>
                                <!-- <a href="authorsadd.php"> <button type="button" class="btn">add +</button></a> -->

                            </div>

                            <div class="table-responsive mt-3">
                                <p id="author-info" class="text-muted mb-2">Showing 1 to 0 of 0 entries</p>
                                <table id="all_authors" class="table table-bordered" role="grid" aria-describedby="all_authors_info">

                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Author's Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <!-- INSERT TBODY HERE -->
                                        <?php
                                        $count = 1;
                                        while ($row = mysqli_fetch_assoc($result)) {
                                            echo "<tr>";
                                            echo "<td>{$count}</td>";
                                            echo "<td>{$row['author_name']}</td>";
                                            echo "<td>
                                                    <form method='post'>
                                                        <input type='hidden' name='id' value='{$row['id']}'>
                                                        <button type='submit' name='edit' class='btn' data-bs-toggle='modal' data-bs-target='#updateModal'>
                                                            <i class='lni lni-pencil-alt'></i>
                                                        </button>
                                                        <button type='submit' name='delete' class='btn'>
                                                            <i class='lni lni-trash-can'></i>
                                                        </button>
                                                    </form>
                                                </td>";
                                            echo "</tr>";
                                            $count++;
                                        }
                                        ?>
                                    </tbody>
                                </table>

                            </div>

                            <div class="card-footer d-flex justify-content-between mb-3">
                                <button type="button" class="btn btn-light disabled" id="prev">←</button>
                                <button type="button" class="btn btn-light disabled" id="next">→</button>
                            </div>

                        </div>

                    </div>

                </div>

            </section>
        </div>

    </div>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="script.js"></script>

</body>

</html>