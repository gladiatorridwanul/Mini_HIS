<?php
// app/views/patient/index.php - Alternative Patient List View
$title = 'Patient List';
ob_start();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-procedures me-2"></i>Patient Management</h5>
        <div>
            <a href="/unidia/public/patient/register" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus"></i> Register New Patient
            </a>
        </div>
    </div>
    <div class="card-body">
        <!-- Statistics -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body p-3">
                        <h6 class="mb-0">Total Patients</h6>
                        <h3 class="mb-0"><?php echo number_format($totalPatients ?? count($patients)); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body p-3">
                        <h6 class="mb-0">Active</h6>
                        <h3 class="mb-0"><?php echo number_format($totalPatients ?? count($patients)); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body p-3">
                        <h6 class="mb-0">Today's Appointments</h6>
                        <h3 class="mb-0">0</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body p-3">
                        <h6 class="mb-0">ID Cards Printed</h6>
                        <h3 class="mb-0">0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search -->
        <div class="row mb-3">
            <div class="col-md-8">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by name, phone, patient code, or email..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    <button class="btn btn-primary" onclick="applyFilters()">Search</button>
                    <button class="btn btn-secondary" onclick="resetFilters()">Reset</button>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-secondary p-2">Total: <?php echo number_format($totalPatients ?? count($patients)); ?></span>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['errors']) && count($_SESSION['errors']) > 0): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['errors']); ?>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-striped" id="patientTable">
                <thead class="table-dark">
                    <tr>
                        <th width="50">#</th>
                        <th width="120">Patient Code</th>
                        <th>Full Name</th>
                        <th width="130">Phone</th>
                        <th>Email</th>
                        <th width="90">Blood Group</th>
                        <th width="80">Gender</th>
                        <th width="100">Status</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($patients) && count($patients) > 0): ?>
                        <?php $counter = (($currentPage ?? 1) - 1) * 20 + 1; ?>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo $counter++; ?></td>
                                <td>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($patient['patient_code']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($patient['full_name'] ?: $patient['first_name'] . ' ' . $patient['last_name']); ?></strong>
                                </td>
                                <td>
                                    <a href="tel:<?php echo $patient['phone']; ?>" class="text-decoration-none text-primary">
                                        <i class="fas fa-phone-alt me-1"></i> <?php echo $patient['phone']; ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($patient['email']): ?>
                                        <a href="mailto:<?php echo $patient['email']; ?>" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars(substr($patient['email'], 0, 25)); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($patient['blood_group']): ?>
                                        <span class="badge bg-danger"><?php echo $patient['blood_group']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $patient['gender'] == 'male' ? 'info' : ($patient['gender'] == 'female' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($patient['gender'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $patient['status'] == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($patient['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="/unidia/public/patient/id-card?id=<?php echo $patient['id']; ?>" 
                                           class="btn btn-info" title="ID Card">
                                            <i class="fas fa-id-card"></i>
                                        </a>
                                        <a href="/unidia/public/patient/view?id=<?php echo $patient['id']; ?>" 
                                           class="btn btn-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/unidia/public/patient/edit?id=<?php echo $patient['id']; ?>" 
                                           class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <!-- ========== ADD THIS BUTTON ========== -->
                                        <a href="<?php echo BASE_URL; ?>/prescriptions/create?patient_id=<?php echo $patient['id']; ?>" 
                                           class="btn btn-success" title="Create Prescription">
                                            <i class="fas fa-prescription"></i>
                                        </a>
                                        <!-- ===================================== -->
                                        <a href="/unidia/public/appointments/book?patient_id=<?php echo $patient['id']; ?>" 
                                           class="btn btn-success" title="Book Appointment">
                                            <i class="fas fa-calendar-plus"></i>
                                        </a>
                                        <a href="javascript:void(0)" 
                                           onclick="deletePatient(<?php echo $patient['id']; ?>)" 
                                           class="btn btn-danger" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="fas fa-users fa-3x d-block mb-2"></i>
                                    <h6>No Patients Found</h6>
                                    <?php if (!empty($search)): ?>
                                        <p>No results matching "<strong><?php echo htmlspecialchars($search); ?></strong>"</p>
                                        <a href="/unidia/public/patient/list" class="btn btn-secondary btn-sm">Clear Search</a>
                                    <?php else: ?>
                                        <p>Start by registering your first patient</p>
                                        <a href="/unidia/public/patient/register" class="btn btn-primary btn-sm">
                                            <i class="fas fa-user-plus"></i> Register Patient
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-3">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=1&search=<?php echo urlencode($search ?? ''); ?>">
                            <i class="fas fa-angle-double-left"></i>
                        </a>
                    </li>
                    <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search ?? ''); ?>">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    </li>
                    
                    <?php 
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search ?? ''); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search ?? ''); ?>">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    </li>
                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search ?? ''); ?>">
                            <i class="fas fa-angle-double-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let searchTimeout;

// Debounce search for better performance
$('#searchInput').on('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        applyFilters();
    }, 500);
});

// Enter key for search
$('#searchInput').on('keypress', function(e) {
    if (e.which === 13) {
        clearTimeout(searchTimeout);
        applyFilters();
    }
});

function applyFilters() {
    const search = $('#searchInput').val().trim();
    let url = '/unidia/public/patient/list?';
    if (search) url += 'search=' + encodeURIComponent(search);
    window.location.href = url;
}

function resetFilters() {
    $('#searchInput').val('');
    window.location.href = '/unidia/public/patient/list';
}

function deletePatient(id) {
    Swal.fire({
        title: 'Delete Patient?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '/unidia/public/patient/delete?id=' + id;
        }
    });
}

// Set current search value on load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const search = urlParams.get('search');
    if (search) {
        $('#searchInput').val(search);
    }
});
</script>

<?php
$content = ob_get_clean();
include BASE_PATH . '/app/views/layouts/main.php';
?>