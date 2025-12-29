<!-- admin header -->
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
       href="https://fonts.googleapis.com/css2?family=Roboto:wght@100;300;400;500;700;900&family=Lato:wght@100;300;400;700;900&family=Raleway:wght@100;200;300;400;500;600;700;800;900&display=swap"
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


   <!--Header and Navmenu-->
   <header id="header" class="header d-flex align-items-center fixed-top">
       <div class="header-container container-fluid container-xl d-flex align-items-center justify-content-between">
           <a href="http://10.48.74.199:81/vetcli/frontend/adminhome.php" class="logo d-flex align-items-center me-auto me-xl-0">
               <h1 class="sitename">VetClinic</h1>
           </a>


           <nav id="navmenu" class="navmenu">
               <ul>
                   <li><a href="http://10.48.74.39/Workshop 2/frontend/report.php" class="active">Dashboard</a></li>


                   <li class="dropdown"><a href="#"><span>Veterinarian</span> <i class="bi bi-chevron-down"></i></a>
                       <ul>
                           <li><a href="http://10.48.74.199:81/vetcli/frontend/vetregister.php">Register Vet</a></li>
                           <li><a href="http://10.48.74.199:81/vetcli/frontend/vet_avail.php">Add Availability Vet</a></li>
                           <li><a href="http://10.48.74.199:81/vetcli/frontend/vetlist.php">List Vet</a></li>
                       </ul>
                   </li>


                   <li class="dropdown"><a href="#"><span>Medicine</span> <i class="bi bi-chevron-down"></i></a>
                       <ul>
                           <li><a href="../frontend/medicinedetails.php">Add Medicine</a></li>
                           <li><a href="../frontend/.php">Stock Medicine</a></li>
                       </ul>
                   </li>


                   <li class="dropdown"><a href="#"><span>Treatment</span> <i class="bi bi-chevron-down"></i></a>
                       <ul>
                           <li><a href="../frontend/.php">View Treatment</a></li>
                       </ul>
                   </li>


                    <li><a href="../frontend/services.php">Service</a></li>


                   <li class="dropdown"><a href="#"><span>Appointment</span> <i class="bi bi-chevron-down"></i></a>
                       <ul>
                           <li><a href="../frontend/.php">View Appointment</a></li>
                       </ul>
                   </li>


                   <li class="dropdown"><a href="#"><span>Payment</span> <i class="bi bi-chevron-down"></i></a>
                       <ul>
                           <li><a href="http://10.48.74.197/test/frontend/paymenthistory.php">List Payment</a></li>
                       </ul>
                   </li>

                    <li><a href="http://10.48.74.199:81/vetcli/frontend/adminprofile.php">MyProfile</a></li>
               </ul>
               <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
           </nav>

           <a class="btn-getstarted" href="http://10.48.74.199:81/vetcli/backend/logout.php">Log out</a>
       </div>
   </header>


