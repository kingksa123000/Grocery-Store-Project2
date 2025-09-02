<?php
session_start();
require_once 'config.php'; // Database connection

// Admin Validation
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];
$query = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$query->execute([$admin_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['user_type'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Search functionality
$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    try {
        $stmt = $conn->prepare("SELECT id, Name, email, user_type FROM users WHERE Name LIKE ?");
        $stmt->execute(['%' . $searchTerm . '%']);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
} else {
    // Fetch all users if no search term
    try {
        $stmt = $conn->query("SELECT id, Name, email, user_type FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Count admin and user accounts
$adminCount = 0;
$userCount = 0;
foreach ($users as $u) {
    if ($u['user_type'] == 'admin') {
        $adminCount++;
    } else {
        $userCount++;
    }
}

// Promote user to admin
if (isset($_GET['promote_id']) && ctype_digit($_GET['promote_id'])) {
    $promote_id = (int)$_GET['promote_id'];
    $stmt = $conn->prepare("UPDATE users SET user_type = 'admin' WHERE id = ?");
    if ($stmt->execute([$promote_id])) {
        $_SESSION['message'] = "User promoted to admin successfully.";
        $_SESSION['message_type'] = 'success';
        header("Location: admin_users.php");
        exit();
    } else {
        $_SESSION['message'] = "Failed to promote user.";
        $_SESSION['message_type'] = 'error';
        header("Location: admin_users.php");
        exit();
    }
}

// Demote admin to user
if (isset($_GET['demote_id']) && ctype_digit($_GET['demote_id'])) {
    $demote_id = (int)$_GET['demote_id'];
    $stmt = $conn->prepare("UPDATE users SET user_type = 'user' WHERE id = ?");
    if ($stmt->execute([$demote_id])) {
        $_SESSION['message'] = "Admin demoted to user successfully.";
        $_SESSION['message_type'] = 'success';
        header("Location: admin_users.php");
        exit();
    } else {
        $_SESSION['message'] = "Failed to demote admin.";
        $_SESSION['message_type'] = 'error';
        header("Location: admin_users.php");
        exit();
    }
}

// Get session messages if they exist
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="\web project\GroceryStore\css\StyleSheet.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="user_dashboard">
        <h1 class="user_title">User Management</h1>

        <?php if (isset($message)): ?>
            <div class="user_message user_<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="user_container">
            <div class="user_group">
                <div style="margin-bottom: 10px;">
                    <form action="admin_users.php" method="GET">
                        <input type="text" name="search" placeholder="Search accounts..." value="<?= htmlspecialchars($searchTerm) ?>">
                        <button type="submit">Search</button>
                    </form>
                </div>
                <h2>Admin Accounts (<?= $adminCount ?>)</h2>
                <div class="user_boxes">
                    <?php foreach ($users as $user): ?>
                        <?php if ($user['user_type'] == 'admin'): ?>
                            <div class="user_box">
                                <div class="user_info">
                                    <div class="user_name"><?= htmlspecialchars($user['Name']) ?></div>
                                    <div class="user_email"><?= htmlspecialchars($user['email']) ?></div>
                                    <span class="user_type" value="<?= htmlspecialchars($user['user_type']) ?>">
                                        <?= htmlspecialchars($user['user_type']) ?>
                                    </span>
                                </div>
                                <div class="user_actions">
                                    <a href="admin_users.php?demote_id=<?= $user['id'] ?>" class="user_btn user_demote-btn">
                                        Remove Admin Privilege
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="user_group">
                <h2>User Accounts (<?= $userCount ?>)</h2>
                <div class="user_boxes">
                    <?php foreach ($users as $user): ?>
                        <?php if ($user['user_type'] == 'user'): ?>
                            <div class="user_box">
                                <div class="user_info">
                                    <div class="user_name"><?= htmlspecialchars($user['Name']) ?></div>
                                    <div class="user_email"><?= htmlspecialchars($user['email']) ?></div>
                                    <span class="user_type" value="<?= htmlspecialchars($user['user_type']) ?>">
                                        <?= htmlspecialchars($user['user_type']) ?>
                                    </span>
                                </div>
                                <div class="user_actions">
                                    <a href="admin_users.php?promote_id=<?= $user['id'] ?>" class="user_btn user_promote-btn">
                                        Promote to Admin
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>