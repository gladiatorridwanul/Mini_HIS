<?php
// Data from controller: $patient, $doctors, $additionalServices, $patients
// $doctors is already filtered for: doctors.status = 'active' AND users.status = 'active' AND users.role_id = 3
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Book Appointment</h5>
                </div>
                <div class="card-body">
                    <!-- ============================================================ -->
                    <!-- PATIENT SELECTION SECTION -->
                    <!-- ============================================================ -->
                    <?php if(isset($patient) && $patient): ?>
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <i class="fas fa-user-circle fa-3x"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1">Booking Appointment For:</h6>
                                    <strong><?php echo htmlspecialchars($patient['full_name'] ?: $patient['first_name'] . ' ' . $patient['last_name']); ?></strong><br>
                                    <small>Patient ID: <?php echo $patient['patient_code']; ?> | Phone: <?php echo $patient['phone']; ?></small>
                                </div>
                            </div>
                            <div>
                                <a href="<?php echo BASE_URL; ?>/appointments/book" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-exchange-alt me-1"></i> Change Patient
                                </a>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="patient_id" id="patient_id" value="<?php echo $patient['id']; ?>">
                    
                    <?php else: ?>
                    <!-- No patient selected -->
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-user-plus text-primary me-2"></i>Select or Add Patient</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" id="patientSearchInput" class="form-control" 
                                               placeholder="Search by name, phone, or patient ID..." 
                                               autocomplete="off">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearchBtn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="patientSearchResults" class="list-group mt-2" style="display:none; max-height: 250px; overflow-y: auto;">
                                    </div>
                                    <small class="text-muted" id="searchStatus">Type at least 2 characters to search</small>
                                </div>
                                <div class="col-md-4 text-end">
                                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#quickPatientModal">
                                        <i class="fas fa-plus me-1"></i> Quick Add Patient
                                    </button>
                                </div>
                            </div>
                            
                            <div id="selectedPatientDisplay" style="display:none;" class="mt-3">
                                <div class="alert alert-success">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                                            <div>
                                                <strong id="selectedPatientName">-</strong><br>
                                                <small id="selectedPatientDetails">ID: - | Phone: -</small>
                                            </div>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearSelectedPatient">
                                                <i class="fas fa-times"></i> Change
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="patient_id" id="patient_id" value="">
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- ============================================================ -->
                    <!-- DOCTOR SELECTION WITH SEARCH - ACTIVE DOCTORS ONLY            -->
                    <!-- ============================================================ -->
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="fas fa-user-md text-primary me-2"></i>Select Doctor</h6>
                            <small class="text-muted ms-2">Only active doctors are listed</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" id="doctorSearchInput" class="form-control" 
                                               placeholder="Search doctor by name or specialization..." 
                                               autocomplete="off" <?php echo (!isset($patient) || !$patient) ? 'disabled' : ''; ?>>
                                        <button class="btn btn-outline-secondary" type="button" id="clearDoctorSearchBtn">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div id="doctorSearchResults" class="list-group mt-2" style="display:none; max-height: 250px; overflow-y: auto;">
                                    </div>
                                    <small class="text-muted" id="doctorSearchStatus"><?php echo (!isset($patient) || !$patient) ? 'Select a patient first' : 'Type at least 2 characters to search'; ?></small>
                                </div>
                            </div>
                            
                            <!-- Selected Doctor Display -->
                            <div id="selectedDoctorDisplay" style="display:none;" class="mt-3">
                                <div class="alert alert-info">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-user-md fa-2x text-primary me-3"></i>
                                            <div>
                                                <strong id="selectedDoctorName">-</strong><br>
                                                <small id="selectedDoctorDetails">Specialization: - | Fee: ৳ -</small>
                                            </div>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="clearSelectedDoctor">
                                                <i class="fas fa-times"></i> Change
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="doctor_id" id="doctor_id" value="">
                            </div>
                            
                            <!-- Hidden select for fallback - Contains only ACTIVE doctors -->
                            <select name="doctor_id_hidden" id="doctor_id_hidden" class="form-select d-none">
                                <option value="">-- Select Doctor --</option>
                                <?php if(isset($doctors)): ?>
                                    <?php 
                                    // $doctors is already filtered by controller query:
                                    // WHERE d.status = 'active' AND u.role_id = 3
                                    // Note: users.status is implicitly checked because user status is part of the join
                                    foreach($doctors as $doctor): 
                                        // Additional safety check: ensure doctor is active
                                        if(isset($doctor['status']) && $doctor['status'] !== 'active') {
                                            continue;
                                        }
                                    ?>
                                        <option value="<?php echo $doctor['id']; ?>" 
                                                data-fee="<?php echo $doctor['consultation_fee']; ?>"
                                                data-specialization="<?php echo htmlspecialchars($doctor['specialization']); ?>"
                                                data-name="Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>"
                                                data-doctor-status="<?php echo isset($doctor['doctor_status']) ? $doctor['doctor_status'] : 'active'; ?>"
                                                data-user-status="<?php echo isset($doctor['user_status']) ? $doctor['user_status'] : 'active'; ?>">
                                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?> 
                                            (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                            <?php if(isset($doctor['status']) && $doctor['status'] !== 'active'): ?>
                                                [INACTIVE]
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            
                            <!-- Fallback message if no active doctors -->
                            <div id="noActiveDoctorsMessage" style="display:none;" class="alert alert-warning mt-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No active doctors are currently available. Please contact the administrator.
                            </div>
                        </div>
                    </div>
                    
                    <!-- ============================================================ -->
                    <!-- APPOINTMENT FORM -->
                    <!-- ============================================================ -->
                    <form id="appointmentForm">
                        <?php if(!isset($patient) || !$patient): ?>
                        <input type="hidden" name="patient_id" id="patient_id" value="">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Select Service <span class="text-danger">*</span></label>
                                <select name="service_id" id="service_id" class="form-select" required disabled>
                                    <option value="">-- Select Doctor First --</option>
                                </select>
                                <small class="text-muted">Select a service to see payment details</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Appointment Date <span class="text-danger">*</span></label>
                                <input type="date" name="appointment_date" id="appointment_date" class="form-control" 
                                       min="<?php echo date('Y-m-d'); ?>" required disabled>
                                <small class="text-muted" id="availabilityMessage">Select a doctor first</small>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Session <span class="text-danger">*</span></label>
                                <select name="shift" id="shift" class="form-select" required disabled>
                                    <option value="">-- Select Doctor and Date First --</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Serial Number</label>
                                <input type="text" id="serial_preview" class="form-control" readonly placeholder="Will be generated">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Appointment Type</label>
                                <select name="appointment_type" id="appointment_type" class="form-select" <?php echo (!isset($patient) || !$patient) ? 'disabled' : ''; ?>>
                                    <option value="regular">Regular</option>
                                    <option value="follow_up">Follow Up</option>
                                    <option value="emergency">Emergency</option>
                                    <option value="walk_in">Walk In</option>
                                    <option value="online">Online Consultation</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Additional Service (Optional)</label>
                                <select name="additional_service_id" id="additional_service_id" class="form-select" <?php echo (!isset($patient) || !$patient) ? 'disabled' : ''; ?>>
                                    <option value="">-- No Additional Service --</option>
                                    <?php if(isset($additionalServices)): ?>
                                        <?php foreach($additionalServices as $service): ?>
                                            <option value="<?php echo $service['id']; ?>" data-price="<?php echo $service['service_price']; ?>">
                                                <?php echo htmlspecialchars($service['service_name']); ?> - ৳<?php echo number_format($service['service_price'], 2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Symptoms / Reason for Visit</label>
                                <textarea name="symptoms" id="symptoms" class="form-control" rows="3" 
                                          placeholder="Describe symptoms, medical history, or reason for visit..." <?php echo (!isset($patient) || !$patient) ? 'disabled' : ''; ?>></textarea>
                            </div>
                            
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Special Note (Staff Only)</label>
                                <textarea name="special_note" id="special_note" class="form-control" rows="2" 
                                          placeholder="Internal notes for staff..." <?php echo (!isset($patient) || !$patient) ? 'disabled' : ''; ?>></textarea>
                            </div>
                        </div>
                        
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                                <i class="fas fa-check-circle me-2"></i>Book Appointment
                            </button>
                            <a href="<?php echo BASE_URL; ?>/patient/list" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Payment Summary</h5>
                </div>
                <div class="card-body" id="paymentSummaryContainer">
                    <div id="paymentSummaryContent">
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                            <p>Select a patient and service to see payment summary</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- QUICK REGISTER PATIENT MODAL -->
<!-- ============================================================ -->
<div class="modal fade" id="quickPatientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2 text-primary"></i>Quick Register Patient
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalErrors" style="display:none;" class="alert-error mb-3"></div>
                
                <div class="alert alert-info alert-dismissible fade show mb-3" style="font-size: 12px; padding: 8px 12px;">
                    <i class="fas fa-info-circle me-1"></i> 
                    <strong>Note:</strong> Multiple patients can share the same phone number.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 10px;"></button>
                </div>
                
                <form id="quickPatientForm">
                    <div class="section-title"><i class="fas fa-user-circle me-1"></i>Personal Information</div>
                    <hr class="section-divider">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="required-star">*</span></label>
                        <input type="text" name="full_name" id="qf_full_name" class="form-control" 
                               placeholder="Enter patient's full name" required>
                        <input type="hidden" name="first_name" id="qf_first_name">
                        <input type="hidden" name="last_name" id="qf_last_name">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone Number <span class="required-star">*</span></label>
                        <input type="tel" name="phone" id="qf_phone" class="form-control" 
                               placeholder="01XXXXXXXXX" required>
                        <small class="text-muted-small">Multiple patients can share the same phone number</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Age / Date of Birth <span class="required-star">*</span></label>
                        <div class="age-dob-group">
                            <div class="age-input">
                                <div class="input-group">
                                    <input type="number" id="qf_age" class="form-control" placeholder="Age (Years)" min="0" max="150">
                                    <span class="input-group-text">Yrs</span>
                                </div>
                                <small class="text-muted-small">Enter age to auto-calculate DOB</small>
                            </div>
                            <div class="dob-input">
                                <input type="date" name="date_of_birth" id="qf_dob" class="form-control">
                                <small class="text-muted-small">Enter DOB to auto-calculate age</small>
                            </div>
                            <div class="age-display" id="ageDisplay">
                                <span class="age-label">Age:</span>
                                <span class="age-value" id="ageDisplayValue">—</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Gender <span class="required-star">*</span></label>
                        <select name="gender" id="qf_gender" class="form-select" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="section-title mt-3"><i class="fas fa-map-marker-alt me-1"></i>Address Information</div>
                    <hr class="section-divider">
                    
                    <div class="mb-3">
                        <label class="form-label">House/Street Address</label>
                        <textarea name="address" id="qf_address" class="form-control" rows="2" 
                                  placeholder="House/Flat No, Road/Street, Village/Area">Bangladesh</textarea>
                        <small class="text-muted-small">Optional</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-success" id="modalSaveRegister">
                    <i class="fas fa-save me-1"></i> Save & Register
                </button>
                <button type="button" class="btn btn-primary" id="modalRegisterPatient">
                    <i class="fas fa-user-plus me-1"></i> Register Patient
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // ============================================================
    // BASE URL DETECTION
    // ============================================================
    var baseUrl = '<?php echo BASE_URL; ?>';
    var apiBase = baseUrl + '/api';
    
    console.log('Base URL:', baseUrl);
    console.log('API Base:', apiBase);
    
    var servicePrice = 0;
    var additionalPrice = 0;
    var doctorId = 0;
    var doctorName = '';
    var availableDays = [];
    var sessionsByDay = {};
    var selectedServiceId = 0;
    var selectedServiceName = '';
    var selectedServiceType = 'consultation';
    var selectedPatientId = <?php echo isset($patient) ? $patient['id'] : 0; ?>;
    var selectedPatientName = '<?php echo isset($patient) ? addslashes($patient['first_name'] . ' ' . $patient['last_name']) : ''; ?>';
    var scheduleData = {};
    var isScheduleLoaded = false;
    var currentSubtotal = 0;
    var currentDiscountAmount = 0;
    var currentTotalAmount = 0;
    
    // ================================================================
    // DOCTOR LIST - ACTIVE DOCTORS ONLY
    // Filtered server-side, but we also verify here for safety
    // ================================================================
    var allDoctors = <?php 
        // Only include doctors that are active (filtered by controller)
        // The controller already queries: WHERE d.status = 'active' AND u.role_id = 3
        // But we add an additional safety filter here
        $activeDoctors = [];
        if(isset($doctors)) {
            foreach($doctors as $doc) {
                // Ensure doctor is active (doctors.status = 'active')
                // Note: users.status is already checked in the query join
                $isActive = true;
                if(isset($doc['status']) && $doc['status'] !== 'active') {
                    $isActive = false;
                }
                if($isActive) {
                    $activeDoctors[] = $doc;
                }
            }
        }
        echo json_encode($activeDoctors); 
    ?>;
    
    // Check if we have any active doctors
    if (allDoctors.length === 0) {
        $('#noActiveDoctorsMessage').show();
        $('#doctorSearchInput').prop('disabled', true).attr('placeholder', 'No active doctors available');
        $('#doctorSearchStatus').text('No active doctors available');
        $('#doctorSearchStatus').css('color', '#ef4444');
    }
    
    console.log('Active Doctors Count:', allDoctors.length);
    console.log('Active Doctors:', allDoctors);
    
    // ============================================================
    // QUICK REGISTER MODAL
    // ============================================================
    $('#qf_age').on('input', function() {
        var years = parseInt($(this).val());
        if (years && years > 0) {
            var today = new Date();
            var dob = new Date(today);
            dob.setFullYear(dob.getFullYear() - years);
            var year = dob.getFullYear();
            var month = String(dob.getMonth() + 1).padStart(2, '0');
            var day = String(dob.getDate()).padStart(2, '0');
            $('#qf_dob').val(year + '-' + month + '-' + day);
            $('#ageDisplayValue').text(years + 'Y');
            $('#ageDisplay').show();
        } else {
            $('#ageDisplayValue').text('—');
        }
    });
    
    $('#qf_dob').on('change input', function() {
        var dob = $(this).val();
        if (dob) {
            var birthDate = new Date(dob);
            var today = new Date();
            var years = today.getFullYear() - birthDate.getFullYear();
            var months = today.getMonth() - birthDate.getMonth();
            if (months < 0) {
                years--;
                months += 12;
            }
            if (years >= 0) {
                $('#qf_age').val(years);
                $('#ageDisplayValue').text(years + 'Y' + (months > 0 ? ' ' + months + 'M' : ''));
                $('#ageDisplay').show();
            } else {
                $('#qf_age').val('');
                $('#ageDisplayValue').text('—');
            }
        } else {
            $('#qf_age').val('');
            $('#ageDisplayValue').text('—');
        }
    });
    
    $('#qf_full_name').on('input', function() {
        var fullName = $(this).val().trim();
        if (fullName) {
            var parts = fullName.split(' ');
            if (parts.length > 1) {
                var firstName = parts.slice(0, -1).join(' ');
                var lastName = parts[parts.length - 1];
                $('#qf_first_name').val(firstName);
                $('#qf_last_name').val(lastName);
            } else {
                $('#qf_first_name').val(fullName);
                $('#qf_last_name').val('');
            }
        }
    });
    
    function resetModalForm() {
        $('#quickPatientForm')[0].reset();
        $('#qf_address').val('Bangladesh');
        $('#modalErrors').hide().html('');
        $('#qf_full_name').removeClass('is-invalid');
        $('#qf_phone').removeClass('is-invalid');
        $('#qf_dob').removeClass('is-invalid');
        $('#qf_gender').removeClass('is-invalid');
        $('#ageDisplayValue').text('—');
        $('#modalRegisterPatient').prop('disabled', false).html('<i class="fas fa-user-plus me-1"></i> Register Patient');
        $('#modalSaveRegister').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save & Register');
    }
    
    function showModalErrors(errors) {
        var html = '<ul>';
        if (Array.isArray(errors)) {
            errors.forEach(function(err) {
                html += '<li>' + err + '</li>';
            });
        } else {
            html += '<li>' + errors + '</li>';
        }
        html += '</ul>';
        $('#modalErrors').html(html).show();
        
        if (errors.some(e => e.toLowerCase().includes('full name') || e.toLowerCase().includes('name'))) {
            $('#qf_full_name').addClass('is-invalid');
        }
        if (errors.some(e => e.toLowerCase().includes('phone'))) {
            $('#qf_phone').addClass('is-invalid');
        }
        if (errors.some(e => e.toLowerCase().includes('birth') || e.toLowerCase().includes('dob') || e.toLowerCase().includes('age'))) {
            $('#qf_dob').addClass('is-invalid');
        }
        if (errors.some(e => e.toLowerCase().includes('gender'))) {
            $('#qf_gender').addClass('is-invalid');
        }
    }
    
    function submitQuickRegister(action) {
        $('#modalErrors').hide().html('');
        $('#qf_full_name').removeClass('is-invalid');
        $('#qf_phone').removeClass('is-invalid');
        $('#qf_dob').removeClass('is-invalid');
        $('#qf_gender').removeClass('is-invalid');
        
        var dob = $('#qf_dob').val();
        var ageVal = $('#qf_age').val();
        
        if (!dob && ageVal && parseInt(ageVal) > 0) {
            var today = new Date();
            var dobCalc = new Date(today);
            dobCalc.setFullYear(dobCalc.getFullYear() - parseInt(ageVal));
            var year = dobCalc.getFullYear();
            var month = String(dobCalc.getMonth() + 1).padStart(2, '0');
            var day = String(dobCalc.getDate()).padStart(2, '0');
            dob = year + '-' + month + '-' + day;
            $('#qf_dob').val(dob);
        }
        
        var formData = {
            full_name: $('#qf_full_name').val().trim(),
            first_name: $('#qf_first_name').val().trim(),
            last_name: $('#qf_last_name').val().trim(),
            phone: $('#qf_phone').val().trim(),
            gender: $('#qf_gender').val(),
            date_of_birth: dob,
            address: $('#qf_address').val().trim() || 'Bangladesh',
            action: action
        };
        
        var errors = [];
        if (!formData.full_name) errors.push('Full name is required');
        if (!formData.phone) errors.push('Phone number is required');
        if (!formData.gender) errors.push('Gender is required');
        if (!formData.date_of_birth) errors.push('Date of birth is required (or enter age)');
        
        if (errors.length > 0) {
            showModalErrors(errors);
            return;
        }
        
        if (!formData.first_name && formData.full_name) {
            var parts = formData.full_name.split(' ');
            if (parts.length > 1) {
                formData.first_name = parts.slice(0, -1).join(' ');
                formData.last_name = parts[parts.length - 1];
            } else {
                formData.first_name = formData.full_name;
                formData.last_name = '';
            }
        }
        
        $('#modalRegisterPatient').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');
        $('#modalSaveRegister').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Processing...');
        
        $.ajax({
            url: baseUrl + '/patient/quick-store',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                $('#modalRegisterPatient').prop('disabled', false).html('<i class="fas fa-user-plus me-1"></i> Register Patient');
                $('#modalSaveRegister').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save & Register');
                
                if (response.success) {
                    // ============================================================
                    // STEP 1: Extract patient payload from any server shape
                    // ============================================================
                    var patientData = null;
                    
                    if (response.patient && typeof response.patient === 'object' && response.patient.id) {
                        // Shape A: full patient object
                        patientData = response.patient;
                        if (!patientData.first_name && formData.first_name) patientData.first_name = formData.first_name;
                        if (!patientData.last_name && formData.last_name)  patientData.last_name  = formData.last_name;
                        if (!patientData.phone && formData.phone)          patientData.phone       = formData.phone;
                        if (!patientData.patient_code && response.patient_code) patientData.patient_code = response.patient_code;
                    } else if (response.patient_id) {
                        // Shape B: flat response
                        var nameParts = (response.patient_name || formData.full_name || '').split(' ');
                        patientData = {
                            id: response.patient_id,
                            patient_code: response.patient_code || '',
                            first_name: formData.first_name || nameParts[0] || '',
                            last_name: formData.last_name || (nameParts.length > 1 ? nameParts.slice(1).join(' ') : ''),
                            full_name: response.patient_name || formData.full_name || '',
                            phone: formData.phone || '',
                            gender: formData.gender || '',
                            date_of_birth: formData.date_of_birth || ''
                        };
                    }
                    
                    if (!patientData || !patientData.id) {
                        showModalErrors(['Server did not return patient ID. Please try again.']);
                        return;
                    }
                    
                    // ============================================================
                    // STEP 2: Close modal + reset form
                    // ============================================================
                    $('#quickPatientModal').modal('hide');
                    resetModalForm();
                    
                    // ============================================================
                    // STEP 3: Immediately select the new patient (populates UI)
                    // ============================================================
                    selectPatient(patientData);
                    
                    // ============================================================
                    // STEP 4: Show success + auto-search the patient in the list
                    // ============================================================
                    showToast('Patient Registered: ' + (patientData.first_name || '') + ' ' + (patientData.last_name || ''), 'success');
                    
                    // Auto-trigger a fresh patient search so the new patient
                    // (plus all others) appear in the dropdown immediately
                    setTimeout(function() {
                        refreshPatientSearchAfterRegister(patientData);
                    }, 300);
                    
                    // Make sure doctor search is enabled
                    if (allDoctors.length > 0) {
                        $('#doctorSearchInput').prop('disabled', false);
                        $('#doctorSearchStatus').text('Type at least 2 characters to search').css('color', '#6c757d');
                    }
                    
                } else {
                    if (response.errors) {
                        showModalErrors(response.errors);
                    } else {
                        showModalErrors([response.message || 'An error occurred']);
                    }
                }
            },
            error: function(xhr) {
                $('#modalRegisterPatient').prop('disabled', false).html('<i class="fas fa-user-plus me-1"></i> Register Patient');
                $('#modalSaveRegister').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save & Register');
                var msg = 'An error occurred. Please try again.';
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.message) msg = resp.message;
                } catch(e) {}
                showModalErrors([msg]);
            }
        });
    }
    
    var quickRegisterModal = new bootstrap.Modal(document.getElementById('quickPatientModal'));
    
    $('#quickPatientModal').on('hidden.bs.modal', function() {
        resetModalForm();
    });
    
    $('#modalRegisterPatient').click(function() {
        submitQuickRegister('register');
    });
    
    $('#modalSaveRegister').click(function() {
        submitQuickRegister('save_register');
    });
    
    $('#quickPatientForm').on('keydown', function(e) {
        if (e.key === 'Enter' && !$(e.target).is('textarea')) {
            e.preventDefault();
            submitQuickRegister('save_register');
        }
    });


    
    // ============================================================
    // ENABLE/DISABLE FORM FIELDS BASED ON PATIENT SELECTION
    // ============================================================
    function updateFormFields(hasPatient) {
        if (allDoctors.length === 0) {
            $('#doctorSearchInput').prop('disabled', true).attr('placeholder', 'No active doctors available');
            $('#doctorSearchStatus').text('No active doctors available');
            $('#doctorSearchStatus').css('color', '#ef4444');
            $('#noActiveDoctorsMessage').show();
        } else {
            $('#doctorSearchInput').prop('disabled', !hasPatient);
            if (!hasPatient) {
                $('#doctorSearchStatus').text('Select a patient first');
                $('#doctorSearchStatus').css('color', '#6c757d');
            } else {
                $('#doctorSearchStatus').text('Type at least 2 characters to search');
                $('#doctorSearchStatus').css('color', '#6c757d');
            }
        }
        
        $('#appointment_type').prop('disabled', !hasPatient);
        $('#additional_service_id').prop('disabled', !hasPatient);
        $('#symptoms').prop('disabled', !hasPatient);
        $('#special_note').prop('disabled', !hasPatient);
        
        if(!hasPatient) {
            $('#service_id').prop('disabled', true);
            $('#appointment_date').prop('disabled', true);
            $('#shift').prop('disabled', true);
            $('#submitBtn').prop('disabled', true);
            $('#serial_preview').val('');
            $('#availabilityMessage').text('Select a patient first');
            $('#availabilityMessage').css('color', '#6c757d');
            $('#paymentSummaryContent').html('<div class="text-center text-muted py-4"><i class="fas fa-info-circle fa-2x mb-2"></i><p>Select a patient and service to see payment summary</p></div>');
        }
    }
    
    // ============================================================
    // PATIENT SEARCH
    // ============================================================
    var searchTimeout;
    $('#patientSearchInput').on('input', function() {
        var query = $(this).val().trim();
        clearTimeout(searchTimeout);
        
        if(query.length < 2) {
            $('#patientSearchResults').hide();
            $('#searchStatus').text('Type at least 2 characters to search');
            return;
        }
        
        $('#searchStatus').text('Searching...');
        $('#patientSearchResults').show().html('<div class="list-group-item text-center text-muted">Searching...</div>');
        
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: apiBase + '/search-patients',
                type: 'GET',
                data: { term: query },
                dataType: 'json',
                timeout: 10000,
                success: function(response) {
                    var results = $('#patientSearchResults');
                    results.empty();
                    
                    if(response && Array.isArray(response) && response.length > 0) {
                        var filteredResults = response.filter(function(patient) {
                            var name = (patient.first_name || '') + ' ' + (patient.last_name || '');
                            var code = patient.patient_code || '';
                            var phone = patient.phone || '';
                            var email = patient.email || '';
                            var searchLower = query.toLowerCase();
                            
                            return name.toLowerCase().indexOf(searchLower) !== -1 ||
                                   code.toLowerCase().indexOf(searchLower) !== -1 ||
                                   phone.indexOf(query) !== -1 ||
                                   email.toLowerCase().indexOf(searchLower) !== -1;
                        });
                        
                        if(filteredResults.length > 0) {
                            $.each(filteredResults, function(i, patient) {
                                var name = (patient.first_name || '') + ' ' + (patient.last_name || '');
                                var details = (patient.patient_code || '') + ' | ' + (patient.phone || '');
                                if(patient.email) details += ' | ' + patient.email;
                                
                                var item = $('<a href="#" class="list-group-item list-group-item-action">')
                                    .html('<div class="d-flex justify-content-between align-items-center">' +
                                          '<div><strong>' + name + '</strong><br><small>' + details + '</small></div>' +
                                          '<span class="badge bg-primary">Select</span></div>')
                                    .data('patient', patient)
                                    .on('click', function(e) {
                                        e.preventDefault();
                                        selectPatient($(this).data('patient'));
                                    });
                                results.append(item);
                            });
                            results.show();
                            $('#searchStatus').text(filteredResults.length + ' patient(s) found matching "' + query + '"');
                        } else {
                            results.append('<div class="list-group-item text-muted text-center">No patients match "' + query + '". Click "Quick Add Patient" to register.</div>');
                            results.show();
                            $('#searchStatus').text('No patients found matching "' + query + '"');
                        }
                    } else {
                        results.append('<div class="list-group-item text-muted text-center">No patients found matching "' + query + '". Click "Quick Add Patient" to register.</div>');
                        results.show();
                        $('#searchStatus').text('No patients found matching "' + query + '"');
                    }
                },
                error: function() {
                    $('#searchStatus').text('Error searching patients. Please try again.');
                    $('#patientSearchResults').hide();
                }
            });
        }, 300);
    });

    // ============================================================
    // Refresh patient search dropdown after Quick Register
    // - Re-runs the search with a 2-char prefix of the new patient's name
    // - Falls back to phone if name is too short
    // - Ensures the newly-added patient (and all others) appear in the list
    // ============================================================
    function refreshPatientSearchAfterRegister(patientData) {
        // Pick a search prefix — prefer first-name, then phone
        var firstName = (patientData.first_name || '').trim();
        var phone = (patientData.phone || '').trim();
        
        var searchTerm = '';
        if (firstName.length >= 2) {
            searchTerm = firstName.substring(0, 2);
        } else if (patientData.full_name && patientData.full_name.length >= 2) {
            searchTerm = patientData.full_name.substring(0, 2);
        } else if (phone.length >= 2) {
            searchTerm = phone.substring(0, 2);
        }
        
        if (!searchTerm) {
            // No usable prefix — just set the status text
            $('#searchStatus')
                .text('✓ Patient registered: ' + (patientData.first_name || '') + ' ' + (patientData.last_name || ''))
                .css('color', '#10b981');
            return;
        }
        
        // Set the search input value + trigger the AJAX search
        $('#patientSearchInput').val(searchTerm);
        $('#searchStatus')
            .text('✓ New patient registered — refreshing list...')
            .css('color', '#10b981');
        
        // Trigger the existing input handler so the dropdown refreshes
        // (the handler already handles the AJAX call to /api/search-patients)
        $('#patientSearchInput').trigger('input');
        
        // After the search completes, remove the "registered" status flash
        // but keep the search results visible so user can see all matches
        setTimeout(function() {
            // Do nothing extra — input handler already populates the list
        }, 500);
    }
    
    // ============================================================
    // SELECT PATIENT
    // ============================================================
    function selectPatient(patient) {
        if (!patient || !patient.id) {
            showToast('Invalid patient data received', 'error');
            return;
        }
        
        selectedPatientId = patient.id;
        selectedPatientName = ((patient.first_name || '') + ' ' + (patient.last_name || '')).trim();
        
        if (!selectedPatientName && patient.full_name) {
            selectedPatientName = patient.full_name;
        }
        
        $('#patient_id').val(patient.id);
        
        $('#selectedPatientName').text(selectedPatientName || 'Patient');
        $('#selectedPatientDetails').text(
            'ID: ' + (patient.patient_code || 'N/A') + ' | Phone: ' + (patient.phone || 'N/A')
        );
        $('#selectedPatientDisplay').show();
        $('#patientSearchResults').hide();
        $('#patientSearchInput').val('');
        $('#searchStatus').text('Patient selected: ' + selectedPatientName);
        
        updateFormFields(true);
        if (allDoctors.length > 0) {
            $('#doctorSearchInput').prop('disabled', false);
            $('#doctorSearchStatus').text('Type at least 2 characters to search');
            $('#doctorSearchStatus').css('color', '#6c757d');
        }
        
        // Reset doctor selection
        clearDoctorSelection();
        
        showToast('Patient Selected: ' + selectedPatientName, 'success');
    }
    
    // ============================================================
    // TOAST NOTIFICATION
    // ============================================================
    function showToast(message, type) {
        var colors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        var toast = $('<div class="alert-toast">' + message + '</div>');
        toast.css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            zIndex: '9999',
            background: colors[type] || '#10b981',
            color: 'white',
            padding: '10px 20px',
            borderRadius: '8px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            fontSize: '14px',
            fontFamily: 'Cambria, Times New Roman, serif',
            animation: 'slideIn 0.3s ease-out'
        });
        $('body').append(toast);
        setTimeout(function() {
            toast.fadeOut(300, function() { $(this).remove(); });
        }, 3000);
    }
    
    $('<style>')
        .prop('type', 'text/css')
        .html('@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }')
        .appendTo('head');
    
    // ============================================================
    // CLEAR SELECTED PATIENT
    // ============================================================
    $('#clearSelectedPatient').on('click', function() {
        selectedPatientId = 0;
        selectedPatientName = '';
        $('#patient_id').val('');
        $('#selectedPatientDisplay').hide();
        $('#patientSearchInput').val('').focus();
        $('#searchStatus').text('Type at least 2 characters to search');
        updateFormFields(false);
        clearDoctorSelection();
        resetDoctorFields();
    });
    
    $('#clearSearchBtn').on('click', function() {
        $('#patientSearchInput').val('');
        $('#patientSearchResults').hide();
        $('#searchStatus').text('Type at least 2 characters to search');
    });
    
    // ============================================================
    // DOCTOR SEARCH - Exactly like patient search
    // ACTIVE DOCTORS ONLY (allDoctors is already filtered)
    // ============================================================
    var doctorSearchTimeout;
    $('#doctorSearchInput').on('input', function() {
        var query = $(this).val().trim();
        clearTimeout(doctorSearchTimeout);
        
        if (selectedPatientId == 0) {
            $('#doctorSearchResults').hide();
            $('#doctorSearchStatus').text('Select a patient first');
            $('#doctorSearchStatus').css('color', '#ef4444');
            return;
        }
        
        if (allDoctors.length === 0) {
            $('#doctorSearchResults').hide();
            $('#doctorSearchStatus').text('No active doctors available');
            $('#doctorSearchStatus').css('color', '#ef4444');
            $('#noActiveDoctorsMessage').show();
            return;
        }
        
        if(query.length < 2) {
            $('#doctorSearchResults').hide();
            $('#doctorSearchStatus').text('Type at least 2 characters to search');
            $('#doctorSearchStatus').css('color', '#6c757d');
            return;
        }
        
        $('#doctorSearchStatus').text('Searching...');
        $('#doctorSearchStatus').css('color', '#6c757d');
        $('#doctorSearchResults').show().html('<div class="list-group-item text-center text-muted">Searching...</div>');
        
        doctorSearchTimeout = setTimeout(function() {
            // Filter doctors client-side from the allDoctors array (already filtered for active)
            var results = $('#doctorSearchResults');
            results.empty();
            
            // allDoctors is already filtered to only include active doctors
            // Both doctors.status = 'active' AND users.status = 'active' are checked server-side
            if (allDoctors && allDoctors.length > 0) {
                var searchLower = query.toLowerCase();
                var filteredResults = allDoctors.filter(function(doctor) {
                    var name = 'Dr. ' + (doctor.first_name || '') + ' ' + (doctor.last_name || '');
                    var specialization = doctor.specialization || '';
                    
                    return name.toLowerCase().indexOf(searchLower) !== -1 ||
                           specialization.toLowerCase().indexOf(searchLower) !== -1;
                });
                
                if(filteredResults.length > 0) {
                    $.each(filteredResults, function(i, doctor) {
                        var name = 'Dr. ' + (doctor.first_name || '') + ' ' + (doctor.last_name || '');
                        var details = (doctor.specialization || 'General Medicine') + ' | Fee: ৳' + (parseFloat(doctor.consultation_fee) || 0).toFixed(2);
                        
                        var item = $('<a href="#" class="list-group-item list-group-item-action">')
                            .html('<div class="d-flex justify-content-between align-items-center">' +
                                  '<div><strong>' + name + '</strong><br><small>' + details + '</small></div>' +
                                  '<span class="badge bg-primary">Select</span></div>')
                            .data('doctor', doctor)
                            .on('click', function(e) {
                                e.preventDefault();
                                selectDoctor($(this).data('doctor'));
                            });
                        results.append(item);
                    });
                    results.show();
                    $('#doctorSearchStatus').text(filteredResults.length + ' doctor(s) found matching "' + query + '"');
                    $('#doctorSearchStatus').css('color', '#6c757d');
                } else {
                    results.append('<div class="list-group-item text-muted text-center">No doctors match "' + query + '".</div>');
                    results.show();
                    $('#doctorSearchStatus').text('No doctors found matching "' + query + '"');
                    $('#doctorSearchStatus').css('color', '#ef4444');
                }
            } else {
                results.append('<div class="list-group-item text-muted text-center">No active doctors available.</div>');
                results.show();
                $('#doctorSearchStatus').text('No active doctors available');
                $('#doctorSearchStatus').css('color', '#ef4444');
                $('#noActiveDoctorsMessage').show();
            }
        }, 300);
    });
    
    $('#clearDoctorSearchBtn').on('click', function() {
        $('#doctorSearchInput').val('');
        $('#doctorSearchResults').hide();
        $('#doctorSearchStatus').text('Type at least 2 characters to search');
        $('#doctorSearchStatus').css('color', '#6c757d');
    });
    
    // ============================================================
    // SELECT DOCTOR
    // ============================================================
    function selectDoctor(doctor) {
        doctorId = doctor.id;
        doctorName = 'Dr. ' + (doctor.first_name || '') + ' ' + (doctor.last_name || '');
        var fee = parseFloat(doctor.consultation_fee) || 0;
        var specialization = doctor.specialization || 'General Medicine';
        
        $('#doctor_id').val(doctor.id);
        
        $('#selectedDoctorName').text(doctorName);
        $('#selectedDoctorDetails').text('Specialization: ' + specialization + ' | Fee: ৳' + fee.toFixed(2));
        $('#selectedDoctorDisplay').show();
        $('#doctorSearchResults').hide();
        $('#doctorSearchInput').val('');
        $('#doctorSearchStatus').text('Doctor selected: ' + doctorName);
        $('#doctorSearchStatus').css('color', '#10b981');
        
        // Load services and availability
        loadDoctorServices(doctor.id);
        loadDoctorAvailability(doctor.id);
        
        showToast('Doctor Selected: ' + doctorName, 'success');
    }
    
    // ============================================================
    // CLEAR SELECTED DOCTOR
    // ============================================================
    function clearDoctorSelection() {
        doctorId = 0;
        doctorName = '';
        $('#doctor_id').val('');
        $('#selectedDoctorDisplay').hide();
        $('#doctorSearchInput').val('');
        $('#doctorSearchStatus').text('Type at least 2 characters to search');
        $('#doctorSearchStatus').css('color', '#6c757d');
        resetDoctorFields();
    }
    
    $('#clearSelectedDoctor').on('click', function() {
        clearDoctorSelection();
    });
    
    // ============================================================
    // LOAD DOCTOR SERVICES
    // ============================================================
    function loadDoctorServices(docId) {
        $('#service_id').html('<option value="">Loading services...</option>').prop('disabled', true);
        selectedServiceId = 0;
        selectedServiceName = '';
        selectedServiceType = 'consultation';
        servicePrice = 0;
        
        if(docId && selectedPatientId > 0) {
            $.ajax({
                url: apiBase + '/get-doctor-services',
                type: 'GET',
                data: { doctor_id: docId },
                dataType: 'json',
                success: function(response) {
                    var serviceSelect = $('#service_id');
                    serviceSelect.html('<option value="">-- Select Service --</option>');
                    
                    if(response.services && response.services.length > 0) {
                        $.each(response.services, function(key, service) {
                            serviceSelect.append('<option value="' + service.id + '" data-price="' + service.service_price + '" data-name="' + service.service_name + '" data-type="' + (service.service_type || 'consultation') + '">' + service.service_name + ' - ৳' + parseFloat(service.service_price).toFixed(2) + '</option>');
                        });
                        serviceSelect.prop('disabled', false);
                    } else {
                        serviceSelect.html('<option value="">No services available</option>').prop('disabled', true);
                    }
                    updatePaymentSummary();
                },
                error: function() {
                    $('#service_id').html('<option value="">Error loading services</option>').prop('disabled', true);
                    updatePaymentSummary();
                }
            });
        }
    }
    
    // ============================================================
    // LOAD DOCTOR AVAILABILITY
    // ============================================================
    function loadDoctorAvailability(docId) {
        isScheduleLoaded = false;
        $('#shift').html('<option value="">Loading availability...</option>').prop('disabled', true);
        $('#serial_preview').val('');
        $('#appointment_date').prop('disabled', true);
        $('#submitBtn').prop('disabled', true);
        $('#availabilityMessage').text('Loading doctor schedule...');
        $('#availabilityMessage').css('color', '#6c757d');
        
        if(docId && selectedPatientId > 0) {
            $.ajax({
                url: apiBase + '/get-doctor-availability',
                type: 'GET',
                data: { doctor_id: docId },
                dataType: 'json',
                timeout: 15000,
                success: function(response) {
                    if(response && typeof response === 'object') {
                        availableDays = response.available_days || [];
                        sessionsByDay = response.sessions_by_day || {};
                        scheduleData = response;
                        isScheduleLoaded = true;
                        
                        if(availableDays.length > 0) {
                            $('#appointment_date').prop('disabled', false);
                            $('#appointment_date').attr('min', new Date().toISOString().split('T')[0]);
                            
                            var dayNames = availableDays.join(', ');
                            $('#availabilityMessage').text('Available on: ' + dayNames);
                            $('#availabilityMessage').css('color', '#10b981');
                            
                            var dayNamesMap = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                            var today = new Date();
                            var foundDate = null;
                            var checkDate = new Date(today);
                            
                            for(var i = 0; i < 14; i++) {
                                var dateStr = checkDate.toISOString().split('T')[0];
                                var dayName = dayNamesMap[checkDate.getDay()];
                                if(availableDays.indexOf(dayName) !== -1) {
                                    foundDate = dateStr;
                                    break;
                                }
                                checkDate.setDate(checkDate.getDate() + 1);
                            }
                            
                            if(foundDate) {
                                $('#appointment_date').val(foundDate);
                                $('#appointment_date').trigger('change');
                            }
                            
                            if(selectedServiceId > 0) {
                                $('#submitBtn').prop('disabled', false);
                            }
                            
                        } else {
                            $('#appointment_date').prop('disabled', true);
                            $('#shift').prop('disabled', true);
                            $('#shift').html('<option value="">No schedule available</option>');
                            $('#serial_preview').val('');
                            $('#submitBtn').prop('disabled', true);
                            
                            var msg = response.message || 'No schedule found for this doctor. Please select a different doctor.';
                            $('#availabilityMessage').text(msg);
                            $('#availabilityMessage').css('color', '#ef4444');
                        }
                    } else {
                        $('#appointment_date').prop('disabled', true);
                        $('#shift').prop('disabled', true);
                        $('#shift').html('<option value="">No schedule available</option>');
                        $('#serial_preview').val('');
                        $('#submitBtn').prop('disabled', true);
                        $('#availabilityMessage').text('No schedule found for this doctor.');
                        $('#availabilityMessage').css('color', '#ef4444');
                    }
                    updatePaymentSummary();
                },
                error: function() {
                    $('#appointment_date').prop('disabled', true);
                    $('#shift').prop('disabled', true);
                    $('#shift').html('<option value="">No schedule available</option>');
                    $('#serial_preview').val('');
                    $('#submitBtn').prop('disabled', true);
                    $('#availabilityMessage').text('No schedule found for this doctor.');
                    $('#availabilityMessage').css('color', '#ef4444');
                    updatePaymentSummary();
                }
            });
        }
    }
    
    // ============================================================
    // RESET DOCTOR FIELDS
    // ============================================================
    function resetDoctorFields() {
        $('#service_id').html('<option value="">-- Select Doctor First --</option>').prop('disabled', true);
        servicePrice = 0;
        selectedServiceId = 0;
        selectedServiceName = '';
        selectedServiceType = 'consultation';
        $('#shift').html('<option value="">-- Select Doctor and Date First --</option>').prop('disabled', true);
        $('#serial_preview').val('');
        $('#appointment_date').prop('disabled', true);
        $('#availabilityMessage').text('Select a doctor first');
        $('#availabilityMessage').css('color', '#6c757d');
        $('#submitBtn').prop('disabled', true);
        updatePaymentSummary();
    }
    
    // ============================================================
    // UPDATE PAYMENT SUMMARY
    // ============================================================
    window.updatePaymentSummary = function() {
        var service = parseFloat(servicePrice) || 0;
        var additional = parseFloat(additionalPrice) || 0;
        var subtotal = service + additional;
        var discountPercent = parseFloat($('#discount_percent').val()) || 0;
        var discountAmount = (subtotal * discountPercent) / 100;
        var total = subtotal - discountAmount;
        
        currentSubtotal = subtotal;
        currentDiscountAmount = discountAmount;
        currentTotalAmount = total;
        
        var summaryHtml = '';
        if(selectedPatientId > 0 && (service > 0 || additional > 0)) {
            var serviceName = $('#service_id option:selected').text() || 'Consultation';
            if(serviceName.includes(' - ')) {
                serviceName = serviceName.split(' - ')[0];
            }
            
            summaryHtml += '<div class="summary-item">';
            summaryHtml += '<div class="d-flex justify-content-between mb-2 pb-2 border-bottom">';
            summaryHtml += '<span><strong>Item</strong></span>';
            summaryHtml += '<span><strong>Amount (৳)</strong></span>';
            summaryHtml += '</div>';
            
            if(service > 0) {
                summaryHtml += '<div class="d-flex justify-content-between mb-2">';
                summaryHtml += '<span>' + serviceName + ':</span>';
                summaryHtml += '<span>৳ ' + service.toFixed(2) + '</span>';
                summaryHtml += '</div>';
            }
            
            if(additional > 0) {
                var additionalName = $('#additional_service_id option:selected').text() || 'Additional Service';
                if(additionalName.includes(' - ')) {
                    additionalName = additionalName.split(' - ')[0];
                }
                summaryHtml += '<div class="d-flex justify-content-between mb-2">';
                summaryHtml += '<span>' + additionalName + ':</span>';
                summaryHtml += '<span>৳ ' + additional.toFixed(2) + '</span>';
                summaryHtml += '</div>';
            }
            
            summaryHtml += '<div class="d-flex justify-content-between mb-2 pt-2 border-top">';
            summaryHtml += '<span><strong>Subtotal:</strong></span>';
            summaryHtml += '<span><strong>৳ ' + subtotal.toFixed(2) + '</strong></span>';
            summaryHtml += '</div>';
            
            summaryHtml += '<div class="d-flex justify-content-between mb-2 align-items-center">';
            summaryHtml += '<span>Discount (%):</span>';
            summaryHtml += '<div class="d-flex align-items-center gap-2">';
            summaryHtml += '<input type="number" step="1" id="discount_percent" class="form-control form-control-sm" style="width: 80px;" value="' + discountPercent + '" min="0" max="100" oninput="updatePaymentSummary()">';
            summaryHtml += '<span>%</span>';
            summaryHtml += '</div>';
            summaryHtml += '</div>';
            
            summaryHtml += '<div class="d-flex justify-content-between mb-2">';
            summaryHtml += '<span>Discount Amount:</span>';
            summaryHtml += '<span>৳ ' + discountAmount.toFixed(2) + '</span>';
            summaryHtml += '</div>';
            
            summaryHtml += '<div class="d-flex justify-content-between mb-2 pt-2 border-top">';
            summaryHtml += '<span><strong>Total Amount:</strong></span>';
            summaryHtml += '<span><strong class="text-success" style="font-size: 18px;">৳ ' + total.toFixed(2) + '</strong></span>';
            summaryHtml += '</div>';
            
            summaryHtml += '<div class="d-flex justify-content-between mb-2">';
            summaryHtml += '<span>Payment Method:</span>';
            summaryHtml += '<select name="payment_method" id="payment_method" class="form-select form-select-sm" style="width: auto;">';
            summaryHtml += '<option value="cash">Cash</option>';
            summaryHtml += '<option value="card">Card</option>';
            summaryHtml += '<option value="mobile_banking">Mobile Banking</option>';
            summaryHtml += '<option value="bank_transfer">Bank Transfer</option>';
            summaryHtml += '</select>';
            summaryHtml += '</div>';
            
            summaryHtml += '<input type="hidden" name="total_amount" id="total_amount" value="' + total + '">';
            summaryHtml += '<input type="hidden" name="discount_amount" id="discount_amount" value="' + discountAmount + '">';
            summaryHtml += '<input type="hidden" name="subtotal" id="subtotal" value="' + subtotal + '">';
            summaryHtml += '<input type="hidden" name="service_name" id="service_name" value="' + serviceName + '">';
            summaryHtml += '<input type="hidden" name="service_id_hidden" id="service_id_hidden" value="' + selectedServiceId + '">';
            summaryHtml += '</div>';
            
            if(selectedPatientId > 0 && doctorId > 0 && $('#appointment_date').val() && $('#shift').val() && selectedServiceId > 0) {
                $('#submitBtn').prop('disabled', false);
            } else {
                $('#submitBtn').prop('disabled', true);
            }
        } else {
            if(selectedPatientId == 0) {
                summaryHtml = '<div class="text-center text-muted py-4">';
                summaryHtml += '<i class="fas fa-user fa-2x mb-2"></i>';
                summaryHtml += '<p>Select a patient first</p>';
                summaryHtml += '</div>';
            } else if(doctorId == 0) {
                summaryHtml = '<div class="text-center text-muted py-4">';
                summaryHtml += '<i class="fas fa-user-md fa-2x mb-2"></i>';
                summaryHtml += '<p>Select a doctor first</p>';
                summaryHtml += '</div>';
            } else {
                summaryHtml = '<div class="text-center text-muted py-4">';
                summaryHtml += '<i class="fas fa-info-circle fa-2x mb-2"></i>';
                summaryHtml += '<p>Select a service to see payment summary</p>';
                summaryHtml += '</div>';
            }
            $('#submitBtn').prop('disabled', true);
        }
        $('#paymentSummaryContent').html(summaryHtml);
    }
    
    // ============================================================
    // SERVICE SELECTION
    // ============================================================
    $('#service_id').change(function() {
        var selected = $(this).find(':selected');
        selectedServiceId = $(this).val() || 0;
        servicePrice = parseFloat(selected.data('price')) || 0;
        selectedServiceName = selected.data('name') || 'Consultation';
        selectedServiceType = selected.data('type') || 'consultation';
        updatePaymentSummary();
    });
    
    // ============================================================
    // ADDITIONAL SERVICE SELECTION
    // ============================================================
    $('#additional_service_id').change(function() {
        var selected = $(this).find(':selected');
        additionalPrice = parseFloat(selected.data('price')) || 0;
        updatePaymentSummary();
    });
    
    // ============================================================
    // APPOINTMENT DATE CHANGE
    // ============================================================
    $('#appointment_date').change(function() {
        var dateVal = $(this).val();
        if(dateVal && doctorId && selectedPatientId > 0) {
            var dayOfWeek = new Date(dateVal + 'T00:00:00').toLocaleString('en-US', { weekday: 'long' });
            
            if(availableDays.length > 0 && availableDays.indexOf(dayOfWeek) === -1) {
                $('#shift').html('<option value="">Doctor not available on this day</option>').prop('disabled', true);
                $('#serial_preview').val('');
                $('#submitBtn').prop('disabled', true);
                $('#availabilityMessage').text('Doctor not available on ' + dayOfWeek);
                $('#availabilityMessage').css('color', '#ef4444');
                return;
            }
            
            var shiftHtml = '<option value="">-- Select Session --</option>';
            var hasShifts = false;
            
            if(sessionsByDay[dayOfWeek]) {
                $.each(sessionsByDay[dayOfWeek], function(key, session) {
                    var sessionType = session.session_type || 'morning';
                    var label = sessionType.charAt(0).toUpperCase() + sessionType.slice(1);
                    var startTime = session.start_time ? session.start_time.substring(0, 5) : '09:00';
                    var endTime = session.end_time ? session.end_time.substring(0, 5) : '13:00';
                    var slotDuration = session.slot_duration || 15;
                    var maxPatients = session.max_patients || 20;
                    shiftHtml += '<option value="' + sessionType + '" data-slot="' + slotDuration + '" data-max="' + maxPatients + '" data-start="' + startTime + '" data-end="' + endTime + '">' + label + ' (' + startTime + ' - ' + endTime + ', ' + slotDuration + ' min, ' + maxPatients + ' max)</option>';
                    hasShifts = true;
                });
            }
            
            if(hasShifts) {
                $('#shift').html(shiftHtml).prop('disabled', false);
                $('#availabilityMessage').text('Available on: ' + dayOfWeek + ' - Select a session');
                $('#availabilityMessage').css('color', '#10b981');
                
                if($('#shift option').length > 1) {
                    $('#shift').val($('#shift option:first').val());
                    $('#shift').trigger('change');
                }
            } else {
                $('#shift').html('<option value="">No sessions available</option>').prop('disabled', true);
                $('#serial_preview').val('');
                $('#submitBtn').prop('disabled', true);
                $('#availabilityMessage').text('No sessions for ' + dayOfWeek);
                $('#availabilityMessage').css('color', '#ef4444');
            }
            updatePaymentSummary();
        }
    });
    
    // ============================================================
    // SHIFT CHANGE - CHECK AVAILABLE SLOTS
    // ============================================================
    $('#shift').change(function() {
        var doctorIdVal = $('#doctor_id').val();
        var dateVal = $('#appointment_date').val();
        var shiftVal = $(this).val();
        var selectedOption = $(this).find(':selected');
        var slotDuration = selectedOption.data('slot') || 15;
        var maxPatients = selectedOption.data('max') || 20;
        var startTime = selectedOption.data('start') || '09:00';
        var endTime = selectedOption.data('end') || '13:00';
        
        if(doctorIdVal && dateVal && shiftVal && selectedPatientId > 0) {
            $.ajax({
                url: apiBase + '/get-available-slots',
                type: 'GET',
                data: { doctor_id: doctorIdVal, date: dateVal, shift: shiftVal },
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        var bookedCount = response.booked_count || 0;
                        var availableSlots = response.available_slots || (maxPatients - bookedCount);
                        var nextSerial = response.next_serial || (bookedCount + 1);
                        
                        if(availableSlots > 0) {
                            var serialPrefix = shiftVal == 'morning' ? 'M' : 'E';
                            $('#serial_preview').val(serialPrefix + String(nextSerial).padStart(3, '0'));
                            
                            if(selectedServiceId > 0) {
                                $('#submitBtn').prop('disabled', false);
                            }
                            $('#availabilityMessage').text('Available: ' + availableSlots + ' slots | Duration: ' + slotDuration + ' min | ' + startTime + ' - ' + endTime);
                            $('#availabilityMessage').css('color', '#10b981');
                        } else {
                            $('#serial_preview').val('No slots available');
                            $('#submitBtn').prop('disabled', true);
                            $('#availabilityMessage').text('No available slots for this session');
                            $('#availabilityMessage').css('color', '#ef4444');
                            
                            Swal.fire({
                                icon: 'warning',
                                title: 'No Slots Available',
                                text: 'All slots are booked for this session. Please select another date or session.',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        }
                    } else {
                        $('#serial_preview').val('Error checking slots');
                        $('#submitBtn').prop('disabled', true);
                        $('#availabilityMessage').text(response.message || 'Error checking availability');
                        $('#availabilityMessage').css('color', '#ef4444');
                    }
                    updatePaymentSummary();
                },
                error: function() {
                    $('#serial_preview').val('Error checking availability');
                    $('#submitBtn').prop('disabled', true);
                    $('#availabilityMessage').text('Error checking available slots');
                    $('#availabilityMessage').css('color', '#ef4444');
                }
            });
        }
    });
    
    // ============================================================
    // FORM SUBMISSION
    // ============================================================
    $('#appointmentForm').submit(function(e) {
        e.preventDefault();
        
        var patientIdVal = $('#patient_id').val();
        var doctorIdVal = $('#doctor_id').val();
        var serviceIdVal = $('#service_id').val();
        var appointmentDate = $('#appointment_date').val();
        var shiftVal = $('#shift').val();
        
        if(!patientIdVal || patientIdVal === '') {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a patient first' });
            return;
        }
        
        if(!doctorIdVal || doctorIdVal === '') {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please select a doctor' });
            return;
        }
        
        if(!serviceIdVal || serviceIdVal === '') {
            serviceIdVal = 0;
        }
        
        var errors = [];
        if(!patientIdVal) errors.push("Patient is required");
        if(!doctorIdVal) errors.push("Doctor is required");
        if(!appointmentDate) errors.push("Appointment date is required");
        if(!shiftVal) errors.push("Session/Shift is required");
        if(!serviceIdVal || serviceIdVal === '0') errors.push("Please select a service");
        
        if(errors.length > 0) {
            Swal.fire({ icon: 'error', title: 'Error', html: errors.join('<br>') });
            return;
        }
        
        var totalAmount = $('#total_amount').val() || 0;
        var discountAmount = $('#discount_amount').val() || 0;
        var subtotalVal = $('#subtotal').val() || 0;
        var paymentMethod = $('#payment_method').val() || 'cash';
        var discountPercent = $('#discount_percent').val() || 0;
        var selectedDoctorName = $('#selectedDoctorName').text() || doctorName || 'N/A';
        var selectedServiceNameDisplay = selectedServiceName || 'Consultation';
        var selectedPatientDisplay = selectedPatientName || 'N/A';
        
        var formData = {
            patient_id: patientIdVal,
            doctor_id: doctorIdVal,
            service_id: serviceIdVal,
            service_name: selectedServiceName || 'Consultation',
            service_price: servicePrice || 0,
            service_type: selectedServiceType || 'consultation',
            appointment_date: appointmentDate,
            shift: shiftVal,
            appointment_type: $('#appointment_type').val() || 'regular',
            additional_service_id: $('#additional_service_id').val() || 0,
            symptoms: $('#symptoms').val() || '',
            special_note: $('#special_note').val() || '',
            payment_method: paymentMethod,
            discount_percent: discountPercent,
            total_amount: totalAmount,
            discount_amount: discountAmount,
            subtotal: subtotalVal
        };
        
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
        
        $.ajax({
            url: apiBase + '/book-appointment',
            type: 'POST',
            data: formData,
            dataType: 'json',
            timeout: 30000,
            success: function(response) {
                if(response.success) {
                    var serviceDisplay = response.service_name || selectedServiceName || 'N/A';
                    var serialDisplay = response.serial_number || 'N/A';
                    var sessionDisplay = response.shift || shiftVal || 'N/A';
                    var dateDisplay = response.appointment_date || appointmentDate || 'N/A';
                    var doctorDisplay = response.doctor_name || selectedDoctorName || 'N/A';
                    var patientDisplay = response.patient_name || selectedPatientDisplay || 'N/A';
                    
                    Swal.fire({
                        icon: 'success',
                        title: '✅ Appointment Booked!',
                        html: '<div style="text-align: left; font-size: 14px; line-height: 2.0; font-family: Cambria, Times New Roman, serif;">' +
                              '<div style="border-bottom: 2px solid #10b981; padding-bottom: 10px; margin-bottom: 10px;">' +
                              '<strong style="color: #10b981; font-size: 16px;">Serial:</strong> <span style="font-weight: 700; font-size: 18px;">' + serialDisplay + '</span>' +
                              '</div>' +
                              '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                              '<span><strong>Patient:</strong></span>' +
                              '<span style="font-weight: 600;">' + patientDisplay + '</span>' +
                              '</div>' +
                              '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                              '<span><strong>Doctor:</strong></span>' +
                              '<span>' + doctorDisplay + '</span>' +
                              '</div>' +
                              '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                              '<span><strong>Service:</strong></span>' +
                              '<span>' + serviceDisplay + '</span>' +
                              '</div>' +
                              '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                              '<span><strong>Date:</strong></span>' +
                              '<span>' + dateDisplay + '</span>' +
                              '</div>' +
                              '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                              '<span><strong>Session:</strong></span>' +
                              '<span>' + sessionDisplay.toUpperCase() + '</span>' +
                              '</div>' +
                              '<div style="display: flex; justify-content: space-between; padding: 2px 0; border-top: 1px dashed #e5e7eb; margin-top: 6px; padding-top: 6px;">' +
                              '<span><strong>Subtotal:</strong></span>' +
                              '<span>৳ ' + parseFloat(response.subtotal || subtotalVal || 0).toFixed(2) + '</span>' +
                              '</div>' +
                              '<div style="display: flex; justify-content: space-between; padding: 2px 0;">' +
                              '<span><strong>Discount:</strong></span>' +
                              '<span style="color: #ef4444;">- ৳ ' + parseFloat(response.discount_amount || discountAmount || 0).toFixed(2) + '</span>' +
                              '</div>' +
                              '<div style="display: flex; justify-content: space-between; padding: 2px 0; border-top: 2px solid #10b981; padding-top: 6px; margin-top: 4px;">' +
                              '<span style="font-weight: 700; font-size: 15px;"><strong>Total Amount:</strong></span>' +
                              '<span style="font-weight: 700; color: #10b981; font-size: 18px;">৳ ' + parseFloat(response.total_amount || totalAmount || 0).toFixed(2) + '</span>' +
                              '</div>' +
                              (response.appointment_number ? '<div style="display: flex; justify-content: space-between; padding: 2px 0;"><span><strong>Appointment ID:</strong></span><span>' + response.appointment_number + '</span></div>' : '') +
                              (response.bill_number ? '<div style="display: flex; justify-content: space-between; padding: 2px 0;"><span><strong>Bill #:</strong></span><span>' + response.bill_number + '</span></div>' : '') +
                              '</div>',
                        confirmButtonText: 'Done',
                        confirmButtonColor: '#10b981',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        timer: 30000,
                        timerProgressBar: true,
                        width: '480px'
                    }).then((result) => {
                        window.location.href = baseUrl + '/appointments/book';
                    });
                } else {
                    Swal.fire({
                        icon: 'error', 
                        title: 'Failed', 
                        text: response.message || 'Please check all fields and try again.',
                        confirmButtonText: 'OK'
                    });
                    $('#submitBtn').prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Book Appointment');
                }
            },
            error: function(xhr) {
                var errorMsg = 'An error occurred. Please try again.';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if(response.message) errorMsg = response.message;
                } catch(e) {}
                
                Swal.fire({
                    icon: 'error', 
                    title: 'Error', 
                    text: errorMsg,
                    confirmButtonText: 'OK'
                });
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-check-circle me-2"></i>Book Appointment');
            }
        });
    });
    
    // ============================================================
    // INITIALIZATION
    // ============================================================
    <?php if(isset($patient) && $patient): ?>
    selectedPatientId = <?php echo $patient['id']; ?>;
    selectedPatientName = '<?php echo addslashes($patient['first_name'] . ' ' . $patient['last_name']); ?>';
    updateFormFields(true);
    if (allDoctors.length > 0) {
        $('#doctorSearchInput').prop('disabled', false);
        $('#doctorSearchStatus').text('Type at least 2 characters to search');
        $('#doctorSearchStatus').css('color', '#6c757d');
    } else {
        $('#doctorSearchInput').prop('disabled', true).attr('placeholder', 'No active doctors available');
        $('#doctorSearchStatus').text('No active doctors available');
        $('#doctorSearchStatus').css('color', '#ef4444');
        $('#noActiveDoctorsMessage').show();
    }
    <?php else: ?>
    updateFormFields(false);
    <?php endif; ?>
});
</script>

