<?php
session_start();
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$total_price = 0;
$message = null; // Initialize message variables
$message_type = null;

// Fetch cart items
try {
    $stmt = $conn->prepare("SELECT p.*, ci.quantity FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $cart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($cart_data) {
        foreach ($cart_data as $item) {
            $total_price += $item['price'] * $item['quantity'];
            // Store all product details from the join, including quantity
            $cart_items[] = $item;
        }
    }

} catch (PDOException $e) {
    $_SESSION['message'] = "Database error fetching cart items: " . $e->getMessage();
    $_SESSION['message_type'] = "error";
    header("Location: cart.php");
    exit();
}

// Handle placing the order
if (isset($_POST['place_order'])) {

    // Re-fetch cart items to ensure they haven't changed since page load
    // This is a security measure to prevent placing orders with stale cart data
     $cart_items = [];
     $total_price = 0;
     try {
         $stmt = $conn->prepare("SELECT p.*, ci.quantity FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = :user_id");
         $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
         $stmt->execute();
         $cart_data_for_order = $stmt->fetchAll(PDO::FETCH_ASSOC);

         if ($cart_data_for_order) {
             foreach ($cart_data_for_order as $item) {
                 $total_price += $item['price'] * $item['quantity'];
                 $cart_items[] = $item;
             }
         }

     } catch (PDOException $e) {
         $_SESSION['message'] = "Database error re-fetching cart items for order: " . $e->getMessage();
         $_SESSION['message_type'] = "error";
         header("Location: cart.php");
         exit();
     }


    if (empty($cart_items)) {
        $_SESSION['message'] = "Your cart is empty. Cannot place an empty order.";
        $_SESSION['message_type'] = "error";
        header("Location: checkout.php"); // Redirect back to checkout
        exit();
    }

    // --- Fetch Customer Data (Using Capitalized Column Names) ---
    $customer_name = '';
    $customer_number = '';
    $customer_email = '';
    $customer_city = '';
    $customer_address = '';

    try {
        // Using capitalized column names as per your latest code snippet for users table
        $stmt = $conn->prepare("SELECT Name, Phone, Email, City, Address FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_data) {
            // Accessing fetched data using capitalized keys
            $customer_name = $user_data['Name'];
            $customer_number = $user_data['Phone'];
            $customer_email = $user_data['Email'];
            $customer_city = $user_data['City'];
            $customer_address = $user_data['Address'];
             // Basic check if essential fields are not empty
            if (empty($customer_name) || empty($customer_number) || empty($customer_email) || empty($customer_city) || empty($customer_address)) {
                 $_SESSION['message'] = "Please complete your profile details (Name, Phone, Email, City, Address) before placing an order.";
                 $_SESSION['message_type'] = "warning";
                 header("Location: user_update_profile.php"); // Redirect to profile update page
                 exit();
            }
        } else {
            // This case should ideally not happen if user_id is from session, but good practice
            $_SESSION['message'] = "Could not retrieve your profile data. Please log in again.";
            $_SESSION['message_type'] = "error";
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Database error fetching user data: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: checkout.php");
        exit();
    }
    // --- END Fetch Customer Data ---


    $stock_errors = [];
    $sufficient_stock = true;

    try {
        $conn->beginTransaction();

        // Check stock again before placing the order within the transaction
        foreach ($cart_items as $item) {
            $product_id = $item['id']; // Use 'id' from the fetched product data
            $ordered_quantity = $item['quantity'];

            // Lock the row for updating and get current quantity and name
            $stmt = $conn->prepare("SELECT quantity, name FROM products WHERE id = :product_id FOR UPDATE");
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                $current_stock = $product['quantity'];
                $product_name = $product['name']; // Product table column names are likely lowercase

                if ($ordered_quantity > $current_stock) {
                    $stock_errors[] = [
                         // Use 'name' and 'unit' from fetched cart data
                        'name' => htmlspecialchars($item['name']) . (isset($item['unit']) ? ' (' . htmlspecialchars($item['unit']) . ')' : ''),
                        'ordered' => $ordered_quantity,
                        'stock' => number_format($current_stock, 2, '.', ''), // Format stock to avoid floating point issues
                    ];
                    $sufficient_stock = false;
                }
            } else {
                 // Product not found (shouldn't happen if cart is valid, but safe check)
                $stock_errors[] = [
                    'name' => 'Product ID: ' . htmlspecialchars($product_id),
                    'ordered' => $ordered_quantity,
                    'stock' => 0,
                ];
                $sufficient_stock = false;
            }
        }

        if (!$sufficient_stock) {
            $conn->rollBack();
            $_SESSION['message'] = "Insufficient stock for the following products: <br>"; // Use <br> for newline in HTML message
            $error_messages_html = [];
            foreach ($stock_errors as $error) {
                $error_messages_html[] = "- " . $error['name'] . ": Ordered " . htmlspecialchars($error['ordered']) . ", Available " . $error['stock'];
            }
            $_SESSION['message'] .= implode("<br>", $error_messages_html);
            $_SESSION['message_type'] = "error";
            header("Location: checkout.php");
            exit();
        }

        // If stock is sufficient, proceed with creating the order
        // --- Modified INSERT INTO orders to include customer data ---
        // Assuming orders table uses lowercase column names (more common)
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, OrderDate, payment_status, name, number, email, city, address) VALUES (:user_id, :total_price, NOW(), 'Pending', :name, :number, :email, :city, :address)"); // Assuming default payment status
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':total_price', $total_price, PDO::PARAM_STR); // Use STR for currency
        // Binding the fetched capitalized data to lowercase placeholders for the orders table
        $stmt->bindParam(':name', $customer_name, PDO::PARAM_STR);
        $stmt->bindParam(':number', $customer_number, PDO::PARAM_STR);
        $stmt->bindParam(':email', $customer_email, PDO::PARAM_STR);
        $stmt->bindParam(':city', $customer_city, PDO::PARAM_STR);
        $stmt->bindParam(':address', $customer_address, PDO::PARAM_STR);
        $stmt->execute();
        $order_id = $conn->lastInsertId();

        // Insert order items and update product quantities
        foreach ($cart_items as $item) {
            $product_id = $item['id'];
            $ordered_quantity = $item['quantity'];
            $price = $item['price']; // Use 'price' from the fetched product data
            $product_name_for_item = $item['name']; // Get the product name from the cart item

            // --- FIX: Include product_name in the INSERT INTO order_items query ---
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (:order_id, :product_id, :product_name, :quantity, :price)");
            $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->bindParam(':product_name', $product_name_for_item, PDO::PARAM_STR); // Bind the product name
            $stmt->bindParam(':quantity', $ordered_quantity, PDO::PARAM_INT);
            $stmt->bindParam(':price', $price, PDO::PARAM_STR); // Use STR for currency
            $stmt->execute();

            // Update product quantity (already checked with FOR UPDATE)
            $update_stmt = $conn->prepare("UPDATE products SET quantity = quantity - :quantity WHERE id = :product_id");
            $update_stmt->bindParam(':quantity', $ordered_quantity, PDO::PARAM_INT);
            $update_stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $update_stmt->execute();
        }

        // Empty the cart
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $conn->commit();
        $_SESSION['message'] = "Order placed successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();

    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['message'] = "Database error during order placement: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
        header("Location: checkout.php");
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
    <title>Checkout</title>
    <link rel="stylesheet" href="/web project/GroceryStore/css/stylesheet.css">
    </head>
