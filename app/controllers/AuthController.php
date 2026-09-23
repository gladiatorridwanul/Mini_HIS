<?php
// app/controllers/AuthController.php

require_once BASE_PATH . '/core/Controller.php';

class AuthController extends Controller {
    
    public function __construct() {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * SHOW LOGIN PAGE - WITHOUT SIDEBAR
     */
    public function showLogin() {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/admin/dashboard');
            return;
        }
        
        // Check for error message from session
        $error = isset($_SESSION['login_error']) ? $_SESSION['login_error'] : '';
        unset($_SESSION['login_error']);
        
        // Render login view WITHOUT sidebar using auth layout
        $content = $this->renderView('auth/login', ['error' => $error]);
        $this->renderAuthLayout('Login', $content);
        exit;
    }
    
    /**
     * HANDLE LOGIN
     */
    public function doLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        error_log("=== LOGIN ATTEMPT ===");
        error_log("Email: " . $email);
        
        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Please enter email and password';
            $this->redirect('/login');
            return;
        }
        
        if (!$this->db) {
            error_log("Database connection failed!");
            $_SESSION['login_error'] = 'Database connection failed.';
            $this->redirect('/login');
            return;
        }
        
        $email = $this->db->real_escape_string($email);
        
        $query = "SELECT u.*, r.slug as role_slug, r.name as role_name 
                  FROM users u 
                  LEFT JOIN roles r ON u.role_id = r.id 
                  WHERE u.email = '$email' AND u.status = 'active'";
        
        error_log("Query: " . $query);
        $result = $this->db->query($query);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            error_log("User found: " . $user['first_name'] . ' ' . $user['last_name']);
            error_log("Stored password hash: " . $user['password']);
            
            $passwordValid = password_verify($password, $user['password']);
            error_log("Password verify: " . ($passwordValid ? 'TRUE' : 'FALSE'));
            
            if (!$passwordValid && $password === $user['password']) {
                error_log("Plain text match! Updating...");
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $this->db->query("UPDATE users SET password = '$newHash' WHERE id = {$user['id']}");
                $passwordValid = true;
            }
            
            if ($passwordValid) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_slug'] = $user['role_slug'] ?? 'admin';
                $_SESSION['role_name'] = $user['role_name'] ?? 'Admin';
                $_SESSION['login_time'] = date('Y-m-d H:i:s');
                
                $this->db->query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");
                
                error_log("=== LOGIN SUCCESS ===");
                error_log("User: " . $_SESSION['user_name']);
                error_log("Role: " . $_SESSION['role_slug']);
                
                $role = $user['role_slug'] ?? 'admin';
                if ($role == 'super_admin' || $role == 'admin') {
                    $this->redirect('/admin/dashboard');
                } elseif ($role == 'doctor') {
                    $this->redirect('/doctor/dashboard');
                } elseif ($role == 'receptionist') {
                    $this->redirect('/reception/dashboard');
                } elseif ($role == 'pharmacist') {
                    $this->redirect('/pharmacy/dashboard');
                } elseif ($role == 'lab_technician') {
                    $this->redirect('/lab/dashboard');
                } else {
                    $this->redirect('/admin/dashboard');
                }
                return;
            } else {
                error_log("=== LOGIN FAILED: Invalid password ===");
                $_SESSION['login_error'] = 'Invalid password. Please try again.';
                $this->redirect('/login');
                return;
            }
        } else {
            error_log("=== LOGIN FAILED: User not found ===");
            $_SESSION['login_error'] = 'User not found or inactive.';
            $this->redirect('/login');
            return;
        }
    }
    
    /**
     * LOGOUT
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->db->query("UPDATE users SET last_login = NULL WHERE id = {$_SESSION['user_id']}");
        }
        $_SESSION = array();
        session_destroy();
        header('Location: /unidia/public/login');
        exit;
    }
    
    /**
     * RENDER AUTH LAYOUT (Without Sidebar)
     * Uses the auth.php layout file
     */
    protected function renderAuthLayout($pageTitle, $content) {
        $layoutFile = BASE_PATH . '/app/views/layouts/auth.php';
        if (file_exists($layoutFile)) {
            // The auth.php layout expects $pageTitle and $content variables
            include $layoutFile;
        } else {
            // Fallback if auth.php doesn't exist
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
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { 
                        font-family: 'Inter', sans-serif; 
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        min-height: 100vh;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 20px;
                    }
                    .auth-container { max-width: 420px; width: 100%; }
                    .auth-card {
                        background: white;
                        border-radius: 20px;
                        padding: 40px 35px;
                        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
                    }
                    .auth-logo { text-align: center; margin-bottom: 30px; }
                    .auth-logo i { font-size: 48px; color: #667eea; }
                    .auth-logo h3 { font-weight: 700; color: #1a1a2e; margin-top: 10px; }
                    .auth-logo p { color: #94a3b8; font-size: 14px; }
                    .form-control {
                        border-radius: 10px;
                        padding: 12px 16px;
                        border: 2px solid #e2e8f0;
                    }
                    .form-control:focus {
                        border-color: #667eea;
                        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
                    }
                    .btn-login {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        border: none;
                        padding: 12px;
                        border-radius: 10px;
                        font-weight: 600;
                        width: 100%;
                        color: white;
                    }
                    .btn-login:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
                    .alert { border-radius: 10px; }
                    .footer-text { text-align: center; margin-top: 20px; color: #94a3b8; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="auth-container">
                    <div class="auth-card">
                        <?php echo $content; ?>
                    </div>
                    <div class="footer-text">© <?php echo date('Y'); ?> UniDia HMS. All rights reserved.</div>
                </div>
            </body>
            </html>
            <?php
        }
    }
}