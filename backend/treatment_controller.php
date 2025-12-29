<?php
// Backend Treatment Controller
// This file handles all the business logic for treatment management

// Database connections (load before security check)
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/select_query_pg.php';
require_once __DIR__ . '/select_query_mysqli.php';

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
       // First try with stock filter
       $stmt = $conn->prepare("SELECT medicineID, medicineName, unitPrice, stockQuantity FROM MEDICINE WHERE stockQuantity > 0 ORDER BY medicineName ASC");
       $stmt->execute();
       $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
       
       // If no medicines with stock, get all medicines
       if (empty($result)) {
           error_log("No medicines with stock found, fetching all medicines");
           $stmt = $conn->prepare("SELECT medicineID, medicineName, unitPrice, stockQuantity FROM MEDICINE ORDER BY medicineName ASC");
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
       // Get the highest treatment ID and increment
       $sql = "SELECT treatmentID FROM TREATMENT ORDER BY CAST(SUBSTRING(treatmentID, 2) AS UNSIGNED) DESC LIMIT 1";
       $stmt = $conn->query($sql);
       $row = $stmt->fetch(PDO::FETCH_ASSOC);

       if ($row && !empty($row['treatmentID'])) {
           // Extract number from ID (e.g., "T005" -> 5)
           $number = intval(substr($row['treatmentID'], 1));
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
      
       // A. Insert Treatment Record
       $sql_insert = "INSERT INTO TREATMENT 
           (treatmentID, treatmentDate, treatmentDescription, treatmentStatus, diagnosis, treatmentFee, vetID, appointmentID)
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
           $stmt_details = $conn->prepare("INSERT INTO MEDICINE_DETAILS (quantityUsed, dosage, instruction, medicineCost, treatmentID, medicineID) VALUES (?, ?, ?, ?, ?, ?)");
           $stmt_stock   = $conn->prepare("UPDATE MEDICINE SET stockQuantity = stockQuantity - ? WHERE medicineID = ?");
           $stmt_price   = $conn->prepare("SELECT unitPrice, stockQuantity FROM MEDICINE WHERE medicineID = ?");

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
               
               if ($qty > $price_row['stockQuantity']) {
                   throw new Exception("Insufficient stock for medicine {$medID}. Available: {$price_row['stockQuantity']}, Requested: {$qty}");
               }

               // Calculate cost
               $unitPrice = (float)$price_row['unitPrice'];
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
           $stmt_update = $conn->prepare("UPDATE TREATMENT SET treatmentFee = ? WHERE treatmentID = ?");
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
   try {
       $offset = ($page - 1) * $limit;
      
       // Determine sort order
       $order_clause = match ($sort_by) {
           'id_asc'   => 'CAST(SUBSTRING(t.treatmentID, 2) AS UNSIGNED) ASC',
           'id_desc'  => 'CAST(SUBSTRING(t.treatmentID, 2) AS UNSIGNED) DESC',
           'date_asc' => 't.treatmentDate ASC, CAST(SUBSTRING(t.treatmentID, 2) AS UNSIGNED) ASC',
           default    => 't.treatmentDate DESC, CAST(SUBSTRING(t.treatmentID, 2) AS UNSIGNED) DESC',
       };

       // Build WHERE clause for appointment filtering
       $where_clause = "";
       $params = [];
       
       if (!empty($appointmentID)) {
           $where_clause = "WHERE t.appointmentID = ?";
           $params = [$appointmentID];
       }

       // Count total records
       $count_sql = "SELECT COUNT(*) FROM TREATMENT t $where_clause";
       $count_stmt = $conn->prepare($count_sql);
       $count_stmt->execute($params);
       $total_rows = $count_stmt->fetchColumn();
       
       $total_pages = $total_rows > 0 ? ceil($total_rows / $limit) : 1;

       // Get paginated data
       $sql = "SELECT t.treatmentID, t.treatmentDate, t.treatmentDescription, 
                      t.treatmentStatus, t.diagnosis, t.treatmentFee, t.vetID
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
           'vet_id' => $vetID
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

