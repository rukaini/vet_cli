<?php

session_start();

$vetID = $_SESSION['vetID'];             
$appointmentID = $_GET['appointment_id'];
$vetName = $_SESSION['vetName'] ?? null;

require_once "../backend/treatment_controller.php";

// If vetName is empty OR it is just the ID (fallback from controller), fetch the real name
if (empty($vetName) || $vetName == $vetID) {
    if (function_exists('getVetByIdPG')) {
        $vetData = getVetByIdPG($vetID); // Calls your PostgreSQL function
        if ($vetData && isset($vetData['vet_name'])) {
            $vetName = $vetData['vet_name'];
            $_SESSION['vetName'] = $vetName; 
            $_SESSION['vetname'] = $vetName; 
        }
    }
}

// Fetch Pet Info from MariaDB
$petInfo = ['pet_id' => '-', 'owner_id' => '-', 'service_id' => '-'];
if (function_exists('getAppointmentByIdMaria')) {
    $data = getAppointmentByIdMaria($appointmentID);
    if ($data) $petInfo = $data;
}

// =========================
// FETCH NAMES FOR OVERVIEW
// =========================
$petName = '-';
$ownerName = '-';
$serviceName = '-';

// Pet Name (PostgreSQL)
if (!empty($petInfo['pet_id']) && function_exists('getPetByIdPG')) {
    $petData = getPetByIdPG($petInfo['pet_id']);
    if ($petData && isset($petData['pet_name'])) {
        $petName = $petData['pet_name'];
    }
}

// Owner Name (PostgreSQL)
if (!empty($petInfo['owner_id']) && function_exists('getOwnerByIdPG')) {
    $ownerData = getOwnerByIdPG($petInfo['owner_id']);
    if ($ownerData && isset($ownerData['owner_name'])) {
        $ownerName = $ownerData['owner_name'];
    }
}

// Service Name (Mapping)
$serviceMapping = [
    'SV001' => 'General Checkup',
    'SV002' => 'Deworming',
    'SV003' => 'Vaccination',
    'SV004' => 'Surgery Consultation',
    'SV005' => 'Flea & Tick Treatment',
    'SV006' => 'Dental Checkup',
    'SV007' => 'Ear & Eye Examination',
    'SV008' => 'Skin & Allergy Treatment',
    'SV009' => 'Minor Wound Treatment',
];

if (!empty($petInfo['service_id']) && isset($serviceMapping[$petInfo['service_id']])) {
    $serviceName = $serviceMapping[$petInfo['service_id']];
}

