<?php
/**
 * CommonHelper.php
 * UniDia Hospital Management System - Common Helper Functions
 * Version: 1.0.1
 */

class CommonHelper {
    
    private static $instance = null;
    private $db;
    
    private function __construct() {
        $dbInstance = Database::getInstance();
        $this->db = $dbInstance->getConnection();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new CommonHelper();
        }
        return self::$instance;
    }
    
    // =============================================
    // DATE & TIME FUNCTIONS
    // =============================================
    
    /**
     * Format date to readable format
     */
    public static function formatDate($date, $format = 'd M Y') {
        if (empty($date) || $date == '0000-00-00') {
            return 'N/A';
        }
        return date($format, strtotime($date));
    }
    
    /**
     * Format datetime to readable format
     */
    public static function formatDateTime($datetime, $format = 'd M Y h:i A') {
        if (empty($datetime)) {
            return 'N/A';
        }
        return date($format, strtotime($datetime));
    }
    
    /**
     * Calculate age from date of birth
     */
    public static function calculateAge($dob) {
        if (empty($dob)) {
            return 'N/A';
        }
        $birthDate = new DateTime($dob);
        $today = new DateTime();
        $age = $today->diff($birthDate);
        return $age->y . ' years';
    }
    
    /**
     * Get days difference between two dates
     */
    public static function daysDifference($startDate, $endDate = null) {
        $end = $endDate ? new DateTime($endDate) : new DateTime();
        $start = new DateTime($startDate);
        $diff = $end->diff($start);
        return $diff->days;
    }
    
    /**
     * Check if date is expired
     */
    public static function isExpired($date) {
        if (empty($date)) return false;
        return strtotime($date) < time();
    }
    
    /**
     * Get time ago string
     */
    public static function timeAgo($datetime) {
        $timestamp = strtotime($datetime);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return $diff . ' seconds ago';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' minutes ago';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' hours ago';
        } elseif ($diff < 604800) {
            return floor($diff / 86400) . ' days ago';
        } elseif ($diff < 2592000) {
            return floor($diff / 604800) . ' weeks ago';
        } elseif ($diff < 31536000) {
            return floor($diff / 2592000) . ' months ago';
        }
        return floor($diff / 31536000) . ' years ago';
    }
    
    // =============================================
    // FORMATTING FUNCTIONS
    // =============================================
    
    /**
     * Format currency
     */
    public static function formatCurrency($amount, $currency = '$') {
        if ($amount === null) $amount = 0;
        return $currency . number_format((float)$amount, 2);
    }
    
    /**
     * Format phone number
     */
    public static function formatPhone($phone) {
        if (empty($phone)) return 'N/A';
        // For 10-digit phone numbers
        if (strlen($phone) == 10) {
            return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
        }
        return $phone;
    }
    
    /**
     * Truncate text
     */
    public static function truncate($text, $length = 50, $suffix = '...') {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Generate random string
     */
    public static function randomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }
    
    /**
     * Generate unique code
     */
    public static function generateCode($prefix, $table, $column, $length = 6) {
        $db = Database::getInstance()->getConnection();
        $code = $prefix . date('Ymd') . str_pad(rand(0, 999999), $length, '0', STR_PAD_LEFT);
        
        // Check if code exists
        $check = $db->query("SELECT id FROM $table WHERE $column = '$code'");
        if ($check && $check->num_rows > 0) {
            return self::generateCode($prefix, $table, $column, $length);
        }
        return $code;
    }
    
    // =============================================
    // STATUS & BADGE FUNCTIONS
    // =============================================
    
    /**
     * Get badge HTML for status
     */
    public static function getStatusBadge($status) {
        $badges = [
            'active' => '<span class="badge bg-success">Active</span>',
            'inactive' => '<span class="badge bg-danger">Inactive</span>',
            'pending' => '<span class="badge bg-warning">Pending</span>',
            'completed' => '<span class="badge bg-success">Completed</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            'scheduled' => '<span class="badge bg-primary">Scheduled</span>',
            'checked_in' => '<span class="badge bg-info">Checked In</span>',
            'in_progress' => '<span class="badge bg-warning">In Progress</span>',
            'no_show' => '<span class="badge bg-dark">No Show</span>',
            'paid' => '<span class="badge bg-success">Paid</span>',
            'partial' => '<span class="badge bg-info">Partial</span>',
            'refunded' => '<span class="badge bg-secondary">Refunded</span>',
            'present' => '<span class="badge bg-success">Present</span>',
            'absent' => '<span class="badge bg-danger">Absent</span>',
            'late' => '<span class="badge bg-warning">Late</span>',
            'leave' => '<span class="badge bg-secondary">Leave</span>',
            'half_day' => '<span class="badge bg-info">Half Day</span>'
        ];
        
        return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
    
    /**
     * Get role badge color
     */
    public static function getRoleBadgeColor($role) {
        $colors = [
            'super_admin' => 'danger',
            'admin' => 'warning',
            'doctor' => 'info',
            'receptionist' => 'success',
            'pharmacist' => 'purple',
            'lab_technician' => 'teal',
            'accountant' => 'orange',
            'nurse' => 'primary'
        ];
        return $colors[$role] ?? 'secondary';
    }
    
    /**
     * Get blood group badge
     */
    public static function getBloodGroupBadge($bloodGroup) {
        if (empty($bloodGroup)) return '<span class="badge bg-secondary">N/A</span>';
        
        $colors = [
            'A+' => 'danger',
            'A-' => 'danger',
            'B+' => 'primary',
            'B-' => 'primary',
            'AB+' => 'success',
            'AB-' => 'success',
            'O+' => 'warning',
            'O-' => 'warning'
        ];
        
        $color = $colors[$bloodGroup] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . $bloodGroup . '</span>';
    }
    
    // =============================================
    // VALIDATION FUNCTIONS
    // =============================================
    
    /**
     * Validate email
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number
     */
    public static function isValidPhone($phone) {
        return preg_match('/^[0-9+\-\s()]{10,15}$/', $phone);
    }
    
    /**
     * Validate date format
     */
    public static function isValidDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Sanitize input
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    // =============================================
    // FILE & IMAGE FUNCTIONS
    // =============================================
    
    /**
     * Upload file
     */
    public static function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf']) {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload failed'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
        
        $filename = time() . '_' . self::randomString(6) . '.' . $extension;
        $uploadPath = $destination . $filename;
        
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => true, 'filename' => $filename, 'path' => $uploadPath];
        }
        
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
    
    /**
     * Delete file
     */
    public static function deleteFile($filePath) {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }
    
    /**
     * Get file size readable
     */
    public static function fileSizeReadable($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
    
    // =============================================
    // DASHBOARD URL FUNCTIONS
    // =============================================
    
    /**
     * Get dashboard URL based on role
     */
    public static function getDashboardUrl($role) {
        $urls = [
            'super_admin' => '/admin/dashboard',
            'admin' => '/admin/dashboard',
            'doctor' => '/doctor/dashboard',
            'receptionist' => '/reception/dashboard',
            'pharmacist' => '/pharmacy/dashboard',
            'lab_technician' => '/lab/dashboard',
            'accountant' => '/account/dashboard',
            'nurse' => '/nurse/dashboard'
        ];
        return $urls[$role] ?? '/admin/dashboard';
    }
    
    // =============================================
    // ENCRYPTION FUNCTIONS
    // =============================================
    
    /**
     * Simple encryption (for non-sensitive data)
     */
    public static function simpleEncrypt($data, $key = 'UniDiaSecretKey2024') {
        return base64_encode(openssl_encrypt($data, 'AES-128-CBC', $key, 0, substr($key, 0, 16)));
    }
    
    /**
     * Simple decryption
     */
    public static function simpleDecrypt($data, $key = 'UniDiaSecretKey2024') {
        return openssl_decrypt(base64_decode($data), 'AES-128-CBC', $key, 0, substr($key, 0, 16));
    }
    
    // =============================================
    // REPORTING FUNCTIONS
    // =============================================
    
    /**
     * Generate CSV from array
     */
    public static function arrayToCsv($data, $filename = 'export.csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add headers
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            
            // Add data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Generate PDF (placeholder - requires library)
     */
    public static function generatePdf($html, $filename = 'document.pdf') {
        // This is a placeholder. In production, use Dompdf, TCPDF, or mpdf
        // For now, just output HTML
        header('Content-Type: text/html');
        echo $html;
        exit;
    }
    
    // =============================================
    // NOTIFICATION FUNCTIONS
    // =============================================
    
    /**
     * Set flash message
     */
    public static function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
            'time' => time()
        ];
    }
    
    /**
     * Get flash message
     */
    public static function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
    
    /**
     * Display flash message
     */
    public static function displayFlash() {
        $flash = self::getFlash();
        if ($flash) {
            $alertClass = $flash['type'] === 'success' ? 'alert-success' : 
                         ($flash['type'] === 'error' ? 'alert-danger' : 'alert-info');
            echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                    <i class="fas fa-' . ($flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle') . ' me-2"></i>
                    ' . htmlspecialchars($flash['message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
        }
    }
    
    // =============================================
    // DATABASE HELPER FUNCTIONS
    // =============================================
    
    /**
     * Get table count
     */
    public function getTableCount($table, $where = '1=1') {
        $query = "SELECT COUNT(*) as count FROM $table WHERE $where";
        $result = $this->db->query($query);
        if ($result) {
            return $result->fetch_assoc()['count'];
        }
        return 0;
    }
    
    /**
     * Get sum of column
     */
    public function getSum($table, $column, $where = '1=1') {
        $query = "SELECT COALESCE(SUM($column), 0) as total FROM $table WHERE $where";
        $result = $this->db->query($query);
        if ($result) {
            return $result->fetch_assoc()['total'];
        }
        return 0;
    }
    
    // =============================================
    // LOGGING FUNCTIONS
    // =============================================
    
    /**
     * Log activity
     */
    public static function logActivity($userId, $action, $module, $details = null) {
        $db = Database::getInstance()->getConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $details = $details ? $db->real_escape_string(json_encode($details)) : 'NULL';
        
        $query = "INSERT INTO audit_logs (user_id, action, module, old_value, ip_address, user_agent) 
                  VALUES ($userId, '$action', '$module', " . ($details !== 'NULL' ? "'$details'" : "NULL") . ", '$ip', '" . $db->real_escape_string($userAgent) . "')";
        
        return $db->query($query);
    }
    
    // =============================================
    // UTILITY FUNCTIONS
    // =============================================
    
    /**
     * Get user IP address
     */
    public static function getClientIP() {
        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    
    /**
     * Get browser info
     */
    public static function getBrowser() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $browser = 'Unknown';
        
        if (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
        elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
        elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
        elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
        elseif (strpos($userAgent, 'Opera') !== false) $browser = 'Opera';
        elseif (strpos($userAgent, 'MSIE') !== false) $browser = 'Internet Explorer';
        
        return $browser;
    }
    
    /**
     * Get OS info
     */
    public static function getOS() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $os = 'Unknown';
        
        if (strpos($userAgent, 'Windows') !== false) $os = 'Windows';
        elseif (strpos($userAgent, 'Mac') !== false) $os = 'Mac';
        elseif (strpos($userAgent, 'Linux') !== false) $os = 'Linux';
        elseif (strpos($userAgent, 'Android') !== false) $os = 'Android';
        elseif (strpos($userAgent, 'iOS') !== false) $os = 'iOS';
        
        return $os;
    }

    function calculateAge($dob) {
    if (empty($dob) || $dob == '0000-00-00') {
        return 'N/A';
    }
    
    try {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        $age = $birthDate->diff($today);
        
        $parts = [];
        if ($age->y > 0) {
            $parts[] = $age->y . ' Y';
        }
        if ($age->m > 0) {
            $parts[] = $age->m . ' M';
        }
        if ($age->d > 0 && $age->y == 0 && $age->m == 0) {
            $parts[] = $age->d . ' D';
        }
        
        return !empty($parts) ? implode(' ', $parts) : '0 D';
    } catch (Exception $e) {
        return 'N/A';
    }
}


}
?>