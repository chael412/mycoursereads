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
                        <h1 class="titlepage display-8"> <i class="lni lni-layers"></i> Generate Reference Materials </h1>
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
                            <li class="breadcrumb-item active" aria-current="page">Summary Report</li>
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
                        <button type="submit" class="btn">Generate Report</button>
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
        books.author, 
        books.title, 
        books.call_no, 
        books.volumes, 
        books.year_publication
    FROM subjects
    LEFT JOIN program_subjects ON subjects.id = program_subjects.subject_id
    LEFT JOIN subject_books ON subjects.id = subject_books.subject_id
    LEFT JOIN books ON subject_books.book_id = books.id
    WHERE program_subjects.program_id = $programId
    ORDER BY subjects.subject_code
    ";

                        $reportResult = $conn->query($sql);

                        // Check if the query was successful
                        if (!$reportResult) {
                            die("Query failed: " . $conn->error);
                        }

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
                        echo '<div class="table-container mt-3">';

                        // Check if there are results
                        if ($reportResult->num_rows > 0) {
                            $currentSubjectCode = '';
                            $subjectRowCount = 0; // To track how many books for the current subject
                            $totalBooksPerSubject = 0; // Total books for current subject
                            $totalVolumesPerSubject = 0; // Total volumes for current subject

                            // Inside the loop where you fetch the report data
                            while ($row = $reportResult->fetch_assoc()) {
                                // Check if we are still in the same subject or starting a new one
                                if ($row['subject_code'] !== $currentSubjectCode) {
                                    // If not the first subject, display totals for the previous subject
                                    if ($currentSubjectCode !== '') {
                                        // Display totals for the previous subject
                                        echo '<tr><td colspan="1" style="font-weight: bold;">Total:</td>';
                                        echo '<td>' . $totalBooksPerSubject . '</td>';
                                        echo '<td>' . $totalVolumesPerSubject . '</td>';
                                        echo '<td colspan="3"></td></tr>'; // Empty columns for alignment

                                        // Reset totals for the new subject
                                        $totalBooksPerSubject = 0;
                                        $totalVolumesPerSubject = 0;
                                    }

                                    // Start a new table for a new subject
                                    echo '<table class="reportTable table table-bordered">';
                                    echo '<thead><tr>';
                                    echo '<th style="font-weight: normal;">COURSE NUMBER: <strong>' . strtoupper(htmlspecialchars($row['subject_code'])) . '</strong></th>';
                                    echo '<th>Author/Title</th>';
                                    echo '<th>Volumes</th>';
                                    echo '<th>Call No.</th>';
                                    echo '<th>Copyright</th>';
                                    echo '</tr></thead>';
                                    echo '<tbody>';

                                    // Display the subject name in the first row
                                    echo '<tr>';
                                    echo '<td rowspan="1" id="subject-' . htmlspecialchars($row['subject_code']) . '">' . 'COURSE TITLE: <br> <strong>' . strtoupper(htmlspecialchars($row['subject_name'])) . '</strong></td>';

                                    // Check if there's a book assigned
                                    if ($row['author'] || $row['title']) {
                                        echo '<td>' . htmlspecialchars($row['author']) . '<br>' . htmlspecialchars($row['title']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['volumes']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['call_no']) . '</td>';
                                        echo '<td>' . 'c' . htmlspecialchars($row['year_publication']) . '</td>';
                                        $totalBooksPerSubject++; // Increment totalBooks for each book displayed
                                        $totalVolumesPerSubject += intval($row['volumes']); // Increment totalVolumes
                                    } else {
                                        // No book assigned, display remark and N/A
                                        echo '<td class="text-danger">No books assigned for this subject.</td>';
                                        echo '<td>N/A</td><td>N/A</td><td>N/A</td><td>N/A</td>';
                                    }
                                    echo '</tr>';

                                    // Update the current subject code
                                    $currentSubjectCode = $row['subject_code'];
                                    $subjectRowCount = 1; // Reset row count for the new subject
                                } else {
                                    // Increment the rowspan for the existing subject
                                    $subjectRowCount++;

                                    // Update the rowspan of the subject name cell
                                    echo '<script>
                                        document.getElementById("subject-' . htmlspecialchars($row['subject_code']) . '").setAttribute("rowspan", ' . $subjectRowCount . ');
                                    </script>';

                                    // Display the book information for the current subject
                                    echo '<tr>';
                                    if ($row['author'] || $row['title']) {
                                        echo '<td>' . htmlspecialchars($row['author']) . '<br>' . htmlspecialchars($row['title']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['volumes']) . '</td>';
                                        echo '<td>' . htmlspecialchars($row['call_no']) . '</td>';
                                        echo '<td>' . 'c' . htmlspecialchars($row['year_publication']) . '</td>';
                                        $totalBooksPerSubject++; // Increment totalBooks for each book displayed
                                        $totalVolumesPerSubject += intval($row['volumes']); // Increment totalVolumes
                                    } else {
                                        // No book assigned, display remark and N/A
                                        echo '<td class="text-danger">No books assigned for this subject.</td>';
                                        echo '<td>N/A</td><td>N/A</td><td>N/A</td><td>N/A</td>';
                                    }
                                    echo '</tr>';
                                }
                            }

                            // Display totals for the last subject
                            echo '<tr><td colspan="1" style="font-weight: bold;">Total:</td>';
                            echo '<td>' . $totalBooksPerSubject . '</td>';
                            echo '<td>' . $totalVolumesPerSubject . '</td>';
                            echo '<td colspan="3"></td></tr>'; // Empty columns for alignment

                            // Close the last table
                            echo '</tbody></table>';
                        } else {
                            echo '<p>No data available for this program.</p>';
                        }

                        // Prepared by section
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

                        // Table Footer for ISU-Lib-Prb-081, Effectivity, and Revision in separate lines
                        echo '<br><table class="table-footer" style="width: 100%; margin-top: 20px; font-size: 0.5rem; border: none;">'; // Add table for the footer section
                        echo '<tr>';
                        echo '<td style="padding-left: 20px; font-size: 0.6rem;">ISU-Lib-Prb-081</td>'; // Document code in the first row
                        echo '</tr>';
                        echo '<tr>';
                        echo '<td style="padding-left: 20px; font-size: 0.6rem;">Effectivity: May 28, 2024 <input type="text" id="effectivity" name="effectivity" style="border: none; width: 120px; outline: none; font-size: 0.7rem;" />';
                        echo '<span id="formatted-effectivity" style="margin-left: 10px; font-size: 0.6rem;"></span></td>'; // Input for Effectivity in the second row
                        echo '</tr>';
                        echo '<tr>';
                        echo '<td style="padding-left: 20px; font-size: 0.6rem;">Revision: 0<input type="text" id="revision" name="revision" style="border: none; width: 100px; outline: none; font-size: 0.7rem;" /></td>'; // Input for Revision in the third row
                        echo '</tr>';
                        echo '</table>'; // Close the table

                        echo '</div>'; // Close table-container
                        echo '</div>'; // Close card-body
                        echo '</div>'; // Close card
                        echo '</div>'; // Close row
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
                                        .footer-section {
                                            margin-top: 20px;
                                            font-size: 0.6rem;
                                            padding-left: 20px; /* Add padding for left alignment */
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
                                        <div style="margin-top: 10px; margin-left: 40px; border-bottom: 1px solid black; width: 17%;"></div> <!-- Underline between name and position -->
                                        <div style="margin-top: 5px; margin-left: 40px;">
                                            <span style="font-size: 1em;">Campus Librarian</span> <!-- Static position display -->
                                        </div> 
                                    </div>
                                `;

                            // Footer section with ISU-Lib-Prb-081, Effectivity, and Revision
                            var footerContent = `
                                    <div class="footer-section">
                                        <div>ISU-Lib-Prb-081</div>
                                        <div>Effectivity: May 28, 2024</div>
                                        <div>Revision: 0</div>
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
                            printWindow.document.write('<div class="reference-info">Reference Materials for ' + courseName + '<br>');
                            if (majorName && majorName.toLowerCase() !== "none") {
                                printWindow.document.write('Major in ' + majorName + '<br>');
                            }
                            printWindow.document.write('Per Course Syllabus in Professional Subjects</div>');


                            printWindow.document.write(printContent);

                            printWindow.document.write(preparedByContent); // Include prepared by content
                            printWindow.document.write(footerContent); // Include footer content

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