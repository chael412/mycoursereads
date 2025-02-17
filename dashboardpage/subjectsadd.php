<?php
include("session_check.php"); // session check to protect pages
ob_start(); // Start output buffering
include("../config/db_localhost.php");


// Update the query to join with program_subjects and programs
$query = "
    SELECT s.id, s.subject_code, s.subject_name, 
           GROUP_CONCAT(p.program_name SEPARATOR ', ') AS program_names
    FROM subjects s
    LEFT JOIN program_subjects ps ON s.id = ps.subject_id
    LEFT JOIN programs p ON ps.program_id = p.id
    GROUP BY s.id, s.subject_code, s.subject_name
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error fetching subjects: " . mysqli_error($conn));
}

// FOR DEBUGGING
// while ($row = mysqli_fetch_assoc($result)) {
//     print_r($row);  
// }

// Insert new subjects
if (isset($_POST['add_subject'])) {
    $subjectCode = $_POST['subject_code'];
    $subjectName = $_POST['subject_name'];
    
    // Check if the program_id array is set and not empty
    if (isset($_POST['program_id']) && !empty($_POST['program_id'])) {
        $programIds = $_POST['program_id']; // Array of program IDs

        // Check if the subject code already exists
        $checkDuplicateQuery = "SELECT * FROM `subjects` WHERE subject_code = '$subjectCode'";
        $resultDuplicate = mysqli_query($conn, $checkDuplicateQuery);

        if (mysqli_num_rows($resultDuplicate) > 0) {
            // If the subject code already exists, set an error message
            $errorMessage = "Course with this code already exists.";
        } else {
            // If it doesn't exist, proceed with the insertion
            $insertQuery = "INSERT INTO `subjects` (`subject_code`, `subject_name`) VALUES ('$subjectCode', '$subjectName')";

            if (mysqli_query($conn, $insertQuery)) {
                $subjectId = mysqli_insert_id($conn); // Get the last inserted subject ID

                // Insert associations into program_subjects
                foreach ($programIds as $programId) {
                    $insertAssocQuery = "INSERT INTO program_subjects (program_id, subject_id) VALUES ('$programId', '$subjectId')";
                    mysqli_query($conn, $insertAssocQuery);
                }

                // Redirect to avoid resubmitting the form
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                echo "Error: " . mysqli_error($conn);
            }
        }
    } else {
        // If no programs are selected, set an error message
        $errorMessage = "Please select at least one program.";
    }
}



