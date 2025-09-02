<?php
session_start();
require_once 'config.php';
//Check if admin session exists
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

//   confirm if you are admin or not if not it will take you back to login page
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

// Initialize variables
$message = '';
$message_type = '';
$product = [
    'id' => '',
    'name' => '',
    'price' => '',
    'category' => '',
    'details' => '',
    'image' => '',
    'quantity' => ''
];

// Handle product deletion
if (isset($_GET['delete_id']) && ctype_digit($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];

    try {
        // Get image path
        $statment = $conn->prepare("SELECT image FROM products WHERE id = ?");
        $statment->execute([$id]);
        $image_path = $statment->fetchColumn();

        // Delete image file if exists
        if ($image_path && file_exists($image_path)) {
            if (!unlink($image_path)) {
                throw new Exception("Failed to delete image file");
            }
        }

        // Delete product from database
        $statment = $conn->prepare("DELETE FROM products WHERE id = ?");
        if (!$statment->execute([$id])) {
            throw new Exception("Failed to delete product");
        }

        $_SESSION['message'] = "Product deleted successfully";
        $_SESSION['message_type'] = 'success';
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }

    header("Location: admin_products.php");
    exit();
}

// Process product form submission (add only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_product'])) {
    // Get form data
    $product['name'] = trim($_POST['product_name']);
    $product['price'] = floatval($_POST['product_price']);
    $product['quantity'] = intval($_POST['product_quantity']);
    $product['category'] = trim($_POST['product_category']);
    $product['details'] = trim($_POST['product_details']);

    // Handle file upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $image_tmp = $_FILES['product_image']['tmp_name'];
        $original_name = basename($_FILES['product_image']['name']);
        $upload_dir = 'uploads/products/';

        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $target_path = $upload_dir . uniqid() . '_' . $original_name;

        // Validate image
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($image_tmp);

        if (in_array($file_type, $allowed_types)) {
            if (move_uploaded_file($image_tmp, $target_path)) {
                $product['image'] = $target_path;
            } else {
                $message = "Error uploading image file.";
                $message_type = 'error';
            }
        } else {
            $message = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            $message_type = 'error';
        }
    }

    // Validate inputs
    if (empty($product['name'])) {
        $message = "Product name is required!";
        $message_type = 'error';
    } elseif (empty($product['price'])) {
        $message = "Product price is required!";
        $message_type = 'error';
    } elseif (empty($product['category'])) {
        $message = "Product category is required!";
        $message_type = 'error';
    } elseif (empty($product['image'])) {
        $message = "Product image is required!";
        $message_type = 'error';
    } else {
        try {
            // Add new product
            $statment = $conn->prepare("INSERT INTO products (name, price, quantity, category, details, image, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $statment->execute([
                $product['name'],
                $product['price'],
                $product['quantity'],
                $product['category'],
                $product['details'],
                $product['image'],
            ]);

            $_SESSION['message'] = "Product added successfully!";
            $_SESSION['message_type'] = 'success';
            header("Location: admin_products.php");
            exit();
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

// Handle search
$search_term = '';
if (isset($_GET['search'])) {
    $search_term = trim($_GET['search']);
}

// Get all products (or filtered products if search term is present)
try {
    if (!empty($search_term)) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE :search ORDER BY created_at DESC");
        $stmt->bindValue(':search', '%' . $search_term . '%', PDO::PARAM_STR);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $products_stmt = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
        $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
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
    <title>Product Management</title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/StyleSheet.css">
    
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="dashboard">
        <?php if (isset($_GET['action']) && $_GET['action'] == 'add'): ?>
            <div class="product-form">
                <h1 class="title">ADD NEW PRODUCT</h1>

                <?php if (!empty($message)): ?>
                    <div class="message <?= $message_type ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <span class="form-label">Product name:</span>
                        <input type="text" class="form-input" name="product_name"
                               value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>

                    <div class="form-row">
                        <span class="form-label">Price:</span>
                        <input type="number" class="form-input" name="product_price"
                               value="<?= htmlspecialchars($product['price']) ?>" min="0" step="0.01" required>
                        <span class="form-label">Quantity:</span>
                        <input type="number" class="form-input" name="product_quantity"
                               value="<?= htmlspecialchars($product['quantity']) ?>" min="0" required>
                    </div>

                    <div class="form-row">
                        <span class="form-label">Category:</span>
                        <select class="form-input" name="product_category" required>
                            <option value="">-- select category --</option>
                            <option value="meat" <?= $product['category'] == 'meat' ? 'selected' : '' ?>>Meat</option>
                            <option value="fruits" <?= $product['category'] == 'fruits' ? 'selected' : '' ?>>Fruits</option>
                            <option value="vegetables" <?= $product['category'] == 'vegetables' ? 'selected' : '' ?>>Vegetables</option>
                            <option value="chicken" <?= $product['category'] == 'chicken' ? 'selected' : '' ?>>Chicken</option>
                            <option value="fish" <?= $product['category'] == 'fish' ? 'selected' : '' ?>>Fish</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <span class="form-label">Product image:</span>
                        <input type="file" class="form-input" name="product_image" accept="image/*" required>
                    </div>

                    <div class="form-row">
                        <span class="form-label">Product details:</span>
                        <textarea class="form-input" name="product_details" rows="4"><?= htmlspecialchars($product['details']) ?></textarea>
                    </div>

                    <div class="form-actions center-buttons">
                        <button type="submit" class="btn update-btn" name="save_product">
                            Add Product
                        </button>
                        <a href="admin_products.php" class="btn delete-btn">Cancel</a>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <h1 class="title">MANAGE PRODUCTS</h1>

            <?php if (!empty($message)): ?>
                <div class="message <?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="center-buttons">
                <a href="admin_products.php?action=add" class="btn update-btn">Add New Product</a>
            </div>

            <div class="search-bar">
                <form method="GET">
                    <input type="text" class="search-input" name="search" placeholder="Search product name" value="<?= htmlspecialchars($search_term) ?>">
                    <button type="submit" class="search-button">Search</button>
                </form>
            </div>

            <div class="products-container">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-box">
                            <img src="<?= htmlspecialchars($product['image']) ?>"
                                 alt="<?= htmlspecialchars($product['name']) ?>"
                                 class="product-img"
                                 onerror="this.src='images/default-product.jpg'">
                            <div class="product-info">
                                <div class="product-price"><?= number_format($product['price'], 2) ?>$</div>
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <div class="product-category"><?= htmlspecialchars($product['category']) ?></div>
                                <p class="product-description"><?= htmlspecialchars($product['details']) ?></p>
                                <div class="product-quantity">Remaining Quantity per kg: <?= htmlspecialchars($product['quantity']) ?></div>
                            </div>
                            <div class="product-actions">
                                <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn update-btn">Update</a>
                                <a href="admin_products.php?delete_id=<?= $product['id'] ?>"
                                   class="btn delete-btn"
                                   onclick="return confirm('Delete <?= addslashes(htmlspecialchars($product['name'])) ?>?')">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <p>No products found. <a href="admin_products.php?action=add">Add your first product!</a></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>