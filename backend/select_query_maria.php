<?php
require_once "../backend/connection.php";

/* =========================
   MARIADB - APPOINTMENT
========================= */

/* ---------- ALL APPOINTMENTS ---------- */
function getAllAppointmentsMaria() {
    $conn = getMariaDBConnection();

    $stmt = $conn->query("
        SELECT
            appointment_id,
            owner_id,
            pet_id,
            vet_id,
            service_id,
            date,
            time,
            status,
            created_at
        FROM appointment
        ORDER BY date DESC, time DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------- APPOINTMENTS BY OWNER ---------- */
function getAppointmentsByOwnerMaria($ownerID) {
    $conn = getMariaDBConnection();

    $stmt = $conn->prepare("
        SELECT
            appointment_id,
            pet_id,
            vet_id,
            service_id,
            date,
            time,
            status,
            created_at
        FROM appointment
        WHERE owner_id = ?
        ORDER BY date DESC, time DESC
    ");
    $stmt->execute([$ownerID]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------- APPOINTMENTS BY VET ---------- */
function getAppointmentsByVetMaria($vetID) {
    $conn = getMariaDBConnection();

    $stmt = $conn->prepare("
        SELECT
            appointment_id,
            owner_id,
            pet_id,
            service_id,
            date,
            time,
            status,
            created_at
        FROM appointment
        WHERE vet_id = ?
        ORDER BY date DESC, time DESC
    ");
    $stmt->execute([$vetID]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------- SINGLE APPOINTMENT ---------- */
function getAppointmentByIdMaria($appointmentID) {
    $conn = getMariaDBConnection();

    $stmt = $conn->prepare("
        SELECT
            appointment_id,
            owner_id,
            pet_id,
            vet_id,
            service_id,
            date,
            time,
            status,
            created_at
        FROM appointment
        WHERE appointment_id = ?
    ");
    $stmt->execute([$appointmentID]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateAppointmentStatusMaria($appointmentID, $treatmentStatus) {
    $conn = getMariaDBConnection(); 
    if (!$conn) return false;

    try {
        // --- MATCH ANIQ'S EXACT ENUM VALUES ---
        
        $newStatus = 'Pending'; // Default fallback

        if ($treatmentStatus === 'Completed') {
            $newStatus = 'Completed'; 
        } 
        elseif ($treatmentStatus === 'In Progress') {
            $newStatus = 'Confirmed'; 
        }
        elseif ($treatmentStatus === 'Deceased') {
            $newStatus = 'Cancelled'; 
        }

        $sql = "UPDATE appointment SET status = ? WHERE appointment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$newStatus, $appointmentID]);
        
        return true;

    } catch (PDOException $e) {
        error_log("Sync Error: " . $e->getMessage());
        return false;
    }
}

/* =========================
   INSERT FOLLOW-UP (ADDED)
========================= */

function getNextAppointmentIDMaria() {
    $conn = getMariaDBConnection();
    try {
        // Find the highest ID (e.g., A005)
        $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(appointment_id, 2) AS UNSIGNED)) FROM appointment");
        $max = $stmt->fetchColumn();
        $num = $max ? $max + 1 : 1;
        return 'A' . str_pad($num, 3, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        return "A999"; // Fallback
    }
}

function createFollowUpAppointment($ownerID, $petID, $vetID, $date, $time) {
    $conn = getMariaDBConnection();
    if (!$conn) return false;

    try {
        $newID = getNextAppointmentIDMaria();
        $serviceID = 'SV003'; // Hardcoded ID for 'Vaccination'
        $status = 'Pending';
        $createdAt = date('Y-m-d H:i:s');

        $sql = "INSERT INTO appointment (appointment_id, owner_id, pet_id, vet_id, service_id, date, time, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$newID, $ownerID, $petID, $vetID, $serviceID, $date, $time, $status, $createdAt]);

        return $newID;
    } catch (PDOException $e) {
        error_log("Follow-up Insert Error: " . $e->getMessage());
        return false;
    }
}

function getAllService() {
    $conn = getMariaDBConnection();
    $stmt = $conn->query("SELECT * FROM service");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}




?>