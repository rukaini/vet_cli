<?php
// backend/medicinedetails_controller.php
session_start();
require_once "connection.php";

// Ensure the connection variable from connection.php is available
global $connMySQL;
$conn = $connMySQL;

// Auth Check
if (!isset($_SESSION['adminID'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

header("Content-Type: application/json");
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {

        /* ================= READ ================= */
        case 'read':
            // SQL UPDATED: snake_case columns
            $stmt = $conn->query("
                SELECT medicine_id, medicine_name,
                       stock_quantity, expiry_date,
                       dosage_instructions, unit_price
                FROM MEDICINE
                ORDER BY medicine_name
            ");

            // PDO: fetchAll instead of fetch_assoc loop
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['status' => 'success', 'data' => $data]);
            break;

        /* ================= CREATE ================= */
        case 'create':
            $name   = $_POST['name'];
            $stock  = (int)$_POST['stock'];
            $expiry = $_POST['expiryDate'];
            $dosage = $_POST['dosage'];
            $price  = $_POST['unitPrice'];
            $admin  = $_POST['admin_id'] ?? null; 

            // PDO: query() returns a statement, fetchColumn() gets the single value
            $stmt = $conn->query("
                SELECT MAX(CAST(SUBSTRING(medicine_id, 2) AS UNSIGNED)) 
                FROM MEDICINE
            ");
            $maxID = $stmt->fetchColumn();
            $num   = ($maxID ?? 0) + 1;
            $newID = "M" . str_pad($num, 3, "0", STR_PAD_LEFT);

            // SQL: snake_case columns
            $sql = "INSERT INTO MEDICINE 
                    (medicine_id, medicine_name, stock_quantity, expiry_date, dosage_instructions, unit_price, admin_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            
            // PDO: Pass values array to execute()
            $stmt->execute([$newID, $name, $stock, $expiry, $dosage, $price, $admin]);

            echo json_encode(['status' => 'success']);
            break;

        /* ================= UPDATE ================= */
        case 'update':
            // SQL: snake_case columns
            $sql = "UPDATE MEDICINE 
                    SET medicine_name=?, stock_quantity=?, expiry_date=?, dosage_instructions=?, unit_price=? 
                    WHERE medicine_id=?";
            
            $stmt = $conn->prepare($sql);
            
            // PDO: Pass values array to execute()
            $stmt->execute([
                $_POST['name'], 
                $_POST['stock'], 
                $_POST['expiryDate'], 
                $_POST['dosage'], 
                $_POST['unitPrice'], 
                $_POST['id']
            ]);

            echo json_encode(['status' => 'success']);
            break;

        /* ================= DELETE ================= */
        case 'delete':
            // SQL: snake_case columns
            $stmt = $conn->prepare("DELETE FROM MEDICINE WHERE medicine_id=?");
            $stmt->execute([$_POST['id']]);

            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>