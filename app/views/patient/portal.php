<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                <h4><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></h4>
                <p>Patient Code: <?php echo $patient['patient_code']; ?></p>
                <hr>
                <p><strong>Phone:</strong> <?php echo $patient['phone']; ?></p>
                <p><strong>Email:</strong> <?php echo $patient['email']; ?></p>
                <a href="/unidia/public/patient/portal/logout" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Upcoming Appointments</div>
            <div class="card-body">
                <?php if(empty($upcomingAppointments)): ?>
                    <p class="text-muted">No upcoming appointments</p>
                <?php else: ?>
                    <table class="table">
                        <thead><tr><th>Date</th><th>Doctor</th><th>Time</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach($upcomingAppointments as $apt): ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($apt['appointment_date'])); ?></td>
                                <td>Dr. <?php echo $apt['doctor_name']; ?></td>
                                <td><?php echo date('h:i A', strtotime($apt['start_time'])); ?></td>
                                <td><?php echo $apt['status']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                <a href="/unidia/public/patient/book-appointment" class="btn btn-primary">Book New Appointment</a>
            </div>
        </div>
    </div>
</div>