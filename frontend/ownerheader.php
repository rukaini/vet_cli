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
            <a href="http://10.48.74.199:81/vetcli/frontend/ownerhome.php" class="logo d-flex align-items-center me-auto me-xl-0">
                <h1 class="sitename">VetClinic</h1>
            </a>


            <nav id="navmenu" class="navmenu">
                <ul>
                    <li><a href="http://10.48.74.199:81/vetcli/frontend/ownerhome.php" class="active">Home</a></li>
                    <li><a href="http://10.48.74.199:81/vetcli/frontend/ownerservices.php">Our Services</a></li>
                    <li><a href="http://10.48.74.199:81/vetcli/frontend/ownerabout.php">About Us</a></li>


                    <li class="dropdown">
                        <a href="#">
                            <span>Appointment</span>
                            <i class="bi bi-chevron-down toggle-dropdown"></i>
                        </a>
                        <ul>
                            <li>
                                <a
                                    href="http://10.48.74.61/vet_clinic/frontend/new_appointment.php">
                                    <i class="fas fa-paw"></i>
                                    <span>Book Appointment</span>
                                </a>
                            </li>


                            <li>
                                <a
                                    href="http://10.48.74.61/vet_clinic/frontend/appointment_list.php">
                                    <i class="fas fa-paw"></i>
                                    <span>Upcoming Appointment</span>
                                </a>
                            </li>


                            <li>
                                <a
                                    href="http://10.48.74.61/vet_clinic/frontend/appointment_history.php">
                                    <i class="fas fa-paw"></i>
                                    <span>Appointment History</span>
                                </a>
                            </li>
                        </ul>
                    </li>




                    <li class="dropdown">
                        <a href="#">
                            <span>MyPet</span>
                            <i class="bi bi-chevron-down toggle-dropdown"></i>
                        </a>
                        <ul>
                            <li>
                                <a href="http://10.48.74.199:81/vetcli/frontend/newpet.php">
                                    <i class="fas fa-paw"></i>
                                    <span>New Pet</span>
                                </a>
                            </li>
                            <li>
                                <a href="http://10.48.74.199:81/vetcli/frontend/ownerpetlist.php">
                                    <i class="fas fa-paw"></i>
                                    <span>View Pet</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li><a href="../frontend/ownertreatment_details.php">Medical History</a></li>

                    <li><a href="http://10.48.74.197/vetclinic/frontend/paymentstatusowner.php">MyPayment</a></li>


                    <li><a href="http://10.48.74.199:81/vetcli/frontend/ownerprofile.php">MyProfile</a></li>
                </ul>
                <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
            </nav>
            <a class="btn-getstarted" href="../backend/logout.php">Log out</a>
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