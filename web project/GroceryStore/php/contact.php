<?php
session_start();
require_once 'config.php';

// Redirect if not logged in (neither user nor admin)
// The contact page requires a logged-in user or admin to associate the message
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? null; // Will be null if admin is logged in
$admin_id = $_SESSION['admin_id'] ?? null; // Will be null if user is logged in

$message = "";
$message_type = "";

// --- Fetch User/Admin Data to Pre-fill Form ---
$logged_in_user_name = '';
$logged_in_user_email = '';

// Determine the ID to use for fetching data (user_id takes precedence if both are set, though typically only one will be)
$current_user_db_id = $user_id ?? $admin_id;

if ($current_user_db_id !== null) {
    try {
        // Fetch name and email for the logged-in user or admin
        $stmt = $conn->prepare("SELECT Name, Email FROM users WHERE id = :id");
        // FIX: Corrected variable name from $current_user_db_db_id to $current_user_db_id
        $stmt->bindParam(':id', $current_user_db_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_data) {
            $logged_in_user_name = $user_data['Name'] ?? '';
            $logged_in_user_email = $user_data['Email'] ?? '';
        } else {
            // This case should ideally not happen if user_id/admin_id is valid
            // but handle it gracefully.
            $message = "Could not retrieve your profile data.";
            $message_type = "warning";
        }
    } catch (PDOException $e) {
        // Temporarily displaying the full error message for debugging
        // Remove or comment out this line and uncomment the lines below once fixed
        echo "<div class='user_contact_message error'>Database error fetching user data: " . htmlspecialchars($e->getMessage()) . "</div>";
        // $message = "Database error fetching user data.";
        // $message_type = "error";
    }
}
// --- End Fetch User/Admin Data ---


// Handle form submission
if (isset($_POST['submit_message'])) {
    // Sanitize input
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $user_message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

    // --- Get name and email directly from fetched data (since fields are readonly) ---
    // Re-fetch user data here to ensure we have the latest name/email from the DB
    // This prevents relying on potentially stale data from the initial page load
    $submitted_name = '';
    $submitted_email = '';
     $current_user_db_id_on_submit = $user_id ?? $admin_id; // Determine ID again on submit

     if ($current_user_db_id_on_submit !== null) {
         try {
            $stmt_fetch_on_submit = $conn->prepare("SELECT Name, Email FROM users WHERE id = :id");
            $stmt_fetch_on_submit->bindParam(':id', $current_user_db_id_on_submit, PDO::PARAM_INT);
            $stmt_fetch_on_submit->execute();
            $user_data_on_submit = $stmt_fetch_on_submit->fetch(PDO::FETCH_ASSOC);

            if ($user_data_on_submit) {
                $submitted_name = $user_data_on_submit['Name'] ?? '';
                $submitted_email = $user_data_on_submit['Email'] ?? '';
            }
         } catch (PDOException $e) {
             // Log this error or handle it appropriately
             error_log("Database error fetching user data on submit: " . $e->getMessage());
             // Fallback to using the data from initial load if re-fetch fails
             $submitted_name = $logged_in_user_name;
             $submitted_email = $logged_in_user_email;
         }
     } else {
         // Should not happen due to initial redirect, but as a safeguard
         $submitted_name = $logged_in_user_name;
         $submitted_email = $logged_in_user_email;
     }
    // --- End getting name and email from fetched data ---


    if (empty($subject) || empty($user_message) || empty($submitted_name) || empty($submitted_email)) {
        // Adjusted message as name/email are now fetched, not user input
        $message = "Subject and Message are required. Could not retrieve your name or email.";
        $message_type = "error";
    } else {
        try {
            // Insert the message into the database
            // The user_id will be stored, and will be NULL if an admin sent the message
            $stmt = $conn->prepare("INSERT INTO contact_messages (user_id, name, email, subject, message, created_at) VALUES (:user_id, :name, :email, :subject, :message_text, NOW())");
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT); // Bind user_id (will be NULL for admin)
            $stmt->bindParam(':name', $submitted_name, PDO::PARAM_STR); // Use fetched name
            $stmt->bindParam(':email', $submitted_email, PDO::PARAM_STR); // Use fetched email
            $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
            $stmt->bindParam(':message_text', $user_message, PDO::PARAM_STR); // Use a different parameter name than the variable
            $stmt->execute();

            $message = "Message sent successfully!";
            $message_type = "success";

            // Clear subject and message fields after successful submission
            if ($message_type === "success") {
                 $subject = '';
                 $user_message = '';
            }


        } catch (PDOException $e) {
            $message = "Database error sending message: " . $e->getMessage();
            $message_type = "error";
        }
    }
}

