<?php
include("session_check.php"); // session check to protect pages
ob_start(); // Start output buffering
include("../config/db_localhost.php");

// Query to get the number of books published each year
$booksByYearQuery = $conn->query("
    SELECT year_publication, COUNT(*) AS count 
    FROM books 
    GROUP BY year_publication 
    ORDER BY year_publication ASC
");

$years = [];
$bookCounts = [];
while ($row = $booksByYearQuery->fetch_assoc()) {
    $years[] = $row['year_publication'];
    $bookCounts[] = $row['count'];
}

// Fetch program names and book counts
$programsData = [];
$programsQuery = $conn->query("SELECT p.program_acronym, p.major, p.id FROM programs p");

if (!$programsQuery) {
    die("Database query failed: " . $conn->error);
}

while ($program = $programsQuery->fetch_assoc()) {
    if (!isset($program['id'])) {
        continue; // Skip if id is not set
    }

    $programId = $program['id'];

    // Query to count books per program
    $bookCountQuery = $conn->query("
        SELECT COUNT(*) AS count 
        FROM books b
        WHERE b.id IN (
            SELECT sb.book_id 
            FROM subject_books sb 
            WHERE sb.subject_id IN (
                SELECT ps.subject_id 
                FROM program_subjects ps 
                WHERE ps.program_id = $programId
            )
        )
    ");

    if (!$bookCountQuery) {
        die("Database query failed: " . $conn->error);
    }

    $bookCount = $bookCountQuery->fetch_assoc()['count'];

    // Store program acronym and major along with book count
    $programsData[] = [
        'label' => $program['program_acronym'] . ' (' . $program['major'] . ')',
        'count' => $bookCount
    ];
}

// Sort programsData alphabetically by 'label'
usort($programsData, function ($a, $b) {
    return strcmp($a['label'], $b['label']);
});

// Fetch subjects and their corresponding book counts
$subjectsData = [];
$subjectsQuery = $conn->query("
    SELECT s.id AS subject_id, s.subject_name AS subject_name, COUNT(sb.book_id) AS count 
    FROM subjects s
    LEFT JOIN subject_books sb ON s.id = sb.subject_id 
    GROUP BY s.id
");

if (!$subjectsQuery) {
    die("Database query failed: " . $conn->error); // Handle query failure
}

while ($subject = $subjectsQuery->fetch_assoc()) {
    $subjectsData[] = [
        'label' => $subject['subject_name'],
        'count' => $subject['count']
    ];
}

// Fetch materials and their corresponding counts
$materialsData = [];
$materialsQuery = $conn->query("
    SELECT m.material AS material_name, mb.material_id AS material_id, COUNT(mb.book_id) AS count 
    FROM material_books mb
    LEFT JOIN materials m ON mb.material_id = m.id
    GROUP BY mb.material_id
");

if (!$materialsQuery) {
    die("Database query failed: " . $conn->error); // Handle query failure
}

while ($material = $materialsQuery->fetch_assoc()) {
    $materialsData[] = [
        'label' => $material['material_name'], // Fetching the material name from the materials table
        'count' => $material['count']
    ];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyCourseReads | Dashboard</title>

    <!-- CDN LINKS -->
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>


    <style>
        .chart-card {
            height: 500px;
            /* Set a fixed height for the chart cards */
        }

        .chart-container {
            position: relative;
            height: 100%;
        }

        .chart-container canvas {
            height: 80% !important;
            /* Adjust height of canvas to fit card */
            width: 100% !important;
            /* Ensure the canvas takes up the full width of the card */
            max-height: 80%;
            /* Prevent the canvas from exceeding the card height */
        }

        /* Icon Container */
        .icon-container {
            font-size: 2rem;
            /* Adjust size as needed */
            color: #fff;
            /* Set icon color to match card text */
        }

        /* Adjust icon margin and alignment */
        .card-body .icon-container {
            flex-shrink: 0;
            /* Prevent icon from shrinking */
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
                        <h1 class="titlepage display-8"> <i class="lni lni-grid-alt"></i> Dashboard </h1>
                    </div>

                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="dashboards.php">
                                    <i class="lni lni-home"></i> <!-- Home Icon -->
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                        </ol>
                    </nav>
                </div>

                <!-- Program Selection -->
                <div class="container mt-4">
                    <div class="row">

                        <!-- Total Programs -->
                        <div class="col-md-3">
                            <div class="card mb-3">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php
                                        $totalPrograms = $conn->query("SELECT COUNT(*) AS count FROM programs")->fetch_assoc()['count'];
                                        ?>
                                        <h5 class="card-title">Total Programs</h5>
                                        <p class="card-text"><?php echo $totalPrograms; ?></p>
                                    </div>
                                    <i class="lni lni-graduation" style="font-size: 2rem; color: #2d669c;"></i> <!-- Icon -->
                                </div>
                            </div>
                        </div>

                        <!-- Total Subjects -->
                        <div class="col-md-3">
                            <div class="card mb-3">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php
                                        $totalSubjects = $conn->query("SELECT COUNT(*) AS count FROM subjects")->fetch_assoc()['count'];
                                        ?>
                                        <h5 class="card-title">Total Courses</h5>
                                        <p class="card-text"><?php echo $totalSubjects; ?></p>
                                    </div>
                                    <i class="lni lni-ruler-pencil" style="font-size: 2rem; color: #fca503;"></i> <!-- Icon -->
                                </div>
                            </div>
                        </div>

                        <!-- Total Books -->
                        <div class="col-md-3">
                            <div class="card mb-3">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php
                                        $totalBooks = $conn->query("SELECT COUNT(*) AS count FROM books")->fetch_assoc()['count'];
                                        ?>
                                        <h5 class="card-title">Total Books</h5>
                                        <p class="card-text"><?php echo $totalBooks; ?></p>
                                    </div>
                                    <i class="lni lni-book" style="font-size: 2rem; color: #04bd1f;"></i> <!-- Icon -->
                                </div>
                            </div>
                        </div>

                        <!-- Total Materials -->
                        <div class="col-md-3">
                            <div class="card mb-3">
                                <div class="card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <?php
                                        // Query to get the total number of materials
                                        $totalMaterials = $conn->query("SELECT COUNT(*) AS count FROM materials")->fetch_assoc()['count'];
                                        ?>
                                        <h5 class="card-title">Total Materials</h5>
                                        <p class="card-text"><?php echo $totalMaterials; ?></p>
                                    </div>
                                    <i class="lni lni-book" style="font-size: 2rem; color: #04a7bd;"></i> <!-- Icon -->
                                </div>
                            </div>
                        </div>


                    </div>

                    <div class="row">
                        <!-- Publications Over Time -->
                        <div class="col-md-6">
                            <div class="card chart-card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Publications Over Time</h5>
                                    <div class="chart-container">
                                        <canvas id="booksByYearChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Distribution of Materials -->
                        <div class="col-md-6">
                            <div class="card chart-card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Distribution of Materials</h5>
                                    <div class="chart-container">
                                        <canvas id="materialsDistributionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Books by Program -->
                        <div class="col-md-12">
                            <div class="card chart-card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Books by Program</h5>
                                    <div class="chart-container">
                                        <canvas id="booksByProgramChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="row"> -->
                    <!-- Book Allocation per Course -->
                    <!-- <div class="col-md-12">
                            <div class="card chart-card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Book Allocation per Course</h5>
                                    <div class="chart-container">
                                        <canvas id="booksBySubjectChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->


                    <script>
                        // Books by Program Chart
                        var ctx = document.getElementById('booksByProgramChart').getContext('2d');
                        var booksByProgramChart = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: [
                                    <?php foreach ($programsData as $programData) : ?> '<?php echo $programData['label']; ?>',
                                    <?php endforeach; ?>
                                ],
                                datasets: [{
                                    label: 'Books by Program',
                                    data: [
                                        <?php foreach ($programsData as $programData) : ?>
                                            <?php echo $programData['count']; ?>,
                                        <?php endforeach; ?>
                                    ],
                                    backgroundColor: [
                                        <?php
                                        // Generate unique colors for each program
                                        foreach ($programsData as $key => $programData) {
                                            echo "'hsl(" . ($key * 30 % 360) . ", 70%, 50%)',";
                                        }
                                        ?>
                                    ],
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        position: 'bottom',
                                        labels: {
                                            font: {
                                                size: 12
                                            },
                                            generateLabels: function(chart) {
                                                // Generate color-coded legend with program labels and apply custom text color
                                                return chart.data.labels.map(function(label, index) {
                                                    return {
                                                        text: label,
                                                        fillStyle: chart.data.datasets[0].backgroundColor[index],
                                                        strokeStyle: chart.data.datasets[0].backgroundColor[index],
                                                        hidden: false,
                                                        index: index,
                                                        // Change the text color here
                                                        fontColor: 'rgba(0, 0, 0, 0.6)' // Add fontColor directly to the label
                                                    };
                                                });
                                            }
                                        }
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(tooltipItem) {
                                                var dataset = booksByProgramChart.data.datasets[tooltipItem.datasetIndex];
                                                var total = dataset.data.reduce((a, b) => a + b, 0);
                                                var currentValue = dataset.data[tooltipItem.dataIndex];
                                                var percentage = Math.round((currentValue / total) * 100);

                                                return booksByProgramChart.data.labels[tooltipItem.dataIndex] + ': ' + currentValue + ' (' + percentage + '%)';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        ticks: {
                                            display: false // Hide x-axis labels
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            // Add padding or margin to the top of the y-axis labels
                                            padding: 10 // Adjust this value for more or less space
                                        }
                                    }
                                }
                            }
                        });


                        //Publication Over TimeChart
                        var ctx2 = document.getElementById('booksByYearChart').getContext('2d');
                        var booksByYearChart = new Chart(ctx2, {
                            type: 'line',
                            data: {
                                labels: <?php echo json_encode($years); ?>,
                                datasets: [{
                                    label: 'Books by Year of Publication',
                                    data: <?php echo json_encode($bookCounts); ?>,
                                    fill: false,
                                    borderColor: '#007bff',
                                    tension: 0.1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                /* Allow the chart to resize with the container */
                                scales: {
                                    x: {
                                        title: {
                                            display: true,
                                            text: 'Year of Publication'
                                        }
                                    },
                                    y: {
                                        title: {
                                            display: true,
                                            text: 'Number of Books'
                                        },
                                        beginAtZero: true
                                    }
                                }
                            }
                        });

                        // // Book Allocation per Course
                        // var ctx3 = document.getElementById('booksBySubjectChart').getContext('2d');
                        // var booksBySubjectChart = new Chart(ctx3, {
                        //     type: 'bar',
                        //     data: {
                        //         labels: [
                        // <?php foreach ($subjectsData as $subjectData) : ?> '<?php echo $subjectData['label']; ?>',
                        // <?php endforeach; ?>
                        //         ],
                        //         datasets: [{
                        //             label: 'Books by Subject',
                        //             data: [
                        //                 <?php foreach ($subjectsData as $subjectData) : ?>
                        //                     <?php echo $subjectData['count']; ?>,
                        //                 <?php endforeach; ?>
                        //             ],
                        //             backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#20c997'],
                        //         }]
                        //     },
                        //     options: {
                        //         responsive: true,
                        //         maintainAspectRatio: false,
                        //         plugins: {
                        //             legend: {
                        //                 position: 'top',
                        //                 labels: {
                        //                     font: {
                        //                         size: 10 // Adjust the font size of the labels here
                        //                     }
                        //                 }
                        //             },
                        //             tooltip: {
                        //                 callbacks: {
                        //                     label: function(tooltipItem) {
                        //                         var dataset = booksBySubjectChart.data.datasets[tooltipItem.datasetIndex];
                        //                         var total = dataset.data.reduce((a, b) => a + b, 0);
                        //                         var currentValue = dataset.data[tooltipItem.dataIndex];
                        //                         var percentage = Math.round((currentValue / total) * 100);

                        //                         return tooltipItem.label + ': ' + currentValue + ' (' + percentage + '%)';
                        //                     }
                        //                 }
                        //             },
                        //             datalabels: {
                        //                 color: '#fff', // Text color for the labels
                        //                 formatter: (value, context) => {
                        //                     const total = context.chart.data.datasets[context.datasetIndex].data.reduce((a, b) => a + b, 0);
                        //                     const percentage = Math.round((value / total) * 100);

                        //                     // Only display percentage if count is greater than 0
                        //                     return value > 0 ? `${percentage}%` : ''; // Return an empty string for zero counts
                        //                 },
                        //                 anchor: 'end', // Position the labels
                        //                 align: 'start'
                        //             }
                        //         }
                        //     },
                        //     plugins: [ChartDataLabels] // Add the plugin here
                        // });

                        //Distribution of Materials
                        var ctx4 = document.getElementById('materialsDistributionChart').getContext('2d');
                        var materialsDistributionChart = new Chart(ctx4, {
                            type: 'bar',
                            data: {
                                labels: [
                                    <?php foreach ($materialsData as $materialData) : ?> '<?php echo $materialData['label']; ?>',
                                    <?php endforeach; ?>
                                ],
                                datasets: [{
                                    label: 'Distribution of Materials',
                                    data: [
                                        <?php foreach ($materialsData as $materialData) : ?>
                                            <?php echo $materialData['count']; ?>,
                                        <?php endforeach; ?>
                                    ],
                                    backgroundColor: [
                                        '#FF6384', // Color for the first material
                                        '#36A2EB', // Color for the second material
                                        '#FFCE56', // Color for the third material
                                        '#4BC0C0', // Color for the fourth material
                                        '#9966FF', // Color for the fifth material
                                        '#FF9F40' // Color for the sixth material
                                    ],
                                    borderColor: '#fff',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1 // Set the step size for y-axis ticks
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: true
                                    },
                                    tooltip: {
                                        callbacks: {
                                            label: function(tooltipItem) {
                                                return tooltipItem.label + ': ' + tooltipItem.raw; // Show count on hover
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    </script>
                </div>

            </section>
            <!-- FOOTER -->
            <?php
            include('footer.php');
            ?>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="script.js"></script>

</body>

</html>