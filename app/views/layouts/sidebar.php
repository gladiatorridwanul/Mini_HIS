<?php
// /app/views/layouts/sidebar.php
// Complete sidebar with Role-Wise Menu Access

// Get user role and permissions
$userRole = $_SESSION['role_slug'] ?? 'guest';
$userName = $_SESSION['user_name'] ?? 'User';
$roleId = $_SESSION['role_id'] ?? 0;

// Get user permissions from database
$userPermissions = [];
if ($roleId > 0) {
    try {
        $conn = Database::getInstance()->getConnection();
        $result = $conn->query("SELECT permission_slug FROM role_permissions WHERE role_id = $roleId");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $userPermissions[] = $row['permission_slug'];
            }
        }
    } catch (Exception $e) {
        $userPermissions = [];
    }
}

// If permissions not found, try to get from session
if (empty($userPermissions) && isset($_SESSION['permissions'])) {
    if (is_array($_SESSION['permissions'])) {
        $userPermissions = $_SESSION['permissions'];
    } elseif (is_string($_SESSION['permissions'])) {
        $userPermissions = json_decode($_SESSION['permissions'], true) ?: [];
    }
}

// Check if user has permission for a menu
function hasMenuAccess($permission, $userPermissions, $userRole) {
    if ($userRole == 'super_admin' || $userRole == 'admin') {
        return true;
    }
    if (empty($permission)) {
        return true;
    }
    return in_array($permission, $userPermissions);
}