// Determine the default diagnosis value from the appointment
$preSelectedService = '';
if (isset($petInfo['service_id']) && isset($serviceMapping[$petInfo['service_id']])) {
    $preSelectedService = $serviceMapping[$petInfo['service_id']];
}
// -------------------------------

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
                <p class="mt-1 text-lg" style="color: #6b7280;">
                    Logged in as Vet: <strong><?php echo htmlspecialchars($vetID); ?></strong>
                    
                    <?php if (!empty($vetName) && $vetName !== $vetID): ?>
                        (<?php echo htmlspecialchars($vetName); ?>)
                    <?php endif; ?>
                </p>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        
       <div class="custom-card p-6 rounded-xl mb-8 bg-white border border-gray-100 shadow-sm transition-shadow hover:shadow-md">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400">
                    Patient Overview
                </h3>
                <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i> Info
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
                <div class="flex items-center space-x-4 group">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-teal-50 flex items-center justify-center text-teal-600 group-hover:bg-teal-100 transition-colors">
                        <i class="fa-regular fa-calendar-check text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Appt ID</p>
                        <p class="text-lg font-bold text-gray-800 tracking-tight font-mono">
                            <?php echo htmlspecialchars($appointmentID); ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 group">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center text-orange-500 group-hover:bg-orange-100 transition-colors">
                        <i class="fa-solid fa-paw text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Pet ID</p>
                        <p class="text-lg font-bold text-gray-800 tracking-tight font-mono">
                            <?php echo htmlspecialchars($petInfo['pet_id']); ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 group">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-500 group-hover:bg-blue-100 transition-colors">
                        <i class="fa-regular fa-user text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Owner ID</p>
                        <p class="text-lg font-bold text-gray-800 tracking-tight font-mono">
                            <?php echo htmlspecialchars($petInfo['owner_id']); ?>
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-4 group">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-purple-50 flex items-center justify-center text-purple-500 group-hover:bg-purple-100 transition-colors">
                        <i class="fa-solid fa-stethoscope text-lg"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Service</p>
                        <p class="text-lg font-bold text-gray-800 tracking-tight font-mono">
                            <?php echo htmlspecialchars($serviceName); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($insert_success): ?>
            <div class="success-message rounded-md font-semibold flex flex-wrap justify-between items-center gap-4">
                <span>Treatment record added successfully! Total fee includes medicine cost.</span>
                <a href="http://10.48.74.197/vetclinic/backend/paymentinsert_controller.php?treatment_id=<?php echo $_GET['treatment_id']; ?>&vet_id=<?php echo $vetID; ?>&owner_id=<?php echo $petInfo['owner_id']; ?>" 
                   class="bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded shadow">
                   Proceed to Payment 
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ($insert_error) echo '<div class="error-message rounded-md font-semibold">Insertion Failed: ' . htmlspecialchars($insert_error) . '</div>'; ?>

        <div class="custom-card p-6 md:p-8 rounded-lg mb-10">
            <h2 class="text-xl md:text-2xl font-bold text-gray-800 mb-6 border-b-2 pb-3" style="color: var(--primary-color); border-color: var(--secondary-color);">Add New Treatment</h2>
            
            <form action="" method="POST" id="treatmentForm">
                <input type="hidden" name="petID" value="<?php echo $petInfo['pet_id']; ?>">
                <input type="hidden" name="ownerID" value="<?php echo $petInfo['owner_id']; ?>">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
                    <input type="hidden" name="treatmentID" value="<?php echo $nextTreatmentID; ?>">
            
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
                        <input type="date" name="treatmentDate" value="<?php echo isset($petInfo['date']) ? $petInfo['date'] : date('Y-m-d'); ?>" required>
                    </div>
                    <div class="lg:col-span-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Consultation Fee (RM)</label>
                        <input type="number" name="treatmentFee" id="baseFee" step="0.01" placeholder="50.00" value="0.00" required>
                    </div>
                    
                    <div class="lg:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Diagnosis / Service Category</label>
                        
                       <select id="diagnosisSelect" name="diagnosisSelect" class="block w-full p-2 border border-gray-300 rounded-md mb-2 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">-- Select Diagnosis --</option>
                            
                            <optgroup label="General Services">
                                <option value="General Checkup" <?php if($preSelectedService == 'General Checkup') echo 'selected'; ?>>General Checkup</option>
                                <option value="Deworming" <?php if($preSelectedService == 'Deworming') echo 'selected'; ?>>Deworming</option>
                                <option value="Vaccination" <?php if($preSelectedService == 'Vaccination') echo 'selected'; ?>>Vaccination</option>
                                <option value="Surgery Consultation" <?php if($preSelectedService == 'Surgery Consultation') echo 'selected'; ?>>Surgery Consultation</option>
                                <option value="Flea & Tick Treatment" <?php if($preSelectedService == 'Flea & Tick Treatment') echo 'selected'; ?>>Flea & Tick Treatment</option>
                                <option value="Dental Checkup" <?php if($preSelectedService == 'Dental Checkup') echo 'selected'; ?>>Dental Checkup</option>
                                <option value="Ear & Eye Examination" <?php if($preSelectedService == 'Ear & Eye Examination') echo 'selected'; ?>>Ear & Eye Examination</option>
                                <option value="Skin & Allergy Treatment" <?php if($preSelectedService == 'Skin & Allergy Treatment') echo 'selected'; ?>>Skin & Allergy Treatment</option>
                                <option value="Minor Wound Treatment" <?php if($preSelectedService == 'Minor Wound Treatment') echo 'selected'; ?>>Minor Wound Treatment</option>
                            </optgroup>

                            <optgroup label="Common Diseases">
                                <option value="Gastroenteritis (Vomiting/Diarrhea)">Gastroenteritis (Vomiting/Diarrhea)</option>
                                <option value="Dermatitis (Skin Infection)">Dermatitis (Skin Infection)</option>
                                <option value="Otitis (Ear Infection)">Otitis (Ear Infection)</option>
                                <option value="Conjunctivitis (Eye Infection)">Conjunctivitis (Eye Infection)</option>
                                <option value="Physical Trauma / Wound">Physical Trauma / Wound</option>
                                <option value="Bone Fracture">Bone Fracture</option>
                                <option value="Urinary Tract Infection (UTI)">Urinary Tract Infection (UTI)</option>
                                <option value="Respiratory Infection (Flu)">Respiratory Infection (Flu)</option>
                                <option value="Parasitic Infection">Parasitic Infection</option>
                                <option value="Dental Disease">Dental Disease</option>
                            </optgroup>

                            <option value="Other">Other (Type Manually)</option>
                        </select>

                        <input type="text" name="diagnosis" id="diagnosisInput" 
                            value="<?php echo htmlspecialchars($preSelectedService ?? ''); ?>" 
                            placeholder="Please type the specific diagnosis..." 
                            class="hidden w-full p-2 border border-gray-300 rounded-md focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div class="lg:col-span-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description / Procedure</label>
                        <textarea name="treatmentDescription" rows="3" required placeholder="Detailed notes (e.g., administered Rabies vaccine, performed scaling)..."></textarea>
                    </div>
                </div>
                
                <div class="mt-8 border-t pt-6" style="border-color: var(--secondary-color);">
                    <h3 class="text-lg font-bold text-gray-800 mb-4" style="color: var(--primary-color);">Medicine & Extras</h3>
                    <div id="medicine-details-container" class="space-y-4"></div>
                    
                    <div class="flex flex-wrap gap-3 mt-4">
                        <button type="button" id="addMedicineBtn" style="background-color: var(--primary-color);" class="flex items-center justify-center space-x-2 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:opacity-90 transition duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            <span>Add Medicine</span>
                        </button>
                        
                        <button type="button" id="openFollowUpBtn" class="flex items-center justify-center space-x-2 text-white font-semibold py-2 px-4 rounded-lg shadow-md hover:opacity-90 transition duration-300" style="background-color: var(--primary-color);">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Schedule Follow-up</span>
                        </button>
                    </div>

                    <div class="mt-6 p-4 rounded-md lg:col-span-4" style="background-color: var(--secondary-color); border: 1px solid var(--primary-color);">
                        <p class="font-bold" style="color: var(--primary-color);">Total Fee (Consultation + Medicine): RM <span id="total-fee-display">0.00</span></p>
                    </div>
                </div>
                <div class="lg:col-span-4 mt-6">
                    <button type="submit" style="background-color: var(--primary-color);" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-lg text-lg font-semibold text-white hover:opacity-90 transition duration-300">
                        Add Treatment Record
                    </button>
                </div>
            
            <div id="vaccineModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="fixed inset-0 bg-gray-900/30 backdrop-blur-sm transition-opacity opacity-0" id="modalBackdrop"></div>

                <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                        
                        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl scale-95 opacity-0" id="modalPanel">
                            
                            <div class="absolute top-0 w-full h-2 bg-gradient-to-r from-teal-400 to-teal-600"></div>

                            <div class="bg-white px-4 pb-4 pt-5 sm:p-8 sm:pb-6">
                                <div class="sm:flex sm:items-start">
                                    <div class="mx-auto flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-full bg-teal-50 sm:mx-0 sm:h-12 sm:w-12">
                                        <i class="fas fa-calendar-check text-2xl text-teal-600"></i>
                                    </div>
                                    <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                        <h3 class="text-xl font-bold leading-6 text-gray-800" id="modal-title">Schedule Follow-up Appointment</h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">
                                                Please select the date and time for the next appointment. Sundays are closed.
                                            </p>
                                            
                                            <div class="mt-6 bg-gray-50 p-6 rounded-xl border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Next Appointment Date</label>
                                                    <input type="date" name="followUpDate" id="followUpDate" 
                                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm p-3 border">
                                                    <p class="text-xs text-red-500 mt-1 hidden" id="sundayError">Sundays are not available.</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Time (30 Min Intervals)</label>
                                                    <select name="followUpTime" id="followUpTime" 
                                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm p-3 border">
                                                        <option value="09:00">09:00 AM</option>
                                                        <option value="09:30">09:30 AM</option>
                                                        <option value="10:00">10:00 AM</option>
                                                        <option value="10:30">10:30 AM</option>
                                                        <option value="11:00">11:00 AM</option>
                                                        <option value="11:30">11:30 AM</option>
                                                        <option value="12:00">12:00 PM</option>
                                                        <option value="12:30">12:30 PM</option>
                                                        <option value="13:00">01:00 PM</option>
                                                        <option value="13:30">01:30 PM</option>
                                                        <option value="14:00">02:00 PM</option>
                                                        <option value="14:30">02:30 PM</option>
                                                        <option value="15:00">03:00 PM</option>
                                                        <option value="15:30">03:30 PM</option>
                                                        <option value="16:00">04:00 PM</option>
                                                        <option value="16:30">04:30 PM</option>
                                                        <option value="17:00">05:00 PM</option>
                                                        <option value="17:30">05:30 PM</option>
                                                        <option value="18:00">06:00 PM</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-100 gap-2">
                                <button type="button" id="confirmFollowUp" 
                                        class="inline-flex w-full justify-center rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-500 sm:ml-3 sm:w-auto transition-all">
                                    <i class="fas fa-calendar-check mr-2 mt-1"></i> Confirm Schedule
                                </button>
                                <button type="button" id="cancelFollowUp" 
                                        class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto transition-all">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
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
                
                <div class="flex justify-end mb-2">
                    <form method="GET" action="" class="flex items-center gap-3">
                        
                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointmentID); ?>">
                        
                        <label for="sort" class="text-xs font-bold uppercase tracking-widest text-gray-400">
                            Sort By
                        </label>
                        
                        <div class="relative group">
                            <select name="sort" onchange="this.form.submit()" 
                                    class="appearance-none bg-transparent hover:bg-gray-50 border border-gray-200 text-gray-600 font-medium py-2 pl-4 pr-10 rounded-full text-sm focus:outline-none focus:border-teal-500 focus:ring-1 focus:ring-teal-500 transition-all duration-300 cursor-pointer shadow-sm">
                                <option value="date_desc" <?php echo (isset($sort_by) && $sort_by == 'date_desc') ? 'selected' : ''; ?>>Date: Newest</option>
                                <option value="date_asc" <?php echo (isset($sort_by) && $sort_by == 'date_asc') ? 'selected' : ''; ?>>Date: Oldest</option>
                                <option value="id_desc" <?php echo (isset($sort_by) && $sort_by == 'id_desc') ? 'selected' : ''; ?>>ID: Descending</option>
                                <option value="id_asc" <?php echo (isset($sort_by) && $sort_by == 'id_asc') ? 'selected' : ''; ?>>ID: Ascending</option>
                            </select>
                            
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400 group-hover:text-teal-600 transition-colors duration-300">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                </svg>
                            </div>
                        </div>
                    </form>
                </div>
                
                <?php if ($has_records): ?>
                    <div class="overflow-hidden rounded-lg border border-gray-100 shadow-sm">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Diagnosis</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Fee</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Vet</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-50">
                            <?php foreach ($treatments as $row): 
                                $status_class = match ($row['treatment_status']) {
                                    'Completed' => 'bg-green-50 text-green-700 border border-green-100',
                                    'In Progress' => 'bg-blue-50 text-blue-700 border border-blue-100',
                                    'Pending' => 'bg-yellow-50 text-yellow-700 border border-yellow-100',
                                    'Deceased' => 'bg-red-50 text-red-700 border border-red-100',
                                    default => 'bg-gray-50 text-gray-600 border border-gray-100',
                                };
                                $diag = !empty($row['diagnosis']) ? $row['diagnosis'] : $row['treatment_description'];
                            ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 text-sm font-semibold text-teal-700"><?php echo htmlspecialchars($row['treatment_id']); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-600 font-mono"><?php echo date('d/m/Y', strtotime($row['treatment_date'])); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 inline-flex text-xs font-medium rounded-full <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($row['treatment_status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 truncate max-w-xs" title="<?php echo htmlspecialchars($row['treatment_description']); ?>">
                                        <?php echo htmlspecialchars($diag); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold text-gray-800 text-right">RM <?php echo number_format($row['treatment_fee'], 2); ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-500"><?php echo htmlspecialchars($row['vet_id']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                        <p class="text-gray-400 text-sm">No treatment history found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- DATA PASSED FROM PHP ---
        const vetID = "<?php echo $vetID; ?>";
        
        const medicineContainer = document.getElementById('medicine-details-container');
        const addMedicineBtn = document.getElementById('addMedicineBtn');
        const baseFeeInput = document.getElementById('baseFee');
        const totalFeeDisplay = document.getElementById('total-fee-display');
        let medicineCounter = 0;
        
        const medicines = <?php echo json_encode($medicine_options); ?>;
        
        function createMedicineRow() {
            const rowId = `med-row-${medicineCounter++}`;
            let optionsHtml = '<option value="" data-price="0.00" selected>Select Medicine</option>';
            
            medicines.forEach(med => {
                optionsHtml += `<option value="${med.medicine_id}" data-price="${parseFloat(med.unit_price).toFixed(2)}">${med.medicine_name} (RM ${parseFloat(med.unit_price).toFixed(2)} / unit)</option>`;
            });

            const row = document.createElement('div');
            row.id = rowId;
            row.className = 'grid grid-cols-1 sm:grid-cols-12 gap-2 p-4 border border-gray-200 rounded-md bg-white shadow-sm';
            
            row.innerHTML = `
                <div class="col-span-12 sm:col-span-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Medicine Name</label>
                    <select class="medicine-select" name="medicineID[]">${optionsHtml}</select>
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

            updateRowCalculation.call(selectElement);
            medicineContainer.appendChild(row);
        }

        function updateRowCalculation() {
            const row = this.closest('.grid');
            const select = row.querySelector('.medicine-select');
            const quantity = row.querySelector('.quantity-input').value;
            const subtotalDisplay = row.querySelector('.subtotal-display');
            
            const selectedOption = select.options[select.selectedIndex];
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
        
        if(addMedicineBtn) addMedicineBtn.addEventListener('click', createMedicineRow);
        baseFeeInput.addEventListener('input', calculateTotalFee);
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
        
    // --- VACCINATION / FOLLOW-UP MODAL LOGIC ---
    const diagnosisSelect = document.getElementById('diagnosisSelect');
    const modal = document.getElementById('vaccineModal');
    const backdrop = document.getElementById('modalBackdrop');
    const panel = document.getElementById('modalPanel');
    const confirmBtn = document.getElementById('confirmFollowUp');
    const cancelBtn = document.getElementById('cancelFollowUp');
    const dateInput = document.getElementById('followUpDate');
    const timeInput = document.getElementById('followUpTime');
    const sundayError = document.getElementById('sundayError');
    
    const openFollowUpBtn = document.getElementById('openFollowUpBtn');

    if (openFollowUpBtn) {
        openFollowUpBtn.addEventListener('click', openModal);
    }

    if (diagnosisSelect) {
        const diagInput = document.getElementById('diagnosisInput');

        diagnosisSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                diagInput.classList.remove('hidden');
                diagInput.style.display = 'block';
                diagInput.value = '';
                diagInput.focus();
            } else {
                diagInput.classList.add('hidden');
                diagInput.style.display = 'none';
                diagInput.value = this.value;
            }
        });
    }

    // --- CHECK AVAILABILITY LOGIC ---
    dateInput.addEventListener('change', function() {
        checkAvailability(this.value);
    });

    function checkAvailability(date) {
        if (!date) return;

        // Reset: Show all options first
        const options = timeInput.options;
        for (let i = 0; i < options.length; i++) {
            options[i].style.display = 'block';
            options[i].disabled = false;
        }

        // Handle Sunday check first
        const selectedDate = new Date(date);
        const day = selectedDate.getDay(); // 0 = Sunday
        if (day === 0) {
            dateInput.value = ''; 
            sundayError.classList.remove('hidden');
            alert('Sorry, the clinic is closed on Sundays. Please select another date.');
            return;
        } else {
            sundayError.classList.add('hidden');
        }

        // AJAX Request
        fetch(`../backend/check_availability.php?date=${date}&vet_id=${vetID}`)
            .then(response => response.json())
            .then(bookedTimes => {
                if (!Array.isArray(bookedTimes)) return;

                // Loop through options and hide if matched
                for (let i = 0; i < options.length; i++) {
                    const optValue = options[i].value; // e.g., "09:00"
                    
                    // Simple check: does the booked time start with this option value?
                    const isBooked = bookedTimes.some(booked => booked.startsWith(optValue));
                    
                    if (isBooked) {
                        options[i].style.display = 'none'; // Hide it
                        options[i].disabled = true;        // Disable it
                    }
                }
                
                if (timeInput.selectedOptions[0].disabled) {
                    timeInput.value = ""; 
                }
            })
            .catch(err => console.error("Error checking availability:", err));
    }

    function openModal() {
        modal.classList.remove('hidden');
        setTimeout(() => {
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('opacity-0', 'scale-95');
            panel.classList.add('opacity-100', 'scale-100');
        }, 10);

        if (!dateInput.value) {
            const today = new Date();
            today.setDate(today.getDate() + 14); 
            // Avoid defaulting to Sunday
            if (today.getDay() === 0) {
                today.setDate(today.getDate() + 1);
            }
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            dateInput.value = `${yyyy}-${mm}-${dd}`;
            
            checkAvailability(dateInput.value);
        } else {
            checkAvailability(dateInput.value);
        }
    }

    function closeModal() {
        backdrop.classList.add('opacity-0');
        panel.classList.remove('opacity-100', 'scale-100');
        panel.classList.add('opacity-0', 'scale-95');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    confirmBtn.addEventListener('click', function() {
        this.innerHTML = '<i class="fas fa-check mr-2"></i> Scheduled';
        this.classList.remove('bg-teal-600', 'hover:bg-teal-500');
        this.classList.add('bg-green-600', 'hover:bg-green-500');
        
        setTimeout(closeModal, 600);
    });

    cancelBtn.addEventListener('click', function() {
        dateInput.value = '';
        timeInput.value = '';
        closeModal();
    });

    });
</script>

<?php include 'footer.php'; ?>