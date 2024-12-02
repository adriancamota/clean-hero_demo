<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user's rewards and transactions
$rewards_sql = "SELECT * FROM rewards WHERE user_id = ?";
$rewards_stmt = $pdo->prepare($rewards_sql);
$rewards_stmt->execute([$_SESSION['user_id']]);
$rewards = $rewards_stmt->fetch();

$transactions_sql = "SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC";
$transactions_stmt = $pdo->prepare($transactions_sql);
$transactions_stmt->execute([$_SESSION['user_id']]);
$transactions = $transactions_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards - Clean Hero</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/navigation.php'; ?>
        <main>
            <h1>Your Rewards</h1>
            <div class="rewards-summary">
                <h2>Total Points: <?php echo number_format($rewards['points'] ?? 0, 2); ?></h2>
            </div>
            
            <h2>Transaction History</h2>
            <div class="transactions">
                <?php foreach ($transactions as $transaction): ?>
                    <div class="transaction-card">
                        <div class="transaction-type <?php echo $transaction['type']; ?>">
                            <?php echo ucfirst($transaction['type']); ?>
                        </div>
                        <div class="transaction-amount">
                            <?php echo number_format($transaction['amount'], 2); ?> points
                        </div>
                        <div class="transaction-description">
                            <?php echo htmlspecialchars($transaction['description']); ?>
                        </div>
                        <div class="transaction-date">
                            <?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html> 