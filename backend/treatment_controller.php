<?php
// Backend Treatment Controller
// This file handles all the business logic for treatment management

// Database connections (load before security check)
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/select_query_pg.php';
require_once __DIR__ . '/select_query_mysqli.php';
// Needed for Pet Info
require_once __DIR__ . '/select_query_maria.php';

// Get database connection
$conn = getMariaDBConnection();
$connPG = getPOSTGRES();

// Handle vet authentication from GET parameters
if (isset($_GET['vet_id'])) {
    $_SESSION['vetID'] = trim($_GET['vet_id']);
}

if (isset($_GET['vetname'])) {
    $_SESSION['vetName'] = urldecode(trim($_GET['vetname']));
}

// Security check - must have vetID in session
if (!isset($_SESSION['vetID'])) {
    die("Unauthorized access. Please log in as veterinarian.");
}

// Get vet and appointment info from session/GET
$vetID = $_SESSION['vetID'];
$appointmentID = isset($_GET['appointment_id']) ? trim($_GET['appointment_id']) : ($_SESSION['appointmentID'] ?? '');
$vetName = $_SESSION['vetName'] ?? $vetID;

// Store appointmentID in session for consistency
if (!empty($appointmentID)) {
    $_SESSION['appointmentID'] = $appointmentID;
}

/**
 * Get all available medicines from database
 */
function getMedicines($conn) {
   try {
       // SQL UPDATED: medicine_id, medicine_name, unit_price, stock_quantity
       // First try with stock filter
       $stmt = $conn->prepare("SELECT medicine_id, medicine_name, unit_price, stock_quantity FROM MEDICINE WHERE stock_quantity > 0 ORDER BY medicine_name ASC");
       $stmt->execute();
       $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
       
       // If no medicines with stock, get all medicines
       if (empty($result)) {
           error_log("No medicines with stock found, fetching all medicines");
           $stmt = $conn->prepare("SELECT medicine_id, medicine_name, unit_price, stock_quantity FROM MEDICINE ORDER BY medicine_name ASC");
           $stmt->execute();
           $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
       }
       
       return $result;
   } catch (PDOException $e) {
       error_log("Error fetching medicines: " . $e->getMessage());
       return [];
   }
}

/**
 * Generate next treatment ID
 */
function getNextTreatmentID($conn) {
   // Check if we have a pre-generated ID in session
   if (isset($_SESSION['nextTreatmentID'])) {
       $id = $_SESSION['nextTreatmentID'];
       unset($_SESSION['nextTreatmentID']);
       return $id;
   }
  
   try {
       // SQL UPDATED: treatment_id
       // Get the highest treatment ID and increment
       $sql = "SELECT treatment_id FROM TREATMENT ORDER BY CAST(SUBSTRING(treatment_id, 2) AS UNSIGNED) DESC LIMIT 1";
       $stmt = $conn->query($sql);
       $row = $stmt->fetch(PDO::FETCH_ASSOC);

       if ($row && !empty($row['treatment_id'])) {
           // Extract number from ID (e.g., "T005" -> 5)
           $number = intval(substr($row['treatment_id'], 1));
           $nextNumber = $number + 1;
           return "T" . str_pad($nextNumber, 3, "0", STR_PAD_LEFT);
       }
       
       // No records found, start with T001
       return "T001";
   } catch (PDOException $e) {
       error_log("Error generating treatment ID: " . $e->getMessage());
       return "T001";
   }
}

/**
 * Process treatment form submission
 */
