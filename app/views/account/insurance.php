<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4><i class="fas fa-shield-alt me-2"></i>Insurance Management</h4>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#claimModal">
            <i class="fas fa-plus me-2"></i>New Claim
        </button>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Claims</div>
                    <div class="h5"><?php echo count($pendingClaims); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Claim #</th>
                            <th>Bill #</th>
                            <th>Patient</th>
                            <th>Provider</th>
                            <th>Policy #</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($claims)): ?>
                            <?php foreach($claims as $claim): ?>
                            <tr>
                                <td><?php echo $claim['claim_number']; ?></td>
                                <td><?php echo $claim['bill_number']; ?></td>
                                <td><?php echo $claim['first_name'] . ' ' . $claim['last_name']; ?></td>
                                <td><?php echo $claim['insurance_provider']; ?></td>
                                <td><?php echo $claim['policy_number']; ?></td>
                                <td>$<?php echo number_format($claim['claim_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $claim['status'] == 'approved' ? 'success' : 
                                            ($claim['status'] == 'rejected' ? 'danger' : 
                                            ($claim['status'] == 'paid' ? 'info' : 'warning')); 
                                    ?>">
                                        <?php echo ucfirst($claim['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($claim['status'] == 'pending'): ?>
                                    <button class="btn btn-sm btn-success me-1" onclick="approveClaim(<?php echo $claim['id']; ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="rejectClaim(<?php echo $claim['id']; ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No claims found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function approveClaim(id) {
    $.post('<?php echo BASE_URL; ?>/account/insurance/approve', {id: id}, function() {
        location.reload();
    });
}
function rejectClaim(id) {
    Swal.fire({
        title: 'Rejection Reason',
        input: 'text',
        showCancelButton: true
    }).then((result) => {
        if(result.isConfirmed) {
            $.post('<?php echo BASE_URL; ?>/account/insurance/reject', {id: id, reason: result.value}, function() {
                location.reload();
            });
        }
    });
}
</script>