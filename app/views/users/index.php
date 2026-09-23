<?php
// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total users count
$totalUsers = isset($totalUsers) ? $totalUsers : 0;
$totalPages = ceil($totalUsers / $limit);
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-users me-2"></i>System Users</h5>
        <a href="/unidia/public/admin/users/create" class="btn btn-primary btn-sm">
            <i class="fas fa-user-plus me-1"></i> Add New User
        </a>
    </div>
    <div class="card-body">
        <!-- Search and Filter -->
        <div class="row mb-3">
            <div class="col-md-4">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, employee ID..." 
                           value="<?php echo isset($search) ? htmlspecialchars($search) : ''; ?>">
                    <button type="submit" class="btn btn-primary ms-2">Search</button>
                    <?php if(isset($search) && !empty($search)): ?>
                        <a href="/unidia/public/admin/users" class="btn btn-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-8 text-end">
                <span class="text-muted">
                    Showing <?php echo isset($users) ? count($users) : 0; ?> of <?php echo $totalUsers; ?> users
                </span>
            </div>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th width="5%">ID</th>
                        <th width="12%">Employee ID</th>
                        <th width="18%">Name</th>
                        <th width="20%">Email</th>
                        <th width="15%">Role</th>
                        <th width="10%">Status</th>
                        <th width="12%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-2x d-block mb-2"></i>
                                No users found
                                <?php if(isset($search) && !empty($search)): ?>
                                    <br><small>Try adjusting your search criteria</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $counter = $offset + 1; ?>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td class="text-center"><?php echo $counter++; ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($user['employee_id'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-primary"><?php echo htmlspecialchars($user['role_name']); ?></span>
                            </td>
                            <td>
                                <?php if($user['status'] == 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php elseif($user['status'] == 'inactive'): ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><?php echo ucfirst($user['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="/unidia/public/admin/users/edit/<?php echo $user['id']; ?>" class="btn btn-warning" title="Edit User">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="/unidia/public/admin/users/delete/<?php echo $user['id']; ?>" class="btn btn-danger" title="Delete User" 
                                       onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="row mt-3">
            <div class="col-12">
                <nav aria-label="User pagination">
                    <ul class="pagination justify-content-center">
                        <!-- Previous Page -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($search) && !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <!-- Page Numbers -->
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        // Show first page if not in range
                        if($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1' . (isset($search) && !empty($search) ? '&search=' . urlencode($search) : '') . '">1</a></li>';
                            if($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        // Page numbers
                        for($i = $startPage; $i <= $endPage; $i++) {
                            $active = ($i == $page) ? 'active' : '';
                            echo '<li class="page-item ' . $active . '">';
                            echo '<a class="page-link" href="?page=' . $i . (isset($search) && !empty($search) ? '&search=' . urlencode($search) : '') . '">' . $i . '</a>';
                            echo '</li>';
                        }
                        
                        // Show last page if not in range
                        if($endPage < $totalPages) {
                            if($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . (isset($search) && !empty($search) ? '&search=' . urlencode($search) : '') . '">' . $totalPages . '</a></li>';
                        }
                        ?>
                        
                        <!-- Next Page -->
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($search) && !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                               aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Page Info -->
        <div class="row mt-2">
            <div class="col-12 text-center">
                <small class="text-muted">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?> 
                    (Total <?php echo $totalUsers; ?> users)
                </small>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-group .btn {
        padding: 4px 8px;
        font-size: 12px;
        line-height: 1.5;
    }
    .table td {
        vertical-align: middle;
    }
    .pagination .page-link {
        padding: 6px 12px;
        font-size: 14px;
    }
    .pagination .active .page-link {
        background-color: #10b981;
        border-color: #10b981;
        color: white;
    }
    .pagination .page-link:hover {
        background-color: #f1f5f9;
    }
    .pagination .active .page-link:hover {
        background-color: #059669;
        border-color: #059669;
        color: white;
    }
</style>