<?php
/**
 * SSO Token Verification
 * ----------------------
 * Used by ALL friend servers (Aniq, Rukai, Tya, Nureen)
 * This file ONLY verifies token issued by iqin's server
 */

define('SSO_SECRET', 'VETCLINIC_SSO_2026_SECRET');

function verifySSOToken($token) {

    // 1. Token must exist and be in correct format
    if (!$token || !str_contains($token, '.')) {
        return false;
    }

    // 2. Split token
    [$payload_b64, $signature] = explode('.', $token, 2);

    // 3. Verify signature
    $expected_signature = hash_hmac('sha256', $payload_b64, SSO_SECRET);
    if (!hash_equals($expected_signature, $signature)) {
        return false;
    }

    // 4. Decode payload
    $payload = json_decode(base64_decode($payload_b64), true);
    if (!$payload) {
        return false;
    }

    // 5. Check expiry
    if (!isset($payload['exp']) || $payload['exp'] < time()) {
        return false;
    }

    // 6. Token is valid → return user data
    return $payload;
}
