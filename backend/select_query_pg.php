<?php
require_once "../backend/connection.php";

/* =========================
   POSTGRESQL SELECT QUERIES
========================= */

/* ---------- ADMIN ---------- */
function getAllAdminsPG() {
    $connPG = getPOSTGRES();
    if (!$connPG) return [];

    $stmt = $connPG->query("
        SELECT admin_id, admin_name, email
        FROM clinic_administrator
        ORDER BY admin_name
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAdminByIdPG($adminID) {
    $connPG = getPOSTGRES();
    if (!$connPG) return null;

    $stmt = $connPG->prepare("
        SELECT admin_id, admin_name, email
        FROM clinic_administrator
        WHERE admin_id = :id
    ");
    $stmt->execute([':id' => $adminID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ---------- VETERINARIAN ---------- */
function getAllVetsPG() {
    $connPG = getPOSTGRES();
    if (!$connPG) return [];

    $stmt = $connPG->query("
        SELECT vet_id, vet_name, specialization
        FROM veterinarian
        ORDER BY vet_name
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getVetByIdPG($vetID) {
    $connPG = getPOSTGRES();
    if (!$connPG) return null;

    $stmt = $connPG->prepare("
        SELECT vet_id, vet_name, specialization
        FROM veterinarian
        WHERE vet_id = :id
    ");
    $stmt->execute([':id' => $vetID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ---------- OWNER ---------- */
function getAllOwnersPG() {
    $connPG = getPOSTGRES();
    if (!$connPG) return [];

    $stmt = $connPG->query("
        SELECT owner_id, owner_name, phone_num
        FROM owner
        ORDER BY owner_name
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getOwnerByIdPG($ownerID) {
    $connPG = getPOSTGRES();
    if (!$connPG) return null;

    $stmt = $connPG->prepare("
        SELECT owner_id, owner_name, phone_num
        FROM owner
        WHERE owner_id = :id
    ");
    $stmt->execute([':id' => $ownerID]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
