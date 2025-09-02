<?php
ob_start(); // Start output buffering
session_start();
include "config.php";

if (isset($_POST["submit"])) {
    $name = filter_var($_POST["name"], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $address = filter_var($_POST["address"], FILTER_SANITIZE_STRING);
    $city = filter_var($_POST["city"], FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST["phone"], FILTER_SANITIZE_STRING);
    $pass = trim($_POST["password"]);
    $cpass = trim($_POST["confirm_password"]);

    // Check if email already exists
    $select = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $select->execute([$email]);

    if ($select->rowCount() > 0) {
        $_SESSION['message'][] = "User email already exists!";
    } elseif ($pass !== $cpass) {
        $_SESSION['message'][] = "Confirm password does not match!";
    } else {
        // Hash password for security
        $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

        // Insert user into the database with default role 'user'
        $insert = $conn->prepare("INSERT INTO users (Name, Email, Address,city, Phone, Password, user_type) VALUES (?, ?, ?, ?, ?, ?,?)");
        $insert->execute([$name, $email, $address,$city, $phone, $hashed_pass, 'user']);

        if ($insert) {
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['message'][] = "Failed to register.";
        }
    }
    header("Location: register.php");
    exit();
}

// Retrieve and display messages from the session
$messages = isset($_SESSION['message']) ? $_SESSION['message'] : [];
unset($_SESSION['message']); // Clear the messages from the session
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Grocery Store</title>
    <link rel="stylesheet" href="/WEB PROJECT/GroceryStore/css/login.css">
</head>
<body>
    <div class="register-wrapper">
        <div class="store-header">
            <img src="/web project/GroceryStore/images/logo.jpg" alt="Al-Rakah Store Logo" class="store-logo">
        </div>
        <div class="login-container">
            <h2>Register</h2>
            <form action="" method="POST" onsubmit="return validateForm()">
                <label for="name">Full Name:</label>
                <input class="input" type="text" id="name" name="name" required placeholder="Enter your full name">
                <div class="error-message" id="name-error"></div>

                <label for="email">Email:</label>
                <input class="input" type="email" id="email" name="email" required placeholder="Enter your email">
                <div class="error-message" id="email-error"></div>

                <label for="address">Address:</label>
                <input class="input" type="text" id="address" name="address" required placeholder="Enter your address">
                <div class="error-message" id="address-error"></div>

                <label for="city">City:</label>
                <input class="input" type="text" id="city" name="city" required placeholder="Enter your city">
                <div class="error-message" id="city-error"></div>

                <label for="phone">Phone Number:</label>
                <input class="input" type="tel" id="phone" name="phone" required placeholder="Enter your phone number">
                <div class="error-message" id="phone-error"></div>

                <label for="password">Password:</label>
                <input class="input" type="password" id="password" name="password" required placeholder="Enter your password">
                <div class="error-message" id="password-error"></div>

                <label for="confirm_password">Confirm Password:</label>
                <input class="input" type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                <div class="error-message" id="confirm-password-error"></div>

                <?php if (!empty($messages)): ?>
                    <div class='message'>
                        <?php foreach ($messages as $msg): ?>
                            <span><?php echo $msg; ?></span>
                        <?php endforeach; ?>
                        <i class='fas fa-times' onclick='this.parentElement.remove();'></i>
                    </div>
                <?php endif; ?>

                <input type="submit" value="Register Now" class="btn" name="submit">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </form>
        </div>
    </div>

    <script>
        function validateForm() {
            let name = document.getElementById("name").value.trim();
            let email = document.getElementById("email").value.trim();
            let address = document.getElementById("address").value.trim();
            let phone = document.getElementById("phone").value.trim();
            let password = document.getElementById("password").value.trim();
            let confirmPassword = document.getElementById("confirm_password").value.trim();
            let nameError = document.getElementById("name-error");
            let emailError = document.getElementById("email-error");
            let addressError = document.getElementById("address-error");
            let phoneError = document.getElementById("phone-error");
            let passwordError = document.getElementById("password-error");
            let confirmPasswordError = document.getElementById("confirm-password-error");
            let isValid = true;

            nameError.textContent = "";
            emailError.textContent = "";
            addressError.textContent = "";
            phoneError.textContent = "";
            passwordError.textContent = "";
            confirmPasswordError.textContent = "";

            if (name === "") {
                nameError.textContent = "Full Name is required.";
                isValid = false;
            }

            if (email === "") {
                emailError.textContent = "Email is required.";
                isValid = false;
            } else if (!/^\S+@\S+\.\S+$/.test(email)) {
                emailError.textContent = "Invalid email format.";
                isValid = false;
            }

            if (address === "") {
                addressError.textContent = "Address is required.";
                isValid = false;
            }

            if (phone === "") {
                phoneError.textContent = "Phone Number is required.";
                isValid = false;
            } else if (!/^\d{10}$/.test(phone)) {
                phoneError.textContent = "Invalid phone number format (10 digits required).";
                isValid = false;
            }

            if (password === "") {
                passwordError.textContent = "Password is required.";
                isValid = false;
            } else if (password.length < 8) {
                passwordError.textContent = "Password must be at least 8 characters long.";
                isValid = false;
            }

            if (confirmPassword === "") {
                confirmPasswordError.textContent = "Confirm Password is required.";
                isValid = false;
            } else if (confirmPassword !== password) {
                confirmPasswordError.textContent = "Passwords do not match.";
                isValid = false;
            }

            return isValid;
        }
    </script>
</body>
</html>