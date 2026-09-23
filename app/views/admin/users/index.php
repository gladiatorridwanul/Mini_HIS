<?php
// Data passed from controller: $users, $roles, $totalUsers, $currentPage, $totalPages, $search, $selectedRole
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - UniDia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/unidia/public/assets/css/style.css">
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
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/unidia/public/admin/dashboard">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white bg-primary" href="/unidia/public/admin/users">
                                <i class="fas fa-users me-2"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/unidia/public/patient/list">
                                <i class="fas fa-procedures me-2"></i> Patients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/unidia/public/doctor/list">
                                <i class="fas fa-user-md me-2"></i> Doctors
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/unidia/public/reception/appointments">
                                <i class="fas fa-calendar-check me-2"></i> Appointments
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/unidia/public/bills">
                                <i class="fas fa-file-invoice-dollar me-2"></i> Billing
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/unidia/public/pharmacy/pos">
                                <i class="fas fa-prescription-bottle me-2"></i> Pharmacy
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/unidia/public/lab/orders">
                                <i class="fas fa-microscope me-2"></i> Laboratory
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/unidia/public/inventory">
                                <i class="fas fa-boxes me-2"></i> Inventory
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/unidia/public/admin/audit-logs">
                                <i class="fas fa-history me-2"></i> Audit Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/unidia/public/admin/settings">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="/unidia/public/logout">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-users me-2"></i> User Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="/unidia/public/admin/users/create" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Add New User
                        </a>
                        <a href="/unidia/public/admin/users/attendance" class="btn btn-sm btn-info mx-2 text-white">
                            <i class="fas fa-clock me-1"></i> Attendance
                        </a>
                        <a href="/unidia/public/admin/users/payroll" class="btn btn-sm btn-success">
                            <i class="fas fa-money-bill me-1"></i> Payroll
                        </a>
                        <a href="/unidia/public/admin/users/roles" class="btn btn-sm btn-secondary mx-2">
                            <i class="fas fa-lock me-1"></i> Roles
                        </a>
                    </div>
                </div>

                <!-- Alert Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-0">Total Users</h6>
                                        <h2 class="mb-0"><?php echo $totalUsers ?? 0; ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-0">Active Users</h6>
                                        <h2 class="mb-0">
                                            <?php 
                                                $activeCount = 0;
                                                foreach($users as $u) if($u['status'] == 'active') $activeCount++;
                                                echo $activeCount;
                                            ?>
                                        </h2>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-0">Roles</h6>
                                        <h2 class="mb-0"><?php echo count($roles ?? []); ?></h2>
                                    </div>
                                    <i class="fas fa-tags fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-0">Today Present</h6>
                                        <h2 class="mb-0">
                                            <?php
                                                $presentCount = 0;
                                                foreach($users as $u) {
                                                    if(isset($u['attended_today']) && $u['attended_today'] > 0) $presentCount++;
                                                }
                                                echo $presentCount;
                                            ?>
                                        </h2>
                                    </div>
                                    <i class="fas fa-calendar-check fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter and Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="/unidia/public/admin/users" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" placeholder="Search by name, email, or employee ID..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="role" class="form-select">
                                    <option value="">All Roles</option>
                                    <?php foreach($roles as $role): ?>
                                        <option value="<?php echo $role['id']; ?>" <?php echo ($selectedRole ?? '') == $role['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search me-1"></i> Filter
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="/unidia/public/admin/users" class="btn btn-secondary w-100">
                                    <i class="fas fa-undo me-1"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-table me-2"></i> System Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th width="150">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">
                                                <i class="fas fa-info-circle me-2"></i> No users found
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($user['employee_id'] ?? 'N/A'); ?></span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $user['role_slug'] == 'super_admin' ? 'danger' : 
                                                            ($user['role_slug'] == 'admin' ? 'warning' : 
                                                            ($user['role_slug'] == 'doctor' ? 'info' : 'secondary')); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($user['role_name'] ?? 'N/A'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($user['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                                </td>
                                                <td>
                                                    <a href="/unidia/public/admin/users/edit/<?php echo $user['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="/unidia/public/admin/users/delete/<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php if ($user['role_slug'] == 'doctor'): ?>
                                                        <a href="/unidia/public/doctor/schedule/<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="Schedule">
                                                            <i class="fas fa-calendar-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if (($totalPages ?? 0) > 1): ?>
                            <nav aria-label="Page navigation" class="mt-3">
                                <ul class="pagination justify-content-center">
                                    <?php if ($currentPage > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&role=<?php echo $selectedRole ?? ''; ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>&role=<?php echo $selectedRole ?? ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($currentPage < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>&role=<?php echo $selectedRole ?? ''; ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt me-2"></i> Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="/unidia/public/admin/users/create" class="btn btn-primary">
                                        <i class="fas fa-user-plus me-2"></i> Create New User
                                    </a>
                                    <a href="/unidia/public/admin/users/attendance" class="btn btn-info text-white">
                                        <i class="fas fa-clock me-2"></i> Mark Today's Attendance
                                    </a>
                                    <a href="/unidia/public/admin/users/roles" class="btn btn-secondary">
                                        <i class="fas fa-key me-2"></i> Manage Roles & Permissions
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-chart-line me-2"></i> User Statistics</h5>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <?php 
                                        $roleCounts = [];
                                        foreach($users as $u) {
                                            $roleName = $u['role_name'] ?? 'Unknown';
                                            if(!isset($roleCounts[$roleName])) $roleCounts[$roleName] = 0;
                                            $roleCounts[$roleName]++;
                                        }
                                    ?>
                                    <?php foreach($roleCounts as $roleName => $count): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($roleName); ?></td>
                                            <td><div class="progress"><div class="progress-bar" style="width: <?php echo ($count / max(1, count($users))) * 100; ?>%"><?php echo $count; ?></div></div></td>
                                            <td width="50"><?php echo $count; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/unidia/public/assets/js/app.js"></script>
</body>
</html>