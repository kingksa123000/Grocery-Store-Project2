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
    <title>Admin Panel</title>
    <link rel="stylesheet" href="\web project\GroceryStore\css\StyleSheet.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    <section class="dashboard">
    <h1 class="title">dashboard</h1>
    
    <div class="box-container">
        <div class="box">
            <?php
            $total_pendings = 0;
            $select_pendings = $conn->prepare("SELECT * FROM `orders` WHERE payment_status = ?");
            $select_pendings->execute(['Pending']);
            
            while($order = $select_pendings->fetch(PDO::FETCH_ASSOC)) {
                $total_pendings += $order['total_price'];
            }
            ?>
            <h3>$<?=$total_pendings; ?></h3>
            <p>total pendings</p>
            <a href="admin_orders.php"class="btn">see orders</a>
        </div>
        <div class="box">
            <?php
            $total_completed = 0;
            $select_completed = $conn->prepare("SELECT * FROM `orders` WHERE payment_status = ?");
            $select_completed->execute(['shipped']);
            
            while($order = $select_completed->fetch(PDO::FETCH_ASSOC)) {
                $total_completed += $order['total_price'];
            }
            ?>
            <h3>$<?= $total_completed; ?></h3>
            <p>completed orders</p>
            <a href="admin_orders.php"class="btn">see orders</a>
        </div>
        <div class="box">
            <?php
            $total_products = 0;
            $select_products = $conn->prepare("SELECT * FROM `products`");
            $select_products->execute([]);
            $number_of_products=$select_products->rowCount();
            ?>
            <h3><?=$number_of_products; ?></h3>
            <p>products added</p>
            <a href="admin_products.php"class="btn">see products</a>
        </div>
        <div class="box">
            <?php
            $total_orders = 0;
            $select_orders = $conn->prepare("SELECT * FROM `orders`");
            $select_orders->execute([]);
            $number_of_orders=$select_orders->rowCount();
            ?>
            <h3><?=$number_of_orders; ?></h3>
            <p>orders placed</p>
            <a href="admin_orders.php"class="btn">see orders</a>
        </div>
        <div class="box">
            <?php
            $total_accounts = 0;
            $select_accounts = $conn->prepare("SELECT * FROM `users`");
            $select_accounts->execute([]);
            $number_of_accounts=$select_accounts->rowCount();
            ?>
            <h3><?=$number_of_accounts; ?></h3>
            <p>total accounts</p>
            <a href="admin_users.php"class="btn">see accounts</a>
        </div>
        <div class="box">
            <?php
            $total_messages = 0;
            $select_messages = $conn->prepare("SELECT * FROM contact_messages");
            $select_messages->execute([]);
            $number_of_messages=$select_messages->rowCount();
            ?>
            <h3><?=$number_of_messages; ?></h3>
            <p>total messages</p>
            <a href="admin_contacts.php"class="btn">see messages</a>
        </div>
        <div class="box">
            <?php
            $total_users = 0;
            $select_users = $conn->prepare("SELECT * FROM users WHERE user_type=?");
            $select_users->execute(["user"]);
            $number_of_users=$select_users->rowCount();
            ?>
            <h3><?=$number_of_users; ?></h3>
            <p>total user accounts</p>
            <a href="admin_users.php"class="btn">see user accounts</a>
        </div>
        <div class="box">
            <?php
            $total_admins = 0;
            $select_admins = $conn->prepare("SELECT * FROM users WHERE user_type=?");
            $select_admins->execute(["admin"]);
            $number_of_admins=$select_admins->rowCount();
            ?>
            <h3><?=$number_of_admins; ?></h3>
            <p>total admin accounts</p>
            <a href="admin_users.php"class="btn">see admin accounts</a>
        </div>
    </div>
</section>
    <?php include 'footer.php'; ?>

</body>
</html>
