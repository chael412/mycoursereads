<?php
include("session_check.php"); // session check to protect pages
ob_start(); // Start output buffering
include("../config/db_localhost.php");

// Insert new material
if (isset($_POST['add_material'])) {
    $materialName = $_POST['material_name'];

    // Check if the material already exists
    $checkDuplicateQuery = "SELECT * FROM `materials` WHERE material = '$materialName'";
    $resultDuplicate = mysqli_query($conn, $checkDuplicateQuery);

    if (mysqli_num_rows($resultDuplicate) > 0) {
        // if already exists, set an error message
        $errorMessage = "Material already exists.";
    } else {
        // if it does not exist, insert it into the materials table
        $insertQuery = "INSERT INTO `materials` (`material`) VALUES ('$materialName')";

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

// Fetch all materials
$result = mysqli_query($conn, "SELECT * FROM `materials`");

// Update material
if (isset($_POST['edit'])) {
    $Id = $_POST['id'];
    $fetchQuery = "SELECT * FROM `materials` WHERE id = $Id";
    $materialData = mysqli_fetch_assoc(mysqli_query($conn, $fetchQuery));

    // Modal for updating material record
    echo '
        <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content col-sm mx-3 my-3">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateModalLabel">Update Material Type</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" onsubmit="return validateForm();">
                            <input type="hidden" name="id" value="' . $materialData['id'] . '">
                            <div class="row">
                                 <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10 mt-3">
                                    <label>Material Name</label>
                                    <input type="text" id="material_name" placeholder="Update material type" name="material_name" required="required" class="form-control form-control-sm" value="' . $materialData['material'] . '" />
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
    $materialName = $_POST['material_name'];

    $updateQuery = "UPDATE `materials` SET `material`='$materialName' WHERE id = $Id";

    mysqli_query($conn, $updateQuery);

    // Redirect to the same page to avoid resubmitting the form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Delete material
if (isset($_POST['delete'])) {
    $Id = $_POST['id'];

    // Now delete the material
    $query = "DELETE FROM `materials` WHERE id = $Id";
    mysqli_query($conn, $query);

    // Redirect to the same page to avoid resubmitting the form
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
    <title>Add Material</title>

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
                        <h1 class="titlepage display-8"> <i class="lni lni-files"></i> Add Type of Material </h1>
                    </div>

                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="dashboards.php">
                                    <i class="lni lni-home"></i> <!-- Home Icon -->
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Add Material</li>
                        </ol>
                    </nav>

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

                                <h4 class="card-title"> <i class="lni lni-files"></i> Type of Material</h4>

                                <form method="post">

                                    <div class="row">

                                        <div class="col-lg-4 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Material Name</label>
                                            <input type="text" id="material_name" placeholder="Enter material type (e.g. ebook, magazine)" required="required" name="material_name" class="form-control form-control-sm" />
                                        </div>

                                    </div>

                                    <div class="row">

                                        <div class="col-sm">
                                            <button type="submit" id="add_material" name="add_material" class="btn btn-md float-end mt-3">Submit</button>
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
                                <h4 class="card-title"><i class="lni lni-files"></i> Type of Materials</h4>
                                <div class="table-responsive">
                                    <table id="booksTable" class="table table-bordered" role="grid" aria-describedby="all_programs_info">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Material type</th>
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
                                                echo "<td>{$row['material']}</td>";
                                                echo "<td>
                                                    <form method='post'>
                                                        <input type='hidden' name='id' value='{$row['id']}'>
                                                        <button type='submit' name='edit' class='btn mt-2' data-bs-toggle='modal' data-bs-target='#updateModal'>
                                                            <i class='lni lni-pencil-alt'></i>
                                                        </button>
                                                        <button type='submit' name='delete' class='btn mt-2'>
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
                            </div>
                        </div>
                    </div>
            </section>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#booksTable').DataTable({
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