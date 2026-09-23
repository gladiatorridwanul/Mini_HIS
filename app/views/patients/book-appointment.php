<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-calendar-plus me-2"></i>Book Appointment</h5>
        <small>Patient: <?php echo htmlspecialchars($patient['full_name'] ?: $patient['first_name'] . ' ' . $patient['last_name']); ?></small>
    </div>
    <div class="card-body">
        <form method="POST" action="/unidia/public/api/book-appointment">
            <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label>Select Doctor <span class="text-danger">*</span></label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">Select Doctor</option>
                        <?php foreach($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>">
                                Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?> 
                                (<?php echo $doctor['specialization']; ?>) - ৳<?php echo $doctor['consultation_fee']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Appointment Date <span class="text-danger">*</span></label>
                    <input type="date" name="appointment_date" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Shift <span class="text-danger">*</span></label>
                    <select name="shift" class="form-select" required>
                        <option value="">Select Shift</option>
                        <option value="morning">Morning (09:00 AM - 01:00 PM)</option>
                        <option value="evening">Evening (02:00 PM - 06:00 PM)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label>Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="mobile_banking">Mobile Banking</option>
                    </select>
                </div>
                <div class="col-md-12 mb-3">
                    <label>Symptoms</label>
                    <textarea name="symptoms" class="form-control" rows="3" placeholder="Describe symptoms..."></textarea>
                </div>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Book Appointment</button>
                <a href="/unidia/public/patient/list" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>