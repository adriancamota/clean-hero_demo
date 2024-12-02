<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clean-Hero - Waste Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/navigation.php'; ?>
        <main class="home-main">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="welcome-section">
                    <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                    <div class="leaf-decoration"></div>
                </div>
                <div class="dashboard">
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <div class="eco-icon">🌱</div>
                            <h3>Your Points</h3>
                            <p class="stat-value"><?php echo number_format(get_user_points($pdo, $_SESSION['user_id']), 2); ?></p>
                        </div>
                        <div class="stat-card">
                            <div class="eco-icon">🌍</div>
                            <h3>Quick Actions</h3>
                            <div class="action-buttons">
                                <a href="report.php" class="action-btn">Report Waste</a>
                                <a href="collect.php" class="action-btn">Collect Waste</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="hero-section">
                    <div class="leaf-decoration"></div>
                    <h1>Welcome to Clean-Hero</h1>
                    <p class="hero-text">Help keep our environment clean by reporting and collecting waste.</p>
                    <div class="cta-buttons">
                        <a href="register.php" class="cta-btn primary">Get Started</a>
                        <a href="login.php" class="cta-btn secondary">Login</a>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 