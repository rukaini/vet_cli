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
// Ensure we have the correct DB connection variable
// Assuming $conn or $connMySQL or $dbLocal is available from connection.php
$dbLocal = $connMySQL ?? $conn ?? null; 

require_once "../backend/treatment_controller.php"; // This likely populates $treatments
include "../frontend/ownerheader.php";

/* =========================
   FETCH MEDICINE INSTRUCTIONS
========================= */
// We initialize an empty array to hold instructions mapped by treatment ID
$instructionsMap = [];

if (!empty($treatments) && $dbLocal) {
    try {
        // 1. Get IDs from the current page's treatments
        $t_ids = array_column($treatments, 'treatment_id');
        
        if (!empty($t_ids)) {
            // Create placeholders for the SQL IN clause (?,?,?)
            $placeholders = str_repeat('?,', count($t_ids) - 1) . '?';

            // 2. Fetch Instructions linked to these treatments
            // We join MEDICINE_DETAILS with MEDICINE to get the name
            $sqlInst = "SELECT md.treatment_id, md.instruction, m.medicine_name, m.medicine_id 
                        FROM MEDICINE_DETAILS md
                        LEFT JOIN MEDICINE m ON md.medicine_id = m.medicine_id
                        WHERE md.treatment_id IN ($placeholders) 
                        AND md.instruction IS NOT NULL 
                        AND md.instruction != ''";
            
            $stmtInst = $dbLocal->prepare($sqlInst);
            $stmtInst->execute($t_ids);
            $allInstructions = $stmtInst->fetchAll(PDO::FETCH_ASSOC);
            
            // 3. Group them by treatment_id for easy access in the loop
            foreach ($allInstructions as $inst) {
                $instructionsMap[$inst['treatment_id']][] = [
                    'medicine' => $inst['medicine_name'] ?? 'Medicine',
                    'instruction' => $inst['instruction'],
                    'id' => $inst['medicine_id']
                ];
            }
        }
    } catch (Exception $e) {
        // Silent fail or log error
        error_log("Error fetching instructions: " . $e->getMessage());
    }
}
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
    body { font-family: 'Poppins', sans-serif; background: #f4f6f8; }
    .glass-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 15px rgba(0,0,0,0.05); }
    .aesthetic-table th { background: #f8fafc; text-transform: uppercase; font-size: 12px; color: #00798C; padding: 16px; }
    .aesthetic-table td { padding: 16px; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    
    /* Button Styles */
    .btn-label {
        background-color: #00798C;
        color: white;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-label:hover { background-color: #00606f; transform: translateY(-1px); }

    /* Modal Styles */
    #instructionModal {
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
    }
    .prescription-paper {
        background: #fff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        /* Flex column to handle header/body/footer scrolling */
        display: flex;
        flex-direction: column;
    }
</style>
</head>

<body>

<div class="max-w-7xl mx-auto mt-28 px-4 pb-20">

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

    <div class="glass-card overflow-x-auto">

        <table class="aesthetic-table w-full">
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="w-1/4">Diagnosis</th>
                    <th>Medicine Info</th> <th>Follow Up</th>
                    <th class="text-right">Total Fee</th>
                    <th>Vet Name</th>
                </tr>
            </thead>

            <tbody class="bg-white">
            <?php if (!empty($treatments)): ?>
                <?php foreach ($treatments as $row): ?>
                <?php 
                    $tID = $row['treatment_id'];
                    $hasInstructions = isset($instructionsMap[$tID]);
                    
                    // Encode instructions to JSON for the JS function
                    $instJson = $hasInstructions ? json_encode($instructionsMap[$tID]) : '[]';
                    $patientName = htmlspecialchars($displayName . "'s Pet"); // Ideally fetch pet name from DB
                ?>
                <tr>

                    <td>
                        <div class="flex flex-col">
                            <span class="font-semibold text-gray-700">
                                <i class="far fa-calendar-alt text-teal-400 mr-1"></i>
                                <?php echo htmlspecialchars($row['treatment_date']); ?>
                            </span>
                            </div>
                    </td>

                    <td>
                        <div class="text-gray-800 font-medium">
                            <?php echo !empty($row['diagnosis']) ? htmlspecialchars($row['diagnosis']) : 'General Checkup'; ?>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            <?php echo htmlspecialchars($row['treatment_description'] ?? ''); ?>
                        </div>
                    </td>

                    <td>
                        <?php if ($hasInstructions): ?>
                            <button onclick='openInstructionModal(<?php echo $instJson; ?>, "<?php echo htmlspecialchars($row['treatment_date']); ?>", "<?php echo $patientName; ?>")' 
                                    class="btn-label">
                                <i class="fas fa-prescription"></i> View Label
                            </button>
                        <?php else: ?>
                            <span class="text-gray-400 text-xs italic">No specific instructions</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if (!empty($row['follow_up_date'])): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Required: <?php echo $row['follow_up_date']; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400 text-sm">-</span>
                        <?php endif; ?>
                    </td>

                    <td class="text-right font-mono font-semibold text-gray-700">
                        RM <?php echo number_format($row['treatment_fee'], 2); ?>
                    </td>

                    <td>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-teal-100 flex items-center justify-center text-teal-600 text-xs">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <span class="text-sm text-gray-600"><?php echo htmlspecialchars($row['vet_name'] ?? 'Vet'); ?></span>
                        </div>
                    </td>

                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-12 text-gray-400">
                        <i class="fas fa-folder-open text-4xl mb-3"></i>
                        <p>No medical records found</p>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="instructionModal" class="fixed inset-0 hidden items-center justify-center z-50 p-4">
    <div class="prescription-paper w-full max-w-sm max-h-[80vh] rounded-xl overflow-hidden relative animate-[fadeIn_0.3s_ease-out]">
        
        <div class="bg-teal-600 px-6 py-4 flex justify-between items-start flex-shrink-0">
            <div>
                <h2 class="text-white text-lg font-bold tracking-wide">MEDICINE INSTRUCTION</h2>
                <p class="text-teal-100 text-xs mt-1">Vet Clinic Kerol</p>
            </div>
            <button onclick="closeModal()" type="button" class="text-teal-100 hover:text-white transition focus:outline-none p-1">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <div class="p-6 bg-white overflow-y-auto flex-1">
            
            <div class="flex justify-between items-center border-b border-gray-100 pb-4 mb-4">
                <div>
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Date</span>
                    <div id="modalDate" class="text-sm font-semibold text-gray-700">--</div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Patient</span>
                    <div id="modalPatient" class="text-sm font-semibold text-gray-700">--</div>
                </div>
            </div>

            <div id="modalContent" class="space-y-6">
                </div>

            <div class="mt-8 pt-4 border-t border-dashed border-gray-300">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-orange-500 mt-1"></i>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        <strong>Important:</strong> Please complete the full course of medication as prescribed. 
                        If you notice any side effects, stop immediately and contact the clinic.
                    </p>
                </div>
            </div>

        </div>

        <div class="bg-gray-50 px-6 py-3 text-center flex-shrink-0 border-t border-gray-100">
            <button onclick="closeModal()" class="text-sm text-gray-500 hover:text-gray-800 font-medium">
                Close Label
            </button>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<script>
    // Open Modal Function
    function openInstructionModal(instructions, dateStr, patientName) {
        const modal = document.getElementById('instructionModal');
        const content = document.getElementById('modalContent');
        const dateEl = document.getElementById('modalDate');
        const patientEl = document.getElementById('modalPatient');

        // Set Header Details
        dateEl.textContent = dateStr;
        patientEl.textContent = patientName;
        content.innerHTML = ''; // Clear previous

        // Loop through medicines and create "cards" for each
        instructions.forEach((item, index) => {
            const html = `
                <div class="bg-blue-50/50 rounded-lg p-4 border border-blue-100 relative">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                            <i class="fas fa-pills"></i>
                        </div>
                        <div>
                            <h3 class="text-gray-800 font-bold text-base leading-tight">${item.medicine}</h3>
                            </div>
                    </div>

                    <div class="bg-white rounded border border-blue-100 p-3">
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-1">Giving Instructions:</h4>
                        <p class="text-sm text-gray-700 font-medium whitespace-pre-wrap leading-relaxed">
                            ${item.instruction}
                        </p>
                    </div>
                </div>
            `;
            content.insertAdjacentHTML('beforeend', html);
        });

        // Show Modal (Flex)
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    // Close Modal Function
    function closeModal() {
        const modal = document.getElementById('instructionModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // Close if clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('instructionModal');
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

</body>
</html>