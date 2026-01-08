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

// UPDATED: Handle new status mappings from Treatment to Appointment
function updateAppointmentStatusMaria($appointmentID, $treatmentStatus) {
    $conn = getMariaDBConnection(); 
    if (!$conn) return false;

    try {
        // Map Treatment Status (PostgreSQL form) to Appointment Status (MariaDB Enum)
        $newApptStatus = 'Pending'; // Default fallback

        switch ($treatmentStatus) {
            case 'Confirmed':
                $newApptStatus = 'Confirmed';
                break;
            case 'In Progress':
                // UPDATED: Now directly sets status to 'In Progress' instead of 'Confirmed'
                $newApptStatus = 'In Progress'; 
                break;
            case 'Completed':
                $newApptStatus = 'Completed';
                break;
            case 'Cancelled':
                $newApptStatus = 'Cancelled';
                break;
            case 'Deceased':
                // If the pet died during treatment, the appointment outcome is effectively cancelled/ended
                $newApptStatus = 'Cancelled'; 
                break;
            case 'Pending':
            default:
                $newApptStatus = 'Pending';
                break;
        }

        $sql = "UPDATE appointment SET status = ? WHERE appointment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$newApptStatus, $appointmentID]);
        
        return true;

    } catch (PDOException $e) {
        error_log("MariaDB Appointment Status Update Error: " . $e->getMessage());
        return false;
    }
}

/* =========================
   INSERT FOLLOW-UP (ID FORMAT: A00036)
========================= */

function getNextAppointmentIDMaria() {
    $conn = getMariaDBConnection();
    try {
        // Find the highest ID (e.g., A00035)
        $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(appointment_id, 2) AS UNSIGNED)) FROM appointment");
        $max = $stmt->fetchColumn();
        $num = $max ? $max + 1 : 1;
        
        // Padding to 5 digits (e.g., A00036)
        return 'A' . str_pad($num, 5, '0', STR_PAD_LEFT);
        
    } catch (PDOException $e) {
        return "A00001"; // Fallback
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

/* =========================
   CHECK AVAILABILITY
========================= */
function getBookedTimesMaria($date, $vetID) {
    $conn = getMariaDBConnection();
    // Fetch times where status is NOT Cancelled or Rejected
    $sql = "SELECT time FROM appointment 
            WHERE date = ? 
            AND vet_id = ? 
            AND status NOT IN ('Cancelled', 'Deceased', 'Rejected')";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([$date, $vetID]);
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>