<?php
class SessionHelper {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public static function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public static function remove($key) {
        unset($_SESSION[$key]);
    }
    
    public static function destroy() {
        session_destroy();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public static function hasRole($roles) {
        if (!self::isLoggedIn()) return false;
        
        if (is_array($roles)) {
            return in_array($_SESSION['role_slug'], $roles);
        }
        
        return $_SESSION['role_slug'] === $roles;
    }
    
    public static function setFlash($type, $message) {
        $_SESSION['flash'][$type] = $message;
    }
    
    public static function getFlash($type) {
        $message = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $message;
    }
}