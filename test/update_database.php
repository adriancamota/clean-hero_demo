<?php
require_once 'includes/db.php';

try {
    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE";
    $pdo->exec($sql);
    
    // Optionally set an initial admin user (replace 1 with the desired user ID)
    $sql = "UPDATE users SET is_admin = TRUE WHERE id = 1";
    $pdo->exec($sql);
    
    echo "Database updated successfully!";
} catch(PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?> 