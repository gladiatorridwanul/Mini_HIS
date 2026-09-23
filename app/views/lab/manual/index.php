<?php
// /app/views/lab/manual/index.php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manual Lab Results - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        .card-custom { background: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header-custom { background: white; border-bottom: 2px solid #3b82f6; padding: 10px 15px; font-weight: 600; font-size: 14px; }
        .filter-section { background: #f8fafc; border-radius: 8px; padding: 12px 15px; margin-bottom: 15px; border: 1px solid #e2e8f0; }
        .btn-action { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 4px; font-size: 11px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 4px; text-decoration: none; }
        .btn-action:hover { background: #e2e8f0; }
        .btn-action.primary { background: #dbeafe; color: #2563eb; border-color: #bfdbfe; }
        .btn-action.primary:hover { background: #2563eb; color: white; }
        .btn-action.success { background: #d1fae5; color: #059669; border-color: #a7f3d0; }
        .btn-action.success:hover { background: #059669; color: white; }
        .btn-action.danger { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
        .btn-action.danger:hover { background: #dc2626; color: white; }
        .btn-action.info { background: #dbeafe; color: #2563eb; border-color: #bfdbfe; }
        .btn-action.info:hover { background: #2563eb; color: white; }
        .status-abnormal { background: #fee2e2; color: #991b1b; padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; display: inline-block; }
        .status-normal { background: #d1fae5; color: #065f46; padding: 2px 10px; border-radius: 12px; font-size: 10px; font-weight: 600; display: inline-block; }
        .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block; }
        .empty-state h6 { color: #64748b; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-flask" style="color: #3b82f6;"></i> Manual Lab Results</h5>
                <p class="text-muted" style="font-size: 11px;">Add lab test results manually without creating an order</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/lab/manual/create" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Add New Result
                </a>
                <a href="<?php echo BASE_URL; ?>/lab/dashboard" class="btn btn-secondary btn-sm">
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

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="<?php echo BASE_URL; ?>/lab/manual" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Patient</label>
                    <select name="patient_id" class="form-select form-select-sm">
                        <option value="">All Patients</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($selectedPatient == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['full_name']); ?> (<?php echo $p['patient_code']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-bold">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by test, patient..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                        <a href="<?php echo BASE_URL; ?>/lab/manual" class="btn btn-secondary btn-sm">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        <div class="card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="fas fa-list"></i> Results List</span>
                <span class="badge bg-secondary"><?php echo count($results); ?> records</span>
            </div>
            <div class="table-responsive">
                <?php if (!empty($results)): ?>
                <table class="table table-bordered table-sm table-hover" style="font-size: 12px; margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Patient</th>
                            <th>Test Name</th>
                            <th>Result</th>
                            <th>Normal Range</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $index => $r): ?>
                        <tr>
                            <td><?php echo (($currentPage - 1) * 10) + $index + 1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($r['patient_name']); ?></strong>
                                <br><small class="text-muted"><?php echo $r['patient_code']; ?></small>
                            </td>
                            <td><strong><?php echo htmlspecialchars($r['test_name']); ?></strong></td>
                            <td>
                                <span class="<?php echo $r['is_abnormal'] ? 'text-danger fw-bold' : 'text-success'; ?>">
                                    <?php echo htmlspecialchars($r['result_value']); ?>
                                </span>
                                <?php if ($r['unit']): ?>
                                    <small class="text-muted"><?php echo $r['unit']; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($r['normal_range'] ?? '-'); ?></td>
                            <td>
                                <span class="<?php echo $r['is_abnormal'] ? 'status-abnormal' : 'status-normal'; ?>">
                                    <?php echo $r['is_abnormal'] ? 'Abnormal' : 'Normal'; ?>
                                </span>
                            </td>
                            <td><?php echo $r['entered_at_formatted']; ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?php echo BASE_URL; ?>/lab/manual/edit/<?php echo $r['id']; ?>" class="btn-action primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>/lab/manual/print-report/<?php echo $r['id']; ?>" target="_blank" class="btn-action info" title="Print Report">
                                        <i class="fas fa-print"></i>
                                    </a>
                                    <a href="javascript:void(0)" onclick="deleteResult(<?php echo $r['id']; ?>)" class="btn-action danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-flask"></i>
                    <h6>No Results Found</h6>
                    <p>Click the "Add New Result" button to add a manual lab result.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="p-3 border-top">
                <nav>
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&patient_id=<?php echo $selectedPatient; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            
            <div class="px-3 pb-2 text-center">
                <small class="text-muted">Total: <?php echo $totalRecords; ?> records</small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function deleteResult(id) {
            Swal.fire({
                title: 'Delete Result?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?php echo BASE_URL; ?>/lab/manual/delete/' + id;
                }
            });
        }
    </script>
</body>
</html>