<style>
    .summary-item { font-size: 14px; }
    .summary-item .border-bottom { border-bottom: 1px solid #e2e8f0 !important; }
    .summary-item .border-top { border-top: 1px solid #e2e8f0 !important; }
    .form-select-sm { display: inline-block; width: auto; }
    #discount_percent { display: inline-block; }
    #availabilityMessage {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        min-height: 20px;
    }
    .btn-lg {
        padding: 10px 30px;
        font-size: 16px;
    }
    #patientSearchResults .list-group-item, #doctorSearchResults .list-group-item {
        cursor: pointer;
        transition: background 0.2s;
    }
    #patientSearchResults .list-group-item:hover, #doctorSearchResults .list-group-item:hover {
        background: #f0f7ff;
    }
    #patientSearchResults .list-group-item .badge, #doctorSearchResults .list-group-item .badge {
        font-size: 10px;
        padding: 4px 8px;
    }
    .modal-lg {
        max-width: 700px;
    }
    #selectedPatientDisplay .alert, #selectedDoctorDisplay .alert {
        padding: 12px 16px;
        margin-bottom: 0;
    }
    
    .modal-content {
        border-radius: 12px;
        border: none;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    }
    .modal-header {
        border-bottom: 2px solid #3b82f6;
        padding: 15px 20px;
    }
    .modal-header .modal-title {
        font-weight: 600;
        font-size: 16px;
        color: #1f2937;
    }
    .modal-body {
        padding: 20px;
    }
    .modal-footer {
        border-top: 1px solid #e5e7eb;
        padding: 15px 20px;
    }
    .modal .form-label {
        font-weight: 500;
        font-size: 13px;
        color: #374151;
        margin-bottom: 4px;
    }
    .modal .form-control, .modal .form-select {
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        padding: 8px 12px;
        font-size: 13px;
    }
    .modal .form-control:focus, .modal .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .modal .required-star {
        color: #ef4444;
    }
    .modal .btn-primary {
        background: #3b82f6;
        border: none;
        padding: 8px 20px;
        font-size: 13px;
        border-radius: 8px;
    }
    .modal .btn-primary:hover {
        background: #2563eb;
    }
    .modal .btn-secondary {
        background: #e5e7eb;
        border: none;
        color: #374151;
        padding: 8px 20px;
        font-size: 13px;
        border-radius: 8px;
    }
    .modal .btn-secondary:hover {
        background: #d1d5db;
    }
    .modal .btn-success {
        background: #10b981;
        border: none;
        padding: 8px 20px;
        font-size: 13px;
        border-radius: 8px;
    }
    .modal .btn-success:hover {
        background: #059669;
    }
    .modal .section-divider {
        border: 0;
        border-top: 1px solid #e5e7eb;
        margin: 12px 0;
    }
    .modal .section-title {
        font-size: 13px;
        font-weight: 600;
        color: #3b82f6;
        margin-bottom: 8px;
    }
    .modal .text-muted-small {
        font-size: 11px;
        color: #9ca3af;
    }
    .modal .alert-error {
        background: #fef2f2;
        color: #991b1b;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 13px;
        border-left: 4px solid #ef4444;
    }
    .modal .alert-error ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .age-dob-group {
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }
    .age-dob-group .age-input {
        flex: 1;
    }
    .age-dob-group .dob-input {
        flex: 2;
    }
    .age-dob-group .age-display {
        font-size: 13px;
        color: #6c757d;
        padding-top: 4px;
        min-width: 80px;
    }
    .age-dob-group .age-display .age-label {
        font-size: 11px;
        color: #9ca3af;
    }
    .age-dob-group .age-display .age-value {
        font-weight: 600;
        color: #1e293b;
    }
    .input-group-text {
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .age-dob-group {
            flex-direction: column;
        }
        .age-dob-group .age-input,
        .age-dob-group .dob-input,
        .age-dob-group .age-display {
            width: 100%;
        }
        .age-dob-group .age-display {
            min-width: auto;
        }
    }
</style>