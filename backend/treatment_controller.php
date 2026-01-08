<?php
// Backend Treatment Controller
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/select_query_pg.php';
//require_once __DIR__ . '/select_query_mysqli.php';
require_once __DIR__ . '/select_query_maria.php';

// =========================================================================
// DATABASE CONNECTIONS
// =========================================================================
$conn = getMariaDBConnection();
$connPG = getPOSTGRES();
global $connMySQL; // Use Local MySQL for Treatments

// Authentication
if (isset($_GET['vet_id'])) $_SESSION['vetID'] = trim($_GET['vet_id']);
if (isset($_GET['vet_name'])) $_SESSION['vetname'] = urldecode(trim($_GET['vet_name']));

if (!isset($_SESSION['vetID'])) die("Unauthorized access.");

$vetID = $_SESSION['vetID'];
$appointmentID = isset($_GET['appointment_id']) ? trim($_GET['appointment_id']) : '';
$vetName = $_SESSION['vetname'] ?? $vetID;

/**
 * Get Medicines (Local MySQL)
 */
function getMedicines($conn) {
   try {
       $stmt = $conn->prepare("SELECT medicine_id, medicine_name, unit_price, stock_quantity FROM MEDICINE WHERE stock_quantity > 0 ORDER BY medicine_name ASC");
       $stmt->execute();
       $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
       
       if (empty($result)) {
           $stmt = $conn->prepare("SELECT medicine_id, medicine_name, unit_price, stock_quantity FROM MEDICINE ORDER BY medicine_name ASC");
           $stmt->execute();
           $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
       }
       return $result;
   } catch (PDOException $e) {
       return [];
   }
}

/**
 * Get Next ID (Local MySQL)
 */
function getNextTreatmentID($conn) {
   try {
       $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(treatment_id, 2) AS UNSIGNED)) FROM TREATMENT");
       $max = $stmt->fetchColumn();
       $num = $max ? $max + 1 : 1;
       return 'T' . str_pad($num, 3, '0', STR_PAD_LEFT);
   } catch (PDOException $e) {
       return "T001";
   }
}

/**
 * Process Form
 */
