<?php
include "config.php";
session_start();

if (isset($_POST["submit"])) {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_STRING);
    $pass = filter_var($_POST["password"], FILTER_SANITIZE_STRING);

    $select = $conn->prepare("SELECT * FROM users WHERE email=?");
    $select->execute([$email]);
    $row = $select->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        if (password_verify($pass, $row['Password'])) {
            if ($row['user_type'] == 'admin') {
                $_SESSION['admin_id'] = $row['id'];
                if (!isset($_SESSION['admin_id'])) {
                    die("Session failed to start.");
                }
                header("Location: Admin.php");
                exit();
            } elseif ($row['user_type'] == 'user') {
                $_SESSION['user_id'] = $row['id'];
                if (!isset($_SESSION['user_id'])) {
                    die("Session failed to start.");
                }
                header("Location: Home.php");
                exit();
            } else {
                $_SESSION['message'][] = "User type not recognized.";
            }
        } else {
            $_SESSION['message'][] = "Incorrect password.";
        }
    } else {
        $_SESSION['message'][] = "No user found with this email.";
    }

    // Redirect to prevent form resubmission on refresh
    header("Location: login.php");
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
    <title>Login - Grocery Store</title>
    <link rel="stylesheet" href="/WEB PROJECT/GroceryStore/css/login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="store-header">
            <img src="/web project/GroceryStore/images/logo.jpg" alt="Al-Rakah Store Logo" class="store-logo">

        </div>
        <div class="login-container">
            <h2>Login</h2>
            <form action="" method="POST" onsubmit="return validateForm()">
                <label for="email">Email:</label>
                <input class="input" type="email" id="email" name="email" required placeholder="Enter your email">
                <div class="error-message" id="email-error"></div>

                <label for="password">Password:</label>
                <input class="input" type="password" id="password" name="password" required placeholder="Enter your password">
                <div class="error-message" id="password-error"></div>

                <?php if (!empty($messages)): ?>
                    <div class='message'>
                        <?php foreach ($messages as $msg): ?>
                            <span><?php echo $msg; ?></span>
                        <?php endforeach; ?>
                        <i class='fas fa-times' onclick='this.parentElement.remove();'></i>
                    </div>
                <?php endif; ?>

                <input type="submit" value="Login" name="submit" class="btn">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </form>
        </div>
    </div>

    <script>
        function validateForm() {
            let email = document.getElementById("email").value.trim();
            let password = document.getElementById("password").value.trim();
            let emailError = document.getElementById("email-error");
            let passwordError = document.getElementById("password-error");
            let isValid = true;

            emailError.textContent = "";
            passwordError.textContent = "";

            if (email === "") {
                emailError.textContent = "Email is required.";
                isValid = false;
            } else if (!/^\S+@\S+\.\S+$/.test(email)) {
                emailError.textContent = "Invalid email format.";
                isValid = false;
            }

            if (password === "") {
                passwordError.textContent = "Password is required.";
                isValid = false;
            }

            return isValid;
        }
    </script>
</body>
</html>