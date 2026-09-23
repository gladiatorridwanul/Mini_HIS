<?php
// /app/views/vaccines/index.php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccination Records - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        .card-custom { background: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header-custom { background: white; border-bottom: 2px solid #3b82f6; padding: 10px 15px; font-weight: 600; font-size: 14px; }
        .badge-status { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .btn-action { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px; }
        .btn-action:hover { background: #e2e8f0; }
        .btn-action.primary { background: #dbeafe; color: #2563eb; border-color: #bfdbfe; }
        .btn-action.primary:hover { background: #2563eb; color: white; }
        .btn-action.danger { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
        .btn-action.danger:hover { background: #dc2626; color: white; }
        .btn-action.success { background: #d1fae5; color: #059669; border-color: #a7f3d0; }
        .btn-action.success:hover { background: #059669; color: white; }
        .vaccine-image { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #e5e7eb; }
        .status-badge { padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; }
        .status-given { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-syringe" style="color: #3b82f6;"></i> Vaccination Records</h5>
                <p class="text-muted" style="font-size: 11px;">
                    <?php echo htmlspecialchars($patient['full_name'] ?? $patient['first_name'] . ' ' . $patient['last_name']); ?> 
                    (ID: <?php echo htmlspecialchars($patient['patient_code']); ?>)
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/vaccines/create?patient_id=<?php echo $patientId; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Add Vaccine
                </a>
                <a href="<?php echo BASE_URL; ?>/vaccines/print-card/<?php echo $patientId; ?>" class="btn btn-info btn-sm" target="_blank">
                    <i class="fas fa-print"></i> Print Card
                </a>
                <a href="<?php echo BASE_URL; ?>/patient/list" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['errors']) && count($_SESSION['errors']) > 0): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
                <?php unset($_SESSION['errors']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Main Card -->
        <div class="card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="fas fa-list"></i> Vaccination Records</span>
                <span class="text-muted" style="font-size: 12px;">Total: <?php echo $totalVaccines; ?> records</span>
            </div>
            <div class="card-body">
                <!-- Search -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form method="GET" action="<?php echo BASE_URL; ?>/vaccines" class="d-flex gap-2">
                            <input type="hidden" name="patient_id" value="<?php echo $patientId; ?>">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by vaccine name, batch, dose..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="<?php echo BASE_URL; ?>/vaccines?patient_id=<?php echo $patientId; ?>" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Vaccines Table -->
                <?php if (!empty($vaccines)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm table-hover" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vaccine</th>
                                <th>Dose</th>
                                <th>Date Given</th>
                                <th>Next Due</th>
                                <th>Batch/Lot</th>
                                <th>Status</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vaccines as $index => $vac): ?>
                            <?php 
                                $status = 'pending';
                                $statusClass = 'status-pending';
                                $statusLabel = 'Pending';
                                
                                if (!empty($vac['date_given']) && $vac['date_given'] != '0000-00-00') {
                                    $status = 'given';
                                    $statusClass = 'status-given';
                                    $statusLabel = 'Given';
                                }
                                
                                if (!empty($vac['next_due']) && $vac['next_due'] != '0000-00-00') {
                                    $today = new DateTime();
                                    $dueDate = new DateTime($vac['next_due']);
                                    if ($today > $dueDate) {
                                        $status = 'overdue';
                                        $statusClass = 'status-overdue';
                                        $statusLabel = 'Overdue';
                                    }
                                }
                            ?>
                            <tr>
                                <td><?php echo (($currentPage - 1) * 10) + $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($vac['vaccine_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($vac['dose'] ?? ''); ?></td>
                                <td><?php echo !empty($vac['date_given']) ? date('d-m-Y', strtotime($vac['date_given'])) : '-'; ?></td>
                                <td><?php echo !empty($vac['next_due']) ? date('d-m-Y', strtotime($vac['next_due'])) : '-'; ?></td>
                                <td><?php echo htmlspecialchars($vac['batch_number'] ?? ''); ?></td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span></td>
                                <td>
                                    <?php if (!empty($vac['vaccine_image'])): ?>
                                        <img src="<?php echo BASE_URL; ?>/<?php echo $vac['vaccine_image']; ?>" class="vaccine-image" alt="Vaccine">
                                    <?php else: ?>
                                        <span class="text-muted">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="<?php echo BASE_URL; ?>/vaccines/edit/<?php echo $vac['id']; ?>" class="btn-action primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/vaccines/delete/<?php echo $vac['id']; ?>" class="btn-action danger" onclick="return confirm('Are you sure you want to delete this vaccine?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination pagination-sm justify-content-center">
                        <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?patient_id=<?php echo $patientId; ?>&page=<?php echo $currentPage - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?patient_id=<?php echo $patientId; ?>&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?patient_id=<?php echo $patientId; ?>&page=<?php echo $currentPage + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-syringe" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 16px;"></i>
                    <h6 style="color: #64748b;">No vaccination records found</h6>
                    <p class="text-muted" style="font-size: 12px;">Click the "Add Vaccine" button to add a new vaccine.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>