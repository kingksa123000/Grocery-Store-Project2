<?php
session_start();
require_once 'config.php'; 

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

// Fetch contact messages with user details
try {
    $stmt = $conn->query("SELECT cm.*, u.Name as userName, u.email as userEmail FROM contact_messages cm LEFT JOIN users u ON cm.user_id = u.id ORDER BY cm.created_at DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Delete message
if (isset($_GET['delete_id']) && ctype_digit($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$delete_id]);

        // Refresh the current page after deletion
        header("Location: admin_contacts.php");
        exit();

    } catch (PDOException $e) {
        // Handle database errors
        error_log("Database error deleting message: " . $e->getMessage());
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
    <title>Admin Contact Messages</title>
    <link rel="stylesheet" href="\web project\GroceryStore\css\StyleSheet.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="user_dashboard">
        <h1 class="contact_admin_title">Contact Messages</h1>

        <?php if (isset($message)): ?>
            <div class="contact_admin_message_box contact_admin_<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="contact_admin_messages_container">
            <?php if (empty($messages)): ?>
                <p class="contact_admin_no_messages">No messages found.</p>
            <?php else: ?>
                <ul class="contact_admin_message_list">
                    <?php foreach ($messages as $msg): ?>
                        <li class="contact_admin_message_item">
                            <div class="contact_admin_message_sender">
                                <strong>From:</strong>
                                <?php if ($msg['userName']): ?>
                                    <?= htmlspecialchars($msg['userName']) ?> (<?= htmlspecialchars($msg['userEmail']) ?>)
                                <?php else: ?>
                                    <?= htmlspecialchars($msg['name']) ?> (<?= htmlspecialchars($msg['email']) ?>)
                                <?php endif; ?>
                            </div>
                            <div class="contact_admin_message_subject"><strong>Subject:</strong> <?= htmlspecialchars($msg['subject']) ?></div>
                            <div class="contact_admin_message_snippet"><?= substr(htmlspecialchars($msg['message']), 0, 100) ?>...</div>
                            <div class="contact_admin_message_actions">
                                <a href="admin_contacts.php?delete_id=<?= $msg['id'] ?>" class="contact_admin_btn contact_admin_demote_btn" onclick="return confirm('Delete this message?')">Delete</a>
                                <a href="view_message.php?id=<?= $msg['id'] ?>" class="contact_admin_btn contact_admin_promote_btn">View Full Message</a>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>