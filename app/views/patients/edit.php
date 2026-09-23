<?php 
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient - <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .edit-container {
            max-width: 900px;
            margin: 30px auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #1e5799 0%, #2b7bc1 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
        }
        .required-field:after {
            content: " *";
            color: red;
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e5799 0%, #2b7bc1 100%);
            border: none;
        }
        .info-badge {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 8px 15px;
            border-radius: 10px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container edit-container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['errors']) && count($_SESSION['errors']) > 0): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-edit me-2"></i> Edit Patient Information</h5>
                <p class="mb-0 mt-2 small opacity-75">Update patient details below</p>
            </div>
            <div class="card-body p-4">
                <!-- Patient Info Summary -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="info-badge">
                            <i class="fas fa-id-card me-2"></i> 
                            <strong>Patient Code:</strong> <?php echo htmlspecialchars($patient['patient_code']); ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-badge">
                            <i class="fas fa-calendar-alt me-2"></i> 
                            <strong>Registered:</strong> <?php echo date('d M Y', strtotime($patient['registration_date'])); ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="<?php echo BASE_URL; ?>/patient/update" id="editForm">
                    <input type="hidden" name="id" value="<?php echo $patient['id']; ?>">
                    
                    <!-- Personal Information Section -->
                    <h6 class="mb-3 text-primary"><i class="fas fa-user-circle me-2"></i> Personal Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">First Name</label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Last Name</label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['email']); ?>">
                            <small class="text-muted">Optional</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required-field">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['phone']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($patient['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($patient['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($patient['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" 
                                   value="<?php echo $patient['date_of_birth']; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Blood Group</label>
                            <select name="blood_group" class="form-select">
                                <option value="">Select Blood Group</option>
                                <option value="A+" <?php echo ($patient['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo ($patient['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo ($patient['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo ($patient['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-</option>
                                <option value="O+" <?php echo ($patient['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo ($patient['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-</option>
                                <option value="AB+" <?php echo ($patient['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo ($patient['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Address Information Section -->
                    <h6 class="mb-3 mt-3 text-primary"><i class="fas fa-map-marker-alt me-2"></i> Address Information</h6>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">House/Street Address</label>
                            <textarea name="address" class="form-control" rows="2" 
                                      placeholder="House/Flat No, Road/Street, Village/Area"><?php echo htmlspecialchars($patient['address']); ?></textarea>
                            <small class="text-muted">Optional</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Division</label>
                            <select name="division_id" id="division_id" class="form-select">
                                <option value="">Select Division</option>
                                <?php foreach($divisions as $division): ?>
                                    <option value="<?php echo $division['id']; ?>" 
                                        <?php echo ($patient['division_id'] == $division['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($division['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">District</label>
                            <select name="district_id" id="district_id" class="form-select" <?php echo !$patient['division_id'] ? 'disabled' : ''; ?>>
                                <option value="">Select Division First</option>
                                <?php foreach($districts as $district): ?>
                                    <option value="<?php echo $district['id']; ?>" 
                                        <?php echo ($patient['district_id'] == $district['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($district['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Thana / Upazila</label>
                            <select name="thana_id" id="thana_id" class="form-select" <?php echo !$patient['district_id'] ? 'disabled' : ''; ?>>
                                <option value="">Select District First</option>
                                <?php foreach($thanas as $thana): ?>
                                    <option value="<?php echo $thana['id']; ?>" 
                                        <?php echo ($patient['thana_id'] == $thana['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($thana['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Emergency Contact Section -->
                    <h6 class="mb-3 mt-3 text-primary"><i class="fas fa-ambulance me-2"></i> Emergency Contact (Optional)</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Contact Name</label>
                            <input type="text" name="emergency_contact_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Contact Phone</label>
                            <input type="tel" name="emergency_contact_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Relation</label>
                            <input type="text" name="emergency_contact_relation" class="form-control" 
                                   value="<?php echo htmlspecialchars($patient['emergency_contact_relation'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <hr class="my-4">
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo BASE_URL; ?>/patient/delete?id=<?php echo $patient['id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this patient? This action cannot be undone.')">
                            <i class="fas fa-trash me-1"></i> Delete Patient
                        </a>
                        <div>
                            <a href="<?php echo BASE_URL; ?>/patient/list" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Patient
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    
    $(document).ready(function() {
        // When division changes, load districts
        $('#division_id').change(function() {
            var division_id = $(this).val();
            
            if (division_id) {
                $.ajax({
                    url: BASE_URL + '/api/get-districts',
                    type: 'GET',
                    data: { division_id: division_id },
                    dataType: 'json',
                    success: function(data) {
                        $('#district_id').html('<option value="">Select District</option>');
                        $('#thana_id').html('<option value="">Select District First</option>');
                        $('#thana_id').prop('disabled', true);
                        
                        if (data.length > 0) {
                            $.each(data, function(key, district) {
                                var selected = (<?php echo $patient['district_id']; ?> == district.id) ? 'selected' : '';
                                $('#district_id').append('<option value="' + district.id + '" ' + selected + '>' + district.name + '</option>');
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
        
        // When district changes, load thanas
        $('#district_id').change(function() {
            var district_id = $(this).val();
            
            if (district_id) {
                $.ajax({
                    url: BASE_URL + '/api/get-thanas',
                    type: 'GET',
                    data: { district_id: district_id },
                    dataType: 'json',
                    success: function(data) {
                        $('#thana_id').html('<option value="">Select Thana/Upazila</option>');
                        
                        if (data.length > 0) {
                            $.each(data, function(key, thana) {
                                var selected = (<?php echo $patient['thana_id']; ?> == thana.id) ? 'selected' : '';
                                $('#thana_id').append('<option value="' + thana.id + '" ' + selected + '>' + thana.name + '</option>');
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
        
        // Form validation
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const firstName = document.querySelector('[name="first_name"]').value.trim();
            const lastName = document.querySelector('[name="last_name"]').value.trim();
            const phone = document.querySelector('[name="phone"]').value.trim();
            
            if (!firstName || !lastName) {
                e.preventDefault();
                alert('First name and last name are required');
                return false;
            }
            
            if (!phone) {
                e.preventDefault();
                alert('Phone number is required');
                return false;
            }
            
            // Phone number validation (Bangladesh format)
            const phoneRegex = /^(01[3-9]\d{8})|(\+8801[3-9]\d{8})$/;
            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid phone number (e.g., 01XXXXXXXXX or +8801XXXXXXXXX)');
                return false;
            }
        });
    });
    </script>
</body>
</html>