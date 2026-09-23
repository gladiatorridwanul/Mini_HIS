<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5><i class="fas fa-user-plus me-2"></i>Register New Patient</h5>
            <small class="text-muted">Fields marked with * are required</small>
        </div>
        <a href="/unidia/public/patient/list" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to List
        </a>
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
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- FIXED: Added note about same phone number -->
        <div class="alert alert-info alert-dismissible fade show mb-3">
            <i class="fas fa-info-circle me-2"></i> 
            <strong>Note:</strong> Multiple patients can be registered with the same phone number (e.g., family members sharing a phone).
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        
        <form method="POST" action="/unidia/public/patient/store" id="patientForm">
            <!-- Personal Information -->
            <div class="row">
                <div class="col-md-12 mb-2">
                    <h6 class="text-primary"><i class="fas fa-user-circle me-2"></i>Personal Information</h6>
                    <hr class="mt-1">
                </div>
                
                <div class="col-md-12 mb-2">
                    <label>Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="full_name" id="full_name" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['full_name']) ? htmlspecialchars($_SESSION['old_input']['full_name']) : ''; ?>" 
                           placeholder="Enter patient's full name"
                           required>
                </div>
                
                <div class="col-md-6 mb-2">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['email']) ? htmlspecialchars($_SESSION['old_input']['email']) : ''; ?>"
                           placeholder="patient@example.com">
                    <small class="text-muted">Optional</small>
                </div>
                
                <div class="col-md-6 mb-2">
                    <label>Phone Number <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" id="phone" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['phone']) ? htmlspecialchars($_SESSION['old_input']['phone']) : ''; ?>" 
                           placeholder="01XXXXXXXXX"
                           required>
                    <small class="text-muted">Multiple patients can share the same phone number</small>
                </div>
                
                <div class="col-md-4 mb-2">
                    <label>Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo (isset($_SESSION['old_input']['gender']) && $_SESSION['old_input']['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (isset($_SESSION['old_input']['gender']) && $_SESSION['old_input']['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo (isset($_SESSION['old_input']['gender']) && $_SESSION['old_input']['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-2">
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" class="form-control" 
                           value="<?php echo isset($_SESSION['old_input']['date_of_birth']) ? htmlspecialchars($_SESSION['old_input']['date_of_birth']) : ''; ?>">
                </div>
                
                <div class="col-md-4 mb-2">
                    <label>Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">Select Blood Group</option>
                        <option value="A+" <?php echo (isset($_SESSION['old_input']['blood_group']) && $_SESSION['old_input']['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo (isset($_SESSION['old_input']['blood_group']) && $_SESSION['old_input']['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo (isset($_SESSION['old_input']['blood_group']) && $_SESSION['old_input']['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo (isset($_SESSION['old_input']['blood_group']) && $_SESSION['old_input']['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                        <option value="O+" <?php echo (isset($_SESSION['old_input']['blood_group']) && $_SESSION['old_input']['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo (isset($_SESSION['old_input']['blood_group']) && $_SESSION['old_input']['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                        <option value="AB+" <?php echo (isset($_SESSION['old_input']['blood_group']) && $_SESSION['old_input']['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo (isset($_SESSION['old_input']['blood_group']) && $_SESSION['old_input']['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                    </select>
                </div>
            </div>
            
            <!-- Address Information -->
            <div class="row mt-2">
                <div class="col-md-12 mb-2">
                    <h6 class="text-primary"><i class="fas fa-map-marker-alt me-2"></i>Address Information</h6>
                    <hr class="mt-1">
                </div>
                
                <div class="col-md-12 mb-2">
                    <label>House/Street Address</label>
                    <textarea name="address" id="address" class="form-control" rows="2" 
                              placeholder="House/Flat No, Road/Street, Village/Area"><?php echo isset($_SESSION['old_input']['address']) ? htmlspecialchars($_SESSION['old_input']['address']) : ''; ?></textarea>
                    <small class="text-muted">Optional</small>
                </div>
                
                <div class="col-md-4 mb-2">
                    <label>Division</label>
                    <select name="division_id" id="division_id" class="form-select">
                        <option value="">Select Division</option>
                        <?php foreach($divisions as $division): ?>
                            <option value="<?php echo $division['id']; ?>" 
                                <?php echo (isset($_SESSION['old_input']['division_id']) && $_SESSION['old_input']['division_id'] == $division['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($division['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-4 mb-2">
                    <label>District</label>
                    <select name="district_id" id="district_id" class="form-select" disabled>
                        <option value="">Select Division First</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-2">
                    <label>Thana / Upazila</label>
                    <select name="thana_id" id="thana_id" class="form-select" disabled>
                        <option value="">Select District First</option>
                    </select>
                </div>
            </div>
            
            <!-- Form Actions -->
            <div class="row mt-3">
                <div class="col-md-12">
                    <hr>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary" id="btnRegister">
                            <i class="fas fa-user-plus me-1"></i> Register Patient
                        </button>
                        <button type="submit" class="btn btn-success" id="btnSaveRegister" name="action" value="save_register">
                            <i class="fas fa-save me-1"></i> Save & Register
                        </button>
                        <a href="/unidia/public/patient/list" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Cancel
                        </a>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle"></i> Press <kbd>Enter</kbd> to Save & Register, or click Register Patient to save and go to list.
                    </small>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Division -> District -> Thana loading
    $('#division_id').change(function() {
        var division_id = $(this).val();
        
        if (division_id) {
            $.ajax({
                url: '/unidia/public/api/get-districts',
                type: 'GET',
                data: { division_id: division_id },
                dataType: 'json',
                success: function(data) {
                    $('#district_id').html('<option value="">Select District</option>');
                    $('#thana_id').html('<option value="">Select District First</option>');
                    $('#thana_id').prop('disabled', true);
                    
                    if (data.length > 0) {
                        $.each(data, function(key, district) {
                            $('#district_id').append('<option value="' + district.id + '">' + district.name + '</option>');
                        });
                        $('#district_id').prop('disabled', false);
                    } else {
                        $('#district_id').html('<option value="">No districts found</option>');
                        $('#district_id').prop('disabled', true);
                    }
                },
                error: function() {
                    $('#district_id').html('<option value="">Error loading districts</option>');
                }
            });
        } else {
            $('#district_id').html('<option value="">Select Division First</option>');
            $('#district_id').prop('disabled', true);
            $('#thana_id').html('<option value="">Select District First</option>');
            $('#thana_id').prop('disabled', true);
        }
    });
    
    $('#district_id').change(function() {
        var district_id = $(this).val();
        
        if (district_id) {
            $.ajax({
                url: '/unidia/public/api/get-thanas',
                type: 'GET',
                data: { district_id: district_id },
                dataType: 'json',
                success: function(data) {
                    $('#thana_id').html('<option value="">Select Thana/Upazila</option>');
                    
                    if (data.length > 0) {
                        $.each(data, function(key, thana) {
                            $('#thana_id').append('<option value="' + thana.id + '">' + thana.name + '</option>');
                        });
                        $('#thana_id').prop('disabled', false);
                    } else {
                        $('#thana_id').html('<option value="">No thanas found</option>');
                        $('#thana_id').prop('disabled', true);
                    }
                },
                error: function() {
                    $('#thana_id').html('<option value="">Error loading thanas</option>');
                }
            });
        } else {
            $('#thana_id').html('<option value="">Select District First</option>');
            $('#thana_id').prop('disabled', true);
        }
    });
    
    // Enter key triggers "Save & Register"
    $('#patientForm').on('keydown', function(e) {
        if (e.key === 'Enter' && !$(e.target).is('textarea')) {
            var target = $(e.target);
            if (!target.is('button')) {
                e.preventDefault();
                $('#btnSaveRegister').click();
            }
        }
    });
    
    // Register button
    $('#btnRegister').click(function(e) {
        // Let the form submit normally
    });
});
</script>

<?php unset($_SESSION['old_input']); ?>