// Define complete menu structure
function getSidebarMenus() {
    return [
        [
            'title' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'url' => '/admin/dashboard',
            'permission' => 'view_dashboard'
        ],
        [
            'title' => 'Reception',
            'icon' => 'fas fa-clipboard-list',
            'url' => '#',
            'permission' => 'view_appointments',
            'submenus' => [
                ['title' => 'Appointments', 'icon' => 'fas fa-calendar-check', 'url' => '/reception/appointments', 'permission' => 'view_appointments'],
                ['title' => 'Daily Patient List', 'icon' => 'fas fa-list', 'url' => '/reception/daily-list', 'permission' => 'view_appointments'],
                ['title' => 'Check In/Out', 'icon' => 'fas fa-clipboard-check', 'url' => '/reception/check-in', 'permission' => 'view_appointments'],
                ['title' => 'Queue Management', 'icon' => 'fas fa-chart-line', 'url' => '/reception/queue', 'permission' => 'manage_queue'],
                ['title' => 'Doctor List', 'icon' => 'fas fa-user-md', 'url' => '/reception/doctor-list', 'permission' => 'view_appointments'],
                ['title' => 'Book Appointment', 'icon' => 'fas fa-plus', 'url' => '/appointments/book', 'permission' => 'view_appointments']
            ]
        ],
        [
            'title' => 'Patient',
            'icon' => 'fas fa-procedures',
            'url' => '#',
            'permission' => 'view_patients',
            'submenus' => [
                ['title' => 'All Patients', 'icon' => 'fas fa-list', 'url' => '/patient/list', 'permission' => 'view_patients'],
                ['title' => 'Register Patient', 'icon' => 'fas fa-user-plus', 'url' => '/patient/register', 'permission' => 'create_patients']
            ]
        ],
        [
            'title' => 'Doctor',
            'icon' => 'fas fa-user-md',
            'url' => '#',
            'permission' => 'view_doctors',
            'submenus' => [
                ['title' => 'All Doctors', 'icon' => 'fas fa-list', 'url' => '/doctor/list', 'permission' => 'view_doctors'],
                ['title' => 'Register Doctor', 'icon' => 'fas fa-user-plus', 'url' => '/doctor/create', 'permission' => 'create_doctors'],
                ['title' => 'Schedules', 'icon' => 'fas fa-calendar-alt', 'url' => '/doctor/schedule-list', 'permission' => 'manage_schedule'],
                ['title' => 'Commissions', 'icon' => 'fas fa-percent', 'url' => '/doctor/commissions', 'permission' => 'manage_commission']
            ]
        ],
        [
            'title' => 'Prescription',
            'icon' => 'fas fa-prescription',
            'url' => '#',
            'permission' => 'view_prescriptions',
            'submenus' => [
                ['title' => 'All Prescriptions', 'icon' => 'fas fa-list', 'url' => '/prescription/list', 'permission' => 'view_prescriptions'],
                ['title' => 'Create Prescription', 'icon' => 'fas fa-plus', 'url' => '/prescription/create', 'permission' => 'create_prescriptions']
            ]
        ],
        [
            'title' => 'Billing',
            'icon' => 'fas fa-file-invoice-dollar',
            'url' => '#',
            'permission' => 'view_bills',
            'submenus' => [
                ['title' => 'All Bills', 'icon' => 'fas fa-list', 'url' => '/bills', 'permission' => 'view_bills'],
                ['title' => 'Create Bill', 'icon' => 'fas fa-plus', 'url' => '/bills/create', 'permission' => 'create_bills'],
                ['title' => 'Payments', 'icon' => 'fas fa-credit-card', 'url' => '/bills/payments', 'permission' => 'process_payments'],
                ['title' => 'Financial Reports', 'icon' => 'fas fa-chart-line', 'url' => '/account/reports', 'permission' => 'view_financial']
            ]
        ],
        [
            'title' => 'Pharmacy',
            'icon' => 'fas fa-prescription-bottle',
            'url' => '#',
            'permission' => 'view_pharmacy',
            'submenus' => [
                ['title' => 'Dashboard', 'icon' => 'fas fa-chart-pie', 'url' => '/pharmacy/dashboard', 'permission' => 'view_pharmacy'],
                ['title' => 'Manage Medicines', 'icon' => 'fas fa-tablets', 'url' => '/pharmacy/medicines', 'permission' => 'manage_medicines'],
                ['title' => 'Point of Sale', 'icon' => 'fas fa-cash-register', 'url' => '/pharmacy/pos', 'permission' => 'process_sales'],
                ['title' => 'E-Prescription', 'icon' => 'fas fa-prescription', 'url' => '/pharmacy/prescriptions', 'permission' => 'dispense_prescriptions'],
                ['title' => 'Stock Management', 'icon' => 'fas fa-boxes', 'url' => '/pharmacy/stock', 'permission' => 'manage_medicines'],
                ['title' => 'Sales History', 'icon' => 'fas fa-history', 'url' => '/pharmacy/sales', 'permission' => 'view_sales']
            ]
        ],
        [
            'title' => 'Laboratory',
            'icon' => 'fas fa-microscope',
            'url' => '#',
            'permission' => 'view_lab',
            'submenus' => [
                ['title' => 'Dashboard', 'icon' => 'fas fa-chart-pie', 'url' => '/lab/dashboard', 'permission' => 'view_lab'],
                ['title' => 'Test Orders', 'icon' => 'fas fa-flask', 'url' => '/lab/orders', 'permission' => 'view_lab_orders'],
                ['title' => 'Create Order', 'icon' => 'fas fa-plus', 'url' => '/lab/create-order', 'permission' => 'create_orders'],
                ['title' => 'Sample Collections', 'icon' => 'fas fa-vial', 'url' => '/lab/sample-collection', 'permission' => 'view_sample_collection'],
                ['title' => 'Enter Results', 'icon' => 'fas fa-edit', 'url' => '/lab/enter-results', 'permission' => 'enter_results'],
                ['title' => 'Reports', 'icon' => 'fas fa-file-download', 'url' => '/lab/reports', 'permission' => 'view_reports'],
                ['title' => 'Patient Lab Tests', 'icon' => 'fas fa-flask', 'url' => '/lab/patient-tests', 'permission' => 'view_patient_lab_tests'],
                ['title' => 'Manual Lab Results', 'icon' => 'fas fa-flask', 'url' => '/lab/manual', 'permission' => 'view_manual_lab'],
                ['title' => 'Manage Tests', 'icon' => 'fas fa-cog', 'url' => '/lab/manage-tests', 'permission' => 'manage_tests'],
                ['title' => 'Categories', 'icon' => 'fas fa-tags', 'url' => '/lab/categories', 'permission' => 'manage_test_categories'],
                ['title' => 'Instruments', 'icon' => 'fas fa-microscope', 'url' => '/lab/instruments', 'permission' => 'manage_instruments'],
                ['title' => 'Accessories', 'icon' => 'fas fa-tools', 'url' => '/lab/accessories', 'permission' => 'manage_accessories'],
                ['title' => 'Lab-Test Prescriptions', 'icon' => 'fas fa-prescription-bottle', 'url' => '/lab/prescriptions', 'permission' => 'view_lab_prescriptions']
            ]
        ],
        [
            'title' => 'Inventory',
            'icon' => 'fas fa-boxes',
            'url' => '#',
            'permission' => 'view_inventory',
            'submenus' => [
                ['title' => 'Dashboard', 'icon' => 'fas fa-chart-pie', 'url' => '/inventory/dashboard', 'permission' => 'view_inventory'],
                ['title' => 'Items', 'icon' => 'fas fa-list', 'url' => '/inventory/items', 'permission' => 'view_inventory_items'],
                ['title' => 'Add Item', 'icon' => 'fas fa-plus', 'url' => '/inventory/items/add', 'permission' => 'add_inventory'],
                ['title' => 'Purchase Orders', 'icon' => 'fas fa-shopping-cart', 'url' => '/inventory/purchase-orders', 'permission' => 'manage_purchase'],
                ['title' => 'Expiry Alerts', 'icon' => 'fas fa-calendar-warning', 'url' => '/inventory/expiry-alerts', 'permission' => 'view_expiry_alerts'],
                ['title' => 'Reorder Alerts', 'icon' => 'fas fa-bell', 'url' => '/inventory/reorder-alerts', 'permission' => 'view_reorder_alerts'],
                ['title' => 'Stock Transfers', 'icon' => 'fas fa-exchange-alt', 'url' => '/inventory/stock-transfers', 'permission' => 'manage_transfers'],
                ['title' => 'Stores', 'icon' => 'fas fa-store', 'url' => '/inventory/stores', 'permission' => 'manage_stores'],
                ['title' => 'Suppliers', 'icon' => 'fas fa-truck', 'url' => '/inventory/suppliers', 'permission' => 'manage_suppliers'],
                ['title' => 'Reports', 'icon' => 'fas fa-chart-bar', 'url' => '/inventory/reports', 'permission' => 'view_inventory_reports']
            ]
        ],
        [
            'title' => 'Vaccine',
            'icon' => 'fas fa-syringe',
            'url' => '#',
            'permission' => 'view_vaccines',
            'submenus' => [
                ['title' => 'All Vaccines', 'icon' => 'fas fa-list', 'url' => '/vaccines', 'permission' => 'view_vaccines'],
                ['title' => 'Add Vaccine', 'icon' => 'fas fa-plus', 'url' => '/vaccines/create', 'permission' => 'manage_vaccines'],
                ['title' => 'Vaccine Card', 'icon' => 'fas fa-id-card', 'url' => '/vaccines/card', 'permission' => 'view_vaccine_card']
            ]
        ],
        [
            'title' => 'User Management',
            'icon' => 'fas fa-users',
            'url' => '#',
            'permission' => 'view_users',
            'submenus' => [
                ['title' => 'Manage Users', 'icon' => 'fas fa-list', 'url' => '/admin/users', 'permission' => 'view_users'],
                ['title' => 'Add User', 'icon' => 'fas fa-user-plus', 'url' => '/admin/users/create', 'permission' => 'create_users'],
                ['title' => 'Roles & Permissions', 'icon' => 'fas fa-lock', 'url' => '/admin/users/roles', 'permission' => 'manage_roles'],
                ['title' => 'Attendance', 'icon' => 'fas fa-clock', 'url' => '/admin/users/attendance', 'permission' => 'view_attendance'],
                ['title' => 'Payroll', 'icon' => 'fas fa-money-bill', 'url' => '/admin/users/payroll', 'permission' => 'view_payroll']
            ]
        ],
        [
            'title' => 'Settings',
            'icon' => 'fas fa-cog',
            'url' => '#',
            'permission' => 'view_settings',
            'submenus' => [
                ['title' => 'System Settings', 'icon' => 'fas fa-sliders-h', 'url' => '/admin/settings', 'permission' => 'view_settings'],
                ['title' => 'Backup History', 'icon' => 'fas fa-database', 'url' => '/admin/settings/backup-history', 'permission' => 'manage_backup']
            ]
        ],
        [
            'title' => 'Audit Logs',
            'icon' => 'fas fa-history',
            'url' => '/admin/audit-logs',
            'permission' => 'view_audit'
        ]
    ];
}

