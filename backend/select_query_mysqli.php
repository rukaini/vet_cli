<?php
require_once "../backend/connection.php";

/* =========================================================
   MYSQL (LOCALHOST) – TREATMENT & MEDICINE SELECT QUERIES
   (UPDATED TO SNAKE_CASE COLUMNS)
========================================================= */

/* ---------- NEXT TREATMENT ID ---------- */
function getNextTreatmentID_MYSQL() {
    global $connMySQL;

    // SQL UPDATED: treatment_id
    $stmt = $connMySQL->query("
        SELECT MAX(CAST(SUBSTRING(treatment_id, 2) AS UNSIGNED)) AS max_num
        FROM TREATMENT
    ");

    $max = $stmt->fetchColumn();
    $num = $max ? $max + 1 : 1;

    return 'T' . str_pad($num, 3, '0', STR_PAD_LEFT);
}

/* ---------- MEDICINE LIST ---------- */
function getMedicines_MYSQL() {
    global $connMySQL;

    // SQL UPDATED: medicine_id, medicine_name, unit_price, stock_quantity
    $stmt = $connMySQL->query("
        SELECT medicine_id, medicine_name, unit_price, stock_quantity
        FROM MEDICINE
        ORDER BY medicine_name
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ---------- TREATMENT LIST ---------- */
function getTreatments_MYSQL($sort_by, $limit, $page) {
    global $connMySQL;

    $offset = ($page - 1) * $limit;

    // SQL UPDATED: treatment_id, treatment_date
    $order = match ($sort_by) {
        'id_asc'   => 'CAST(SUBSTRING(treatment_id,2) AS UNSIGNED) ASC',
        'id_desc'  => 'CAST(SUBSTRING(treatment_id,2) AS UNSIGNED) DESC',
        'date_asc' => 'treatment_date ASC',
        default    => 'treatment_date DESC',
    };

    $total = $connMySQL->query("SELECT COUNT(*) FROM TREATMENT")->fetchColumn();
    $pages = ceil($total / $limit);

    // SQL UPDATED: Select snake_case columns
    $stmt = $connMySQL->query("
        SELECT treatment_id, treatment_date, treatment_description,
               treatment_status, diagnosis, treatment_fee, vet_id
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
?>