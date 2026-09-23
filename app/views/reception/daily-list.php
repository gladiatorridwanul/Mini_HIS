<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); 

// Get user role from session
$userRole = $_SESSION['role_slug'] ?? 'guest';
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? 'User';

// Check if user can perform actions
$canManageAppointments = in_array($userRole, ['super_admin', 'admin', 'receptionist', 'nurse']);
$canConfirm = in_array($userRole, ['super_admin', 'admin', 'receptionist']);
$canCheckIn = in_array($userRole, ['super_admin', 'admin', 'receptionist', 'nurse']);
$canStart = in_array($userRole, ['super_admin', 'admin', 'doctor']);
$canComplete = in_array($userRole, ['super_admin', 'admin', 'doctor']);
$canReschedule = in_array($userRole, ['super_admin', 'admin', 'receptionist']);
$canCancel = in_array($userRole, ['super_admin', 'admin', 'receptionist']);
$canPrintSlip = in_array($userRole, ['super_admin', 'admin', 'receptionist', 'nurse']);
$canPrescribe = in_array($userRole, ['super_admin', 'admin', 'doctor']);
$canView = true; // All logged-in users can view
?>

<style>
    .card { border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px; background: white; }
    .card-header { background: white; border-bottom: 2px solid #10b981; border-radius: 12px 12px 0 0 !important; font-weight: 600; }
    .serial-badge { font-size: 16px; font-weight: bold; background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; display: inline-block; }
    .status-scheduled { background: #f59e0b; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .status-confirmed { background: #3b82f6; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .status-checked_in { background: #8b5cf6; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .status-completed { background: #10b981; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .status-in_progress { background: #3b82f6; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .status-canceled { background: #ef4444; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .status-no_show { background: #6c757d; color: white; padding: 3px 10px; border-radius: 20px; font-size: 11px; display: inline-block; }
    .patient-row { transition: background 0.2s; }
    .patient-row:hover { background: #f0fdf4; }
    .table th { background: #f8fafc; }
    .btn-action { padding: 3px 6px; font-size: 10px; border-radius: 4px; margin: 1px; border: none; cursor: pointer; }
    .btn-action i { font-size: 11px; }
    .btn-cancel { background: #ef4444; color: white; }
    .btn-cancel:hover { background: #dc2626; color: white; }
    .btn-confirm { background: #3b82f6; color: white; }
    .btn-confirm:hover { background: #2563eb; color: white; }
    .btn-reschedule { background: #f59e0b; color: white; }
    .btn-reschedule:hover { background: #d97706; color: white; }
    .btn-print-slip { background: #10b981; color: white; }
    .btn-print-slip:hover { background: #059669; color: white; }
    .btn-start { background: #8b5cf6; color: white; }
    .btn-start:hover { background: #7c3aed; color: white; }
    .btn-complete { background: #10b981; color: white; }
    .btn-complete:hover { background: #059669; color: white; }
    .btn-view { background: #3b82f6; color: white; }
    .btn-view:hover { background: #2563eb; color: white; }
    .btn-prescribe { background: #8b5cf6; color: white; }
    .btn-prescribe:hover { background: #7c3aed; color: white; }
    .action-group { display: flex; gap: 3px; flex-wrap: wrap; justify-content: center; }
    .role-badge { font-size: 10px; padding: 2px 10px; border-radius: 12px; }
    .summary-card { background: #f8fafc; border-radius: 8px; padding: 10px 14px; border: 1px solid #e2e8f0; }
    .summary-card .stat-item { display: inline-block; margin-right: 20px; }
    .summary-card .stat-item .label { font-size: 11px; color: #94a3b8; }
    .summary-card .stat-item .value { font-size: 18px; font-weight: 600; color: #1e293b; }
    .summary-card .stat-item .value.text-success { color: #10b981; }
    .summary-card .stat-item .value.text-warning { color: #f59e0b; }
    .summary-card .stat-item .value.text-danger { color: #ef4444; }
    .summary-card .stat-item .value.text-primary { color: #3b82f6; }
    @media print { .no-print { display: none; } }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2 no-print">
    <div>
        <h2><i class="fas fa-list text-success me-2"></i> Daily Patient List</h2>
        <p class="text-muted small mb-0">
            <?php echo date('l, d F Y'); ?> 
            <span class="badge bg-info ms-2 role-badge"><?php echo ucfirst(str_replace('_', ' ', $userRole)); ?></span>
        </p>
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fas fa-print"></i> Print List</button>
        <?php if (in_array($userRole, ['super_admin', 'admin', 'receptionist'])): ?>
        <a href="<?php echo BASE_URL; ?>/patient/book-appointment" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> New Appointment</a>
        <?php endif; ?>
        <?php if (in_array($userRole, ['super_admin', 'admin', 'doctor'])): ?>
        <a href="<?php echo BASE_URL; ?>/prescription/create" class="btn btn-primary btn-sm"><i class="fas fa-prescription"></i> Prescribe</a>
        <?php endif; ?>
    </div>
</div>

<!-- Role-based Summary Stats -->
<div class="row g-2 mb-4 no-print">
    <?php if (in_array($userRole, ['super_admin', 'admin', 'receptionist', 'nurse'])): ?>
    <div class="col-md-3 col-6">
        <div class="summary-card">
            <div class="stat-item">
                <div class="label">Total Patients</div>
                <div class="value" id="totalCount"><?php echo $totalPatients ?? 0; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="summary-card">
            <div class="stat-item">
                <div class="label">Checked In</div>
                <div class="value text-primary" id="checkedInCount">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="summary-card">
            <div class="stat-item">
                <div class="label">In Progress</div>
                <div class="value text-warning" id="inProgressCount">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="summary-card">
            <div class="stat-item">
                <div class="label">Completed</div>
                <div class="value text-success" id="completedCount"><?php echo $completedCount ?? 0; ?></div>
            </div>
        </div>
    </div>
    <?php elseif (in_array($userRole, ['doctor'])): ?>
    <div class="col-md-3 col-6">
        <div class="summary-card">
            <div class="stat-item">
                <div class="label">My Patients</div>
                <div class="value" id="totalCount"><?php echo $totalPatients ?? 0; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="summary-card">
            <div class="stat-item">
                <div class="label">In Progress</div>
                <div class="value text-warning" id="inProgressCount">0</div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="summary-card">
            <div class="stat-item">
                <div class="label">Completed</div>
                <div class="value text-success" id="completedCount"><?php echo $completedCount ?? 0; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="summary-card">
            <div class="stat-item">
                <div class="label">Pending</div>
                <div class="value text-danger" id="pendingCount">0</div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-12">
        <div class="summary-card text-center text-muted py-2">
            <i class="fas fa-info-circle me-2"></i> Viewing daily patient list as <?php echo ucfirst(str_replace('_', ' ', $userRole)); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Date Filter -->
<div class="card no-print">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-3">
                <label class="form-label fw-bold small">Select Date</label>
                <input type="date" id="filter_date" class="form-control form-control-sm" value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small">Shift</label>
                <select id="filter_shift" class="form-select form-select-sm">
                    <option value="">All Shifts</option>
                    <option value="morning">Morning Session</option>
                    <option value="evening">Evening Session</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small">Status</label>
                <select id="filter_status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="checked_in">Checked In</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Completed</option>
                    <option value="canceled">Canceled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold small">&nbsp;</label>
                <button id="filter_btn" class="btn btn-success w-100 btn-sm"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
    </div>
</div>

<div id="loadingSpinner" class="text-center py-5" style="display: none;">
    <div class="spinner-border text-success" role="status"></div>
    <p class="mt-2">Loading appointments...</p>
</div>

<div id="morning_section"></div>
<div id="evening_section"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const USER_ROLE = '<?php echo $userRole; ?>';

// Determine if user can perform actions
const canManageAppointments = ['super_admin', 'admin', 'receptionist', 'nurse'].includes(USER_ROLE);
const canConfirm = ['super_admin', 'admin', 'receptionist'].includes(USER_ROLE);
const canCheckIn = ['super_admin', 'admin', 'receptionist', 'nurse'].includes(USER_ROLE);
const canStart = ['super_admin', 'admin', 'doctor'].includes(USER_ROLE);
const canComplete = ['super_admin', 'admin', 'doctor'].includes(USER_ROLE);
const canReschedule = ['super_admin', 'admin', 'receptionist'].includes(USER_ROLE);
const canCancel = ['super_admin', 'admin', 'receptionist'].includes(USER_ROLE);
const canPrintSlip = ['super_admin', 'admin', 'receptionist', 'nurse'].includes(USER_ROLE);
const canPrescribe = ['super_admin', 'admin', 'doctor'].includes(USER_ROLE);

function loadPatients() {
    const date = $('#filter_date').val();
    const shift = $('#filter_shift').val();
    const status = $('#filter_status').val();
    
    $('#loadingSpinner').show();
    $('#morning_section').html('');
    $('#evening_section').html('');
    
    // Reset summary counts
    $('#totalCount').text('0');
    $('#checkedInCount').text('0');
    $('#inProgressCount').text('0');
    $('#completedCount').text('0');
    $('#pendingCount').text('0');
    
    $.ajax({
        url: BASE_URL + '/api/daily-patients',
        method: 'GET',
        data: { date: date, shift: shift, status: status },
        dataType: 'json',
        success: function(response) {
            $('#loadingSpinner').hide();
            
            if(response.error) {
                showError(response.error);
                return;
            }
            
            const patients = response;
            
            // Update summary counts
            updateSummaryCounts(patients);
            
            if(patients && patients.length > 0) {
                const morningPatients = patients.filter(p => p.session_type === 'morning');
                const eveningPatients = patients.filter(p => p.session_type === 'evening');
                
                renderSection('morning_section', 'Morning Session', '🌅', morningPatients);
                renderSection('evening_section', 'Evening Session', '🌙', eveningPatients);
            } else {
                $('#morning_section').html(emptyState('Morning'));
                $('#evening_section').html(emptyState('Evening'));
            }
        },
        error: function(xhr, status, error) {
            $('#loadingSpinner').hide();
            console.error('Error:', xhr.responseText);
            showError('Failed to load appointments. Please check your connection.');
        }
    });
}

function updateSummaryCounts(patients) {
    if (!patients || patients.length === 0) return;
    
    const total = patients.length;
    const checkedIn = patients.filter(p => p.status === 'checked_in').length;
    const inProgress = patients.filter(p => p.status === 'in_progress').length;
    const completed = patients.filter(p => p.status === 'completed').length;
    const pending = patients.filter(p => p.status === 'scheduled' || p.status === 'confirmed').length;
    
    $('#totalCount').text(total);
    $('#checkedInCount').text(checkedIn);
    $('#inProgressCount').text(inProgress);
    $('#completedCount').text(completed);
    $('#pendingCount').text(pending);
}

function renderSection(containerId, title, icon, patients) {
    if(!patients || patients.length === 0) {
        $(`#${containerId}`).html(emptyState(title));
        return;
    }
    
    let html = `
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">${icon} ${title} <span class="badge bg-success ms-2">${patients.length} patients</span></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:60px">Serial</th>
                                <th>Patient Name</th>
                                <th>Phone</th>
                                <th>Doctor</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th class="no-print" style="min-width:180px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
    `;
    
    patients.forEach(p => {
        const statusClass = `status-${p.status || 'scheduled'}`;
        const statusText = (p.status || 'scheduled').toUpperCase().replace('_', ' ');
        
        html += `
            <tr class="patient-row" data-id="${p.id}" data-status="${p.status}">
                <td><span class="serial-badge">${p.serial_number || 'N/A'}</span></td>
                <td><strong>${escapeHtml(p.first_name)} ${escapeHtml(p.last_name)}</strong></td>
                <td>${p.phone || 'N/A'}</td>
                <td>Dr. ${escapeHtml(p.doctor_fname)} ${escapeHtml(p.doctor_lname)}</td>
                <td>${p.start_time ? p.start_time.substring(0,5) : 'N/A'}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td class="no-print">
                    <div class="action-group">
                        ${canPrintSlip ? `
                        <button class="btn-action btn-print-slip" onclick="printSlip(${p.id})" title="Print Serial Slip">
                            <i class="fas fa-print"></i>
                        </button>
                        ` : ''}
                        
                        ${canConfirm && p.status == 'scheduled' ? `
                        <button class="btn-action btn-confirm" onclick="confirmAppointment(${p.id})" title="Confirm">
                            <i class="fas fa-check-circle"></i>
                        </button>
                        ` : ''}
                        
                        ${canCheckIn && (p.status == 'scheduled' || p.status == 'confirmed') ? `
                        <button class="btn-action btn-start" onclick="checkInPatient(${p.id})" title="Check In">
                            <i class="fas fa-clipboard-check"></i>
                        </button>
                        ` : ''}
                        
                        ${canStart && p.status == 'checked_in' ? `
                        <button class="btn-action btn-start" onclick="startConsultation(${p.id})" title="Start">
                            <i class="fas fa-play"></i>
                        </button>
                        ` : ''}
                        
                        ${canComplete && p.status == 'in_progress' ? `
                        <button class="btn-action btn-complete" onclick="completeConsultation(${p.id})" title="Complete">
                            <i class="fas fa-check-double"></i>
                        </button>
                        ` : ''}
                        
                        ${canPrescribe && (p.status == 'checked_in' || p.status == 'in_progress' || p.status == 'completed') ? `
                        <button class="btn-action btn-prescribe" onclick="createPrescription(${p.patient_id}, ${p.id})" title="Prescribe">
                            <i class="fas fa-prescription"></i>
                        </button>
                        ` : ''}
                        
                        ${canReschedule ? `
                        <button class="btn-action btn-reschedule" onclick="rescheduleAppointment(${p.id})" title="Reschedule">
                            <i class="fas fa-calendar-alt"></i>
                        </button>
                        ` : ''}
                        
                        ${canCancel ? `
                        <button class="btn-action btn-cancel" onclick="cancelAppointment(${p.id})" title="Cancel">
                            <i class="fas fa-times"></i>
                        </button>
                        ` : ''}
                        
                        <!-- View Details - Always visible -->
                        <button class="btn-action btn-view" onclick="viewAppointment(${p.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    $(`#${containerId}`).html(html);
}

function emptyState(title) {
    return `
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">${title}</h5>
            </div>
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-calendar-day fa-3x mb-3"></i>
                <p>No appointments found</p>
            </div>
        </div>
    `;
}

function showError(message) {
    $('#morning_section').html(`
        <div class="alert alert-danger text-center py-4">
            <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
            <p>${message}</p>
            <button onclick="loadPatients()" class="btn btn-sm btn-danger">Retry</button>
        </div>
    `);
}

// ============================================================
// ACTION FUNCTIONS
// ============================================================

function printSlip(appointmentId) {
    window.open(BASE_URL + '/api/print-serial?appointment_id=' + appointmentId, '_blank');
}

function createPrescription(patientId, appointmentId) {
    window.open(BASE_URL + '/prescription/create?patient_id=' + patientId + '&appointment_id=' + appointmentId, '_blank');
}

function viewAppointment(appointmentId) {
    window.open(BASE_URL + '/reception/appointments?view=' + appointmentId, '_blank');
}

function confirmAppointment(appointmentId) {
    Swal.fire({
        title: 'Confirm Appointment?',
        text: 'Mark this appointment as confirmed',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Confirm',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3b82f6'
    }).then((result) => {
        if(result.isConfirmed) {
            updateAppointmentStatus(appointmentId, 'confirmed', 'Appointment confirmed successfully');
        }
    });
}

function checkInPatient(appointmentId) {
    Swal.fire({
        title: 'Check In Patient?',
        text: 'Mark this patient as checked in',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Check In',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#8b5cf6'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: BASE_URL + '/reception/do-checkin',
                method: 'POST',
                data: { appointment_id: appointmentId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Checked In!',
                            html: `Patient checked in successfully!<br>Queue Number: <strong>${response.queue_number || 'N/A'}</strong>`,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            loadPatients();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Check-in failed', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error. Please try again.', 'error');
                }
            });
        }
    });
}

function startConsultation(appointmentId) {
    Swal.fire({
        title: 'Start Consultation?',
        text: 'Mark this appointment as in progress',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Start',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#8b5cf6'
    }).then((result) => {
        if(result.isConfirmed) {
            updateAppointmentStatus(appointmentId, 'in_progress', 'Consultation started');
        }
    });
}

function completeConsultation(appointmentId) {
    Swal.fire({
        title: 'Complete Consultation?',
        text: 'Mark this appointment as completed',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Complete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#10b981'
    }).then((result) => {
        if(result.isConfirmed) {
            updateAppointmentStatus(appointmentId, 'completed', 'Consultation completed');
        }
    });
}

function rescheduleAppointment(appointmentId) {
    $.ajax({
        url: BASE_URL + '/api/get-appointment-details',
        method: 'GET',
        data: { appointment_id: appointmentId },
        dataType: 'json',
        success: function(response) {
            if(response.success && response.data) {
                const app = response.data;
                Swal.fire({
                    title: 'Reschedule Appointment',
                    html: `
                        <div class="text-left">
                            <p><strong>Patient:</strong> ${escapeHtml(app.patient_name)}</p>
                            <p><strong>Doctor:</strong> Dr. ${escapeHtml(app.doctor_name)}</p>
                            <hr>
                            <div class="mb-2">
                                <label class="form-label">New Date</label>
                                <input type="date" id="reschedule_date" class="form-control" value="${app.appointment_date}" min="${new Date().toISOString().split('T')[0]}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">New Session</label>
                                <select id="reschedule_shift" class="form-select">
                                    <option value="morning" ${app.session_type == 'morning' ? 'selected' : ''}>Morning</option>
                                    <option value="evening" ${app.session_type == 'evening' ? 'selected' : ''}>Evening</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Reason</label>
                                <input type="text" id="reschedule_reason" class="form-control" placeholder="Reason for rescheduling">
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Reschedule',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#f59e0b',
                    preConfirm: () => {
                        return {
                            date: document.getElementById('reschedule_date').value,
                            shift: document.getElementById('reschedule_shift').value,
                            reason: document.getElementById('reschedule_reason').value
                        };
                    }
                }).then((result) => {
                    if(result.isConfirmed) {
                        $.ajax({
                            url: BASE_URL + '/api/reschedule-appointment',
                            method: 'POST',
                            data: {
                                appointment_id: appointmentId,
                                new_date: result.value.date,
                                new_shift: result.value.shift,
                                reason: result.value.reason
                            },
                            dataType: 'json',
                            success: function(response) {
                                if(response.success) {
                                    Swal.fire('Rescheduled!', response.message, 'success').then(() => {
                                        loadPatients();
                                    });
                                } else {
                                    Swal.fire('Error', response.message || 'Failed to reschedule', 'error');
                                }
                            },
                            error: function() {
                                Swal.fire('Error', 'Server error. Please try again.', 'error');
                            }
                        });
                    }
                });
            } else {
                Swal.fire('Error', 'Could not load appointment details', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Server error. Please try again.', 'error');
        }
    });
}

function cancelAppointment(appointmentId) {
    Swal.fire({
        title: 'Cancel Appointment?',
        html: `
            <div class="text-left">
                <p>Are you sure you want to cancel this appointment?</p>
                <div class="mb-2">
                    <label class="form-label">Reason</label>
                    <input type="text" id="cancel_reason" class="form-control" placeholder="Reason for cancellation">
                </div>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'No, Keep',
        confirmButtonColor: '#ef4444',
        preConfirm: () => {
            return { reason: document.getElementById('cancel_reason').value || 'Cancelled by staff' };
        }
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/api/cancel-appointment',
                method: 'POST',
                data: {
                    appointment_id: appointmentId,
                    cancel_reason: result.value.reason
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Cancelled!', response.message, 'success').then(() => {
                            loadPatients();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Failed to cancel', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error. Please try again.', 'error');
                }
            });
        }
    });
}

// ============================================================
// UPDATE APPOINTMENT STATUS HELPER
// ============================================================

function updateAppointmentStatus(appointmentId, status, successMessage) {
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/api/update-appointment-status',
        method: 'POST',
        data: { appointment_id: appointmentId, status: status },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire('Success!', successMessage, 'success').then(() => {
                    loadPatients();
                });
            } else {
                Swal.fire('Error', response.message || 'Update failed', 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Server error. Please try again.', 'error');
        }
    });
}

// ============================================================
// EVENT HANDLERS
// ============================================================

function escapeHtml(str) {
    if(!str) return '';
    return String(str).replace(/[&<>"]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        if(m === '"') return '&quot;';
        return m;
    });
}

$('#filter_btn').click(loadPatients);
$('#filter_date').change(loadPatients);
$('#filter_shift').change(loadPatients);
$('#filter_status').change(loadPatients);

$(document).ready(function() {
    loadPatients();
    
    // Auto-refresh every 30 seconds
    setInterval(loadPatients, 30000);
});
</script>