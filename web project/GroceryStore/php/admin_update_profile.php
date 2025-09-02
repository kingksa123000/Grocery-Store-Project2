<?php
include 'config.php';
session_start();

// Strict admin verification
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Verify user is actually an admin
$admin_id = $_SESSION['admin_id'];
try {
    $query = $conn->prepare("SELECT user_type FROM users WHERE id = ?");
    $query->execute([$admin_id]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['user_type'] !== 'admin') {
        // If not an admin, destroy session and redirect
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    echo "Database error during admin verification: " . $e->getMessage();
    exit(); // Exit to prevent further execution
}


$message = ''; 

// Fetch current admin data
try {
    
    $select = $conn->prepare("SELECT Name, Phone, Email, City, Address, Password FROM `users` WHERE `id` = ?");
    $select->execute([$admin_id]);
    $current_user = $select->fetch(PDO::FETCH_ASSOC);

    if (!$current_user) {
        // If user data not found, something is wrong with the session or database
        $message = "Could not fetch admin data.";
    }
} catch (PDOException $e) {
    $message = "Database error fetching admin data: " . $e->getMessage();
}


// Handle form submission
if (isset($_POST['update_profile'])) {
    // Sanitize and filter input data
    $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING); 
    $city = filter_var($_POST['city'], FILTER_SANITIZE_STRING);   
    $address = filter_var($_POST['address'], FILTER_SANITIZE_STRING); 

    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($username) || empty($email) || empty($phone) || empty($city) || empty($address)) {
        $message = "All fields are required!";
    }
    // Check if changing password
    elseif (!empty($new_password)) {
        // Verify old password
        if (!password_verify($old_password, $current_user['Password'])) {
            $message = "Old password is incorrect!";
        }
        // Check if new passwords match
        elseif ($new_password !== $confirm_password) {
            $message = "New passwords don't match!";
        }
        // Update with new password and other fields
        else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            try {
                
                $update = $conn->prepare("UPDATE `users` SET `Name` = ?, `Phone` = ?, `Email` = ?, `City` = ?, `Address` = ?, `Password` = ? WHERE `id` = ?");
                $update->execute([$username, $phone, $email, $city, $address, $hashed_password, $admin_id]);
                $message = "Profile updated successfully!";
            } catch (PDOException $e) {
                 $message = "Database error updating profile with password: " . $e->getMessage();
            }
        }
    }
    // Update without changing password
    else {
        // Verify old password if provided (for non-password changes
         if (!empty($old_password) && !password_verify($old_password, $current_user['Password'])) {
             $message = "Current password is incorrect!";
         } else {
            try {
                
                $update = $conn->prepare("UPDATE `users` SET `Name` = ?, `Phone` = ?, `Email` = ?, `City` = ?, `Address` = ? WHERE `id` = ?");
                $update->execute([$username, $phone, $email, $city, $address, $admin_id]);
                $message = "Profile updated successfully!";
            } catch (PDOException $e) {
                 $message = "Database error updating profile: " . $e->getMessage();
            }
         }
    }

    // Refresh admin data after update attempt to show latest info
    try {
        $select->execute([$admin_id]);
        $current_user = $select->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
         // Handle error fetching data after update
         $message .= " Error refreshing data: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Admin Profile</title>
    <link rel="stylesheet" href="\web project\GroceryStore\css\StyleSheet.css"> 
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="update_admin_form_container">
        <h1 class="update_admin_form_title">UPDATE ADMIN PROFILE</h1>

        <?php if (!empty($message)): ?>
            <div class="update_admin_form_message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateAdminProfileForm()">
            <div class="update_admin_form_row">
                <label class="update_admin_form_label" for="username">Username:</label>
                <input type="text" class="update_admin_form_input" id="username" name="username"
                       value="<?php echo htmlspecialchars($current_user['Name'] ?? ''); ?>" required>
            </div>

            <div class="update_admin_form_row">
                <label class="update_admin_form_label" for="email">Email:</label>
                <input type="email" class="update_admin_form_input" id="email" name="email"
                       value="<?php echo htmlspecialchars($current_user['Email'] ?? ''); ?>" required>
            </div>

            <div class="update_admin_form_row">
                <label class="update_admin_form_label" for="phone">Phone:</label>
                <input type="text" class="update_admin_form_input" id="phone" name="phone"
                       value="<?php echo htmlspecialchars($current_user['Phone'] ?? ''); ?>" required>
            </div>

            <div class="update_admin_form_row">
                <label class="update_admin_form_label" for="city">City:</label>
                <input type="text" class="update_admin_form_input" id="city" name="city"
                       value="<?php echo htmlspecialchars($current_user['City'] ?? ''); ?>" required>
            </div>

            <div class="update_admin_form_row">
                <label class="update_admin_form_label" for="address">Address:</label>
                <input type="text" class="update_admin_form_input" id="address" name="address"
                       value="<?php echo htmlspecialchars($current_user['Address'] ?? ''); ?>" required>
            </div>

            <div class="update_admin_form_row">
                <label class="update_admin_form_label" for="old_password">Old Password:</label>
                <input type="password" class="update_admin_form_input" id="old_password" name="old_password"
                       placeholder="enter previous password">
            </div>

            <div class="update_admin_form_row">
                <label class="update_admin_form_label" for="new_password">New Password:</label>
                <input type="password" class="update_admin_form_input" id="new_password" name="new_password"
                       placeholder="enter new password">
            </div>

            <div class="update_admin_form_row">
                <label class="update_admin_form_label" for="confirm_password">Confirm Password:</label>
                <input type="password" class="update_admin_form_input" id="confirm_password" name="confirm_password"
                       placeholder="confirm new password">
            </div>

            <div class="update_admin_form_actions center-buttons">
                <a href="Admin.php" class="update_admin_form_btn">Go Back</a>
                <button type="submit" class="update_admin_form_btn" name="update_profile">Update Profile</button>
            </div>
        </form>
    </div>

    <script>
        function validateAdminProfileForm() {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const city = document.getElementById('city').value.trim();
            const address = document.getElementById('address').value.trim();
            const oldPassword = document.getElementById('old_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Basic required field check
            if (username === '' || email === '' || phone === '' || city === '' || address === '') {
                alert('Please fill in all required fields.');
                return false;
            }

            // Password validation if new password is provided
            if (newPassword !== '') {
                if (oldPassword === '') {
                    alert('Please enter your old password to set a new one.');
                    return false;
                }
                if (newPassword !== confirmPassword) {
                    alert('New passwords do not match.');
                    return false;
                }
            }

            return true; 
        }
    </script>
</body>
</html>
