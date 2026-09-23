<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>Add New Doctor</h5>
    </div>
    <div class="card-body">
        <?php if(isset($_SESSION['errors']) && count($_SESSION['errors']) > 0): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach($_SESSION['errors'] as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>
        
        <form method="POST" action="/unidia/public/doctor/store" id="doctorForm">
            <!-- ============================================================ -->
            <!-- PERSONAL INFORMATION -->
            <!-- ============================================================ -->
            <div class="row">
                <div class="col-12 mb-3">
                    <h6 class="fw-bold text-primary"><i class="fas fa-user-circle me-2"></i>Personal Information</h6>
                    <hr>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <select name="title" class="form-select" required>
                        <option value="">Select</option>
                        <option value="Dr.">Dr.</option>
                        <option value="Prof.">Prof.</option>
                        <option value="Assoc. Prof.">Assoc. Prof.</option>
                        <option value="Asst. Prof.">Asst. Prof.</option>
                        <option value="Asst. Prof.">Physiotherapist</option>
                    </select>
                </div>
                
                <div class="col-md-5 mb-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['first_name']) ? htmlspecialchars($_SESSION['old_input']['first_name']) : ''; ?>" required>
                </div>
                
                <div class="col-md-5 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['last_name']) ? htmlspecialchars($_SESSION['old_input']['last_name']) : ''; ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['email']) ? htmlspecialchars($_SESSION['old_input']['email']) : ''; ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['phone']) ? htmlspecialchars($_SESSION['old_input']['phone']) : ''; ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo (isset($_SESSION['old_input']['gender']) && $_SESSION['old_input']['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (isset($_SESSION['old_input']['gender']) && $_SESSION['old_input']['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo (isset($_SESSION['old_input']['gender']) && $_SESSION['old_input']['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['date_of_birth']) ? $_SESSION['old_input']['date_of_birth'] : ''; ?>">
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo isset($_SESSION['old_input']['address']) ? htmlspecialchars($_SESSION['old_input']['address']) : ''; ?></textarea>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- PROFESSIONAL INFORMATION -->
            <!-- ============================================================ -->
            <div class="row">
                <div class="col-12 mb-3 mt-2">
                    <h6 class="fw-bold text-success"><i class="fas fa-stethoscope me-2"></i>Professional Information</h6>
                    <hr>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <select name="department_id" id="departmentSelect" class="form-select" required>
                            <option value="">Select Department</option>
                            <?php foreach($departments as $d): ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo (isset($_SESSION['old_input']['department_id']) && $_SESSION['old_input']['department_id'] == $d['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary" id="addDepartmentBtn" title="Add New Department">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <small class="text-muted">Select existing or add new department</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Specialization <span class="text-danger">*</span></label>
                    <input type="text" name="specialization" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['specialization']) ? htmlspecialchars($_SESSION['old_input']['specialization']) : ''; ?>" required>
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Qualification <span class="text-danger">*</span></label>
                    <input type="text" name="qualification" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['qualification']) ? htmlspecialchars($_SESSION['old_input']['qualification']) : ''; ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Experience (Years)</label>
                    <input type="number" name="experience_years" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['experience_years']) ? $_SESSION['old_input']['experience_years'] : 0; ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">BMDC No. <span class="text-danger">*</span></label>
                    <input type="text" name="bmdc_number" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['bmdc_number']) ? htmlspecialchars($_SESSION['old_input']['bmdc_number']) : ''; ?>" required>
                    <small class="text-muted">Bangladesh Medical & Dental Council Registration Number</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Consultation Fee (৳) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="consultation_fee" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['consultation_fee']) ? $_SESSION['old_input']['consultation_fee'] : ''; ?>" required>
                </div>
                
                <!-- ============================================================ -->
                <!-- DOCTOR INFORMATION (English & Bangla) -->
                <!-- ============================================================ -->
                <div class="col-12 mb-3 mt-2">
                    <h6 class="fw-bold text-primary"><i class="fas fa-info-circle me-2"></i>Doctor Information</h6>
                    <hr>
                    <p class="text-muted small">Add detailed information about the doctor that will appear on prescriptions</p>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor Information (English)</label>
                    <textarea name="doctor_info_en" class="form-control" rows="4" placeholder="Enter doctor information in English..."><?php echo isset($_SESSION['old_input']['doctor_info_en']) ? htmlspecialchars($_SESSION['old_input']['doctor_info_en']) : ''; ?></textarea>
                    <small class="text-muted">This will appear on prescriptions in English format</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor Information (Bangla)</label>
                    <textarea name="doctor_info_bn" class="form-control" rows="4" placeholder="ডাক্তারের তথ্য বাংলায় লিখুন..."><?php echo isset($_SESSION['old_input']['doctor_info_bn']) ? htmlspecialchars($_SESSION['old_input']['doctor_info_bn']) : ''; ?></textarea>
                    <small class="text-muted">This will appear on prescriptions in Bangla format</small>
                </div>
                
                <!-- ============================================================ -->
                <!-- SERVICES SECTION -->
                <!-- ============================================================ -->
                <div class="col-md-12 mb-3 mt-2">
                    <h6 class="fw-bold text-primary"><i class="fas fa-list-alt me-2"></i>Services & Price List with Commission</h6>
                    <hr>
                    <p class="text-muted small">Add services that this doctor provides with their specific prices and commission percentages</p>
                </div>
                
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="servicesTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="45%">Service Name</th>
                                    <th width="20%">Price (৳)</th>
                                    <th width="20%">Commission (%)</th>
                                    <th width="10%">Action</th>
                                </tr>
                            </thead>
                            <tbody id="servicesContainer">
                                <?php 
                                $oldServices = isset($_SESSION['old_input']['services']) ? $_SESSION['old_input']['services'] : [];
                                if(empty($oldServices)):
                                ?>
                                <tr class="service-row" id="serviceRow_1">
                                    <td class="text-center">1</td>
                                    <td>
                                        <input type="text" name="service_name[]" class="form-control" placeholder="e.g., ECG, Echo, Consultation" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="service_price[]" class="form-control service-price" placeholder="Price" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="service_commission[]" class="form-control service-commission" placeholder="Commission %" value="0">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm remove-service" data-id="1" style="display: none;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php $counter = 1; foreach($oldServices as $service): ?>
                                    <tr class="service-row" id="serviceRow_<?php echo $counter; ?>">
                                        <td class="text-center"><?php echo $counter; ?></td>
                                        <td>
                                            <input type="text" name="service_name[]" class="form-control" 
                                                   value="<?php echo htmlspecialchars($service['name'] ?? ''); ?>" 
                                                   placeholder="e.g., ECG, Echo, Consultation" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="service_price[]" class="form-control service-price" 
                                                   value="<?php echo $service['price'] ?? ''; ?>" placeholder="Price" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="service_commission[]" class="form-control service-commission" 
                                                   value="<?php echo $service['commission'] ?? 0; ?>" placeholder="Commission %">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-danger btn-sm remove-service" data-id="<?php echo $counter; ?>"
                                                    style="<?php echo count($oldServices) <= 1 ? 'display: none;' : ''; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php $counter++; endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5">
                                        <button type="button" class="btn btn-secondary btn-sm" id="addServiceBtn">
                                            <i class="fas fa-plus me-1"></i> Add Another Service
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- PAYROLL INFORMATION (Optional) -->
            <!-- ============================================================ -->
            <div class="row">
                <div class="col-12 mb-3">
                    <h6 class="fw-bold text-warning"><i class="fas fa-money-bill-wave me-2"></i>Payroll Information <span class="text-muted">(Optional)</span></h6>
                    <hr>
                </div>
                <div class="col-md-12 mb-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Payroll information is optional. You can set it up later from the Payroll management section.
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Basic Salary (৳)</label>
                    <input type="number" name="basic_salary" class="form-control" placeholder="e.g. 50000" step="100" 
                           value="<?php echo isset($_SESSION['old_input']['basic_salary']) ? $_SESSION['old_input']['basic_salary'] : ''; ?>">
                    <small class="text-muted">Leave empty to set default based on role</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">House Rent Allowance (HRA) (৳)</label>
                    <input type="number" name="hra" class="form-control" placeholder="e.g. 10000" step="100" 
                           value="<?php echo isset($_SESSION['old_input']['hra']) ? $_SESSION['old_input']['hra'] : ''; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Medical Allowance (৳)</label>
                    <input type="number" name="medical_allowance" class="form-control" placeholder="e.g. 5000" step="100" 
                           value="<?php echo isset($_SESSION['old_input']['medical_allowance']) ? $_SESSION['old_input']['medical_allowance'] : ''; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Conveyance Allowance (৳)</label>
                    <input type="number" name="conveyance" class="form-control" placeholder="e.g. 5000" step="100" 
                           value="<?php echo isset($_SESSION['old_input']['conveyance']) ? $_SESSION['old_input']['conveyance'] : ''; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Other Allowances (৳)</label>
                    <input type="number" name="other_allowances" class="form-control" placeholder="e.g. 0" step="100" 
                           value="<?php echo isset($_SESSION['old_input']['other_allowances']) ? $_SESSION['old_input']['other_allowances'] : ''; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Provident Fund (PF) (৳)</label>
                    <input type="number" name="provident_fund" class="form-control" placeholder="e.g. 6000" step="100" 
                           value="<?php echo isset($_SESSION['old_input']['provident_fund']) ? $_SESSION['old_input']['provident_fund'] : ''; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Professional Tax (PT) (৳)</label>
                    <input type="number" name="professional_tax" class="form-control" placeholder="e.g. 200" step="100" 
                           value="<?php echo isset($_SESSION['old_input']['professional_tax']) ? $_SESSION['old_input']['professional_tax'] : ''; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Income Tax (TDS) (৳)</label>
                    <input type="number" name="income_tax" class="form-control" placeholder="e.g. 0" step="100" 
                           value="<?php echo isset($_SESSION['old_input']['income_tax']) ? $_SESSION['old_input']['income_tax'] : ''; ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Other Deductions (৳)</label>
                    <input type="number" name="other_deductions" class="form-control" placeholder="e.g. 0" step="100" 
                           value="<?php echo isset($_SESSION['old_input']['other_deductions']) ? $_SESSION['old_input']['other_deductions'] : ''; ?>">
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Doctor
                    </button>
                    <a href="/unidia/public/doctor/list" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD DEPARTMENT MODAL - FIXED WITH PROPER BACKDROP HANDLING -->
