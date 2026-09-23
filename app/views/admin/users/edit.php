<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - UniDia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
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
                        <li class="nav-item"><a class="nav-link text-danger" href="/unidia/public/logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-user-edit me-2"></i> Edit User</h1>
                    <a href="/unidia/public/admin/users" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i> Back to Users</a>
                </div>

                <div class="card">
                    <div class="card-header">
                        Editing: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/unidia/public/admin/users/update/<?php echo $user['id']; ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password <small class="text-muted">(Leave blank to keep current)</small></label>
                                    <input type="password" name="password" class="form-control" placeholder="Enter new password to change">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-select">
                                        <option value="">Select</option>
                                        <option value="male" <?php echo ($user['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                        <option value="female" <?php echo ($user['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                                        <option value="other" <?php echo ($user['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Role <span class="text-danger">*</span></label>
                                    <select name="role_id" id="role_id" class="form-select" required>
                                        <?php foreach($roles as $role): ?>
                                            <option value="<?php echo $role['id']; ?>" <?php echo $user['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($role['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="suspended" <?php echo $user['status'] == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Address</label>
                                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">State</label>
                                    <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($user['state'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Country</label>
                                    <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($user['country'] ?? 'Bangladesh'); ?>">
                                </div>
                            </div>

                            <!-- Doctor Fields (if user is doctor) -->
                            <?php if ($user['role_id'] == 3): ?>
                                <hr>
                                <h5><i class="fas fa-user-md me-2"></i> Doctor Information</h5>
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Department</label>
                                        <select name="department_id" class="form-select">
                                            <?php foreach($departments as $dept): ?>
                                                <option value="<?php echo $dept['id']; ?>" <?php echo ($doctorInfo['department_id'] ?? '') == $dept['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($dept['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Specialization</label>
                                        <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($doctorInfo['specialization'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Qualification</label>
                                        <input type="text" name="qualification" class="form-control" value="<?php echo htmlspecialchars($doctorInfo['qualification'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">License Number</label>
                                        <input type="text" name="license_number" class="form-control" value="<?php echo htmlspecialchars($doctorInfo['license_number'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Consultation Fee ($)</label>
                                        <input type="number" step="0.01" name="consultation_fee" class="form-control" value="<?php echo $doctorInfo['consultation_fee'] ?? 0; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Commission Percentage (%)</label>
                                        <input type="number" step="0.01" name="commission_percentage" class="form-control" value="<?php echo $doctorInfo['commission_percentage'] ?? 0; ?>">
                                    </div>
                                </div>
                            <?php endif; ?>

                            <hr>
                            <div class="text-end">
                                <a href="/unidia/public/admin/users" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>