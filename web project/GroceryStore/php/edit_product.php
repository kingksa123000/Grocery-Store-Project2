<?php
// THIS MUST BE THE VERY FIRST LINE - NO WHITESPACE BEFORE
session_start();
require_once 'config.php';

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

// Get product data for editing
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $id = (int)$_GET['id'];

    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found");
        }
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = 'error';
        header("Location: admin_products.php");
        exit();
    }
} else {
    header("Location: admin_products.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    // Get form data
    $product['name'] = trim($_POST['product_name']);
    $product['price'] = floatval($_POST['product_price']);
    $product['category'] = trim($_POST['product_category']);
    $product['details'] = trim($_POST['product_details']);
    $product['quantity'] = intval($_POST['product_quantity']);

    // Handle file upload
    $image_updated = false;
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
                // Delete old image
                if (!empty($product['image']) && file_exists($product['image'])) {
                    unlink($product['image']);
                }
                $product['image'] = $target_path;
                $image_updated = true;
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
    } else {
        try {
            if ($image_updated) {
                $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, category = ?, details = ?, image = ?, quantity = ? WHERE id = ?");
                $stmt->execute([
                    $product['name'],
                    $product['price'],
                    $product['category'],
                    $product['details'],
                    $product['image'],
                    $product['quantity'],
                    $product['id']
                ]);
            } else {
                $stmt = $conn->prepare("UPDATE products SET name = ?, price = ?, category = ?, details = ?, quantity = ? WHERE id = ?");
                $stmt->execute([
                    $product['name'],
                    $product['price'],
                    $product['category'],
                    $product['details'],
                    $product['quantity'],
                    $product['id']
                ]);
            }

            $_SESSION['message'] = "Product updated successfully!";
            $_SESSION['message_type'] = 'success';
            header("Location: admin_products.php");
            exit();
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
            $message_type = 'error';
        }
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
    <title>Update Product</title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/StyleSheet.css">
    <link rel="stylesheet" href="update_product_styles.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="dashboard">
        <div class="update_product_form">
            <h1 class="title">UPDATE PRODUCT</h1>

            <?php if (!empty($message)): ?>
                <div class="message <?= $message_type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                <div class="update_product_form-row">
                    <span class="update_product_form-label">Product Name:</span>
                    <input type="text" class="update_product_form-input" name="product_name"
                           value="<?= htmlspecialchars($product['name']) ?>" required>
                </div>

                <div class="update_product_form-row">
                    <span class="update_product_form-label">Price:</span>
                    <input type="number" class="update_product_form-input" name="product_price"
                           value="<?= htmlspecialchars($product['price']) ?>" min="0" step="0.01" required>
                </div>

                <div class="update_product_form-row">
                    <span class="update_product_form-label">Quantity:</span>
                    <input type="number" class="update_product_form-input" name="product_quantity"
                           value="<?= htmlspecialchars($product['quantity']) ?>" min="0" required>
                </div>

                <div class="update_product_form-row">
                    <span class="update_product_form-label">Category:</span>
                    <select class="update_product_form-input" name="product_category" required>
                        <option value="">-- Select Category --</option>
                        <option value="meat" <?= $product['category'] == 'meat' ? 'selected' : '' ?>>Meat</option>
                        <option value="fruits" <?= $product['category'] == 'fruits' ? 'selected' : '' ?>>Fruits</option>
                        <option value="vegetables" <?= $product['category'] == 'vegetables' ? 'selected' : '' ?>>Vegetables</option>
                        <option value="chicken" <?= $product['category'] == 'chicken' ? 'selected' : '' ?>>Chicken</option>
                        <option value="fish" <?= $product['category'] == 'fish' ? 'selected' : '' ?>>Fish</option>
                    </select>
                </div>

                <div class="update_product_form-row">
                    <span class="update_product_form-label">Product Image:</span>
                    <input type="file" class="update_product_form-input" name="product_image" accept="image/*">
                    <?php if (!empty($product['image'])): ?>
                        <span class="update_product_form-label">Current Image:</span>
                        <img src="<?= htmlspecialchars($product['image']) ?>" class="update_product_current-image"
                             onerror="this.src='images/default-product.jpg'">
                    <?php endif; ?>
                </div>

                <div class="update_product_form-row">
                    <span class="update_product_form-label">Product Details:</span>
                    <textarea class="update_product_form-input" name="product_details" rows="5"><?= htmlspecialchars($product['details']) ?></textarea>
                </div>

                <div class="update_product_form-actions">
                    <button type="submit" class="update_product_btn update_product_update-btn" name="update_product">Update Product</button>
                    <a href="admin_products.php" class="update_product_btn update_product_cancel-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>