<?php
session_start();
require_once 'config.php';

// Redirect if not logged in


$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$admin_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;


// Fetch all products from the database
try {
    $products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Check for session messages (e.g., after adding to cart)
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
    <title>Product Catalog</title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/stylesheet.css">
    <link rel="shortcut icon" href="\web project\GroceryStore\images\logo.jpg" type="image/x-icon">
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="dashboard">
        <h1 class="title">Product Catalog</h1>

        <?php if (!empty($message)): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="user_product_container">
            <?php if ($products && $products->rowCount() > 0): ?>
                <?php foreach ($products as $product): ?>
                    <div class="user_product_box">
                        <img src="<?= htmlspecialchars($product['image']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="user_product_img"
                             onerror="this.src='images/default-product.jpg'">
                        <div class="user_product_info">
                            <div class="user_product_price">$<?= number_format($product['price'], 2) ?></div>
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="user_product_category">Category: <?= htmlspecialchars($product['category']) ?></div>
                            <p class="user_product_description"><?= htmlspecialchars($product['details']) ?></p>
                        </div>
                        <div class="user_product_actions">
                            <input type="number" id="quantity_<?= $product['id'] ?>" value="1" min="1" class="user_product_quantity">
                            <button class="user_product_add_cart" onclick="addToCart(<?= $product['id'] ?>, 'quantity_<?= $product['id'] ?>')">Add to Cart</button>
                            <button class="user_product_details" onclick="showProductDetails(<?= $product['id'] ?>)">Show Details</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="user_product_no_products">
                    <p>No products found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function addToCart(productId, quantityId) {
            const quantity = document.getElementById(quantityId).value;

            // Basic validation: Ensure quantity is a positive integer
            if (!Number.isInteger(Number(quantity)) || Number(quantity) < 1) {
                alert("Please enter a valid quantity.");
                return;
            }

            // AJAX request to add to cart
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ productId: productId, quantity: quantity }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Product added to cart!");
                } else {
                    alert("Failed to add product to cart.");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("An error occurred.");
            });
        }

        function showProductDetails(productId) {
            window.location.href = 'user_product_detail_page.php?id=' + productId;
        }
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>