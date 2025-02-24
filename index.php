<?php
session_start();
include('header.php');
?>

<head>
    <script>
        function checkLoginStatus(event) {
            // Check if user is logged in
            const userLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

            if (!userLoggedIn) {
                // Prevent the form submission
                event.preventDefault();

                // Show the login prompt modal
                const loginPromptModal = new bootstrap.Modal(document.getElementById('loginPromptModal'));
                loginPromptModal.show();
            }
        }
    </script>
</head>

<body class="d-flex flex-column min-vh-100">
    <!-- HOME  -->
    <section>
        <div id="carouselExampleCaptions" class="carousel slide">
            <div class="overlay">
                <div class="container-lg">
                    <div class="row justify-content-center align-items-center mt-5">
                        <div class="col-md-7 text-center text-md-start">
                            <h1>
                                <div class="introtext display-6 d-md-block">
                                    Find Your Book.
                                </div>
                            </h1>
                            <h2 class="introtext2 display-8 my-2 d-md-block">
                                <i class="bi bi-check-circle-fill"></i>
                                Discover a wide range of books aligned with your curriculum on our school library website.
                            </h2>
                            <!-- Search form -->
                            <form class="d-flex my-2" role="search" action="library.php" method="GET" onsubmit="checkLoginStatus(event)">
                                <input class="form-control me-2" type="search" placeholder="Search by title, author, year, subject..." name="search" aria-label="Search">
                                <button class="btn" type="submit"><i class="lni lni-search-alt"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-inner" style="min-height:80vh;">
                <div class="carousel-item carousel-image bg-img-1 active "></div>
            </div>
        </div>
    </section>

    <section id="about" class="py-5" style="background-color: white;">
        <div class="container">
            <div class="row ">
                <div class="col-md-8">
                    <h2 style="color: #0A6847;">ABOUT US</h2>
                    <p class="mt-3" style="text-align: justify; color: #0A6847;">
                        Welcome to MyCourseReads: Curriculum Bibliographic Analyzer of ISU-I Library, your go-to resource for finding and accessing books that are specifically aligned with the curriculum of our university. Our platform is designed to streamline the process of locating relevant academic resources, ensuring that you have the materials you need to succeed in your studies.
                    </p>
                    <p class="mt-3" style="text-align: justify; color: #0A6847;">
                        Our mission is to provide a comprehensive and user-friendly system that supports students and faculty in their academic journey. With advanced search and filter options, you can easily discover books by title, author, year, or subject, all tailored to your specific program.
                    </p>
                    <p class="mt-3" style="text-align: justify; color: #0A6847;">
                        At MyCourseReads, we believe in empowering our academic community by making knowledge more accessible. Explore our extensive collection and enhance your learning experience today!
                    </p>
                </div>
                <div class="col-md-4">
                    <img src="img/bg33.png" alt="About Us Image" class="img-fluid">
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <?php
    include('footer.php');
    ?>

    <!-- Login Prompt Modal -->
    <div class="modal fade" id="loginPromptModal" tabindex="-1" aria-labelledby="loginPromptModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginPromptModalLabel">Login Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    You need to login to use the search feature.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" data-bs-toggle="modal" data-bs-target="#loginModal">Login Now</button>
                    <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</body>