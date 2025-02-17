<?php
include("session_check.php"); // session check to protect pages
ob_start();
include("../config/db_localhost.php");

// Fetch subjects for the select picker
$subjectsQuery = "SELECT id, subject_code, subject_name FROM subjects";
$subjectsResult = mysqli_query($conn, $subjectsQuery);

if (!$subjectsResult) {
    die("Error fetching subjects: " . mysqli_error($conn));
}

// Fetch materials for the select picker
$materialsQuery = "SELECT id, material FROM materials";
$materialsResult = mysqli_query($conn, $materialsQuery);

if (!$materialsResult) {
    die("Error fetching materials: " . mysqli_error($conn));
}

// Insert new book
if (isset($_POST['save_book'])) {
    $title = $_POST['title'];
    $call_no = $_POST['call_no'];
    $author = $_POST['author'];
    $volumes = $_POST['volumes'];
    $year_publication = $_POST['year_publication'];
    $subject_ids = $_POST['subject_id']; // Array of subject IDs
    $material_ids = $_POST['material_id']; // Array of material IDs

    // Initialize variables for file upload
    $targetFile = "../img/nocover.png"; // Default path to 'no cover' image
    $uploadOk = 1;

    // PHP processing script for file upload
    if (isset($_FILES["book_cover"]) && $_FILES["book_cover"]["error"] == UPLOAD_ERR_OK) {
        $targetDir = "../uploads/";
        $targetFile = $targetDir . basename($_FILES["book_cover"]["name"]);

        $check = getimagesize($_FILES["book_cover"]["tmp_name"]);
        if ($check === false) {
            $uploadOk = 0;
            echo "Error: File is not an image.";
        }

        if ($_FILES["book_cover"]["size"] > 5000000) {
            $uploadOk = 0;
            echo "Error: File is too large.";
        }

        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
            $uploadOk = 0;
            echo "Error: Only JPG, JPEG, PNG & GIF files are allowed.";
        }

        if ($uploadOk == 0) {
            echo "Error: File was not uploaded.";
            $targetFile = "../img/nocover.png";
        } else {
            if (move_uploaded_file($_FILES["book_cover"]["tmp_name"], $targetFile)) {
                echo "The file " . htmlspecialchars(basename($_FILES["book_cover"]["name"])) . " has been uploaded.";
            } else {
                echo "Error: There was an error uploading your file.";
                $targetFile = "../img/nocover.png";
            }
        }
    }

    // Check if the book already exists (allowing duplicates for ebooks)
    $checkDuplicateQuery = "SELECT * FROM books WHERE call_no = ?";
    $stmt = mysqli_prepare($conn, $checkDuplicateQuery);
    mysqli_stmt_bind_param($stmt, "s", $call_no);
    mysqli_stmt_execute($stmt);
    $resultDuplicate = mysqli_stmt_get_result($stmt);

    // Determine if it's an ebook or not
    $isEbook = ($call_no === 'ebook');

    if (mysqli_num_rows($resultDuplicate) > 0 && !$isEbook) {
        $errorMessage = "Book with this call no already exists.";
    } else {
        $insertQuery = "INSERT INTO books (title, call_no, author, volumes, year_publication, book_cover) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($stmt, "ssssss", $title, $call_no, $author, $volumes, $year_publication, $targetFile);
        if (mysqli_stmt_execute($stmt)) {
            $bookId = mysqli_insert_id($conn);

            // Insert associations into subject_books
            foreach ($subject_ids as $subjectId) {
                $insertAssocQuery = "INSERT INTO subject_books (book_id, subject_id) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $insertAssocQuery);
                mysqli_stmt_bind_param($stmt, "ii", $bookId, $subjectId);
                mysqli_stmt_execute($stmt);
            }

            // Insert associations into material_books
            foreach ($material_ids as $materialId) {
                $insertMaterialQuery = "INSERT INTO material_books (material_id, book_id) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $insertMaterialQuery);
                mysqli_stmt_bind_param($stmt, "ii", $materialId, $bookId);
                mysqli_stmt_execute($stmt);
            }

            // Redirect to avoid resubmitting the form
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    }
}

