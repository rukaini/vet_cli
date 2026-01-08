<?php
session_start();

// --- 1. Authentication Check (Dual Access) ---
$userID = null;
$userRole = null;

if (isset($_SESSION['vetID'])) {
    $userID = $_SESSION['vetID'];
    $userRole = 'Vet';
} elseif (isset($_SESSION['adminID'])) {
    $userID = $_SESSION['adminID'];
    $userRole = 'Admin';
} else {
    // If neither is logged in, redirect
    echo "<script>alert('Unauthorized access. Please login.'); window.location.href='../backend/logout.php';</script>";
    exit();
}

// --- 2. Backend Connection & Data Fetching ---
require_once "../backend/connection.php";

// Use the Local MySQL Connection
$conn = $connMySQL;

try {
    // Fetch all medicine details (Read-Only View)
    $sql = "SELECT medicine_id, medicine_name, stock_quantity, 
                   expiry_date, dosage_instructions, unit_price,
                   created_at
            FROM MEDICINE
            ORDER BY medicine_name ASC";
    
    $stmt = $conn->query($sql);
    $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $inventory = [];
    $error_msg = $e->getMessage();
}

// --- 3. Dynamic Header Include ---
if ($userRole === 'Vet') {
    include "../frontend/vetheader.php";
} else {
    include "../frontend/adminheader.php";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine List - VetClinic</title>

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
            min-height: 80vh;
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
            vertical-align: middle;
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
        .badge-low { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-good { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        
        .search-input {
            transition: all 0.3s;
        }
        .search-input:focus {
            box-shadow: 0 0 0 4px rgba(0, 121, 140, 0.1);
            border-color: var(--primary-color);
        }
        
        /* Fade Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.5s ease-out forwards; }
    </style>
</head>

<body>

    <div class="page-header mt-[80px]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-end">
            <div>
                <h1 class="text-3xl font-bold" style="color: var(--primary-color);">Pharmacy Inventory</h1>
                <p class="text-gray-500 mt-2">View available medicines, prices, and dosage instructions.</p>
            </div>
            <div class="hidden sm:block">
                 <div class="inline-flex items-center px-4 py-2 bg-teal-50 rounded-full border border-teal-100 text-sm text-teal-700">
                    <?php if ($userRole === 'Admin'): ?>
                        <i class="fas fa-user-shield mr-2"></i>
                    <?php else: ?>
                        <i class="fas fa-user-md mr-2"></i>
                    <?php endif; ?>
                    
                    <?php echo $userRole; ?> Access: <span class="font-semibold ml-1"><?php echo htmlspecialchars($userID); ?></span>
                </div>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-10">

        <div class="glass-card p-4 mb-6 fade-in flex flex-col md:flex-row gap-4 justify-between items-center">
            
            <div class="relative w-full md:w-96">
                <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <input type="text" id="searchInput" placeholder="Search by name or ID..." 
                       class="search-input w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none"
                       onkeyup="filterTable()">
            </div>

            <div class="flex items-center gap-3 w-full md:w-auto">
                <label class="text-sm font-semibold text-gray-500 whitespace-nowrap"><i class="fas fa-sort mr-1"></i> Sort by:</label>
                <select id="sortSelect" onchange="sortTable()" 
                        class="cursor-pointer py-2 pl-3 pr-8 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 focus:outline-none">
                    <option value="name">Name (A-Z)</option>
                    <option value="price_high">Price (High-Low)</option>
                    <option value="price_low">Price (Low-High)</option>
                    <option value="stock">Stock Quantity</option>
                </select>
            </div>
        </div>

        <div class="glass-card fade-in" style="animation-delay: 0.1s;">
            <div class="overflow-x-auto">
                <table class="aesthetic-table" id="medicineTable">
                    <thead>
                        <tr>
                            <th>Medicine Info</th>
                            <th>Stock Status</th>
                            <th>Unit Price</th>
                            <th>Expiry Date</th>
                            <th class="w-1/3">Dosage / Instructions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <?php if (count($inventory) > 0): ?>
                            <?php foreach ($inventory as $med): 
                                // Logic for Badge
                                $stock = (int)$med['stock_quantity'];
                                $badgeClass = ($stock < 10) ? 'badge-low' : 'badge-good';
                                $badgeText = ($stock < 10) ? 'Low Stock' : 'In Stock';
                                $price = number_format((float)$med['unit_price'], 2);
                            ?>
                            <tr class="medicine-row" 
                                data-name="<?php echo strtolower(htmlspecialchars($med['medicine_name'])); ?>"
                                data-id="<?php echo strtolower(htmlspecialchars($med['medicine_id'])); ?>"
                                data-price="<?php echo $med['unit_price']; ?>"
                                data-stock="<?php echo $stock; ?>">
                                
                                <td>
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-800 text-base" style="color: var(--primary-color);">
                                            <?php echo htmlspecialchars($med['medicine_name']); ?>
                                        </span>
                                        <span class="text-xs text-gray-400 font-mono mt-1 uppercase">
                                            ID: <?php echo htmlspecialchars($med['medicine_id']); ?>
                                        </span>
                                    </div>
                                </td>

                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-semibold text-gray-700"><?php echo $stock; ?> units</span>
                                        <span class="badge <?php echo $badgeClass; ?>"><?php echo $badgeText; ?></span>
                                    </div>
                                </td>

                                <td>
                                    <span class="font-mono font-bold text-gray-600">RM <?php echo $price; ?></span>
                                </td>

                                <td>
                                    <div class="flex items-center text-sm text-gray-500">
                                        <i class="far fa-calendar-alt mr-2 text-teal-400"></i>
                                        <?php echo htmlspecialchars($med['expiry_date']); ?>
                                    </div>
                                </td>

                                <td>
                                    <p class="text-sm text-gray-600 italic">
                                        "<?php echo htmlspecialchars($med['dosage_instructions'] ?: 'No instructions provided.'); ?>"
                                    </p>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-12">
                                    <div class="flex flex-col items-center justify-center text-gray-400">
                                        <i class="fas fa-box-open text-4xl mb-3"></i>
                                        <p>No medicines found in the database.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="bg-gray-50 p-4 border-t border-gray-100 flex justify-between items-center text-xs text-gray-500">
                <span>Total Items: <strong><?php echo count($inventory); ?></strong></span>
                <span>Last Updated: <?php echo date("Y-m-d"); ?></span>
            </div>
        </div>

    </main>

    <?php include "../frontend/footer.php"; ?>

    <script>
        function filterTable() {
            const input = document.getElementById("searchInput");
            const filter = input.value.toLowerCase();
            const rows = document.querySelectorAll(".medicine-row");

            rows.forEach(row => {
                const name = row.getAttribute("data-name");
                const id = row.getAttribute("data-id");
                
                if (name.includes(filter) || id.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function sortTable() {
            const table = document.getElementById("medicineTable");
            const tbody = table.querySelector("tbody");
            const rows = Array.from(tbody.querySelectorAll(".medicine-row"));
            const criteria = document.getElementById("sortSelect").value;

            rows.sort((a, b) => {
                if (criteria === "name") {
                    return a.dataset.name.localeCompare(b.dataset.name);
                } else if (criteria === "price_high") {
                    return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
                } else if (criteria === "price_low") {
                    return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
                } else if (criteria === "stock") {
                    return parseInt(a.dataset.stock) - parseInt(b.dataset.stock);
                }
            });

            // Re-append rows in new order
            rows.forEach(row => tbody.appendChild(row));
        }
    </script>
</body>
</html>