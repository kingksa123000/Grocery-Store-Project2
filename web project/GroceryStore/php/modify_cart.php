<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if product ID is provided in the GET request
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid product ID.";
    $_SESSION['message_type'] = "error";
    header("Location: ShoppingCart.php");
    exit();
}

$product_id = $_GET['id'];

// Fetch the cart item details for the given product ID
try {
    $stmt = $conn->prepare("
        SELECT p.name, p.image, ci.quantity
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ? AND ci.product_id = ?
    ");
    $stmt->execute([$user_id, $product_id]);
    $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cart_item) {
        $_SESSION['message'] = "Product not found in your cart.";
        $_SESSION['message_type'] = "error";
        header("Location: ShoppingCart.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "Database error: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: ShoppingCart.php");
    exit();
}

// Handle updating the cart item quantity
if (isset($_POST['update_quantity'])) {
    $new_quantity = $_POST['quantity'];

    // Validate quantity
    if (!is_numeric($new_quantity) || $new_quantity < 1) {
        $_SESSION['message'] = "Invalid quantity.";
        $_SESSION['message_type'] = "error";
        header("Location: modify_cart.php?id=" . $product_id);
        exit();
    }

    try {
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$new_quantity, $user_id, $product_id]);

        $_SESSION['message'] = "Cart updated successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: ShoppingCart.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['message'] = "Database error: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: modify_cart.php?id=" . $product_id);
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
    <title>Modify Cart Item</title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/stylesheet.css">
    <style>
        .modify-cart-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            margin-top: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 0px 4px black;
            width: 80%;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .modify-cart-title {
            font-size: 24px;
            margin-bottom: 20px;
            color: #333;
        }

        .modify-cart-item-details {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            width: 100%;
            box-sizing: border-box;
        }

        .modify-cart-item-image {
            max-width: 100px;
            height: auto;
            margin-right: 15px;
            border-radius: 4px;
        }

        .modify-cart-item-info {
            flex-grow: 1;
        }

        .modify-cart-item-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .modify-cart-form {
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        .modify-cart-form label {
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }

        .modify-cart-form input[type="number"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
            width: 80px;
        }

        .modify-cart-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modify-cart-button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .modify-cart-button-update {
            background-color: #5cb85c;
            color: white;
        }

        .modify-cart-button-update:hover {
            background-color: #4cae4c;
        }

        .modify-cart-button-cancel {
            background-color: red;
            color: white;
            text-decoration: none;
        }

        .modify-cart-button-cancel:hover {
            background-color:darkred;
        }

        .user_cart_message { /* Inherit styling for messages */
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
        }

        .user_cart_message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .user_cart_message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="modify-cart-container">
        <h1 class="modify-cart-title">Modify Cart Item</h1>

        <?php if (!empty($message)): ?>
            <div class="user_cart_message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="modify-cart-item-details">
            <?php if (!empty($cart_item['image'])): ?>
                <img src="<?= htmlspecialchars($cart_item['image']) ?>" alt="<?= htmlspecialchars($cart_item['name']) ?>" class="modify-cart-item-image">
            <?php endif; ?>
            <div class="modify-cart-item-info">
                <h3 class="modify-cart-item-name"><?= htmlspecialchars($cart_item['name']) ?></h3>
            </div>
        </div>

        <div class="modify-cart-form">
            <form method="post">
                <label for="quantity">New Quantity:</label>
                <input type="number" id="quantity" name="quantity" value="<?= htmlspecialchars($cart_item['quantity']) ?>" min="1" required>
                <div class="modify-cart-actions">
                    <button type="submit" name="update_quantity" class="modify-cart-button modify-cart-button-update">Update Quantity</button>
                    <a href="ShoppingCart.php" class="modify-cart-button modify-cart-button-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>