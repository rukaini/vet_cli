<?php
// test_connection.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Tester</h1>";
echo "<p>Running diagnostics on your connections...</p>";

// ---------------------------------------------------------
// 1. TEST POSTGRESQL (The one causing "PG Connect Fail")
// ---------------------------------------------------------
echo "<h2>1. Testing PostgreSQL (Friend 1: User Accounts)</h2>";

// --- CHECK DRIVER FIRST ---
if (!in_array('pgsql', PDO::getAvailableDrivers())) {
    echo "<p style='color:red; font-weight:bold;'>CRITICAL ERROR: 'pgsql' driver is NOT enabled.</p>";
    echo "<p>You need to open <code>php.ini</code>, find <code>;extension=pdo_pgsql</code>, remove the semicolon <code>;</code>, and restart Apache.</p>";
} else {
    echo "<p style='color:green;'>✓ Driver 'pdo_pgsql' is enabled.</p>";
    
    // --- CREDENTIALS (VERIFY THESE WITH FRIEND 1) ---
    $pg_host = '10.48.74.199';
    $pg_db   = 'postgres';      // Ask friend: Is it 'postgres' or 'vet_clinic'?
    $pg_user = 'postgres';      // Ask friend: Is it 'postgres' or something else?
    $pg_pass = 'password';      // Ask friend: Is it really 'password'? (Try '1234' or 'admin')

    try {
        $dsn = "pgsql:host=$pg_host;dbname=$pg_db";
        // 3-second timeout so it doesn't hang
        $options = [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        
        $connPG = new PDO($dsn, $pg_user, $pg_pass, $options);
        echo "<p style='color:green; font-weight:bold;'>✓ CONNECTION SUCCESSFUL!</p>";
        
        // Test fetching a table name to be sure
        $stmt = $connPG->query("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname='public' LIMIT 1");
        $table = $stmt->fetchColumn();
        echo "<p>Test Query Result: Found table '<strong>$table</strong>'</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color:red; font-weight:bold;'>X CONNECTION FAILED</p>";
        echo "<div style='background:#fee; border:1px solid red; padding:10px;'>";
        echo "<strong>Error Message:</strong> " . $e->getMessage() . "<br><br>";
        
        $msg = $e->getMessage();
        if (strpos($msg, '08006') !== false || strpos($msg, 'timeout') !== false) {
            echo "<strong>Diagnosis:</strong> The Server IP ($pg_host) is unreachable. Check VPN/WiFi/Firewall.";
        } elseif (strpos($msg, 'password authentication failed') !== false) {
            echo "<strong>Diagnosis:</strong> Wrong Password or Username.";
        } elseif (strpos($msg, 'does not exist') !== false) {
            echo "<strong>Diagnosis:</strong> The database name '$pg_db' is wrong.";
        }
        echo "</div>";
    }
}

// ---------------------------------------------------------
// 2. TEST MARIADB (Friend 2: Appointments)
// ---------------------------------------------------------
echo "<hr><h2>2. Testing MariaDB (Friend 2: Appointments)</h2>";
$ma_host = '10.48.74.61';
$ma_port = '3309'; 
$ma_db   = 'vet_clinic';
$ma_user = 'root';
$ma_pass = '1234';

try {
    $dsn = "mysql:host=$ma_host;port=$ma_port;dbname=$ma_db;charset=utf8";
    $connMa = new PDO($dsn, $ma_user, $ma_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]);
    echo "<p style='color:green; font-weight:bold;'>✓ CONNECTION SUCCESSFUL!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>X FAILED: " . $e->getMessage() . "</p>";
}

// ---------------------------------------------------------
// 3. TEST LOCAL MYSQL (Your DB)
// ---------------------------------------------------------
echo "<hr><h2>3. Testing Local MySQL (Treatments)</h2>";
$my_host = 'localhost';
$my_db   = 'vet_clinic';
$my_user = 'rukaini';
$my_pass = '12345678';

try {
    $dsn = "mysql:host=$my_host;dbname=$my_db;charset=utf8";
    $connMy = new PDO($dsn, $my_user, $my_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "<p style='color:green; font-weight:bold;'>✓ CONNECTION SUCCESSFUL!</p>";
} catch (PDOException $e) {
    echo "<p style='color:red;'>X FAILED: " . $e->getMessage() . "</p>";
}
?>