<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || !is_admin($pdo, $_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $id = $_POST['id'];
        $table = $_POST['table'];
        
        switch ($_POST['action']) {
            case 'delete':
                switch ($table) {
                    case 'users':
                        delete_user($pdo, $id);
                        break;
                    case 'reports':
                        delete_record($pdo, 'reports', $id);
                        break;
                    case 'rewards':
                        delete_record($pdo, 'rewards', $id);
                        break;
                    case 'collected_wastes':
                        delete_record($pdo, 'collected_wastes', $id);
                        break;
                    case 'notifications':
                        delete_record($pdo, 'notifications', $id);
                        break;
                    case 'transactions':
                        delete_record($pdo, 'transactions', $id);
                        break;
                }
                break;
            case 'toggle_admin':
                toggle_admin_status($pdo, $id);
                break;
        }
    }
}

// Get current tab
$current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';

// Fetch data based on current tab
$search = isset($_GET['search']) ? $_GET['search'] : '';
switch ($current_tab) {
    case 'users':
        $sql = "SELECT * FROM users WHERE name LIKE :search OR email LIKE :search ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
        $data = $stmt->fetchAll();
        break;
    case 'reports':
        $sql = "SELECT r.*, u.name as reporter_name 
                FROM reports r 
                JOIN users u ON r.user_id = u.id 
                WHERE u.name LIKE :search 
                ORDER BY r.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
        $data = $stmt->fetchAll();
        break;
    case 'rewards':
        $sql = "SELECT r.*, u.name as user_name 
                FROM rewards r 
                JOIN users u ON r.user_id = u.id 
                WHERE u.name LIKE :search 
                ORDER BY r.points DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
        $data = $stmt->fetchAll();
        break;
    case 'collected_wastes':
        $sql = "SELECT cw.*, u.name as collector_name, r.location, r.waste_type, r.amount 
                FROM collected_wastes cw 
                JOIN users u ON cw.collector_id = u.id 
                JOIN reports r ON cw.report_id = r.id 
                WHERE u.name LIKE :search 
                ORDER BY cw.collection_date DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
        $data = $stmt->fetchAll();
        break;
    case 'notifications':
        $sql = "SELECT n.*, u.name as user_name 
                FROM notifications n 
                JOIN users u ON n.user_id = u.id 
                WHERE u.name LIKE :search 
                ORDER BY n.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
        $data = $stmt->fetchAll();
        break;
    case 'transactions':
        $sql = "SELECT t.*, u.name as user_name 
                FROM transactions t 
                JOIN users u ON t.user_id = u.id 
                WHERE u.name LIKE :search 
                ORDER BY t.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
        $data = $stmt->fetchAll();
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Clean Hero</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/navigation.php'; ?>
        <main>
            <h1>Admin Dashboard</h1>
            
            <!-- Tab Navigation -->
            <div class="admin-tabs">
                <a href="?tab=users" class="tab-btn <?php echo $current_tab === 'users' ? 'active' : ''; ?>">Users</a>
                <a href="?tab=reports" class="tab-btn <?php echo $current_tab === 'reports' ? 'active' : ''; ?>">Reports</a>
                <a href="?tab=rewards" class="tab-btn <?php echo $current_tab === 'rewards' ? 'active' : ''; ?>">Rewards</a>
                <a href="?tab=collected_wastes" class="tab-btn <?php echo $current_tab === 'collected_wastes' ? 'active' : ''; ?>">Collected Wastes</a>
                <a href="?tab=notifications" class="tab-btn <?php echo $current_tab === 'notifications' ? 'active' : ''; ?>">Notifications</a>
                <a href="?tab=transactions" class="tab-btn <?php echo $current_tab === 'transactions' ? 'active' : ''; ?>">Transactions</a>
            </div>

            <div class="admin-controls">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="action-buttons">
                    <button onclick="printTable()" class="admin-btn">Print</button>
                    <button onclick="exportCSV()" class="admin-btn">Export CSV</button>
                </div>
            </div>

            <div class="table-container">
                <table id="dataTable">
                    <thead>
                        <?php switch($current_tab): 
                            case 'users': ?>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Created At</th>
                                    <th>Admin</th>
                                    <th>Actions</th>
                                </tr>
                            <?php break;
                            case 'reports': ?>
                                <tr>
                                    <th>ID</th>
                                    <th>Reporter</th>
                                    <th>Location</th>
                                    <th>Waste Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            <?php break;
                            case 'rewards': ?>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Points</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            <?php break;
                            case 'collected_wastes': ?>
                                <tr>
                                    <th>ID</th>
                                    <th>Collector</th>
                                    <th>Location</th>
                                    <th>Waste Type</th>
                                    <th>Amount</th>
                                    <th>Collection Date</th>
                                    <th>Actions</th>
                                </tr>
                            <?php break;
                            case 'notifications': ?>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Message</th>
                                    <th>Read</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            <?php break;
                            case 'transactions': ?>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Description</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            <?php break;
                        endswitch; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <?php switch($current_tab):
                                case 'users': ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo format_date($row['created_at']); ?></td>
                                        <td>
                                            <form action="admin.php" method="POST" class="inline-form">
                                                <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                <input type="hidden" name="action" value="toggle_admin">
                                                <button type="submit" class="toggle-btn <?php echo $row['is_admin'] ? 'active' : ''; ?>">
                                                    <?php echo $row['is_admin'] ? 'Yes' : 'No'; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="editUser(<?php echo $row['id']; ?>)" class="edit-btn">Edit</button>
                                                <form action="admin.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php break;
                                case 'reports': ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['reporter_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td><?php echo htmlspecialchars($row['waste_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                                        <td><?php echo format_date($row['created_at']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="editRecord('reports', <?php echo $row['id']; ?>)" class="edit-btn">Edit</button>
                                                <form action="admin.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this report?');">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="table" value="reports">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php break;
                                case 'rewards': ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                        <td><?php echo number_format($row['points'], 2); ?></td>
                                        <td><?php echo format_date($row['updated_at']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="editRecord('rewards', <?php echo $row['id']; ?>)" class="edit-btn">Edit</button>
                                                <form action="admin.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this reward?');">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="table" value="rewards">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php break;
                                case 'collected_wastes': ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['collector_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td><?php echo htmlspecialchars($row['waste_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['amount']); ?></td>
                                        <td><?php echo format_date($row['collection_date']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="editRecord('collected_wastes', <?php echo $row['id']; ?>)" class="edit-btn">Edit</button>
                                                <form action="admin.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this collected waste?');">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="table" value="collected_wastes">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php break;
                                case 'notifications': ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['message']); ?></td>
                                        <td><?php echo $row['is_read'] ? 'Yes' : 'No'; ?></td>
                                        <td><?php echo format_date($row['created_at']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="editRecord('notifications', <?php echo $row['id']; ?>)" class="edit-btn">Edit</button>
                                                <form action="admin.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="table" value="notifications">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php break;
                                case 'transactions': ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                                        <td><?php echo number_format($row['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td><?php echo format_date($row['created_at']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="editRecord('transactions', <?php echo $row['id']; ?>)" class="edit-btn">Edit</button>
                                                <form action="admin.php" method="POST" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this transaction?');">
                                                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="table" value="transactions">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="delete-btn">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php break;
                            endswitch; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="assets/js/admin.js"></script>
</body>
</html> 