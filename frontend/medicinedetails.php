<?php
// frontend/medicinedetails.php
session_start();
require_once "../backend/token_auth.php";
/* ================= AUTH ================= */
if (isset($_GET['admin_id'])) {
    $_SESSION['adminID'] = trim($_GET['admin_id']);
}

if (!isset($_SESSION['adminID'])) {
    echo "<script>alert('Unauthorized access. Please login.'); window.location.href='../backend/logout.php';</script>";
    exit();
}

$adminID = $_SESSION['adminID'];
include "../frontend/adminheader.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Inventory - VetClinic</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script> tailwind.config = { corePlugins: { preflight: false } } </script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary-color: #00798C;       /* Teal */
            --primary-hover: #00606f;       /* Darker Teal */
            --bg-color: #f8fafc;            /* Very Light Gray/Blueish */
            --surface-color: #ffffff;
            --text-main: #334155;
            --text-light: #64748b;
            --danger-color: #ef4444;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            -webkit-font-smoothing: antialiased;
        }

        .main-wrapper {
            margin-top: 110px;
            padding-bottom: 60px;
        }

        /* --- Minimalist Cards --- */
        .glass-card {
            background: var(--surface-color);
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        /* --- Inputs & Form --- */
        .input-minimal {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            width: 100%;
        }
        
        .input-minimal:focus {
            background-color: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 121, 140, 0.1);
            outline: none;
        }

        label {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-main);
            margin-bottom: 0.35rem;
            display: block;
        }

        /* --- Main Submit Button --- */
        .btn-teal {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            letter-spacing: 0.025em;
            transition: all 0.2s;
            border: none;
        }
        .btn-teal:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 121, 140, 0.2);
        }

        /* --- NEW: Minimalist Reset Button (No Border) --- */
        .btn-reset-minimal {
            background: transparent;
            border: none;
            color: #94a3b8; /* Soft Gray */
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-reset-minimal:hover {
            background-color: #f1f5f9; /* Lightest Gray BG on hover */
            color: #475569; /* Darker Gray text on hover */
        }

        /* --- NEW: Aesthetic Minimalist Action Buttons (No Border) --- */
        .action-btn-minimal {
            width: 36px;
            height: 36px;
            border-radius: 8px; /* Soft square corners */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0 4px;
            border: none; /* Removed heavy border */
            font-size: 0.95rem;
            background-color: transparent;
        }

        /* Edit Button Styles (Teal) */
        .action-btn-minimal.edit {
            color: var(--primary-color);
        }
        .action-btn-minimal.edit:hover {
            background-color: rgba(0, 121, 140, 0.1); /* Soft Teal BG */
            transform: translateY(-1px);
        }

        /* Delete Button Styles (Red) */
        .action-btn-minimal.delete {
            color: var(--danger-color);
        }
        .action-btn-minimal.delete:hover {
            background-color: rgba(239, 68, 68, 0.1); /* Soft Red BG */
            transform: translateY(-1px);
        }


        /* --- Table Styling --- */
        .clean-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .clean-table thead th {
            background-color: #f1f5f9;
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .clean-table tbody tr {
            transition: background 0.2s;
        }
        
        .clean-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .clean-table td {
            padding: 1rem 1.5rem;
            font-size: 0.9rem;
            color: var(--text-main);
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .clean-table tbody tr:last-child td {
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

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in { animation: fadeIn 0.4s ease-out forwards; }
    </style>
</head>

<body>

<main class="main-wrapper max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="mb-10 relative fade-in text-center">
    <div>
        <h1 class="text-3xl font-bold" style="color: var(--primary-color);">Medicine Inventory</h1>
        <p class="text-gray-500 mt-2">Manage stock, track expiry, and update pricing.</p>
    </div>
    <div class="mt-4 md:mt-0 md:absolute md:right-0 md:top-1/2 md:transform md:-translate-y-1/2 flex justify-center">
         <div class="inline-flex items-center px-4 py-2 bg-white rounded-lg shadow-sm border border-gray-100 text-sm text-gray-600">
            <i class="fas fa-user-shield mr-2 text-teal-600"></i>
            Admin: <span class="font-semibold ml-1"><?php echo htmlspecialchars($adminID); ?></span>
        </div>
    </div>
</div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <div class="lg:col-span-4 fade-in" style="animation-delay: 0.1s;">
            <div class="glass-card p-6 sticky top-24">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-bold text-gray-800">Add / Edit Medicine</h2>
                    
                    <button onclick="resetForm()" class="btn-reset-minimal" title="Clear Form">
                        <i class="fas fa-sync-alt"></i> Reset
                    </button>
                </div>

                <form id="medicineForm" class="space-y-4">
                    <input type="hidden" id="medicineId">
                    <input type="hidden" id="admin_id_input" value="<?php echo $_SESSION['adminID']; ?>">

                    <div>
                        <label for="name">Medicine Name</label>
                        <input type="text" id="name" class="input-minimal" placeholder="e.g. Amoxicillin 500mg" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="stock">Stock Qty</label>
                            <input type="number" id="stock" class="input-minimal" placeholder="0" min="0" required>
                        </div>
                        <div>
                            <label for="unitPrice">Price (RM)</label>
                            <input type="number" step="0.01" id="unitPrice" class="input-minimal" placeholder="0.00" required>
                        </div>
                    </div>

                    <div>
                        <label for="expiryDate">Expiry Date</label>
                        <input type="date" id="expiryDate" class="input-minimal" required>
                    </div>

                    <div>
                        <label for="dosage">Dosage Instructions</label>
                        <textarea id="dosage" rows="2" class="input-minimal" placeholder="e.g. 1 tablet twice daily after food" required></textarea>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full btn-teal shadow-lg shadow-teal-500/20">
                            <i class="fas fa-plus-circle mr-2"></i> Save Record
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="lg:col-span-8 fade-in" style="animation-delay: 0.2s;">
            <div class="glass-card overflow-hidden min-h-[600px]">
                
                <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row gap-4 justify-between items-center bg-white">
                    <div class="relative w-full sm:w-64">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" id="searchInput" placeholder="Search medicines..." 
                               class="pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm w-full focus:ring-2 focus:ring-teal-500/20 focus:outline-none"
                               onkeyup="renderMedicineList()">
                    </div>
                    
                    <div class="flex items-center gap-2 w-full sm:w-auto">
                        <label class="text-xs font-semibold text-gray-500 whitespace-nowrap">Sort by:</label>
                        <select id="sortSelect" onchange="fetchInventory()" 
                                class="py-2 pl-3 pr-8 bg-white border border-gray-200 rounded-lg text-sm text-gray-700 focus:border-teal-500 focus:outline-none cursor-pointer">
                            <option value="name_asc">Name (A-Z)</option>
                            <option value="name_desc">Name (Z-A)</option>
                            <option value="date_desc">Newest First</option>
                            <option value="date_asc">Oldest First</option>
                            <option value="stock_asc">Stock (Low-High)</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="clean-table">
                        <thead>
                            <tr>
                                <th>Details</th>
                                <th>Status</th>
                                <th>Price</th>
                                <th>Created at</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="medicineTableBody">
                            <tr>
                                <td colspan="5" class="text-center py-10 text-gray-400">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i><br>Loading inventory...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-gray-100 bg-gray-50 text-xs text-gray-500 text-center">
                    Showing all available records
                </div>

            </div>
        </div>

    </div>

</main>

<?php include "footer.php"; ?>

<script>
    let medicineInventory = [];

    /* ================= FETCH DATA ================= */
    async function fetchInventory() {
        const sortValue = document.getElementById('sortSelect').value;
        const tbody = document.getElementById('medicineTableBody');
        
        // Visual Loading State
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-12 text-gray-400"><i class="fas fa-circle-notch fa-spin text-2xl text-teal-500 mb-3"></i><br>Updating list...</td></tr>`;

        try {
            const res = await fetch(`../backend/medicinedetails_controller.php?action=read&sort=${sortValue}`);
            const json = await res.json();
            
            if(json.status === 'success'){
                medicineInventory = json.data || [];
                renderMedicineList();
            } else {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Failed to load data.</td></tr>`;
            }
        } catch (error) {
            console.error(error);
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-red-500">Connection Error</td></tr>`;
        }
    }

    /* ================= RENDER TABLE ================= */
    function renderMedicineList() {
        const tbody = document.getElementById('medicineTableBody');
        const term = document.getElementById('searchInput').value.toLowerCase();
        tbody.innerHTML = '';

        const filtered = medicineInventory.filter(m =>
            (m.medicine_name && m.medicine_name.toLowerCase().includes(term)) || 
            (m.medicine_id && m.medicine_id.toLowerCase().includes(term))
        );

        if (!filtered.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-12">
                        <div class="text-gray-400 mb-2"><i class="far fa-folder-open text-3xl"></i></div>
                        <p class="text-sm text-gray-500">No medicines found.</p>
                    </td>
                </tr>`;
            return;
        }

        filtered.forEach(m => {
            // Null Safety
            const adminId = m.admin_id ? m.admin_id : '<span class="text-gray-300">-</span>';
            const createdRaw = m.created_at ? m.created_at : '';
            const createdDate = createdRaw ? new Date(createdRaw).toLocaleDateString() : '-';
            
            const price = parseFloat(m.unit_price).toFixed(2);
            const stock = parseInt(m.stock_quantity);

            // Logic for Badge
            let stockBadge = '';
            if(stock < 10) {
                stockBadge = `<span class="badge badge-low">Low: ${stock}</span>`;
            } else {
                stockBadge = `<span class="badge badge-good">In Stock: ${stock}</span>`;
            }

            tbody.innerHTML += `
                <tr class="group">
                    <td>
                        <div class="flex flex-col">
                            <span class="font-bold text-gray-800 text-sm group-hover:text-teal-600 transition">${m.medicine_name}</span>
                            <span class="text-xs text-gray-400 font-mono mt-1">${m.medicine_id}</span>
                            <span class="text-xs text-gray-500 mt-1">Exp: ${m.expiry_date}</span>
                        </div>
                    </td>
                    <td class="align-middle">
                        ${stockBadge}
                    </td>
                    <td class="align-middle font-semibold text-gray-700">
                        RM ${price}
                    </td>
                    <td class="align-middle">
                        <div class="flex flex-col text-xs text-gray-500">
                            <span><i class="far fa-calendar-alt mr-1"></i> ${createdDate}</span>
                            <span class="mt-1"><i class="far fa-user mr-1"></i> ${adminId}</span>
                        </div>
                    </td>
                    <td class="align-middle text-center">
                        <button onclick="editMedicine('${m.medicine_id}')" class="action-btn-minimal edit" title="Edit">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button onclick="deleteMedicine('${m.medicine_id}')" class="action-btn-minimal delete" title="Delete">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </td>
                </tr>`;
        });
    }

    /* ================= FORM HANDLER ================= */
    document.getElementById('medicineForm').addEventListener('submit', async e => {
        e.preventDefault();

        // Validation
        const stock = document.getElementById('stock').value;
        const price = document.getElementById('unitPrice').value;

        if(stock < 0 || price < 0) {
            alert("Stock and Price cannot be negative.");
            return;
        }

        const payload = {
            action: document.getElementById('medicineId').value ? 'update' : 'create',
            id: document.getElementById('medicineId').value,
            name: document.getElementById('name').value,
            stock: stock,
            expiryDate: document.getElementById('expiryDate').value,
            unitPrice: price,
            dosage: document.getElementById('dosage').value,
            admin_id: document.getElementById('admin_id_input').value
        };

        // Button Loading State
        const btn = e.target.querySelector('button[type="submit"]');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        btn.disabled = true;
        btn.classList.add('opacity-70', 'cursor-not-allowed');

        try {
            const res = await fetch('../backend/medicinedetails_controller.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(payload)
            });
            
            const result = await res.json();
            
            if(result.status === 'success') {
                resetForm();
                fetchInventory();
                
                // Success feedback
                const successMsg = document.createElement('div');
                successMsg.className = 'fixed bottom-5 right-5 bg-teal-600 text-white px-6 py-3 rounded-lg shadow-lg fade-in';
                successMsg.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Saved successfully!';
                document.body.appendChild(successMsg);
                setTimeout(() => successMsg.remove(), 3000);
            } else {
                alert("Error: " + result.message);
            }
        } catch (err) {
            console.error(err);
            alert("An error occurred while saving.");
        } finally {
            btn.innerHTML = originalContent;
            btn.disabled = false;
            btn.classList.remove('opacity-70', 'cursor-not-allowed');
        }
    });

    /* ================= ACTIONS ================= */
    function editMedicine(id) {
        const m = medicineInventory.find(x => x.medicine_id === id);
        if(!m) return;

        document.getElementById('medicineId').value = m.medicine_id;
        document.getElementById('name').value = m.medicine_name;
        document.getElementById('stock').value = m.stock_quantity;
        document.getElementById('expiryDate').value = m.expiry_date;
        document.getElementById('unitPrice').value = m.unit_price;
        document.getElementById('dosage').value = m.dosage_instructions;

        // Smooth scroll to form
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        // Highlight form
        const formCard = document.querySelector('.glass-card');
        formCard.classList.add('ring-2', 'ring-teal-400');
        setTimeout(() => formCard.classList.remove('ring-2', 'ring-teal-400'), 1000);
    }

    async function deleteMedicine(id) {
        if (!confirm('Are you sure you want to delete this record?')) return;
        
        await fetch('../backend/medicinedetails_controller.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'delete', id: id })
        });
        fetchInventory();
    }

    function resetForm() {
        document.getElementById('medicineForm').reset();
        document.getElementById('medicineId').value = '';
    }

    // Init
    document.addEventListener('DOMContentLoaded', fetchInventory);
</script>

</body>
</html>