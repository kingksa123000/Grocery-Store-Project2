<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;

// Handle adding items to cart (Database)
if (isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    // Validate quantity
    if (!is_numeric($quantity) || $quantity < 1) {
        $_SESSION['message'] = "Invalid quantity.";
        $_SESSION['message_type'] = "error";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    try {
        // Check if item already exists in the cart for this user
        $stmt = $conn->prepare("SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_item) {
            // Update quantity if item exists
            $new_quantity = $existing_item['quantity'] + $quantity;
            $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$new_quantity, $user_id, $product_id]);
        } else {
            // Insert new item if it doesn't exist
            $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity, added_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }

        $_SESSION['message'] = "Product added to cart!";
        $_SESSION['message_type'] = "success";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();

    } catch (PDOException $e) {
        $_SESSION['message'] = "Database error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

// Handle removing items from cart (Database)
if (isset($_GET['remove'])) {
    $product_id = $_GET['remove'];

    try {
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);

        $_SESSION['message'] = "Product removed from cart.";
        $_SESSION['message_type'] = "success";
        header("Location: ShoppingCart.php"); // Changed redirect
        exit();

    } catch (PDOException $e) {
        $_SESSION['message'] = "Database error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: ShoppingCart.php"); // Changed redirect
        exit();
    }
}

// Fetch cart items (Database)
$cart_items = [];
$total_price = 0;

try {
    $stmt = $conn->prepare("
        SELECT p.*, ci.quantity
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_data as $item) {
        $total_price += $item['price'] * $item['quantity'];
        $cart_items[] = [
            'product' => $item,
            'quantity' => $item['quantity'],
        ];
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Database error: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
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
    <title>Shopping Cart</title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/stylesheet.css">

</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="user_cart_container">
        <h1 class="user_cart_title">Shopping Cart</h1>

        <?php if (!empty($message)): ?>
            <div class="user_cart_message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <p class="user_cart_empty">Your cart is empty.</p>
        <?php else: ?>
            <div class="user_cart_items">
                <?php foreach ($cart_items as $item): ?>
                    <div class="user_cart_item">
                        <img src="<?= htmlspecialchars($item['product']['image']) ?>" alt="<?= htmlspecialchars($item['product']['name']) ?>" class="user_cart_item_image">
                        <div class="user_cart_item_info">
                            <h3><?= htmlspecialchars($item['product']['name']) ?></h3>
                            <p>Price: $<?= number_format($item['product']['price'], 2) ?></p>
                            <p>Quantity: <?= $item['quantity'] ?></p>
                            <p>Subtotal: $<?= number_format($item['product']['price'] * $item['quantity'], 2) ?></p>
                            <div class="user_cart_actions">
                                <a href="ShoppingCart.php?remove=<?= $item['product']['id'] ?>" class="user_cart_remove">Remove</a> <a href="modify_cart.php?id=<?= $item['product']['id'] ?>" class="user_cart_modify">Modify</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="user_cart_total">
                <p>Total: $<?= number_format($total_price, 2) ?></p>
                <a href="checkout.php" class="user_cart_checkout">Proceed to Checkout</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>