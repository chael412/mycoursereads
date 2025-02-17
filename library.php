<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php"); // Redirect to login page or home page
    exit();
}
include('header.php');
include("config/db_localhost.php");

// Initialize total search result count
$totalResults = 0;

// Initialize filter variables
$yearFilter = isset($_GET['year']) ? $_GET['year'] : '';
$programFilter = isset($_GET['program']) ? $_GET['program'] : '';

// Pagination variables
$limit = 9; // Number of books per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build the SQL query with filters
$sql = "
    SELECT 
        books.id, books.title, books.year_publication, books.book_cover,
        books.author AS author_names, books.call_no,
        GROUP_CONCAT(DISTINCT subjects.subject_name SEPARATOR ', ') AS subject_names
    FROM books
    LEFT JOIN subject_books ON books.id = subject_books.book_id
    LEFT JOIN subjects ON subject_books.subject_id = subjects.id
    LEFT JOIN program_subjects ON subjects.id = program_subjects.subject_id
    WHERE 1=1
";

// Apply search filter
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $sql .= " AND (books.title LIKE '%$search%' 
               OR books.author LIKE '%$search%' 
               OR books.year_publication LIKE '%$search%' 
               OR subjects.subject_name LIKE '%$search%')";
}

// Apply year filter
if ($yearFilter) {
    $sql .= " AND books.year_publication = '$yearFilter'";
}

// Apply program filter
if ($programFilter) {
    $sql .= " AND program_subjects.program_id = '$programFilter'";
}

// Group and execute the query for total results
$totalQuery = $sql;
$totalQuery .= " GROUP BY books.id";
$totalResult = $conn->query($totalQuery);
$totalResults = $totalResult->num_rows;

// Modify the SQL query to limit results per page
$sql .= " GROUP BY books.id LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Fetch years and programs for filter options
$yearsSql = "SELECT DISTINCT year_publication FROM books ORDER BY year_publication DESC";
$yearsResult = $conn->query($yearsSql);

$programsSql = "
    SELECT programs.id, programs.program_name, programs.major 
    FROM programs
    LEFT JOIN program_subjects ON programs.id = program_subjects.program_id
    GROUP BY programs.id
";
$programsResult = $conn->query($programsSql);

// Calculate the total number of pages
$totalPages = ceil($totalResults / $limit);
?>