// Filter menus by permissions
$allMenus = getSidebarMenus();
$filteredMenus = [];

foreach ($allMenus as $menu) {
    if (hasMenuAccess($menu['permission'], $userPermissions, $userRole)) {
        if (!empty($menu['submenus'])) {
            $filteredSubmenus = [];
            foreach ($menu['submenus'] as $submenu) {
                if (hasMenuAccess($submenu['permission'], $userPermissions, $userRole)) {
                    $filteredSubmenus[] = $submenu;
                }
            }
            if (!empty($filteredSubmenus)) {
                $menu['submenus'] = $filteredSubmenus;
                $filteredMenus[] = $menu;
            }
        } else {
            $filteredMenus[] = $menu;
        }
    }
}

if (empty($filteredMenus)) {
    $filteredMenus = [
        [
            'title' => 'No Access',
            'icon' => 'fas fa-lock',
            'url' => '#',
            'is_empty' => true
        ]
    ];
}
?>

<!-- Sidebar HTML -->
<div class="sidebar" id="sidebar">
    <div class="user-info">
        <div class="user-avatar">
            <i class="fas fa-user-md"></i>
        </div>
        <div class="user-details">
            <div class="user-name"><?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'System Admin'; ?></div>
            <div class="user-role"><?php echo isset($_SESSION['role_slug']) ? ucfirst(str_replace('_', ' ', $_SESSION['role_slug'])) : 'Super Admin'; ?></div>
        </div>
    </div>
    
    <div class="sidebar-menu-wrapper">
        <div class="sidebar-menu" id="sidebarMenu">
            <?php foreach($filteredMenus as $menu): ?>
                <?php if(isset($menu['is_empty']) && $menu['is_empty']): ?>
                    <div class="text-center text-muted small py-3 px-2">
                        <i class="fas fa-lock fa-2x mb-2 d-block"></i>
                        <span>No accessible menus</span>
                        <br>
                        <small>Contact administrator</small>
                    </div>
                <?php elseif(empty($menu['submenus'])): ?>
                    <a href="<?php echo BASE_URL . $menu['url']; ?>" class="menu-item" data-url="<?php echo $menu['url']; ?>">
                        <i class="<?php echo $menu['icon']; ?>"></i>
                        <span><?php echo htmlspecialchars($menu['title']); ?></span>
                    </a>
                <?php else: ?>
                    <div class="has-submenu" data-group="<?php echo strtolower(str_replace(' ', '_', $menu['title'])); ?>">
                        <div class="menu-item toggle-menu" data-url="#">
                            <i class="<?php echo $menu['icon']; ?>"></i>
                            <span><?php echo htmlspecialchars($menu['title']); ?></span>
                            <i class="fas fa-chevron-down chevron"></i>
                        </div>
                        <div class="submenu" style="display:none;">
                            <?php foreach($menu['submenus'] as $submenu): ?>
                                <a href="<?php echo BASE_URL . $submenu['url']; ?>" class="menu-item sub-link" data-url="<?php echo $submenu['url']; ?>">
                                    <i class="<?php echo $submenu['icon']; ?>"></i>
                                    <span><?php echo htmlspecialchars($submenu['title']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="sidebar-footer">
        &copy; <?php echo date('Y'); ?> BCPCC HMS. All rights reserved.
    </div>
</div>

<style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 280px;
        height: 100vh;
        background: #2c2c2c;
        transition: transform 0.3s ease;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }
    .sidebar-menu-wrapper {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 0 10px;
    }
    
    .user-info {
        padding: 20px 15px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        background: #2c2c2c;
        flex-shrink: 0;
    }
    .user-avatar {
        width: 50px;
        height: 50px;
        background: #1abc9c;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        flex-shrink: 0;
    }
    .user-details {
        flex: 1;
        overflow: hidden;
    }
    .user-name {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 3px;
        color: white;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .user-role {
        font-size: 11px;
        opacity: 0.7;
        color: #ccc;
        text-transform: capitalize;
    }
    
    .sidebar-menu {
        padding: 15px 0;
    }
    .menu-item {
        padding: 10px 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #e0e0e0;
        text-decoration: none;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        border-radius: 8px;
        margin-bottom: 2px;
        font-family: 'Cambria', 'Georgia', serif;
        border: none;
        background: transparent;
        width: 100%;
        text-align: left;
    }
    .menu-item i {
        width: 22px;
        font-size: 14px;
        text-align: center;
        flex-shrink: 0;
    }
    .menu-item span {
        flex: 1;
    }
    .menu-item .chevron {
        margin-left: auto;
        transition: transform 0.3s ease;
        font-size: 11px;
        flex-shrink: 0;
    }
    .menu-item:hover {
        background: #3a3a3a;
        color: white;
    }
    .menu-item.active {
        background: #1abc9c;
        color: white;
    }
    .menu-item.active .chevron {
        color: white;
    }
    
    .submenu {
        padding-left: 34px;
        display: none;
        background: transparent;
        padding-top: 5px;
        padding-bottom: 5px;
    }
    .submenu.open {
        display: block;
    }
    .submenu .menu-item {
        padding: 8px 12px;
        font-size: 13px;
        margin-bottom: 1px;
        border-left: 2px solid transparent;
        transition: all 0.3s ease;
    }
    .submenu .menu-item:hover {
        background: #3a3a3a;
        border-left-color: #1abc9c;
    }
    .submenu .menu-item.active {
        background: #1abc9c;
        color: white;
        border-left-color: #ffffff;
    }
    
    .has-submenu.open .menu-item .chevron {
        transform: rotate(180deg);
    }
    
    .sidebar-menu-wrapper::-webkit-scrollbar { width: 5px; }
    .sidebar-menu-wrapper::-webkit-scrollbar-track { background: #3a3a3a; border-radius: 5px; }
    .sidebar-menu-wrapper::-webkit-scrollbar-thumb { background: #1abc9c; border-radius: 5px; }
    
    .sidebar-footer {
        padding: 12px 15px;
        text-align: center;
        border-top: 1px solid rgba(255,255,255,0.1);
        font-size: 10px;
        color: #888;
        background: #2c2c2c;
        flex-shrink: 0;
        font-family: 'Cambria', 'Georgia', serif;
    }
</style>

<script>
// ============================================================
// SIDEBAR MENU TOGGLE - JQUERY VERSION (MORE RELIABLE)
// ============================================================
(function() {
    // Wait for jQuery to be ready
    function initSidebar() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initSidebar, 500);
            return;
        }
        
        var $ = jQuery;
        
        // Toggle submenu on click
        $('.toggle-menu').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var $parent = $(this).closest('.has-submenu');
            var $submenu = $parent.find('.submenu');
            var isOpen = $parent.hasClass('open');
            
            // Close all other submenus
            $('.has-submenu.open').not($parent).each(function() {
                $(this).removeClass('open');
                $(this).find('.submenu').removeClass('open').hide();
            });
            
            // Toggle current
            if (isOpen) {
                $parent.removeClass('open');
                $submenu.removeClass('open').slideUp(200);
            } else {
                $parent.addClass('open');
                $submenu.addClass('open').slideDown(200);
            }
        });
        
        // Highlight active menu items
        var currentUrl = window.location.pathname;
        $('.menu-item[data-url]').each(function() {
            var url = $(this).attr('data-url');
            if (url && url !== '#' && currentUrl.indexOf(url) !== -1) {
                $(this).addClass('active');
                
                // Open parent submenu if this is a sub-link
                var $parentSubmenu = $(this).closest('.submenu');
                if ($parentSubmenu.length) {
                    $parentSubmenu.addClass('open').show();
                    var $parentHasSubmenu = $parentSubmenu.closest('.has-submenu');
                    if ($parentHasSubmenu.length) {
                        $parentHasSubmenu.addClass('open');
                    }
                }
            }
        });
        
        console.log('Sidebar initialized successfully');
    }
    
    // Start initialization
    if (document.readyState === 'complete') {
        initSidebar();
    } else {
        document.addEventListener('readystatechange', function() {
            if (document.readyState === 'complete') {
                initSidebar();
            }
        });
    }
})();
</script>