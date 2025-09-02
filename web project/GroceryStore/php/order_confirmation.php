<?php
// order_confirmation.php
session_start();
require_once 'config.php';

// Redirect if id (order ID) is not set
if (!isset($_GET['id'])) {
    header("Location: ShoppingCart.php");
    exit();
}

$id = $_GET['id']; // Use 'id' instead of 'order_id'

// Fetch order details from the database
try {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?"); 
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        header("Location: cart.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Database error: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/stylesheet.css">
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="user_order_confirmation_container">
        <h1>Order Confirmation</h1>
        <p>Your order (Order ID: <?= htmlspecialchars($id) ?>) has been placed successfully!</p>
        <p>Total: $<?= number_format($order['total_price'], 2) ?></p>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>