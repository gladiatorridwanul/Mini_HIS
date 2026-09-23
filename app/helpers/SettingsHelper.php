<?php
class SettingsHelper {
    
    public static function get($key, $default = null) {
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetch();
        
        return $result['setting_value'] ?? $default;
    }
    
    public static function set($key, $value, $type = 'text', $description = null) {
        $db = Database::getInstance()->getConnection();
        
        $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_type, description) 
                VALUES (:key, :value, :type, :description) 
                ON DUPLICATE KEY UPDATE setting_value = :value2, updated_at = NOW()";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'key' => $key,
            'value' => $value,
            'type' => $type,
            'description' => $description,
            'value2' => $value
        ]);
    }
    
    public static function getAll() {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM system_settings ORDER BY setting_key";
        $stmt = $db->query($sql);
        return $stmt->fetchAll();
    }
    
    public static function getGrouped() {
        $settings = self::getAll();
        $grouped = [
            'general' => [],
            'email' => [],
            'billing' => [],
            'appointment' => [],
            'notification' => []
        ];
        
        foreach ($settings as $setting) {
            $key = $setting['setting_key'];
            
            if (strpos($key, 'smtp_') === 0) {
                $grouped['email'][] = $setting;
            } elseif (in_array($key, ['currency', 'tax_percentage'])) {
                $grouped['billing'][] = $setting;
            } elseif (strpos($key, 'appointment_') === 0) {
                $grouped['appointment'][] = $setting;
            } elseif (strpos($key, 'enable_') === 0) {
                $grouped['notification'][] = $setting;
            } else {
                $grouped['general'][] = $setting;
            }
        }
        
        return $grouped;
    }
    
    public static function getAppName() {
        return self::get('site_name', 'UniDia Healthcare');
    }
    
    public static function getCurrency() {
        return self::get('currency', 'USD');
    }
    
    public static function getTaxPercentage() {
        return (float)self::get('tax_percentage', 5);
    }
    
    public static function isMaintenanceMode() {
        return self::get('maintenance_mode', '0') === '1';
    }
}