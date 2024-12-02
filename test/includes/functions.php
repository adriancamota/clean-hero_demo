<?php
/**
 * Common utility functions for the Clean Hero application
 */

/**
 * Sanitize user input
 * @param string $data
 * @return string
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Upload and process an image
 * @param array $file
 * @return string|false Returns the file path on success, false on failure
 */
function handle_image_upload($file) {
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return false;
    }

    // Check file size (limit to 5MB)
    if ($file["size"] > 5000000) {
        return false;
    }

    // Allow certain file formats
    $allowed_types = array("jpg", "jpeg", "png", "gif");
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }

    return false;
}

/**
 * Calculate reward points for waste collection
 * @param string $waste_type
 * @param string $amount
 * @return float
 */
function calculate_reward_points($waste_type, $amount) {
    // Extract numeric value from amount string (e.g., "5 kg" -> 5)
    $numeric_amount = floatval(preg_replace('/[^0-9.]/', '', $amount));
    
    // Base points per kg/unit
    $points_per_unit = array(
        'plastic' => 2.0,
        'paper' => 1.5,
        'glass' => 3.0,
        'metal' => 4.0,
        'electronic' => 5.0,
        'organic' => 1.0
    );

    $waste_type = strtolower($waste_type);
    $points = isset($points_per_unit[$waste_type]) 
        ? $points_per_unit[$waste_type] * $numeric_amount 
        : 1.0 * $numeric_amount; // Default multiplier for unknown waste types

    return round($points, 2);
}

/**
 * Add transaction and update user's points
 * @param PDO $pdo
 * @param int $user_id
 * @param string $type
 * @param float $amount
 * @param string $description
 * @return bool
 */
function add_transaction($pdo, $user_id, $type, $amount, $description) {
    try {
        $pdo->beginTransaction();

        // Insert transaction
        $transaction_sql = "INSERT INTO transactions (user_id, type, amount, description) 
                          VALUES (?, ?, ?, ?)";
        $transaction_stmt = $pdo->prepare($transaction_sql);
        $transaction_stmt->execute([$user_id, $type, $amount, $description]);

        // Update rewards
        $points_modifier = ($type === 'earned') ? $amount : -$amount;
        $rewards_sql = "INSERT INTO rewards (user_id, points) 
                       VALUES (?, ?) 
                       ON DUPLICATE KEY UPDATE points = points + ?";
        $rewards_stmt = $pdo->prepare($rewards_sql);
        $rewards_stmt->execute([$user_id, $points_modifier, $points_modifier]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Transaction error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect if user is not logged in
 * @param string $redirect_to Optional redirect URL
 */
function require_login($redirect_to = 'login.php') {
    if (!is_logged_in()) {
        header("Location: $redirect_to");
        exit();
    }
}

/**
 * Format date for display
 * @param string $date
 * @return string
 */
function format_date($date) {
    return date('M j, Y g:i A', strtotime($date));
}

/**
 * Get user's current points
 * @param PDO $pdo
 * @param int $user_id
 * @return float
 */
function get_user_points($pdo, $user_id) {
    $sql = "SELECT points FROM rewards WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?: 0;
}

/**
 * Check if user is admin
 * @param PDO $pdo
 * @param int $user_id
 * @return bool
 */
function is_admin($pdo, $user_id) {
    $sql = "SELECT is_admin FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    return (bool)$stmt->fetchColumn();
}

/**
 * Delete user and related data
 * @param PDO $pdo
 * @param int $user_id
 * @return bool
 */
function delete_user($pdo, $user_id) {
    try {
        $pdo->beginTransaction();
        
        // Delete related records first
        $tables = ['rewards', 'transactions', 'reports', 'collected_wastes', 'notifications'];
        foreach ($tables as $table) {
            $sql = "DELETE FROM $table WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id]);
        }
        
        // Delete user
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting user: " . $e->getMessage());
        return false;
    }
}

/**
 * Toggle user's admin status
 * @param PDO $pdo
 * @param int $user_id
 * @return bool
 */
function toggle_admin_status($pdo, $user_id) {
    try {
        $sql = "UPDATE users SET is_admin = NOT is_admin WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error toggling admin status: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a record from any table
 * @param PDO $pdo
 * @param string $table
 * @param int $id
 * @return bool
 */
function delete_record($pdo, $table, $id) {
    try {
        $sql = "DELETE FROM " . $table . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        return true;
    } catch (Exception $e) {
        error_log("Error deleting record from $table: " . $e->getMessage());
        return false;
    }
}

/**
 * Update a record in any table
 * @param PDO $pdo
 * @param string $table
 * @param int $id
 * @param array $data
 * @return bool
 */
function update_record($pdo, $table, $id, $data) {
    try {
        $sets = [];
        $values = [];
        foreach ($data as $key => $value) {
            $sets[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;
        
        $sql = "UPDATE " . $table . " SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        return true;
    } catch (Exception $e) {
        error_log("Error updating record in $table: " . $e->getMessage());
        return false;
    }
}