// Fetch books / join from the database to the table
$booksQuery = "
    SELECT b.*, 
    GROUP_CONCAT(DISTINCT s.subject_code SEPARATOR ', ') AS subjects,
    GROUP_CONCAT(DISTINCT m.material SEPARATOR ', ') AS materials
    FROM books b
    LEFT JOIN subject_books sb ON b.id = sb.book_id
    LEFT JOIN subjects s ON sb.subject_id = s.id
    LEFT JOIN material_books mb ON b.id = mb.book_id
    LEFT JOIN materials m ON mb.material_id = m.id
    GROUP BY b.id
";
$booksResult = mysqli_query($conn, $booksQuery);

if (!$booksResult) {
    die("Error fetching books: " . mysqli_error($conn));
}

if (isset($_POST['edit'])) {
    $Id = $_POST['id'];
    $fetchQuery = "SELECT * FROM `books` WHERE id = $Id";
    $bookData = mysqli_fetch_assoc(mysqli_query($conn, $fetchQuery));

    // Fetch subjects
    $subjectsQuery = "SELECT id, subject_code, subject_name FROM subjects";
    $subjectsResult = mysqli_query($conn, $subjectsQuery);

    if (!$subjectsResult) {
        die("Error fetching subjects: " . mysqli_error($conn));
    }

    // Fetch materials
    $materialsQuery = "SELECT id, material FROM materials";
    $materialsResult = mysqli_query($conn, $materialsQuery);

    if (!$materialsResult) {
        die("Error fetching materials: " . mysqli_error($conn));
    }

    // Fetch current subject and material associations
    $assignedSubjects = [];
    $assignedMaterials = [];

    // Fetch current subjects
    $assignedSubjectsQuery = "SELECT subject_id FROM subject_books WHERE book_id = $Id";
    $assignedSubjectsResult = mysqli_query($conn, $assignedSubjectsQuery);

    if (!$assignedSubjectsResult) {
        die("Error fetching assigned subjects: " . mysqli_error($conn));
    }

    while ($assignedSubject = mysqli_fetch_assoc($assignedSubjectsResult)) {
        $assignedSubjects[] = $assignedSubject['subject_id'];
    }

    // Fetch current materials
    $assignedMaterialsQuery = "SELECT material_id FROM material_books WHERE book_id = $Id";
    $assignedMaterialsResult = mysqli_query($conn, $assignedMaterialsQuery);

    if (!$assignedMaterialsResult) {
        die("Error fetching assigned materials: " . mysqli_error($conn));
    }

    while ($assignedMaterial = mysqli_fetch_assoc($assignedMaterialsResult)) {
        $assignedMaterials[] = $assignedMaterial['material_id'];
    }

    echo '
        <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content col-sm mx-3 my-3">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateModalLabel">Update Book Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post" action="' . $_SERVER['PHP_SELF'] . '" enctype="multipart/form-data" onsubmit="return validateForm();">
                            <input type="hidden" name="id" value="' . $bookData['id'] . '">
                            <div class="row">
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                    <label>Material Type</label>
                                    <select name="material_id[]" class="form-select form-select-sm" required>
                                        <option value="">Update Material Type</option>';

    // Populate materials from the database
    while ($material = mysqli_fetch_assoc($materialsResult)) {
        // Check if the material is currently assigned to the book
        $selected = in_array($material['id'], $assignedMaterials) ? 'selected' : '';
        echo "<option value='{$material['id']}' $selected>{$material['material']}</option>";
    }

    echo '
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                    <label>Title</label>
                                    <input type="text" name="title" value="' . $bookData['title'] . '" required="required" class="form-control form-control-sm" />
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                    <label>Author(s)</label>
                                    <input type="text" name="author" value="' . $bookData['author'] . '" required="required" class="form-control form-control-sm" />
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                    <label>Call No.</label>
                                    <input type="text" id="call_no" name="call_no" value="' . $bookData['call_no'] . '" required="required" class="form-control form-control-sm" />
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                    <label>Volumes</label>
                                    <input type="text" name="volumes" value="' . $bookData['volumes'] . '" required="required" class="form-control form-control-sm" />
                                </div>
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                    <label>Year of Publication</label>
                                    <input type="text" name="year_publication" value="' . $bookData['year_publication'] . '" required="required" class="form-control form-control-sm" />
                                 </div>
                                <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                    <label>Book Cover</label>
                                    <input type="file" name="book_cover" accept="image/*" class="form-control form-control-sm" />
                                    <div class="form-check mt-2">
                                        <input type="checkbox" class="form-check-input" id="removeCover" name="remove_cover" value="1">
                                        <label class="form-check-label" for="removeCover">Remove current cover</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-10 mt-3">
                                    <label>Select Subject(s)</label>
                                    <select name="subject_id[]" class="selectpicker form-control" multiple data-live-search="true" data-actions-box="true" data-selected-text-format="count > 1" title="Select Subjects">';

    if ($subjectsResult) {
        // Modify the query to order subjects by subject_code
        $subjectsResult = mysqli_query($conn, "SELECT id, subject_code, subject_name FROM subjects ORDER BY subject_code ASC");
        while ($subject = mysqli_fetch_assoc($subjectsResult)) {
            $selected = in_array($subject['id'], $assignedSubjects) ? 'selected' : '';
            $displayText = "{$subject['subject_code']} - {$subject['subject_name']}";
            echo "<option value='{$subject['id']}' $selected>{$displayText}</option>";
        }
    } else {
        echo "<option disabled>No subjects available</option>";
    }

    echo ' 
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-5">
                                <div class="col-sm">
                                    <button type="submit" name="update" class="btn btn-md float-end mt-3">Update Book</button>
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
        
            // Initialize selectpicker
            var selectPicker = document.querySelector(".selectpicker");
            if (selectPicker) {
                $(selectPicker).selectpicker("refresh");
            }
        });
        </script>
    ';
}



