<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>VetClinic</title>
    <meta name="description" content="">
    <meta name="keywords" content="">

    <link href="../MediTrust/assets/img/favicon.jpeg" rel="icon">
    <link href="../MediTrust/assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

    <link href="../MediTrust/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../MediTrust/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../MediTrust/assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="../MediTrust/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="../MediTrust/assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="../MediTrust/assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

    <link href="../MediTrust/assets/css/main.css" rel="stylesheet">
</head>

<body class="index-page">

    <header id="header" class="header d-flex align-items-center fixed-top">
        <div class="header-container container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="http://10.48.74.199:81/vetcli/frontend/vethome.php" class="logo d-flex align-items-center me-auto me-xl-0">
                <h1 class="sitename">VetClinic</h1>
            </a>

            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="http://10.48.74.199:81/vetcli/frontend/vethome.php" class="active">Home</a></li>

                    <li class="dropdown"><a href="#"><span>Patient Records</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            <li><a href="http://10.48.74.61/Vet_clinic/frontend/vet_app_list.php">List Appointment</a></li>
                            <li><a href="http://10.48.74.61/Vet_clinic/frontend/vet_app_history.php?vet_id=<?= $_SESSION['vetID'] ?>&vetname=<?= $_SESSION['vetName'] ?? '' ?>">Appointment History</a></li>
                        </ul>
                    </li>

                    <li class="dropdown"><a href="#"><span>Treatment</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            <li><a href="treatment_list.php">List Treatment</a></li>
                        </ul>
                    </li>

                    <li class="dropdown"><a href="#"><span>Medicine</span> <i class="bi bi-chevron-down toggle-dropdown"></i></a>
                        <ul>
                            <li><a href="medicine_list.php">Medicine Inventory</a></li>
                        </ul>
                    </li>

                    <li><a href="http://10.48.74.199:81/vetcli/frontend/vetprofile.php">MyProfile</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>

            <a class="btn-getstarted" href="http://10.48.74.199:81/vetcli/backend/logout.php">Log out</a>
        </div>
    </header>
    <script src="../MediTrust/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../MediTrust/assets/vendor/aos/aos.js"></script>
    <script src="../MediTrust/assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="../MediTrust/assets/vendor/glightbox/js/glightbox.min.js"></script>

    <script src="../MediTrust/assets/js/main.js"></script>
    <script>
        AOS.init(); // initialize animations
    </script>
</body>
</html>