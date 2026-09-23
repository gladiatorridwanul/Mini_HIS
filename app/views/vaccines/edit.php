<?php
// /app/views/vaccines/edit.php
if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Vaccine - UniDia HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f2f5; font-family: 'Inter', sans-serif; font-size: 13px; }
        .card-custom { background: white; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border: 1px solid #e5e7eb; overflow: hidden; }
        .card-header-custom { background: white; border-bottom: 2px solid #3b82f6; padding: 10px 15px; font-weight: 600; font-size: 14px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { font-size: 11px; font-weight: 600; color: #475569; display: block; margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 12px; font-size: 13px; transition: all 0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); outline: none; }
        .form-group textarea { min-height: 60px; resize: vertical; }
        .btn-tab { padding: 8px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; border: none; text-decoration: none; }
        .btn-save { background: #f59e0b; color: white; }
        .btn-save:hover { background: #d97706; }
        .btn-cancel { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-cancel:hover { background: #e2e8f0; color: #1e293b; }
        .image-preview { width: 150px; height: 150px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; margin-top: 8px; }
        .image-preview.hide { display: none; }
    </style>
</head>
<body>
    <div class="container-fluid py-2">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h5 style="color: #1f2937; margin: 0;"><i class="fas fa-syringe" style="color: #3b82f6;"></i> Edit Vaccine</h5>
                <p class="text-muted" style="font-size: 11px;">
                    Patient: <?php echo htmlspecialchars($patient['full_name'] ?? $patient['first_name'] . ' ' . $patient['last_name']); ?>
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?php echo BASE_URL; ?>/vaccines?patient_id=<?php echo $patientId; ?>" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
        </div>

        <!-- Main Card -->
        <div class="card-custom">
            <div class="card-header-custom">
                <i class="fas fa-syringe text-primary"></i> Edit Vaccine
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo BASE_URL; ?>/vaccines/update/<?php echo $vaccine['id']; ?>" enctype="multipart/form-data">
                    <input type="hidden" name="patient_id" value="<?php echo $patientId; ?>">
                    <?php if (isset($_GET['prescription_id']) && $_GET['prescription_id'] > 0): ?>
                        <input type="hidden" name="prescription_id" value="<?php echo (int)$_GET['prescription_id']; ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Vaccine Name <span class="text-danger">*</span></label>
                                <input type="text" name="vaccine_name" required value="<?php echo htmlspecialchars($vaccine['vaccine_name']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Dose</label>
                                <input type="text" name="dose" value="<?php echo htmlspecialchars($vaccine['dose'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date Given</label>
                                <input type="date" name="date_given" value="<?php echo $vaccine['date_given'] ?? ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Next Due Date</label>
                                <input type="date" name="next_due" value="<?php echo $vaccine['next_due'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Batch / Lot Number</label>
                                <input type="text" name="batch_number" value="<?php echo htmlspecialchars($vaccine['batch_number'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Injection Site</label>
                                <input type="text" name="site" value="<?php echo htmlspecialchars($vaccine['site'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Administered By</label>
                                <input type="text" name="administered_by" value="<?php echo htmlspecialchars($vaccine['administered_by'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes"><?php echo htmlspecialchars($vaccine['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Vaccine Image / Card</label>
                                <?php if (!empty($vaccine['vaccine_image'])): ?>
                                    <div class="mb-2">
                                        <img src="<?php echo BASE_URL; ?>/<?php echo $vaccine['vaccine_image']; ?>" class="image-preview" alt="Current vaccine image">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="vaccine_image" accept="image/*" onchange="previewImage(event)">
                                <small class="text-muted">Upload new image to replace existing (JPEG, PNG)</small>
                                <img id="imagePreview" class="image-preview hide" src="#" alt="Preview">
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button type="submit" class="btn-tab btn-save">
                            <i class="fas fa-save"></i> Update Vaccine
                        </button>
                        <a href="<?php echo BASE_URL; ?>/vaccines?patient_id=<?php echo $patientId; ?>" class="btn-tab btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const preview = document.getElementById('imagePreview');
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hide');
                    preview.classList.add('show');
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        }
    </script>
</body>
</html>