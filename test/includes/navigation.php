<nav>
    <div class="logo">
        <span>Clean-Hero</span>
    </div>
    <ul class="menu">
        <li><a href="index.php">Home</a></li>
        <li><a href="report.php">Report Waste</a></li>
        <li><a href="collect.php">Collect Waste</a></li>
        <li><a href="rewards.php">Rewards</a></li>
        <li><a href="leaderboard.php">Leaderboard</a></li>
        <?php if (isset($_SESSION['user_id']) && is_admin($pdo, $_SESSION['user_id'])): ?>
            <li><a href="admin.php">Admin</a></li>
        <?php endif; ?>
    </ul>
    <div class="user-menu">
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php
            $points_sql = "SELECT points FROM rewards WHERE user_id = ?";
            $points_stmt = $pdo->prepare($points_sql);
            $points_stmt->execute([$_SESSION['user_id']]);
            $points = $points_stmt->fetchColumn() ?: 0;
            ?>
            <span class="points"><?php echo number_format($points, 2); ?></span>
            <a href="logout.php" class="login-btn">Logout</a>
        <?php else: ?>
            <span class="points">0.00</span>
            <a href="login.php" class="login-btn">Login</a>
        <?php endif; ?>
    </div>
</nav> 