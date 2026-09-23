<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-user-plus me-2"></i>Create New User</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="/unidia/public/admin/users/store" id="createUserForm">
            <!-- Basic Information -->
            <div class="row">
                <div class="col-12 mb-3">
                    <h6 class="fw-bold text-primary"><i class="fas fa-user me-2"></i>Basic Information</h6>
                    <hr>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Title</label>
                    <select name="title" class="form-select">
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
                    <input type="text" name="first_name" class="form-control" required>
                </div>
                <div class="col-md-5 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                    <small class="text-muted">Minimum 6 characters</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required id="roleSelect">
                        <option value="">Select Role</option>
                        <?php foreach($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo $role['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Department with Add New Button -->
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department</label>
                    <div class="input-group">
                        <select name="department_id" id="departmentSelect" class="form-select">
                            <option value="">Select Department</option>
                            <?php foreach($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>"><?php echo $dept['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary" id="addDeptBtn" title="Add New Department">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <small class="text-muted">Select existing or add new department</small>
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <!-- Doctor Specific Fields -->
            <div class="row" id="doctorFields" style="display: none;">
                <div class="col-12 mb-3">
                    <h6 class="fw-bold text-success"><i class="fas fa-user-md me-2"></i>Doctor Information</h6>
                    <hr>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Specialization <span class="text-danger">*</span></label>
                    <input type="text" name="specialization" class="form-control" placeholder="e.g. Cardiologist" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Qualification <span class="text-danger">*</span></label>
                    <input type="text" name="qualification" class="form-control" placeholder="e.g. MBBS, MD" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Experience (Years)</label>
                    <input type="number" name="experience_years" class="form-control" placeholder="0" value="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">License Number (BMDC) <span class="text-danger">*</span></label>
                    <input type="text" name="license_number" class="form-control" placeholder="e.g. A12345" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Consultation Fee (৳) <span class="text-danger">*</span></label>
                    <input type="number" name="consultation_fee" class="form-control" placeholder="0.00" step="0.01" required>
                </div>
                
                <!-- Services Section with Commission -->
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

            <!-- Payroll Information (Optional) -->
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
                    <input type="number" name="basic_salary" class="form-control" placeholder="e.g. 50000" step="100">
                    <small class="text-muted">Leave empty to set default based on role</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">House Rent Allowance (HRA) (৳)</label>
                    <input type="number" name="hra" class="form-control" placeholder="e.g. 10000" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Medical Allowance (৳)</label>
                    <input type="number" name="medical_allowance" class="form-control" placeholder="e.g. 5000" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Conveyance Allowance (৳)</label>
                    <input type="number" name="conveyance" class="form-control" placeholder="e.g. 5000" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Other Allowances (৳)</label>
                    <input type="number" name="other_allowances" class="form-control" placeholder="e.g. 0" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Provident Fund (PF) (৳)</label>
                    <input type="number" name="provident_fund" class="form-control" placeholder="e.g. 6000" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Professional Tax (PT) (৳)</label>
                    <input type="number" name="professional_tax" class="form-control" placeholder="e.g. 200" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Income Tax (TDS) (৳)</label>
                    <input type="number" name="income_tax" class="form-control" placeholder="e.g. 0" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Other Deductions (৳)</label>
                    <input type="number" name="other_deductions" class="form-control" placeholder="e.g. 0" step="100">
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Create User
                    </button>
                    <a href="/unidia/public/admin/users" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- ADD DEPARTMENT MODAL -->
<!-- ============================================================ -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1" aria-labelledby="addDepartmentModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="addDepartmentModalLabel"><i class="fas fa-plus-circle me-2"></i>Add New Department</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="deptAlert" style="display: none;"></div>
                <div class="mb-3">
                    <label class="form-label">Department Name <span class="text-danger">*</span></label>
                    <input type="text" id="newDeptName" class="form-control" placeholder="e.g., Cardiology, Neurology" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Department Code</label>
                    <input type="text" id="newDeptCode" class="form-control" placeholder="e.g., CARD, NEURO" maxlength="10">
                    <small class="text-muted">Optional short code for the department</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea id="newDeptDesc" class="form-control" rows="2" placeholder="Department description..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="saveDeptBtn">
                    <i class="fas fa-save me-1"></i> Save Department
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('roleSelect');
    const doctorFields = document.getElementById('doctorFields');
    var departmentModal = null;
    
    // Initialize modal
    if (document.getElementById('addDepartmentModal')) {
        departmentModal = new bootstrap.Modal(document.getElementById('addDepartmentModal'), {
            backdrop: 'static',
            keyboard: true
        });
    }
    
    function toggleDoctorFields() {
        const selectedRole = roleSelect.options[roleSelect.selectedIndex]?.text || '';
        if (selectedRole === 'Doctor') {
            doctorFields.style.display = 'block';
            // Make doctor fields required
            document.querySelectorAll('#doctorFields input[required]').forEach(el => {
                el.setAttribute('required', 'required');
            });
        } else {
            doctorFields.style.display = 'none';
            // Remove required from doctor fields
            document.querySelectorAll('#doctorFields input[required]').forEach(el => {
                el.removeAttribute('required');
            });
        }
    }
    
    roleSelect.addEventListener('change', toggleDoctorFields);
    toggleDoctorFields();
    
    // Services management
    var serviceCounter = 1;
    
    // Update serial numbers
    function updateSerialNumbers() {
        $('.service-row').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }
    
    // Add new service row
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
    
    // Remove service row
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
    
    // Initialize
    updateRemoveButtons();
    
    // ============================================================ //
    // OPEN MODAL - Using button click instead of data-bs-toggle    //
    // ============================================================ //
    $('#addDeptBtn').click(function() {
        // Reset form fields
        $('#newDeptName').val('');
        $('#newDeptCode').val('');
        $('#newDeptDesc').val('');
        $('#deptAlert').hide().removeClass('alert alert-success alert-danger');
        $('#saveDeptBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Department');
        
        // Show modal using Bootstrap's API
        if (departmentModal) {
            departmentModal.show();
        } else {
            $('#addDepartmentModal').modal('show');
        }
    });
    
    // ============================================================ //
    // ADD DEPARTMENT VIA AJAX                                      //
    // ============================================================ //
    $('#saveDeptBtn').click(function() {
        var name = $('#newDeptName').val().trim();
        var code = $('#newDeptCode').val().trim();
        var description = $('#newDeptDesc').val().trim();
        var alertDiv = $('#deptAlert');
        
        // Validate
        if (name === '') {
            alertDiv.removeClass('alert-success alert-danger').addClass('alert alert-danger').html('<i class="fas fa-exclamation-circle me-2"></i>Department name is required.').show();
            $('#newDeptName').focus();
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
                    $('#newDeptName').val('');
                    $('#newDeptCode').val('');
                    $('#newDeptDesc').val('');
                    
                    // Close modal properly
                    setTimeout(function() {
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
                    $('#saveDeptBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Department');
                }
            },
            error: function(xhr) {
                var errorMsg = 'Failed to add department. Please try again.';
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.message) errorMsg = response.message;
                } catch(e) {}
                alertDiv.removeClass('alert-success').addClass('alert alert-danger').html('<i class="fas fa-exclamation-circle me-2"></i>' + errorMsg).show();
                $('#saveDeptBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Department');
            }
        });
    });
    
    // ============================================================ //
    // FIX: Clean up backdrop when modal is hidden manually         //
    // ============================================================ //
    $('#addDepartmentModal').on('hidden.bs.modal', function() {
        // Reset form fields
        $('#newDeptName').val('');
        $('#newDeptCode').val('');
        $('#newDeptDesc').val('');
        $('#deptAlert').hide().removeClass('alert alert-success alert-danger');
        $('#saveDeptBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Department');
        
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