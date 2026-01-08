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

/* ---------- UPDATE APPOINTMENT STATUS (ADDED FOR SYNC) ---------- */
function updateAppointmentStatusMaria($appointmentID, $treatmentStatus) {
    // 1. Connect to Aniq's Database (MariaDB)
    $conn = getMariaDBConnection(); 
    if (!$conn) return false; // Safety check

    try {
        // 2. Map Your Treatment Status -> His Appointment Status
        $newStatus = 'Pending'; // Default

        if ($treatmentStatus === 'Completed') {
            $newStatus = 'Done'; // or 'Completed', depending on what he wants
        } 
        elseif ($treatmentStatus === 'In Progress') {
            $newStatus = 'In Progress';
        }
        elseif ($treatmentStatus === 'Deceased') {
            $newStatus = 'Cancelled'; // Or keep as 'Done' if preferred
        }

        // 3. Run the SQL Update
        // This matches the columns in your screenshot: status, appointment_id
        $sql = "UPDATE appointment SET status = ? WHERE appointment_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$newStatus, $appointmentID]);
        
        return true;
    } catch (PDOException $e) {
        // STOP EVERYTHING and show me the error!
        die("SYNC ERROR: Could not connect to Aniq's DB (10.48.74.61). Reason: " . $e->getMessage());
    }
}


?>