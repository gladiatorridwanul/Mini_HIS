<div class="container-fluid">
    <h4 class="mb-4"><i class="fas fa-history me-2"></i>Audit Logs</h4>

    <div class="card shadow">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3">
                    <select class="form-select" id="filterUser" onchange="filterLogs()">
                        <option value="">All Users</option>
                        <?php foreach($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="dateFrom" onchange="filterLogs()">
                </div>
                <div class="col-md-3">
                    <input type="date" class="form-control" id="dateTo" onchange="filterLogs()">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($logs)): ?>
                            <?php foreach($logs as $log): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i:s A', strtotime($log['created_at'])); ?></td>
                                <td><?php echo $log['user_name']; ?></td>
                                <td><?php echo $log['action']; ?></td>
                                <td><?php echo $log['module']; ?></td>
                                <td><?php echo substr($log['new_value'] ?? '', 0, 100); ?></td>
                                <td><?php echo $log['ip_address']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center py-4">No logs found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function filterLogs() {
    var params = {
        user_id: $('#filterUser').val(),
        date_from: $('#dateFrom').val(),
        date_to: $('#dateTo').val()
    };
    var queryString = $.param(params);
    window.location.href = '<?php echo BASE_URL; ?>/admin/audit-logs?' + queryString;
}
</script>