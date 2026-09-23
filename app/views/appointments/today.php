<div class="row">
    <div class="col-md-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>Today's Appointments (<?php echo date('F j, Y'); ?>)</h5>
            </div>
            <div class="card-body">
                <div id="todayAppointmentsList">
                    <div class="text-center py-4">Loading...</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12">
        <div class="card shadow">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Appointments</h5>
            </div>
            <div class="card-body">
                <div id="upcomingAppointmentsList">
                    <div class="text-center py-4">Loading...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    loadTodayAppointments();
    loadUpcomingAppointments();
});

function loadTodayAppointments() {
    $.get('/unidia/public/api/today-appointments', function(data) {
        var html = '';
        if(data.length > 0) {
            html = '<div class="table-responsive"><table class="table table-bordered"><thead class="table-dark"><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Type</th><th>Status</th><th>Action</th></tr></thead><tbody>';
            data.forEach(function(appt) {
                var statusBadge = appt.status == 'scheduled' ? 'primary' : (appt.status == 'confirmed' ? 'success' : 'warning');
                html += `<tr>
                    <td>${appt.start_time}</td>
                    <td><strong>${appt.patient_name}</strong><br><small>${appt.patient_code}</small></td>
                    <td>Dr. ${appt.doctor_name}</td>
                    <td>${appt.appointment_type}</td>
                    <td><span class="badge bg-${statusBadge}">${appt.status}</span></td>
                    <td>
                        <a href="<?php echo BASE_URL; ?>/prescriptions/create?appointment_id=<?php echo $appt['id']; ?>" 
                           class="btn btn-sm btn-primary" title="Create Prescription">
                            <i class="fas fa-prescription"></i>
                        </a>
                        <button class="btn btn-sm btn-danger" onclick="cancelAppt(<?php echo $appt['id']; ?>)">Cancel</button>
                    </td>
                    <td><button class="btn btn-sm btn-danger" onclick="cancelAppt(${appt.id})">Cancel</button></td>
                </tr>`;
            });
            html += '</tbody></table></div>';
        } else {
            html = '<div class="alert alert-info text-center">No appointments scheduled for today.</div>';
        }
        $('#todayAppointmentsList').html(html);
    });
}

function loadUpcomingAppointments() {
    $.get('/unidia/public/api/upcoming-appointments', function(data) {
        var html = '';
        if(data.length > 0) {
            html = '<div class="table-responsive"><table class="table table-bordered"><thead class="table-dark"><td><th>Date</th><th>Time</th><th>Patient</th><th>Doctor</th><th>Type</th><th>Status</th></tr></thead><tbody>';
            data.forEach(function(appt) {
                var statusBadge = appt.status == 'scheduled' ? 'primary' : (appt.status == 'confirmed' ? 'success' : 'warning');
                html += `<tr>
                    <td>${appt.appointment_date}</td>
                    <td>${appt.start_time}</td>
                    <td><strong>${appt.patient_name}</strong><br><small>${appt.patient_code}</small></td>
                    <td>Dr. ${appt.doctor_name}</td>
                    <td>${appt.appointment_type}</td>
                    <td><span class="badge bg-${statusBadge}">${appt.status}</span></td>
                </tr>`;
            });
            html += '</tbody></table></div>';
        } else {
            html = '<div class="alert alert-info text-center">No upcoming appointments found.</div>';
        }
        $('#upcomingAppointmentsList').html(html);
    });
}

function cancelAppt(id) {
    if(confirm('Cancel this appointment?')) {
        $.post('/unidia/public/api/cancel-appointment', {appointment_id: id}, function(response) {
            if(response.success) location.reload();
        });
    }
}
</script>