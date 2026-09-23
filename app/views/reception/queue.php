<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .queue-item {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        padding: 15px;
        margin-bottom: 10px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
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
    .calling-animation {
        animation: pulse 1s ease-in-out;
        background-color: #fff3cd !important;
    }
    @keyframes pulse {
        0% { background-color: #fff3cd; }
        100% { background-color: transparent; }
    }
    .serving-card {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }
    .serving-number {
        font-size: 48px;
        font-weight: bold;
        letter-spacing: 2px;
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
    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    .stat-number {
        font-size: 28px;
        font-weight: bold;
    }
    .stat-label {
        font-size: 11px;
        color: #6c757d;
    }
    .doctor-filter {
        background: #f8fafc;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 15px;
    }
    .btn-prescription {
        background: #2563eb;
        color: white;
        border: none;
    }
    .btn-prescription:hover {
        background: #1d4ed8;
        color: white;
    }
</style>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list-ol me-2"></i>Patient Queue Management
                </h6>
                <div class="d-flex gap-2">
                    <span class="auto-refresh-badge">
                        <i class="fas fa-sync-alt fa-fw"></i>
                        <span>Auto-refresh: 10s</span>
                    </span>
                    <button class="btn btn-sm btn-outline-secondary" onclick="loadQueue()">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Doctor Filter -->
                <div class="doctor-filter">
                    <div class="row g-2">
                        <div class="col-md-6">
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
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Filter by Shift</label>
                            <select id="shiftFilter" class="form-select form-select-sm">
                                <option value="">All Shifts</option>
                                <option value="morning">Morning Shift</option>
                                <option value="evening">Evening Shift</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold small">&nbsp;</label>
                            <button class="btn btn-primary btn-sm w-100" onclick="loadQueue()">
                                <i class="fas fa-search me-1"></i> Apply
                            </button>
                        </div>
                    </div>
                </div>
                
                <div id="queueDisplay">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div><br>
                        Loading queue...
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Currently Serving Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success text-white">
                <h6 class="m-0 font-weight-bold">
                    <i class="fas fa-user-check me-2"></i>Currently Serving
                </h6>
            </div>
            <div class="card-body text-center" id="currentServingDisplay">
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-muted"></div>
                    <p class="mt-2">Loading...</p>
                </div>
            </div>
        </div>

        <!-- Queue Statistics Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Queue Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-4 mb-3">
                        <div class="stat-card">
                            <div class="stat-number text-warning" id="waitingCount">0</div>
                            <div class="stat-label">Waiting</div>
                        </div>
                    </div>
                    <div class="col-4 mb-3">
                        <div class="stat-card">
                            <div class="stat-number text-success" id="inProgressCount">0</div>
                            <div class="stat-label">In Progress</div>
                        </div>
                    </div>
                    <div class="col-4 mb-3">
                        <div class="stat-card">
                            <div class="stat-number text-info" id="completedCount">0</div>
                            <div class="stat-label">Completed Today</div>
                        </div>
                    </div>
                </div>
                <hr>
                <button class="btn btn-primary w-100 mb-2" onclick="loadQueue()">
                    <i class="fas fa-sync-alt me-1"></i>Refresh Now
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
let autoRefreshInterval;

$(document).ready(function() {
    loadQueue();
    startAutoRefresh();
    
    $('#doctorFilter, #shiftFilter').on('change', function() {
        loadQueue();
    });
});

function startAutoRefresh() {
    if(autoRefreshInterval) clearInterval(autoRefreshInterval);
    autoRefreshInterval = setInterval(function() {
        loadQueue();
    }, 10000);
}

function loadQueue() {
    let doctorId = $('#doctorFilter').val();
    let shift = $('#shiftFilter').val();
    
    $('#queueDisplay').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary"></div>
            <p class="mt-2">Loading queue...</p>
        </div>
    `);
    
    $.ajax({
        url: BASE_URL + '/api/queue-list',
        method: 'GET',
        data: { doctor_id: doctorId, shift: shift },
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            console.log('Queue response:', response);
            
            if(response.success) {
                renderQueue(response.queue || []);
                renderCurrentServing(response.currentServing);
                updateStatistics(response.stats || {waiting: 0, in_progress: 0, completed: 0});
            } else {
                showError(response.message || 'Failed to load queue data');
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
            
            showError(errorMsg);
        }
    });
}

function showError(message) {
    $('#queueDisplay').html(`
        <div class="text-center py-5 text-danger">
            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
            <p>${message}</p>
            <button class="btn btn-sm btn-outline-danger" onclick="loadQueue()">
                <i class="fas fa-redo"></i> Retry
            </button>
        </div>
    `);
}

function renderQueue(queue) {
    if(!queue || queue.length === 0) {
        $('#queueDisplay').html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-clipboard-list fa-4x mb-3"></i>
                <p>No patients in queue</p>
                <small>Patients will appear here after check-in</small>
            </div>
        `);
        return;
    }
    
    let html = '';
    queue.forEach(function(item) {
        let statusClass = item.status || 'waiting';
        let statusText = (item.status || 'waiting').toUpperCase().replace('_', ' ');
        let patientName = item.patient_name || (item.first_name + ' ' + item.last_name) || 'Unknown';
        let doctorName = item.doctor_name || (item.doctor_fname + ' ' + item.doctor_lname) || '';
        let serialNumber = item.serial_number || 'N/A';
        
        html += `
            <div class="queue-item ${statusClass}">
                <div class="d-flex justify-content-between align-items-center">
                    <div style="min-width: 80px;">
                        <span class="badge bg-${statusClass == 'in_progress' ? 'success' : 'warning'} fs-5 p-2">
                            #${serialNumber}
                        </span>
                    </div>
                    <div class="flex-grow-1 px-3">
                        <strong>${escapeHtml(patientName)}</strong>
                        <br><small class="text-muted">Dr. ${escapeHtml(doctorName)}</small>
                        <br><small class="text-muted"><i class="far fa-clock"></i> ${item.shift == 'morning' ? 'Morning Shift' : 'Evening Shift'}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-${statusClass == 'in_progress' ? 'success' : 'warning'} mb-2">
                            ${statusText}
                        </span>
                        <div class="mt-1">
                            ${statusClass == 'waiting' ? 
                                `<button class="btn btn-sm btn-success call-patient" data-id="${item.id}" data-serial="${serialNumber}">
                                    <i class="fas fa-bullhorn me-1"></i>Call
                                </button>` : 
                                (statusClass == 'in_progress' ? 
                                    `<div class="btn-group btn-group-sm" role="group">
                                        <a href="${BASE_URL}/prescriptions/create?appointment_id=${item.appointment_id}" 
                                           class="btn btn-prescription" title="Create Prescription">
                                            <i class="fas fa-prescription"></i> Rx
                                        </a>
                                        <button class="btn btn-info complete-consultation" data-appointment-id="${item.appointment_id}">
                                            <i class="fas fa-check me-1"></i>Complete
                                        </button>
                                    </div>` : '')
                            }
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    $('#queueDisplay').html(html);
    
    // Attach event handlers
    $('.call-patient').off('click').on('click', function() {
        let queueId = $(this).data('id');
        let serialNumber = $(this).data('serial');
        callPatient(queueId, serialNumber);
    });
    
    $('.complete-consultation').off('click').on('click', function() {
        let appointmentId = $(this).data('appointment-id');
        completeConsultation(appointmentId);
    });
}

function renderCurrentServing(currentServing) {
    if(currentServing && currentServing.id) {
        let serialNumber = currentServing.serial_number || 'N/A';
        let patientName = currentServing.patient_name || (currentServing.first_name + ' ' + currentServing.last_name) || 'Unknown';
        let doctorName = currentServing.doctor_name || (currentServing.doctor_fname + ' ' + currentServing.doctor_lname) || '';
        
        $('#currentServingDisplay').html(`
            <div class="serving-number" style="font-size: 48px; color: #10b981;">
                #${serialNumber}
            </div>
            <h5 class="mt-2">${escapeHtml(patientName)}</h5>
            <p class="text-muted mb-0">Dr. ${escapeHtml(doctorName)}</p>
        `);
    } else {
        $('#currentServingDisplay').html(`
            <div style="font-size: 48px;" class="text-muted">
                <i class="fas fa-user-clock"></i>
            </div>
            <p class="text-muted py-3">No patient being served</p>
        `);
    }
}

function updateStatistics(stats) {
    $('#waitingCount').text(stats.waiting || 0);
    $('#inProgressCount').text(stats.in_progress || 0);
    $('#completedCount').text(stats.completed || 0);
}

function callPatient(queueId, serialNumber) {
    Swal.fire({
        title: 'Call Patient',
        text: `Call patient #${serialNumber}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Call',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/api/call-patient',
                method: 'POST',
                data: { queue_id: queueId },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        // Play voice announcement
                        let msg = new SpeechSynthesisUtterance(`Attention please, patient number ${response.queue_number || serialNumber}, please proceed to consultation room`);
                        msg.lang = 'en-US';
                        msg.rate = 0.9;
                        window.speechSynthesis.cancel();
                        window.speechSynthesis.speak(msg);
                        
                        Swal.fire({
                            title: 'Called!',
                            html: `Patient <strong>${response.queue_number || serialNumber}</strong> has been called.`,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        loadQueue();
                    } else {
                        Swal.fire('Error', response.message || 'Failed to call patient', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error', 'error');
                }
            });
        }
    });
}

function completeConsultation(appointmentId) {
    Swal.fire({
        title: 'Complete Consultation?',
        text: 'This will mark the appointment as completed and remove patient from queue.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, complete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if(result.isConfirmed) {
            $.ajax({
                url: BASE_URL + '/api/update-appointment-status',
                method: 'POST',
                data: { appointment_id: appointmentId, status: 'completed' },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        Swal.fire('Success', 'Consultation completed', 'success');
                        loadQueue();
                    } else {
                        Swal.fire('Error', response.message || 'Failed to complete', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Server error', 'error');
                }
            });
        }
    });
}

function showError(message) {
    $('#queueDisplay').html(`
        <div class="text-center py-5 text-danger">
            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
            <p>${message}</p>
            <button class="btn btn-sm btn-outline-danger" onclick="loadQueue()">
                <i class="fas fa-redo"></i> Retry
            </button>
        </div>
    `);
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