// Update book
if (isset($_POST['update'])) {
    $book_id = $_POST['id'];
    $title = $_POST['title'];
    $call_no = $_POST['call_no'];
    $author = $_POST['author'];
    $volumes = $_POST['volumes'];
    $year_publication = $_POST['year_publication'];
    $subject_ids = $_POST['subject_id']; // Array of subject IDs
    $material_ids = $_POST['material_id']; // Array of material IDs

    // Initialize variables for file upload
    $targetFile = "";
    $uploadOk = 1;

    // Check if a new file is uploaded
    if (isset($_FILES["book_cover"]) && $_FILES["book_cover"]["error"] == UPLOAD_ERR_OK) {
        $targetDir = "../uploads/"; // Directory where the uploaded files will be stored
        $targetFile = $targetDir . basename($_FILES["book_cover"]["name"]); // Path to the uploaded file

        // Check if the file is an actual image
        $check = getimagesize($_FILES["book_cover"]["tmp_name"]);
        if ($check === false) {
            $uploadOk = 0;
            echo "Error: File is not an image.";
        }

        // Check file size (limit to 5MB)
        if ($_FILES["book_cover"]["size"] > 5000000) {
            $uploadOk = 0;
            echo "Error: File is too large.";
        }

        // Allow certain file formats
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        if (!in_array($imageFileType, ["jpg", "jpeg", "png", "gif"])) {
            $uploadOk = 0;
            echo "Error: Only JPG, JPEG, PNG & GIF files are allowed.";
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo "Error: File was not uploaded.";
        } else {
            // If everything is ok, try to upload the file
            if (move_uploaded_file($_FILES["book_cover"]["tmp_name"], $targetFile)) {
                echo "The file " . htmlspecialchars(basename($_FILES["book_cover"]["name"])) . " has been uploaded.";
            } else {
                echo "Error: There was an error uploading your file.";
            }
        }
    } else {
        // Handle case where no new file is uploaded
        if (isset($_POST['remove_cover']) && $_POST['remove_cover'] == '1') {
            // If the user wants to remove the cover, set to default image
            $targetFile = "../img/nocover.png";
        } else {
            // Keep existing cover if no new image is uploaded and cover is not removed
            $query = "SELECT book_cover FROM books WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $book_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            $targetFile = $row['book_cover'];
        }
    }

    // Check if the book exists
    $checkBookQuery = "SELECT * FROM books WHERE id = ?";
    $stmt = mysqli_prepare($conn, $checkBookQuery);
    mysqli_stmt_bind_param($stmt, "i", $book_id);
    mysqli_stmt_execute($stmt);
    $resultBook = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($resultBook) > 0) {
        // Proceed with updating the book
        $updateQuery = "UPDATE books SET title = ?, call_no = ?, author = ?, volumes = ?, year_publication = ?, book_cover = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($stmt, "ssssssi", $title, $call_no, $author, $volumes, $year_publication, $targetFile, $book_id);
        if (mysqli_stmt_execute($stmt)) {
            // Update associations in subject_books
            // First, delete existing associations for subjects
            $deleteAssocQuery = "DELETE FROM subject_books WHERE book_id = ?";
            $stmt = mysqli_prepare($conn, $deleteAssocQuery);
            mysqli_stmt_bind_param($stmt, "i", $book_id);
            mysqli_stmt_execute($stmt);

            // Insert new associations for subjects
            foreach ($subject_ids as $subjectId) {
                $insertAssocQuery = "INSERT INTO subject_books (book_id, subject_id) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $insertAssocQuery);
                mysqli_stmt_bind_param($stmt, "ii", $book_id, $subjectId);
                mysqli_stmt_execute($stmt);
            }

            // Update associations in material_books
            // First, delete existing associations for materials
            $deleteMaterialAssocQuery = "DELETE FROM material_books WHERE book_id = ?";
            $stmt = mysqli_prepare($conn, $deleteMaterialAssocQuery);
            mysqli_stmt_bind_param($stmt, "i", $book_id);
            mysqli_stmt_execute($stmt);

            // Insert new associations for materials
            foreach ($material_ids as $materialId) {
                $insertMaterialAssocQuery = "INSERT INTO material_books (book_id, material_id) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $insertMaterialAssocQuery);
                mysqli_stmt_bind_param($stmt, "ii", $book_id, $materialId);
                mysqli_stmt_execute($stmt);
            }

            // Redirect to avoid resubmitting the form
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            echo "Error: " . mysqli_error($conn);
        }
    } else {
        echo "Error: Book not found.";
    }
}

