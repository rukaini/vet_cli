<!--vetheader.php -->
<?php

/* =========================
   TOKEN AUTH (FRIEND SIDE)
========================= */
require_once "../backend/auth_header.php";

/* =========================
   ROLE GUARD (VET ONLY)
========================= */
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'vet') {
    die("Unauthorized role");
}
?>


<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>VetClinic</title>
    <meta name="description" content="">
    <meta name="keywords" content="">


    <!-- Favicons -->
    <link href="../MediTrust/assets/img/favicon.jpeg" rel="icon">
    <link href="../MediTrust/assets/img/apple-touch-icon.png" rel="apple-touch-icon">


    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap"
        rel="stylesheet">


    <!-- Vendor CSS Files -->
    <link href="../MediTrust/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../MediTrust/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../MediTrust/assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="../MediTrust/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../MediTrust/assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="../MediTrust/assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">


    <!-- Main CSS File -->
    <link href="../MediTrust/assets/css/main.css" rel="stylesheet">
</head>


<body class="index-page">


    <!-- ======= Header ======= -->
    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="header-container container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="http://10.48.74.199:81/vetcli/frontend/vethome.php<?= urlencode($_SESSION['sso_token']) ?>" class="logo d-flex align-items-center me-auto me-xl-0">
                <h1 class="sitename">VetClinic</h1>
            </a>


            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="http://10.48.74.199:81/vetcli/frontend/vethome.php?token=<?= urlencode($_SESSION['sso_token']) ?>">Home</a></li>


                    <li><a href="http://10.48.74.39/Workshop 2/frontend/report_vet.php?token=<?= urlencode($_SESSION['sso_token']) ?>">Dashboard</a></li>



                    <li><a href="http://10.48.74.199:81/vetcli/frontend/myschedule.php?token=<?= urlencode($_SESSION['sso_token']) ?>">MySchedule</a></li>


                    <li class="dropdown"><a href="#"><span>Patient Records</span> <i
                                class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            <li><a href="http://10.48.74.61/Vet_clinic/frontend/vet_app_list.php?token=<?= urlencode($_SESSION['sso_token']) ?>">List Appointment</a></li>
                            <li><a href="http://10.48.74.61/Vet_clinic/frontend/vet_app_history.php?token=<?= urlencode($_SESSION['sso_token']) ?>">Appointment History</a></li>
                        </ul>
                    </li>

                    <li class="dropdown"><a href="#"><span>Treatment</span> <i
                                class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            
                            <li><a href="treatment_list.php?token=<?= urlencode($_SESSION['sso_token']) ?>">List Treatment</a></li>
                        </ul>
                    </li>

                    <li><a href="http://10.48.74.199:81/vetcli/frontend/vetprofile.php?token=<?= urlencode($_SESSION['sso_token']) ?>">MyProfile</a></li>

                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>

            <a class="btn-getstarted" href="http://10.48.74.199:81/vetcli/backend/logout.php?token=<?= urlencode($_SESSION['sso_token']) ?>">Log out</a>

        </div>

    </header>
    <!-- End Header -->


    <!-- ======= Vendor JS Files ======= -->
    <script src="../MediTrust/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../MediTrust/assets/vendor/aos/aos.js"></script>
    <script src="../MediTrust/assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="../MediTrust/assets/vendor/glightbox/js/glightbox.min.js"></script>


    <!-- ======= Main JS File ======= -->
    <script src="../MediTrust/assets/js/main.js"></script>
    <script>
        AOS.init(); // initialize animations
    </script>