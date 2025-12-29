<?php
session_start();

$vetID = $_SESSION['vetID'];             
$appointmentID = $_GET['appointment_id'];


require_once "../backend/treatment_controller.php";

// Fetch Pet Info from MariaDB (snake_case)
$petInfo = ['pet_id' => '-', 'owner_id' => '-'];
if (function_exists('getAppointmentByIdMaria')) {
    $data = getAppointmentByIdMaria($appointmentID);
    if($data) $petInfo = $data;
}

include "../frontend/vetheader.php";
?>

<script src="https://cdn.tailwindcss.com"></script>
<script> tailwind.config = { corePlugins: { preflight: false } } </script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
    .main-content-wrapper { margin-top: 80px; font-family: 'Poppins', sans-serif; background-color: #f4f6f8; min-height: 80vh; }
    :root { --primary-color: #00798C; --secondary-color: #D1EAEF; }
    .custom-header-bg { background-color: white; color: var(--primary-color); padding: 2.5rem 0; margin-bottom: 2rem; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); border-bottom: 3px solid var(--primary-color); }
    .custom-card { box-shadow: 0 0 20px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb; background: white; }
    .success-message { background-color: var(--secondary-color); color: #055a64; border-left: 5px solid var(--primary-color); padding: 1rem; margin-bottom: 1.5rem; }
    .error-message { background-color: #f8d7da; color: #721c24; border-left: 5px solid #dc3545; padding: 1rem; margin-bottom: 1.5rem; }
    input, select, textarea { display: block; width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; }
    input:focus, select:focus, textarea:focus { border-color: var(--primary-color); outline: none; }
    input:read-only { background-color: #f3f4f6; color: #6b7280; cursor: not-allowed; }
    .table thead tr th { background-color: var(--secondary-color) !important; color: var(--primary-color) !important; font-weight: 700; }
</style>

<main class="main-content-wrapper pb-10">

    <div class="custom-header-bg">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl md:text-4xl font-bold" style="color: var(--primary-color);">Vet Clinic Treatment Portal</h1>
            <p class="mt-1 text-lg" style="color: #6b7280;">Logged in as Vet: <strong><?php echo htmlspecialchars($vetID); ?></strong>
            <?php if(isset($vetName)): ?>
                (<?php echo htmlspecialchars(urldecode($vetName)); ?>)
            <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="custom-card p-4 rounded-lg mb-6 bg-white">
            <h3 class="text-lg font-bold mb-3" style="color: var(--primary-color);">Patient Information</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div><span class="font-bold">Appt ID:</span> <?php echo $appointmentID; ?></div>
                <div><span class="font-bold">Pet ID:</span> <?php echo $petInfo['pet_id']; ?></div>
                <div><span class="font-bold">Owner ID:</span> <?php echo $petInfo['owner_id']; ?></div>
                <div><span class="font-bold">Service:</span> <?php echo $petInfo['service_id'] ?? '-'; ?></div>
            </div>
        </div>

        <?php if ($insert_success): ?>
            <div class="success-message rounded-md font-semibold flex flex-wrap justify-between items-center gap-4">
                <span>Treatment record added successfully! Total fee includes medicine cost.</span>
                <a href="http://10.48.74.197/vet_clinic/frontend/payment_gateway.php?treatment_id=<?php echo $_GET['treatment_id']; ?>&vet_id=<?php echo $vetID; ?>" 
                   class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded shadow">
                   Proceed to Payment (Tya)
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ($insert_error) echo '<div class="error-message rounded-md font-semibold">Insertion Failed: ' . htmlspecialchars($insert_error) . '</div>'; ?>

        <div class="custom-card p-6 md:p-8 rounded-lg mb-10">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 border-b-2 pb-3" style="color: var(--primary-color); border-color: var(--secondary-color);">Add New Treatment</h2>
            
            <form action="" method="POST" id="treatmentForm">
                <input type="hidden" name="petID" value="<?php echo $petInfo['pet_id']; ?>">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <input type="hidden" name="treatmentID" value="<?php echo $nextTreatmentID; ?>">
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Appointment ID</label>
                        <input type="text" name="appointmentID" value="<?php echo $appointmentID; ?>" readonly>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vet ID</label>
                        <input type="text" name="vetID" value="<?php echo $vetID; ?>" readonly>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="treatmentStatus" required>
                            <option value="Pending">Pending</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Deceased" class="text-red-600 font-bold">Deceased (Pet Died)</option>
                        </select>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="treatmentDate" required>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Base Fee (RM)</label>
                        <input type="number" name="treatmentFee" id="baseFee" step="0.01" placeholder="50.00" value="0.00" required>
                    </div>
                    <div class="lg:col-span-2">
    <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis</label>
    
    <select id="diagnosisSelect" class="block w-full p-2 border border-gray-300 rounded-md mb-2 focus:ring-teal-500 focus:border-teal-500">
        <option value="">-- Select Common Diagnosis --</option>
        <option value="General Checkup / Healthy">General Checkup / Healthy</option>
        <option value="Vaccination">Vaccination</option>
        <option value="Gastroenteritis (Vomiting/Diarrhea)">Gastroenteritis (Vomiting/Diarrhea)</option>
        <option value="Dermatitis (Skin Infection)">Dermatitis (Skin Infection)</option>
        <option value="Otitis (Ear Infection)">Otitis (Ear Infection)</option>
        <option value="Conjunctivitis (Eye Infection)">Conjunctivitis (Eye Infection)</option>
        <option value="Physical Trauma / Wound">Physical Trauma / Wound</option>
        <option value="Bone Fracture">Bone Fracture</option>
        <option value="Parasitic Infection (Fleas/Ticks)">Parasitic Infection (Fleas/Ticks)</option>
        <option value="Dental Disease">Dental Disease</option>
        <option value="Respiratory Infection (Flu)">Respiratory Infection (Flu)</option>
        <option value="Urinary Tract Infection (UTI)">Urinary Tract Infection (UTI)</option>
        <option value="Other">Other (Type Manually)</option>
    </select>

    <input type="text" name="diagnosis" id="diagnosisInput" 
           placeholder="Please type the specific diagnosis..." 
           class="hidden w-full p-2 border border-gray-300 rounded-md focus:ring-teal-500 focus:border-teal-500">
</div>
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description / Procedure</label>
                        <textarea name="treatmentDescription" rows="3" required placeholder="Detailed notes..."></textarea>
                    </div>
                </div>
                
                <div class="mt-8 border-t pt-6" style="border-color: var(--secondary-color);">
                    <h3 class="text-lg font-bold text-gray-800 mb-4" style="color: var(--primary-color);">Medicine Dispensed</h3>
                    <div id="medicine-details-container" class="space-y-4"></div>
                    <button type="button" id="addMedicineBtn" style="background-color: var(--primary-color);" class="mt-4 flex items-center justify-center space-x-2 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:opacity-90 transition duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                        <span>Add Medicine</span>
                    </button>
                    <div class="mt-6 p-4 rounded-md lg:col-span-4" style="background-color: var(--secondary-color); border: 1px solid var(--primary-color);">
                        <p class="font-bold" style="color: var(--primary-color);">Total Fee (Base + Medicine): RM <span id="total-fee-display">0.00</span></p>
                    </div>
                </div>
                <div class="lg:col-span-4 mt-6">
                    <button type="submit" style="background-color: var(--primary-color);" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-lg font-semibold text-white hover:opacity-90 transition duration-300">
                        Add Treatment Record
                    </button>
                </div>
            </form>
        </div>

        <div class="custom-card p-6 md:p-8 rounded-lg mb-8">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 border-b-2 pb-3 flex justify-between items-center" style="color: var(--primary-color); border-color: var(--secondary-color);">
                <span>Existing Treatments (<?php echo $total_rows; ?> Records)</span>
                <button type="button" id="toggleTreatmentsBtn" class="text-white font-semibold py-1 px-3 rounded-lg shadow-md hover:opacity-90 transition duration-300 text-sm" style="background-color: var(--primary-color);">
                    <span id="toggleText">Minimize</span>
                    <svg id="toggleIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 inline-block ml-1 transition-transform transform rotate-180">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" />
                    </svg>
                </button>
            </h2>
            
            <div id="treatmentListContent" class="space-y-4">
                <div class="flex justify-end mb-4">
                    <form method="GET" action="" class="flex items-center space-x-3">
                        <button type="submit" style="background-color: var(--primary-color);" class="text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:opacity-90 transition duration-300 text-sm">Apply Sort</button>
                    </form>
                </div>
                
                <?php if ($has_records): ?>
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 border table">
                    <thead class="bg-blue-50"><tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Diagnosis / Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Total Fee</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Vet</th>
                    </tr></thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                    <?php foreach ($treatments as $row): 
                        // UPDATED TO SNAKE_CASE KEYS FROM DB
                        $status_class = match ($row['treatment_status']) {
                            'Completed' => 'bg-green-100 text-green-700 ring-1 ring-green-600/20',
                            'In Progress' => 'bg-blue-100 text-blue-700 ring-1 ring-blue-600/20',
                            'Pending' => 'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-600/20',
                            default => 'bg-gray-100 text-gray-700 ring-1 ring-gray-600/20',
                        };
                        $diag = !empty($row['diagnosis']) ? $row['diagnosis'] : $row['treatment_description'];
                    ?>
                        <tr>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['treatment_id']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['treatment_date']); ?></td>
                            <td class="px-6 py-4"><span class="px-3 py-1 inline-flex text-xs font-semibold rounded-full <?php echo $status_class; ?>"><?php echo htmlspecialchars($row['treatment_status']); ?></span></td>
                            <td class="px-6 py-4 text-sm text-gray-600 truncate max-w-xs"><?php echo htmlspecialchars($diag); ?></td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-900">RM <?php echo number_format($row['treatment_fee'], 2); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($row['vet_id']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table></div>
                    
                    <?php else: ?>
                    <p class="text-gray-500 py-4">No treatments found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const medicineContainer = document.getElementById('medicine-details-container');
        const addMedicineBtn = document.getElementById('addMedicineBtn');
        const baseFeeInput = document.getElementById('baseFee');
        const totalFeeDisplay = document.getElementById('total-fee-display');
        let medicineCounter = 0;
        
        // Pass PHP array to JS safely
        const medicines = <?php echo json_encode($medicine_options); ?>;
        
        function createMedicineRow() {
            const rowId = `med-row-${medicineCounter++}`;
            let optionsHtml = '<option value="" data-price="0.00">Select Medicine</option>';
            // UPDATED JS TO USE SNAKE_CASE
            medicines.forEach(med => {
                optionsHtml += `<option value="${med.medicine_id}" data-price="${parseFloat(med.unit_price).toFixed(2)}">${med.medicine_name} (RM ${parseFloat(med.unit_price).toFixed(2)} / unit)</option>`;
            });

            const row = document.createElement('div');
            row.id = rowId;
            row.className = 'grid grid-cols-1 sm:grid-cols-12 gap-2 p-4 border border-gray-200 rounded-md bg-white shadow-sm';
            row.innerHTML = `
                <div class="col-span-12 sm:col-span-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Medicine Name</label>
                    <select class="medicine-select" name="medicineID[]" required>${optionsHtml}</select>
                </div>
                <div class="col-span-6 sm:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Qty Used</label>
                    <input type="number" name="quantityUsed[]" class="quantity-input" min="1" value="1" required>
                </div>
                <div class="col-span-6 sm:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Dosage</label>
                    <input type="text" name="dosage[]" placeholder="e.g. 5ml or 2 tablets" required>
                </div>
                <div class="col-span-10 sm:col-span-2 flex items-center pt-2 sm:pt-0">
                    <p class="text-sm font-semibold text-gray-800">Cost: RM <span class="subtotal-display">0.00</span></p>
                </div>
                <div class="col-span-2 sm:col-span-1 flex justify-end items-end">
                    <button type="button" class="remove-medicine-btn text-red-600 hover:text-red-800 font-bold py-1 px-2 rounded">&times;</button>
                </div>
                <div class="col-span-12">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Instruction</label>
                    <input type="text" name="instruction[]" placeholder="Instruction to the owner" required>
                </div>
            `;
            
            const selectElement = row.querySelector('.medicine-select');
            const quantityInput = row.querySelector('.quantity-input');
            const removeButton = row.querySelector('.remove-medicine-btn');

            selectElement.addEventListener('change', updateRowCalculation);
            quantityInput.addEventListener('input', updateRowCalculation);
            removeButton.addEventListener('click', () => { row.remove(); calculateTotalFee(); });

            // Initialize this row's calc
            updateRowCalculation.call(selectElement);
            medicineContainer.appendChild(row);
        }

        function updateRowCalculation() {
            const row = this.closest('.grid');
            const select = row.querySelector('.medicine-select');
            const quantity = row.querySelector('.quantity-input').value;
            const subtotalDisplay = row.querySelector('.subtotal-display');
            
            const selectedOption = select.options[select.selectedIndex];
            // Safe fallback if data-price is missing
            const unitPrice = parseFloat(selectedOption.getAttribute('data-price')) || 0.00; 
            const subtotal = unitPrice * parseInt(quantity || 0);
            
            subtotalDisplay.textContent = subtotal.toFixed(2);
            calculateTotalFee();
        }

        function calculateTotalFee() {
            let totalMedicineCost = 0.00;
            document.querySelectorAll('.subtotal-display').forEach(span => {
                totalMedicineCost += parseFloat(span.textContent) || 0;
            });
            const baseFee = parseFloat(baseFeeInput.value) || 0.00; 
            const finalTotal = baseFee + totalMedicineCost;
            totalFeeDisplay.textContent = finalTotal.toFixed(2);
        }
        
        // Listeners
        addMedicineBtn.addEventListener('click', createMedicineRow);
        baseFeeInput.addEventListener('input', calculateTotalFee);
        
        // Run once on load
        calculateTotalFee();
        
        // Toggle Logic for List
        const toggleButton = document.getElementById('toggleTreatmentsBtn');
        const treatmentContent = document.getElementById('treatmentListContent');
        const toggleText = document.getElementById('toggleText');
        const toggleIcon = document.getElementById('toggleIcon');
        let isExpanded = true; 
        
        if (toggleButton) {
            toggleButton.addEventListener('click', () => {
                isExpanded = !isExpanded;
                if (isExpanded) {
                    treatmentContent.style.display = 'block';
                    toggleText.textContent = 'Minimize';
                    toggleIcon.classList.remove('rotate-0'); 
                    toggleIcon.classList.add('rotate-180');
                } else {
                    treatmentContent.style.display = 'none';
                    toggleText.textContent = 'Expand';
                    toggleIcon.classList.remove('rotate-180');
                    toggleIcon.classList.add('rotate-0'); 
                }
            });
        }
        // --- Diagnosis Dropdown Logic ---
    const diagSelect = document.getElementById('diagnosisSelect');
    const diagInput = document.getElementById('diagnosisInput');

    if (diagSelect && diagInput) {
        diagSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                // If 'Other', show the text box and clear it for typing
                diagInput.classList.remove('hidden');
                diagInput.style.display = 'block'; // Ensure it's visible if Tailwind 'hidden' conflicts
                diagInput.value = '';
                diagInput.focus();
            } else {
                // Otherwise, hide text box and copy the selection value into it
                diagInput.classList.add('hidden');
                diagInput.style.display = 'none';
                diagInput.value = this.value;
            }
        });
    }
    });
</script>

<?php include 'footer.php'; ?>