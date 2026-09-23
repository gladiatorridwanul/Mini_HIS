<?php
// public/setup-admin.php
// Run this once to create/update admin user with correct password

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance()->getConnection();

// Check if admin exists
$check = $db->query("SELECT id, password FROM users WHERE email = 'admin@unidia.com'");

if ($check && $check->num_rows > 0) {
    $row = $check->fetch_assoc();
    $password = 'admin123';
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    $db->query("UPDATE users SET password = '$hashed' WHERE id = {$row['id']}");
    
    echo "✅ Admin password updated successfully!<br>";
    echo "Email: admin@unidia.com<br>";
    echo "Password: admin123<br>";
    echo "Hash: " . $hashed . "<br>";
} else {
    // Create new admin user
    $password = 'admin123';
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    // Get super admin role
    $roleCheck = $db->query("SELECT id FROM roles WHERE slug = 'super_admin'");
    if ($roleCheck && $roleCheck->num_rows > 0) {
        $roleId = $roleCheck->fetch_assoc()['id'];
    } else {
        $roleId = 1;
    }
    
    $query = "INSERT INTO users (role_id, first_name, last_name, email, password, status) 
              VALUES ($roleId, 'System', 'Admin', 'admin@unidia.com', '$hashed', 'active')";
    
    if ($db->query($query)) {
        echo "✅ Admin user created successfully!<br>";
        echo "Email: admin@unidia.com<br>";
        echo "Password: admin123<br>";
    } else {
        echo "❌ Error creating admin: " . $db->error . "<br>";
    }
}

echo "<br><a href='/unidia/public/login'>🔐 Go to Login</a>";