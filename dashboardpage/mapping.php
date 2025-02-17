<?php
include("session_check.php"); // session check to protect pages
ob_start(); // Start output buffering
include("../config/db_localhost.php");
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
        .table th,
        .table td {

            /* Center align text */
            vertical-align: middle;
            /* Center align vertically */

        }

        .table th {
            width: 15%;
            /* Adjust width for other columns */
            text-align: center;
        }


        .table th:nth-child(2) {
            width: 45%;
            /* Expand Author/Title column */
        }

        .table-text {
            font-size: 0.7rem;
            /* Adjust this size to match the table text */
            margin: 0;
            /* Remove any default margin if needed */

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
                        <h1 class="titlepage display-8"> <i class="lni lni-layers"></i> Collection Mapping </h1>
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
                            <li class="breadcrumb-item active" aria-current="page">Collection Mapping</li>
                        </ol>
                    </nav>
                </div>

                <div class="container mt-4">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="program" class="form-label">Select Program:</label>
                            <select class="form-select" id="program" name="program_id" required>
                                <option value="">-- Select Program --</option>
                                <?php
                                // Fetch all programs including major
                                $programsResult = $conn->query("SELECT id, program_name, major FROM programs ORDER BY program_name ASC");

                                // Check if a program is selected (from GET)
                                $selectedProgramId = isset($_GET['program_id']) ? $_GET['program_id'] : '';

                                // Loop through programs and mark the selected one
                                while ($program = $programsResult->fetch_assoc()) {
                                    $programDisplay = $program['program_name'] . ' - ' . $program['major'];
                                    // Check if this program matches the selected one
                                    $selected = ($program['id'] == $selectedProgramId) ? 'selected' : '';
                                    echo '<option value="' . $program['id'] . '" ' . $selected . '>' . $programDisplay . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn">Generate Mapping</button>
                    </form>



                    <!-- TABLE REPORTS -->
                    <?php
                    if (isset($_GET['program_id'])) {
                        $programId = $_GET['program_id'];

                        // Fetch the program name and major
                        $programQuery = $conn->query("SELECT program_name, major FROM programs WHERE id = $programId");
                        $programData = $programQuery->fetch_assoc();
                        $programNameWithMajor = htmlspecialchars($programData['program_name'] . ' - ' . $programData['major']);


                        $sql = "
                        SELECT 
                            subjects.subject_code, 
                            subjects.subject_name, 
                            IFNULL(COUNT(DISTINCT books.id), 0) AS num_titles,
                            IFNULL(SUM(books.volumes), 0) AS num_volumes,
                            SUM(CASE WHEN books.year_publication >= YEAR(CURDATE()) - 5 THEN 1 ELSE 0 END) AS copyrighted_5_years_title,
                            SUM(CASE WHEN books.year_publication >= YEAR(CURDATE()) - 5 THEN books.volumes ELSE 0 END) AS copyrighted_5_years_volume,
                            SUM(CASE WHEN books.year_publication >= YEAR(CURDATE()) - 10 AND books.year_publication < YEAR(CURDATE()) - 5 THEN 1 ELSE 0 END) AS copyrighted_10_years_title,
                            SUM(CASE WHEN books.year_publication >= YEAR(CURDATE()) - 10 AND books.year_publication < YEAR(CURDATE()) - 5 THEN books.volumes ELSE 0 END) AS copyrighted_10_years_volume
                        FROM subjects
                        LEFT JOIN program_subjects ON subjects.id = program_subjects.subject_id
                        LEFT JOIN subject_books ON subjects.id = subject_books.subject_id
                        LEFT JOIN books ON subject_books.book_id = books.id
                        WHERE program_subjects.program_id = $programId
                        GROUP BY subjects.subject_code, subjects.subject_name
                        ORDER BY subjects.subject_code
                    ";

                        $reportResult = $conn->query($sql);

                        // Check if the query was successful
                        if (!$reportResult) {
                            die("Query failed: " . $conn->error);
                        }

                        // Initialize variables to track totals
                        $total_titles = 0;
                        $total_volumes = 0;
                        $total_copyrighted_5_years_title = 0;
                        $total_copyrighted_5_years_volume = 0;
                        $total_copyrighted_10_years_title = 0;
                        $total_copyrighted_10_years_volume = 0;
                        $total_added_books = 0; // Initialize this based on your criteria

                        // Display the report table inside the card
                        echo '<div class="row my-3 align-items-center justify-content-center">';
                        echo '<div class="card col-sm mx-3 my-3" style="width: 100%;">';
                        echo '<div class="card-body">';
                        echo '<div class="d-flex justify-content-between align-items-center mb-3">';
                        echo '<div>';
                        echo '<h7 class="card-title mb-0" style="text-transform: uppercase;"><strong>' . $programNameWithMajor . '</strong></h7>';
                        echo '</div>';
                        echo '<button class="btn" onclick="printReport(\'' . htmlspecialchars($programData['program_name']) . '\', \'' . htmlspecialchars($programData['major']) . '\')"><i class="lni lni-printer"></i> Print</button>';
                        echo '</div>';

                        // Start of mapping table
                        echo '<table class="reportTable table table-bordered">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th rowspan="3">COURSE CODE</th>';
                        echo '<th rowspan="3">SPECIALIZATION</th>';
                        echo '<th rowspan="3">Minimum Title Requirement of CHED and Accrediting Body/ Organization</th>';
                        echo '<th rowspan="3">NO. OF TITLES</th>';
                        echo '<th rowspan="3">NO. OF VOLUMES</th>';
                        echo '<th colspan="6">NO. OF COLLECTIONS</th>'; // Main header for NO. OF COLLECTIONS
                        echo '</tr>';
                        echo '<tr>';
                        echo '<th colspan="2">COPYRIGHTED FOR THE LAST FIVE (5) YEARS</th>'; // Sub-header for last 5 years
                        echo '<th colspan="2">COPYRIGHTED FOR THE LAST TEN (10) YEARS</th>'; // Sub-header for last 10 years 
                        echo '<th rowspan="2">ACQUISITION PLAN</th>'; // Acquisition Plan header
                        echo '</tr>';
                        echo '<tr>';
                        echo '<th>Title</th>';  // Sub-column for Title under the last 5 years
                        echo '<th>Volume</th>'; // Sub-column for Volume under the last 5 years
                        echo '<th>Title</th>';  // Sub-column for Title under the last 10 years
                        echo '<th>Volume</th>'; // Sub-column for Volume under the last 10 years
                        echo '</tr>';
                        echo '</thead>';

                        // Track the current subject
                        $currentSubjectCode = '';

                        // Inside the loop where you fetch report data
                        while ($row = $reportResult->fetch_assoc()) {
                            // Start a new row for each subject
                            echo '<tr>';
                            echo '<td>' . htmlspecialchars($row['subject_code']) . '</td>';
                            echo '<td><strong>' . htmlspecialchars($row['subject_name']) . '</strong></td>';
                            echo '<td>5</td>'; // Minimum Title Requirement (assuming it's always 5)
                            echo '<td>' . htmlspecialchars($row['num_titles']) . '</td>'; // Number of Titles
                            echo '<td>' . htmlspecialchars($row['num_volumes']) . '</td>'; // Number of Volumes
                            echo '<td>' . htmlspecialchars($row['copyrighted_5_years_title']) . '</td>'; // Title (5 years)
                            echo '<td>' . htmlspecialchars($row['copyrighted_5_years_volume']) . '</td>'; // Volume (5 years)
                            echo '<td>' . htmlspecialchars($row['copyrighted_10_years_title']) . '</td>'; // Title (10 years)
                            echo '<td>' . htmlspecialchars($row['copyrighted_10_years_volume']) . '</td>'; // Volume (10 years)

                            // Calculate the number of books to be added
                            $added_books = max(0, 5 - $row['num_titles']); // Books to add if titles are less than 5


                            // Output the values in the table row
                            echo '<td>' . $added_books . '</td>'; // Number of Added Books for Acquisitional Plan


                            // Update totals
                            $total_titles += (int)$row['num_titles'];
                            $total_volumes += (int)$row['num_volumes'];
                            $total_copyrighted_5_years_title += $row['copyrighted_5_years_title'];
                            $total_copyrighted_5_years_volume += $row['copyrighted_5_years_volume'];
                            $total_copyrighted_10_years_title += $row['copyrighted_10_years_title'];
                            $total_copyrighted_10_years_volume += $row['copyrighted_10_years_volume'];
                            $total_added_books += $added_books;

                            echo '</tr>';
                        }

                        // Print totals row
                        echo '<tr>';
                        echo '<td colspan="3"><strong>Totals</strong></td>';
                        echo '<td><strong>' . $total_titles . '</strong></td>';
                        echo '<td><strong>' . $total_volumes . '</strong></td>';
                        echo '<td><strong>' . $total_copyrighted_5_years_title . '</strong></td>';
                        echo '<td><strong>' . $total_copyrighted_5_years_volume . '</strong></td>';
                        echo '<td><strong>' . $total_copyrighted_10_years_title . '</strong></td>';
                        echo '<td><strong>' . $total_copyrighted_10_years_volume . '</strong></td>';
                        echo '<td><strong>' . $total_added_books . '</strong></td>'; // Total for Added Books for Acquisitional Plan
                        echo '</tr>';

                        echo '</tbody>';
                        echo '</table>';


                        // Prepared by section
                        echo '<div class="prepared-by-section" style="margin-left: 20px; margin-top: 20px;">'; // Adjusted margin for left space and added top margin
                        echo '<label style="margin-bottom: 10px; font-size: 0.7rem;">Prepared by:</label>'; // Label with font size
                        echo '<div style="margin-top: 10px; margin-left: 40px; font-size: 0.7rem;">'; // Added a div to wrap the name display for spacing
                        echo '<span style="font-size: 1em; font-weight: bold;">MARGIE D. CACAL, RL</span>'; // Static name display
                        echo '</div>';
                        echo '<div style="margin-top: 10px; margin-left: 40px; border-bottom: 1px solid black; width: 10%;"></div>'; // Underline between name and position
                        echo '<div style="margin-top: 5px; margin-left: 40px; font-size: 0.7rem;">'; // Added a div for position display
                        echo '<span style="font-size: 1em;">Campus Librarian</span>'; // Static position display
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>


                    <!-- Add JavaScript for printing -->
                    <script>
                        function printReport(courseName, majorName) {
                            var printWindow = window.open('', '', 'height=600,width=800');
                            var printContent = '';

                            // Get all tables with the reportTable class
                            var tables = document.querySelectorAll('.reportTable');

                            // Concatenate the outerHTML of each table
                            tables.forEach(function(table) {
                                printContent += table.outerHTML + '<br>'; // Add a line break between tables
                            });

                            // Add CSS for printing
                            var styles = `
                                    <style>
                                        @page {
                                            size: A4 landscape; /* Set landscape orientation */
                                            margin: 10mm; /* Optional: adjust margins */
                                        }
                                        body {
                                            font-family: Arial, sans-serif;
                                        }
                                        table {
                                            border-collapse: collapse;
                                            width: 100%;
                                            margin-bottom: 20px; /* Space between tables */
                                        }
                                        table, th, td {
                                            border: 1px solid black;
                                        }
                                        th, td {
                                            padding: 8px;
                                            text-align: left;
                                        }
                                        th {
                                            background-color: #f2f2f2;
                                        }
                                        th:nth-child(1) { width: 25%; } /* Adjust width for COURSE NUMBER */
                                        th:nth-child(2) { width: 45%; } /* Adjust width for Author/Title */
                                        th:nth-child(3) { width: 10%; } /* Adjust width for Volumes */
                                        th:nth-child(4) { width: 10%; } /* Adjust width for Call No. */
                                        th:nth-child(5) { width: 10%; } /* Adjust width for Copyright */
                                        .header {
                                            text-align: center;
                                            margin-bottom: 10px;
                                            line-height: 1.5;
                                        }
                                        .university-name {
                                            font-size: 1em;
                                            font-weight: bold;
                                        }
                                        .location {
                                            font-size: 1em;
                                        }
                                        .logo {
                                            width: 85px; /* Adjust size as needed */
                                            vertical-align: middle;
                                            margin-right: 35px;
                                        }
                                        .header-container {
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                        }
                                        .reference-info {
                                            text-align: center;
                                            margin-top: 20px;
                                            font-size: 1em; /* Adjust font size as needed */
                                            margin-bottom: 20px;
                                            font-weight: bold;
                                            line-height: 1.5;
                                        }
                                    </style>
                                `;

                            // Prepare the "Prepared by" content
                            var preparedByContent = `
                                    <div class="prepared-by-section" style="margin-left: 20px; margin-top: 20px;">
                                        <label style="margin-bottom: 10px; font-size: 1em;">Prepared by:</label>
                                        <div style="margin-top: 10px; margin-left: 40px;">
                                            <span style="font-size: 1em; font-weight: bold;">MARGIE D. CACAL, RL</span> <!-- Static name display -->
                                        </div>
                                        <div style="margin-top: 10px; margin-left: 40px; border-bottom: 1px solid black; width: 11%;"></div> <!-- Underline between name and position -->
                                        <div style="margin-top: 5px; margin-left: 40px;">
                                            <span style="font-size: 1em;">Campus Librarian</span> <!-- Static position display -->
                                        </div> 
                                    </div>
                                `;



                            printWindow.document.write('<html><head><title>Print Report</title>' + styles + '</head><body>');
                            printWindow.document.write('<div class="header-container">');
                            printWindow.document.write('<img src="icons/isulogo.png" class="logo" alt="ISU Logo">'); // Logo
                            printWindow.document.write('<div class="header">');
                            printWindow.document.write('<div class="location">Republic of the Philippines</div>');
                            printWindow.document.write('<div class="university-name">ISABELA STATE UNIVERSITY</div>');
                            printWindow.document.write('<div class="location">City of Ilagan, Isabela</div>');
                            printWindow.document.write('</div>');
                            printWindow.document.write('</div>');

                            // Add the new paragraph
                            printWindow.document.write('<div class="reference-info">Summary of Library Holdings in ' + courseName + '<br>');
                            if (majorName && majorName.toLowerCase() !== "none") {
                                printWindow.document.write('Major in ' + majorName + '<br>');
                            }
                            printWindow.document.write('as of May 2022</div>');


                            printWindow.document.write(printContent);

                            printWindow.document.write(preparedByContent); // Include prepared by content

                            printWindow.document.write('</body></html>');
                            printWindow.document.close();
                            printWindow.focus();
                            printWindow.print();
                        }
                    </script>

                </div>
            </section>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="script.js"></script>

</body>

</html>