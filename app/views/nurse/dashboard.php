<div class="container-fluid">
    <h4 class="mb-4"><i class="fas fa-user-nurse me-2"></i>Nurse Dashboard</h4>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Today's Patients</div>
                    <div class="h5 mb-0"><?php echo count($todayPatients); ?></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Vital Signs Pending</div>
                    <div class="h5 mb-0"><?php echo $vitalSignsPending; ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Today's Patient List</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr><th>Patient</th><th>Doctor</th><th>Time</th><th>Vital Signs</th><th>Action</th></tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($todayPatients)): ?>
                                    <?php foreach($todayPatients as $patient): ?>
                                    <tr>
                                        <td><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></td>
                                        <td>Dr. <?php echo $patient['doctor_name']; ?></td>
                                        <td><?php echo date('h:i A', strtotime($patient['start_time'])); ?></td>
                                        <td>
                                            <?php if($patient['vitals_taken']): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="recordVitals(<?php echo $patient['patient_id']; ?>, <?php echo $patient['appointment_id']; ?>)">
                                                <i class="fas fa-heartbeat me-1"></i>Record Vitals
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center text-muted py-4">No patients for today</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Doctor Schedules</h6>
                </div>
                <div class="card-body">
                    <?php if(!empty($doctorSchedule)): ?>
                        <?php foreach($doctorSchedule as $schedule): ?>
                        <div class="mb-3 p-2 bg-light rounded">
                            <strong>Dr. <?php echo $schedule['first_name'] . ' ' . $schedule['last_name']; ?></strong>
                            <br><small class="text-muted"><?php echo $schedule['specialization']; ?></small>
                            <br><small><i class="fas fa-clock me-1"></i><?php echo $schedule['start_time'] . ' - ' . $schedule['end_time']; ?></small>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No doctors available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vital Signs Modal -->
<div class="modal fade" id="vitalSignsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-heartbeat me-2"></i>Record Vital Signs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="vitalSignsForm">
                    <input type="hidden" name="patient_id" id="vsPatientId">
                    <input type="hidden" name="appointment_id" id="vsAppointmentId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Blood Pressure (Systolic)</label>
                            <input type="number" name="bp_systolic" class="form-control" placeholder="mmHg">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Blood Pressure (Diastolic)</label>
                            <input type="number" name="bp_diastolic" class="form-control" placeholder="mmHg">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Heart Rate</label>
                            <input type="number" name="heart_rate" class="form-control" placeholder="bpm">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Temperature</label>
                            <input type="number" step="0.1" name="temperature" class="form-control" placeholder="°C">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Respiratory Rate</label>
                            <input type="number" name="respiratory_rate" class="form-control" placeholder="breaths/min">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Oxygen Saturation</label>
                            <input type="number" name="oxygen_saturation" class="form-control" placeholder="%">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Weight (kg)</label>
                            <input type="number" step="0.1" name="weight" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Height (cm)</label>
                            <input type="number" step="0.1" name="height" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>BMI</label>
                            <input type="text" class="form-control" id="bmi" readonly>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>Save Vital Signs
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function recordVitals(patientId, appointmentId) {
    $('#vsPatientId').val(patientId);
    $('#vsAppointmentId').val(appointmentId);
    $('#vitalSignsModal').modal('show');
}

// Auto-calculate BMI
$('input[name="weight"], input[name="height"]').on('input', function() {
    var weight = parseFloat($('input[name="weight"]').val()) || 0;
    var height = parseFloat($('input[name="height"]').val()) || 0;
    if(weight > 0 && height > 0) {
        var bmi = weight / ((height/100) * (height/100));
        $('#bmi').val(bmi.toFixed(1));
    }
});

$('#vitalSignsForm').submit(function(e) {
    e.preventDefault();
    $.post('<?php echo BASE_URL; ?>/nurse/record-vitals', $(this).serialize(), function(response) {
        if(response.success) {
            Swal.fire('Saved!', 'Vital signs recorded successfully', 'success');
            $('#vitalSignsModal').modal('hide');
            location.reload();
        }
    });
});
</script>