<?php
session_start();

define('SSO_SECRET', 'VETCLINIC_SSO_2026_SECRET');

// 🔥 IMPORTANT: override old session if token is present
if (isset($_GET['token'])) {
    session_unset();
}

// If already logged in AND no token → allow
if (
    !isset($_GET['token']) &&
    (isset($_SESSION['ownerID']) || isset($_SESSION['vetID']) || isset($_SESSION['adminID']))
) {
    return;
}

// Token required for first entry
if (!isset($_GET['token'])) {
    die("Unauthorized");
}

$token = $_GET['token'];
$parts = explode('.', $token);
if (count($parts) !== 2) die("Invalid token");

[$payload_b64, $signature] = $parts;

$expected_sig = hash_hmac('sha256', $payload_b64, SSO_SECRET);
if (!hash_equals($expected_sig, $signature)) die("Invalid token");

$payload = json_decode(base64_decode($payload_b64), true);
if (!$payload || $payload['exp'] < time()) die("Token expired");

// Create new session from token
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

// Clean URL
header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
exit;