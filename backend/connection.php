<?php

// =========================================================================
// 1. LOCAL DATABASE (MySQL) - Uses PDO (YOU - RUKAINI)
// =========================================================================
$mysql_host = 'localhost';
$mysql_db   = 'vet_clinic';
$mysql_user = 'rukaini';
$mysql_pass = '12345678';

try {
    $connMySQL = new PDO(
        "mysql:host=$mysql_host;dbname=$mysql_db;charset=utf8",
        $mysql_user,
        $mysql_pass
    );
    $connMySQL->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("MySQL Connection failed: " . $e->getMessage());
}


// =========================================================================
// 2. FRIEND 1: PostgreSQL (User Accounts) 
// =========================================================================
function getPOSTGRES(){
    $pg_host = '10.48.74.199';
    $pg_db   = 'postgres';
    $pg_user = 'postgres';
    $pg_pass = 'password';
    $connPG  = null;

    try {
        $connPG = new PDO(
            "pgsql:host=$pg_host;dbname=$pg_db",
            $pg_user,
            $pg_pass
        );
        $connPG->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        error_log("PostgreSQL Connection failed: " . $e->getMessage());
        return null;
    }
    return $connPG;
}


// =========================================================================
// 4. TYAA - MYSQL CONNECTION (ADDED)
// =========================================================================
function getMySQL_Tyaa() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=10.48.74.197;dbname=vet_clinic;charset=utf8",
                "tya",
                "1234",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            error_log("Tyaa MySQL connection failed: " . $e->getMessage());
            return null;
        }
    }

    return $conn;
}


// =========================================================================
// 5. MARIADB CONNECTION (AS REQUESTED)
// =========================================================================
function getMariaDBConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=10.48.74.61;port=3309;dbname=vet_clinic;charset=utf8",
                "root",
                "1234",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            die("MariaDB connection failed: " . $e->getMessage());
        }
    }

    return $conn;
}

// =========================================================================
// 3. FRIEND 3: Firebird (Appointments) - LAZY LOADING
// =========================================================================
function getFirebird() {
    $fb_host = '10.48.74.39';
    $fb_path = 'C:\\Program Files\\Firebird\\Firebird_5_0\\VETCLINIC1.FDB'; 
    $fb_user = 'SYSDBA';
    $fb_pass = '1234';

    try {
        $fb_dsn = "firebird:dbname={$fb_host}/3050:{$fb_path}";
        $connFB = new PDO($fb_dsn, $fb_user, $fb_pass);
        $connFB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $connFB;
    } catch(PDOException $e) {
        error_log("Firebird Connection failed: " . $e->getMessage());
        return null;
    }
}

?>
