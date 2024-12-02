<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || !is_admin($pdo, $_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$table = $_GET['table'] ?? '';
$id = $_GET['id'] ?? '';

if (empty($table) || empty($id)) {
    header('Location: admin.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    unset($data['submit']); // Remove submit button from data
    
    if (update_record($pdo, $table, $id, $data)) {
        header("Location: admin.php?tab=$table");
        exit();
    }
}

// Fetch record
$sql = "SELECT * FROM $table WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    header('Location: admin.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Record - Clean Hero</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/navigation.php'; ?>
        <main>
            <h1>Edit <?php echo ucfirst($table); ?> Record</h1>
            
            <form method="POST" class="edit-form">
                <?php foreach ($record as $field => $value): ?>
                    <?php if ($field !== 'id' && $field !== 'created_at' && $field !== 'updated_at'): ?>
                        <div class="form-group">
                            <label for="<?php echo $field; ?>"><?php echo ucwords(str_replace('_', ' ', $field)); ?></label>
                            <input type="text" name="<?php echo $field; ?>" id="<?php echo $field; ?>" 
                                   value="<?php echo htmlspecialchars($value); ?>" required>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                
                <div class="form-actions">
                    <button type="submit" name="submit" class="submit-btn">Update</button>
                    <a href="admin.php?tab=<?php echo $table; ?>" class="cancel-btn">Cancel</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html> 