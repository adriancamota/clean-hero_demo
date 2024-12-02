<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        header('Location: index.php');
        exit();
    } else {
        $error = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Clean Hero</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/navigation.php'; ?>
        <main>
            <h1>Login</h1>
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST" class="form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" required>
                </div>
                <button type="submit" class="submit-btn">Login</button>
            </form>
            <div class="register-link">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </main>
    </div>
</body>
</html> 