function processTreatmentForm($conn, $postData, $vetID, $appointmentID) {
   try {
       if (empty($appointmentID)) throw new Exception("No Appointment Selected.");
       
       $conn->beginTransaction();

       $treatmentID = trim($postData['treatmentID']);
       $baseFee = (float)($postData['treatmentFee'] ?? 0.00);
       $treatmentDate = $postData['treatmentDate'];
       $treatmentDescription = trim($postData['treatmentDescription']);
       $treatmentStatus = $postData['treatmentStatus'];
       $diagnosis = trim($postData['diagnosis'] ?? '');
      
       // Pet History (MariaDB check)
       if ($treatmentStatus === 'Deceased') {
           if (function_exists('getAppointmentByIdMaria')) {
               $apptData = getAppointmentByIdMaria($appointmentID);
               $petID_Log = $postData['petID'] ?? $apptData['pet_id'] ?? 'Unknown';
               $ownerID_Log = $apptData['owner_id'] ?? 'Unknown';

               // Try to log history locally
               try {
                   $stmtHist = $conn->prepare("INSERT INTO PET_HISTORY (pet_id, owner_id, event_type, description) VALUES (?, ?, ?, ?)");
                   $stmtHist->execute([$petID_Log, $ownerID_Log, 'Deceased', "Deceased in treatment $treatmentID"]);
               } catch (Exception $ex) { /* Ignore if table missing */ }
           }
       }

       // Insert Treatment
       $stmt = $conn->prepare("INSERT INTO TREATMENT (treatment_id, treatment_date, treatment_description, treatment_status, diagnosis, treatment_fee, vet_id, appointment_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
       $stmt->execute([$treatmentID, $treatmentDate, $treatmentDescription, $treatmentStatus, $diagnosis, $baseFee, $vetID, $appointmentID]);

       // Process Medicines
       $total_medicine_cost = 0.00;
       $medicine_ids = $postData['medicineID'] ?? [];
       $quantities   = $postData['quantityUsed'] ?? [];
       $dosages      = $postData['dosage'] ?? [];
       $instructions = $postData['instruction'] ?? [];

       if (!empty($medicine_ids)) {
           $stmt_details = $conn->prepare("INSERT INTO MEDICINE_DETAILS (quantity_used, dosage, instruction, medicine_cost, treatment_id, medicine_id) VALUES (?, ?, ?, ?, ?, ?)");
           $stmt_stock   = $conn->prepare("UPDATE MEDICINE SET stock_quantity = stock_quantity - ? WHERE medicine_id = ?");
           $stmt_price   = $conn->prepare("SELECT unit_price, stock_quantity FROM MEDICINE WHERE medicine_id = ?");

           foreach ($medicine_ids as $key => $medID) {
               $medID = trim($medID);
               if (empty($medID)) continue;
               
               $qty = (int)($quantities[$key] ?? 0);
               $dosage = trim($dosages[$key] ?? '');
               $instruction = trim($instructions[$key] ?? '');
               
               $stmt_price->execute([$medID]);
               $price_row = $stmt_price->fetch(PDO::FETCH_ASSOC);

               if (!$price_row) throw new Exception("Medicine ID {$medID} not found.");
               if ($qty > $price_row['stock_quantity']) throw new Exception("Insufficient stock for {$medID}.");

               $cost = (float)$price_row['unit_price'] * $qty;
               $total_medicine_cost += $cost;

               $stmt_details->execute([$qty, $dosage, $instruction, $cost, $treatmentID, $medID]);
               $stmt_stock->execute([$qty, $medID]);
           }
       }

       if ($total_medicine_cost > 0) {
           $finalFee = $baseFee + $total_medicine_cost;
           $conn->prepare("UPDATE TREATMENT SET treatment_fee = ? WHERE treatment_id = ?")->execute([$finalFee, $treatmentID]);
       }

       $conn->commit();

       // --- START: FOLLOW-UP VACCINATION LOGIC ---
       if (!empty($postData['followUpDate']) && !empty($postData['followUpTime'])) {
           if (function_exists('createFollowUpAppointment')) {
               // Retrieve IDs needed for appointment
               $f_ownerID = $postData['ownerID'] ?? ''; 
               $f_petID   = $postData['petID'] ?? '';
               
               // Create the appointment in MariaDB
               createFollowUpAppointment($f_ownerID, $f_petID, $vetID, $postData['followUpDate'], $postData['followUpTime']);
           }
       }
       // --- END: FOLLOW-UP VACCINATION LOGIC ---

       // --- TAMBAHAN: SYNC KE APPOINTMENT (REQ BY ASYIQIN) ---
      if (function_exists('updateAppointmentStatusMaria')) {
        updateAppointmentStatusMaria($appointmentID, $treatmentStatus); 
    }

       return ['success' => true];

   } catch (Exception $e) {
       if ($conn->inTransaction()) $conn->rollBack();
       return ['success' => false, 'error' => $e->getMessage()];
   }
}

/**
 * Get List
 */
function getTreatmentsList($conn, $appointmentID, $sort_by, $limit, $page) {
   try {
       $limit = (int)$limit;
       $page = (int)$page;
       $offset = ($page - 1) * $limit;
      
       $order_clause = match ($sort_by) {
           'id_asc'   => 'CAST(SUBSTRING(t.treatment_id, 2) AS UNSIGNED) ASC',
           'id_desc'  => 'CAST(SUBSTRING(t.treatment_id, 2) AS UNSIGNED) DESC',
           'date_asc' => 't.treatment_date ASC',
           default    => 't.treatment_date DESC',
       };

       // No WHERE clause = Select ALL treatments
       $count_sql = "SELECT COUNT(*) FROM TREATMENT t";
       $count_stmt = $conn->prepare($count_sql);
       $count_stmt->execute();
       $total_rows = $count_stmt->fetchColumn();
       
       $total_pages = $total_rows > 0 ? ceil($total_rows / $limit) : 1;

       $sql = "SELECT t.treatment_id, t.treatment_date, t.treatment_description, 
                      t.treatment_status, t.diagnosis, t.treatment_fee, t.vet_id
               FROM TREATMENT t
               ORDER BY $order_clause 
               LIMIT ? OFFSET ?";
       
       $stmt = $conn->prepare($sql);
       
       $stmt->bindValue(1, $limit, PDO::PARAM_INT);
       $stmt->bindValue(2, $offset, PDO::PARAM_INT);
       $stmt->execute();
      
       return [
           'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
           'total_rows' => $total_rows,
           'total_pages' => $total_pages,
           'current_page' => $page
       ];
       
   } catch (PDOException $e) {
       return ['data' => [], 'total_rows' => 0, 'total_pages' => 1, 'current_page' => 1];
   }
}

// =========================================================================
// MAIN EXECUTION
// =========================================================================
$insert_success = false;
$insert_error = "";
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
if ($page < 1) $page = 1;

if (isset($_GET['success']) && $_GET['success'] === 'true') $insert_success = true;

// POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   // Use Local MySQL for saving
   $result = processTreatmentForm($connMySQL, $_POST, $vetID, $appointmentID);
  
   if ($result['success']) {
       $url_params = [
           'success' => 'true', 'sort' => $sort_by, 'appointment_id' => $appointmentID, 
           'vet_id' => $vetID, 'treatment_id' => $_POST['treatmentID']
       ];
       if (isset($_SESSION['vetName'])) $url_params['vetname'] = urlencode($_SESSION['vetName']);
       header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($url_params));
       exit();
   } else {
       $insert_error = $result['error'];
   }
}

// GET
try {
    // Use Local MySQL for fetching
    $nextTreatmentID     = getNextTreatmentID($connMySQL);
    $medicine_options    = getMedicines($connMySQL);
    
    // This will now return ALL treatments because we removed the WHERE clause
    $treatment_list_data = getTreatmentsList($connMySQL, $appointmentID, $sort_by, $limit, $page);

    $treatments   = $treatment_list_data['data'];
    $total_rows   = $treatment_list_data['total_rows'];
    $total_pages  = $treatment_list_data['total_pages'];
    $page         = $treatment_list_data['current_page'];
    $has_records  = count($treatments) > 0;
    
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    $nextTreatmentID = "T001";
    $medicine_options = []; $treatments = [];
    $total_rows = 0; $has_records = false;
    $insert_error = "Error: " . $e->getMessage();
}
?>