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

$current_orders = [];
$past_orders = [];
$error_message = null;

// --- COOKIE HANDLING FOR PAST PURCHASED PRODUCT IDs AND NAMES ---
$past_purchased_products = [];
if (isset($_COOKIE['past_purchased_products'])) {
    $past_purchased_product_ids = unserialize($_COOKIE['past_purchased_products']);
    if (!empty($past_purchased_product_ids)) {
        try {
            $placeholders = implode(',', array_fill(0, count($past_purchased_product_ids), '?'));
            $stmt = $conn->prepare("SELECT id, name FROM products WHERE id IN ($placeholders)");
            $stmt->execute($past_purchased_product_ids);
            while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $past_purchased_products[$product['id']] = $product['name'];
            }
        } catch (PDOException $e) {
            $error_message = "Database Error fetching past purchased products: " . $e->getMessage();
        }
    }
}

// Function to add a product ID to the past purchases cookie
function addPastPurchasedProduct(int $productId) {
    $existing_ids = [];
    if (isset($_COOKIE['past_purchased_products'])) {
        $existing_ids = unserialize($_COOKIE['past_purchased_products']);
    }
    if (!in_array($productId, $existing_ids)) {
        $existing_ids[] = $productId;
        // Limit the number of stored product IDs to prevent overly large cookies
        if (count($existing_ids) > 10) {
            array_shift($existing_ids); // Remove the oldest
        }
        setcookie('past_purchased_products', serialize($existing_ids), time() + (86400 * 30), "/"); // Expires in 30 days
    }
}
// --- END COOKIE HANDLING ---

