<?php
require_once 'db.php';

try {
    // 1. Add 'role' column if it doesn't exist
    $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER balance");
        echo "Column 'role' added to users table.<br>";
    } else {
        echo "Column 'role' already exists.<br>";
    }

    // 2. Insert Admin User
    $username = 'admin';
    $password = 'admin123';
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    
    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, balance) VALUES (?, ?, 'admin', 0)");
        $stmt->execute([$username, $passwordHash]);
        echo "Admin user created successfully.<br>";
    } else {
        // Update existing admin to ensure role is correct
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin', password_hash = ? WHERE username = ?");
        $stmt->execute([$passwordHash, $username]);
        echo "Admin user updated.<br>";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
