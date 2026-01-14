<?php
// Backend Treatment Controller
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/select_query_pg.php';
require_once __DIR__ . '/select_query_maria.php';

// =========================================================================
// DATABASE CONNECTIONS
// =========================================================================
$conn = getMariaDBConnection();
$connPG = getPOSTGRES();
global $connMySQL; // Use Local MySQL for Treatments

if (isset($_GET['action']) && $_GET['action'] === 'complete_and_pay' && isset($_GET['treatment_id'])) {
    $tID = $_GET['treatment_id'];
    
    // 1. Update Treatment Status to 'Completed' in MySQL
    $stmt = $connMySQL->prepare("UPDATE TREATMENT SET treatment_status = 'Completed' WHERE treatment_id = ?");
    $stmt->execute([$tID]);
    
    // 2. Sync Status to MariaDB (if needed)
    if (isset($_GET['appointment_id']) && function_exists('updateAppointmentStatusMaria')) {
         updateAppointmentStatusMaria($_GET['appointment_id'], 'Completed');
    }

    // 3. Automatically Redirect to Payment Page
    $paymentURL = "http://10.48.74.197/vetclinic/backend/paymentinsert_controller.php";
    $params = [
        'treatment_id' => $tID,
        'vet_id'       => $_GET['vet_id'] ?? '',
        'owner_id'     => $_GET['owner_id'] ?? '',
        'token'        => $_GET['token'] ?? ''
    ];
    
    header("Location: " . $paymentURL . "?" . http_build_query($params));
    exit();
}
// Authentication
// Authentication
if (isset($_GET['vet_id'])) $_SESSION['vetID'] = trim($_GET['vet_id']);
if (isset($_GET['vet_name'])) $_SESSION['vetname'] = urldecode(trim($_GET['vet_name']));

// FIX: Allow both Admin and Vet
if (isset($_SESSION['vetID'])) {
    $vetID = $_SESSION['vetID'];
} elseif (isset($_SESSION['adminID'])) {
    // If admin is logged in, use adminID or handle accordingly
    $vetID = $_SESSION['adminID']; 
} else {
    die("Unauthorized access.");
}
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

       $treatmentID = getNextTreatmentID($conn);
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

               try {
                   $stmtHist = $conn->prepare("INSERT INTO PET_HISTORY (pet_id, owner_id, event_type, description) VALUES (?, ?, ?, ?)");
                   $stmtHist->execute([$petID_Log, $ownerID_Log, 'Deceased', "Deceased in treatment $treatmentID"]);
               } catch (Exception $ex) { /* Ignore */ }
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

       // Follow-Up Logic
$followUpCreated = null; // null means no attempt, true=success, false=fail

       if (!empty($postData['followUpDate']) && !empty($postData['followUpTime'])) {
           if (function_exists('createFollowUpAppointment')) {
               $f_ownerID = $postData['ownerID'] ?? ''; 
               $f_petID   = $postData['petID'] ?? '';
               $f_serviceID = $postData['followUpService'] ?? 'SV001'; 
               
               // Capture the result
               $newApptID = createFollowUpAppointment($f_ownerID, $f_petID, $vetID, $postData['followUpDate'], $postData['followUpTime'], $f_serviceID);
               
               $followUpCreated = ($newApptID !== false);
           }
       }

       // Sync Status Logic
       if (function_exists('updateAppointmentStatusMaria')) {
            updateAppointmentStatusMaria($appointmentID, $treatmentStatus); 
       }

       // Return success AND the follow-up status
       return ['success' => true, 'treatmentID' => $treatmentID, 'followUpStatus' => $followUpCreated];

   } catch (Exception $e) {
       if ($conn->inTransaction()) $conn->rollBack();
       return ['success' => false, 'error' => $e->getMessage()];
   }
}

/**
 * Get List (UPDATED WITH SEARCH & CORRECT SORTING)
 */
function getTreatmentsList($conn, $search,  $sort_by, $limit, $page) {
   try {
       $limit = (int)$limit;
       $page = (int)$page;
       $offset = ($page - 1) * $limit;
      
       // --- 1. DEFINE SORTING LOGIC ---
       // This ensures 'T001', 'T002' sorts correctly by number, not just string
       $order_clause = match ($sort_by) {
           'id_asc'   => 'CAST(SUBSTRING(t.treatment_id, 2) AS UNSIGNED) ASC',
           'id_desc'  => 'CAST(SUBSTRING(t.treatment_id, 2) AS UNSIGNED) DESC',
           'date_asc' => 't.treatment_date ASC',
           default    => 't.treatment_date DESC',
       };

       // --- 2. BUILD SEARCH QUERY ---
       $where_sql = "";
       $params = [];
       
       if (!empty($search)) {
           $where_sql = "WHERE t.treatment_id LIKE ? OR t.diagnosis LIKE ? OR t.treatment_description LIKE ?";
           $term = "%" . $search . "%";
           $params = [$term, $term, $term];
       }

       // --- 3. COUNT TOTAL ROWS (Filtered) ---
       $count_sql = "SELECT COUNT(*) FROM TREATMENT t $where_sql";
       $count_stmt = $conn->prepare($count_sql);
       $count_stmt->execute($params);
       $total_rows = $count_stmt->fetchColumn();
       
       $total_pages = $total_rows > 0 ? ceil($total_rows / $limit) : 1;

       // --- 4. FETCH DATA (Filtered & Sorted) ---
       $sql = "SELECT t.treatment_id, t.treatment_date, t.treatment_description, 
                      t.treatment_status, t.diagnosis, t.treatment_fee, t.vet_id
               FROM TREATMENT t
               $where_sql
               ORDER BY $order_clause 
               LIMIT $limit OFFSET $offset"; 
               // Note: Binding LIMIT/OFFSET in PDO can be tricky with some drivers when params exist, 
               // so injecting integers directly is safe here since we cast them to (int) above.
       
       $stmt = $conn->prepare($sql);
       $stmt->execute($params);
      
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
$search = isset($_GET['search']) ? trim($_GET['search']) : ''; // Capture Search Param
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;
if ($page < 1) $page = 1;

if (isset($_GET['success']) && $_GET['success'] === 'true') $insert_success = true;

// POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   $result = processTreatmentForm($connMySQL, $_POST, $vetID, $appointmentID);
  
   if ($result['success']) {
       $url_params = [
           'success' => 'true', 
           'sort' => $sort_by, 
           'appointment_id' => $appointmentID, 
           'vet_id' => $vetID, 
           'treatment_id' => $result['treatmentID'],
           'token' => $_SESSION['sso_token'] ?? '' 
       ];

       // CHECK FOLLOW UP STATUS
       if (isset($result['followUpStatus']) && $result['followUpStatus'] === false) {
           $url_params['warning'] = 'followup_failed';
       }

       if (isset($_SESSION['vetName'])) $url_params['vetname'] = urlencode($_SESSION['vetName']);
       header("Location: " . $_SERVER['PHP_SELF'] . '?' . http_build_query($url_params));
       exit();
   } else {
       $insert_error = $result['error'];
   }
}

// GET (Fetch List with Search & Sort)
try {
    $nextTreatmentID     = getNextTreatmentID($connMySQL);
    $medicine_options    = getMedicines($connMySQL);
    
    // Pass the $search variable to the function
    $treatment_list_data = getTreatmentsList($connMySQL, $search, $sort_by, $limit, $page);

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