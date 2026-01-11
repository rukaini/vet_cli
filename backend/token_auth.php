<?php
session_start();

define('SSO_SECRET', 'VETCLINIC_SSO_2026_SECRET');

// 1. Override old session if a new token is present
if (isset($_GET['token'])) {
    session_unset();
}

// 2. If already logged in AND no token provided -> Allow access (Return to page)
if (
    !isset($_GET['token']) &&
    (isset($_SESSION['ownerID']) || isset($_SESSION['vetID']) || isset($_SESSION['adminID']))
) {
    return;
}

// 3. Token required if not logged in
if (!isset($_GET['token'])) {
    die("Unauthorized: Login Required");
}

$token = $_GET['token'];
$parts = explode('.', $token);
if (count($parts) !== 2) die("Invalid token format");

[$payload_b64, $signature] = $parts;

// 4. Verify Signature
$expected_sig = hash_hmac('sha256', $payload_b64, SSO_SECRET);
if (!hash_equals($expected_sig, $signature)) die("Invalid token signature");

// 5. Decode Payload & Check Expiry
$payload = json_decode(base64_decode($payload_b64), true);
if (!$payload || $payload['exp'] < time()) die("Token expired");

// 6. Create New Session
$id   = $payload['id'];
$name = $payload['name'];
$type = $payload['type'] ?? 'owner';

switch ($type) {
    case 'admin':
        $_SESSION['adminID']   = $id;
        $_SESSION['adminname'] = $name;
        break;
    case 'vet':
        $_SESSION['vetID']   = $id;
        $_SESSION['vetname'] = $name;
        break;
    default:
        $_SESSION['ownerID']   = $id;
        $_SESSION['ownername'] = $name;
        break;
}

// 7. Remove Token from URL (Clean Redirect)
header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
exit;
?>