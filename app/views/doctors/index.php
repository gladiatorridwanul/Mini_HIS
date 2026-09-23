<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5><i class="fas fa-user-md me-2"></i>Doctor List</h5>
        <a href="/unidia/public/doctor/create" class="btn btn-primary btn-sm">Add Doctor</a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr><th>Name</th><th>Specialization</th><th>Department</th><th>Phone</th><th>Fee</th><th>Commission</th><th>Status</th><th>Actions</th></td>
                </thead>
                <tbody>
                    <?php foreach($doctors as $d): ?>
                    <tr>
                        <td><strong>Dr. <?php echo $d['first_name'] . ' ' . $d['last_name']; ?></strong><br><small><?php echo $d['email']; ?></small></td>
                        <td><?php echo $d['specialization']; ?></td>
                        <td><?php echo $d['dept_name']; ?></td>
                        <td><?php echo $d['phone']; ?></td>
                        <td>$<?php echo number_format($d['consultation_fee'], 2); ?></td>
                        <td><?php echo $d['commission_percentage']; ?>%</td>
                        <td><span class="badge bg-success"><?php echo ucfirst($d['status']); ?></span></td>
                        <td>
                            <a href="/unidia/public/doctor/view/<?php echo $d['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                            <a href="/unidia/public/doctor/edit/<?php echo $d['id']; ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                            <a href="/unidia/public/doctor/schedule/<?php echo $d['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-calendar-alt"></i></a>
                            <a href="/unidia/public/doctor/delete/<?php echo $d['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>