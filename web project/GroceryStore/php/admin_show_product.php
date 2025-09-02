<?php
// THIS MUST BE THE VERY FIRST LINE - NO WHITESPACE BEFORE
session_start();
require_once 'config.php';

// Handle product deletion
if (isset($_GET['delete_id']) && ctype_digit($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    
    try {
        // Get image path
        $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $image_path = $stmt->fetchColumn();
        
        // Delete image file if exists
        if ($image_path && file_exists($image_path)) {
            if (!unlink($image_path)) {
                throw new Exception("Failed to delete image file");
            }
        }
        
        // Delete product from database
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        if (!$stmt->execute([$id])) {
            throw new Exception("Failed to delete product");
        }
        
        $_SESSION['message'] = "Product deleted successfully";
        $_SESSION['message_type'] = 'success';
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    
    // Must redirect before any output
    header("Location: admin_products.php");
    exit();
}

// Get all products
try {
    $products = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Get session messages if they exist
$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';
unset($_SESSION['message'], $_SESSION['message_type']);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management</title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/StyleSheet.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="dashboard">
        <h1 class="title">MANAGE PRODUCTS</h1>
        
        <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="center-buttons">
            <a href="add_product.php" class="btn">Add New Product</a>
        </div>
        
        <div class="products-container">
            <?php if ($products && $products->rowCount() > 0): ?>
                <?php foreach ($products as $product): ?>
                <div class="product-box">
                    <img src="<?= htmlspecialchars($product['image']) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         class="product-img"
                         onerror="this.src='images/default-product.jpg'">
                    <div class="product-info">
                        <div class="product-price"><?= number_format($product['price'], 2) ?>/-</div>
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                        <p class="product-description"><?= htmlspecialchars($product['details']) ?></p>
                    </div>
                    <div class="product-actions">
                        <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn update-btn">Edit</a>
                        <a href="admin_products.php?delete_id=<?= $product['id'] ?>" 
                           class="btn delete-btn"
                           onclick="return confirm('Delete <?= addslashes($product['name']) ?>?')">Delete</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-products">
                    <p>No products found. Add your first product!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
</body>
</html>