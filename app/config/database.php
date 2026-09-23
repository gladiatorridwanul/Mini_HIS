<?php
class DatabaseConfig {
    // Primary Database
    const HOST = 'localhost';
    const NAME = 'unidia_db';
    const USER = 'root';
    const PASS = '';
    const CHARSET = 'utf8mb4';
    
    // Secondary Database (Read Replica - Optional)
    const READ_HOST = 'localhost';
    const READ_NAME = 'unidia_db';
    const READ_USER = 'root';
    const READ_PASS = '';
    
    // Connection Pool Settings
    const MAX_CONNECTIONS = 100;
    const MIN_CONNECTIONS = 5;
    const CONNECTION_TIMEOUT = 30;
    
    // Backup Settings
    const BACKUP_ENABLED = true;
    const BACKUP_PATH = 'backups/';
    const BACKUP_INTERVAL = 86400; // 24 hours
    
    public static function getDSN() {
        return "mysql:host=" . self::HOST . ";dbname=" . self::NAME . ";charset=" . self::CHARSET;
    }
    
    public static function getOptions() {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_PERSISTENT => true
        ];
    }
}