<!-- ============================================================ -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addDepartmentModalLabel"><i class="fas fa-plus-circle me-2"></i>Add New Department</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="departmentAlert" style="display: none;"></div>
                <div class="mb-3">
                    <label class="form-label">Department Name <span class="text-danger">*</span></label>
                    <input type="text" id="newDepartmentName" class="form-control" placeholder="e.g., Cardiology, Neurology" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Department Code</label>
                    <input type="text" id="newDepartmentCode" class="form-control" placeholder="e.g., CARD, NEURO" maxlength="10">
                    <small class="text-muted">Optional short code for the department</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea id="newDepartmentDescription" class="form-control" rows="2" placeholder="Department description..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="saveDepartmentBtn">
                    <i class="fas fa-save me-1"></i> Save Department
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    var serviceCounter = $('#servicesContainer .service-row').length || 1;
    var departmentModal = null;
    
    // Initialize modal
    if (document.getElementById('addDepartmentModal')) {
        departmentModal = new bootstrap.Modal(document.getElementById('addDepartmentModal'), {
            backdrop: 'static',
            keyboard: true
        });
    }
    
    function updateSerialNumbers() {
        $('.service-row').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }
    
    $('#addServiceBtn').click(function() {
        serviceCounter++;
        var newRowId = 'serviceRow_' + serviceCounter;
        
        var newRow = `
            <tr class="service-row" id="${newRowId}">
                <td class="text-center">${serviceCounter}</td>
                <td>
                    <input type="text" name="service_name[]" class="form-control" placeholder="e.g., ECG, Echo, Consultation" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="service_price[]" class="form-control service-price" placeholder="Price" required>
                </td>
                <td>
                    <input type="number" step="0.01" name="service_commission[]" class="form-control service-commission" placeholder="Commission %" value="0">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-danger btn-sm remove-service" data-id="${serviceCounter}">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('#servicesContainer').append(newRow);
        updateRemoveButtons();
        updateSerialNumbers();
    });
    
    $(document).on('click', '.remove-service', function() {
        var rowId = $(this).data('id');
        $('#serviceRow_' + rowId).remove();
        updateRemoveButtons();
        updateSerialNumbers();
    });
    
    function updateRemoveButtons() {
        var rowCount = $('.service-row').length;
        if (rowCount > 1) {
            $('.remove-service').show();
        } else {
            $('.remove-service').hide();
        }
    }
    
    updateRemoveButtons();
    
    // ============================================================ //
    // OPEN MODAL - Using button click instead of data-bs-toggle    //
    // ============================================================ //
    $('#addDepartmentBtn').click(function() {
        // Reset form fields
        $('#newDepartmentName').val('');
        $('#newDepartmentCode').val('');
        $('#newDepartmentDescription').val('');
        $('#departmentAlert').hide().removeClass('alert alert-success alert-danger');
        $('#saveDepartmentBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Department');
        
        // Show modal using Bootstrap's API
        if (departmentModal) {
            departmentModal.show();
        } else {
            // Fallback
            $('#addDepartmentModal').modal('show');
        }
    });
    
    // ============================================================ //
    // ADD DEPARTMENT VIA AJAX                                      //
    // ============================================================ //
    $('#saveDepartmentBtn').click(function() {
        var name = $('#newDepartmentName').val().trim();
        var code = $('#newDepartmentCode').val().trim();
        var description = $('#newDepartmentDescription').val().trim();
        var alertDiv = $('#departmentAlert');
        
        // Validate
        if (name === '') {
            alertDiv.removeClass('alert-success alert-danger').addClass('alert alert-danger').html('<i class="fas fa-exclamation-circle me-2"></i>Department name is required.').show();
            $('#newDepartmentName').focus();
            return;
        }
        
        // Disable button and show loading
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');
        alertDiv.hide();
        
        $.ajax({
            url: '/unidia/public/api/add-department',
            method: 'POST',
            data: {
                name: name,
                code: code,
                description: description
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Add new department to the select dropdown
                    var newOption = $('<option>', {
                        value: response.department_id,
                        text: response.department_name,
                        selected: true
                    });
                    $('#departmentSelect').append(newOption);
                    
                    // Show success message
                    alertDiv.removeClass('alert-danger').addClass('alert alert-success').html('<i class="fas fa-check-circle me-2"></i>' + response.message).show();
                    
                    // Reset form
                    $('#newDepartmentName').val('');
                    $('#newDepartmentCode').val('');
                    $('#newDepartmentDescription').val('');
                    
                    // ============================================================ //
                    // FIX: Properly close modal and remove backdrop              //
                    // ============================================================ //
                    setTimeout(function() {
                        // Hide the modal properly
                        if (departmentModal) {
                            departmentModal.hide();
                        } else {
                            $('#addDepartmentModal').modal('hide');
                        }
                        
                        // Force remove any lingering backdrops
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                        $('body').css('overflow', 'auto');
                        $('body').css('padding-right', '');
                        
                        alertDiv.hide();
                    }, 1200);
                } else {
                    alertDiv.removeClass('alert-success').addClass('alert alert-danger').html('<i class="fas fa-exclamation-circle me-2"></i>' + response.message).show();
                    $('#saveDepartmentBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Department');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Failed to add department. Please try again.';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch(e) {}
                alertDiv.removeClass('alert-success').addClass('alert alert-danger').html('<i class="fas fa-exclamation-circle me-2"></i>' + errorMsg).show();
                $('#saveDepartmentBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Department');
            }
        });
    });
    
    // ============================================================ //
    // FIX: Clean up backdrop when modal is hidden manually         //
    // ============================================================ //
    $('#addDepartmentModal').on('hidden.bs.modal', function() {
        // Reset form fields
        $('#newDepartmentName').val('');
        $('#newDepartmentCode').val('');
        $('#newDepartmentDescription').val('');
        $('#departmentAlert').hide().removeClass('alert alert-success alert-danger');
        $('#saveDepartmentBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Department');
        
        // Ensure backdrop is removed
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open');
        $('body').css('overflow', 'auto');
        $('body').css('padding-right', '');
    });
    
    // ============================================================ //
    // FIX: Also clean up if modal is closed via ESC or click      //
    // ============================================================ //
    $(document).on('click', '[data-bs-dismiss="modal"]', function() {
        setTimeout(function() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            $('body').css('overflow', 'auto');
            $('body').css('padding-right', '');
        }, 300);
    });
});
</script>

<style>
    #servicesTable {
        border-radius: 10px;
        overflow: hidden;
    }
    #servicesTable thead th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 13px;
    }
    .service-row input {
        font-size: 14px;
    }
    .btn-sm {
        padding: 5px 10px;
    }
    
    /* Ensure modal backdrop doesn't cause blur issues */
    .modal-backdrop {
        opacity: 0.5 !important;
    }
    .modal-open {
        overflow: auto !important;
        padding-right: 0 !important;
    }
</style>

<?php unset($_SESSION['old_input']); ?>