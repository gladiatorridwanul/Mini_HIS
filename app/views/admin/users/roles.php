<?php
// Data from controller: $roles
$permissionsByModule = $this->permissionHelper->getPermissionsByModule();
$rolePermissions = [];
foreach($roles as $role) {
    $rolePermissions[$role['id']] = $this->permissionHelper->getRolePermissions($role['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles & Permissions - UniDia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block bg-dark sidebar" style="min-height: 100vh;">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">UniDia</h4>
                    </div>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a class="nav-link text-white" href="/unidia/public/admin/dashboard"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link active text-white bg-primary" href="/unidia/public/admin/users"><i class="fas fa-users me-2"></i> Users</a></li>
                        <li class="nav-item"><a class="nav-link text-white" href="/unidia/public/patient/list"><i class="fas fa-procedures me-2"></i> Patients</a></li>
                        <li class="nav-item mt-4"><a class="nav-link text-danger" href="/unidia/public/logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-lock me-2"></i> Roles & Permissions</h1>
                    <a href="/unidia/public/admin/users" class="btn btn-secondary">Back to Users</a>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>

                <div class="row">
                    <?php foreach($roles as $role): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-<?php echo $role['id'] == 1 ? 'danger' : ($role['id'] == 2 ? 'warning' : 'primary'); ?> text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    <?php echo htmlspecialchars($role['name']); ?>
                                    <span class="badge bg-light text-dark float-end"><?php echo $role['user_count'] ?? 0; ?> Users</span>
                                </h5>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <form method="POST" action="/unidia/public/admin/users/roles/update/<?php echo $role['id']; ?>">
                                    <?php foreach($permissionsByModule as $module => $permissions): ?>
                                        <div class="mb-3">
                                            <strong class="text-primary"><?php echo ucfirst($module); ?></strong>
                                            <hr class="my-1">
                                            <?php foreach($permissions as $permission): ?>
                                                <div class="form-check">
                                                    <input type="checkbox" name="permissions[]" value="<?php echo $permission['slug']; ?>" 
                                                           class="form-check-input" id="role_<?php echo $role['id']; ?>_perm_<?php echo $permission['slug']; ?>"
                                                           <?php echo in_array($permission['slug'], $rolePermissions[$role['id']] ?? []) ? 'checked' : ''; ?>
                                                           <?php echo $role['id'] == 1 ? 'disabled' : ''; ?>>
                                                    <label class="form-check-label small" for="role_<?php echo $role['id']; ?>_perm_<?php echo $permission['slug']; ?>">
                                                        <?php echo htmlspecialchars($permission['name']); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if($role['id'] != 1): ?>
                                        <button type="submit" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-save me-1"></i> Save Permissions
                                        </button>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-info-circle"></i> Super Admin has all permissions by default
                                        </div>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select/Deselect all for each module
        document.querySelectorAll('strong.text-primary').forEach(header => {
            let moduleDiv = header.closest('.mb-3');
            let checkboxes = moduleDiv.querySelectorAll('input[type="checkbox"]');
            let selectAllId = 'select_all_' + Math.random();
            
            let selectAllHtml = `<div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" id="${selectAllId}" onchange="toggleModule(this, '${selectAllId}')">
                <label class="form-check-label fw-bold">Select All</label>
            </div>`;
            moduleDiv.insertAdjacentHTML('afterbegin', selectAllHtml);
            
            window[`toggleModule_${selectAllId}`] = function(checkbox, id) {
                moduleDiv.querySelectorAll('input[type="checkbox"]:not([disabled])').forEach(cb => {
                    if(cb.id !== id) cb.checked = checkbox.checked;
                });
            };
        });
    </script>
</body>
</html>