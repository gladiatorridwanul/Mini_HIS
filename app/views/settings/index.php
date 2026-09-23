<?php if (!defined('BASE_URL')) define('BASE_URL', '/unidia/public'); ?>

<style>
    .settings-card {
        background: white;
        border-radius: 16px;
        padding: 0;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .settings-header {
        background: #f8fafc;
        padding: 15px 20px;
        border-bottom: 1px solid #e5e7eb;
        border-radius: 16px 16px 0 0;
    }
    .settings-header h6 {
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }
    .settings-body {
        padding: 25px;
    }
    .group-nav {
        background: white;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .group-nav .list-group-item {
        border: none;
        border-bottom: 1px solid #f1f5f9;
        color: #1f2937;
        padding: 12px 20px;
        font-size: 14px;
        transition: all 0.2s;
        text-decoration: none;
        display: flex;
        align-items: center;
    }
    .group-nav .list-group-item:last-child {
        border-bottom: none;
    }
    .group-nav .list-group-item:hover {
        background: #f8fafc;
    }
    .group-nav .list-group-item.active {
        background: #10b981;
        color: white;
        border-color: #10b981;
    }
    .group-nav .list-group-item.active i {
        color: white;
    }
    .group-nav .list-group-item i {
        width: 20px;
        color: #6c757d;
        margin-right: 10px;
    }
    .group-nav .list-group-item.active i {
        color: white;
    }
    
    .form-label {
        font-weight: 600;
        font-size: 14px;
        color: #1f2937;
        margin-bottom: 6px;
    }
    .form-control, .form-select {
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        padding: 10px 14px;
        font-size: 14px;
        transition: all 0.2s;
    }
    .form-control:focus, .form-select:focus {
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
    }
    .form-control-color {
        padding: 4px;
        height: 50px;
        width: 70px;
    }
    .text-muted {
        font-size: 12px;
        margin-top: 4px;
        display: block;
        color: #6c757d;
    }
    
    .btn-save {
        background: #10b981;
        border: none;
        color: white;
        padding: 10px 30px;
        border-radius: 10px;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s;
    }
    .btn-save:hover {
        background: #059669;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    
    .btn-backup {
        background: #3b82f6;
        border: none;
        color: white;
        padding: 10px 15px;
        border-radius: 10px;
        font-weight: 500;
        font-size: 13px;
        width: 100%;
        transition: all 0.2s;
        border: none;
    }
    .btn-backup:hover {
        background: #2563eb;
        color: white;
    }
    
    .btn-backup-history {
        background: #8b5cf6;
        border: none;
        color: white;
        padding: 10px 15px;
        border-radius: 10px;
        font-weight: 500;
        font-size: 13px;
        width: 100%;
        transition: all 0.2s;
        margin-top: 8px;
        display: block;
        text-align: center;
        text-decoration: none;
    }
    .btn-backup-history:hover {
        background: #7c3aed;
        color: white;
        text-decoration: none;
    }
    
    .empty-settings {
        text-align: center;
        padding: 40px 20px;
    }
    .empty-settings i {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 15px;
    }
    .empty-settings h5 {
        color: #475569;
        margin-bottom: 8px;
    }
    .empty-settings p {
        color: #94a3b8;
        font-size: 14px;
    }
    
    @media (max-width: 768px) {
        .settings-body { padding: 15px; }
        .group-nav .list-group-item { padding: 10px 15px; font-size: 13px; }
        .btn-save { width: 100%; }
    }
</style>

<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold"><i class="fas fa-cog text-success me-2"></i>System Settings</h2>
            <p class="text-muted mb-0" style="font-size: 14px;">Configure system parameters and preferences</p>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Navigation - Only show groups with settings -->
        <div class="col-md-3 mb-4">
            <div class="group-nav">
                <div class="list-group list-group-flush">
                    <?php 
                    // Define group icons and labels
                    $groupConfigs = [
                        'general' => ['label' => 'General', 'icon' => 'globe'],
                        'billing' => ['label' => 'Billing', 'icon' => 'file-invoice-dollar'],
                        'appointment' => ['label' => 'Appointment', 'icon' => 'calendar-check'],
                        'patient' => ['label' => 'Patient', 'icon' => 'user'],
                        'doctor' => ['label' => 'Doctor', 'icon' => 'user-md'],
                        'notification' => ['label' => 'Notification', 'icon' => 'bell'],
                        'email' => ['label' => 'Email', 'icon' => 'envelope'],
                        'security' => ['label' => 'Security', 'icon' => 'shield-alt'],
                        'pharmacy' => ['label' => 'Pharmacy', 'icon' => 'prescription-bottle']
                    ];
                    
                    $currentGroup = isset($currentGroup) ? $currentGroup : 'general';
                    $groups = isset($groups) ? $groups : [];
                    
                    // Only show groups that have settings
                    $visibleGroups = [];
                    foreach($groups as $grp) {
                        if(isset($groupedSettings[$grp]) && !empty($groupedSettings[$grp])) {
                            $visibleGroups[] = $grp;
                        }
                    }
                    
                    // If no groups have settings, show a message
                    if(empty($visibleGroups)) {
                        echo '<div class="list-group-item text-muted text-center py-3">No settings available</div>';
                    }
                    ?>
                    <?php foreach($visibleGroups as $grp): 
                        $config = isset($groupConfigs[$grp]) ? $groupConfigs[$grp] : ['label' => ucfirst($grp), 'icon' => 'cog'];
                    ?>
                    <a href="<?php echo BASE_URL; ?>/admin/settings?group=<?php echo $grp; ?>" 
                       class="list-group-item list-group-item-action <?php echo $currentGroup == $grp ? 'active' : ''; ?>">
                        <i class="fas fa-<?php echo $config['icon']; ?>"></i>
                        <?php echo $config['label']; ?>
                        <span class="badge bg-secondary ms-auto" style="font-size: 10px;">
                            <?php echo isset($groupedSettings[$grp]) ? count($groupedSettings[$grp]) : 0; ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="card mt-3 border-0 shadow-sm">
                <div class="card-body">
                    <button class="btn-backup" onclick="createBackup()">
                        <i class="fas fa-database me-2"></i>Backup Database
                    </button>
                    <a href="<?php echo BASE_URL; ?>/admin/settings/backup-history" class="btn-backup-history">
                        <i class="fas fa-history me-2"></i>Backup History
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Settings Form -->
        <div class="col-md-9">
            <div class="settings-card">
                <div class="settings-header">
                    <h6><i class="fas fa-sliders-h me-2"></i><?php 
                        $currentLabel = isset($groupConfigs[$currentGroup]) ? $groupConfigs[$currentGroup]['label'] : ucfirst($currentGroup);
                        echo $currentLabel . ' Settings';
                        if(isset($groupedSettings[$currentGroup])) {
                            echo ' <span class="badge bg-secondary" style="font-size: 11px;">' . count($groupedSettings[$currentGroup]) . ' settings</span>';
                        }
                    ?></h6>
                </div>
                <div class="settings-body">
                    <form id="settingsForm" action="<?php echo BASE_URL; ?>/admin/settings/update" method="POST" enctype="multipart/form-data">
                        <?php if(isset($settings) && is_array($settings) && !empty($settings)): ?>
                            
                            <?php foreach($settings as $setting): ?>
                                <?php 
                                // Safe access with fallbacks
                                $settingKey = isset($setting['setting_key']) ? $setting['setting_key'] : '';
                                $settingValue = isset($setting['setting_value']) ? $setting['setting_value'] : '';
                                $settingType = isset($setting['setting_type']) ? $setting['setting_type'] : 'text';
                                $displayName = isset($setting['display_name']) ? $setting['display_name'] : ucfirst(str_replace('_', ' ', $settingKey));
                                $description = isset($setting['description']) ? $setting['description'] : '';
                                
                                // Skip if no key
                                if(empty($settingKey)) continue;
                                ?>
                                <div class="mb-4">
                                    <label class="form-label">
                                        <?php echo htmlspecialchars($displayName); ?>
                                    </label>
                                    
                                    <?php if($settingType == 'text'): ?>
                                        <input type="text" name="settings[<?php echo htmlspecialchars($settingKey); ?>]" 
                                               class="form-control" value="<?php echo htmlspecialchars($settingValue); ?>">
                                               
                                    <?php elseif($settingType == 'number'): ?>
                                        <input type="number" name="settings[<?php echo htmlspecialchars($settingKey); ?>]" 
                                               class="form-control" value="<?php echo htmlspecialchars($settingValue); ?>">
                                               
                                    <?php elseif($settingType == 'boolean'): ?>
                                        <select name="settings[<?php echo htmlspecialchars($settingKey); ?>]" class="form-select">
                                            <option value="0" <?php echo ($settingValue == '0' || $settingValue === 0) ? 'selected' : ''; ?>>Disabled</option>
                                            <option value="1" <?php echo ($settingValue == '1' || $settingValue === 1) ? 'selected' : ''; ?>>Enabled</option>
                                        </select>
                                        
                                    <?php elseif($settingType == 'color'): ?>
                                        <input type="color" name="settings[<?php echo htmlspecialchars($settingKey); ?>]" 
                                               class="form-control form-control-color" value="<?php echo htmlspecialchars($settingValue); ?>">
                                               
                                    <?php elseif($settingType == 'file' || $settingType == 'image'): ?>
                                        <?php if(!empty($settingValue) && file_exists(BASE_PATH . '/public/' . $settingValue)): ?>
                                            <div class="mb-2">
                                                <img src="<?php echo BASE_URL . '/' . $settingValue; ?>" 
                                                     alt="Current Logo" style="max-height: 80px; border-radius: 8px; border: 1px solid #e5e7eb; padding: 4px;">
                                                <br>
                                                <small class="text-muted">Current: <?php echo basename($settingValue); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" name="logo" class="form-control" accept="image/*">
                                        <small class="text-muted">Upload a new logo image (PNG, JPG, SVG)</small>
                                        
                                    <?php else: ?>
                                        <textarea name="settings[<?php echo htmlspecialchars($settingKey); ?>]" 
                                                  class="form-control" rows="3"><?php echo htmlspecialchars($settingValue); ?></textarea>
                                    <?php endif; ?>
                                    
                                    <?php if(!empty($description)): ?>
                                        <small class="text-muted"><?php echo htmlspecialchars($description); ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="mt-4">
                                <button type="submit" class="btn-save">
                                    <i class="fas fa-save me-2"></i>Save Settings
                                </button>
                            </div>
                            
                        <?php else: ?>
                            <div class="empty-settings">
                                <i class="fas fa-cog"></i>
                                <h5>No settings found for this group</h5>
                                <p>Switch to another group or create new settings.</p>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

function createBackup() {
    Swal.fire({
        title: 'Creating Backup...',
        text: 'Please wait while we create a database backup',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: BASE_URL + '/admin/settings/backup',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Backup Created!',
                    text: response.message,
                    confirmButtonColor: '#10b981'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Backup Failed',
                    text: response.message || 'Failed to create backup'
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to create database backup'
            });
        }
    });
}

$(document).ready(function() {
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Saving Settings...',
            text: 'Please wait',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        var formData = new FormData(this);
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Settings updated successfully',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update settings'
                    });
                }
            },
            error: function(xhr) {
                let errorMsg = 'An error occurred while saving settings.';
                try {
                    let response = JSON.parse(xhr.responseText);
                    if(response.message) errorMsg = response.message;
                } catch(e) {}
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMsg
                });
            }
        });
    });
});

// Quick save with Ctrl+S
$(document).on('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        $('#settingsForm').submit();
    }
});
</script>