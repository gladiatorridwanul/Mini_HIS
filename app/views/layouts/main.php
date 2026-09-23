<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'UniDia HMS'; ?> - BCPCC HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cambria&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/responsive.css">
    <style>
        /* Reset and Base Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #e0e0e0; 
            font-family: 'Cambria', 'Georgia', serif; 
            overflow-x: hidden; 
        }
        
        /* Sidebar Styles */
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
        
        /* User Info */
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
        
        /* Menu Styles */
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
        
        /* Submenu Styles */
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
        
        /* Scrollbar */
        .sidebar-menu-wrapper::-webkit-scrollbar { width: 5px; }
        .sidebar-menu-wrapper::-webkit-scrollbar-track { background: #3a3a3a; border-radius: 5px; }
        .sidebar-menu-wrapper::-webkit-scrollbar-thumb { background: #1abc9c; border-radius: 5px; }
        
        /* Sidebar Footer */
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
        
        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 20px 25px;
            min-height: 100vh;
            background: #e0e0e0;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            font-family: 'Cambria', 'Georgia', serif;
        }
        .logout-btn:hover {
            background: #c82333;
            color: white;
            text-decoration: none;
        }
        
        /* Mobile Menu Toggle */
        .menu-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 45px;
            height: 45px;
            background: #1abc9c;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            cursor: pointer;
            z-index: 1100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            border: none;
        }
        .menu-toggle:hover {
            background: #16a085;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .menu-toggle {
                display: flex;
            }
            .top-bar {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            .page-title {
                font-size: 16px;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 10px;
            }
            .top-bar {
                padding: 10px 15px;
            }
            .logout-btn {
                font-size: 12px;
                padding: 4px 12px;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .main-content > * {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Alert/Notification styles */
        .alert {
            border-radius: 8px;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        /* Card styles */
        .card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            font-family: 'Cambria', 'Georgia', serif;
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 10px 10px 0 0 !important;
        }
        
        /* Table styles */
        .table {
            font-family: 'Cambria', 'Georgia', serif;
        }
        .table thead th {
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        /* Button styles */
        .btn {
            font-family: 'Cambria', 'Georgia', serif;
            border-radius: 6px;
        }
        
        /* Form styles */
        .form-control, .form-select {
            font-family: 'Cambria', 'Georgia', serif;
            border-radius: 6px;
        }
        .form-label {
            font-weight: 500;
            font-family: 'Cambria', 'Georgia', serif;
        }
        
        /* Badge styles */
        .badge {
            font-family: 'Cambria', 'Georgia', serif;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <!-- Sidebar - Using dynamic sidebar.php -->
    <?php include BASE_PATH . '/app/views/layouts/sidebar.php'; ?>
    
    <!-- Mobile Menu Toggle -->
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <h4 class="page-title"><?php echo $pageTitle ?? 'Dashboard'; ?></h4>
            <a href="<?php echo BASE_URL; ?>/logout" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <!-- Display Flash Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['warning'])): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['warning']; unset($_SESSION['warning']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['info'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo $_SESSION['info']; unset($_SESSION['info']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php echo $content ?? ''; ?>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/prescription.js"></script>
    
    <script>
    // ============================================================
    // MOBILE MENU TOGGLE
    // ============================================================
    (function() {
        var menuToggle = document.getElementById('menuToggle');
        var sidebar = document.getElementById('sidebar');
        
        if (menuToggle) {
            menuToggle.addEventListener('click', function() {
                sidebar.classList.toggle('open');
                if (sidebar.classList.contains('open')) {
                    menuToggle.innerHTML = '<i class="fas fa-times"></i>';
                } else {
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            });
        }
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 768) {
                var isClickInside = sidebar.contains(event.target) || menuToggle.contains(event.target);
                if (!isClickInside && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
                }
            }
        });
        
        // Handle window resize for sidebar
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
                menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            }
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                var closeBtn = alert.querySelector('.btn-close');
                if (closeBtn) {
                    closeBtn.click();
                }
            });
        }, 5000);
    })();
    </script>
</body>
</html>