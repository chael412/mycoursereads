<?php
include("session_check.php"); // session check to protect pages
ob_start(); // Start output buffering
include("../config/db_localhost.php");

// Fetch the first program to auto-select
$firstProgramId = null;
$programsResult = $conn->query("SELECT id FROM programs LIMIT 1");
if ($programsResult->num_rows > 0) {
    $firstProgram = $programsResult->fetch_assoc();
    $firstProgramId = $firstProgram['id'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>

    <!-- CDN LINKS -->
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>

    <style>
        .book-container {
            display: none;
            /* Initially hidden */
            border: 1px solid #ccc;
            /* Border for the book container */
            padding: 10px;
            /* Padding inside the book container */
            background-color: #f9f9f9;
            /* Background color for the book container */
            margin-left: 20px;
            /* Space between subject and book container */
        }

        .subject-divider {
            border-top: 1px solid #eaeaea;
            /* Divider line */
            margin: 10px 0;
            /* Margin for spacing */
        }

        .subject-item {
            cursor: pointer;
            /* Change cursor to pointer */
        }

        /* Adjust the size of the select dropdown */
        .form-select.w-auto {
            width: auto;
            /* Adjust width as needed */
            min-width: 150px;
            /* Set a minimum width */
        }
    </style>
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
                        <h1 class="titlepage display-8"> <i class="lni lni-layers"></i> Programs & Courses </h1>
                    </div>

                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="dashboards.php">
                                    <i class="lni lni-home"></i> <!-- Home Icon -->
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Reports</li>
                            <li class="breadcrumb-item active" aria-current="page">Programs & Courses</li>
                        </ol>
                    </nav>
                </div>

                <div class="container">
                    <!-- Card for Course Selection and Generated Report -->
                    <div class="row my-3 align-items-center justify-content-center">
                        <div class="card col-sm mx-3 my-3">
                            <div class="card-body">
                                <!-- Course Selection Form -->
                                <form method="GET" action="" id="reportForm" class="d-flex align-items-center mb-4 mt-2">
                                    <i class="lni lni-funnel mx-2"></i>
                                    <select class="form-select form-select-sm" id="program" name="program_id" required onchange="this.form.submit()">
                                        <option value="">-- Select Program --</option>
                                        <?php
                                        // Fetch all programs including major
                                        $programsResult = $conn->query("SELECT id, program_name, major FROM programs ORDER BY program_name ASC");
                                        while ($program = $programsResult->fetch_assoc()) {
                                            $programDisplay = $program['program_name'] . ' - ' . $program['major'];
                                            // Set the selected attribute if the program ID matches the one in the URL
                                            $selected = (isset($_GET['program_id']) && $_GET['program_id'] == $program['id']) ? 'selected' : '';
                                            echo '<option value="' . $program['id'] . '" ' . $selected . '>' . $programDisplay . '</option>';
                                        }
                                        ?>
                                    </select>
                                </form>

                                <!-- Generated Report -->
                                <?php
                                // Automatically select the first program if no program is selected
                                $programId = isset($_GET['program_id']) ? $_GET['program_id'] : $firstProgramId;

                                if ($programId) {
                                    // Fetch the program name and major
                                    $programQuery = $conn->query("SELECT program_name, major FROM programs WHERE id = $programId");
                                    $programData = $programQuery->fetch_assoc();
                                    $programNameWithMajor = htmlspecialchars($programData['program_name'] . ' - ' . $programData['major']);

                                    // Fetch the report data
                                    $sql = "
                    SELECT 
                        subjects.subject_code, subjects.subject_name, 
                        GROUP_CONCAT(books.title SEPARATOR ', ') AS book_titles,
                        GROUP_CONCAT(books.year_publication SEPARATOR ', ') AS publication_years
                    FROM subjects
                    LEFT JOIN program_subjects ON subjects.id = program_subjects.subject_id
                    LEFT JOIN subject_books ON subjects.id = subject_books.subject_id
                    LEFT JOIN books ON subject_books.book_id = books.id
                    WHERE program_subjects.program_id = $programId
                    GROUP BY subjects.subject_code, subjects.subject_name
                    ";
                                    $reportResult = $conn->query($sql);

                                    // Initialize total book count
                                    $totalBooks = 0;

                                    // Display the report content
                                    echo '<h7 class="card-title mb-0" style="text-transform: uppercase;"><strong>' . $programNameWithMajor . '</strong></h7>';

                                    // Toggle Button for All Books
                                    echo '<button class="btn btn-sm mb-3" id="toggleAllBooks" onclick="toggleAllBooks()" style="float: right;"><i class="lni lni-radio-button"></i> Toggle All Books</button>';

                                    echo '<p style="margin: 0; font-size: 0.8em;">';
                                    echo '<span style="font-size: 1.5em; color: green;">&#8226;</span> books within 5 years &nbsp;&nbsp;|&nbsp;&nbsp; ';
                                    echo '<span style="font-size: 1.5em; color: red;">&#8226;</span> books older than 5 years';
                                    echo '</p>';

                                    echo '<div class="subject-divider"></div>'; // Divider for each subject

                                    if ($reportResult->num_rows > 0) {
                                        while ($row = $reportResult->fetch_assoc()) {
                                            echo '<div class="subject-item" onclick="toggleBooks(this)">'; // Make subject clickable
                                            echo '<div class="d-flex justify-content-between align-items-center">';
                                            echo '<div>';
                                            echo '<strong>' . htmlspecialchars($row['subject_code']) . '</strong> ' . htmlspecialchars($row['subject_name']);
                                            echo '</div>';
                                            echo '</div>';
                                            echo '<div class="book-container" style="margin-top: 15px;">'; // Container for books
                                            echo '<div style="white-space: nowrap;">'; // Prevent text wrapping
                                            $bookTitles = explode(', ', $row['book_titles']);
                                            $publicationYears = explode(', ', $row['publication_years']);

                                            // Check if book titles are available
                                            if (empty($bookTitles[0])) {
                                                echo 'No books assigned'; // Message when no books are available
                                            } else {
                                                for ($i = 0; $i < count($bookTitles); $i++) {
                                                    $yearPublished = intval($publicationYears[$i]);
                                                    $currentYear = date('Y');
                                                    $age = $currentYear - $yearPublished;

                                                    // Determine bullet color based on book age
                                                    $bulletColor = ($age >= 5 && $age <= 6) ? 'red' : 'green';

                                                    echo '<span style="font-size: 1.5em; color:' . $bulletColor . ';">&#8226;</span> ' . htmlspecialchars($bookTitles[$i]) . '<br>'; // Larger bullet symbol
                                                }
                                                $totalBooks += count($bookTitles); // Increment total book count
                                            }
                                            echo '</div>';
                                            echo '</div>'; // Close book-container div
                                            echo '<div class="subject-divider"></div>'; // Divider for each subject
                                            echo '</div>'; // Close subject-item div
                                        }
                                    } else {
                                        echo '<p>No subjects available for this program.</p>';
                                    }

                                    // Display total book count
                                    echo '<p>Total Books: <strong>' . $totalBooks . '</strong></p>';
                                } else {
                                    echo '<p>Please select a program to view the report.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SCRIPTS -->
            <script>
                function toggleBooks(subjectItem) {
                    const bookContainer = subjectItem.querySelector('.book-container');
                    if (bookContainer) {
                        bookContainer.style.display = bookContainer.style.display === 'none' || bookContainer.style.display === '' ? 'block' : 'none';
                    }
                }

                function toggleAllBooks() {
                    const bookContainers = document.querySelectorAll('.book-container');
                    bookContainers.forEach(container => {
                        container.style.display = container.style.display === 'none' || container.style.display === '' ? 'block' : 'none';
                    });
                }

                // Ensure all book containers are initially hidden
                document.querySelectorAll('.book-container').forEach(container => {
                    container.style.display = 'none'; // Initial state is hidden
                });
            </script>

            <!-- JAVASCRIPT FOR TOGGLING SIDE BAR -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
            <script src="script.js"></script>
        </div>
    </div>
</body>

</html>