function processTreatmentForm($conn, $postData, $vetID, $appointmentID) {
   try {
       // Validate required data
       if (empty($appointmentID)) {
           throw new Exception("No Appointment Selected.");
       }
       
       if (empty($postData['treatmentID']) || empty($postData['treatmentDate'])) {
           throw new Exception("Missing required fields.");
       }
      
       $conn->beginTransaction();

       $treatmentID = trim($postData['treatmentID']);
       $baseFee = (float)($postData['treatmentFee'] ?? 0.00);
       $treatmentDate = $postData['treatmentDate'];
       $treatmentDescription = trim($postData['treatmentDescription']);
       $treatmentStatus = $postData['treatmentStatus'];
       $diagnosis = trim($postData['diagnosis'] ?? '');
      
       // --- FRIEND REQUEST: PET HISTORY LOGIC ---
       if ($treatmentStatus === 'Deceased') {
           // We need pet_id from MariaDB to log this
           $apptData = getAppointmentByIdMaria($appointmentID);
           $petID_Log = $postData['petID'] ?? $apptData['pet_id'] ?? 'Unknown';
           $ownerID_Log = $apptData['owner_id'] ?? 'Unknown';

           // Insert into PET_HISTORY (using snake_case)
           $stmtHist = $conn->prepare("INSERT INTO PET_HISTORY (pet_id, owner_id, event_type, description) VALUES (?, ?, ?, ?)");
           $stmtHist->execute([
               $petID_Log, 
               $ownerID_Log, 
               'Deceased', 
               "Pet marked deceased during treatment $treatmentID. Diagnosis: $diagnosis"
           ]);
       }

       // A. Insert Treatment Record
       // SQL UPDATED: treatment_id, treatment_date, etc.
       $sql_insert = "INSERT INTO TREATMENT 
           (treatment_id, treatment_date, treatment_description, treatment_status, diagnosis, treatment_fee, vet_id, appointment_id)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
       
       $stmt = $conn->prepare($sql_insert);
       $stmt->execute([
           $treatmentID, 
           $treatmentDate, 
           $treatmentDescription,
           $treatmentStatus, 
           $diagnosis, 
           $baseFee, 
           $vetID, 
           $appointmentID
       ]);

       // B. Process Medicines (if any)
       $total_medicine_cost = 0.00;
       $medicine_ids = $postData['medicineID'] ?? [];
       $quantities   = $postData['quantityUsed'] ?? [];
       $dosages      = $postData['dosage'] ?? [];
       $instructions = $postData['instruction'] ?? [];

       // Check if there are medicines to process
       if (!empty($medicine_ids) && is_array($medicine_ids)) {
           // SQL UPDATED: quantity_used, medicine_cost, treatment_id, medicine_id
           $stmt_details = $conn->prepare("INSERT INTO MEDICINE_DETAILS (quantity_used, dosage, instruction, medicine_cost, treatment_id, medicine_id) VALUES (?, ?, ?, ?, ?, ?)");
           // SQL UPDATED: stock_quantity, medicine_id
           $stmt_stock   = $conn->prepare("UPDATE MEDICINE SET stock_quantity = stock_quantity - ? WHERE medicine_id = ?");
           // SQL UPDATED: unit_price, stock_quantity, medicine_id
           $stmt_price   = $conn->prepare("SELECT unit_price, stock_quantity FROM MEDICINE WHERE medicine_id = ?");

           foreach ($medicine_ids as $key => $medID) {
               $medID = trim($medID);
               
               // Skip empty medicine selections
               if (empty($medID)) continue;
               
               $qty = isset($quantities[$key]) ? (int)$quantities[$key] : 0;
               $dosage = isset($dosages[$key]) ? trim($dosages[$key]) : '';
               $instruction = isset($instructions[$key]) ? trim($instructions[$key]) : '';
               
               if ($qty <= 0) {
                   throw new Exception("Invalid quantity for medicine {$medID}");
               }
              
               // Get medicine price and check stock
               $stmt_price->execute([$medID]);
               $price_row = $stmt_price->fetch(PDO::FETCH_ASSOC);

               if (!$price_row) {
                   throw new Exception("Medicine ID {$medID} not found.");
               }
               
               // Array keys updated to snake_case from DB
               if ($qty > $price_row['stock_quantity']) {
                   throw new Exception("Insufficient stock for medicine {$medID}. Available: {$price_row['stock_quantity']}, Requested: {$qty}");
               }

               // Calculate cost
               $unitPrice = (float)$price_row['unit_price'];
               $cost = $unitPrice * $qty;
               $total_medicine_cost += $cost;

               // Insert medicine details
               $stmt_details->execute([
                   $qty, 
                   $dosage, 
                   $instruction, 
                   $cost, 
                   $treatmentID, 
                   $medID
               ]);
               
               // Update stock
               $stmt_stock->execute([$qty, $medID]);
           }
       }

       // C. Update Total Fee (Base Fee + Medicine Cost)
       if ($total_medicine_cost > 0) {
           $finalFee = $baseFee + $total_medicine_cost;
           // SQL UPDATED: treatment_fee, treatment_id
           $stmt_update = $conn->prepare("UPDATE TREATMENT SET treatment_fee = ? WHERE treatment_id = ?");
           $stmt_update->execute([$finalFee, $treatmentID]);
       }

       $conn->commit();
      
       // Generate Next ID for next form submission
       $number = intval(substr($treatmentID, 1)) + 1;
       $_SESSION['nextTreatmentID'] = "T" . str_pad($number, 3, "0", STR_PAD_LEFT);

       return ['success' => true];

   } catch (Exception $e) {
       if ($conn->inTransaction()) {
           $conn->rollBack();
       }
       error_log("Treatment processing error: " . $e->getMessage());
       return ['success' => false, 'error' => $e->getMessage()];
   }
}

