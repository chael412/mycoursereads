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
    <title>Data Recovery</title>

    <!-- CDN LINKS -->
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/css/bootstrap-select.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.1/js/bootstrap-select.min.js"></script>
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
                        <h1 class="titlepage display-8"> <i class="lni lni-database"></i> Data Recovery (not working yet)</h1>
                    </div>

                    <!-- Breadcrumb Navigation -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="dashboards.php">
                                    <i class="lni lni-home"></i> <!-- Home Icon -->
                                </a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Settings</li>
                            <li class="breadcrumb-item active" aria-current="page">Data Recovery</li>
                        </ol>
                    </nav>
                </div>

                <div class="container mt-4">

                    <!-- Export Data Section -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Export Current Data</h5>
                            <p class="card-text text-muted" style="font-size: 0.85rem;">Click the button below to create and download a backup of the current database.</p>
                            <a href="export_data.php" class="btn">Export Data</a>
                        </div>
                    </div>

                    <!-- Restore Data Section -->
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Restore Data</h5>
                            <form action="data_recovery_process.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="backupFile" class="form-label text-muted" style="font-size: 0.85rem;">Upload Backup File (SQL)</label>
                                    <input type="file" name="backupFile" id="backupFile" class="form-control" required>
                                </div>
                                <button type="submit" class="btn ">Restore Data</button>
                            </form>
                        </div>
                    </div>
                </div>


            </section>



            <!-- JAVASCRIPT FOR TOGGLING SIDE BAR -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
            <script src="script.js"></script>
        </div>
    </div>
</body>

</html>