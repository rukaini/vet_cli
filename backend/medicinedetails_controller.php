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

        /* ================= READ (WITH SORTING) ================= */
        case 'read':
            // 1. Handle Sorting
            $sort = $_GET['sort'] ?? 'name_asc';
            
            $orderBy = match ($sort) {
                'date_desc'  => 'created_at DESC',
                'date_asc'   => 'created_at ASC',
                'name_asc'   => 'medicine_name ASC',
                'name_desc'  => 'medicine_name DESC',
                'stock_asc'  => 'stock_quantity ASC',
                'stock_desc' => 'stock_quantity DESC',
                default      => 'medicine_name ASC'
            };

            // 2. Select New Columns (admin_id, created_at)
            $sql = "SELECT medicine_id, medicine_name, stock_quantity, 
                           expiry_date, dosage_instructions, unit_price,
                           admin_id, created_at
                    FROM MEDICINE
                    ORDER BY $orderBy";

            $stmt = $conn->query($sql);
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

            // Generate ID (M001, M002...)
            $stmt = $conn->query("
                SELECT MAX(CAST(SUBSTRING(medicine_id, 2) AS UNSIGNED)) 
                FROM MEDICINE
            ");
            $maxID = $stmt->fetchColumn();
            $num   = ($maxID ?? 0) + 1;
            $newID = "M" . str_pad($num, 3, "0", STR_PAD_LEFT);

            // Insert including admin_id
            // Note: created_at is usually automatic in DB (DEFAULT CURRENT_TIMESTAMP)
            // If you need to force it, add NOW() to values.
            $sql = "INSERT INTO MEDICINE 
                    (medicine_id, medicine_name, stock_quantity, expiry_date, dosage_instructions, unit_price, admin_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$newID, $name, $stock, $expiry, $dosage, $price, $admin]);

            echo json_encode(['status' => 'success']);
            break;

        /* ================= UPDATE ================= */
        case 'update':
            // Usually we don't update created_at or admin_id on edit, but we can if needed.
            // Keeping it simple: update details only.
            $sql = "UPDATE MEDICINE 
                    SET medicine_name=?, stock_quantity=?, expiry_date=?, dosage_instructions=?, unit_price=? 
                    WHERE medicine_id=?";
            
            $stmt = $conn->prepare($sql);
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