<body>
    <?php include 'user_header.php'; ?>
    <div class="user_checkout_container">
        <h1 class="user_checkout_title">Checkout</h1>
        <?php if (!empty($message)): ?>
            <div class="user_checkout_message <?= htmlspecialchars($message_type) ?>"><?= $message ?></div>
        <?php endif; ?>
        <?php if (empty($cart_items)): ?>
            <p class="user_checkout_empty">Your cart is empty.</p>
        <?php else: ?>
             <div class="user_checkout_customer_details">
                 <h2>Your Details</h2>
                 <?php
                 // Fetch user data again to display on the checkout page
                 // This is done here to ensure the displayed data is current
                 $display_user_data = null;
                 try {
                     // Using capitalized column names as per your users table
                     $stmt = $conn->prepare("SELECT Name, Phone, Email, City, Address FROM users WHERE id = :user_id");
                     $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                     $stmt->execute();
                     $display_user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                 } catch (PDOException $e) {
                      // Handle this display error gracefully - TEMPORARILY display the full error message
                      echo "<p class='error'>Database Error fetching your details for display: " . htmlspecialchars($e->getMessage()) . "</p>";
                      // You can remove the line above and uncomment the line below once debugging is done
                      // echo "<p class='error'>Error loading your details for display.</p>";
                 }

                 if ($display_user_data): ?>
                     <p><strong>Name:</strong> <?= htmlspecialchars($display_user_data['Name']) ?></p>
                     <p><strong>Number:</strong> <?= htmlspecialchars($display_user_data['Phone']) ?></p>
                     <p><strong>Email:</strong> <?= htmlspecialchars($display_user_data['Email']) ?></p>
                     <p><strong>City:</strong> <?= htmlspecialchars($display_user_data['City']) ?></p>
                     <p><strong>Address:</strong> <?= htmlspecialchars($display_user_data['Address']) ?></p>
                      <p>Please ensure these details are correct. You can update them in your profile.</p>
                 <?php else: ?>
                      <p class='warning'>Your details could not be loaded. Please ensure your profile is complete.</p>
                      <?php if (isset($user_id)): // Provide a link to profile if user is logged in ?>
                           <p><a href="user_update_profile.php">Go to Profile</a></p>
                      <?php endif; ?>
                 <?php endif; ?>
             </div>

             <h2>Order Summary</h2>
             <div class="user_checkout_items">
                 <?php foreach ($cart_items as $item): ?>
                     <div class="user_checkout_item">
                          <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="user_checkout_item_image">
                         <div class="user_checkout_item_info">
                             <h3><?= htmlspecialchars($item['name']) ?></h3>
                             <p>Price: $<?= number_format($item['price'], 2) ?></p>
                             <p>Quantity: <span data-product-id="<?= $item['id'] ?>" data-product-name="<?= htmlspecialchars($item['name']) ?>"><?= $item['quantity'] ?></span></p>
                              <p>Subtotal: $<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                         </div>
                     </div>
                 <?php endforeach; ?>
             </div>
             <div class="user_checkout_total">
                 <p><strong>Total:</strong> $<?= number_format($total_price, 2) ?></p>
                 <div class="user_checkout_actions">
                      <a href="ShoppingCart.php" class="user_checkout_go_back">Go Back to Cart</a>
                      <form id="checkoutForm" method="post" onsubmit="return validateCheckout()">
                          <button type="submit" name="place_order" class="user_checkout_place_order">Place Order</button>
                      </form>
                 </div>
             </div>
         <?php endif; ?>
    </div>
    <?php include 'footer.php'; ?>

    <script>
             // This costumer side validation
              async function validateCheckout() {
              const cartItems = document.querySelectorAll('.user_checkout_item_info span[data-product-id]');
              const productsToCheck = [];

              cartItems.forEach(productIdElement => {
                  const productName = productIdElement.dataset.productName;
                  const productId = parseInt(productIdElement.dataset.productId);
                  const quantityElement = productIdElement.closest('.user_checkout_item_info').querySelector('p:nth-child(3) span');

                  if (productId && quantityElement) {
                   const orderedQuantity = parseInt(quantityElement.textContent);
                   productsToCheck.push({ id: productId, name: productName, ordered: orderedQuantity });
                  }
                 });

                  if (productsToCheck.length === 0) {
                   alert('Your cart is empty.');
                   return false; // Prevent form submission
                   }

               try {
                   // Make an AJAX request to check stock
                    const response = await fetch('check_stock.php', {
                    method: 'POST',
                    headers: {
                     'Content-Type': 'application/json',
                     'X-Requested-With': 'XMLHttpRequest' // Indicate it's an AJAX request
                    },
                    body: JSON.stringify({ products: productsToCheck }),
                   });

                    if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.has_error) {
                     let errorMessage = 'Insufficient stock for the following products:\n';
                     data.errors.forEach(error => {
                      errorMessage += `- ${error.name}: Ordered ${error.ordered}, Available ${error.stock}\n`;
                     });
                     alert(errorMessage);
                     return false; // Prevent form submission
                    }

                    return true; // Allow form submission if client-side check passes

                   } catch (error) {
                    console.error('Error checking stock:', error);
                    alert('An error occurred while checking stock. Please try again.');
                    return false;
                   }
     }
    </script>
</body>
</html>
