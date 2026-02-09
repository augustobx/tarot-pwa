<?php
/**
 * Database Migration: Add module support to chats table
 * Run this once to update the database schema
 */

require_once 'shared/api/db.php';

try {
    // Add module column to chats table
    $pdo->exec("
        ALTER TABLE chats 
        ADD COLUMN module VARCHAR(20) DEFAULT 'tarot' 
        AFTER user_id
    ");
    
    echo "✅ Added 'module' column to chats table\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "ℹ️  Column 'module' already exists in chats table\n";
    } else {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

try {
    // Create user_sessions table for module-specific preferences
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            module VARCHAR(20),
            last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            preferences JSON,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_module (user_id, module)
        )
    ");
    
    echo "✅ Created user_sessions table\n";
} catch (PDOException $e) {
    echo "❌ Error creating user_sessions: " . $e->getMessage() . "\n";
}

echo "\n✅ Database migration complete!\n";
