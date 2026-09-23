<?php
/**
 * Menu & Permissions Management Page
 * Shows menus from sidebar with role-wise access
 */

// No need for ob_start() or ob_get_clean() here
// The controller handles the layout
?>

<style>
    /* Cambria font for all elements */
    * {
        font-family: 'Cambria', 'Georgia', serif;
    }
    
    .page-header {
        margin-bottom: 20px;
    }
    
    .page-header h4 {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        font-family: 'Cambria', 'Georgia', serif;
    }
    
    .page-header p {
        color: #94a3b8;
        font-size: 13px;
        margin: 2px 0 0 0;
        font-family: 'Cambria', 'Georgia', serif;
    }
    
    .btn-back {
        color: #64748b;
        text-decoration: none;
        font-size: 13px;
        font-family: 'Cambria', 'Georgia', serif;
        padding: 6px 14px;
        border-radius: 6px;
        background: #f1f5f9;
        transition: all 0.2s;
    }
    
    .btn-back:hover {
        background: #e2e8f0;
        color: #1f2937;
    }
    
    /* Role Cards */
    .role-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        height: 100%;
        transition: box-shadow 0.2s;
    }
    
    .role-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.10);
    }
    
    .role-card-header {
        padding: 12px 16px;
        color: white;
        font-weight: 600;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: 'Cambria', 'Georgia', serif;
    }
    
    .role-card-header .badge-count {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 2px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .role-card-body {
        padding: 14px 16px;
        background: #fafafa;
        font-family: 'Cambria', 'Georgia', serif;
    }
    
    /* Role Header Colors - Matches sidebar */
    .role-super-admin { background: linear-gradient(135deg, #dc3545, #c82333); }
    .role-admin { background: linear-gradient(135deg, #f59e0b, #d97706); }
    .role-doctor { background: linear-gradient(135deg, #3b82f6, #2563eb); }
    .role-receptionist { background: linear-gradient(135deg, #10b981, #059669); }
    .role-pharmacist { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
    .role-lab-technician { background: linear-gradient(135deg, #14b8a6, #0d9488); }
    .role-accountant { background: linear-gradient(135deg, #f97316, #ea580c); }
    .role-nurse { background: linear-gradient(135deg, #ef4444, #dc2626); }
    .role-default { background: linear-gradient(135deg, #6b7280, #4b5563); }
    
    /* Scroll Area */
    .scroll-area {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 4px;
    }
    
    .scroll-area::-webkit-scrollbar {
        width: 4px;
    }
    
    .scroll-area::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    
    .scroll-area::-webkit-scrollbar-thumb {
        background: #3b82f6;
        border-radius: 10px;
    }
    
    /* Menu Group */
    .menu-group {
        margin-bottom: 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        padding: 8px 10px;
        background: white;
    }
    
    .menu-group:last-child {
        margin-bottom: 0;
    }
    
    .menu-group .menu-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        cursor: pointer;
        padding: 4px 0;
    }
    
    .menu-group .menu-title {
        font-weight: 600;
        color: #1e293b;
        font-size: 12px;
        font-family: 'Cambria', 'Georgia', serif;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .menu-group .menu-title i {
        width: 18px;
        color: #3b82f6;
    }
    
    .menu-group .menu-count {
        font-size: 10px;
        color: #94a3b8;
        background: #f1f5f9;
        padding: 0 8px;
        border-radius: 10px;
        font-weight: 500;
    }
    
    .menu-group .menu-items {
        padding-top: 4px;
        display: none;
    }
    
    .menu-group.open .menu-items {
        display: block;
    }
    
    .menu-group .toggle-icon {
        transition: transform 0.3s;
        font-size: 10px;
        color: #94a3b8;
    }
    
    .menu-group.open .toggle-icon {
        transform: rotate(180deg);
    }
    
    /* Permission Checkbox */
    .perm-check {
        padding-left: 10px;
        margin-bottom: 2px;
        font-family: 'Cambria', 'Georgia', serif;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .perm-check .form-check-input {
        width: 13px;
        height: 13px;
        margin-top: 0;
        border: 2px solid #cbd5e1;
        border-radius: 3px;
        cursor: pointer;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    
    .perm-check .form-check-input:checked {
        background-color: #3b82f6;
        border-color: #3b82f6;
    }
    
    .perm-check .form-check-input:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    
    .perm-check .form-check-label {
        font-size: 11px;
        color: #334155;
        cursor: pointer;
        font-family: 'Cambria', 'Georgia', serif;
        padding: 2px 0;
    }
    
    .perm-check .form-check-input:disabled + .form-check-label {
        color: #94a3b8;
        cursor: not-allowed;
    }
    
    .perm-check .submenu-label {
        font-size: 10px;
        color: #64748b;
        padding-left: 4px;
    }
    
    /* Select All Button */
    .select-all-btn {
        font-size: 9px;
        padding: 1px 8px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
        font-family: 'Cambria', 'Georgia', serif;
        color: #64748b;
    }
    
    .select-all-btn:hover {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .select-all-btn.disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    .select-all-btn.disabled:hover {
        background: white;
        color: #64748b;
        border-color: #e5e7eb;
    }
    
    /* Save Button */
    .btn-save {
        background: #3b82f6;
        border: none;
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 12px;
        transition: all 0.3s;
        width: 100%;
        font-family: 'Cambria', 'Georgia', serif;
        margin-top: 8px;
    }
    
    .btn-save:hover {
        background: #2563eb;
        color: white;
    }
    
    .btn-save:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Super Admin Alert */
    .super-alert {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        border-radius: 6px;
        padding: 4px 10px;
        font-size: 10px;
        margin-top: 8px;
        font-family: 'Cambria', 'Georgia', serif;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .super-alert i {
        color: #3b82f6;
    }
    
    /* Alert Messages */
    .alert-custom {
        border-radius: 8px;
        border: none;
        padding: 8px 14px;
        font-size: 13px;
        font-family: 'Cambria', 'Georgia', serif;
        margin-bottom: 15px;
    }
    .alert-success-custom {
        background: #ecfdf5;
        color: #065f46;
        border-left: 3px solid #10b981;
    }
    .alert-danger-custom {
        background: #fef2f2;
        color: #991b1b;
        border-left: 3px solid #ef4444;
    }
    
    /* Empty State */
    .empty-state {
        padding: 40px 20px;
        text-align: center;
    }
    .empty-state i {
        font-size: 40px;
        color: #cbd5e1;
        margin-bottom: 12px;
    }
    .empty-state h6 {
        color: #475569;
        margin-bottom: 4px;
        font-family: 'Cambria', 'Georgia', serif;
    }
    .empty-state p {
        color: #94a3b8;
        font-size: 13px;
        font-family: 'Cambria', 'Georgia', serif;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .role-card-header { padding: 10px 14px; font-size: 13px; }
        .role-card-body { padding: 12px 14px; }
        .scroll-area { max-height: 250px; }
        .perm-check .form-check-label { font-size: 10px; }
        .menu-group .menu-title { font-size: 11px; }
    }
</style>

<div class="container-fluid px-3">
    <!-- Header -->
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h4><i class="fas fa-bars text-primary me-2"></i>Menu Permissions</h4>
                <p>Manage menu access for each role. Super Admin has full access by default.</p>
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>New:</strong> "Edit Bills" permission added to Billing module.
                </small>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/users" class="btn-back">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert-custom alert-success-custom">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close float-end" data-bs-dismiss="alert" style="font-size: 10px;"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert-custom alert-danger-custom">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close float-end" data-bs-dismiss="alert" style="font-size: 10px;"></button>
        </div>
    <?php endif; ?>

    <!-- Roles Grid -->
    <div class="row">
        <?php if (!empty($roles)): ?>
            <?php foreach($roles as $role): 
                $roleSlug = $role['slug'] ?? strtolower(str_replace(' ', '_', $role['name']));
                $isSuperAdmin = ($role['id'] == 1 || $roleSlug == 'super_admin');
                
                $rolePerms = isset($rolePermissions[$role['id']]) && is_array($rolePermissions[$role['id']]) 
                    ? $rolePermissions[$role['id']] 
                    : [];
                
                $roleClass = 'role-default';
                switch($roleSlug) {
                    case 'super_admin': $roleClass = 'role-super-admin'; break;
                    case 'admin': $roleClass = 'role-admin'; break;
                    case 'doctor': $roleClass = 'role-doctor'; break;
                    case 'receptionist': $roleClass = 'role-receptionist'; break;
                    case 'pharmacist': $roleClass = 'role-pharmacist'; break;
                    case 'lab_technician': $roleClass = 'role-lab-technician'; break;
                    case 'accountant': $roleClass = 'role-accountant'; break;
                    case 'nurse': $roleClass = 'role-nurse'; break;
                }
            ?>
            <div class="col-lg-6 col-xl-4 mb-3">
                <div class="role-card">
                    <!-- Card Header -->
                    <div class="role-card-header <?php echo $roleClass; ?>">
                        <span>
                            <i class="fas <?php echo $isSuperAdmin ? 'fa-crown' : 'fa-user-shield'; ?> me-2"></i>
                            <?php echo htmlspecialchars($role['name']); ?>
                        </span>
                        <span class="badge-count">
                            <i class="fas fa-users me-1"></i> <?php echo $role['user_count'] ?? 0; ?>
                        </span>
                    </div>
                    
                    <!-- Card Body -->
                    <div class="role-card-body">
                        <form class="role-permission-form" data-role-id="<?php echo $role['id']; ?>">
                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                            
                            <div class="scroll-area">
                                <?php if (!empty($permissionsByModule)): ?>
                                    <?php foreach($permissionsByModule as $module => $permissions): 
                                        $menuIcon = getMenuIcon($module);
                                        $menuTitle = ucfirst(str_replace('_', ' ', $module));
                                        
                                        // Sort permissions: main permissions first, then sub-permissions
                                        usort($permissions, function($a, $b) {
                                            $mainPerms = ['view_dashboard', 'view_appointments', 'view_patients', 'view_doctors', 'view_prescriptions', 'view_bills', 'view_pharmacy', 'view_lab', 'view_inventory', 'view_users', 'view_settings', 'view_audit', 'view_vaccines'];
                                            $aIsMain = in_array($a['slug'], $mainPerms);
                                            $bIsMain = in_array($b['slug'], $mainPerms);
                                            if ($aIsMain && !$bIsMain) return -1;
                                            if (!$aIsMain && $bIsMain) return 1;
                                            return strcmp($a['name'], $b['name']);
                                        });
                                        
                                        $checkedCount = 0;
                                        foreach($permissions as $perm) {
                                            if (in_array($perm['slug'], $rolePerms)) {
                                                $checkedCount++;
                                            }
                                        }
                                        $totalCount = count($permissions);
                                    ?>
                                        <div class="menu-group <?php echo $checkedCount > 0 ? 'open' : ''; ?>" data-module="<?php echo $module; ?>">
                                            <div class="menu-header" onclick="toggleMenuGroup(this)">
                                                <span class="menu-title">
                                                    <i class="fas <?php echo $menuIcon; ?>"></i>
                                                    <?php echo $menuTitle; ?>
                                                    <span class="menu-count"><?php echo $checkedCount; ?>/<?php echo $totalCount; ?></span>
                                                </span>
                                                <span>
                                                    <span class="select-all-btn" onclick="event.stopPropagation(); toggleModule(this, '<?php echo $role['id']; ?>', '<?php echo $module; ?>')">
                                                        Select All
                                                    </span>
                                                    <i class="fas fa-chevron-down toggle-icon"></i>
                                                </span>
                                            </div>
                                            <div class="menu-items">
                                                <?php foreach($permissions as $perm): 
                                                    $checked = in_array($perm['slug'], $rolePerms);
                                                    $isMain = in_array($perm['slug'], ['view_dashboard', 'view_appointments', 'view_patients', 'view_doctors', 'view_prescriptions', 'view_bills', 'view_pharmacy', 'view_lab', 'view_inventory', 'view_users', 'view_settings', 'view_audit', 'view_vaccines']);
                                                    
                                                    // Highlight the edit_bills permission
                                                    $isEditBills = ($perm['slug'] == 'edit_bills');
                                                    $highlightClass = $isEditBills ? 'border border-warning bg-warning bg-opacity-10' : '';
                                                ?>
                                                    <div class="perm-check form-check <?php echo $highlightClass; ?>" style="<?php echo $isEditBills ? 'border-radius:4px;padding:2px 6px;' : ''; ?>">
                                                        <input type="checkbox" 
                                                               name="permissions[]" 
                                                               value="<?php echo htmlspecialchars($perm['slug']); ?>" 
                                                               class="form-check-input perm-checkbox" 
                                                               id="perm_<?php echo $role['id']; ?>_<?php echo htmlspecialchars($perm['slug']); ?>"
                                                               data-role="<?php echo $role['id']; ?>"
                                                               data-module="<?php echo $module; ?>"
                                                               <?php echo $checked ? 'checked' : ''; ?>
                                                               <?php echo $isSuperAdmin ? 'disabled' : ''; ?>>
                                                        <label class="form-check-label <?php echo !$isMain ? 'submenu-label' : ''; ?>" for="perm_<?php echo $role['id']; ?>_<?php echo htmlspecialchars($perm['slug']); ?>">
                                                            <?php echo htmlspecialchars($perm['name']); ?>
                                                            <?php if ($isEditBills): ?>
                                                                <span class="badge bg-warning text-dark ms-1" style="font-size:8px;">NEW</span>
                                                            <?php endif; ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-inbox d-block mb-1"></i>
                                        <span style="font-size: 12px;">No menus found</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($isSuperAdmin): ?>
                                <div class="super-alert">
                                    <i class="fas fa-info-circle"></i>
                                    Super Admin has full access to all menus
                                </div>
                            <?php else: ?>
                                <button type="submit" class="btn-save" onclick="saveRolePermissions(event, <?php echo $role['id']; ?>)">
                                    <i class="fas fa-save me-2"></i> Save Permissions
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Empty State -->
    <?php if (empty($roles)): ?>
        <div class="empty-state">
            <i class="fas fa-users-slash"></i>
            <h6>No Roles Found</h6>
            <p>Please create roles to manage menu permissions.</p>
            <a href="<?php echo BASE_URL; ?>/admin/users/roles/create" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-2"></i> Create Role
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';

/**
 * Toggle menu group expansion
 */
function toggleMenuGroup(header) {
    const group = header.closest('.menu-group');
    group.classList.toggle('open');
}

/**
 * Toggle all permissions in a module
 */
function toggleModule(btn, roleId, module) {
    if (btn.classList.contains('disabled')) return;
    
    const form = btn.closest('.role-permission-form');
    const group = btn.closest('.menu-group');
    const checkboxes = group.querySelectorAll('.perm-checkbox[data-role="' + roleId + '"][data-module="' + module + '"]');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    
    checkboxes.forEach(cb => {
        if (!cb.disabled) {
            cb.checked = !allChecked;
        }
    });
    
    btn.textContent = allChecked ? 'Select All' : 'Deselect All';
    updateMenuCounts(group);
}

/**
 * Update menu counts
 */
function updateMenuCounts(group) {
    const checkboxes = group.querySelectorAll('.perm-checkbox:not(:disabled)');
    const checked = group.querySelectorAll('.perm-checkbox:checked:not(:disabled)');
    const countBadge = group.querySelector('.menu-count');
    if (countBadge) {
        countBadge.textContent = checked.length + '/' + checkboxes.length;
    }
    
    // Update Select All button text
    const selectBtn = group.querySelector('.select-all-btn');
    if (selectBtn && !selectBtn.classList.contains('disabled')) {
        if (checkboxes.length > 0 && checked.length === checkboxes.length) {
            selectBtn.textContent = 'Deselect All';
        } else {
            selectBtn.textContent = 'Select All';
        }
    }
}

/**
 * Save role permissions via AJAX
 */
function saveRolePermissions(event, roleId) {
    event.preventDefault();
    
    const form = event.target.closest('form');
    const checkboxes = form.querySelectorAll('.perm-checkbox:checked');
    const permissions = [];
    
    checkboxes.forEach(cb => {
        if (!cb.disabled) {
            permissions.push(cb.value);
        }
    });
    
    const formData = new FormData();
    permissions.forEach(perm => {
        formData.append('permissions[]', perm);
    });
    
    const btn = form.querySelector('.btn-save');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Saving...';
    btn.disabled = true;
    
    fetch(BASE_URL + '/admin/users/roles/update/' + roleId, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
            return;
        }
        return response.json();
    })
    .then(data => {
        if (data && data.success) {
            showToast('✅ ' + data.message, 'success');
        } else if (data && !data.success) {
            showToast('❌ ' + data.message, 'error');
        }
        btn.innerHTML = originalText;
        btn.disabled = false;
        updateAllMenuCounts(roleId);
    })
    .catch(error => {
        showToast('❌ Error: ' + error.message, 'error');
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

/**
 * Update all menu counts for a role
 */
function updateAllMenuCounts(roleId) {
    const forms = document.querySelectorAll('.role-permission-form[data-role-id="' + roleId + '"]');
    forms.forEach(form => {
        const groups = form.querySelectorAll('.menu-group');
        groups.forEach(group => {
            updateMenuCounts(group);
        });
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const colors = {
        success: '#10b981',
        error: '#ef4444',
        warning: '#f59e0b',
        info: '#3b82f6'
    };
    
    document.querySelectorAll('.toast-notification').forEach(el => el.remove());
    
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type] || '#3b82f6'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        z-index: 99999;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
        max-width: 400px;
        font-family: 'Cambria', 'Georgia', serif;
        font-size: 13px;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Add slide-in animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.role-permission-form').forEach(form => {
        const roleId = form.dataset.roleId;
        updateAllMenuCounts(roleId);
    });
});
</script>

<?php
/**
 * Helper function for menu icons - matches sidebar icons
 */
function getMenuIcon($module) {
    $icons = [
        'dashboard' => 'fa-tachometer-alt',
        'reception' => 'fa-clipboard-list',
        'patients' => 'fa-procedures',
        'vaccines' => 'fa-syringe',
        'doctors' => 'fa-user-md',
        'prescriptions' => 'fa-prescription',
        'billing' => 'fa-file-invoice-dollar',
        'pharmacy' => 'fa-prescription-bottle',
        'lab' => 'fa-microscope',
        'inventory' => 'fa-boxes',
        'users' => 'fa-users',
        'settings' => 'fa-cog',
        'audit' => 'fa-history'
    ];
    return $icons[$module] ?? 'fa-circle';
}
?>