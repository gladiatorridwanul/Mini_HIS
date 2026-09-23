<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .search-results {
        position: absolute;
        z-index: 1000;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        max-height: 400px;
        overflow-y: auto;
        width: 100%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: none;
    }
    .search-result-item {
        padding: 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        transition: all 0.2s;
    }
    .search-result-item:hover {
        background: #f0fdf4;
    }
    .appointment-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
        transition: all 0.2s;
        background: white;
    }
    .appointment-card:hover {
        border-color: #10b981;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    .queue-item {
        transition: all 0.2s;
        border-left: 3px solid transparent;
        padding: 12px;
        margin-bottom: 8px;
        background: #f8fafc;
        border-radius: 8px;
    }
    .queue-item.waiting {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }
    .queue-item.in_progress {
        border-left-color: #10b981;
        background: #f0fdf4;
    }
    .queue-item.completed {
        border-left-color: #6c757d;
        background: #f1f5f9;
        opacity: 0.7;
    }
    .auto-refresh-badge {
        background: #10b981;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .doctor-filter {
        background: #f8fafc;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 15px;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
        display: inline-block;
    }
    .status-scheduled { background: #fef3c7; color: #d97706; }
    .status-confirmed { background: #dbeafe; color: #1e40af; }
    .status-checked_in { background: #ede9fe; color: #5b21b6; }
    .status-in_progress { background: #dbeafe; color: #1e40af; }
    .status-completed { background: #d1fae5; color: #065f46; }
    .status-canceled { background: #fee2e2; color: #991b1b; }
    .btn-sm { padding: 3px 8px; font-size: 11px; }
</style>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-clipboard-check me-2"></i>Patient Check-in
                </h6>
                <div class="auto-refresh-badge">
                    <i class="fas fa-sync-alt fa-fw"></i>
                    <span>Auto-refresh: 10s</span>
                </div>
            </div>
            <div class="card-body">
                <!-- Doctor Filter -->
                <div class="doctor-filter">
                    <label class="form-label fw-bold small">Filter by Doctor</label>
                    <select id="doctorFilter" class="form-select form-select-sm">
                        <option value="0">All Doctors</option>
                        <?php if(isset($doctors) && !empty($doctors)): ?>
                            <?php foreach($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?> (<?php echo $doctor['specialization']; ?>)
                            </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="0">No doctors available</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Search Patient</label>
                    <div style="position: relative;">
                        <input type="text" class="form-control form-control-lg" id="patientSearch" 
                               placeholder="Search by name, phone, or patient code..." autocomplete="off">
                        <div id="patientResults" class="search-results"></div>
                    </div>
                </div>
                
                <div id="appointmentSection" style="display: none;">
                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="fas fa-calendar-day me-2"></i>Today's Appointments</h6>
                        <button class="btn btn-sm btn-outline-secondary" onclick="refreshAppointments()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div id="patientAppointments">
                        <div class="text-center py-3 text-muted">Search for a patient to see appointments</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-warning d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-dark">
                    <i class="fas fa-list-ol me-2"></i>Current Queue
                </h6>
                <span class="badge bg-light text-dark" id="queueCount">0</span>
            </div>
            <div class="card-body" id="currentQueue" style="max-height: 500px; overflow-y: auto;">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary"></div><br>
                    Loading queue...
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let searchTimeout;
let autoRefreshInterval;
let currentPatientId = null;

$(document).ready(function() {
    loadCurrentQueue();
    startAutoRefresh();
    
    $('#doctorFilter').on('change', function() {
        loadCurrentQueue();
        if(currentPatientId) {
            loadPatientAppointments(currentPatientId);
        }
    });
    
    $('#patientSearch').on('input', function() {
        clearTimeout(searchTimeout);
        let search = $(this).val();
        
        if(search.length < 2) {
            $('#patientResults').hide();
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performPatientSearch(search);
        }, 300);
    });
    
    $(document).click(function(e) {
        if(!$(e.target).closest('#patientSearch, #patientResults').length) {
            $('#patientResults').hide();
        }
    });
});

function startAutoRefresh() {
    if(autoRefreshInterval) clearInterval(autoRefreshInterval);
    autoRefreshInterval = setInterval(function() {
        loadCurrentQueue();
    }, 10000);
}

function performPatientSearch(search) {
    $.ajax({
        url: BASE_URL + '/reception/search',
        method: 'GET',
        data: { term: search },
        dataType: 'json',
        success: function(patients) {
            if(!patients || patients.length === 0) {
                $('#patientResults').html('<div class="search-result-item text-muted">No patients found</div>').show();
                return;
            }
            
            let html = '';
            patients.forEach(function(patient) {
                html += `
                    <div class="search-result-item" onclick="selectPatient(${patient.id}, '${escapeHtml(patient.first_name)} ${escapeHtml(patient.last_name)}')">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${escapeHtml(patient.first_name)} ${escapeHtml(patient.last_name)}</strong>
                                <br><small class="text-muted">${patient.patient_code}</small>
                            </div>
                            <div class="text-end">
                                <small><i class="fas fa-phone"></i> ${patient.phone}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#patientResults').html(html).show();
        },
        error: function(xhr) {
            console.error('Search error:', xhr);
            $('#patientResults').html('<div class="search-result-item text-danger">Error searching patients</div>').show();
        }
    });
}

function selectPatient(patientId, patientName) {
    $('#patientSearch').val(patientName);
    currentPatientId = patientId;
    $('#patientResults').hide();
    loadPatientAppointments(patientId);
}

function loadPatientAppointments(patientId) {
    let doctorId = $('#doctorFilter').val();
    let today = new Date().toISOString().split('T')[0];
    
    $('#patientAppointments').html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading appointments...</div>');
    $('#appointmentSection').show();
    
    $.ajax({
        url: BASE_URL + '/api/get-filtered-appointments',
        method: 'GET',
        data: { 
            patient_id: patientId, 
            doctor_id: doctorId,
            date: today 
        },
        dataType: 'json',
        success: function(response) {
            if(!response.success || !response.data || response.data.length === 0) {
                $('#patientAppointments').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No appointments found for today.
                        <br><small>Please book an appointment first.</small>
                    </div>
                `);
                return;
            }
            
            let html = '';
            response.data.forEach(function(app) {
                let statusBadge = '';
                let checkinButton = '';
                let statusClass = app.status || 'scheduled';
                
                statusBadge = `<span class="status-badge status-${statusClass}">${statusClass.toUpperCase().replace('_', ' ')}</span>`;
                
                if(app.status === 'scheduled' || app.status === 'confirmed') {
                    checkinButton = `<button class="btn btn-success btn-sm checkin-btn" data-id="${app.id}">
                                        <i class="fas fa-check me-1"></i>Check In
                                    </button>`;
                } else if(app.status === 'checked_in' || app.status === 'in_progress') {
                    checkinButton = `<span class="badge bg-info">Already Checked In</span>`;
                } else if(app.status === 'completed') {
                    checkinButton = `<span class="badge bg-success">Completed</span>`;
                } else {
                    checkinButton = `<span class="badge bg-secondary">${statusClass}</span>`;
                }
                
                html += `
                    <div class="appointment-card">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong>Dr. ${escapeHtml(app.doctor_fname || app.doctor_first || '')} ${escapeHtml(app.doctor_lname || app.doctor_last || '')}</strong>
                                    ${statusBadge}
                                </div>
                                <div><small class="text-muted">${escapeHtml(app.specialization || 'General')}</small></div>
                                <div><small><i class="far fa-clock me-1"></i> ${app.start_time ? app.start_time.substring(0,5) : 'N/A'} - ${app.end_time ? app.end_time.substring(0,5) : 'N/A'}</small></div>
                                <div><small><i class="fas fa-tag me-1"></i> ${app.session_type === 'morning' ? 'Morning Shift' : 'Evening Shift'}</small></div>
                            </div>
                            <div class="ms-3">
                                ${checkinButton}
                            </div>
                        </div>
                    </div>
                `;
            });
            $('#patientAppointments').html(html);
            
            $('.checkin-btn').off('click').on('click', function() {
                let appointmentId = $(this).data('id');
                performCheckIn(appointmentId);
            });
        },
        error: function(xhr) {
            console.error('Appointments error:', xhr);
            $('#patientAppointments').html('<div class="alert alert-danger">Error loading appointments. Please try again.</div>');
        }
    });
}

function performCheckIn(appointmentId) {
    Swal.fire({
        title: 'Confirm Check-in',
        text: 'Are you sure you want to check in this patient?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Check In',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
            
            $.ajax({
                url: BASE_URL + '/api/update-appointment-status',
                method: 'POST',
                data: { 
                    appointment_id: appointmentId, 
                    status: 'checked_in' 
                },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Checked In!',
                            text: 'Patient checked in successfully!',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            $('#patientSearch').val('');
                            $('#appointmentSection').hide();
                            $('#patientAppointments').empty();
                            currentPatientId = null;
                            loadCurrentQueue();
                        });
                    } else {
                        Swal.fire('Error', response.message || 'Check-in failed', 'error');
                    }
                },
                error: function(xhr) {
                    console.error('Check-in error:', xhr);
                    Swal.fire('Error', 'Server error. Please try again.', 'error');
                }
            });
        }
    });
}

function loadCurrentQueue() {
    let doctorId = $('#doctorFilter').val();
    
    $('#currentQueue').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading queue...</p>
        </div>
    `);
    
    $.ajax({
        url: BASE_URL + '/api/queue-list',
        method: 'GET',
        data: { doctor_id: doctorId },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            console.log('Queue response:', response);
            
            if(response.success && response.queue) {
                renderQueue(response.queue);
                $('#queueCount').text(response.queue.length);
            } else {
                $('#currentQueue').html(`
                    <div class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                        <p>${response.message || 'Failed to load queue'}</p>
                        <button class="btn btn-sm btn-outline-danger" onclick="loadCurrentQueue()">
                            <i class="fas fa-redo"></i> Retry
                        </button>
                    </div>
                `);
                $('#queueCount').text(0);
            }
        },
        error: function(xhr, status, error) {
            console.error('Queue error:', status, error);
            console.error('Response:', xhr.responseText);
            
            let errorMsg = 'Error loading queue';
            try {
                let response = JSON.parse(xhr.responseText);
                if(response.message) errorMsg = response.message;
            } catch(e) {}
            
            $('#currentQueue').html(`
                <div class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                    <p>${errorMsg}</p>
                    <button class="btn btn-sm btn-outline-danger" onclick="loadCurrentQueue()">
                        <i class="fas fa-redo"></i> Retry
                    </button>
                </div>
            `);
            $('#queueCount').text(0);
        }
    });
}

function renderQueue(queue) {
    if(!queue || queue.length === 0) {
        $('#currentQueue').html(`
            <div class="text-center py-4 text-muted">
                <i class="fas fa-users fa-3x mb-3"></i>
                <p>Queue is empty</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    queue.forEach(function(item) {
        let statusClass = item.status || 'waiting';
        let statusText = (item.status || 'waiting').toUpperCase().replace('_', ' ');
        
        html += `
            <div class="queue-item ${statusClass}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-${statusClass == 'in_progress' ? 'success' : 'warning'} fs-5 p-2">
                            #${item.serial_number || 'N/A'}
                        </span>
                    </div>
                    <div class="flex-grow-1 px-3">
                        <strong>${escapeHtml(item.patient_name || 'Unknown')}</strong>
                        <br><small class="text-muted">Dr. ${escapeHtml(item.doctor_name || '')}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-${statusClass == 'in_progress' ? 'success' : 'warning'}">
                            ${statusText}
                        </span>
                    </div>
                </div>
            </div>
        `;
    });
    $('#currentQueue').html(html);
}

function refreshAppointments() {
    if(currentPatientId) {
        loadPatientAppointments(currentPatientId);
    }
}

function escapeHtml(str) {
    if(!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if(m === '&') return '&amp;';
        if(m === '<') return '&lt;';
        if(m === '>') return '&gt;';
        return m;
    });
}
</script>