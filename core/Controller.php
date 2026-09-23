<?php
// core/Controller.php - COMPLETE FILE WITH FIXES

if (!class_exists('Controller')) {

    class Controller {
        protected $db;
        
        public function __construct() {
            global $conn;
            $this->db = $conn;
            
            // Ensure session is started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        }
        
        // ============================================================
        // FIX: LOAD MODEL WITH FIND METHOD
        // ============================================================
        
        /**
         * Load a model class
         * Usage: $model = $this->model('Appointment');
         */
        protected function model($modelName) {
            $modelFile = BASE_PATH . '/app/models/' . $modelName . '.php';
            if (file_exists($modelFile)) {
                require_once $modelFile;
                if (class_exists($modelName)) {
                    return new $modelName($this->db);
                }
            }
            return null;
        }
        
        /**
         * Find a record by ID using a model
         * Usage: $record = $this->findModel('Appointment', $id);
         */
        protected function findModel($modelName, $id) {
            $model = $this->model($modelName);
            if ($model && method_exists($model, 'find')) {
                return $model->find($id);
            }
            // Fallback: direct query
            if ($modelName === 'Appointment') {
                $table = 'appointments';
            } elseif ($modelName === 'Patient') {
                $table = 'patients';
            } elseif ($modelName === 'Doctor') {
                $table = 'doctors';
            } elseif ($modelName === 'Prescription') {
                $table = 'prescriptions';
            } else {
                $table = strtolower($modelName) . 's';
            }
            
            $query = "SELECT * FROM $table WHERE id = " . (int)$id;
            $result = $this->db->query($query);
            if ($result && $result->num_rows > 0) {
                return $result->fetch_assoc();
            }
            return null;
        }
        
        // ============================================================
        // REST OF YOUR EXISTING METHODS
        // ============================================================
        
        /**
         * RENDER A VIEW WITH FULL LAYOUT
         */
        protected function view($view, $data = [], $title = '') {
            extract($data);
            ob_start();
            $viewFile = BASE_PATH . '/app/views/' . $view . '.php';
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo "View not found: " . $view;
            }
            $content = ob_get_clean();
            $pageTitle = $title ?: ucwords(str_replace('/', ' ', $view));
            $this->renderLayout($pageTitle, $content);
        }
        
        /**
         * RENDER VIEW WITHOUT LAYOUT
         */
        protected function renderView($viewPath, $data = []) {
            extract($data);
            ob_start();
            $viewFile = BASE_PATH . '/app/views/' . $viewPath . '.php';
            if (file_exists($viewFile)) {
                include $viewFile;
            } else {
                echo "View not found: " . $viewPath;
            }
            return ob_get_clean();
        }

        /**
         * Check if user has specific permission
         */
        protected function checkPermission($permission) {
            if (isset($_SESSION['role_slug']) && ($_SESSION['role_slug'] == 'super_admin' || $_SESSION['role_slug'] == 'admin')) {
                return true;
            }
            if (isset($_SESSION['permissions']) && in_array($permission, $_SESSION['permissions'])) {
                return true;
            }
            $this->redirect('/403');
            exit;
        }

        /**
         * Check if user has any of the given permissions
         */
        protected function checkAnyPermission($permissions) {
            if (isset($_SESSION['role_slug']) && ($_SESSION['role_slug'] == 'super_admin' || $_SESSION['role_slug'] == 'admin')) {
                return true;
            }
            if (isset($_SESSION['permissions'])) {
                foreach ($permissions as $permission) {
                    if (in_array($permission, $_SESSION['permissions'])) {
                        return true;
                    }
                }
            }
            $this->redirect('/403');
            exit;
        }
        
        /**
         * RENDER LAYOUT WITH CONTENT (With Sidebar)
         */
        protected function renderLayout($pageTitle, $content) {
            $active_menu = $this->getActiveMenu();
            $layoutFile = BASE_PATH . '/app/views/layouts/main.php';
            if (file_exists($layoutFile)) {
                include $layoutFile;
            } else {
                $userName = $_SESSION['user_name'] ?? 'User';
                $roleSlug = $_SESSION['role_slug'] ?? 'admin';
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title><?php echo $pageTitle; ?> - UniDia HMS</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
                    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                </head>
                <body>
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
                                    <div class="container-fluid">
                                        <a class="navbar-brand" href="#">UniDia HMS</a>
                                        <span class="navbar-text text-white">
                                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($userName); ?>
                                            <span class="badge bg-info ms-2"><?php echo ucfirst(str_replace('_', ' ', $roleSlug)); ?></span>
                                        </span>
                                        <div class="ms-auto">
                                            <a href="<?php echo BASE_URL; ?>/logout" class="btn btn-danger btn-sm">
                                                <i class="fas fa-sign-out-alt me-1"></i> Logout
                                            </a>
                                        </div>
                                    </div>
                                </nav>
                                <?php echo $content; ?>
                            </div>
                        </div>
                    </div>
                    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
                </body>
                </html>
                <?php
            }
        }
        
        /**
         * RENDER LOGIN LAYOUT (Without Sidebar)
         */
        protected function renderLoginLayout($pageTitle, $content) {
            $layoutFile = BASE_PATH . '/app/views/layouts/login.php';
            if (file_exists($layoutFile)) {
                include $layoutFile;
            } else {
                ?>
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title><?php echo $pageTitle; ?> - UniDia HMS</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
                    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                    <style>
                        body {
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            min-height: 100vh;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            font-family: 'Inter', sans-serif;
                            margin: 0;
                            padding: 20px;
                        }
                        .login-card {
                            background: white;
                            border-radius: 20px;
                            padding: 40px;
                            width: 100%;
                            max-width: 420px;
                            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
                        }
                        .login-card .logo {
                            text-align: center;
                            margin-bottom: 30px;
                        }
                        .login-card .logo i {
                            font-size: 48px;
                            color: #667eea;
                            background: rgba(102, 126, 234, 0.1);
                            padding: 15px;
                            border-radius: 15px;
                        }
                        .login-card .logo h4 {
                            margin-top: 15px;
                            font-weight: 700;
                            color: #1a202c;
                        }
                        .login-card .logo p {
                            color: #718096;
                            font-size: 14px;
                        }
                        .form-control {
                            border-radius: 10px;
                            padding: 12px 15px;
                            border: 2px solid #e2e8f0;
                            font-size: 14px;
                        }
                        .form-control:focus {
                            border-color: #667eea;
                            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
                        }
                        .btn-login {
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                            border: none;
                            border-radius: 10px;
                            padding: 12px;
                            font-weight: 600;
                            font-size: 16px;
                            width: 100%;
                            color: white;
                            transition: all 0.3s;
                        }
                        .btn-login:hover {
                            transform: translateY(-2px);
                            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
                            color: white;
                        }
                        .alert {
                            border-radius: 10px;
                            font-size: 14px;
                            margin-bottom: 20px;
                        }
                        .form-check-label {
                            font-size: 14px;
                            color: #4a5568;
                        }
                        .footer-text {
                            text-align: center;
                            margin-top: 20px;
                            font-size: 13px;
                            color: #a0aec0;
                        }
                        .input-group-text {
                            border-radius: 10px 0 0 10px;
                            border: 2px solid #e2e8f0;
                            border-right: none;
                            background: white;
                        }
                        .input-group .form-control {
                            border-radius: 0 10px 10px 0;
                            border-left: none;
                        }
                        .input-group:focus-within .input-group-text,
                        .input-group:focus-within .form-control {
                            border-color: #667eea;
                        }
                    </style>
                </head>
                <body>
                    <?php echo $content; ?>
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
                </body>
                </html>
                <?php
            }
        }
        
        /**
         * RENDER AUTH LAYOUT (Without Sidebar) - Uses auth.php layout
         */
        protected function renderAuthLayout($pageTitle, $content) {
            $layoutFile = BASE_PATH . '/app/views/layouts/auth.php';
            if (file_exists($layoutFile)) {
                include $layoutFile;
            } else {
                // Fallback to login layout if auth.php doesn't exist
                $this->renderLoginLayout($pageTitle, $content);
            }
        }
        
        /**
         * REDIRECT TO A URL
         */
        protected function redirect($url) {
            // Ensure BASE_URL is defined
            if (!defined('BASE_URL')) {
                define('BASE_URL', '/unidia/public');
            }
            header("Location: " . BASE_URL . $url);
            exit;
        }
        
        /**
         * CHECK IF USER IS AUTHENTICATED
         */
        protected function checkAuth() {
            if (!isset($_SESSION['user_id'])) {
                $this->redirect('/login');
                exit;
            }
        }
        
        /**
         * REQUIRE USER TO BE LOGGED IN
         */
        protected function requireLogin() {
            if (!isset($_SESSION['user_id'])) {
                $this->redirect('/login');
                exit;
            }
        }
        
        /**
         * RETURN JSON RESPONSE
         */
        protected function json($data) {
            header('Content-Type: application/json');
            echo json_encode($data);
            exit;
        }
        
        /**
         * SET FLASH MESSAGE
         */
        protected function setFlash($type, $message) {
            $_SESSION['flash'] = [
                'type' => $type,
                'message' => $message
            ];
        }
        
        /**
         * GET AND CLEAR FLASH MESSAGE
         */
        protected function getFlash() {
            if (isset($_SESSION['flash'])) {
                $flash = $_SESSION['flash'];
                unset($_SESSION['flash']);
                return $flash;
            }
            return null;
        }
        
        /**
         * GET ACTIVE MENU ITEM BASED ON CURRENT URL
         */
        private function getActiveMenu() {
            $request = $_SERVER['REQUEST_URI'];
            if(strpos($request, 'admin/dashboard') !== false) return 'dashboard';
            if(strpos($request, 'admin/users') !== false) return 'users';
            if(strpos($request, 'patient/') !== false) return 'patients';
            if(strpos($request, 'doctor/') !== false) return 'doctors';
            if(strpos($request, 'reception/') !== false) return 'appointments';
            if(strpos($request, 'bills') !== false || strpos($request, 'billing') !== false) return 'billing';
            if(strpos($request, 'pharmacy') !== false) return 'pharmacy';
            if(strpos($request, 'lab') !== false) return 'laboratory';
            if(strpos($request, 'inventory') !== false) return 'inventory';
            if(strpos($request, 'audit') !== false) return 'audit';
            if(strpos($request, 'settings') !== false) return 'settings';
            return '';
        }
    }
}
?>