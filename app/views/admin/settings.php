<div class="container-fluid">
    <h4 class="mb-4"><i class="fas fa-cog me-2"></i>System Settings</h4>

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="list-group" id="settingsTabs">
                <a class="list-group-item list-group-item-action active" data-bs-toggle="list" href="#general">General</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#email">Email Settings</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#billing">Billing</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#prescription">Prescription</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#appointment">Appointment</a>
                <a class="list-group-item list-group-item-action" data-bs-toggle="list" href="#backup">Backup & Restore</a>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="general">
                    <div class="card shadow">
                        <div class="card-header"><h6 class="mb-0">General Settings</h6></div>
                        <div class="card-body">
                            <form>
                                <div class="mb-3">
                                    <label>Hospital/Clinic Name</label>
                                    <input type="text" class="form-control" value="UniDia Healthcare">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label>Phone</label>
                                        <input type="text" class="form-control" value="+1 234 567 8900">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label>Email</label>
                                        <input type="email" class="form-control" value="info@unidia.com">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label>Address</label>
                                    <textarea class="form-control" rows="3">123 Healthcare Street, Medical District</textarea>
                                </div>
                                <div class="mb-3">
                                    <label>Currency</label>
                                    <select class="form-select">
                                        <option value="USD">USD ($)</option>
                                        <option value="EUR">EUR (€)</option>
                                        <option value="GBP">GBP (£)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label>Time Zone</label>
                                    <select class="form-select">
                                        <option>America/New_York</option>
                                        <option>Europe/London</option>
                                        <option>Asia/Dhaka</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="backup">
                    <div class="card shadow">
                        <div class="card-header"><h6 class="mb-0">Backup & Restore</h6></div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Regular backups are essential. Download a backup before making major changes.
                            </div>
                            <button class="btn btn-primary mb-3" onclick="createBackup()">
                                <i class="fas fa-download me-2"></i>Create New Backup
                            </button>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr><th>Backup File</th><th>Date</th><th>Size</th><th>Actions</th></tr>
                                    </thead>
                                    <tbody id="backupList">
                                        <tr><td colspan="4" class="text-center">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function createBackup() {
    Swal.fire({
        title: 'Creating Backup...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });
    
    $.post('<?php echo BASE_URL; ?>/admin/backup/create', function(response) {
        Swal.fire('Success!', 'Backup created successfully', 'success');
        loadBackups();
    });
}

function loadBackups() {
    $.get('<?php echo BASE_URL; ?>/admin/backup/list', function(data) {
        var html = '';
        data.forEach(function(backup) {
            html += `<tr>
                <td>${backup.filename}</td>
                <td>${backup.date}</td>
                <td>${backup.size}</td>
                <td>
                    <a href="${backup.download_url}" class="btn btn-sm btn-primary"><i class="fas fa-download"></i></a>
                    <button class="btn btn-sm btn-danger" onclick="deleteBackup('${backup.filename}')"><i class="fas fa-trash"></i></button>
                </td>
            </tr>`;
        });
        $('#backupList').html(html || '<tr><td colspan="4" class="text-center">No backups found</td></tr>');
    });
}
</script>