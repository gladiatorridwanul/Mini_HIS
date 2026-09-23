<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-user-edit me-2"></i>Edit User: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
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
        
        <form method="POST" action="/unidia/public/admin/users/update/<?php echo $user['id']; ?>" id="editUserForm">
            <!-- ============================================================ -->
            <!-- BASIC INFORMATION -->
            <!-- ============================================================ -->
            <div class="row">
                <div class="col-12 mb-3">
                    <h6 class="fw-bold text-primary"><i class="fas fa-user me-2"></i>Basic Information</h6>
                    <hr>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Title</label>
                    <select name="title" class="form-select">
                        <option value="">Select</option>
                        <option value="Dr." <?php echo ($user['title'] ?? '') == 'Dr.' ? 'selected' : ''; ?>>Dr.</option>
                        <option value="Prof." <?php echo ($user['title'] ?? '') == 'Prof.' ? 'selected' : ''; ?>>Prof.</option>
                        <option value="Assoc. Prof." <?php echo ($user['title'] ?? '') == 'Assoc. Prof.' ? 'selected' : ''; ?>>Assoc. Prof.</option>
                        <option value="Asst. Prof." <?php echo ($user['title'] ?? '') == 'Asst. Prof.' ? 'selected' : ''; ?>>Asst. Prof.</option>
                        <option value="Physiotherapist" <?php echo ($user['title'] ?? '') == 'Physiotherapist' ? 'selected' : ''; ?>>Physiotherapist</option>
                    </select>
                </div>
                
                <div class="col-md-5 mb-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                
                <div class="col-md-5 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Employee ID</label>
                    <input type="text" class="form-control" 
                           value="<?php echo htmlspecialchars($user['employee_id'] ?? 'N/A'); ?>" disabled>
                    <small class="text-muted">Employee ID cannot be changed</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                    <small class="text-muted">Leave blank to keep current password</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo ($user['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($user['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo ($user['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" 
                           value="<?php echo $user['date_of_birth'] ?? ''; ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Role <span class="text-danger">*</span></label>
                    <select name="role_id" class="form-select" required id="roleSelect">
                        <?php foreach($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>" <?php echo $user['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($role['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">Select Department</option>
                        <?php foreach($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" 
                                <?php echo ($userDepartment ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- DOCTOR SPECIFIC FIELDS -->
            <!-- ============================================================ -->
            <div class="row" id="doctorFields" style="display: <?php echo ($user['role_id'] == 3) ? 'block' : 'none'; ?>;">
                <div class="col-12 mb-3">
                    <h6 class="fw-bold text-success"><i class="fas fa-user-md me-2"></i>Doctor Information</h6>
                    <hr>
                </div>
                <?php 
                $doctorInfo = $doctorInfo ?? null;
                $doctorServices = $doctorServices ?? [];
                ?>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Specialization <span class="text-danger">*</span></label>
                    <input type="text" name="specialization" class="form-control" 
                           value="<?php echo htmlspecialchars($doctorInfo['specialization'] ?? 'General Medicine'); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Qualification <span class="text-danger">*</span></label>
                    <input type="text" name="qualification" class="form-control" 
                           value="<?php echo htmlspecialchars($doctorInfo['qualification'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Experience (Years)</label>
                    <input type="number" name="experience_years" class="form-control" 
                           value="<?php echo $doctorInfo['experience_years'] ?? 0; ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">License Number (BMDC) <span class="text-danger">*</span></label>
                    <input type="text" name="license_number" class="form-control" 
                           value="<?php echo htmlspecialchars($doctorInfo['bmdc_number'] ?? ''); ?>" required>
                    <small class="text-muted">Bangladesh Medical & Dental Council Registration Number</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Consultation Fee (৳) <span class="text-danger">*</span></label>
                    <input type="number" name="consultation_fee" class="form-control" 
                           value="<?php echo $doctorInfo['consultation_fee'] ?? 0; ?>" step="0.01" required>
                </div>
                
                <!-- ============================================================ -->
                <!-- DOCTOR INFORMATION (English & Bangla) - NEW FIELDS -->
                <!-- ============================================================ -->
                <div class="col-12 mb-3 mt-2">
                    <h6 class="fw-bold text-primary"><i class="fas fa-info-circle me-2"></i>Doctor Information (Prescription Heading)</h6>
                    <hr>
                    <p class="text-muted small">Add detailed information about the doctor that will appear on prescriptions</p>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor Information (English)</label>
                    <textarea name="doctor_info_en" class="form-control" rows="4" placeholder="Enter doctor information in English..."><?php echo htmlspecialchars($doctorInfo['doctor_info_en'] ?? ''); ?></textarea>
                    <small class="text-muted">This will appear on prescriptions in English format</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor Information (Bangla)</label>
                    <textarea name="doctor_info_bn" class="form-control" rows="4" placeholder="ডাক্তারের তথ্য বাংলায় লিখুন..."><?php echo htmlspecialchars($doctorInfo['doctor_info_bn'] ?? ''); ?></textarea>
                    <small class="text-muted">This will appear on prescriptions in Bangla format</small>
                </div>
                
                <!-- ============================================================ -->
                <!-- SERVICES SECTION WITH COMMISSION -->
                <!-- ============================================================ -->
                <div class="col-md-12 mb-3 mt-2">
                    <h6 class="fw-bold text-primary"><i class="fas fa-list-alt me-2"></i>Services & Price List with Commission</h6>
                    <hr>
                    <p class="text-muted small">Add or edit services that this doctor provides with their specific prices and commission percentages</p>
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
                                <?php if(empty($doctorServices)): ?>
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
                                <?php $i = 1; foreach($doctorServices as $service): ?>
                                <tr class="service-row" id="serviceRow_<?php echo $i; ?>">
                                    <td class="text-center"><?php echo $i; ?></td>
                                    <td>
                                        <input type="text" name="service_name[]" class="form-control" 
                                               value="<?php echo htmlspecialchars($service['service_name']); ?>" placeholder="e.g., ECG, Echo, Consultation" required>
                                        <input type="hidden" name="service_id[]" value="<?php echo $service['id']; ?>">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="service_price[]" class="form-control service-price" 
                                               value="<?php echo $service['service_price']; ?>" placeholder="Price" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" name="service_commission[]" class="form-control service-commission" 
                                               value="<?php echo $service['commission_percentage']; ?>" placeholder="Commission %">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm remove-service" data-id="<?php echo $i; ?>"
                                                <?php echo count($doctorServices) <= 1 ? 'style="display: none;"' : ''; ?>>
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php $i++; endforeach; ?>
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
                        Payroll information is optional. Leave fields empty to keep current values or use defaults.
                    </div>
                </div>
                <?php 
                $payrollData = $payrollData ?? null;
                ?>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Basic Salary (৳)</label>
                    <input type="number" name="basic_salary" class="form-control" 
                           value="<?php echo $payrollData['basic_salary'] ?? ''; ?>" step="100">
                    <small class="text-muted">Leave empty to keep current</small>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">House Rent Allowance (HRA) (৳)</label>
                    <input type="number" name="hra" class="form-control" 
                           value="<?php echo $payrollData['hra'] ?? 0; ?>" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Medical Allowance (৳)</label>
                    <input type="number" name="medical_allowance" class="form-control" 
                           value="<?php echo $payrollData['medical_allowance'] ?? 0; ?>" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Conveyance Allowance (৳)</label>
                    <input type="number" name="conveyance" class="form-control" 
                           value="<?php echo $payrollData['conveyance'] ?? 0; ?>" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Other Allowances (৳)</label>
                    <input type="number" name="other_allowances" class="form-control" 
                           value="<?php echo $payrollData['other_allowances'] ?? 0; ?>" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Provident Fund (PF) (৳)</label>
                    <input type="number" name="provident_fund" class="form-control" 
                           value="<?php echo $payrollData['provident_fund'] ?? 0; ?>" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Professional Tax (PT) (৳)</label>
                    <input type="number" name="professional_tax" class="form-control" 
                           value="<?php echo $payrollData['professional_tax'] ?? 0; ?>" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Income Tax (TDS) (৳)</label>
                    <input type="number" name="income_tax" class="form-control" 
                           value="<?php echo $payrollData['income_tax'] ?? 0; ?>" step="100">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Other Deductions (৳)</label>
                    <input type="number" name="other_deductions" class="form-control" 
                           value="<?php echo $payrollData['other_deductions'] ?? 0; ?>" step="100">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo ($user['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($user['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="suspended" <?php echo ($user['status'] ?? '') == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- SUBMIT BUTTONS -->
            <!-- ============================================================ -->
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update User
                    </button>
                    <a href="/unidia/public/admin/users" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('roleSelect');
    const doctorFields = document.getElementById('doctorFields');
    
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
    
    // ==================== SERVICES MANAGEMENT ====================
    var serviceCounter = <?php echo isset($doctorServices) ? count($doctorServices) : 1; ?>;
    
    function updateSerialNumbers() {
        $('.service-row').each(function(index) {
            $(this).find('td:first').text(index + 1);
        });
    }
    
    function updateRemoveButtons() {
        var rowCount = $('.service-row').length;
        if (rowCount > 1) {
            $('.remove-service').show();
        } else {
            $('.remove-service').hide();
        }
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
    
    // Initialize
    updateRemoveButtons();
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
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .card-header h5 {
        color: white;
        margin: 0;
    }
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .status-active { background: #d4edda; color: #155724; }
    .status-inactive { background: #f8d7da; color: #721c24; }
    .status-suspended { background: #fff3cd; color: #856404; }
</style>

<?php unset($_SESSION['old_input']); ?>