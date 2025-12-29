<?php
require_once "../backend/connection.php";

/* =========================================================
   MYSQL (LOCALHOST) – TREATMENT & MEDICINE SELECT QUERIES
========================================================= */

/* ---------- NEXT TREATMENT ID ---------- */
function getNextTreatmentID_MYSQL() {
    global $connMySQL;

    $stmt = $connMySQL->query("
        SELECT MAX(CAST(SUBSTRING(treatmentID, 2) AS UNSIGNED)) AS max_num
        FROM TREATMENT
    ");

    $max = $stmt->fetchColumn();
    $num = $max ? $max + 1 : 1;

    return 'T' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

/* ---------- MEDICINE LIST ---------- */
function getMedicines_MYSQL() {
    global $connMySQL;

    $stmt = $connMySQL->query("
        SELECT medicineID, medicineName, unitPrice, stockQuantity
        FROM MEDICINE
        ORDER BY medicineName
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------- TREATMENT LIST ---------- */
function getTreatments_MYSQL($sort_by, $limit, $page) {
    global $connMySQL;

    $offset = ($page - 1) * $limit;

    $order = match ($sort_by) {
        'id_asc'   => 'CAST(SUBSTRING(treatmentID,2) AS UNSIGNED) ASC',
        'id_desc'  => 'CAST(SUBSTRING(treatmentID,2) AS UNSIGNED) DESC',
        'date_asc' => 'treatmentDate ASC',
        default    => 'treatmentDate DESC',
    };

    $total = $connMySQL->query("SELECT COUNT(*) FROM TREATMENT")->fetchColumn();
    $pages = ceil($total / $limit);

    $stmt = $connMySQL->query("
        SELECT treatmentID, treatmentDate, treatmentDescription,
               treatmentStatus, diagnosis, treatmentFee, vetID
        FROM TREATMENT
        ORDER BY $order
        LIMIT $limit OFFSET $offset
    ");

    return [
        'rows'  => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'pages' => $pages
    ];
}
