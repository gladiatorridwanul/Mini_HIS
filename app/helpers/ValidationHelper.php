<?php
class ValidationHelper {
    public static function sanitize($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function validatePhone($phone) {
        return preg_match('/^[0-9]{10,15}$/', $phone);
    }
    
    public static function validateRequired($fields, $data) {
        $errors = [];
        foreach($fields as $field) {
            if(empty($data[$field])) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        return $errors;
    }
    
    public static function validateNumeric($value) {
        return is_numeric($value);
    }
    
    public static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
}