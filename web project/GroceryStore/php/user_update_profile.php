<?php
session_start();
require_once 'config.php';

// Strict user verification
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verify user exists
$user_id = $_SESSION['user_id'];
try {
    $query = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $query->execute([$user_id]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // If user data not found, something is wrong with the session or database
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    // Handle database error during user verification
    echo "Database error during user verification: " . htmlspecialchars($e->getMessage());
    exit();
}


$message = '';
$message_type = ''; 

// Fetch current user data
try {
    
    $select = $conn->prepare("SELECT Name, Phone, Email, City, Address, Password FROM `users` WHERE `id` = ?");
    $select->execute([$user_id]);
    $current_user = $select->fetch(PDO::FETCH_ASSOC);

    if (!$current_user) {
         // If user data not found, something is wrong with the session or database
        $message = "Could not fetch user data.";
        $message_type = "error"; 
    }

} catch (PDOException $e) {
    $message = "Database error fetching user data: " . htmlspecialchars($e->getMessage());
    $message_type = "error"; 
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

    
    $update_successful = false;

    // Validate inputs
    if (empty($username) || empty($email) || empty($phone) || empty($city) || empty($address)) {
        $message = "All fields are required!";
        $message_type = "error";
    }
    // Check if changing password
    elseif (!empty($new_password)) {
        // Verify old password
        if (!password_verify($old_password, $current_user['Password'])) {
            $message = "Current password is incorrect!";
            $message_type = "error";
        }
        // Check if new passwords match
        elseif ($new_password !== $confirm_password) {
            $message = "New passwords don't match!";
            $message_type = "error";
        }
        // Update with new password and other fields
        else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            try {
                
                $update = $conn->prepare("UPDATE `users` SET `Name` = ?, `Phone` = ?, `Email` = ?, `City` = ?, `Address` = ?, `Password` = ? WHERE `id` = ?");
                if ($update->execute([$username, $phone, $email, $city, $address, $hashed_password, $user_id])) {
                    $message = "Profile updated successfully!";
                    $message_type = "success";
                    $update_successful = true;
                } else {
                    $message = "Error updating profile!";
                    $message_type = "error";
                }
            } catch (PDOException $e) {
                 // Catch specific duplicate email error
                 if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), "'Email'") !== false) {
                     $message = "The email address '$email' is already in use. Please use a different email.";
                     $message_type = "error";
                 } else {
                    // Handle other database errors
                    $message = "Database error updating profile with password: " . htmlspecialchars($e->getMessage());
                    $message_type = "error";
                 }
            }
        }
    }
    // Update without changing password
    else {
        // Verify old password if provided
         if (!empty($old_password) && !password_verify($old_password, $current_user['Password'])) {
             $message = "Current password is incorrect!";
             $message_type = "error";
         } else {
            try {
                $update = $conn->prepare("UPDATE `users` SET `Name` = ?, `Phone` = ?, `Email` = ?, `City` = ?, `Address` = ? WHERE `id` = ?");
                if ($update->execute([$username, $phone, $email, $city, $address, $user_id])) {
                    $message = "Profile updated successfully!";
                    $message_type = "success";
                    $update_successful = true;
                } else {
                    $message = "Error updating profile!";
                    $message_type = "error";
                }
            } catch (PDOException $e) {
                 // Catch specific duplicate email error
                 if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && strpos($e->getMessage(), "'Email'") !== false) {
                     $message = "The email address '$email' is already in use. Please use a different email.";
                     $message_type = "error";
                 } else {
                    // Handle other database errors
                    $message = "Database error updating profile: " . htmlspecialchars($e->getMessage());
                    $message_type = "error";
                 }
            }
         }
    }

    if ($message_type !== "error") { // Re-fetch only if no database error occurred during update
        try {
            $select->execute([$user_id]);
            $current_user = $select->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
             // Handle error fetching data after update
             // Append this error to the existing message if one exists
             $fetch_error_message = " Error refreshing data: " . htmlspecialchars($e->getMessage());
             if (!empty($message)) {
                 $message .= $fetch_error_message;
             } else {
                 $message = "Profile updated, but failed to refresh data: " . htmlspecialchars($e->getMessage());
                 $message_type = "warning"; 
             }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Your Profile</title>
    <link rel="stylesheet" href="\web project\GroceryStore\css\StyleSheet.css">
    </head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="update_user_form_container">
        <h1 class="update_user_form_title">UPDATE YOUR PROFILE</h1>

        <?php if (!empty($message)): ?>
            <div class="update_user_form_message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateUserProfileForm()">
            <div class="update_user_form_row">
                <label class="update_user_form_label" for="username">Username:</label>
                <input type="text" class="update_user_form_input" id="username" name="username"
                       value="<?= htmlspecialchars($current_user['Name'] ?? '') ?>" required>
            </div>

            <div class="update_user_form_row">
                <label class="update_user_form_label" for="email">Email:</label>
                <input type="email" class="update_user_form_input" id="email" name="email"
                       value="<?= htmlspecialchars($current_user['Email'] ?? '') ?>" required>
            </div>

             <div class="update_user_form_row">
                <label class="update_user_form_label" for="phone">Phone:</label>
                <input type="text" class="update_user_form_input" id="phone" name="phone"
                       value="<?= htmlspecialchars($current_user['Phone'] ?? '') ?>" required>
            </div>

            <div class="update_user_form_row">
                <label class="update_user_form_label" for="city">City:</label>
                <input type="text" class="update_user_form_input" id="city" name="city"
                       value="<?= htmlspecialchars($current_user['City'] ?? '') ?>" required>
            </div>

            <div class="update_user_form_row">
                <label class="update_user_form_label" for="address">Address:</label>
                <input type="text" class="update_user_form_input" id="address" name="address"
                       value="<?= htmlspecialchars($current_user['Address'] ?? '') ?>" required>
            </div>

            <div class="update_user_form_row">
                <label class="update_user_form_label" for="old_password">Current Password:</label>
                <input type="password" class="update_user_form_input" id="old_password" name="old_password"
                       placeholder="Enter to verify changes">
            </div>

            <div class="update_user_form_row">
                <label class="update_user_form_label" for="new_password">New Password:</label>
                <input type="password" class="update_user_form_input" id="new_password" name="new_password"
                       placeholder="Leave blank to keep current">
            </div>

            <div class="update_user_form_row">
                <label class="update_user_form_label" for="confirm_password">Confirm Password:</label>
                <input type="password" class="update_user_form_input" id="confirm_password" name="confirm_password"
                       placeholder="Required if changing password">
            </div>

            <div class="update_user_form_actions center-buttons">
                <a href="Home.php" class="update_user_form_btn">Cancel</a>
                <button type="submit" class="update_user_form_btn" name="update_profile">Save Changes</button>
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function validateUserProfileForm() {
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
                     alert('Please enter your current password to set a new one.');
                     return false;
                 }
                if (newPassword !== confirmPassword) {
                    alert('New passwords do not match.');
                    return false;
                }
            } else {
            }


            return true;
        }
    </script>
</body>
</html>