// Get session messages if they exist and clear them
// This is useful if redirected from another page with a message
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
    <title>Contact Us</title>
    <link rel="stylesheet" href="\web project\GroceryStore\css\StyleSheet.css">
    <style>
        /* Basic styling for messages */
        .user_contact_message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .user_contact_message.success {
            color: green;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .user_contact_message.error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
         .user_contact_message.warning {
            color: #856404;
            background-color: #fff3cd;
            border-color: #ffeeba;
         }

         .user_contact_container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }

        .user_contact_title {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .user_contact_form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .user_contact_form input[type="text"],
        .user_contact_form input[type="email"],
        .user_contact_form textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding in width */
        }

        /* Style for readonly inputs */
        .user_contact_form input[type="text"][readonly],
        .user_contact_form input[type="email"][readonly] {
            background-color: #eee; /* Grey out the background */
            cursor: not-allowed; /* Indicate it's not editable */
        }


        .user_contact_submit {
            display: block; /* Make button full width */
            width: 100%;
            padding: 10px;
            background-color: #2ecc71; /* Green */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .user_contact_submit:hover {
            background-color: #27ae60; /* Darker green */
        }

        .user_contact_info_message {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e9e9e9; /* Light grey background */
            border-left: 4px solid #555; /* Dark grey border */
            border-radius: 4px;
            font-size: 0.95em;
            color: #333;
        }
         .user_contact_info_message a {
             color: #007bff; /* Blue link color */
             text-decoration: none;
         }
         .user_contact_info_message a:hover {
             text-decoration: underline;
         }


    </style>
</head>
<body>
    <?php include 'user_header.php'; ?>

    <div class="user_contact_container">
        <h1 class="user_contact_title">Contact Us</h1>

        <?php if (!empty($message)): ?>
            <div class="user_contact_message <?= htmlspecialchars($message_type) ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($user_id !== null): // Only show this message for regular users ?>
            <div class="user_contact_info_message">
                Your name and email are pre-filled from your profile. To change them, please go to your <a href="user_update_profile.php">Update Profile</a> page. You can find the link in the navigation bar.
            </div>
        <?php endif; ?>


        <form method="post" class="user_contact_form" onsubmit="return validateContactForm()">
             <label for="name">Name:</label>
             <input type="text" name="name" id="name" value="<?= htmlspecialchars($logged_in_user_name) ?>" required readonly>

             <label for="email">Email:</label>
             <input type="email" name="email" id="email" value="<?= htmlspecialchars($logged_in_user_email) ?>" required readonly>

            <label for="subject">Subject:</label>
            <input type="text" name="subject" id="subject" value="<?= htmlspecialchars($subject ?? '') ?>" required>

            <label for="message">Message:</label>
            <textarea name="message" id="message" rows="5" required><?= htmlspecialchars($user_message ?? '') ?></textarea>

            <button type="submit" name="submit_message" class="user_contact_submit">Send Message</button>
        </form>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        function validateContactForm() {
            // No need to get name and email values as they are readonly and pre-filled
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();

            // Only check subject and message for emptiness
            if (subject === '' || message === '') {
                alert('Please fill in the Subject and Message fields.');
                return false;
            }

            // Email validation is not needed here as the field is readonly and pre-filled from DB
            // The server-side should trust the email associated with the user_id/admin_id

            return true;
        }
    </script>
</body>
</html>
