<?php
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$doctorName = htmlspecialchars($doctor['title'] . ' ' . $doctor['first_name'] . ' ' . $doctor['last_name']);
?>

<style>
    .doctor-info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 16px;
        padding: 20px 25px;
        color: white;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .doctor-avatar {
        width: 70px;
        height: 70px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
    }
    .doctor-details {
        flex: 1;
    }
    .doctor-details h3 {
        margin: 0;
        font-size: 24px;
    }
    .doctor-meta {
        margin-top: 8px;
    }
    .doctor-meta .badge {
        font-size: 13px;
        padding: 6px 14px;
    }
    
    /* Schedule Table - Responsive */
    .schedule-table {
        font-size: 13px;
        width: 100%;
        border-collapse: collapse;
    }
    .schedule-table th, .schedule-table td {
        vertical-align: middle;
        padding: 8px 6px;
        text-align: center;
    }
    .schedule-table th {
        white-space: nowrap;
        font-size: 12px;
    }
    .schedule-table .day-name {
        background: #f8fafc;
        font-weight: 600;
        font-size: 14px;
        min-width: 80px;
        white-space: nowrap;
    }
    .schedule-table .day-name small {
        display: block;
        font-size: 10px;
        font-weight: normal;
    }
    .schedule-table input[type="time"],
    .schedule-table input[type="number"] {
        border-radius: 6px;
        border: 1px solid #e2e8f0;
        padding: 5px 8px;
        font-size: 13px;
        width: 100%;
        min-width: 70px;
        max-width: 120px;
        transition: all 0.2s;
    }
    .schedule-table input[type="time"]:focus,
    .schedule-table input[type="number"]:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.1);
        outline: none;
    }
    .schedule-table input:disabled {
        background: #f1f5f9;
        cursor: not-allowed;
        opacity: 0.6;
    }
    .schedule-table .form-check-input {
        width: 38px;
        height: 19px;
        cursor: pointer;
        margin: 0 auto;
    }
    .schedule-table .form-check-input:checked {
        background-color: #10b981;
        border-color: #10b981;
    }
    
    /* Column Widths */
    .col-day { width: 8%; min-width: 80px; }
    .col-active { width: 6%; min-width: 55px; }
    .col-time { width: 12%; min-width: 100px; }
    .col-slot { width: 9%; min-width: 80px; }
    .col-patients { width: 9%; min-width: 80px; }
    
    /* Session Labels */
    .session-label {
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: block;
    }
    .session-label.morning {
        color: #d97706;
    }
    .session-label.evening {
        color: #2563eb;
    }
    .session-sub-label {
        font-size: 9px;
        color: #64748b;
        display: block;
        font-weight: normal;
    }
    
    /* Status Indicators */
    .status-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 4px;
    }
    .status-active {
        background: #10b981;
    }
    .status-inactive {
        background: #94a3b8;
    }
    
    /* Schedule Actions */
    .schedule-actions {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        align-items: center;
        flex-wrap: wrap;
    }
    
    /* Header Styling */
    .header-morning {
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        color: #92400e;
        font-weight: 700;
        font-size: 14px;
    }
    .header-evening {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: #1e40af;
        font-weight: 700;
        font-size: 14px;
    }
    .header-main {
        background: #1e293b;
        color: white;
        font-weight: 700;
    }
    .header-sub {
        background: #f1f5f9;
        font-weight: 600;
        font-size: 11px;
        color: #475569;
    }
    
    /* Responsive - Large Screens */
    @media (min-width: 1400px) {
        .schedule-table {
            font-size: 14px;
        }
        .schedule-table th, .schedule-table td {
            padding: 10px 10px;
        }
        .schedule-table input[type="time"],
        .schedule-table input[type="number"] {
            font-size: 14px;
            padding: 6px 10px;
            min-width: 90px;
            max-width: 140px;
        }
        .schedule-table .form-check-input {
            width: 44px;
            height: 22px;
        }
        .col-day { min-width: 100px; }
        .col-time { min-width: 120px; }
    }
    
    /* Responsive - Medium Screens */
    @media (max-width: 1200px) {
        .schedule-table {
            font-size: 12px;
        }
        .schedule-table th, .schedule-table td {
            padding: 6px 4px;
        }
        .schedule-table input[type="time"],
        .schedule-table input[type="number"] {
            font-size: 12px;
            padding: 4px 6px;
            min-width: 60px;
            max-width: 100px;
        }
        .schedule-table .form-check-input {
            width: 34px;
            height: 17px;
        }
        .col-day { min-width: 65px; font-size: 12px; }
        .col-active { min-width: 45px; }
        .col-time { min-width: 80px; }
        .col-slot { min-width: 65px; }
        .col-patients { min-width: 65px; }
        .session-label { font-size: 10px; }
        .header-morning, .header-evening { font-size: 12px; }
        .schedule-table .day-name { font-size: 12px; min-width: 60px; }
    }
    
    /* Responsive - Tablet */
    @media (max-width: 992px) {
        .schedule-table {
            font-size: 11px;
        }
        .schedule-table th, .schedule-table td {
            padding: 5px 3px;
        }
        .schedule-table input[type="time"],
        .schedule-table input[type="number"] {
            font-size: 11px;
            padding: 3px 4px;
            min-width: 50px;
            max-width: 80px;
        }
        .schedule-table .form-check-input {
            width: 30px;
            height: 15px;
        }
        .col-day { min-width: 55px; font-size: 11px; }
        .col-active { min-width: 38px; }
        .col-time { min-width: 65px; }
        .col-slot { min-width: 55px; }
        .col-patients { min-width: 55px; }
        .session-label { font-size: 9px; }
        .session-sub-label { font-size: 8px; }
        .header-morning, .header-evening { font-size: 11px; }
        .schedule-table .day-name { font-size: 11px; min-width: 50px; }
        .schedule-table .day-name small { font-size: 9px; }
        .doctor-info-card {
            flex-direction: column;
            text-align: center;
        }
        .doctor-meta .badge {
            font-size: 11px;
            padding: 4px 10px;
        }
    }
    
    /* Responsive - Mobile */
    @media (max-width: 768px) {
        .schedule-table {
            font-size: 10px;
        }
        .schedule-table th, .schedule-table td {
            padding: 4px 2px;
        }
        .schedule-table input[type="time"],
        .schedule-table input[type="number"] {
            font-size: 10px;
            padding: 2px 3px;
            min-width: 40px;
            max-width: 65px;
        }
        .schedule-table .form-check-input {
            width: 26px;
            height: 13px;
        }
        .col-day { min-width: 45px; font-size: 10px; }
        .col-active { min-width: 32px; }
        .col-time { min-width: 50px; }
        .col-slot { min-width: 45px; }
        .col-patients { min-width: 45px; }
        .session-label { font-size: 8px; }
        .session-sub-label { font-size: 7px; }
        .header-morning, .header-evening { font-size: 10px; }
        .schedule-table .day-name { font-size: 10px; min-width: 40px; }
        .schedule-table .day-name small { font-size: 8px; }
        .schedule-table th .fas {
            font-size: 10px;
        }
        .schedule-actions {
            flex-direction: column;
            width: 100%;
        }
        .schedule-actions .btn {
            width: 100%;
            justify-content: center;
        }
        .doctor-info-card {
            padding: 15px;
        }
        .doctor-avatar {
            width: 50px;
            height: 50px;
            font-size: 24px;
        }
        .doctor-details h3 {
            font-size: 18px;
        }
    }
    
    /* Extra Small Mobile */
    @media (max-width: 576px) {
        .schedule-table {
            font-size: 9px;
        }
        .schedule-table th, .schedule-table td {
            padding: 3px 1px;
        }
        .schedule-table input[type="time"],
        .schedule-table input[type="number"] {
            font-size: 9px;
            padding: 2px 2px;
            min-width: 35px;
            max-width: 55px;
            border-radius: 4px;
        }
        .schedule-table .form-check-input {
            width: 22px;
            height: 11px;
        }
        .col-day { min-width: 38px; font-size: 9px; }
        .col-active { min-width: 28px; }
        .col-time { min-width: 42px; }
        .col-slot { min-width: 38px; }
        .col-patients { min-width: 38px; }
        .session-label { font-size: 7px; }
        .session-sub-label { font-size: 6px; }
        .header-morning, .header-evening { font-size: 9px; }
        .schedule-table .day-name { font-size: 9px; min-width: 35px; }
        .schedule-table .day-name small { font-size: 7px; }
        .schedule-table th .fas {
            font-size: 8px;
        }
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-clock me-2 text-primary"></i>Doctor Schedule</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/dashboard">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/doctor/list">Doctors</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/doctor/schedule-list">Schedules</a></li>
                    <li class="breadcrumb-item active"><?php echo $doctorName; ?></li>
                </ol>
            </nav>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/doctor/schedule-list" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Schedules
            </a>
        </div>
    </div>

    <!-- Doctor Info Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="doctor-info-card">
                <div class="doctor-avatar">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="doctor-details">
                    <h3 class="mb-1"><?php echo $doctorName; ?></h3>
                    <div class="doctor-meta">
                        <span class="badge bg-primary me-2">
                            <i class="fas fa-stethoscope me-1"></i> <?php echo htmlspecialchars($doctor['specialization']); ?>
                        </span>
                        <span class="badge bg-secondary me-2">
                            <i class="fas fa-id-card me-1"></i> <?php echo htmlspecialchars($doctor['bmdc_number']); ?>
                        </span>
                        <span class="badge bg-success">
                            <i class="fas fa-money-bill-wave me-1"></i> Consultation: ৳ <?php echo number_format($doctor['consultation_fee'], 2); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Form -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Weekly Schedule Configuration</h5>
            <span class="badge bg-light text-dark">
                <i class="fas fa-info-circle me-1"></i> Toggle sessions with checkboxes
            </span>
        </div>
        <div class="card-body">
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo BASE_URL; ?>/doctor/save-schedule/<?php echo $doctor['id']; ?>" id="scheduleForm">
                <div class="table-responsive">
                    <table class="table table-bordered schedule-table">
                        <thead>
                            <tr class="header-main">
                                <th rowspan="2" class="col-day" style="vertical-align: middle;">
                                    <i class="fas fa-calendar-day me-1"></i> Day
                                </th>
                                <th colspan="5" class="header-morning">
                                    <i class="fas fa-sun me-2"></i> Morning Session
                                </th>
                                <th colspan="5" class="header-evening">
                                    <i class="fas fa-moon me-2"></i> Evening Session
                                </th>
                            </tr>
                            <tr class="header-sub">
                                <th class="col-active"><i class="fas fa-check-circle"></i> Active</th>
                                <th class="col-time"><i class="fas fa-hourglass-start"></i> Start</th>
                                <th class="col-time"><i class="fas fa-hourglass-end"></i> End</th>
                                <th class="col-slot"><i class="fas fa-stopwatch"></i> Slot</th>
                                <th class="col-patients"><i class="fas fa-users"></i> Patients</th>
                                <th class="col-active"><i class="fas fa-check-circle"></i> Active</th>
                                <th class="col-time"><i class="fas fa-hourglass-start"></i> Start</th>
                                <th class="col-time"><i class="fas fa-hourglass-end"></i> End</th>
                                <th class="col-slot"><i class="fas fa-stopwatch"></i> Slot</th>
                                <th class="col-patients"><i class="fas fa-users"></i> Patients</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($days as $day): 
                                $morning = $sessionsByDay[$day]['morning'] ?? null;
                                $evening = $sessionsByDay[$day]['evening'] ?? null;
                                $hasMorning = !empty($morning);
                                $hasEvening = !empty($evening);
                            ?>
                            <tr class="schedule-row" id="row-<?php echo $day; ?>">
                                <td class="day-name text-center">
                                    <strong><?php echo $day; ?></strong>
                                    <small>
                                        <?php 
                                        $activeCount = 0;
                                        if($hasMorning) $activeCount++;
                                        if($hasEvening) $activeCount++;
                                        echo $activeCount > 0 ? '<span class="badge bg-success">' . $activeCount . ' active</span>' : '<span class="badge bg-secondary">Inactive</span>';
                                        ?>
                                    </small>
                                </td>
                                
                                <!-- Morning Session -->
                                <td class="col-active text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input type="checkbox" name="<?php echo $day; ?>_morning_active" class="form-check-input toggle-session" 
                                               value="1" data-day="<?php echo $day; ?>" data-session="morning"
                                               <?php echo $hasMorning ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td class="col-time">
                                    <input type="time" name="<?php echo $day; ?>_morning_start" class="form-control form-control-sm time-input" 
                                           value="<?php echo $hasMorning ? $morning['start_time'] : '09:00'; ?>"
                                           id="<?php echo $day; ?>_morning_start" <?php echo !$hasMorning ? 'disabled' : ''; ?>>
                                </td>
                                <td class="col-time">
                                    <input type="time" name="<?php echo $day; ?>_morning_end" class="form-control form-control-sm time-input" 
                                           value="<?php echo $hasMorning ? $morning['end_time'] : '13:00'; ?>"
                                           id="<?php echo $day; ?>_morning_end" <?php echo !$hasMorning ? 'disabled' : ''; ?>>
                                </td>
                                <td class="col-slot">
                                    <input type="number" name="<?php echo $day; ?>_morning_slot" class="form-control form-control-sm" 
                                           value="<?php echo $hasMorning ? $morning['slot_duration'] : 15; ?>" min="5" max="60" step="5">
                                </td>
                                <td class="col-patients">
                                    <input type="number" name="<?php echo $day; ?>_morning_patients" class="form-control form-control-sm" 
                                           value="<?php echo $hasMorning ? $morning['max_patients'] : 10; ?>" min="1" max="50">
                                </td>
                                
                                <!-- Evening Session -->
                                <td class="col-active text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input type="checkbox" name="<?php echo $day; ?>_evening_active" class="form-check-input toggle-session" 
                                               value="1" data-day="<?php echo $day; ?>" data-session="evening"
                                               <?php echo $hasEvening ? 'checked' : ''; ?>>
                                    </div>
                                </td>
                                <td class="col-time">
                                    <input type="time" name="<?php echo $day; ?>_evening_start" class="form-control form-control-sm time-input" 
                                           value="<?php echo $hasEvening ? $evening['start_time'] : '16:00'; ?>"
                                           id="<?php echo $day; ?>_evening_start" <?php echo !$hasEvening ? 'disabled' : ''; ?>>
                                </td>
                                <td class="col-time">
                                    <input type="time" name="<?php echo $day; ?>_evening_end" class="form-control form-control-sm time-input" 
                                           value="<?php echo $hasEvening ? $evening['end_time'] : '20:00'; ?>"
                                           id="<?php echo $day; ?>_evening_end" <?php echo !$hasEvening ? 'disabled' : ''; ?>>
                                </td>
                                <td class="col-slot">
                                    <input type="number" name="<?php echo $day; ?>_evening_slot" class="form-control form-control-sm" 
                                           value="<?php echo $hasEvening ? $evening['slot_duration'] : 15; ?>" min="5" max="60" step="5">
                                </td>
                                <td class="col-patients">
                                    <input type="number" name="<?php echo $day; ?>_evening_patients" class="form-control form-control-sm" 
                                           value="<?php echo $hasEvening ? $evening['max_patients'] : 10; ?>" min="1" max="50">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Help Information -->
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Instructions:</strong>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <ul class="mb-0">
                                <li><i class="fas fa-check-circle text-success me-1"></i> Check "Active" to enable a session</li>
                                <li><i class="fas fa-clock me-1"></i> Set start and end times</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <ul class="mb-0">
                                <li><i class="fas fa-stopwatch me-1"></i> Slot duration: Time per patient (minutes)</li>
                                <li><i class="fas fa-users me-1"></i> Max patients: Total capacity</li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <ul class="mb-0">
                                <li><i class="fas fa-copy me-1"></i> Use "Copy" button to duplicate Monday's schedule</li>
                                <li><i class="fas fa-save me-1"></i> Save to apply changes</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="schedule-actions mt-4">
                    <button type="button" class="btn btn-outline-secondary" onclick="copyScheduleFromMonday()">
                        <i class="fas fa-copy me-2"></i>Copy Monday Schedule
                    </button>
                    <button type="reset" class="btn btn-outline-danger">
                        <i class="fas fa-undo me-2"></i>Reset
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg px-4">
                        <i class="fas fa-save me-2"></i>Save Schedule
                    </button>
                    <a href="<?php echo BASE_URL; ?>/doctor/schedule-list" class="btn btn-secondary btn-lg px-4">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle session inputs based on checkbox
    const toggleButtons = document.querySelectorAll('.toggle-session');
    
    function toggleSession(checkbox) {
        const day = checkbox.getAttribute('data-day');
        const session = checkbox.getAttribute('data-session');
        const isChecked = checkbox.checked;
        
        const startInput = document.getElementById(`${day}_${session}_start`);
        const endInput = document.getElementById(`${day}_${session}_end`);
        const slotInput = document.querySelector(`input[name="${day}_${session}_slot"]`);
        const patientsInput = document.querySelector(`input[name="${day}_${session}_patients"]`);
        const row = checkbox.closest('tr');
        
        // Enable/disable inputs
        if (startInput) startInput.disabled = !isChecked;
        if (endInput) endInput.disabled = !isChecked;
        if (slotInput) slotInput.disabled = !isChecked;
        if (patientsInput) patientsInput.disabled = !isChecked;
        
        // Update row status
        if (row) {
            const statusBadge = row.querySelector('.day-name .badge');
            const activeCount = document.querySelectorAll(`#row-${day} input.toggle-session:checked`).length;
            if (statusBadge) {
                if (activeCount > 0) {
                    statusBadge.className = 'badge bg-success';
                    statusBadge.textContent = activeCount + ' active';
                } else {
                    statusBadge.className = 'badge bg-secondary';
                    statusBadge.textContent = 'Inactive';
                }
            }
        }
    }
    
    toggleButtons.forEach(button => {
        button.addEventListener('change', function() {
            toggleSession(this);
        });
        // Initialize state
        toggleSession(button);
    });
    
    // Validate time ranges
    const timeInputs = document.querySelectorAll('.time-input');
    timeInputs.forEach(input => {
        input.addEventListener('change', function() {
            const row = this.closest('tr');
            const session = this.id.includes('morning') ? 'morning' : 'evening';
            const startInput = document.getElementById(`${this.id.replace('_start', '').replace('_end', '')}_start`);
            const endInput = document.getElementById(`${this.id.replace('_start', '').replace('_end', '')}_end`);
            
            if (startInput && endInput && startInput.value && endInput.value) {
                if (startInput.value >= endInput.value) {
                    alert('End time must be after start time!');
                    endInput.value = startInput.value;
                }
            }
        });
    });
});

