<?php
// backend/medicinedetails_controller.php
session_start();
require_once "connection.php";


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
            $data = [];
            $res = $conn->query("
                SELECT medicineID, medicineName,
                       stockQuantity, expiryDate,
                       dosageInstructions, unitPrice
                FROM MEDICINE
                ORDER BY medicineName
            ");

            while ($row = $res->fetch_assoc()) {
                $data[] = $row;
            }

            echo json_encode([
                'status' => 'success',
                'data'   => $data
            ]);
            break;

        /* ================= CREATE ================= */
        case 'create':
            $name   = $_POST['name'];
            $stock  = (int)$_POST['stock'];
            $expiry = $_POST['expiryDate'];
            $dosage = $_POST['dosage'];
            $price  = $_POST['unitPrice'];

            // Generate new ID
            $idRes = $conn->query("
                SELECT MAX(CAST(SUBSTRING(medicineID,2) AS UNSIGNED)) AS maxID
                FROM MEDICINE
            ");
            $num   = ($idRes->fetch_assoc()['maxID'] ?? 0) + 1;
            $newID = "M" . str_pad($num, 3, "0", STR_PAD_LEFT);

            $stmt = $conn->prepare("
                INSERT INTO MEDICINE
                (medicineID, medicineName, stockQuantity,
                 expiryDate, dosageInstructions, unitPrice)
                VALUES (?,?,?,?,?,?)
            ");
            $stmt->bind_param(
                "ssisss",
                $newID, $name, $stock, $expiry, $dosage, $price
            );
            $stmt->execute();

            echo json_encode(['status' => 'success']);
            break;

        /* ================= UPDATE ================= */
        case 'update':
            $stmt = $conn->prepare("
                UPDATE MEDICINE
                SET medicineName=?, stockQuantity=?, expiryDate=?,
                    dosageInstructions=?, unitPrice=?
                WHERE medicineID=?
            ");
            $stmt->bind_param(
                "sissss",
                $_POST['name'],
                $_POST['stock'],
                $_POST['expiryDate'],
                $_POST['dosage'],
                $_POST['unitPrice'],
                $_POST['id']
            );
            $stmt->execute();

            echo json_encode(['status' => 'success']);
            break;

        /* ================= DELETE ================= */
        case 'delete':
            $stmt = $conn->prepare("
                DELETE FROM MEDICINE WHERE medicineID=?
            ");
            $stmt->bind_param("s", $_POST['id']);
            $stmt->execute();

            echo json_encode(['status' => 'success']);
            break;

        default:
            echo json_encode([
                'status'  => 'error',
                'message' => 'Invalid action'
            ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => $e->getMessage()
    ]);
}

