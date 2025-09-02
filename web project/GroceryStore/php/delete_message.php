<?php
session_start();
require_once 'config.php';

// Admin Validation
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get message ID from URL
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $delete_id = (int)$_GET['id'];

    try {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$delete_id]);

        $_SESSION['message'] = "Message deleted successfully.";
        $_SESSION['message_type'] = 'success';
        header("Location: admin_contacts.php");
        exit();
    } catch (PDOException $e) {
        error_log("Database error (delete message): " . $e->getMessage());
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
?>