try {
    // Fetch current orders (Pending)
    $current_orders_query = "
        SELECT * FROM orders
        WHERE payment_status = 'Pending'
    ";
    if ($user_id !== null) {
        $current_orders_query .= " AND user_id = :user_id";
    }
    $current_orders_query .= " ORDER BY OrderDate DESC";
    $current_orders_stmt = $conn->prepare($current_orders_query);
    if ($user_id !== null) {
        $current_orders_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    $current_orders_stmt->execute();
    $current_orders = $current_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch past orders (Cancelled, Completed, Shipped)
    $past_orders_query = "
        SELECT * FROM orders
        WHERE payment_status IN ('Cancelled', 'Completed', 'Shipped')
    ";
    if ($user_id !== null) {
        $past_orders_query .= " AND user_id = :user_id";
    }
    $past_orders_query .= " ORDER BY OrderDate DESC";
    $past_orders_stmt = $conn->prepare($past_orders_query);
    if ($user_id !== null) {
        $past_orders_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    $past_orders_stmt->execute();
    $past_orders_result = $past_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
    $past_orders = $past_orders_result;

    // --- STORE PRODUCT IDs FROM PAST ORDERS IN COOKIE ---
    foreach ($past_orders as $order) {
        $items_stmt = $conn->prepare("SELECT product_id FROM order_items WHERE order_id = ?");
        $items_stmt->execute([$order['id']]);
        $items = $items_stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($items as $productId) {
            addPastPurchasedProduct($productId);
        }
    }
    // --- END STORING IN COOKIE ---

} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
}

// Handle order cancellation
if (isset($_POST['cancel_order'])) {
    $order_id_to_cancel = $_POST['order_id'];

    $where_clause = 'id = :order_id';
    $params = [':order_id' => $order_id_to_cancel];

    // For regular users, ensure they are cancelling their own order
    if ($user_id !== null) {
        $where_clause .= ' AND user_id = :user_id';
        $params[':user_id'] = $user_id;
    }

    try {
        $conn->beginTransaction(); // Start transaction

        // Check if the order status is 'Pending'
        $order_to_cancel_stmt = $conn->prepare("
            SELECT payment_status FROM orders WHERE $where_clause
        ");
        $order_to_cancel_stmt->execute($params);
        $order_to_cancel_status = $order_to_cancel_stmt->fetch(PDO::FETCH_ASSOC)['payment_status'] ?? null;

        if ($order_to_cancel_status === 'Pending') {
            // Get the items in the order to restock
            $get_items_stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = :order_id_cancel");
            $get_items_stmt->bindParam(':order_id_cancel', $order_id_to_cancel, PDO::PARAM_INT);
            $get_items_stmt->execute();
            $order_items_to_restock = $get_items_stmt->fetchAll(PDO::FETCH_ASSOC);

            // Restock the products
            foreach ($order_items_to_restock as $item) {
                $update_stock_stmt = $conn->prepare("UPDATE products SET quantity = quantity + :quantity WHERE id = :product_id");
                $update_stock_stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                $update_stock_stmt->bindParam(':product_id', $item['product_id'], PDO::PARAM_INT);
                $update_stock_stmt->execute();
            }

            // Update the order status to 'Cancelled'
            $update_stmt = $conn->prepare("
                UPDATE orders SET payment_status = 'Cancelled' WHERE $where_clause
            ");
            $update_stmt->execute($params);

            $conn->commit(); // Commit transaction

            $_SESSION['message'] = "Order #$order_id_to_cancel has been cancelled and stock has been updated.";
            $_SESSION['message_type'] = "success";
        } else {
            $conn->rollBack(); // Rollback if order is not pending
            $_SESSION['message'] = "Cannot cancel order #$order_id_to_cancel. Order is already confirmed.";
            $_SESSION['message_type'] = "error";
        }
    } catch (PDOException $e) {
        $conn->rollBack(); // Rollback on error
        $_SESSION['message'] = "Database Error during cancellation: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }

    header("Location: orders.php"); // Redirect back to my_orders
    exit();
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
    <title>My Orders</title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/orders.css">
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="user_orders_container">
        <h1 class="user_orders_title">My Orders</h1>

        <?php if (!empty($message)): ?>
            <div class="user_orders_message <?= htmlspecialchars($message_type) ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="user_orders_message error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="user_orders_tabs">
            <button class="user_orders_tab active" onclick="openTab('current')">Current Orders</button>
            <button class="user_orders_tab" onclick="openTab('past')">Past Orders</button>
        </div>

        <div id="current" class="user_orders_tab_content" style="display: block;">
            <?php if (empty($current_orders)): ?>
                <p class="user_orders_empty">No current orders</p>
            <?php else: ?>
                <div class="user_orders_list">
                    <?php foreach ($current_orders as $order): ?>
                        <div class="user_orders_order">
                            <div class="user_orders_order_header">
                                <span class="user_orders_order_id">Order #<?= $order['id'] ?></span>
                                <span class="user_orders_order_date"><?= date('M j, Y g:i A', strtotime($order['OrderDate'])) ?></span>
                                <span class="user_orders_order_status" style="color: <?= $order['payment_status'] == 'Pending' ? '#e67e22' : '#2ecc71' ?>">
                                    <?= $order['payment_status'] ?>
                                </span>
                                <span class="user_orders_order_total">$<?= number_format($order['total_price'], 2) ?></span>
                                <?php if ($admin_id !== null): ?>
                                    <span class="user_orders_customer_id">User ID: <?= $order['user_id'] ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="user_orders_order_details">
                                <div class="user_orders_summary">

                                    <span>Total Price: $<?= number_format($order['total_price'] ?? 0, 2) ?></span>
                                </div>

                                <div class="user_orders_items">
                                    <h4>Order Items:</h4>
                                    <?php
                                    try {
                                        $items_stmt = $conn->prepare("
                                            SELECT oi.*, p.name as product_name
                                            FROM order_items oi
                                            JOIN products p ON oi.product_id = p.id
                                            WHERE oi.order_id = ?
                                        ");
                                        $items_stmt->execute([$order['id']]);
                                        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($items as $item): ?>
                                            <div class="user_orders_item">
                                                <span><?= htmlspecialchars($item['product_name']) ?></span>
                                                <span style="text-align: center; width: 50px; display: inline-block;">Qty: <?= $item['quantity'] ?></span>
                                                <span>Price: $<?= number_format($item['price'], 2) ?></span>
                                            </div>
                                        <?php endforeach;
                                    } catch (PDOException $e) {
                                        echo "<p class='error'>Error fetching order items: " . htmlspecialchars($e->getMessage()) . "</p>";
                                    }
                                    ?>
                                </div>
                                <?php if ($order['payment_status'] === 'Pending'): ?>
                                    <form method="post">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="cancel_order" class="user_orders_cancel">Cancel Order</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="past" class="user_orders_tab_content" style="display: none;">
            <?php if (empty($past_orders)): ?>
                <p class="user_orders_empty">No past orders</p>
            <?php else: ?>
                <div class="user_orders_list">
                    <?php foreach ($past_orders as $order): ?>
                        <div class="user_orders_order">
                            <div class="user_orders_order_header">
                                <span class="user_orders_order_id">Order #<?= $order['id'] ?></span>
                                <span class="user_orders_order_date"><?= date('M j, Y g:i A', strtotime($order['OrderDate'])) ?></span>
                                <span class="user_orders_order_status" style="color: <?= $order['payment_status'] == 'Cancelled' ? '#e74c3c' : ($order['payment_status'] == 'Shipped' ? '#3498db' : '#2ecc71') ?>">
                                    <?= $order['payment_status'] ?>
                                </span>
                                <span class="user_orders_order_total">$<?= number_format($order['total_price'], 2) ?></span>
                                <?php if ($admin_id !== null): ?>
                                    <span class="user_orders_customer_id">User ID: <?= $order['user_id'] ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="user_orders_order_details">
                                <div class="user_orders_summary">

                                    <span>Total Price: $<?= number_format($order['total_price'] ?? 0, 2) ?></span>
                                </div>

                                <div class="user_orders_items">
                                    <h4>Order Items:</h4>
                                    <?php
                                    try {
                                        $items_stmt = $conn->prepare("
                                            SELECT oi.*, p.name as product_name
                                            FROM order_items oi
                                            JOIN products p ON oi.product_id = p.id
                                            WHERE oi.order_id = ?
                                        ");
                                        $items_stmt->execute([$order['id']]);
                                        $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

                                        foreach ($items as $item): ?>
                                            <div class="user_orders_item">
                                                <span><?= htmlspecialchars($item['product_name']) ?></span>
                                                <span style="text-align: center; width: 50px; display: inline-block;">Qty: <?= $item['quantity'] ?></span>
                                                <span>Price: $<?= number_format($item['price'], 2) ?></span>
                                            </div>
                                        <?php endforeach;
                                    } catch (PDOException $e) {
                                        echo "<p class='error'>Error fetching order items: " . htmlspecialchars($e->getMessage()) . "</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($past_purchased_products)): ?>
                <div class="user_orders_cookie_info">
                    <h3>Recently Purchased Products (from Cookie):</h3>
                    <ul>
                        <?php foreach ($past_purchased_products as $productId => $productName): ?>
                            <li>ID: <?= htmlspecialchars($productId) ?>, Name: <?= htmlspecialchars($productName) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p class="user_orders_empty">No past purchased products stored in a cookie yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            document.querySelectorAll('.user_orders_tab_content').forEach(tab => {
                tab.style.display = 'none';
            });

            document.querySelectorAll('.user_orders_tab').forEach(tab => {
                tab.classList.remove('active');
            });

            document.getElementById(tabName).style.display = 'block';
            event.currentTarget.classList.add('active');
        }
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>