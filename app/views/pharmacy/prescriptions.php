<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .prescription-card {
        border-left: 4px solid #10b981;
        transition: transform 0.2s;
    }
    .prescription-card:hover {
        transform: translateX(5px);
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-prescription text-success me-2"></i>E-Prescriptions</h2>
</div>

<div class="row">
    <?php if(!empty($prescriptions)): ?>
        <?php foreach($prescriptions as $rx): ?>
        <div class="col-md-6 mb-4">
            <div class="card prescription-card shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h5 class="mb-1">Rx #<?php echo $rx['prescription_number']; ?></h5>
                            <small class="text-muted"><?php echo date('d M Y', strtotime($rx['prescription_date'])); ?></small>
                        </div>
                        <span class="badge bg-warning">Pending</span>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Patient</small>
                            <div><strong><?php echo $rx['patient_name']; ?></strong></div>
                            <small><?php echo $rx['phone']; ?></small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Prescribed By</small>
                            <div><strong>Dr. <?php echo $rx['doctor_name']; ?></strong></div>
                        </div>
                    </div>
                    
                    <?php if($rx['diagnosis']): ?>
                    <div class="mb-3">
                        <small class="text-muted">Diagnosis</small>
                        <p class="mb-0 small"><?php echo $rx['diagnosis']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-pills text-muted me-1"></i>
                            <small class="text-muted">Items pending</small>
                        </div>
                        <a href="<?php echo BASE_URL; ?>/pharmacy/dispense/<?php echo $rx['id']; ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-pills me-1"></i>Dispense
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="fas fa-prescription fa-3x mb-3"></i>
                <p>No pending prescriptions found</p>
            </div>
        </div>
    <?php endif; ?>
</div>