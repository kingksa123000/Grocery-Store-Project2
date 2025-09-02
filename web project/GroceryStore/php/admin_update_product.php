<?php
include 'config.php';
session_start();

// ✅ Check if admin session exists
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// ✅ Fetch user type to confirm they are an admin
$admin_id = $_SESSION['admin_id'];
$query = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
$query->execute([$admin_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['user_type'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: login.php"); // Logout non-admin users
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update Products</title>
    <link rel="stylesheet" href="\web project\GroceryStore\css\StyleSheet.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
</body>
</html>
