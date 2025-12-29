<?php
// frontend/medicinedetails.php
session_start();

/* ================= AUTH ================= */
if (isset($_GET['admin_id'])) {
    $_SESSION['adminID'] = trim($_GET['admin_id']);
}

if (!isset($_SESSION['adminID'])) {
    die("Unauthorized");
}

include "../frontend/adminheader.php";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <br><br><br><br>
    <meta charset="UTF-8">
    <title>Medicine Invetory Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

        :root {
            --primary-color: #00798C;
            --secondary-color: #D1EAEF;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f8;
        }

        .card {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e7eb;
        }

        input:focus {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 3px rgba(0, 121, 140, 0.15) !important;
        }

        th {
            background-color: var(--secondary-color);
            color: var(--primary-color);
        }
    </style>
</head>

<body>

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <div class="mb-8 border-b pb-4">
            <h1 class="text-3xl font-bold" style="color:var(--primary-color);">
                Medicine Inventory
            </h1>
            <p class="text-gray-500 mt-1">
                Manage stock, prices, and medicine details
            </p>
        </div>

        <div id="notification"
            class="hidden fixed top-5 right-5 z-50 p-4 rounded-lg shadow-lg font-semibold text-sm">
        </div>

        <div class="card bg-white p-6 rounded-lg mb-10" id="formCard">
            <div class="flex justify-between items-center border-b pb-3 mb-6">
                <h2 class="text-xl font-bold" id="formTitle" style="color:var(--primary-color);">
                    Add New Medicine
                </h2>
                <button onclick="resetForm()" class="text-sm text-gray-500 hover:text-red-600">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>

            <form id="medicineForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <input type="hidden" id="medicineId">
                <input type="hidden" id="admin_id_input" value="<?php echo $_SESSION['adminID']; ?>">

                <div>
                    <label class="text-sm font-medium">Medicine Name</label>
                    <input type="text" id="name" class="w-full p-3 border rounded-md" required>
                </div>

                <div>
                    <label class="text-sm font-medium">Stock Quantity</label>
                    <input type="number" id="stock" class="w-full p-3 border rounded-md" min="0" required>
                </div>

                <div>
                    <label class="text-sm font-medium">Expiry Date</label>
                    <input type="date" id="expiryDate" class="w-full p-3 border rounded-md" required>
                </div>

                <div>
                    <label class="text-sm font-medium">Unit Price (RM)</label>
                    <input type="number" step="0.01" id="unitPrice" class="w-full p-3 border rounded-md" required>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-medium">Dosage Instruction</label>
                    <input type="text" id="dosage" class="w-full p-3 border rounded-md" required>
                </div>

                <div class="md:col-span-2">
                    <button type="submit"
                        class="w-full py-3 rounded-lg text-white font-semibold"
                        style="background-color:var(--primary-color);">
                        <i class="fas fa-plus mr-2"></i> Save Medicine
                    </button>
                </div>
            </form>
        </div>

        <div class="card bg-white p-6 rounded-lg">
            <div class="flex justify-between mb-4">
                <h2 class="text-xl font-bold" style="color:var(--primary-color);">
                    Current Inventory
                </h2>
                <input type="text" id="searchInput"
                    placeholder="Search..."
                    class="p-2 border rounded-md text-sm"
                    onkeyup="renderMedicineList()">
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Stock</th>
                            <th class="px-4 py-2 text-left">Expiry</th>
                            <th class="px-4 py-2 text-left">Price</th>
                            <th class="px-4 py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="medicineTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-6 text-gray-500">
                                Loading data...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
        let medicineInventory = [];

        /* ================= FETCH ================= */
        async function fetchInventory() {
            const res = await fetch('../backend/medicinedetails_controller.php?action=read');
            const json = await res.json();
            medicineInventory = json.data || [];
            renderMedicineList();
        }

        /* ================= RENDER ================= */
        function renderMedicineList() {
            const tbody = document.getElementById('medicineTableBody');
            const term = document.getElementById('searchInput').value.toLowerCase();
            tbody.innerHTML = '';

            const filtered = medicineInventory.filter(m =>
                m.medicine_name.toLowerCase().includes(term) || // SNAKE_CASE
                m.medicine_id.toLowerCase().includes(term)      // SNAKE_CASE
            );

            if (!filtered.length) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4">No data</td></tr>`;
                return;
            }

            filtered.forEach(m => {
                // NOTE: Using snake_case keys from DB
                tbody.innerHTML += `
        <tr class="hover:bg-gray-50">
            <td class="px-4 py-2 text-xs font-mono">${m.medicine_id}</td>
            <td class="px-4 py-2">${m.medicine_name}</td>
            <td class="px-4 py-2">${m.stock_quantity}</td>
            <td class="px-4 py-2">${m.expiry_date}</td>
            <td class="px-4 py-2">RM ${parseFloat(m.unit_price).toFixed(2)}</td>
            <td class="px-4 py-2 text-center">
                <button onclick="editMedicine('${m.medicine_id}')" class="text-teal-600 mr-3">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteMedicine('${m.medicine_id}')" class="text-red-600">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
            });
        }

        /* ================= SAVE ================= */
        document.getElementById('medicineForm').addEventListener('submit', async e => {
            e.preventDefault();

            const payload = {
                action: document.getElementById('medicineId').value ? 'update' : 'create',
                id: document.getElementById('medicineId').value,
                name: document.getElementById('name').value,
                stock: document.getElementById('stock').value,
                expiryDate: document.getElementById('expiryDate').value,
                unitPrice: document.getElementById('unitPrice').value,
                dosage: document.getElementById('dosage').value,
                // Pass admin ID
                admin_id: document.getElementById('admin_id_input').value
            };

            await fetch('../backend/medicinedetails_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            resetForm();
            fetchInventory();
        });

        /* ================= HELPERS ================= */
        function editMedicine(id) {
            // SNAKE_CASE
            const m = medicineInventory.find(x => x.medicine_id === id);
            document.getElementById('medicineId').value = m.medicine_id;
            document.getElementById('name').value = m.medicine_name;
            document.getElementById('stock').value = m.stock_quantity;
            document.getElementById('expiryDate').value = m.expiry_date;
            document.getElementById('unitPrice').value = m.unit_price;
            document.getElementById('dosage').value = m.dosage_instructions;
        }

        async function deleteMedicine(id) {
            if (!confirm('Delete ' + id + '?')) return;
            await fetch('../backend/medicinedetails_controller.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    id
                })
            });
            fetchInventory();
        }

        function resetForm() {
            document.getElementById('medicineForm').reset();
            document.getElementById('medicineId').value = '';
        }

        document.addEventListener('DOMContentLoaded', fetchInventory);
    </script>

</body>

</html>