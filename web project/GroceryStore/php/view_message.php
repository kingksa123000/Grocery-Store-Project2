<?php
session_start();
require_once 'config.php';

// Admin Validation
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get message ID
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $message_id = (int)$_GET['id'];

    // Fetch message details
    try {
        $stmt = $conn->prepare("SELECT cm.*, u.Name as userName, u.email as userEmail FROM contact_messages cm LEFT JOIN users u ON cm.user_id = u.id WHERE cm.id = ?");
        $stmt->execute([$message_id]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$message) {
            $_SESSION['message'] = "Message not found.";
            $_SESSION['message_type'] = 'error';
            header("Location: admin_contacts.php");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database error (get message): " . $e->getMessage());
        $_SESSION['message'] = "Database error.";
        $_SESSION['message_type'] = 'error';
        header("Location: admin_contacts.php");
        exit();
    }
} else {
    $_SESSION['message'] = "Invalid message ID.";
    $_SESSION['message_type'] = 'error';
    header("Location: admin_contacts.php");
    exit();
}

// Get session messages if they exist
if (isset($_SESSION['message'])) {
    $message_session = $_SESSION['message'];
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
    <title>View Message</title>
    <link rel="stylesheet" href="\web project\GroceryStore\css\StyleSheet.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="user_dashboard">
        <h1 class="contact_admin_title">View Message</h1>

        <?php if (isset($message_session)): ?>
            <div class="contact_admin_message_box contact_admin_<?= $message_type ?>">
                <?= htmlspecialchars($message_session) ?>
            </div>
        <?php endif; ?>

        <div class="show_message_container">
            <div class="show_message_sender">
                <strong>From:</strong>
                <?php if ($message['userName']): ?>
                    <?= htmlspecialchars($message['userName']) ?> (<?= htmlspecialchars($message['userEmail']) ?>)
                <?php else: ?>
                    <?= htmlspecialchars($message['name']) ?> (<?= htmlspecialchars($message['email']) ?>)
                <?php endif; ?>
            </div>
            <div class="show_message_subject"><strong>Subject:</strong> <?= htmlspecialchars($message['subject']) ?></div>
            <div class="show_message_message"><strong>Message:</strong><br><?= nl2br(htmlspecialchars($message['message'])) ?></div>
            <div class="show_message_actions">
                <a href="delete_message.php?id=<?= $message['id'] ?>" class="contact_admin_btn contact_admin_demote_btn" onclick="return confirm('Delete this message?')">Delete</a>
                <a href="admin_contacts.php" class="contact_admin_btn contact_admin_promote_btn">Back to Messages</a>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>