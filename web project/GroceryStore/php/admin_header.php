<?php
include 'config.php';

// Check if user is logged in
$is_logged_in = isset($_SESSION['admin_id']) || isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['admin_id']);

// Fetch profile data if logged in
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
    <title>Admin Panel</title>
    <link rel="stylesheet" href="css/StyleSheet.css">
    <link rel="shortcut icon" href="/web project/GroceryStore/images/logo.jpg" type="image/x-icon">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userBtn = document.getElementById('user-btn');
            const profile = document.querySelector('.profile');
            
            userBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                profile.classList.toggle('active');
            });
            
            // Close profile when clicking outside
            document.addEventListener('click', function() {
                profile.classList.remove('active');
            });
            
            // Prevent profile from closing when clicking inside it
            profile.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    </script>
</head>
<body>
    <header class="header">
        <div class="flex">
            <a href="Admin.php" class="logo">Admin <span>Panel</span></a>
            <nav class="navbar">
                <a href="Admin.php">Home</a>
                <a href="Admin_products.php">Products</a>
                <a href="Admin_orders.php">Orders</a>
                <a href="Admin_users.php">Users</a>
                <a href="Admin_contacts.php">Contacts</a>
            </nav>
            <div class="icons">                    
                <button id="user-btn" class="user_icon">👤</button>
            </div>
            <div class="profile">
                <?php if ($is_logged_in): ?>
                    <span>Welcome, <?= htmlspecialchars($fetch_profile['Name']) ?> (<?= $is_admin ? 'Admin' : 'User' ?>)</span>
                    <div class="profile-actions">
                        <a href="admin_update_profile.php" class="btn">Update Profile</a>
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
</body>
</html>