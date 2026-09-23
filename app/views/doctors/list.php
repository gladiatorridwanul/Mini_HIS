<?php
// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Get database connection
$db = Database::getInstance()->getConnection();

// Build search condition
$searchCondition = "";
if (!empty($search)) {
    $search = $db->real_escape_string($search);
    $searchCondition = " AND (u.first_name LIKE '%$search%' 
                           OR u.last_name LIKE '%$search%' 
                           OR d.bmdc_number LIKE '%$search%' 
                           OR d.specialization LIKE '%$search%')";
}

// Get total doctors count - ONLY ACTIVE DOCTORS (both doctor and user status must be active)
$countQuery = "SELECT COUNT(*) as total 
               FROM doctors d 
               JOIN users u ON d.user_id = u.id 
               WHERE d.status = 'active' AND u.status = 'active' $searchCondition";
$countResult = $db->query($countQuery);
$totalDoctors = $countResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($totalDoctors / $limit);

// Get doctors with pagination - ONLY ACTIVE DOCTORS (both doctor and user status must be active)
$query = "SELECT d.*, u.first_name, u.last_name, u.title, u.email, u.phone, u.employee_id, u.status as user_status,
                 dep.name as department_name
          FROM doctors d 
          JOIN users u ON d.user_id = u.id 
          LEFT JOIN departments dep ON d.department_id = dep.id
          WHERE d.status = 'active' AND u.status = 'active' $searchCondition
          ORDER BY u.first_name ASC 
          LIMIT $limit OFFSET $offset";
$result = $db->query($query);

$doctors = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Get services count for this doctor
        $servicesQuery = $db->query("SELECT COUNT(*) as count FROM doctor_services WHERE doctor_id = {$row['id']} AND status = 'active'");
        $servicesCount = $servicesQuery ? $servicesQuery->fetch_assoc()['count'] : 0;
        $row['services'] = [];
        $row['services_count'] = $servicesCount;
        $doctors[] = $row;
    }
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-user-md me-2"></i>Doctors List</h5>
        <a href="/unidia/public/doctor/create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus me-1"></i> Add New Doctor
        </a>
    </div>
    <div class="card-body">
        <!-- Search -->
        <div class="row mb-3">
            <div class="col-md-4">
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, BMDC, specialization..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary ms-2">Search</button>
                    <?php if($search): ?>
                        <a href="/unidia/public/doctor/list" class="btn btn-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="col-md-8 text-end">
                <span class="text-muted">
                    Showing <?php echo count($doctors); ?> of <?php echo $totalDoctors; ?> active doctors
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
                        <th width="5%">#</th>
                        <th width="20%">Doctor Name</th>
                        <th width="12%">BMDC No.</th>
                        <th width="15%">Department</th>
                        <th width="15%">Specialization</th>
                        <th width="10%">Consultation Fee</th>
                        <th width="10%">Services</th>
                        <th width="8%">Status</th>
                        <th width="8%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($doctors)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-user-md fa-2x d-block mb-2"></i>
                                No active doctors found
                                <?php if($search): ?>
                                    <br><small>Try adjusting your search criteria</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $counter = $offset + 1; ?>
                        <?php foreach($doctors as $doc): ?>
                        <tr>
                            <td class="text-center"><?php echo $counter++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars(($doc['title'] ?? '') . ' ' . $doc['first_name'] . ' ' . $doc['last_name']); ?></strong>
                                <br>
                                <small class="text-muted">ID: <?php echo htmlspecialchars($doc['employee_id'] ?? ''); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($doc['bmdc_number'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($doc['department_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($doc['specialization'] ?? ''); ?></td>
                            <td class="text-end">৳ <?php echo number_format($doc['consultation_fee'] ?? 0, 2); ?></td>
                            <td class="text-center">
                                <?php 
                                $serviceCount = $doc['services_count'] ?? 0;
                                if($serviceCount > 0) {
                                    echo '<span class="badge bg-info">' . $serviceCount . ' Services</span>';
                                } else {
                                    echo '<span class="badge bg-secondary">No Services</span>';
                                }
                                ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success">Active</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="/unidia/public/doctor/view/<?php echo $doc['id']; ?>" class="btn btn-info" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/unidia/public/doctor/edit/<?php echo $doc['id']; ?>" class="btn btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="/unidia/public/doctor/delete/<?php echo $doc['id']; ?>" class="btn btn-danger" title="Delete" 
                                       onclick="return confirm('Are you sure you want to delete this doctor? This action cannot be undone.')">
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
                <nav aria-label="Doctor pagination">
                    <ul class="pagination justify-content-center">
                        <!-- Previous Page -->
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
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
                            echo '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '">1</a></li>';
                            if($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        // Page numbers
                        for($i = $startPage; $i <= $endPage; $i++) {
                            $active = ($i == $page) ? 'active' : '';
                            echo '<li class="page-item ' . $active . '">';
                            echo '<a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '">' . $i . '</a>';
                            echo '</li>';
                        }
                        
                        // Show last page if not in range
                        if($endPage < $totalPages) {
                            if($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '">' . $totalPages . '</a></li>';
                        }
                        ?>
                        
                        <!-- Next Page -->
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
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
                    (Total <?php echo $totalDoctors; ?> active doctors)
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