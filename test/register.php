<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($name)) {
        $errors[] = "Name is required";
    }

    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if email already exists
    if (empty($errors)) {
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$email]);
        if ($check_stmt->fetch()) {
            $errors[] = "Email already registered";
        }
    }

    // If no errors, create the user
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Insert user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $user_sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
            $user_stmt = $pdo->prepare($user_sql);
            $user_stmt->execute([$name, $email, $hash]);
            
            $user_id = $pdo->lastInsertId();

            // Initialize rewards record
            $rewards_sql = "INSERT INTO rewards (user_id, points) VALUES (?, 0)";
            $rewards_stmt = $pdo->prepare($rewards_sql);
            $rewards_stmt->execute([$user_id]);

            $pdo->commit();

            // Log the user in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $name;

            header('Location: index.php');
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Registration failed. Please try again.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Clean Hero</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/navigation.php'; ?>
        <main>
            <h1>Create Account</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" required>
                    <small class="form-text">Must be at least 6 characters long</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button type="submit" class="submit-btn">Register</button>
            </form>

            <p class="text-center">Already have an account? <a href="login.php">Login here</a></p>
        </main>
    </div>
</body>
</html> 