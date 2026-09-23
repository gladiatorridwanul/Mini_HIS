<?php
// public/debug-login.php
// Run this to debug login issues

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../core/Database.php';

echo "<h1>Login Debug</h1>";

$db = Database::getInstance()->getConnection();

if (!$db) {
    echo "❌ Database connection failed!<br>";
    exit;
}
echo "✅ Database connected successfully.<br><br>";

// Check users table
$result = $db->query("SELECT id, email, first_name, last_name, password FROM users WHERE email = 'admin@unidia.com'");

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "✅ User found: " . $user['first_name'] . ' ' . $user['last_name'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Stored password hash: " . $user['password'] . "<br>";
    echo "Hash length: " . strlen($user['password']) . "<br>";
    
    // Test password verification
    $testPassword = 'admin123';
    $verified = password_verify($testPassword, $user['password']);
    echo "Testing password 'admin123': " . ($verified ? '✅ MATCHES' : '❌ DOES NOT MATCH') . "<br><br>";
    
    // If not verified, try to update password
    if (!$verified) {
        echo "<strong>Password doesn't match. Updating...</strong><br>";
        $newHash = password_hash($testPassword, PASSWORD_DEFAULT);
        $update = $db->query("UPDATE users SET password = '$newHash' WHERE id = {$user['id']}");
        if ($update) {
            echo "✅ Password updated successfully!<br>";
            echo "New hash: " . $newHash . "<br>";
            
            // Test again
            $result2 = $db->query("SELECT password FROM users WHERE id = {$user['id']}");
            $row2 = $result2->fetch_assoc();
            $verified2 = password_verify($testPassword, $row2['password']);
            echo "Testing new password: " . ($verified2 ? '✅ MATCHES' : '❌ DOES NOT MATCH') . "<br>";
        } else {
            echo "❌ Failed to update password: " . $db->error . "<br>";
        }
    }
} else {
    echo "❌ User not found. Creating...<br>";
    
    // Get super admin role
    $roleCheck = $db->query("SELECT id FROM roles WHERE slug = 'super_admin'");
    if ($roleCheck && $roleCheck->num_rows > 0) {
        $roleId = $roleCheck->fetch_assoc()['id'];
    } else {
        $roleId = 1;
    }
    
    $password = 'admin123';
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    
    $insert = "INSERT INTO users (role_id, first_name, last_name, email, password, status) 
               VALUES ($roleId, 'System', 'Admin', 'admin@unidia.com', '$hashed', 'active')";
    
    if ($db->query($insert)) {
        echo "✅ Admin user created successfully!<br>";
        echo "Email: admin@unidia.com<br>";
        echo "Password: admin123<br>";
    } else {
        echo "❌ Error creating admin: " . $db->error . "<br>";
    }
}

echo "<br><a href='/unidia/public/login'>🔐 Go to Login</a>";