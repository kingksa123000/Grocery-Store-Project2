<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get data from the request
$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['productId'];
$quantity = $data['quantity'];

// Validate data
if (!is_numeric($product_id) || !is_numeric($quantity) || $quantity < 1) {
    http_response_code(400); // Bad request
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
    exit();
}

// Add to cart (database)
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
        $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }

    echo json_encode(['success' => true, 'message' => 'Product added to cart.']);

} catch (PDOException $e) {
    http_response_code(500); // Internal server error
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

?>