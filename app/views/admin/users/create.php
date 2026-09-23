<?php
// Data from controller: $roles, $departments, $permissionsByModule
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User - UniDia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 5px;
        }
        .permission-card {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse" style="min-height: 100vh;">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">UniDia</h4>
                        <small class="text-muted">Hospital Management System</small>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link text-white" href="/unidia/public/admin/dashboard"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link active text-white bg-primary" href="/unidia/public/admin/users"><i class="fas fa-users me-2"></i> Users</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="/unidia/public/patient/list"><i class="fas fa-procedures me-2"></i> Patients</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="/unidia/public/doctor/list"><i class="fas fa-user-md me-2"></i> Doctors</a></li>
                        <li class="nav-item mt-4"><a class="nav-link text-danger" href="/unidia/public/logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-user-plus me-2"></i> Create New User</h1>
                    <a href="/unidia/public/admin/users" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Users</a>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="userTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#basicInfo">Basic Information</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#doctorInfo" id="doctorTab" style="display:none;">Doctor Details</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#permissionsTab">Permissions</button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/unidia/public/admin/users/store" id="userForm">
                            <div class="tab-content">
                                <!-- Tab 1: Basic Information -->
                                <div class="tab-pane fade show active" id="basicInfo">
                                    <div class="form-section">
                                        <h5><i class="fas fa-user-circle me-2"></i> Personal Information</h5>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label required-field">First Name</label>
                                                <input type="text" name="first_name" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label required-field">Last Name</label>
                                                <input type="text" name="last_name" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label required-field">Email</label>
                                                <input type="email" name="email" class="form-control" required>
                                                <small class="text-muted">This will be used for login</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label required-field">Password</label>
                                                <input type="password" name="password" class="form-control" required>
                                                <small class="text-muted">Minimum 6 characters</small>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Phone</label>
                                                <input type="tel" name="phone" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Gender</label>
                                                <select name="gender" class="form-select">
                                                    <option value="">Select</option>
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
                                                <label class="form-label required-field">Role</label>
                                                <select name="role_id" id="role_id" class="form-select" required onchange="toggleDoctorFields()">
                                                    <option value="">Select Role</option>
                                                    <?php foreach($roles as $role): ?>
                                                        <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea name="address" class="form-control" rows="2"></textarea>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">City</label>
                                                <input type="text" name="city" class="form-control">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">State</label>
                                                <input type="text" name="state" class="form-control">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Country</label>
                                                <input type="text" name="country" class="form-control" value="Bangladesh">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab 2: Doctor Information (Hidden by default) -->
                                <div class="tab-pane fade" id="doctorInfo">
                                    <div class="form-section">
                                        <h5><i class="fas fa-user-md me-2"></i> Doctor Professional Information</h5>
                                        <hr>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Department</label>
                                                <select name="department_id" class="form-select">
                                                    <option value="">Select Department</option>
                                                    <?php foreach($departments as $dept): ?>
                                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Specialization</label>
                                                <input type="text" name="specialization" class="form-control" placeholder="e.g., Cardiologist, Neurologist">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Qualification</label>
                                                <input type="text" name="qualification" class="form-control" placeholder="e.g., MBBS, MD, PhD">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">License Number</label>
                                                <input type="text" name="license_number" class="form-control" placeholder="Medical license number">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Consultation Fee ($)</label>
                                                <input type="number" step="0.01" name="consultation_fee" class="form-control" value="0">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Commission Percentage (%)</label>
                                                <input type="number" step="0.01" name="commission_percentage" class="form-control" value="0">
                                                <small class="text-muted">Percentage of revenue for this doctor</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab 3: Permissions (Custom access control) -->
                                <div class="tab-pane fade" id="permissionsTab">
                                    <div class="form-section">
                                        <h5><i class="fas fa-lock me-2"></i> Custom Permissions</h5>
                                        <hr>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Note:</strong> Leave unchecked to use default role-based permissions. 
                                            Check specific permissions to grant additional access beyond the user's role.
                                        </div>
                                        
                                        <?php if (isset($permissionsByModule) && !empty($permissionsByModule)): ?>
                                            <div class="row">
                                                <?php foreach($permissionsByModule as $module => $permissions): ?>
                                                <div class="col-md-4 mb-3">
                                                    <div class="card">
                                                        <div class="card-header bg-light">
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input select-module-all" 
                                                                       data-module="<?php echo $module; ?>" id="select_all_<?php echo $module; ?>">
                                                                <label class="form-check-label fw-bold" for="select_all_<?php echo $module; ?>">
                                                                    <i class="fas fa-folder-open me-1"></i> <?php echo ucfirst(str_replace('_', ' ', $module)); ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <div class="card-body permission-card">
                                                            <?php foreach($permissions as $permission): ?>
                                                            <div class="form-check mb-1">
                                                                <input type="checkbox" name="custom_permissions[]" 
                                                                       value="<?php echo $permission['slug']; ?>" 
                                                                       class="form-check-input permission-checkbox module-<?php echo $module; ?>"
                                                                       id="perm_<?php echo $permission['slug']; ?>">
                                                                <label class="form-check-label small" for="perm_<?php echo $permission['slug']; ?>">
                                                                    <?php echo htmlspecialchars($permission['name']); ?>
                                                                </label>
                                                            </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                No permissions found. Please run the database setup script first.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-section">
                                        <h5><i class="fas fa-info-circle me-2"></i> Permission Summary</h5>
                                        <hr>
                                        <div id="permissionSummary" class="alert alert-secondary">
                                            <span id="selectedCount">0</span> custom permissions selected
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <div class="text-end">
                                <a href="/unidia/public/admin/users" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle doctor fields based on role selection
        function toggleDoctorFields() {
            var roleId = document.getElementById('role_id').value;
            var doctorTab = document.getElementById('doctorTab');
            var doctorTabPane = document.getElementById('doctorInfo');
            
            if (roleId == '3') { // Doctor role ID
                doctorTab.style.display = 'block';
                // Activate first tab if doctor tab is shown
            } else {
                doctorTab.style.display = 'none';
                if (doctorTabPane.classList.contains('active')) {
                    document.querySelector('#userTabs button[data-bs-target="#basicInfo"]').click();
                }
            }
        }
        
        // Update permission summary
        function updatePermissionSummary() {
            var selected = document.querySelectorAll('input[name="custom_permissions[]"]:checked').length;
            document.getElementById('selectedCount').innerText = selected;
        }
        
        // Select/Deselect all permissions for a module
        document.querySelectorAll('.select-module-all').forEach(function(selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                var module = this.getAttribute('data-module');
                var checkboxes = document.querySelectorAll('.module-' + module);
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updatePermissionSummary();
            });
        });
        
        // Update select-all checkboxes when individual permissions change
        document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updatePermissionSummary();
                
                // Update select-all checkbox for the module
                var module = this.classList[1] ? this.classList[1].replace('module-', '') : '';
                var moduleCheckboxes = document.querySelectorAll('.module-' + module);
                var allChecked = Array.from(moduleCheckboxes).every(cb => cb.checked);
                var selectAllCheckbox = document.getElementById('select_all_' + module);
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                }
            });
        });
        
        // Initial update
        updatePermissionSummary();
        
        // Form validation before submit
        document.getElementById('userForm').addEventListener('submit', function(e) {
            var roleId = document.getElementById('role_id').value;
            if (!roleId) {
                e.preventDefault();
                alert('Please select a role for the user');
                return false;
            }
            
            var firstName = document.querySelector('input[name="first_name"]').value;
            var lastName = document.querySelector('input[name="last_name"]').value;
            var email = document.querySelector('input[name="email"]').value;
            var password = document.querySelector('input[name="password"]').value;
            
            if (!firstName || !lastName || !email || !password) {
                e.preventDefault();
                alert('Please fill all required fields (First Name, Last Name, Email, Password)');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>