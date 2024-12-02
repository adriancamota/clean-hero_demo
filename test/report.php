<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location = $_POST['location'];
    $waste_type = $_POST['waste_type'];
    $amount = $_POST['amount'];
    $user_id = $_SESSION['user_id'];
    
    // Handle file upload
    if (isset($_FILES['waste_image']) && $_FILES['waste_image']['error'] === UPLOAD_ERR_OK) {
        $image = $_FILES['waste_image'];
        $upload_dir = 'uploads/';
        $image_name = uniqid() . '_' . basename($image['name']);
        $image_path = $upload_dir . $image_name;
        
        if (move_uploaded_file($image['tmp_name'], $image_path)) {
            // Insert report into database
            $sql = "INSERT INTO reports (user_id, location, waste_type, amount, image_url, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')";
            $stmt = $pdo->prepare($sql);
            
            try {
                $stmt->execute([$user_id, $location, $waste_type, $amount, $image_path]);
                
                // Create notification
                $notif_sql = "INSERT INTO notifications (user_id, message, type) 
                             VALUES (?, 'Your waste report has been submitted successfully', 'report')";
                $notif_stmt = $pdo->prepare($notif_sql);
                $notif_stmt->execute([$user_id]);
                
                $success = "Waste report submitted successfully!";
            } catch (PDOException $e) {
                $error = "Error submitting report: " . $e->getMessage();
            }
        } else {
            $error = "Error uploading image";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Waste - Clean Hero</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/navigation.php'; ?>
        <main>
            <h1>Report waste</h1>
            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="report.php" method="POST" enctype="multipart/form-data">
                <div class="upload-section">
                    <h2>Upload Waste Image</h2>
                    <div class="upload-area">
                        <input type="file" name="waste_image" accept="image/*" required>
                        <p>PNG, JPG, GIF up to 10MB</p>
                    </div>
                </div>

                <div class="verify-section">
                    <h2>Verify Waste</h2>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" name="location" placeholder="Enter waste location" required>
                    </div>

                    <div class="form-group">
                        <label for="waste_type">Waste Type</label>
                        <input type="text" name="waste_type" placeholder="Enter waste type" required>
                    </div>

                    <div class="form-group">
                        <label for="amount">Estimated Amount</label>
                        <input type="text" name="amount" placeholder="Enter amount (e.g., 5 kg)" required>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Submit Report</button>
            </form>
        </main>
    </div>
</body>
</html> 