// Update subjects
if (isset($_POST['edit'])) {
    $Id = $_POST['id'];
    $fetchQuery = "SELECT * FROM `subjects` WHERE id = $Id";
    $subjectData = mysqli_fetch_assoc(mysqli_query($conn, $fetchQuery));

    // Fetch programs and current associations
    $programs = mysqli_query($conn, "SELECT id, program_name, major FROM programs");

    // Debug: Check if $programs query executed correctly
    if (!$programs) {
        die("Error fetching programs: " . mysqli_error($conn));
    }

    $assignedPrograms = []; // To hold the current associations
    $assignedProgramsQuery = "SELECT program_id FROM program_subjects WHERE subject_id = $Id";
    $assignedProgramsResult = mysqli_query($conn, $assignedProgramsQuery);

    // Debug: Check if $assignedProgramsResult query executed correctly
    if (!$assignedProgramsResult) {
        die("Error fetching assigned programs: " . mysqli_error($conn));
    }

    while ($assignedProgram = mysqli_fetch_assoc($assignedProgramsResult)) {
        $assignedPrograms[] = $assignedProgram['program_id'];
    }

    // Debug: Check the assignedPrograms array
    // print_r($assignedPrograms);

    echo '
<div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content col-sm mx-3 my-3">
            <div class="modal-header">
                <h5 class="modal-title" id="updateModalLabel">Update Program Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post" onsubmit="return validateForm();">
                    <input type="hidden" name="id" value="' . $subjectData['id'] . '">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10 mt-3">
                            <label>Course Number</label>
                            <input type="text" id="subject_code" name="subject_code" required="required" class="form-control form-control-sm" value="' . $subjectData['subject_code'] . '" />
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10 mt-3">
                            <label>Course Title</label>
                            <input type="text" id="subject_name" name="subject_name" required="required" class="form-control form-control-sm" value="' . $subjectData['subject_name'] . '" />
                        </div>
                        <div class="col-lg-6 col-md-4 col-sm-4 col-xs-10 mt-3">
                            <label>Select Program(s)</label>
                            <select name="program_id[]" class="selectpicker custom-selectpicker" multiple data-live-search="true" data-actions-box="true" data-selected-text-format="count > 3" title="Select Programs">';

    // Ensure that the fetch is done correctly
    if ($programs) {
        $programs = mysqli_query($conn, "SELECT id, program_name, major FROM programs ORDER BY program_name ASC");
        while ($program = mysqli_fetch_assoc($programs)) {
            $displayText = "{$program['program_name']} - {$program['major']}";
            $selected = in_array($program['id'], $assignedPrograms) ? 'selected' : '';
            echo "<option value='{$program['id']}' $selected>{$displayText}</option>";
        }
    } else {
        echo "<option disabled>No programs available</option>";
    }

    echo '                      </select>
                            <script>
                                document.addEventListener("DOMContentLoaded", function() {
                                    var selectPicker = document.querySelector(".selectpicker");
                                    if (selectPicker) {
                                        $(selectPicker).selectpicker("refresh");
                                    }
                                });
                            </script>
                        </div>
                    </div>
                    <br><br><br><br>
                    <div class="row mt-5">
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
    $subject_code = $_POST['subject_code'];
    $subject_name = $_POST['subject_name'];
    $programIds = $_POST['program_id']; // Array of program IDs

    $updateQuery = "UPDATE `subjects` SET `subject_code`='$subject_code', `subject_name`='$subject_name' WHERE id = $Id";
    mysqli_query($conn, $updateQuery);

    // Remove old associations
    $deleteOldAssociations = "DELETE FROM program_subjects WHERE subject_id = $Id";
    mysqli_query($conn, $deleteOldAssociations);

    // Insert new associations
    foreach ($programIds as $programId) {
        $insertAssocQuery = "INSERT INTO program_subjects (program_id, subject_id) VALUES ('$programId', '$Id')";
        mysqli_query($conn, $insertAssocQuery);
    }

    // Redirect to avoid resubmitting the form
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Delete subjects
if (isset($_POST['delete'])) {
    $Id = $_POST['id'];

    // First, delete the associations in subject_books
    $deleteAssociationsQuery = "DELETE FROM subject_books WHERE subject_id = $Id";
    if (!mysqli_query($conn, $deleteAssociationsQuery)) {
        die("Error deleting subject_books associations: " . mysqli_error($conn));
    }

    // Then, delete the associations in program_subjects
    $deleteAssociationsQuery = "DELETE FROM program_subjects WHERE subject_id = $Id";
    if (!mysqli_query($conn, $deleteAssociationsQuery)) {
        die("Error deleting program_subjects associations: " . mysqli_error($conn));
    }

    // Finally, delete the subject itself
    $deleteSubjectQuery = "DELETE FROM subjects WHERE id = $Id";
    if (mysqli_query($conn, $deleteSubjectQuery)) {
        // If successful, redirect to the same page to refresh the list
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}


// Fetch all subjects
$result = mysqli_query($conn, "SELECT * FROM `subjects`");

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Courses</title>

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

    <!-- Bootstrap Selectpicker CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css">

    <!-- Bootstrap Selectpicker JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/js/bootstrap-select.min.js"></script>

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
                        <h1 class="titlepage display-8"> <i class="lni lni-ruler-pencil"></i> Add Course </h1>
                    </div>

                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="dashboards.php">
                                    <i class="lni lni-home"></i> <!-- Home Icon -->
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Courses</li>
                            <li class="breadcrumb-item active" aria-current="page">Add Course</li>
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
                                <h4 class="card-title"> <i class="lni lni-ruler-pencil"></i> Course Information</h4>
                                <form method="post">
                                    <div class="row">
                                        <div class="col-lg-6 col-md-4 col-sm-4 col-xs-10 mt-3">
                                            <label>Course Number</label>
                                            <input type="text" id="subject_code" name="subject_code" required="required" class="form-control form-control-sm" placeholder="Enter Course Code & Number" />
                                        </div>
                                        <div class="col-lg-6 col-md-4 col-sm-4 col-xs-10 mt-3">
                                            <label>Course Title</label>
                                            <input type="text" id="subject_name" name="subject_name" required="required" class="form-control form-control-sm" placeholder="Enter Course Title" />
                                        </div>
                                        <div class="col-lg-6 col-md-4 col-sm-4 col-xs-10 mt-3">
                                            <label>Select Program(s)</label>
                                            <select name="program_id[]" class="selectpicker custom-selectpicker" multiple data-live-search="true" data-actions-box="true" data-selected-text-format="count > 3">
                                                <?php
                                                // Fetch programs from database
                                                $programs = mysqli_query($conn, "SELECT id, program_name, major FROM programs ORDER BY program_name ASC");
                                                while ($program = mysqli_fetch_assoc($programs)) {
                                                    // Combine program name and major
                                                    $displayText = "{$program['program_name']} - {$program['major']}";
                                                    echo "<option value='{$program['id']}'>{$displayText}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm">
                                            <button type="submit" id="add_subject" name="add_subject" class="btn btn-md float-end mt-3">Submit</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- TABLE -->
                    <div class="row my-3 align-items-center justify-content-center">
                        <div class="card col-sm mx-3 my-3" style="width: 100%;">
                            <div class="card-body">
                                <h4 class="card-title"><i class="lni lni-ruler-pencil"></i> Courses</h4>
                                <div class="table-responsive">
                                    <table id="booksTable" class="table table-bordered" role="grid" aria-describedby="all_Subjects_info">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Course Number</th>
                                                <th>Course Title</th>
                                                <th>Programs</th> <!-- New column for programs -->
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Run the query again to fetch results for display
                                            $result = mysqli_query($conn, $query);

                                            while ($row = mysqli_fetch_assoc($result)) {
                                                // Check if 'program_names' exists and handle it
                                                $program_names = isset($row['program_names']) ? $row['program_names'] : 'No programs assigned';

                                                echo "<tr>";
                                                echo "<td>{$row['id']}</td>";
                                                echo "<td>{$row['subject_code']}</td>";
                                                echo "<td>{$row['subject_name']}</td>";
                                                echo "<td>{$program_names}</td>";
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
            $('#booksTable').DataTable({
                "pageLength": 7, // Limit the number of rows per page to 8
                "lengthChange": false, // Hide the "Show # entries" dropdown
                "info": true, // Display the "Showing # to # of # entries" information
                "pagingType": "simple_numbers", // Use simple pagination controls
                "order": [
                    [0, 'desc']
                ] // Sort by the first column (date added) in descending order
            });
        });
    </script>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="script.js"></script>

</body>

</html>