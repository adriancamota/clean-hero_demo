<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

// Fetch top users by points
$sql = "SELECT u.name, r.points 
        FROM users u 
        JOIN rewards r ON u.id = r.user_id 
        ORDER BY r.points DESC 
        LIMIT 10";
$stmt = $pdo->query($sql);
$leaderboard = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard - Clean Hero</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/navigation.php'; ?>
        <main>
            <h1>Leaderboard</h1>
            <div class="leaderboard">
                <?php foreach ($leaderboard as $index => $entry): ?>
                    <div class="leaderboard-entry">
                        <span class="rank"><?php echo $index + 1; ?></span>
                        <span class="name"><?php echo htmlspecialchars($entry['name']); ?></span>
                        <span class="points"><?php echo number_format($entry['points'], 2); ?> points</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html> 