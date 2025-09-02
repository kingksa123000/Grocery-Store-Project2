<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Check if product ID is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php"); // Redirect to the product catalog if no valid ID
    exit();
}

$product_id = $_GET['id'];

// Fetch product details from the database
try {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: index.php"); // Redirect if product not found
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Product Details</title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/stylesheet.css">
    
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="product-details-container">
        <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>

        <div class="product-details">
            <div class="product-image">
                <img src="<?= htmlspecialchars($product['image']) ?>"
                     alt="<?= htmlspecialchars($product['name']) ?>"
                     class="product-detail-img"
                     onerror="this.src='images/default-product.jpg'">
            </div>

            <div class="product-info">
                <div class="product-price">$<?= number_format($product['price'], 2) ?></div>
                <div class="product-category">Category: <?= htmlspecialchars($product['category']) ?></div>
                <div class="product-description">
                    <h3>Description:</h3>
                    <p><?= htmlspecialchars($product['details']) ?></p>
                </div>

                <div class="product-actions">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" value="1" min="1" class="product-quantity">
                    <button class="add-to-cart-button" onclick="addToCart(<?= $product['id'] ?>, 'quantity')">Add to Cart</button>
                    <button class="back-to-catalog" onclick="window.location.href='Home.php'">Back</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function addToCart(productId, quantityId) {
            const quantity = document.getElementById(quantityId).value;

            if (!Number.isInteger(Number(quantity)) || Number(quantity) < 1) {
                alert("Please enter a valid quantity.");
                return;
            }

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
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>