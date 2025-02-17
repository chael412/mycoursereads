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

// FILL THE UPDATE FUNCTIONAND MODAL CODE HERE LATER
// FILL THE DELETE FUNCTION AND MODAL CODE HERE LATER

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">

    <!-- FOR MULTI SELECT MIGHT CAUSE SOME ALIGNMENT ISSUES OR OVER READ??????? -->

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>

    <!-- DataTables Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8"
        src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

    <!-- Bootstrap Selectpicker CSS -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css">

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
                        <h1 class="titlepage display-8"> <i class="lni lni-book"></i> Add Books (API)</h1>
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
                            <?php if (isset($errorMessage)): ?>
                                <div class="alert alert-danger mt-3" role="alert">
                                    <?php echo $errorMessage; ?>
                                </div>
                            <?php endif; ?>

                            <div class="card-body">
                                <h4 class="card-title"> <i class="lni lni-book"></i> Book Information</h4>
                                <form method="post" enctype="multipart/form-data" onsubmit="return validateForm()">

                                    <div class="row">
                                        <!-- Button to Open Search Modal -->

                                        <div class="my-3">
                                            <button type="button" class="btn" data-bs-toggle="modal"
                                                data-bs-target="#apiBookSearchModal">
                                                <i class="lni lni-search"></i> Search from Vivliotek
                                            </button>
                                        </div>

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
                                            <input type="text" id="author" name="author" required="required"
                                                class="form-control form-control-sm" placeholder="Enter Author" />
                                        </div>

                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Title</label>
                                            <input type="text" id="title" name="title" required="required"
                                                class="form-control form-control-sm" placeholder="Enter Title" />
                                        </div>

                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Volumes</label>
                                            <input type="text" id="volumes" name="volumes" required="required"
                                                class="form-control form-control-sm" placeholder="Enter Volumes" />
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Call No.</label>
                                            <input type="text" id="call_no" name="call_no" required="required"
                                                class="form-control form-control-sm" placeholder="Enter Call No." />
                                        </div>

                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Year of Publication (Copyright)</label>
                                            <input type="text" id="year_publication" name="year_publication"
                                                required="required" class="form-control form-control-sm"
                                                placeholder="Enter Year of Publication" />
                                        </div>

                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Book Cover (Optional)</label>
                                            <div class="input-group">
                                                <input type="file" id="book_cover" name="book_cover" accept="image/*"
                                                    class="form-control form-control-sm" />
                                                <button type="button" id="removeFileButton" class="btn btn-sm"
                                                    style="display: none;">x</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-lg-3 col-md-3 col-sm-3 col-xs-10 mt-3">
                                            <label>Select Subjects</label>
                                            <select name="subject_id[]" class="selectpicker custom-selectpicker btn1"
                                                style="background-color: white;" multiple data-live-search="true"
                                                data-actions-box="true" data-selected-text-format="count > 1">
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
                                            <button type="submit" id="save_book" name="save_book"
                                                class="btn btn-md float-end mt-3">Add Book</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- FILL Table displaying books later -->

                </div>
            </section>
        </div>
    </div>

    <!-- Book Search Modal -->
    <div class="modal fade" id="apiBookSearchModal" tabindex="-1" aria-labelledby="apiBookSearchModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="apiBookSearchModalLabel">Search Books from Vivliotek</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <!-- API Book Search Table -->
                    <table id="apiBooksTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Title</th>
                                <th>Authors</th>
                                <th>Call Number</th>
                                <th>Volume</th>
                                <th>Year</th>
                                <th>Material</th>
                            </tr>
                        </thead>
                        <tbody>
                            <div id="loadingSpinner" style="display:none; text-align:center; margin:20px;">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p>Loading books, please wait...</p>
                            </div>
                            <!-- Fetched Data Will Load Here via JS -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" id="selectBooksBtn">Add Selected Books</button>
                    <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const materialSelect = document.querySelector('select[name="material_id[]"]');
            const callNoInput = document.getElementById("call_no");

            materialSelect.addEventListener("change", function () {
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
        document.getElementById('book_cover').addEventListener('change', function () {
            var removeButton = document.getElementById('removeFileButton');
            if (this.value) {
                removeButton.style.display = 'inline-block';
            } else {
                removeButton.style.display = 'none';
            }
        });

        document.getElementById('removeFileButton').addEventListener('click', function () {
            var fileInput = document.getElementById('book_cover');
            fileInput.value = ''; // Clear the file input
            this.style.display = 'none'; // Hide the remove button
        });
    </script>

    <script>
        $(document).ready(function () {
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>
    <script src="script.js"></script>

    <!-- GET API SCRIPT -->
    <script>
        $(document).ready(function () {
            let allBooks = []; // To store all books from all pages

            $('#apiBookSearchModal').on('shown.bs.modal', function () {
                $('#loadingSpinner').show();
                $('#apiBooksTable').hide();
                fetchAllBooksFromAPI(); // Fetch all pages
            });

            function fetchAllBooksFromAPI(page = 1) {
                $.ajax({
                    url: 'fetch_api_books.php?page=' + page,
                    method: 'GET',
                    success: function (response) {
                        let books = JSON.parse(response);

                        if (books.books && books.books.data) {
                            allBooks = allBooks.concat(books.books.data);

                            if (page < books.books.last_page) {
                                fetchAllBooksFromAPI(page + 1); // Fetch next page
                            } else {
                                renderBooksTable();
                            }
                        } else {
                            $('#apiBooksTable tbody').append('<tr><td colspan="7" class="text-center">No books found</td></tr>');
                            $('#loadingSpinner').hide();
                            $('#apiBooksTable').show();
                        }
                    },
                    error: function () {
                        alert('Error fetching data from the API.');
                        $('#loadingSpinner').hide();
                        $('#apiBooksTable').show();
                    }
                });
            }

            function renderBooksTable() {
                let tableBody = $('#apiBooksTable tbody');
                tableBody.empty();

                allBooks.forEach(function (book) {
                    let authors = book.authors ? book.authors.map(a => a.author_name).join(', ') : 'N/A';
                    let material = book.material ? book.material.material_name : 'N/A';
                    let volume = book.volume ?? 'N/A';

                    let row = `
                    <tr>
                        <td><input type="checkbox" class="select-book" 
                            data-title="${book.title}" 
                            data-author="${authors}" 
                            data-call="${book.call_no}" 
                            data-volume="${volume}" 
                            data-year="${book.year_publication}" 
                            data-material="${material}"></td>
                        <td>${book.title}</td>
                        <td>${authors}</td>
                        <td>${book.call_no}</td>
                        <td>${volume}</td>
                        <td>${book.year_publication}</td>
                        <td>${material}</td>
                    </tr>`;
                    tableBody.append(row);
                });

                // Initialize DataTable after all data is loaded
                $('#apiBooksTable').DataTable({
                    "pageLength": 20,
                    "lengthChange": false,
                    "searching": true,
                    "info": true,
                    "pagingType": "simple_numbers",
                    "order": [[1, 'asc']],
                    "destroy": true
                });

                $('#loadingSpinner').hide();
                $('#apiBooksTable').show();
            }

            // Book selection logic remains the same
            $('#selectBooksBtn').on('click', function () {
                let selectedBooks = [];
                $('.select-book:checked').each(function () {
                    selectedBooks.push({
                        title: $(this).data('title'),
                        author: $(this).data('author'),
                        call_no: $(this).data('call'),
                        volume: $(this).data('volume'),
                        year_publication: $(this).data('year'),
                        material: $(this).data('material')
                    });
                });

                if (selectedBooks.length > 0) {
                    let bookDetails = selectedBooks.map(book =>
                        `${book.title} (Author: ${book.author}, Call No: ${book.call_no})`
                    ).join('\n');
                    alert('Selected Books:\n' + bookDetails);

                    const firstBook = selectedBooks[0];
                    $('input[name="title"]').val(firstBook.title);
                    $('input[name="author"]').val(firstBook.author);
                    $('input[name="call_no"]').val(firstBook.call_no);
                    $('input[name="volumes"]').val(firstBook.volume);
                    $('input[name="year_publication"]').val(firstBook.year_publication);
                } else {
                    alert('No books selected');
                }

                $('#apiBookSearchModal').modal('hide');
            });
        });
    </script>

</body>


</html>