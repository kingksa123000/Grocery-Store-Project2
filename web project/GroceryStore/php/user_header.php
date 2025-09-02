<?php
require_once 'config.php';

$is_logged_in = isset($_SESSION['admin_id']) || isset($_SESSION['user_id']);
$is_user = isset($_SESSION['user_id']);


if ($is_logged_in) {
    $user_id = $_SESSION['admin_id'] ?? $_SESSION['user_id'];
    $select_profile = $conn->prepare("SELECT * FROM `users` WHERE `id` = ?");
    $select_profile->execute([$user_id]);
    $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Panel</title>
    <link rel="stylesheet" href="\web project\GroceryStore\css\StyleSheet.css">
    

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userBtn = document.getElementById('user-btn');
            const profile = document.querySelector('.profile');
            
            if (userBtn && profile) {
                userBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profile.classList.toggle('active');
                });
                
                document.addEventListener('click', function() {
                    profile.classList.remove('active');
                });
                
                profile.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        });
    </script>
</head>
<body>
<header class="header">
    <div class="flex">
        <a href="Home.php" class="logo">User <span>Panel</span></a>
        <nav class="navbar">
            <a href="Home.php">Home</a>
            <a href="orders.php">Orders</a>
            <a href="ShoppingCart.php">Cart</a>
            <a href="contact.php">Contact</a>
        </nav>
        <div class="icons">                    
            <button id="user-btn" class="user_icon">👤</button>
        </div>
        <div class="profile">
            <?php if ($is_logged_in): ?>
                <span>Welcome, <?= htmlspecialchars($fetch_profile['Name']) ?> (<?= $is_user ? 'User' : 'Admin' ?>)</span>
                <div class="profile-actions">
                    <a href="user_update_profile.php" class="btn">Update Profile</a>
                    <a href="logout.php" class="delete-btn">Logout</a>
                </div>
            <?php else: ?>
                <div class="profile-actions">
                    <a href="login.php" class="login-btn">Login Now</a>
                    <a href="register.php" class="register-btn">Register Now</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>