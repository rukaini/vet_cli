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
    // 1. Get the Postgres Connection
    $connPG = getPOSTGRES();
    if (!$connPG) return null;

    // 2. The SELECT Statement
    // We specifically select 'vet_name' so you can call it later
    $stmt = $connPG->prepare("
        SELECT vet_name
        FROM veterinarian
        WHERE vet_id = :id
    ");
    
    // 3. Execute with the ID
    $stmt->execute([':id' => $vetID]);
    
    // 4. Return the result (as an array)
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

/* ---------- OWNER NAME ONLY ---------- */
function getOwnerNameByIdPG($ownerID) {
    $connPG = getPOSTGRES();
    if (!$connPG) return null;

    $stmt = $connPG->prepare("
        SELECT owner_name
        FROM owner
        WHERE owner_id = :id
    ");
    $stmt->execute([':id' => $ownerID]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}


/* ---------- PET ---------- */
function getPetByIdPG($petID) {
    $connPG = getPOSTGRES();
    if (!$connPG) return null;

    $stmt = $connPG->prepare("
        SELECT pet_id, pet_name
        FROM pet
        WHERE pet_id = :id
    ");
    $stmt->execute([':id' => $petID]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}