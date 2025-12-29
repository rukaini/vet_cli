<?php
session_start();

// --- 1. Authentication Check ---
if (!isset($_SESSION['vetID'])) {
    echo "<script>alert('Unauthorized access. Please login.'); window.location.href='userlogin.php';</script>";
    exit();
}

$vetID = $_SESSION['vetID'];

// --- 2. Include Backend Files ---
require_once "../backend/connection.php";
require_once "../backend/select_query_pg.php"; // Explicitly include PG queries
require_once "../backend/treatment_controller.php";

// --- 3. Force Fetch Vet Name Logic ---
$displayName = ""; // Default empty

// Check if we have the name in Session
if (isset($_SESSION['vetName']) && !empty($_SESSION['vetName']) && $_SESSION['vetName'] !== $vetID) {
    $displayName = $_SESSION['vetName'];
} 
// If not, try to fetch from Postgres Database
else {
    if (function_exists('getVetByIdPG')) {
        $vetData = getVetByIdPG($vetID);
        if ($vetData && isset($vetData['vet_name'])) {
            $displayName = $vetData['vet_name'];
            $_SESSION['vetName'] = $displayName; // Save for later
        }
    }
}

// Fallback: If still empty, use a placeholder or generic title
if (empty($displayName)) {
    $displayName = "Veterinarian"; 
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
        /* --- EXACT STYLE MATCH FROM TREATMENT_VIEW.PHP --- */
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        
        :root {
            --primary-color: #00798C; /* The Teal Color */
            --secondary-color: #D1EAEF; /* Light Blue Accent */
            --bg-color: #f4f6f8;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: #334155;
        }

        .main-content-wrapper {
            margin-top: 80px;
            min-height: 85vh;
            padding-bottom: 60px;
        }

        /* --- Header Style --- */
        .custom-header-bg {
            background-color: white;
            color: var(--primary-color);
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            border-bottom: 3px solid var(--primary-color);
        }

        /* --- Minimalist Card --- */
        .custom-card {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
            background: white;
            border-radius: 0.5rem;
        }

        /* --- Table Styling --- */
        .custom-table thead tr th {
            background-color: var(--secondary-color) !important;
            color: var(--primary-color) !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.05em;
            padding: 1rem 1.5rem;
        }

        .table-row-hover {
            transition: background-color 0.2s ease;
        }
        .table-row-hover:hover {
            background-color: #f8fafc;
        }

        /* --- Inputs & Selects --- */
        select {
            display: block;
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: white;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 121, 140, 0.15); 
        }

        /* --- Status Pills --- */
        .status-badge {
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>

<body>

<main class="main-content-wrapper">

    <div class="custom-header-bg">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl md:text-4xl font-bold" style="color: var(--primary-color);">Treatment List</h1>
            <p class="mt-2 text-lg text-gray-500">
                Logged in as Vet: <strong><?php echo htmlspecialchars($vetID); ?></strong> 
                <?php if (!empty($displayName) && $displayName !== "Veterinarian"): ?>
                    (<?php echo htmlspecialchars(urldecode($displayName)); ?>)
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="custom-card p-6 md:p-8 mb-8">
            
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 border-b pb-4" style="border-color: #f3f4f6;">
                <h2 class="text-xl font-bold mb-4 md:mb-0" style="color: var(--primary-color);">
                    Treatment List
                </h2>

                <form method="GET" action="" class="w-full md:w-auto flex items-center gap-3">
                    <label for="sort" class="text-sm font-semibold text-gray-600 whitespace-nowrap">Sort By:</label>
                    <div class="relative w-full md:w-56">
                        <select name="sort" onchange="this.form.submit()">
                            <option value="date_desc" <?php echo ($sort_by == 'date_desc') ? 'selected' : ''; ?>>Date: Newest First</option>
                            <option value="date_asc" <?php echo ($sort_by == 'date_asc') ? 'selected' : ''; ?>>Date: Oldest First</option>
                            <option value="id_desc" <?php echo ($sort_by == 'id_desc') ? 'selected' : ''; ?>>ID: High to Low</option>
                            <option value="id_asc" <?php echo ($sort_by == 'id_asc') ? 'selected' : ''; ?>>ID: Low to High</option>
                        </select>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto rounded-lg border border-gray-100">
                <table class="min-w-full divide-y divide-gray-100 custom-table">
                    <thead>
                        <tr>
                            <th class="text-left">Treatment ID</th>
                            <th class="text-left">Date</th>
                            <th class="text-left">Description / Diagnosis</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Total Fee</th>
                            <th class="text-left pl-6">Vet</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        <?php if (isset($treatments) && count($treatments) > 0): ?>
                            <?php foreach ($treatments as $row): 
                                // Status Colors
                                $status_class = match ($row['treatment_status']) {
                                    'Completed'   => 'bg-green-100 text-green-700',
                                    'In Progress' => 'bg-blue-100 text-blue-700',
                                    'Pending'     => 'bg-yellow-100 text-yellow-700',
                                    'Deceased'    => 'bg-red-100 text-red-700',
                                    default       => 'bg-gray-100 text-gray-700',
                                };
                                $desc = !empty($row['diagnosis']) ? $row['diagnosis'] : $row['treatment_description'];
                            ?>
                            <tr class="table-row-hover">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold" style="color: var(--primary-color);">
                                    <?php echo htmlspecialchars($row['treatment_id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <?php echo htmlspecialchars($row['treatment_date']); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 max-w-xs truncate" title="<?php echo htmlspecialchars($row['treatment_description']); ?>">
                                    <?php echo htmlspecialchars($desc); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($row['treatment_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-800 text-right">
                                    RM <?php echo number_format($row['treatment_fee'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 pl-6">
                                    <?php echo htmlspecialchars($row['vet_id']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500 italic">
                                    No treatments found in the records.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4 pt-4 border-t border-gray-100">
                <span class="text-sm text-gray-500">
                    Showing Page <strong><?php echo $page; ?></strong> of <strong><?php echo $total_pages; ?></strong>
                </span>
                
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&sort=<?php echo $sort_by; ?>" 
                           class="px-4 py-2 text-sm border border-gray-300 rounded hover:bg-gray-50 text-gray-600 no-underline transition">
                           &larr; Previous
                        </a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&sort=<?php echo $sort_by; ?>" 
                           style="background-color: var(--primary-color);"
                           class="px-4 py-2 text-sm text-white rounded shadow hover:opacity-90 no-underline transition">
                           Next &rarr;
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php include "footer.php"; ?>

</body>
</html>