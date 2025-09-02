<?php
session_start();
require_once 'config.php';

// Redirect if not admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$message = ""; // Initialize message variable

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['new_status'];
    $current_status = $_POST['current_status'];

    try {
        // Start transaction
        $conn->beginTransaction();

        // Update order status
        $update_order = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        $update_order->execute([$new_status, $order_id]);

        // Handle stock updates based on status change
        // If changing from Pending to Shipped, reduce product quantities
        if ($current_status == 'Pending' && $new_status == 'Shipped') {
            // Get order items
            $order_items = $conn->prepare("
                SELECT product_id, quantity
                FROM order_items
                WHERE order_id = ?
            ");
            $order_items->execute([$order_id]);
            $items = $order_items->fetchAll(PDO::FETCH_ASSOC);

            // Update each product's quantity
            foreach ($items as $item) {
                $update_product = $conn->prepare("
                    UPDATE products
                    SET quantity = quantity - ?
                    WHERE id = ?
                ");
                $update_product->execute([$item['quantity'], $item['product_id']]);
            }
        }
        // If changing from Shipped back to Cancelled, restock product quantities
         elseif ($current_status == 'Shipped' && $new_status == 'Cancelled') {
             // Get order items
             $order_items = $conn->prepare("
                 SELECT product_id, quantity
                 FROM order_items
                 WHERE order_id = ?
             ");
             $order_items->execute([$order_id]);
             $items = $order_items->fetchAll(PDO::FETCH_ASSOC);

             // Restock each product's quantity if cancelled after shipping
             foreach ($items as $item) {
                 $update_product = $conn->prepare("
                     UPDATE products
                     SET quantity = quantity + ?
                     WHERE id = ?
                 ");
                 $update_product->execute([$item['quantity'], $item['product_id']]);
             }
         }


        // Commit transaction
        $conn->commit();
        $message = "Order #$order_id status updated to $new_status!";

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollBack();
        $message = "Error updating order: " . $e->getMessage();
    }
    // Redirect to prevent form resubmission on refresh
    header("Location: admin_orders.php");
    exit();
}

// Fetch pending orders
$pending_orders_query = "
    SELECT o.*
    FROM orders o
    WHERE o.payment_status = 'Pending'
    ORDER BY o.OrderDate DESC
";
$pending_orders_stmt = $conn->prepare($pending_orders_query);
$pending_orders_stmt->execute();
$pending_orders = $pending_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all orders for management
$all_orders_query = "
    SELECT o.*
    FROM orders o
    ORDER BY o.OrderDate DESC
    LIMIT 50
";
$all_orders_stmt = $conn->prepare($all_orders_query);
$all_orders_stmt->execute();
$all_orders = $all_orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
    <link rel="stylesheet" href="\web project\GroceryStore\css\stylesheet.css"> 
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="orders_container">
        <h1 class="orders_title">Orders Management</h1>

        <?php if (!empty($message)): ?>
            <div class="orders_message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="orders_tabs">
            <button class="orders_tab active" onclick="openTab('pending')">Pending Orders</button>
            <button class="orders_tab" onclick="openTab('all')">All Orders</button>
        </div>

        <div id="pending" class="orders_tab_content" style="display: block;">
            <?php if (empty($pending_orders)): ?>
                <p class="orders_empty">No pending orders</p>
            <?php else: ?>
                <div class="orders_list">
                    <?php foreach ($pending_orders as $order): ?>
                        <div class="orders_order">
                            <div class="orders_order_header">
                                <span class="orders_order_id">Order #<?= $order['id'] ?></span>
                                <span class="orders_order_date"><?= date('M j, Y g:i A', strtotime($order['OrderDate'])) ?></span>
                                <span class="orders_order_status" style="color: #e67e22;">Pending</span>
                                <span class="orders_order_total">$<?= number_format($order['total_price'], 2) ?></span>
                                <span class="orders_customer_id">User ID: <?= $order['user_id'] ?></span>
                            </div>

                            <div class="orders_order_details">
                                <div class="admin_user_details">
                                    <h4>Customer Details:</h4>
                                    <p>Name: <?= htmlspecialchars($order['name']) ?></p>
                                    <p>Number: <?= htmlspecialchars($order['number']) ?></p>
                                    <p>Email: <?= htmlspecialchars($order['email']) ?></p>
                                    <p>City: <?= htmlspecialchars($order['city']) ?></p>
                                    <p>Address: <?= htmlspecialchars($order['address']) ?></p>
                                </div>

                                <div class="orders_items">
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
                                            <div class="orders_item">
                                                <span><?= htmlspecialchars($item['product_name']) ?></span>
                                                <span>Qty: <?= $item['quantity'] ?></span>
                                                <span>Price: $<?= number_format($item['price'], 2) ?></span>
                                            </div>
                                        <?php endforeach;
                                    } catch (PDOException $e) {
                                         echo "<p class='orders_message error'>Error fetching order items: " . htmlspecialchars($e->getMessage()) . "</p>";
                                    }
                                    ?>
                                </div>
                            </div>

                            <form method="POST" class="orders_actions">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="hidden" name="current_status" value="<?= $order['payment_status'] ?>">
                                <select name="new_status" class="orders_status_select">
                                    <option value="Shipped">Mark as Shipped</option>
                                    <option value="Cancelled">Cancel Order</option>
                                </select>
                                <button type="submit" name="update_status" class="orders_update_btn">Update Status</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div id="all" class="orders_tab_content" style="display: none;">
            <?php if (empty($all_orders)): ?>
                <p class="orders_empty">No orders found</p>
            <?php else: ?>
                <div class="orders_list">
                    <?php foreach ($all_orders as $order): ?>
                        <div class="orders_order">
                            <div class="orders_order_header">
                                <span class="orders_order_id">Order #<?= $order['id'] ?></span>
                                <span class="orders_order_date"><?= date('M j, Y g:i A', strtotime($order['OrderDate'])) ?></span>
                                <span class="orders_order_status" style="color: <?= $order['payment_status'] == 'Pending' ? '#e67e22' : ($order['payment_status'] == 'Shipped' ? '#2ecc71' : '#e74c3c') ?>">
                                    <?= $order['payment_status'] ?>
                                </span>
                                <span class="orders_order_total">$<?= number_format($order['total_price'], 2) ?></span>
                                <span class="orders_customer_id">User ID: <?= $order['user_id'] ?></span>
                            </div>

                            <div class="orders_order_details">
                                <div class="admin_user_details">
                                    <h4>Customer Details:</h4>
                                    <p>Name: <?= htmlspecialchars($order['name']) ?></p>
                                    <p>Number: <?= htmlspecialchars($order['number']) ?></p>
                                    <p>Email: <?= htmlspecialchars($order['email']) ?></p>
                                    <p>City: <?= htmlspecialchars($order['city']) ?></p>
                                    <p>Address: <?= htmlspecialchars($order['address']) ?></p>
                                </div>

                                <div class="orders_items">
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
                                            <div class="orders_item">
                                                <span><?= htmlspecialchars($item['product_name']) ?></span>
                                                <span>Qty: <?= $item['quantity'] ?></span>
                                                <span>Price: $<?= number_format($item['price'], 2) ?></span>
                                            </div>
                                        <?php endforeach;
                                    } catch (PDOException $e) {
                                         echo "<p class='orders_message error'>Error fetching order items: " . htmlspecialchars($e->getMessage()) . "</p>";
                                    }
                                    ?>
                                </div>
                            </div>
                            </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            // Hide all tab content
            document.querySelectorAll('.orders_tab_content').forEach(tab => {
                tab.style.display = 'none';
            });

            // Remove active class from all tabs
            document.querySelectorAll('.orders_tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show the selected tab and mark its button as active
            document.getElementById(tabName).style.display = 'block';
            // Find the button that corresponds to the clicked tab and add the 'active' class
            document.querySelector(`.orders_tabs button[onclick*="'${tabName}'"]`).classList.add('active');
        }

         // Set the active tab on page load based on URL hash or default
         document.addEventListener('DOMContentLoaded', () => {
             const urlParams = new URLSearchParams(window.location.search);
             const tab = urlParams.get('tab');
             if (tab) {
                 // Find the button for the tab and trigger its click
                  const tabButton = document.querySelector(`.orders_tabs button[onclick*="'${tab}'"]`);
                  if(tabButton){
                     tabButton.click();
                  } else {
                      // Default to 'pending' if hash doesn't match a tab
                      openTab('pending');
                  }
             } else {
                 // Default to 'pending' tab if no hash
                 openTab('pending');
             }
         });
    </script>
</body>
</html>
