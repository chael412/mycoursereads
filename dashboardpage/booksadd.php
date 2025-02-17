<?php
include("session_check.php"); // session check to protect pages
ob_start();
include("../config/db_vivliotek.php");

// Insert save book code here
if (isset($_POST['save_books'])) {
    if (!empty($_POST['subject_id']) && !empty($_POST['book_id'])) {
        foreach ($_POST['book_id'] as $index => $book_id) {
            if (isset($_POST['subject_id'][$index]) && is_array($_POST['subject_id'][$index])) {
                foreach ($_POST['subject_id'][$index] as $subject_id) {
                    $sql = "INSERT INTO book_subjectcourse (book_id, subject_id) VALUES ('$book_id', '$subject_id')";
                    if (!mysqli_query($conn, $sql)) {
                        $errorMessage = "Error adding book to subjects: " . mysqli_error($conn);
                        break;
                    }
                }
            }
        }
        if (!isset($errorMessage)) {
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        $errorMessage = "Please select at least one book and one subject.";
    }
}

// Fetch books from book_subjectcourse
$booksQuery = "SELECT b.id, 
       GROUP_CONCAT(DISTINCT a.author_name SEPARATOR ', ') as author, 
       b.title, 
       b.call_no, 
       b.volume, 
       b.year_publication, 
       m.material_name as materials,
       GROUP_CONCAT(DISTINCT s.subject_code SEPARATOR ', ') as subjects
FROM book_subjectcourse bs
INNER JOIN books b ON bs.book_id = b.id
LEFT JOIN subjectcourse s ON bs.subject_id = s.id
LEFT JOIN book_authors ba ON b.id = ba.book_id
LEFT JOIN authors a ON ba.author_id = a.id
LEFT JOIN materials m ON b.material_id = m.id
GROUP BY b.id
";

$booksResult = mysqli_query($conn, $booksQuery);

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
                        <h1 class="titlepage display-8"> <i class="lni lni-book"></i> Add Books (Vivliotek db)</h1>
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

                                <!-- Form for Adding Books -->
                                <form method="post" action="" enctype="multipart/form-data"
                                    onsubmit="return validateForm()">
                                    <!-- Button to Open Search Modal -->
                                    <button type="button" class="btn my-3" data-bs-toggle="modal"
                                        data-bs-target="#VivlioBookSearchModal">
                                        <i class="lni lni-search"></i> Search from Vivliotek
                                    </button>

                                    <!-- Selected Books Display Section -->
                                    <div id="selectedBooksContainer" class="mt-3"></div>

                                    <!-- Submit Button -->
                                    <button type="submit" id="save_books" name="save_books"
                                        class="btn btn-md float-end mt-3">Add Books</button>
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
                                                <th>Volume</th>
                                                <th>Copyright</th>
                                                <th>Material</th>
                                                <th>Subjects</th>
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
                                                echo "<td>{$row['volume']}</td>";
                                                echo "<td>{$row['year_publication']}</td>";
                                                echo "<td>{$row['materials']}</td>";
                                                echo "<td>{$row['subjects']}</td>";

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

    <!-- Book Search Modal -->
    <div class="modal fade" id="VivlioBookSearchModal" tabindex="-1" aria-labelledby="#VivlioBookSearchModallLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="#VivlioBookSearchModalLabel">Search Books from Vivliotek</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Book Search Table -->
                    <table id="VivlioBooksTable" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>ID</th>
                                <th>Authors</th>
                                <th>Title</th>
                                <th>Call Number</th>
                                <th>Volume</th>
                                <th>Year</th>
                                <th>Material</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT books.*, 
                            GROUP_CONCAT(authors.author_name SEPARATOR ', ') AS authors, 
                            materials.material_name 
                     FROM books
                     LEFT JOIN book_authors ON books.id = book_authors.book_id
                     LEFT JOIN authors ON book_authors.author_id = authors.id
                     LEFT JOIN materials ON books.material_id = materials.id
                     GROUP BY books.id";
                            $result = mysqli_query($conn, $query);
                            while ($book = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td><input type='checkbox' class='select-book' 
                                data-id='{$book['id']}'
                                data-author='{$book['authors']}' 
                                data-title='{$book['title']}' 
                                data-call='{$book['call_no']}' 
                                data-volume='{$book['volume']}' 
                                data-year='{$book['year_publication']}' 
                                data-material='{$book['material_name']}'></td>";

                                echo "<td>{$book['id']}</td>";
                                echo "<td>{$book['authors']}</td>";
                                echo "<td>{$book['title']}</td>";
                                echo "<td>{$book['call_no']}</td>";
                                echo "<td>{$book['volume']}</td>";
                                echo "<td>{$book['year_publication']}</td>";
                                echo "<td>{$book['material_name']}</td>";
                                echo "</tr>";
                            }
                            ?>
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

    <script>
        $(document).ready(function () {
            $('#VivlioBooksTable').DataTable({
                "pageLength": 5,
                "lengthChange": false,
                "info": true,
                "pagingType": "simple_numbers"
            });

            $('#selectBooksBtn').click(function () {
                let selectedBooks = [];
                $('.select-book:checked').each(function () {
                    selectedBooks.push({
                        book_id: $(this).closest('tr').find('td:nth-child(2)').text(), // Captures the book ID from the second column
                        author: $(this).data('author'),
                        title: $(this).data('title'),
                        call: $(this).data('call'),
                        volume: $(this).data('volume'),
                        year: $(this).data('year'),
                        material: $(this).data('material')
                    });

                });

                let displayHtml = '<table class="table table-bordered"><thead><tr><th>Author</th><th>Title</th><th>Call Number</th><th>Volume</th><th>Year</th><th>Material</th><th>Subjects</th></tr></thead><tbody>';
                selectedBooks.forEach((book, index) => {
                    displayHtml += `<tr>
                    <td>${book.author}</td>
                    <td>${book.title}</td>
                    <td>${book.call}</td>
                    <td>${book.volume}</td>
                    <td>${book.year}</td>
                    <td>${book.material}</td>
                    <td>
                        <select name="subject_id[${index}][]" class="selectpicker custom-selectpicker btn1" style="background-color: white;" multiple data-live-search="true" data-actions-box="true" data-selected-text-format="count > 1">
                            <?php
                            $subjects = mysqli_query($conn, "SELECT id, subject_code, subject_name FROM subjectcourse ORDER BY subject_code ASC");
                            while ($subject = mysqli_fetch_assoc($subjects)) {
                                echo "<option value='{$subject['id']}'>{$subject['subject_code']} - {$subject['subject_name']}</option>";
                            }
                            ?>
                        </select>
                        <input type="hidden" name="book_id[${index}]" value="${book.book_id}">
                    </td>
                </tr>`;
                });
                displayHtml += '</tbody></table>';
                $('#selectedBooksContainer').html(displayHtml);

                $('#VivlioBookSearchModal').modal('hide');
                $('.selectpicker').selectpicker('refresh');
            });
        });
    </script>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>
    <script src="script.js"></script>

</body>

</html>