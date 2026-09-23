<div class="card">
    <div class="card-header bg-warning text-white">
        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Doctor</h5>
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
        
        <form method="POST" action="/unidia/public/doctor/update/<?php echo $doctor['id']; ?>" id="doctorForm">
            <div class="row">
                <!-- ============================================================ -->
                <!-- PERSONAL INFORMATION -->
                <!-- ============================================================ -->
                <div class="col-md-12 mb-3">
                    <h6 class="fw-bold text-primary"><i class="fas fa-user-circle me-2"></i>Personal Information</h6>
                    <hr>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <select name="title" class="form-select" required>
                        <option value="">Select</option>
                        <option value="Dr." <?php echo ($doctor['title'] == 'Dr.') ? 'selected' : ''; ?>>Dr.</option>
                        <option value="Prof." <?php echo ($doctor['title'] == 'Prof.') ? 'selected' : ''; ?>>Prof.</option>
                        <option value="Assoc. Prof." <?php echo ($doctor['title'] == 'Assoc. Prof.') ? 'selected' : ''; ?>>Assoc. Prof.</option>
                        <option value="Asst. Prof." <?php echo ($doctor['title'] == 'Asst. Prof.') ? 'selected' : ''; ?>>Asst. Prof.</option>
                        <option value="Physiotherapist" <?php echo ($user['title'] ?? '') == 'Physiotherapist' ? 'selected' : ''; ?>>Physiotherapist</option>
                    </select>
                </div>
                
                <div class="col-md-5 mb-3">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($doctor['first_name']); ?>" required>
                </div>
                
                <div class="col-md-5 mb-3">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($doctor['last_name']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                    <small class="text-muted">Leave blank to keep current password</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($doctor['phone']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo ($doctor['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo ($doctor['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo ($doctor['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" 
                           value="<?php echo $doctor['date_of_birth']; ?>">
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($doctor['address'] ?? ''); ?></textarea>
                </div>

                <!-- ============================================================ -->
                <!-- PROFESSIONAL INFORMATION -->
                <!-- ============================================================ -->
                <div class="col-md-12 mb-3 mt-2">
                    <h6 class="fw-bold text-success"><i class="fas fa-stethoscope me-2"></i>Professional Information</h6>
                    <hr>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Department <span class="text-danger">*</span></label>
                    <select name="department_id" class="form-select" required>
                        <option value="">Select Department</option>
                        <?php foreach($departments as $d): ?>
                            <option value="<?php echo $d['id']; ?>" 
                                <?php echo ($doctor['department_id'] == $d['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($d['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Specialization <span class="text-danger">*</span></label>
                    <input type="text" name="specialization" class="form-control" 
                           value="<?php echo htmlspecialchars($doctor['specialization']); ?>" required>
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Qualification <span class="text-danger">*</span></label>
                    <input type="text" name="qualification" class="form-control" 
                           value="<?php echo htmlspecialchars($doctor['qualification']); ?>" required>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Experience (Years)</label>
                    <input type="number" name="experience_years" class="form-control" 
                           value="<?php echo $doctor['experience_years']; ?>">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">BMDC No. <span class="text-danger">*</span></label>
                    <input type="text" name="bmdc_number" class="form-control" 
                           value="<?php echo htmlspecialchars($doctor['bmdc_number']); ?>" required>
                    <small class="text-muted">Bangladesh Medical & Dental Council Registration Number</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label class="form-label">Consultation Fee (৳) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="consultation_fee" class="form-control" 
                           value="<?php echo $doctor['consultation_fee']; ?>" required>
                </div>
                
                <!-- ============================================================ -->
                <!-- DOCTOR INFORMATION (English & Bangla) - NEW FIELDS -->
                <!-- ============================================================ -->
                <div class="col-12 mb-3 mt-2">
                    <h6 class="fw-bold text-primary"><i class="fas fa-info-circle me-2"></i>Doctor Information</h6>
                    <hr>
                    <p class="text-muted small">Add detailed information about the doctor that will appear on prescriptions</p>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor Information (English)</label>
                    <textarea name="doctor_info_en" class="form-control" rows="4" placeholder="Enter doctor information in English..."><?php echo htmlspecialchars($doctor['doctor_info_en'] ?? ''); ?></textarea>
                    <small class="text-muted">This will appear on prescriptions in English format</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Doctor Information (Bangla)</label>
                    <textarea name="doctor_info_bn" class="form-control" rows="4" placeholder="ডাক্তারের তথ্য বাংলায় লিখুন..."><?php echo htmlspecialchars($doctor['doctor_info_bn'] ?? ''); ?></textarea>
                    <small class="text-muted">This will appear on prescriptions in Bangla format</small>
                </div>
                
                <!-- ============================================================ -->
                <!-- SERVICES SECTION -->
                <!-- ============================================================ -->
                <div class="col-md-12 mb-3 mt-2">
                    <h6 class="fw-bold text-primary"><i class="fas fa-list-alt me-2"></i>Services & Price List with Commission</h6>
                    <hr>
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
                                <?php if(isset($doctor['services']) && count($doctor['services']) > 0): ?>
                                    <?php $serviceCounter = 1; ?>
                                    <?php foreach($doctor['services'] as $service): ?>
                                        <tr class="service-row" id="serviceRow_<?php echo $serviceCounter; ?>">
                                            <td class="text-center"><?php echo $serviceCounter; ?></td>
                                            <td>
                                                <input type="text" name="service_name[]" class="form-control" 
                                                       placeholder="e.g., ECG, Echo, Consultation" 
                                                       value="<?php echo htmlspecialchars($service['service_name']); ?>" required>
                                                <input type="hidden" name="service_id[]" value="<?php echo $service['id']; ?>">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="service_price[]" class="form-control service-price" 
                                                       placeholder="Price" value="<?php echo $service['service_price']; ?>" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="service_commission[]" class="form-control service-commission" 
                                                       placeholder="Commission %" value="<?php echo $service['commission_percentage']; ?>">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-danger btn-sm remove-service" data-id="<?php echo $serviceCounter; ?>" 
                                                        style="<?php echo (count($doctor['services']) <= 1) ? 'display: none;' : ''; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php $serviceCounter++; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
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
                
                <div class="col-md-12 mt-4">
                    <hr>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-2"></i> 
                        <strong>Note:</strong> Commission percentage is set per service. The commission amount will be calculated automatically based on the service price and commission percentage.
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Doctor
                    </button>
                    <a href="/unidia/public/doctor/list" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    var serviceCounter = <?php echo isset($doctor['services']) ? count($doctor['services']) : 1; ?>;
    
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
</style>