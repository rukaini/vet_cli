<?php
session_start();

// --- FORCE UPDATE LOGIC (Added for Debugging) ---
if (isset($_GET['vet_id'])) {
    $_SESSION['vetID'] = trim($_GET['vet_id']);
}

// --- 1. Authentication Check ---
if (!isset($_SESSION['vetID'])) {
    echo "<script>alert('Unauthorized access. Please login.'); window.location.href='../backend/logout.php';</script>";
    exit();
}

$vetID = $_SESSION['vetID'];
$vetName = "Veterinarian";

// --- 2. Include Backend Files ---
require_once "../backend/connection.php";
require_once "../backend/select_query_pg.php"; 
require_once "../backend/treatment_controller.php";

// --- 3. Force Fetch Vet Name Logic ---
$displayName = ""; 

if (isset($_SESSION['vetName']) && !empty($_SESSION['vetName']) && $_SESSION['vetName'] !== $vetID) {
    $displayName = $_SESSION['vetName'];
} else {
    if (function_exists('getVetByIdPG')) {
        $vetData = getVetByIdPG($vetID);
        if ($vetData && isset($vetData['vet_name'])) {
            $vetName = $vetData['vet_name'];
            $_SESSION['vetName'] = $vetName; 
        }
    }
}

if (empty($displayName)) {
    $displayName = "Veterinarian"; 
}

$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_desc';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// --- PREPARE DATA FETCHING ---
// Determine DB connections
$dbLocal = isset($connMySQL) ? $connMySQL : (isset($conn) ? $conn : null); // For Treatments/Instructions
$dbMaria = isset($conn) ? $conn : null; // For Appointments (Time)

$instructionsMap = [];
$treatmentTimes = [];

