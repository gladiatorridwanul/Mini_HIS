<?php
if (!defined('BASE_URL')) {
    define('BASE_URL', '/unidia/public');
}
?>

<style>
    /* Additional styles specific to book appointment page */
    .search-container { position: relative; }
    .search-input { width: 100%; padding: 12px 15px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; }
    .search-input:focus { outline: none; border-color: #10b981; }
    
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        border: 1px solid #e5e7eb;
    }
    .search-result-item { padding: 12px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
    .search-result-item:hover { background: #f0fdf4; }
    
    .selected-info { background: #f0fdf4; border-radius: 8px; padding: 12px; margin-top: 12px; border-left: 3px solid #10b981; }
    
    .shift-container { display: flex; gap: 15px; margin-top: 10px; }
    .shift-card {
        flex: 1;
        cursor: pointer;
        padding: 15px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        text-align: center;
        background: white;
        transition: all 0.3s ease;
    }
    .shift-card:hover { border-color: #10b981; background: #f0fdf4; }
    .shift-card.active { background: #10b981; color: white; border-color: #10b981; }
    .shift-card i { font-size: 24px; color: #10b981; margin-bottom: 8px; display: block; }
    .shift-card.active i { color: white; }
    .shift-card h4 { font-size: 16px; margin-bottom: 5px; }
    .shift-card small { font-size: 11px; opacity: 0.8; display: block; }
    
    .availability-info { background: #fef3c7; border-left: 3px solid #f59e0b; padding: 12px; border-radius: 8px; margin-top: 15px; }
    .availability-info.success { background: #d1fae5; border-left-color: #10b981; }
    .availability-info.error { background: #fee2e2; border-left-color: #ef4444; }
    
    .summary-card { background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
    .summary-amount { font-size: 28px; font-weight: 700; margin: 10px 0; color: #10b981; }
    
    .book-btn {
        background: #10b981;
        border: none;
        padding: 14px 24px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 50px;
        width: 100%;
        color: white;
        display: block;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .book-btn:hover:not(:disabled) { background: #059669; transform: translateY(-2px); }
    .book-btn:disabled { background: #9ca3af; cursor: not-allowed; opacity: 0.6; }
    
    .loading-spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid #f3f3f3; border-top: 2px solid #10b981; border-radius: 50%; animation: spin 0.8s linear infinite; margin-right: 8px; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    
    .alert-toast { position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease-out; border-radius: 8px; padding: 12px 18px; }
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    
    .form-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #e5e7eb;
    }
    .card-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .card-title i { width: 30px; height: 30px; background: #10b981; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 14px; }
    
    .modern-datepicker {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
    }
</style>

<div class="row">
    <div class="col-lg-7">
        <!-- Patient Search -->
        <div class="form-card">
            <div class="card-title"><i class="fas fa-user"></i><span>Patient Information</span></div>
            <div class="search-container">
                <input type="text" id="patient_search" class="search-input" placeholder="🔍 Search by name, phone number or patient ID...">
                <div id="patient_results" class="search-results"></div>
                <input type="hidden" id="patient_id">
            </div>
            <div id="selected_patient_info" class="selected-info" style="display: none;">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-user-circle fa-2x" style="color: #10b981;"></i>
                    <div>
                        <strong id="selected_patient_name"></strong><br>
                        <small id="selected_patient_details" class="text-muted"></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Doctor Search -->
        <div class="form-card">
            <div class="card-title"><i class="fas fa-user-md"></i><span>Doctor Information</span></div>
            <div class="search-container">
                <input type="text" id="doctor_search" class="search-input" placeholder="🔍 Search by doctor name or specialization...">
                <div id="doctor_results" class="search-results"></div>
                <input type="hidden" id="doctor_id">
            </div>
            <div id="selected_doctor_info" class="selected-info" style="display: none;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong id="selected_doctor_name"></strong><br>
                        <small id="selected_doctor_details" class="text-muted"></small>
                    </div>
                    <div class="text-end">
                        <small>Consultation Fee</small>
                        <strong class="d-block" id="consultation_fee_display" style="color: #10b981;">$0</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date & Shift Selection -->
        <div class="form-card">
            <div class="card-title"><i class="fas fa-calendar-alt"></i><span>Select Date & Shift</span></div>
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">📅 Appointment Date</label>
                    <input type="date" id="appointment_date" class="modern-datepicker">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-bold">⏰ Select Shift</label>
                    <div class="shift-container">
                        <div class="shift-card" data-shift="morning">
                            <i class="fas fa-sun"></i>
                            <h4>Morning</h4>
                            <small>9:00 AM - 1:00 PM</small>
                        </div>
                        <div class="shift-card" data-shift="evening">
                            <i class="fas fa-moon"></i>
                            <h4>Evening</h4>
                            <small>2:00 PM - 6:00 PM</small>
                        </div>
                    </div>
                </div>
            </div>
            <div id="availability_info" style="display: none;"></div>
        </div>

        <!-- Service Selection -->
        <div class="form-card">
            <div class="card-title"><i class="fas fa-flask"></i><span>Additional Services</span></div>
            <select id="service_id" class="form-select">
                <option value="0">-- No additional service --</option>
                <?php foreach($services as $s): ?>
                <option value="<?php echo $s['id']; ?>" data-price="<?php echo $s['default_price']; ?>">
                    <?php echo htmlspecialchars($s['service_name']); ?> - $<?php echo number_format($s['default_price'], 2); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Symptoms & Notes -->
        <div class="form-card">
            <div class="card-title"><i class="fas fa-notes-medical"></i><span>Symptoms & Special Notes</span></div>
            <textarea id="symptoms" class="form-control mb-3" rows="3" placeholder="Describe symptoms or reason for visit..."></textarea>
            <textarea id="special_note" class="form-control" rows="2" placeholder="Any special instructions or notes..."></textarea>
        </div>
    </div>

    <div class="col-lg-5">
        <!-- Payment Summary -->
        <div class="summary-card">
            <div class="text-center">
                <i class="fas fa-receipt fa-3x mb-3" style="color: #10b981;"></i>
                <h3>Payment Summary</h3>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3">
                <span>Consultation Fee:</span>
                <strong id="summary_consultation">$0.00</strong>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span>Service Fee:</span>
                <strong id="summary_service">$0.00</strong>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span>Discount:</span>
                <div class="d-flex align-items-center gap-2">
                    <input type="number" id="discount" class="form-control form-control-sm" style="width: 100px;" value="0" min="0" step="10">
                    <span>USD</span>
                </div>
            </div>
            <hr>
            <div class="d-flex justify-content-between mb-3">
                <span class="fw-bold">Total Amount:</span>
                <strong class="summary-amount" id="summary_total">$0.00</strong>
            </div>
            <hr>
            <div class="mb-3">
                <label class="form-label">Payment Method</label>
                <select id="payment_method" class="form-select">
                    <option value="cash">💵 Cash</option>
                    <option value="card">💳 Credit/Debit Card</option>
                    <option value="mobile_banking">📱 Mobile Banking</option>
                    <option value="insurance">🏥 Insurance</option>
                </select>
            </div>
        </div>

        <!-- Book Appointment Button -->
        <button type="button" id="book_btn" class="book-btn" disabled>
            <i class="fas fa-check-circle"></i> Book Appointment
        </button>
        <div id="booking_status" class="text-center mt-3" style="font-size: 13px; padding: 10px; background: #f8f9fa; border-radius: 10px;">
            <span id="status_icon">⚠️</span>
            <span id="status_message" style="color: #f59e0b;">Please select: Patient, Doctor, Date & Shift</span>
        </div>
    </div>
</div>

<div id="alert_container"></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

let state = {
    patientId: null,
    doctorId: null,
    appointmentDate: null,
    shift: null,
    consultationFee: 0,
    serviceFee: 0
};

// Set today's date as default
const today = new Date();
const year = today.getFullYear();
const month = String(today.getMonth() + 1).padStart(2, '0');
const day = String(today.getDate()).padStart(2, '0');
const todayDate = `${year}-${month}-${day}`;
$('#appointment_date').val(todayDate);
state.appointmentDate = todayDate;

function updateStatusMessage() {
    const missing = [];
    if(!state.patientId) missing.push('Patient');
    if(!state.doctorId) missing.push('Doctor');
    if(!state.shift) missing.push('Shift');
    
    if(missing.length > 0) {
        $('#status_icon').html('⚠️');
        $('#status_message').html('Please select: ' + missing.join(', ')).css('color', '#f59e0b');
        $('#book_btn').prop('disabled', true);
    } else {
        $('#status_icon').html('✅');
        $('#status_message').html('All ready! Click to book appointment.').css('color', '#10b981');
        $('#book_btn').prop('disabled', false);
    }
}

function showAlert(message, type = 'success') {
    const colors = { success: '#10b981', error: '#ef4444', warning: '#f59e0b', info: '#3b82f6' };
    const alertDiv = $(`<div class="alert-toast" style="background: ${colors[type]}; color: white; padding: 12px 18px; border-radius: 8px; z-index: 9999;">${message}</div>`);
    $('#alert_container').html(alertDiv);
    setTimeout(() => { alertDiv.fadeOut(500, function() { $(this).remove(); }); }, 4000);
}

function calculateTotal() {
    const discount = parseFloat($('#discount').val()) || 0;
    const total = state.consultationFee + state.serviceFee - discount;
    const finalTotal = total < 0 ? 0 : total;
    $('#summary_consultation').text('$' + state.consultationFee.toFixed(2));
    $('#summary_service').text('$' + state.serviceFee.toFixed(2));
    $('#summary_total').text('$' + finalTotal.toFixed(2));
}

function checkAvailability() {
    if(!state.doctorId || !state.appointmentDate || !state.shift) {
        $('#availability_info').hide();
        return;
    }
    
    $('#availability_info').show();
    $('#availability_info').html('<div class="availability-info"><div class="loading-spinner"></div> Checking available slots...</div>');
    
    $.ajax({
        url: BASE_URL + '/api/get-available-slots',
        method: 'GET',
        data: {
            doctor_id: state.doctorId,
            date: state.appointmentDate,
            shift: state.shift
        },
        dataType: 'json',
        success: function(response) {
            if(response.has_availability) {
                $('#availability_info').html(`
                    <div class="availability-info success">
                        <i class="fas fa-check-circle"></i>
                        <strong>✓ Slots Available!</strong><br>
                        <span style="font-size: 18px; font-weight: bold;">Your Serial Number: ${response.next_serial}</span><br>
                        <small>Shift timing: ${response.start_time} - ${response.end_time} | 
                        Available slots: ${response.available_slots} / ${response.max_patients}</small>
                    </div>
                `);
            } else {
                $('#availability_info').html(`
                    <div class="availability-info error">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong>No slots available!</strong><br>
                        This shift is fully booked.<br>
                        <small>Max patients: ${response.max_patients} | Booked: ${response.booked_count}</small>
                    </div>
                `);
                $('#book_btn').prop('disabled', true);
            }
        },
        error: function() {
            $('#availability_info').html(`<div class="availability-info error"><i class="fas fa-exclamation-circle"></i> Error checking availability</div>`);
        }
    });
}

// Search Patients
let patientTimeout;
$('#patient_search').on('input', function() {
    clearTimeout(patientTimeout);
    const search = $(this).val();
    if(search.length < 2) { $('#patient_results').hide(); return; }
    
    patientTimeout = setTimeout(() => {
        $.ajax({
            url: BASE_URL + '/api/search-patients',
            method: 'GET',
            data: { search: search },
            success: function(patients) {
                if(patients.length === 0) {
                    $('#patient_results').html('<div class="search-result-item">No patients found</div>').show();
                    return;
                }
                let html = '';
                patients.forEach(p => {
                    html += `<div class="search-result-item" onclick="selectPatient(${p.id}, '${p.first_name} ${p.last_name}', '${p.patient_code} | ${p.phone}')">
                                <strong>${p.first_name} ${p.last_name}</strong><br>
                                <small>${p.patient_code} | ${p.phone}</small>
                            </div>`;
                });
                $('#patient_results').html(html).show();
            }
        });
    }, 300);
});

window.selectPatient = function(id, name, details) {
    state.patientId = id;
    $('#patient_search').val(name);
    $('#selected_patient_name').html(name);
    $('#selected_patient_details').html(details);
    $('#selected_patient_info').fadeIn();
    $('#patient_results').hide();
    updateStatusMessage();
    showAlert('✓ Patient selected: ' + name, 'success');
};

// Search Doctors
let doctorTimeout;
$('#doctor_search').on('input', function() {
    clearTimeout(doctorTimeout);
    const search = $(this).val();
    if(search.length < 2) { $('#doctor_results').hide(); return; }
    
    doctorTimeout = setTimeout(() => {
        $.ajax({
            url: BASE_URL + '/api/search-doctors',
            method: 'GET',
            data: { search: search },
            success: function(doctors) {
                if(doctors.length === 0) {
                    $('#doctor_results').html('<div class="search-result-item">No doctors found</div>').show();
                    return;
                }
                let html = '';
                doctors.forEach(d => {
                    html += `<div class="search-result-item" onclick="selectDoctor(${d.id}, 'Dr. ${d.first_name} ${d.last_name}', '${d.specialization}', ${d.consultation_fee})">
                                <strong>Dr. ${d.first_name} ${d.last_name}</strong><br>
                                <small>${d.specialization} | Fee: $${d.consultation_fee}</small>
                            </div>`;
                });
                $('#doctor_results').html(html).show();
            }
        });
    }, 300);
});

window.selectDoctor = function(id, name, specialization, fee) {
    state.doctorId = id;
    state.consultationFee = fee;
    $('#doctor_search').val(name);
    $('#selected_doctor_name').html(name);
    $('#selected_doctor_details').html(specialization);
    $('#consultation_fee_display').text('$' + fee);
    $('#selected_doctor_info').fadeIn();
    $('#doctor_results').hide();
    calculateTotal();
    if(state.shift) checkAvailability();
    updateStatusMessage();
    showAlert('✓ Doctor selected: ' + name, 'success');
};

// Shift Selection
$('.shift-card').click(function() {
    $('.shift-card').removeClass('active');
    $(this).addClass('active');
    state.shift = $(this).data('shift');
    if(state.doctorId && state.appointmentDate) checkAvailability();
    updateStatusMessage();
    showAlert('✓ ' + state.shift.charAt(0).toUpperCase() + state.shift.slice(1) + ' shift selected', 'success');
});

// Date Change
$('#appointment_date').change(function() {
    state.appointmentDate = $(this).val();
    if(state.doctorId && state.shift) checkAvailability();
    updateStatusMessage();
});

// Service & Discount
$('#service_id').change(function() {
    state.serviceFee = parseFloat($(this).find(':selected').data('price')) || 0;
    calculateTotal();
});
$('#discount').on('input', calculateTotal);

// BOOK APPOINTMENT BUTTON
$('#book_btn').click(function() {
    if(!state.patientId || !state.doctorId || !state.appointmentDate || !state.shift) {
        showAlert('Please select patient, doctor, date and shift', 'error');
        return;
    }
    
    const $btn = $(this);
    $btn.prop('disabled', true).html('<span class="loading-spinner"></span> Booking appointment...');
    
    showAlert('Processing your appointment booking...', 'info');
    
    $.ajax({
        url: BASE_URL + '/api/book-appointment',
        method: 'POST',
        data: {
            patient_id: state.patientId,
            doctor_id: state.doctorId,
            appointment_date: state.appointmentDate,
            shift: state.shift,
            service_id: $('#service_id').val(),
            appointment_type: 'regular',
            symptoms: $('#symptoms').val(),
            special_note: $('#special_note').val(),
            payment_method: $('#payment_method').val(),
            discount: $('#discount').val() || 0
        },
        dataType: 'json',
        success: function(response) {
            console.log("Response:", response);
            if(response.success) {
                showAlert('✓ ' + response.message, 'success');
                window.open(BASE_URL + '/api/print-serial?appointment_id=' + response.appointment_id, '_blank');
                setTimeout(function() {
                    showAlert('Redirecting to appointment list...', 'info');
                }, 1000);
                setTimeout(function() {
                    window.location.href = BASE_URL + '/reception/appointments';
                }, 2000);
            } else {
                showAlert('✗ ' + (response.message || 'Booking failed'), 'error');
                $btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Book Appointment');
            }
        },
        error: function(xhr) {
            console.error("AJAX Error:", xhr);
            let errorMsg = 'Server error. Please try again.';
            try {
                const response = JSON.parse(xhr.responseText);
                if(response.message) errorMsg = response.message;
            } catch(e) {}
            showAlert('✗ ' + errorMsg, 'error');
            $btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> Book Appointment');
        }
    });
});

// Close search results on click outside
$(document).click(function(e) {
    if(!$(e.target).closest('#patient_search').length) $('#patient_results').hide();
    if(!$(e.target).closest('#doctor_search').length) $('#doctor_results').hide();
});

// Initialize
calculateTotal();
updateStatusMessage();
</script>