<?php
session_start();

// --- FORCE UPDATE LOGIC (Added for Debugging) ---
if (isset($_GET['owner_id'])) {
    $_SESSION['ownerID'] = trim($_GET['owner_id']);
}


/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['ownerID'])) {
    echo "<script>alert('Unauthorized access. Please login.'); window.location.href='../backend/logout.php';</script>";
    exit();
}

$ownerID = $_SESSION['ownerID'];
$displayName = $_SESSION['ownerName'] ?? 'Owner';

/* =========================
   LOAD DEPENDENCIES
========================= */
require_once "../backend/connection.php";
require_once "../backend/select_query_pg.php";
require_once "../backend/treatment_controller.php";

include "../frontend/ownerheader.php";

/* =========================
   SORT & PAGINATION
========================= */
$sort_by = $_GET['sort'] ?? 'date_desc';
$page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pet Medical History</title>

<script src="https://cdn.tailwindcss.com"></script>
<script> tailwind.config = { corePlugins: { preflight: false } } </script>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #f4f6f8;
}
.glass-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 15px rgba(0,0,0,0.05);
}
.aesthetic-table th {
    background: #f8fafc;
    text-transform: uppercase;
    font-size: 12px;
    color: #00798C;
    padding: 16px;
}
.aesthetic-table td {
    padding: 16px;
    font-size: 14px;
    border-bottom: 1px solid #f1f5f9;
}
.badge {
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
}
.badge-blue { background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe; }
.badge-gray { background:#f8fafc;color:#64748b;border:1px solid #e2e8f0; }
</style>
</head>

<body>

<div class="max-w-7xl mx-auto mt-28 px-4">

    <!-- PAGE HEADER -->
    <div class="flex justify-between items-end mb-6">
        <div>
            <h1 class="text-3xl font-bold text-teal-600">
                <i class="fas fa-notes-medical mr-2"></i> Medical History
            </h1>
            <p class="text-gray-500">Your pet’s treatment and medical records</p>
        </div>
        <div class="text-sm text-gray-600">
            Logged in as <strong class="text-teal-600"><?php echo htmlspecialchars($displayName); ?></strong>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="glass-card overflow-x-auto">

        <table class="aesthetic-table w-full">
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="w-1/4">Diagnosis</th>
                    <th>Medicine</th>
                    <th>Medicine Description</th>
                    <th>Follow Up</th>
                    <th>Follow Up Date</th>
                    <th class="text-right">Total Fee</th>
                    <th>Vet Name</th>
                </tr>
            </thead>

            <tbody class="bg-white">
            <?php if (!empty($treatments)): ?>
                <?php foreach ($treatments as $row): ?>
                <tr>

                    <!-- Date -->
                    <td>
                        <i class="far fa-calendar-alt text-teal-400 mr-1"></i>
                        <?php echo htmlspecialchars($row['treatment_date']); ?>
                    </td>

                    <!-- Diagnosis -->
                    <td>
                        <?php
                            echo !empty($row['diagnosis'])
                                ? htmlspecialchars($row['diagnosis'])
                                : htmlspecialchars($row['treatment_description']);
                        ?>
                    </td>

                    <!-- Medicine -->
                    <td>
                        <?php echo $row['medicine_name'] ?? '-'; ?>
                    </td>

                    <!-- Medicine Description -->
                    <td>
                        <?php echo $row['medicine_description'] ?? '-'; ?>
                    </td>

                    <!-- Follow Up -->
                    <td>
                        <?php if (!empty($row['follow_up_date'])): ?>
                            <span class="badge badge-blue">Required</span>
                        <?php else: ?>
                            <span class="badge badge-gray">No</span>
                        <?php endif; ?>
                    </td>

                    <!-- Follow Up Date -->
                    <td>
                        <?php echo $row['follow_up_date'] ?? '-'; ?>
                    </td>

                    <!-- Total Fee -->
                    <td class="text-right font-mono font-semibold">
                        RM <?php echo number_format($row['treatment_fee'], 2); ?>
                    </td>

                    <!-- Vet Name -->
                    <td>
                        <?php echo htmlspecialchars($row['vet_name'] ?? 'Vet'); ?>
                    </td>

                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center py-12 text-gray-400">
                        <i class="fas fa-folder-open text-4xl mb-3"></i>
                        <p>No medical records found</p>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

    </div>
</div>

<?php include "footer.php"; ?>

</body>
</html>