// Copy Monday schedule to all weekdays
function copyScheduleFromMonday() {
    const days = ['Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    const mondayMorningActive = document.querySelector('input[name="Monday_morning_active"]');
    const mondayMorningStart = document.getElementById('Monday_morning_start');
    const mondayMorningEnd = document.getElementById('Monday_morning_end');
    const mondayMorningSlot = document.querySelector('input[name="Monday_morning_slot"]');
    const mondayMorningPatients = document.querySelector('input[name="Monday_morning_patients"]');
    const mondayEveningActive = document.querySelector('input[name="Monday_evening_active"]');
    const mondayEveningStart = document.getElementById('Monday_evening_start');
    const mondayEveningEnd = document.getElementById('Monday_evening_end');
    const mondayEveningSlot = document.querySelector('input[name="Monday_evening_slot"]');
    const mondayEveningPatients = document.querySelector('input[name="Monday_evening_patients"]');
    
    if (!mondayMorningActive || !mondayEveningActive) {
        alert('Monday schedule not found!');
        return;
    }
    
    if (!confirm('Copy Monday schedule to all weekdays (Tuesday-Sunday)?')) {
        return;
    }
    
    days.forEach(day => {
        // Morning
        const morningActive = document.querySelector(`input[name="${day}_morning_active"]`);
        const morningStart = document.getElementById(`${day}_morning_start`);
        const morningEnd = document.getElementById(`${day}_morning_end`);
        const morningSlot = document.querySelector(`input[name="${day}_morning_slot"]`);
        const morningPatients = document.querySelector(`input[name="${day}_morning_patients"]`);
        
        if (morningActive) {
            morningActive.checked = mondayMorningActive.checked;
            const event = new Event('change');
            morningActive.dispatchEvent(event);
        }
        if (morningStart) morningStart.value = mondayMorningStart.value;
        if (morningEnd) morningEnd.value = mondayMorningEnd.value;
        if (morningSlot) morningSlot.value = mondayMorningSlot.value;
        if (morningPatients) morningPatients.value = mondayMorningPatients.value;
        
        // Evening
        const eveningActive = document.querySelector(`input[name="${day}_evening_active"]`);
        const eveningStart = document.getElementById(`${day}_evening_start`);
        const eveningEnd = document.getElementById(`${day}_evening_end`);
        const eveningSlot = document.querySelector(`input[name="${day}_evening_slot"]`);
        const eveningPatients = document.querySelector(`input[name="${day}_evening_patients"]`);
        
        if (eveningActive) {
            eveningActive.checked = mondayEveningActive.checked;
            const event = new Event('change');
            eveningActive.dispatchEvent(event);
        }
        if (eveningStart) eveningStart.value = mondayEveningStart.value;
        if (eveningEnd) eveningEnd.value = mondayEveningEnd.value;
        if (eveningSlot) eveningSlot.value = mondayEveningSlot.value;
        if (eveningPatients) eveningPatients.value = mondayEveningPatients.value;
    });
    
    alert('Schedule copied from Monday to all weekdays successfully!');
}
</script>