/**
 * Get list of treatments with pagination and sorting
 */
function getTreatmentsList($conn, $appointmentID, $sort_by, $limit, $page) {
   // This function exists in select_query_mysqli.php which we updated.
   // If you call this specific function in this file, we update it too.
   try {
       $offset = ($page - 1) * $limit;
      
       // Determine sort order (SQL UPDATED: treatment_id, treatment_date)
       $order_clause = match ($sort_by) {
           'id_asc'   => 'CAST(SUBSTRING(t.treatment_id, 2) AS UNSIGNED) ASC',
           'id_desc'  => 'CAST(SUBSTRING(t.treatment_id, 2) AS UNSIGNED) DESC',
           'date_asc' => 't.treatment_date ASC, CAST(SUBSTRING(t.treatment_id, 2) AS UNSIGNED) ASC',
           default    => 't.treatment_date DESC, CAST(SUBSTRING(t.treatment_id, 2) AS UNSIGNED) DESC',
       };

       // Build WHERE clause for appointment filtering
       $where_clause = "";
       $params = [];
       
       if (!empty($appointmentID)) {
           // SQL UPDATED: appointment_id
           $where_clause = "WHERE t.appointment_id = ?";
           $params = [$appointmentID];
       }

       // Count total records
       $count_sql = "SELECT COUNT(*) FROM TREATMENT t $where_clause";
       $count_stmt = $conn->prepare($count_sql);
       $count_stmt->execute($params);
       $total_rows = $count_stmt->fetchColumn();
       
       $total_pages = $total_rows > 0 ? ceil($total_rows / $limit) : 1;

       // Get paginated data (SQL UPDATED: Select snake_case columns)
       $sql = "SELECT t.treatment_id, t.treatment_date, t.treatment_description, 
                      t.treatment_status, t.diagnosis, t.treatment_fee, t.vet_id
               FROM TREATMENT t
               $where_clause
               ORDER BY $order_clause 
               LIMIT ? OFFSET ?";
       
       $stmt = $conn->prepare($sql);
       
       // Bind parameters
       if (!empty($appointmentID)) {
           $stmt->execute(array_merge($params, [$limit, $offset]));
       } else {
           $stmt->execute([$limit, $offset]);
       }
      
       return [
           'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
           'total_rows' => $total_rows,
           'total_pages' => $total_pages,
           'current_page' => $page
       ];
       
   } catch (PDOException $e) {
       error_log("Error fetching treatments: " . $e->getMessage());
       return [
           'data' => [],
           'total_rows' => 0,
           'total_pages' => 1,
           'current_page' => 1
       ];
   }
}

// =========================================================================
// MAIN EXECUTION (Request Handling)
// =========================================================================

// Initialize variables
$insert_success = false;
$insert_error = "";
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 5;

// Ensure page is at least 1
if ($page < 1) $page = 1;

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] === 'true') {
    $insert_success = true;
}

// POST: Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   $result = processTreatmentForm($conn, $_POST, $vetID, $appointmentID);
  
   if ($result['success']) {
       // Redirect to prevent form resubmission
       $url_params = [
           'success' => 'true',
           'sort' => $sort_by,
           'appointment_id' => $appointmentID,
           'vet_id' => $vetID,
           'treatment_id' => $_POST['treatmentID'] // FRIEND REQUEST: Add ID for Tya
       ];
       
       if (isset($_SESSION['vetName']) && !empty($_SESSION['vetName'])) {
           $url_params['vetname'] = urlencode($_SESSION['vetName']);
       }
       
       $redirect_url = $_SERVER['PHP_SELF'] . '?' . http_build_query($url_params);
       header("Location: " . $redirect_url);
       exit();
   } else {
       $insert_error = $result['error'];
   }
}

// GET: Prepare Data for View
try {
    $nextTreatmentID     = getNextTreatmentID($conn);
    $medicine_options    = getMedicines($conn);
    $treatment_list_data = getTreatmentsList($conn, $appointmentID, $sort_by, $limit, $page);

    // Unpack for HTML view
    $treatments   = $treatment_list_data['data'];
    $total_rows   = $treatment_list_data['total_rows'];
    $total_pages  = $treatment_list_data['total_pages'];
    $page         = $treatment_list_data['current_page']; // Use the validated page number
    $has_records  = count($treatments) > 0;
    
} catch (Exception $e) {
    error_log("Error preparing view data: " . $e->getMessage());
    
    // Set safe defaults
    $nextTreatmentID = "T001";
    $medicine_options = [];
    $treatments = [];
    $total_rows = 0;
    $total_pages = 1;
    $has_records = false;
    $insert_error = "Error loading data: " . $e->getMessage();
}
?>