if (!empty($treatments) && $dbLocal) {
    try {
        // Get IDs from the current page's treatments
        $t_ids = array_column($treatments, 'treatment_id');
        
        if (!empty($t_ids)) {
            $placeholders = str_repeat('?,', count($t_ids) - 1) . '?';

            // --- 1. FETCH INSTRUCTIONS ---
            $sqlInst = "SELECT md.treatment_id, md.instruction, m.medicine_name 
                        FROM MEDICINE_DETAILS md
                        LEFT JOIN MEDICINE m ON md.medicine_id = m.medicine_id
                        WHERE md.treatment_id IN ($placeholders) 
                        AND md.instruction IS NOT NULL 
                        AND md.instruction != ''";
            
            $stmtInst = $dbLocal->prepare($sqlInst);
            $stmtInst->execute($t_ids);
            $allInstructions = $stmtInst->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($allInstructions as $inst) {
                $instructionsMap[$inst['treatment_id']][] = [
                    'medicine' => $inst['medicine_name'] ?? 'Medicine',
                    'instruction' => $inst['instruction']
                ];
            }

            // --- 2. FETCH APPOINTMENT TIMES ---
            // First: Get appointment_id for these treatments (Local DB)
            $sqlAppt = "SELECT treatment_id, appointment_id FROM TREATMENT WHERE treatment_id IN ($placeholders)";
            $stmtAppt = $dbLocal->prepare($sqlAppt);
            $stmtAppt->execute($t_ids);
            $apptMap = $stmtAppt->fetchAll(PDO::FETCH_KEY_PAIR); // [treatment_id => appointment_id]

            // Second: Get Time from Appointment Table (MariaDB)
            if (!empty($apptMap) && $dbMaria) {
                $a_ids = array_values($apptMap);
                $a_ids = array_filter($a_ids); // Remove nulls
                
                if (!empty($a_ids)) {
                    $placeholdersA = str_repeat('?,', count($a_ids) - 1) . '?';
                    $sqlTime = "SELECT appointment_id, time FROM appointment WHERE appointment_id IN ($placeholdersA)";
                    $stmtTime = $dbMaria->prepare($sqlTime);
                    $stmtTime->execute($a_ids);
                    $timeMap = $stmtTime->fetchAll(PDO::FETCH_KEY_PAIR); // [appointment_id => time]

                    // Map back to treatment_id
                    foreach ($apptMap as $tid => $aid) {
                        if (isset($timeMap[$aid])) {
                            $treatmentTimes[$tid] = $timeMap[$aid];
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silently fail if tables/connections missing
    }
}

include "../frontend/vetheader.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treatment List - VetClinic</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script> tailwind.config = { corePlugins: { preflight: false } } </script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- Shared Aesthetic Styles --- */
        :root {
            --primary-color: #00798C;       /* Teal */
            --primary-hover: #00606f;
            --bg-color: #f4f6f8;
            --surface-color: #ffffff;
            --text-main: #334155;
            --text-light: #64748b;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        .main-wrapper {
            margin-top: 100px;
            padding-bottom: 60px;
            min-height: 85vh;
        }

        /* --- Glass/Minimalist Card --- */
        .glass-card {
            background: var(--surface-color);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        /* --- Header Section --- */
        .page-header {
            background-color: white;
            border-bottom: 3px solid var(--primary-color);
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* --- Table Styling --- */
        .aesthetic-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .aesthetic-table thead th {
            background-color: #f8fafc;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1.25rem 1.5rem;
            border-bottom: 2px solid #e2e8f0;
            text-align: left;
        }

        .aesthetic-table tbody tr {
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .aesthetic-table tbody tr:hover {
            background-color: #f0f9ff;
        }

        .aesthetic-table td {
            padding: 1.25rem 1.5rem;
            font-size: 0.9rem;
            color: var(--text-main);
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        .aesthetic-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* --- Badges --- */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge-green  { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-blue   { background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-yellow { background-color: #fefce8; color: #ca8a04; border: 1px solid #fef08a; }
        .badge-red    { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-gray   { background-color: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>

<div class="page-header mt-[80px]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-end">
        <div>
            <h1 class="text-3xl font-bold" style="color: var(--primary-color);">Treatment History</h1>
            <p class="text-gray-500 mt-2">View patient treatments, diagnoses, and fees.</p>
        </div>
        <div class="hidden sm:block">
             <div class="inline-flex items-center px-4 py-2 bg-teal-50 rounded-full border border-teal-100 text-sm text-teal-700">
                <i class="fas fa-user-md mr-2"></i>
                Vet Access : <strong><?php echo htmlspecialchars($vetID); ?></strong>
                <?php if (!empty($displayName) && $displayName !== "Veterinarian"): ?>
                    <span class="ml-1 text-gray-500">(<?php echo htmlspecialchars(urldecode($displayName)); ?>)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-10">

    <div class="glass-card p-4 mb-6 fade-in flex flex-col md:flex-row gap-4 justify-between items-center">
        
        <h2 class="text-lg font-bold text-gray-700 pl-2">
            <i class="fas fa-notes-medical mr-2 text-teal-500"></i> Records List
        </h2>

        <form method="GET" action="" class="w-full md:w-auto flex items-center gap-3">
            <label for="sort" class="text-sm font-semibold text-gray-500 whitespace-nowrap"><i class="fas fa-sort mr-1"></i> Sort by:</label>
            <div class="relative">
                <select name="sort" onchange="this.form.submit()" 
                        class="cursor-pointer py-2 pl-3 pr-10 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none shadow-sm appearance-none">
                    <option value="date_desc" <?php echo ($sort_by == 'date_desc') ? 'selected' : ''; ?>>Date: Newest First</option>
                    <option value="date_asc" <?php echo ($sort_by == 'date_asc') ? 'selected' : ''; ?>>Date: Oldest First</option>
                    <option value="id_desc" <?php echo ($sort_by == 'id_desc') ? 'selected' : ''; ?>>ID: High to Low</option>
                    <option value="id_asc" <?php echo ($sort_by == 'id_asc') ? 'selected' : ''; ?>>ID: Low to High</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <i class="fas fa-chevron-down text-xs"></i>
                </div>
            </div>
        </form>
    </div>

    <div class="glass-card fade-in" style="animation-delay: 0.1s;">
        <div class="overflow-x-auto">
            <table class="aesthetic-table">
                <thead>
                    <tr>
                        <th class="w-24">ID</th>
                        <th class="w-36">Date & Time</th> <th class="w-5/12">Diagnosis & Details</th> 
                        <th>Status</th>
                        <th class="text-right">Total Fee</th>
                        <th>Vet ID</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    <?php if (isset($treatments) && count($treatments) > 0): ?>
                        <?php foreach ($treatments as $row): 
                            // Determine Status Badge Color
                            $status_badge = match ($row['treatment_status']) {
                                'Completed'   => 'badge-green',
                                'In Progress' => 'badge-blue',
                                'Pending'     => 'badge-yellow',
                                'Deceased'    => 'badge-red',
                                default       => 'badge-gray',
                            };
                            
                            $tID = $row['treatment_id'];
                            $hasInstructions = isset($instructionsMap[$tID]) && count($instructionsMap[$tID]) > 0;
                            $timeStr = isset($treatmentTimes[$tID]) ? date('h:i A', strtotime($treatmentTimes[$tID])) : '';
                        ?>
                        <tr>
                            <td>
                                <span class="font-bold text-base" style="color: var(--primary-color);">
                                    <?php echo htmlspecialchars($row['treatment_id']); ?>
                                </span>
                            </td>

                            <td>
                                <div class="flex flex-col">
                                    <div class="flex items-center text-sm text-gray-600 font-medium">
                                        <i class="far fa-calendar-alt mr-2 text-teal-400"></i>
                                        <?php echo date('d/m/Y', strtotime($row['treatment_date'])); ?>
                                    </div>
                                    <?php if ($timeStr): ?>
                                    <div class="flex items-center text-xs text-gray-400 mt-1 ml-6">
                                        <i class="far fa-clock mr-1.5"></i>
                                        <?php echo $timeStr; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <div class="mb-1">
                                    <span class="font-bold text-gray-800 text-sm block">
                                        <?php echo htmlspecialchars($row['diagnosis'] ?: 'General Checkup'); ?>
                                    </span>
                                </div>

                                <?php if (!empty($row['treatment_description'])): ?>
                                    <div class="text-xs text-gray-500 leading-relaxed italic mb-2">
                                        <?php echo nl2br(htmlspecialchars($row['treatment_description'])); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($hasInstructions): ?>
                                    <div class="mt-2 bg-slate-50 rounded-lg p-2.5 border border-slate-100 max-w-md">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 flex items-center">
                                            <i class="fas fa-prescription-bottle-alt mr-1.5 text-teal-500"></i> 
                                            Instructions
                                        </p>
                                        <ul class="space-y-1.5">
                                            <?php foreach ($instructionsMap[$tID] as $item): ?>
                                                <li class="text-xs text-slate-700 flex items-start">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-teal-400 mt-1.5 mr-2 flex-shrink-0"></span>
                                                    <span>
                                                        <strong class="text-slate-800"><?php echo htmlspecialchars($item['medicine']); ?>:</strong> 
                                                        <?php echo htmlspecialchars($item['instruction']); ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <span class="badge <?php echo $status_badge; ?>">
                                    <?php echo htmlspecialchars($row['treatment_status']); ?>
                                </span>
                            </td>

                            <td class="text-right">
                                <span class="font-mono font-bold text-gray-700">RM <?php echo number_format($row['treatment_fee'], 2); ?></span>
                            </td>

                            <td>
                                <span class="text-xs font-mono text-gray-500 bg-gray-50 px-2 py-1 rounded border border-gray-100">
                                    <?php echo htmlspecialchars($row['vet_id']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-12">
                                <div class="flex flex-col items-center justify-center text-gray-400">
                                    <i class="fas fa-folder-open text-4xl mb-3"></i>
                                    <p>No treatments found in the records.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="bg-gray-50 p-4 border-t border-gray-100 flex flex-col sm:flex-row justify-between items-center gap-4">
            <span class="text-xs text-gray-500 font-medium">
                Showing Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong>
            </span>
            
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sort_by; ?>" 
                       class="px-4 py-2 text-sm bg-white border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition shadow-sm no-underline">
                       &larr; Previous
                    </a>
                <?php else: ?>
                    <span class="px-4 py-2 text-sm bg-gray-100 border border-gray-200 text-gray-400 rounded-lg cursor-not-allowed">
                       &larr; Previous
                    </span>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sort_by; ?>" 
                       class="px-4 py-2 text-sm text-white rounded-lg shadow-md hover:opacity-90 transition no-underline"
                       style="background-color: var(--primary-color);">
                       Next &rarr;
                    </a>
                <?php else: ?>
                    <span class="px-4 py-2 text-sm bg-gray-100 border border-gray-200 text-gray-400 rounded-lg cursor-not-allowed">
                       Next &rarr;
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</main>

<?php include "footer.php"; ?>

</body>
</html>