<body class="d-flex flex-column min-vh-100">
    <div class="container mt-5 pt-5"> <!-- Added pt-5 for padding at the top -->
        <h7>YOUR SEARCH RESULT: <?php echo $totalResults; ?></h7>
    </div>

    <section class="container mt-2">
        <div class="row">

            <!-- FILTER SIDEBAR -->
            <div class="col-md-3">
                <div class="card p-3">
                    <h5 class="card-title">Filters</h5>
                    <form action="library.php" method="GET">
                        <!-- Search Bar -->
                        <div class="mb-3">
                            <input class="form-control" type="search" name="search" placeholder="Search by title, author, year, subject..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        </div>

                        <!-- Year Filter -->
                        <div class="mb-3">
                            <label for="year" class="form-label"><i class="lni lni-calendar"></i> Year</label>
                            <select class="form-select" name="year" id="year">
                                <option value="">All Years</option>
                                <?php while ($year = $yearsResult->fetch_assoc()) { ?>
                                    <option value="<?php echo $year['year_publication']; ?>" <?php echo ($year['year_publication'] == $yearFilter) ? 'selected' : ''; ?>>
                                        <?php echo $year['year_publication']; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Program Filter -->
                        <div class="mb-3">
                            <label for="program" class="form-label"><i class="lni lni-graduation"></i> Programs</label>
                            <select class="form-select" name="program" id="program">
                                <option value="">All Programs</option>
                                <?php while ($program = $programsResult->fetch_assoc()) { ?>
                                    <option value="<?php echo $program['id']; ?>" <?php echo ($program['id'] == $programFilter) ? 'selected' : ''; ?>>
                                        <?php echo $program['program_name'] . ' (' . $program['major'] . ')'; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <button class="btn" type="submit">Apply Filters</button>
                    </form>

                    <!-- Image below filters -->
                    <!-- <div class="mt-5">
                        <img src="../img/bg88.png" alt="Filter Image" class="img-fluid">
                    </div> -->

                </div>

                <!-- Image below filters -->
                <div class="mt-5">
                    <img src="img/bg44.png" alt="Filter Image" class="img-fluid">
                </div>

            </div>


            <!-- BOOK LIST -->
            <div class="col-md-9">
                <!-- Breadcrumb Navigation -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="index.php">
                                <i class="lni lni-home"></i> <!-- Home Icon -->
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">Library</li>
                    </ol>
                </nav>
                <div class="row my-2 align-items-center justify-content-center">
                    <?php
                    // Output the HTML for each book card
                    if ($result->num_rows > 0) {
                        $count = 0; // Initialize counter to keep track of columns
                        while ($row = $result->fetch_assoc()) {
                            if ($count % 3 == 0) {
                                // Close the previous row if not the first row
                                if ($count != 0) {
                                    echo '</div>';
                                }
                                // Start a new row
                                echo '<div class="row my-2 align-items-center justify-content-center">'; // Reduced margin-top and margin-bottom
                            }
                            echo '<div class="col-md-4 d-flex justify-content-center mb-4">'; // Adjusted to fit three cards per row and added margin-bottom
                            echo '<div class="card card-book" style="width: 100%; height: 25rem;">'; // Adjusted card width and height if needed
                            echo '<div class="card-body">';
                            echo '<div class="col text-center">';
                            echo '<img src="uploads/' . $row["book_cover"] . '" alt="' . $row["title"] . '" style="max-height: 150px; max-width: 100%; object-fit: cover;">'; // Set max-height and max-width for the image
                            echo '</div>';
                            echo '<h4 class="card-title mt-5 mb-3" style="font-size: 0.8rem; font-weight: bold;">' . $row["title"] . '</h4>';
                            echo '<p class="card-text mb-1" style="font-size: 0.65rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . $row["author_names"] . '</p>';
                            echo '<p class="card-text mb-1" style="font-size: 0.65rem;">' . $row["year_publication"] . '</p>';
                            echo '<p class="card-text mb-1" style="font-size: 0.65rem;">' . $row["subject_names"] . '</p>';
                            echo '<p class="card-text mb-1" style="font-size: 0.65rem;">' . $row["call_no"] . '</p>';
                            

                            // echo '<a href="#" class="btn position-absolute bottom-0 start-50 translate-middle-x mb-4">View Details</a>'; // Adjust margin bottom to bring the button closer to the bottom
                            echo '</div>';
                            echo '</div>';
                            echo '</div>';
                            $count++;
                        }
                        // Close the last row
                        if ($count % 3 != 0) {
                            echo '</div>';
                        }
                    } else {
                        echo "No books found";
                    }
                    ?>
                </div>

                <!-- Pagination -->
                <nav aria-label="Page navigation" class="text-center">
                    <ul class="pagination">
                        <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                            <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page=' . ($page - 1) . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '&year=' . urlencode($yearFilter) . '&program=' . urlencode($programFilter); ?>" tabindex="-1">Previous</a>
                        </li>

                        <?php
                        $maxLinks = 5; // Number of pagination links to show
                        $start = max(1, $page - floor($maxLinks / 2));
                        $end = min($totalPages, $start + $maxLinks - 1);

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1' . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '&year=' . urlencode($yearFilter) . '&program=' . urlencode($programFilter) . '">1</a></li>';
                            if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }

                        for ($i = $start; $i <= $end; $i++) {
                            echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '&year=' . urlencode($yearFilter) . '&program=' . urlencode($programFilter) . '">' . $i . '</a></li>';
                        }

                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '&year=' . urlencode($yearFilter) . '&program=' . urlencode($programFilter) . '">' . $totalPages . '</a></li>';
                        }
                        ?>

                        <li class="page-item <?php if ($page >= $totalPages) echo 'disabled'; ?>">
                            <a class="page-link" href="<?php echo ($page >= $totalPages) ? '#' : '?page=' . ($page + 1) . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '') . '&year=' . urlencode($yearFilter) . '&program=' . urlencode($programFilter); ?>">Next</a>
                        </li>
                    </ul>
                </nav>


            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <?php
    include('footer.php');
    ?>
</body>