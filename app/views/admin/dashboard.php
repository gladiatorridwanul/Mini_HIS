<div class="row">
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white stat-card">
            <div class="card-body">
                <h6>Total Doctors</h6>
                <h2><?php echo $stats['totalDoctors']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white stat-card">
            <div class="card-body">
                <h6>Total Patients</h6>
                <h2><?php echo $stats['totalPatients']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white stat-card">
            <div class="card-body">
                <h6>Today's Appointments</h6>
                <h2><?php echo $stats['todayAppointments']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-danger text-white stat-card">
            <div class="card-body">
                <h6>Today's Revenue</h6>
                <h2>$<?php echo number_format($stats['todayRevenue'], 2); ?></h2>
            </div>
        </div>
    </div>
</div>
<div class="alert alert-info">Welcome to UniDia HMS, <?php echo $_SESSION['user_name']; ?>!</div>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><a href="/unidia/public/patient/register" class="btn btn-primary w-100">Register Patient</a></div>
                    <div class="col-md-3"><a href="/unidia/public/admin/users/create" class="btn btn-info w-100">Add User</a></div>
                    <div class="col-md-3"><a href="/unidia/public/patient/book-appointment" class="btn btn-success w-100">Book Appointment</a></div>
                    <div class="col-md-3"><a href="/unidia/public/doctor/create" class="btn btn-secondary w-100">Add Doctor</a></div>
                </div>
            </div>
        </div>
    </div>
</div>