// Delete book
if (isset($_POST['delete'])) {
    $Id = $_POST['id'];

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // First, delete the related records in subject_books
        $deleteBookSubjectsQuery = "DELETE FROM subject_books WHERE book_id = ?";
        $stmt = mysqli_prepare($conn, $deleteBookSubjectsQuery);
        mysqli_stmt_bind_param($stmt, "i", $Id);
        mysqli_stmt_execute($stmt);

        // Then, delete the related records in material_books
        $deleteBookMaterialsQuery = "DELETE FROM material_books WHERE book_id = ?";
        $stmt = mysqli_prepare($conn, $deleteBookMaterialsQuery);
        mysqli_stmt_bind_param($stmt, "i", $Id);
        mysqli_stmt_execute($stmt);

        // Finally, delete the book
        $deleteBookQuery = "DELETE FROM books WHERE id = ?";
        $stmt = mysqli_prepare($conn, $deleteBookQuery);
        mysqli_stmt_bind_param($stmt, "i", $Id);
        mysqli_stmt_execute($stmt);

        // Commit transaction
        mysqli_commit($conn);

        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit(); // Ensure no further code is executed after redirect
    } catch (Exception $e) {
        // Rollback transaction if something goes wrong
        mysqli_rollback($conn);
        echo "Error deleting book: " . $e->getMessage();
    }
}

ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Collections</title>

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
                        <h1 class="titlepage display-8"> <i class="lni lni-book"></i> Add Books </h1>
                    </div>

                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="dashboards.php">
                                    <i class="lni lni-home"></i> <!-- Home Icon -->
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Collections</li>
                            <li class="breadcrumb-item active" aria-current="page">Books</li>
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
                                <h4 class="card-title"> <i class="lni lni-book"></i> Book Information</h4>
                                <form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">

                                    <div class="row">
                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Material Type</label>
                                            <select name="material_id[]" class="form-select form-select-sm" required>
                                                <option value="">Select Material Type</option>
                                                <?php
                                                // Fetch material types from database
                                                $materials = mysqli_query($conn, "SELECT id, material FROM materials");
                                                while ($material = mysqli_fetch_assoc($materials)) {
                                                    echo "<option value='{$material['id']}'>{$material['material']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Author(s)</label>
                                            <input type="text" id="author" name="author" required="required" class="form-control form-control-sm" placeholder="Enter Author" />
                                        </div>

                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Title</label>
                                            <input type="text" id="title" name="title" required="required" class="form-control form-control-sm" placeholder="Enter Title" />
                                        </div>

                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Volumes</label>
                                            <input type="text" id="volumes" name="volumes" required="required" class="form-control form-control-sm" placeholder="Enter Volumes" />
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Call No.</label>
                                            <input type="text" id="call_no" name="call_no" required="required" class="form-control form-control-sm" placeholder="Enter Call No." />
                                        </div>

                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Year of Publication (Copyright)</label>
                                            <input type="text" id="year_publication" name="year_publication" required="required" class="form-control form-control-sm" placeholder="Enter Year of Publication" />
                                        </div>

                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Book Cover (Optional)</label>
                                            <div class="input-group">
                                                <input type="file" id="book_cover" name="book_cover" accept="image/*" class="form-control form-control-sm" />
                                                <button type="button" id="removeFileButton" class="btn btn-sm" style="display: none;">x</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Select Subjects</label>
                                            <select name="subject_id[]" class="selectpicker custom-selectpicker btn1" style="background-color: white;" multiple data-live-search="true" data-actions-box="true" data-selected-text-format="count > 1">
                                                <?php
                                                // Fetch subjects from database and order alphabetically by subject_code
                                                $subjects = mysqli_query($conn, "SELECT id, subject_code, subject_name FROM subjects ORDER BY subject_code ASC");
                                                while ($subject = mysqli_fetch_assoc($subjects)) {
                                                    // Combine subject name and code
                                                    $displayText = "{$subject['subject_code']} - {$subject['subject_name']}";
                                                    echo "<option value='{$subject['id']}'>{$displayText}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>



                                    <div class="row">
                                        <div class="col-sm">
                                            <button type="submit" id="save_book" name="save_book" class="btn btn-md float-end mt-3">Add Book</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Table displaying books -->
                    <div class="row my-3 align-items-center justify-content-center">
                        <div class="card col-sm mx-3 my-3" style="width: 100%;">
                            <div class="card-body">
                                <h4 class="card-title"> <i class="lni lni-book"></i> Books List</h4>
                                <div class="table-container">
                                    <table id="booksTable" class="table table-bordered" role="grid">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Author(s)</th>
                                                <th>Title</th>
                                                <th>Call No.</th>
                                                <th>Volumes</th>
                                                <th>Copyright</th>
                                                <th>Material</th>
                                                <th>Subjects</th>
                                                <th>Cover</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            while ($row = mysqli_fetch_assoc($booksResult)) {
                                                echo "<tr>";
                                                echo "<td>{$row['id']}</td>";
                                                echo "<td>{$row['author']}</td>";
                                                echo "<td>{$row['title']}</td>";
                                                echo "<td>{$row['call_no']}</td>";
                                                echo "<td>{$row['volumes']}</td>";
                                                echo "<td>{$row['year_publication']}</td>";
                                                echo "<td>{$row['materials']}</td>";
                                                echo "<td>{$row['subjects']}</td>";
                                                echo "<td><img src='{$row['book_cover']}' alt='Book Cover' style='width: 50px; height: 50px;'></td>";


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
        document.addEventListener("DOMContentLoaded", function() {
            const materialSelect = document.querySelector('select[name="material_id[]"]');
            const callNoInput = document.getElementById("call_no");

            materialSelect.addEventListener("change", function() {
                const selectedMaterial = materialSelect.options[materialSelect.selectedIndex].text;

                if (selectedMaterial.toLowerCase() === "e-book" || selectedMaterial.toLowerCase() === "ebook") {
                    callNoInput.value = "ebook"; // Populate the input with "ebook"
                    callNoInput.readOnly = true; // Set to read-only
                } else {
                    callNoInput.readOnly = false; // Enable editing
                    callNoInput.value = ""; // Clear the input if it was previously set
                }
            });
        });
    </script>


    <script>
        document.getElementById('book_cover').addEventListener('change', function() {
            var removeButton = document.getElementById('removeFileButton');
            if (this.value) {
                removeButton.style.display = 'inline-block';
            } else {
                removeButton.style.display = 'none';
            }
        });

        document.getElementById('removeFileButton').addEventListener('click', function() {
            var fileInput = document.getElementById('book_cover');
            fileInput.value = ''; // Clear the file input
            this.style.display = 'none'; // Hide the remove button
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#booksTable').DataTable({
                "pageLength": 7, // Limit the number of rows per page to 7
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