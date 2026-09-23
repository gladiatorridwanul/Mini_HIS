<?php
class AuthMiddleware {
    public static function handle() {
        if(!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
    
    public static function role($roles) {
        self::handle();
        
        if(!in_array($_SESSION['role_slug'], (array)$roles)) {
            header('HTTP/1.0 403 Forbidden');
            die('Access Denied');
        }
    }
    
    public static function guest() {
        if(isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . '/' . $_SESSION['role_slug'] . '/dashboard');
            exit;
        }
    }
    
    public static function checkPermission($permission) {
        if(!isset($_SESSION['permissions'])) {
            $userModel = new User();
            $permissions = $userModel->getPermissions($_SESSION['user_id']);
            $_SESSION['permissions'] = json_decode($permissions, true);
        }
        
        return isset($_SESSION['permissions'][$permission]) && $_SESSION['permissions'][$permission] === true;
    }
}