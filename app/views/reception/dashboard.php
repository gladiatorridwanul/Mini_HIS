<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    /* ========== ADD PRESCRIPTION BUTTON STYLE ========== */
    .btn-prescription {
        background: #2563eb;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .btn-prescription:hover {
        background: #1d4ed8;
        color: white;
        transform: scale(1.02);
    }
    /* ================================================ */
    
    .queue-item {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
        padding: 12px 15px;
        margin-bottom: 8px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .queue-item.waiting {
        border-left-color: #f59e0b;
        background: #fffbeb;
    }
    .queue-item.in_progress {
        border-left-color: #10b981;
        background: #f0fdf4;
    }
    .queue-actions {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: wrap;
    }
    .stat-icon {
        font-size: 28px;
        opacity: 0.3;
        margin-right: 10px;
    }
    .btn-quick {
        padding: 12px;
        border-radius: 10px;
        font-weight: 500;
        transition: all 0.2s;
        text-align: center;
        display: block;
        text-decoration: none;
        margin-bottom: 10px;
    }
    .btn-quick:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
</style>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Today's Check-ins</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo count($todayCheckins); ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-calendar-check fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Waiting Patients</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $waitingPatients; ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-clock fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Available Doctors</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $availableDoctors; ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-user-md fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Appointments</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $totalAppointments; ?></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-list fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Today's Queue Status</h6>
                <span class="badge bg-primary">Live</span>
            </div>
            <div class="card-body">
                <div class="queue-display">
                    <?php if(!empty($todayCheckins)): ?>
                        <?php foreach($todayCheckins as $queue): ?>
                        <div class="queue-item <?php echo $queue['status']; ?> d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-<?php echo $queue['status'] == 'in_progress' ? 'success' : 'warning'; ?> fs-5 p-2">
                                    #<?php echo $queue['serial_number']; ?>
                                </span>
                            </div>
                            <div>
                                <strong><?php echo $queue['first_name'] . ' ' . $queue['last_name']; ?></strong>
                                <br><small class="text-muted">Dr. <?php echo $queue['doctor_fname'] . ' ' . $queue['doctor_lname']; ?></small>
                            </div>
                            <div>
                                <span class="badge bg-<?php echo $queue['status'] == 'waiting' ? 'warning' : 'info'; ?>">
                                    <?php echo ucfirst($queue['status']); ?>
                                </span>
                            </div>
                            <div class="queue-actions">
                                <?php if($queue['status'] == 'waiting'): ?>
                                <button class="btn btn-sm btn-success call-patient" data-id="<?php echo $queue['id']; ?>">
                                    <i class="fas fa-bullhorn"></i> Call
                                </button>
                                <?php endif; ?>
                                <?php if($queue['status'] == 'in_progress'): ?>
                                <!-- ========== ADDED CREATE PRESCRIPTION BUTTON ========== -->
                                <a href="<?php echo BASE_URL; ?>/prescriptions/create?appointment_id=<?php echo $queue['appointment_id']; ?>" 
                                   class="btn-prescription" title="Create Prescription">
                                    <i class="fas fa-prescription"></i> Rx
                                </a>
                                <!-- ==================================================== -->
                                <button class="btn btn-sm btn-info complete-consultation" data-appointment-id="<?php echo $queue['appointment_id']; ?>">
                                    <i class="fas fa-check"></i> Complete
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-clipboard-list fa-4x mb-3"></i>
                            <p>No patients in queue</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-success text-white">
                <h6 class="m-0 font-weight-bold">Quick Actions</h6>
            </div>
            <div class="card-body">
                <a href="<?php echo BASE_URL; ?>/reception/check-in" class="btn btn-primary btn-quick">
                    <i class="fas fa-clipboard-check fa-lg me-2"></i> Patient Check-in
                </a>
                <a href="<?php echo BASE_URL; ?>/reception/select-patient" class="btn btn-success btn-quick">
                    <i class="fas fa-calendar-plus fa-lg me-2"></i> New Appointment
                </a>
                <a href="<?php echo BASE_URL; ?>/patient/register" class="btn btn-info btn-quick">
                    <i class="fas fa-user-plus fa-lg me-2"></i> Register Patient
                </a>
                <a href="<?php echo BASE_URL; ?>/reception/queue" class="btn btn-warning btn-quick">
                    <i class="fas fa-list-ol fa-lg me-2"></i> Full Queue View
                </a>
                <!-- ========== ADDED PRESCRIPTION SHORTCUT ========== -->
                <a href="<?php echo BASE_URL; ?>/prescriptions" class="btn btn-secondary btn-quick" style="background: #6b7280;">
                    <i class="fas fa-prescription fa-lg me-2"></i> Prescriptions
                </a>
                <!-- ================================================ -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.call-patient').click(function() {
        var queueId = $(this).data('id');
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.post('<?php echo BASE_URL; ?>/reception/call-patient', {queue_id: queueId}, function(response) {
            if(response.success) {
                var utterance = new SpeechSynthesisUtterance('Patient number ' + response.queue_number + ', please proceed to consultation room');
                window.speechSynthesis.speak(utterance);
                setTimeout(function() { 
                    location.reload(); 
                }, 1000);
            } else {
                alert('Failed to call patient: ' + response.message);
                $btn.prop('disabled', false).html('<i class="fas fa-bullhorn"></i> Call');
            }
        }).fail(function() {
            alert('Error calling patient. Please try again.');
            $btn.prop('disabled', false).html('<i class="fas fa-bullhorn"></i> Call');
        });
    });
    
    // ========== COMPLETE CONSULTATION HANDLER - UPDATED ==========
    $('.complete-consultation').click(function() {
        var appointmentId = $(this).data('appointment-id');
        var $btn = $(this);
        
        Swal.fire({
            title: 'Complete Consultation?',
            text: 'This will mark the consultation as completed and update the bill status.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Complete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.post('<?php echo BASE_URL; ?>/reception/complete-consultation', {appointment_id: appointmentId}, function(response) {
                    if(response.success) {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Consultation completed successfully',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        setTimeout(function() { 
                            location.reload(); 
                        }, 1500);
                    } else {
                        Swal.fire('Error', response.message || 'Failed to complete consultation', 'error');
                        $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Complete');
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Failed to complete consultation. Please try again.', 'error');
                    $btn.prop('disabled', false).html('<i class="fas fa-check"></i> Complete');
                });
            }
        });
    });
    // ========================================================
    
    // Auto refresh every 60 seconds
    setInterval(function() { 
        location.reload(); 
    }, 60000);
});
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>