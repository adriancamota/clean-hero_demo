<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle collection submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'])) {
    $report_id = $_POST['report_id'];
    $collector_id = $_SESSION['user_id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Update report status and collector
        $update_sql = "UPDATE reports SET status = 'collected', collector_id = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$collector_id, $report_id]);
        
        // Create collection record
        $collect_sql = "INSERT INTO collected_wastes (report_id, collector_id, collection_date) 
                       VALUES (?, ?, NOW())";
        $collect_stmt = $pdo->prepare($collect_sql);
        $collect_stmt->execute([$report_id, $collector_id]);
        
        // Add points to collector's rewards
        $points = 10; // Define points based on your rules
        $rewards_sql = "INSERT INTO rewards (user_id, points, name, collection_info) 
                       VALUES (?, ?, 'Collection Reward', 'Points earned from waste collection')
                       ON DUPLICATE KEY UPDATE points = points + ?";
        $rewards_stmt = $pdo->prepare($rewards_sql);
        $rewards_stmt->execute([$collector_id, $points, $points]);
        
        // Record transaction
        $trans_sql = "INSERT INTO transactions (user_id, type, amount, description) 
                     VALUES (?, 'earned', ?, 'Points earned from waste collection')";
        $trans_stmt = $pdo->prepare($trans_sql);
        $trans_stmt->execute([$collector_id, $points]);
        
        $pdo->commit();
        $success = "Waste collected successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error collecting waste: " . $e->getMessage();
    }
}

// Fetch pending waste collection tasks
$sql = "SELECT r.*, u.name as reporter_name 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.status = 'pending' 
        ORDER BY r.created_at DESC";
$stmt = $pdo->query($sql);
$tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collect Waste - Clean Hero</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/navigation.php'; ?>
        <main>
            <h1>Waste Collection Tasks</h1>
            
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="tasks-container">
                <?php foreach ($tasks as $task): ?>
                    <div class="task-card">
                        <div class="location">
                            <span class="icon">📍</span>
                            <?php echo htmlspecialchars($task['location']); ?>
                        </div>
                        <div class="details">
                            <span class="reporter">
                                Reported by: <?php echo htmlspecialchars($task['reporter_name']); ?>
                            </span>
                            <span class="waste-type">
                                <?php echo htmlspecialchars($task['waste_type']); ?>
                            </span>
                            <span class="amount">
                                <?php echo htmlspecialchars($task['amount']); ?>
                            </span>
                            <span class="date">
                                <?php echo date('Y-m-d', strtotime($task['created_at'])); ?>
                            </span>
                        </div>
                        <?php if ($task['image_url']): ?>
                            <div class="waste-image">
                                <img src="<?php echo htmlspecialchars($task['image_url']); ?>" alt="Waste Image">
                            </div>
                        <?php endif; ?>
                        <form action="collect.php" method="POST" class="collect-form">
                            <input type="hidden" name="report_id" value="<?php echo $task['id']; ?>">
                            <button type="submit" class="collect-btn">Collect Waste</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html> 