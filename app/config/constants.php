<?php
// Application Constants
define('APP_NAME', 'UniDia Application');
define('APP_VERSION', 'V-1.0.1');
define('APP_AUTHOR', 'UniDia Healthcare');
define('APP_YEAR', '2024');

// Upload Paths
define('UPLOAD_DIR', ROOT_PATH . '/public/uploads/');
define('PROFILE_PHOTOS', UPLOAD_DIR . 'profiles/');
define('PATIENT_DOCUMENTS', UPLOAD_DIR . 'patients/');
define('DOCTOR_SIGNATURES', UPLOAD_DIR . 'signatures/');
define('LAB_REPORTS', UPLOAD_DIR . 'lab_reports/');
define('INVOICES', UPLOAD_DIR . 'invoices/');

// File Upload Limits
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);

// Pagination
define('ITEMS_PER_PAGE', 25);
define('MAX_ITEMS_PER_PAGE', 100);

// Date Formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y');
define('DISPLAY_TIME_FORMAT', 'h:i A');

// Currency
define('CURRENCY_SYMBOL', '$');
define('CURRENCY_CODE', 'USD');
define('TAX_PERCENTAGE', 5);
define('DECIMAL_POINTS', 2);

// Prescription Settings
define('MAX_PRESCRIPTION_ITEMS', 20);
define('PRESCRIPTION_VALIDITY_DAYS', 90);

// Appointment Settings
define('DEFAULT_SLOT_DURATION', 15); // minutes
define('MAX_APPOINTMENTS_PER_SLOT', 1);

// Queue Settings
define('QUEUE_PREFIX', 'Q');
define('AUTO_REFRESH_INTERVAL', 30); // seconds

// Security
define('PASSWORD_MIN_LENGTH', 8);
define('LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15); // minutes
define('SESSION_LIFETIME', 3600); // 1 hour

// Cache Settings
define('CACHE_ENABLED', false);
define('CACHE_LIFETIME', 3600);

// Debug Mode
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);
define('ERROR_LOG_PATH', ROOT_PATH . '/app/logs/error.log');

// Email Settings
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'noreply@unidia.com');
define('MAIL_PASSWORD', '');
define('MAIL_ENCRYPTION', 'tls');
define('MAIL_FROM_ADDRESS', 'noreply@unidia.com');
define('MAIL_FROM_NAME', 'UniDia Application');

// SMS Settings (Optional)
define('SMS_ENABLED', false);
define('SMS_API_KEY', '');
define('SMS_SENDER_ID', 'UNIDIA');

// Barcode Settings
define('BARCODE_TYPE', 'CODE128');
define('QR_SIZE', 200);

// Payment Gateway Settings
define('PAYMENT_GATEWAY', 'stripe'); // stripe, paypal, razorpay
define('STRIPE_PUBLIC_KEY', '');
define('STRIPE_SECRET_KEY', '');
define('PAYPAL_CLIENT_ID', '');
define('PAYPAL_SECRET', '');